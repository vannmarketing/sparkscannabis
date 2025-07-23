# syntax=docker/dockerfile:1

# 1. Build node_modules
FROM node:20 AS node_modules
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci

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
WORKDIR /var/www/html
COPY . .
COPY --from=node_modules /app/node_modules ./node_modules
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
RUN npm run prod
RUN php artisan config:cache && php artisan route:cache && php artisan event:cache && php artisan storage:link || true

