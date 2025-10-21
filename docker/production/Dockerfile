FROM dunglas/frankenphp:1.9.1-alpine AS base

RUN install-php-extensions \
	pdo_psql \
	redis \
	zip \
	opcache \
	intl \
	pcntl

ENV SERVER_NAME=:80

FROM base AS production

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY . /app