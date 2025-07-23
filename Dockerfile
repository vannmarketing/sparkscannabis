# syntax=docker/dockerfile:1

# 1. Build vendor dependencies with PHP + extensions
FROM php:8.2-fpm AS vendor
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
# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
WORKDIR /app
COPY composer.json composer.lock ./
COPY platform ./platform
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# 2. Build node_modules
FROM node:20 AS node_modules
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci

# 3. Final app image
FROM php:8.2-fpm
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip calendar
WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY --from=node_modules /app/node_modules ./node_modules
COPY . .
RUN composer run-script post-autoload-dump
RUN npm run prod
RUN php artisan config:cache && php artisan route:cache && php artisan event:cache && php artisan storage:link || true

