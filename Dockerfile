# syntax=docker/dockerfile:1

FROM composer:2.7 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

FROM node:20 AS node_modules
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci

FROM php:8.2-fpm

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip calendar

# Install Node.js 20 LTS
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest

# Enable and configure PHP extensions (most are already installed in the base image)
RUN php -m

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY --from=node_modules /app/node_modules ./node_modules

# Copy the entire application first (including local packages like platform/)
COPY . .

# Create all necessary directories with proper permissions
RUN mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data \
        storage \
        bootstrap/cache \
    && chmod -R 775 \
        storage \
        bootstrap/cache

# Install PHP dependencies without running scripts first
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Now run the post-install scripts
RUN composer run-script post-autoload-dump

# Install JS dependencies and build assets
RUN npm ci && npm run prod

# Publish Botble CMS assets
RUN php artisan cms:publish:assets

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage symlink
RUN php artisan storage:link || true

# Cache configuration and routes (skip view:cache as it may require database access)
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan event:cache

# Nginx config
COPY ./docker/nginx.conf /etc/nginx/nginx.conf

# Create supervisor directory if it doesn't exist
RUN mkdir -p /etc/supervisor/conf.d/

# Copy supervisor config
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Start supervisord
CMD ["supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

