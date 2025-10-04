# Production Dockerfile with FrankenPHP and Laravel Octane
FROM dunglas/frankenphp:1-php8.4-alpine

# Security: Add labels for container metadata
LABEL maintainer="admin@learningcenter.com" \
      version="1.0" \
      description="Production Laravel Learning Center with FrankenPHP" \
      security.scan="enabled"

# Security: Create non-root user early
RUN addgroup -g 1001 -S appgroup && \
    adduser -u 1001 -S appuser -G appgroup

# Set working directory
WORKDIR /app

# Security: Install system dependencies with specific versions and remove cache
RUN apk add --no-cache --update \
    git=~2.43 \
    curl=~8.5 \
    libpng-dev=~1.6 \
    oniguruma-dev=~6.9 \
    libxml2-dev=~2.12 \
    zip=~3.0 \
    unzip=~6.0 \
    nodejs=~20.10 \
    npm=~10.2 \
    postgresql-dev=~16.1 \
    redis=~7.2 \
    supervisor=~4.2 \
    ca-certificates \
    tzdata \
    && rm -rf /var/cache/apk/* \
    && rm -rf /tmp/*

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

# Security: Copy application files with proper ownership
COPY --chown=appuser:appgroup . /app

# Security: Set strict permissions and create necessary directories
RUN mkdir -p /app/storage/logs /app/storage/framework/cache /app/storage/framework/sessions /app/storage/framework/views \
    && mkdir -p /app/bootstrap/cache \
    && chown -R appuser:appgroup /app \
    && chmod -R 750 /app \
    && chmod -R 755 /app/storage \
    && chmod -R 755 /app/bootstrap/cache \
    && chmod -R 644 /app/public \
    && find /app -type f -name "*.php" -exec chmod 644 {} \; \
    && find /app -type f -name "*.sh" -exec chmod 750 {} \;

# Install PHP dependencies with security checks
RUN composer install --no-dev --optimize-autoloader --no-interaction --audit \
    && composer clear-cache

# Install and build frontend assets with security audit
RUN npm ci --only=production --audit \
    && npm run build \
    && npm cache clean --force \
    && rm -rf node_modules package-lock.json

# Security: Configure FrankenPHP with restricted permissions
COPY --chown=root:root docker/production/Caddyfile /etc/caddy/Caddyfile
RUN chmod 644 /etc/caddy/Caddyfile

# Security: Configure Supervisor with restricted permissions
COPY --chown=root:root docker/production/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
RUN chmod 644 /etc/supervisor/conf.d/supervisord.conf

# Security: Create startup script with minimal permissions
COPY --chown=root:root docker/production/start-container /usr/local/bin/start-container
RUN chmod 755 /usr/local/bin/start-container

# Security: Create security directories and files
RUN mkdir -p /var/log/security \
    && touch /var/log/security/access.log \
    && touch /var/log/security/error.log \
    && chown -R appuser:appgroup /var/log/security \
    && chmod 750 /var/log/security

# Security: Remove unnecessary packages and files
RUN apk del --purge \
    git \
    zip \
    unzip \
    && rm -rf /var/cache/apk/* \
    && rm -rf /tmp/* \
    && rm -rf /root/.composer \
    && rm -rf /root/.npm

# Security: Set environment variables for hardening
ENV PHP_INI_SCAN_DIR="/usr/local/etc/php/conf.d:/app/docker/production/php-security.d"
ENV COMPOSER_ALLOW_SUPERUSER=0
ENV NODE_ENV=production

# Expose ports (non-privileged)
EXPOSE 8080 8443

# Security: Enhanced health check with timeout and authentication
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -f --max-time 5 http://localhost:8080/health || exit 1

# Security: Switch to non-root user
USER appuser:appgroup

# Security: Use exec form and specific command
CMD ["sh", "/usr/local/bin/start-container"]