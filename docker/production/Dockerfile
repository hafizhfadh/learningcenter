# docker/production/Dockerfile
FROM dunglas/frankenphp:1.9.1-alpine AS base
ENV SERVER_NAME="http://"

# OS tools for health & builds
RUN apk add --no-cache git unzip curl

RUN install-php-extensions \
    gd pdo pdo_pgsql redis zip opcache intl pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

FROM base AS production
WORKDIR /var/www/html

# App code + configs
COPY . .
COPY docker/production/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/production/php.ini /usr/local/etc/php/php.ini

# Ensure PHP log dir exists (your php.ini points here)
RUN mkdir -p /var/log/php && chown -R www-data:www-data /var/log/php

# Production deps & caches
RUN composer install --no-interaction --prefer-dist --no-dev \
 && php artisan config:cache \
 && php artisan route:cache \
 && php artisan view:cache \
 && php artisan event:cache

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80 443 2019
# No CMD — each Compose service supplies its own command
