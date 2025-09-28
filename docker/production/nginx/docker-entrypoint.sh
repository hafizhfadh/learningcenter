#!/bin/sh
set -e

# Function to log messages
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

# Function to generate self-signed certificates if none exist
generate_self_signed_cert() {
    local domain=${1:-localhost}
    local cert_dir="/etc/nginx/ssl"
    
    if [ ! -f "$cert_dir/cert.pem" ] || [ ! -f "$cert_dir/key.pem" ]; then
        log "Generating self-signed certificate for $domain"
        
        openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
            -keyout "$cert_dir/key.pem" \
            -out "$cert_dir/cert.pem" \
            -subj "/C=US/ST=State/L=City/O=Organization/CN=$domain"
        
        # Create chain file (same as cert for self-signed)
        cp "$cert_dir/cert.pem" "$cert_dir/chain.pem"
        
        log "Self-signed certificate generated"
    else
        log "SSL certificates already exist"
    fi
}

# Function to test nginx configuration
test_nginx_config() {
    log "Testing Nginx configuration"
    if nginx -t; then
        log "Nginx configuration is valid"
        return 0
    else
        log "ERROR: Nginx configuration is invalid"
        return 1
    fi
}

# Function to wait for upstream services
wait_for_upstream() {
    local host=${1:-caddy}
    local port=${2:-80}
    local timeout=${3:-30}
    
    log "Waiting for upstream service $host:$port"
    
    for i in $(seq 1 $timeout); do
        if nc -z "$host" "$port" 2>/dev/null; then
            log "Upstream service $host:$port is ready"
            return 0
        fi
        log "Waiting for $host:$port... ($i/$timeout)"
        sleep 1
    done
    
    log "WARNING: Upstream service $host:$port is not ready after ${timeout}s"
    return 1
}

# Function to setup log rotation
setup_logrotate() {
    log "Setting up log rotation"
    
    cat > /etc/logrotate.d/nginx << 'EOF'
/var/log/nginx/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 nginx nginx
    postrotate
        if [ -f /var/run/nginx.pid ]; then
            kill -USR1 `cat /var/run/nginx.pid`
        fi
    endscript
}
EOF
}

# Function to create custom error pages
create_error_pages() {
    local html_dir="/usr/share/nginx/html"
    
    # Create 404 page
    cat > "$html_dir/404.html" << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>404 Not Found</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
        .error { color: #666; }
    </style>
</head>
<body>
    <h1>404 - Page Not Found</h1>
    <p class="error">The page you are looking for could not be found.</p>
</body>
</html>
EOF

    # Create 50x page
    cat > "$html_dir/50x.html" << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>Service Temporarily Unavailable</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
        .error { color: #666; }
    </style>
</head>
<body>
    <h1>Service Temporarily Unavailable</h1>
    <p class="error">We are currently experiencing technical difficulties. Please try again later.</p>
</body>
</html>
EOF
}

# Main execution
main() {
    log "Starting Nginx container initialization"
    
    # Set default domain from environment variable
    DOMAIN=${DOMAIN:-localhost}
    
    # Create error pages
    create_error_pages
    
    # Generate SSL certificates if needed
    generate_self_signed_cert "$DOMAIN"
    
    # Setup log rotation
    setup_logrotate
    
    # Wait for upstream services (optional, don't fail if not available)
    wait_for_upstream "caddy" "80" "10" || true
    
    # Test configuration
    if ! test_nginx_config; then
        log "FATAL: Nginx configuration test failed"
        exit 1
    fi
    
    # Set proper permissions
    chown -R nginx:nginx /var/cache/nginx
    chown -R nginx:nginx /var/log/nginx
    
    log "Nginx initialization completed successfully"
    
    # Execute the main command
    exec "$@"
}

# Run main function
main "$@"