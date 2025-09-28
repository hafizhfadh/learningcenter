FROM nginx:1.25-alpine

# Install additional packages for security and monitoring
RUN apk add --no-cache \
    curl \
    openssl \
    certbot \
    certbot-nginx \
    logrotate \
    && rm -rf /var/cache/apk/*

# Create nginx user and directories
RUN addgroup -g 101 -S nginx \
    && adduser -S -D -H -u 101 -h /var/cache/nginx -s /sbin/nologin -G nginx -g nginx nginx

# Create necessary directories
RUN mkdir -p /var/cache/nginx/proxy \
    && mkdir -p /var/cache/nginx/fastcgi \
    && mkdir -p /var/log/nginx \
    && mkdir -p /etc/nginx/ssl \
    && mkdir -p /etc/nginx/sites-available \
    && mkdir -p /etc/nginx/sites-enabled \
    && mkdir -p /var/www/certbot \
    && chown -R nginx:nginx /var/cache/nginx \
    && chown -R nginx:nginx /var/log/nginx \
    && chown -R nginx:nginx /var/www/certbot

# Copy configuration files
COPY nginx.conf /etc/nginx/nginx.conf
COPY conf.d/ /etc/nginx/conf.d/
COPY sites-available/ /etc/nginx/sites-available/

# Create symlink for default site
RUN ln -sf /etc/nginx/sites-available/default.conf /etc/nginx/sites-enabled/default.conf

# Copy SSL certificate placeholder (will be replaced by real certs)
COPY ssl/ /etc/nginx/ssl/

# Copy custom error pages
COPY html/ /usr/share/nginx/html/

# Copy entrypoint script
COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/nginx-health || exit 1

# Expose ports
EXPOSE 80 443

# Set entrypoint
ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["nginx", "-g", "daemon off;"]