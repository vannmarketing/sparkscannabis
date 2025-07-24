# syntax=docker/dockerfile:1

# 1. Build node_modules
FROM node:20 AS node_modules
WORKDIR /app
COPY package.json package-lock.json ./
COPY platform ./platform
RUN npm ci
RUN apt-get update && apt-get install -y wget && rm -rf /var/lib/apt/lists/*

# 2. Final app image
FROM php:8.2-fpm
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    curl \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip calendar
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs
WORKDIR /var/www/html
COPY . .
COPY --from=node_modules /app/node_modules ./node_modules

# Ensure cache and storage directories exist and are writable
RUN mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
RUN npm run prod
RUN php artisan config:cache && php artisan route:cache && php artisan event:cache && php artisan storage:link || true

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD curl -f http://localhost:8080/ || exit 1

