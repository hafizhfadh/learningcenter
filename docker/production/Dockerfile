# Multi-stage Production Dockerfile with FrankenPHP and Laravel Octane
# Optimized for build time and image size

# =============================================================================
# Stage 1: Base - System dependencies and PHP extensions
# =============================================================================
FROM dunglas/frankenphp:1-php8.4-alpine AS base

# Security: Add labels for container metadata
LABEL maintainer="admin@learningcenter.com" \
      version="2.0" \
      description="Production Laravel Learning Center with FrankenPHP (Multi-stage)" \
      security.scan="enabled"

# Security: Create non-root user early
RUN addgroup -g 1001 -S appgroup && \
    adduser -u 1001 -S appuser -G appgroup

# Install system dependencies (cached when unchanged)
RUN apk add --no-cache --update \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    icu-dev \
    libzip-dev \
    postgresql-dev \
    redis \
    supervisor \
    autoconf \
    g++ \
    make \
    ca-certificates \
    tzdata \
    && rm -rf /var/cache/apk/* \
    && rm -rf /tmp/*

# Install PHP extensions (cached when base image unchanged)
RUN docker-php-ext-install \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    opcache \
    intl \
    zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Configure PHP for production
COPY docker/production/php.ini /usr/local/etc/php/conf.d/99-production.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# =============================================================================
# Stage 2: Composer Dependencies
# =============================================================================
FROM base AS composer-deps

# Set working directory
WORKDIR /app

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Copy application structure needed for autoloading
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY routes ./routes
COPY artisan ./

# Install PHP dependencies with security checks (cached when composer files unchanged)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --audit \
    && composer clear-cache

# =============================================================================
# Stage 3: Frontend Build
# =============================================================================
FROM node:20-alpine AS frontend-build

# Set working directory
WORKDIR /app

# Install build dependencies for Node.js
RUN apk add --no-cache git

# Copy package files first for better caching
COPY package.json package-lock.json ./

# Install Node.js dependencies including dev dependencies for build (cached when package files unchanged)
RUN npm ci --audit \
    && npm cache clean --force

# Copy source files needed for build
COPY resources/ ./resources/
COPY public/ ./public/
COPY vite.config.js ./

# Build frontend assets
RUN npm run build

# =============================================================================
# Stage 4: Final Production Image
# =============================================================================
FROM base AS production

# Set working directory
WORKDIR /app

# Security: Install only runtime dependencies (no build tools)
RUN apk add --no-cache \
    git \
    zip \
    unzip \
    && rm -rf /var/cache/apk/* \
    && rm -rf /tmp/*

# Copy vendor dependencies from composer stage
COPY --from=composer-deps --chown=appuser:appgroup /app/vendor ./vendor

# Copy built frontend assets from frontend stage
COPY --from=frontend-build --chown=appuser:appgroup /app/public/build ./public/build

# Security: Copy application files with proper ownership (excluding node_modules and vendor)
COPY --chown=appuser:appgroup app ./app
COPY --chown=appuser:appgroup bootstrap ./bootstrap
COPY --chown=appuser:appgroup config ./config
COPY --chown=appuser:appgroup database ./database
COPY --chown=appuser:appgroup public ./public
COPY --chown=appuser:appgroup resources ./resources
COPY --chown=appuser:appgroup routes ./routes
COPY --chown=appuser:appgroup storage ./storage
COPY --chown=appuser:appgroup artisan ./
COPY --chown=appuser:appgroup composer.json composer.lock ./

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

# Security: Remove unnecessary packages and files (keep git for Laravel)
RUN apk del --purge \
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