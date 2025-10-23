FROM dunglas/frankenphp:1.9.1-alpine AS base

# Set Caddy server name to "http://" to serve on 80 and not 443
# Read more: https://frankenphp.dev/docs/config/#environment-variables
ENV SERVER_NAME="http://"

RUN apk update \
    && apk add --no-cache \
    git \
    unzip \
    supervisor

RUN install-php-extensions \
    gd \
    pdo \
    pdo_pgsql \
	redis \
	zip \
	opcache \
	intl \
	pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

FROM base AS production

WORKDIR /var/www/html

# Copy the Laravel application files into the container.
COPY . .

# Start with base PHP config, then add extensions.
COPY ./docker/production/Caddyfile /etc/frankenphp/Caddyfile
COPY ./docker/production/php.ini /usr/local/etc/php/php.ini
COPY ./docker/production/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Install Laravel dependencies using Composer.
RUN composer install --no-interaction --no-progress

# Set permissions for Laravel.
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80 443

# Start Supervisor.
CMD ["/usr/bin/supervisord", "-n", "-c",  "/etc/supervisor/conf.d/supervisord.conf"]