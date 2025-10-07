# syntax=docker/dockerfile:1.6

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock .
RUN composer install --no-dev --no-progress --prefer-dist --optimize-autoloader --no-interaction --no-scripts
COPY . .
RUN composer install --no-dev --no-progress --prefer-dist --optimize-autoloader --no-interaction --no-scripts

FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json* pnpm-lock.yaml* bun.lockb* ./
RUN if [ -f package-lock.json ]; then npm ci; \
    elif [ -f pnpm-lock.yaml ]; then npm install -g pnpm && pnpm install --frozen-lockfile; \
    elif [ -f bun.lockb ]; then npm install -g bun && bun install; \
    else npm install; \
    fi
COPY resources resources
COPY public public
COPY vite.config.js vite.config.js
COPY postcss.config.js postcss.config.js 2>/dev/null || true
COPY tailwind.config.js tailwind.config.js 2>/dev/null || true
RUN npm run build

FROM dunglas/frankenphp:1.1.3-php8.3-alpine AS runner
WORKDIR /var/www/html

ENV APP_ENV=production \
    APP_DEBUG=0 \
    OCTANE_SERVER=frankenphp \
    FRANKENPHP_CONFIG="worker ./public/index.php" \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apk add --no-cache icu-data-full bash curl git wget

COPY --from=vendor /app /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build
COPY deploy/production/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini

RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

HEALTHCHECK --interval=30s --timeout=5s --retries=5 CMD wget -q -O /dev/null http://127.0.0.1:9000/health || exit 1

USER www-data

CMD ["sh", "-c", "php artisan optimize && php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=9000 --workers=${OCTANE_WORKERS:-4} --max-requests=${OCTANE_MAX_REQUESTS:-500} --task-workers=${OCTANE_TASK_WORKERS:-2}"]
