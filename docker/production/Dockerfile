# Production Dockerfile with FrankenPHP and Laravel Octane
FROM dunglas/frankenphp:1-php8.4-alpine

# Set working directory
WORKDIR /app

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    postgresql-dev \
    redis \
    supervisor

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Configure PHP for production
COPY docker/production/php.ini /usr/local/etc/php/conf.d/99-production.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /app

# Set proper permissions
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app/storage \
    && chmod -R 755 /app/bootstrap/cache

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install and build frontend assets
RUN npm ci --only=production \
    && npm run build \
    && rm -rf node_modules

# Configure FrankenPHP
COPY docker/production/Caddyfile /etc/caddy/Caddyfile

# Configure Supervisor for Laravel Octane
COPY docker/production/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create startup script
COPY docker/production/start-container /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container

# Expose port
EXPOSE 80 443

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Start container
CMD ["/usr/local/bin/start-container"]