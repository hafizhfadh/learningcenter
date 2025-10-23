# docker/production/Dockerfile
FROM dunglas/frankenphp:1.9.1-alpine AS base

# OS tools for build + health
RUN apk add --no-cache git unzip supervisor curl

# PHP extensions (common for Laravel + Octane)
RUN install-php-extensions \
    gd pdo pdo_pgsql redis zip opcache intl pcntl

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# -------------------------------
# Production stage
# -------------------------------
FROM base AS production

WORKDIR /var/www/html

# Copy code (use .dockerignore to slim context)
COPY . .

# Caddy / PHP / Supervisor configs
COPY docker/production/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/production/php.ini /usr/local/etc/php/php.ini
COPY docker/production/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Ensure PHP log dir exists
RUN mkdir -p /var/log/php && chown -R www-data:www-data /var/log/php

# Install PHP deps for production
RUN composer install \
    --no-interaction --no-progress --prefer-dist --no-dev \
 && php artisan config:cache \
 && php artisan route:cache \
 && php artisan view:cache \
 && php artisan event:cache

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 443 2019

CMD ["/usr/bin/supervisord", "-n", "-c",  "/etc/supervisor/conf.d/supervisord.conf"]
