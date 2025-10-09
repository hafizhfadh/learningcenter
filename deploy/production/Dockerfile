# syntax=docker/dockerfile:1.6

FROM php:8.3-cli AS php_deps
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends git unzip libicu-dev libzip-dev; \
    docker-php-ext-install intl pcntl zip opcache; \
    rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-progress --prefer-dist --optimize-autoloader --no-interaction --no-scripts

FROM php_deps AS php_builder
WORKDIR /app
COPY artisan artisan
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY app ./app
COPY resources ./resources
COPY routes ./routes
COPY public ./public
RUN composer dump-autoload --classmap-authoritative --no-dev

FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json* pnpm-lock.yaml* bun.lockb* ./
RUN if [ -f package-lock.json ]; then npm ci; \
    elif [ -f pnpm-lock.yaml ]; then npm install -g pnpm && pnpm install --frozen-lockfile; \
    elif [ -f bun.lockb ]; then npm install -g bun && bun install; \
    else npm install; \
    fi
COPY resources ./resources
COPY public ./public
COPY vite.config.js vite.config.js
RUN --mount=type=bind,source=.,target=/context,ro \
    set -eux; \
    for file in postcss.config.js tailwind.config.js tsconfig.json; do \
        if [ -f "/context/${file}" ]; then cp "/context/${file}" "./${file}"; fi; \
    done
RUN npm run build

FROM dunglas/frankenphp:1.1.3-php8.3-alpine AS runtime
WORKDIR /var/www/html

ENV APP_ENV=production \
    APP_DEBUG=0 \
    OCTANE_SERVER=frankenphp \
    FRANKENPHP_CONFIG="worker ./public/index.php" \
    COMPOSER_ALLOW_SUPERUSER=1

RUN set -eux; \
    apk add --no-cache bash curl git wget icu-data-full libzip; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev; \
    docker-php-ext-configure intl; \
    docker-php-ext-install intl pdo_mysql zip; \
    apk del .build-deps

COPY --from=php_builder /app/composer.json ./composer.json
COPY --from=php_builder /app/composer.lock ./composer.lock
COPY --from=php_builder /app/vendor ./vendor
COPY --from=php_builder /app/bootstrap ./bootstrap
COPY --from=php_builder /app/config ./config
COPY --from=php_builder /app/database ./database
COPY --from=php_builder /app/app ./app
COPY --from=php_builder /app/resources ./resources
COPY --from=php_builder /app/routes ./routes
COPY --from=php_builder /app/artisan ./artisan
COPY --from=php_builder /app/public ./public
COPY --from=frontend /app/public/build ./public/build
COPY deploy/production/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini

RUN rm -rf public/storage \
    && mkdir -p storage/framework/{cache,data,sessions,testing,views} \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

HEALTHCHECK --interval=30s --timeout=5s --retries=5 CMD wget -q -O /dev/null http://127.0.0.1:9000/health || exit 1

USER www-data

CMD ["sh", "-c", "php artisan optimize && php artisan octane:start --server=frankenphp --host=${OCTANE_HOST:-0.0.0.0} --port=${OCTANE_PORT:-9000} --workers=${OCTANE_WORKERS:-4} --max-requests=${OCTANE_MAX_REQUESTS:-500} --task-workers=${OCTANE_TASK_WORKERS:-2} ${OCTANE_HTTPS:+--https} ${OCTANE_FRANKENPHP_HTTP_REDIRECT:+--http-redirect}"]
