# syntax=docker/dockerfile:1

FROM php:8.2-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    bash \
    curl \
    git \
    unzip \
    libpng \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    zlib-dev \
    libxml2-dev \
    nodejs=18.20.2-r0 \
    npm

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        zip \
        gd \
        mbstring \
        exif \
        pcntl \
        bcmath \
        intl \
        xml \
        opcache \
        fileinfo \
        tokenizer \
        curl

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy app files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install JS dependencies and build assets
RUN npm install && npm run prod

# Publish CMS assets
RUN php artisan cms:publish:assets --force

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage symlink
RUN php artisan storage:link || true

# Optimize Laravel
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache

# Nginx config
COPY ./docker/nginx.conf /etc/nginx/nginx.conf

# Expose port 80
EXPOSE 80

# Start Nginx and PHP-FPM
CMD ["/bin/sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"] 