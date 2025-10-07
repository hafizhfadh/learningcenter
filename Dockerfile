# syntax=docker/dockerfile:1.7

ARG PHP_VERSION=8.3
ARG NODE_VERSION=22

FROM composer:2 AS php_builder
WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction

COPY app ./app
COPY artisan ./artisan
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY public ./public
COPY resources ./resources
COPY routes ./routes
COPY storage ./storage

RUN mkdir -p bootstrap/cache \
    && composer dump-autoload --optimize

FROM node:${NODE_VERSION}-alpine AS frontend
WORKDIR /var/www/html

COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./vite.config.js

RUN npm run build

FROM dunglas/frankenphp:1-php${PHP_VERSION} AS runtime
WORKDIR /var/www/html

ENV APP_ENV=production \
    OCTANE_HTTPS=true

COPY --from=php_builder /var/www/html/vendor ./vendor
COPY --from=php_builder /var/www/html/composer.json ./composer.json
COPY --from=php_builder /var/www/html/composer.lock ./composer.lock
COPY --from=php_builder /var/www/html/artisan ./artisan
COPY --from=php_builder /var/www/html/app ./app
COPY --from=php_builder /var/www/html/bootstrap ./bootstrap
COPY --from=php_builder /var/www/html/config ./config
COPY --from=php_builder /var/www/html/database ./database
COPY --from=php_builder /var/www/html/public ./public
COPY --from=php_builder /var/www/html/resources ./resources
COPY --from=php_builder /var/www/html/routes ./routes
COPY --from=php_builder /var/www/html/storage ./storage

RUN rm -rf public/build
COPY --from=frontend /var/www/html/public/build ./public/build

RUN rm -rf resources/js resources/css

COPY docker/startup.sh /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/startup.sh

RUN if command -v apt-get > /dev/null; then \
        apt-get update && \
        apt-get install -y --no-install-recommends bash postgresql-client && \
        rm -rf /var/lib/apt/lists/*; \
    elif command -v apk > /dev/null; then \
        apk add --no-cache bash postgresql-client; \
    fi

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80 443

ENTRYPOINT ["/usr/local/bin/startup.sh"]
