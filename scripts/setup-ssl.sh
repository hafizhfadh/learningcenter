#!/bin/bash

# Learning Center SSL/TLS Setup Script
# Sets up Let's Encrypt SSL certificates for production deployment
# Author: Learning Center Team
# Version: 1.0.0

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
DOCKER_COMPOSE_FILE="$PROJECT_ROOT/docker/production/resource-optimization.yml"
SSL_DIR="$PROJECT_ROOT/docker/production/ssl"
NGINX_CONF_DIR="$PROJECT_ROOT/docker/production"
LOG_FILE="/var/log/learning-center-ssl.log"

# Default values
DOMAIN=""
EMAIL=""
STAGING=false
FORCE_RENEWAL=false

# Logging functions
log() {
    local level=$1
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${timestamp} [${level}] ${message}" | tee -a "$LOG_FILE"
}

info() {
    log "INFO" "${BLUE}$*${NC}"
}

success() {
    log "SUCCESS" "${GREEN}$*${NC}"
}

warning() {
    log "WARNING" "${YELLOW}$*${NC}"
}

error() {
    log "ERROR" "${RED}$*${NC}"
}

# Show usage information
usage() {
    echo "Usage: $0 -d DOMAIN -e EMAIL [OPTIONS]"
    echo ""
    echo "Required arguments:"
    echo "  -d, --domain DOMAIN    Domain name for SSL certificate"
    echo "  -e, --email EMAIL      Email address for Let's Encrypt registration"
    echo ""
    echo "Optional arguments:"
    echo "  -s, --staging          Use Let's Encrypt staging environment (for testing)"
    echo "  -f, --force            Force certificate renewal"
    echo "  -h, --help             Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 -d example.com -e admin@example.com"
    echo "  $0 -d example.com -e admin@example.com --staging"
    echo "  $0 -d example.com -e admin@example.com --force"
}

# Parse command line arguments
parse_args() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            -d|--domain)
                DOMAIN="$2"
                shift 2
                ;;
            -e|--email)
                EMAIL="$2"
                shift 2
                ;;
            -s|--staging)
                STAGING=true
                shift
                ;;
            -f|--force)
                FORCE_RENEWAL=true
                shift
                ;;
            -h|--help)
                usage
                exit 0
                ;;
            *)
                error "Unknown option: $1"
                usage
                exit 1
                ;;
        esac
    done
    
    # Validate required arguments
    if [[ -z "$DOMAIN" ]]; then
        error "Domain is required. Use -d or --domain to specify."
        usage
        exit 1
    fi
    
    if [[ -z "$EMAIL" ]]; then
        error "Email is required. Use -e or --email to specify."
        usage
        exit 1
    fi
}

# Validate domain and email
validate_inputs() {
    info "Validating inputs..."
    
    # Validate domain format
    if ! [[ "$DOMAIN" =~ ^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$ ]]; then
        error "Invalid domain format: $DOMAIN"
        exit 1
    fi
    
    # Validate email format
    if ! [[ "$EMAIL" =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$ ]]; then
        error "Invalid email format: $EMAIL"
        exit 1
    fi
    
    # Check if domain resolves to this server
    local server_ip=$(curl -s ifconfig.me || curl -s ipinfo.io/ip || echo "unknown")
    local domain_ip=$(dig +short "$DOMAIN" | tail -n1)
    
    if [[ "$server_ip" != "unknown" && "$domain_ip" != "$server_ip" ]]; then
        warning "Domain $DOMAIN does not resolve to this server IP ($server_ip)"
        warning "Current domain IP: $domain_ip"
        warning "SSL certificate generation may fail if DNS is not properly configured"
        
        read -p "Continue anyway? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
    
    success "Input validation passed"
}

# Install Certbot
install_certbot() {
    info "Installing Certbot..."
    
    if command -v certbot &> /dev/null; then
        success "Certbot is already installed"
        return
    fi
    
    # Install snapd if not present
    if ! command -v snap &> /dev/null; then
        sudo apt update
        sudo apt install -y snapd
    fi
    
    # Install certbot via snap
    sudo snap install core; sudo snap refresh core
    sudo snap install --classic certbot
    
    # Create symlink
    sudo ln -sf /snap/bin/certbot /usr/bin/certbot
    
    success "Certbot installed successfully"
}

# Create SSL directory structure
create_ssl_directories() {
    info "Creating SSL directory structure..."
    
    sudo mkdir -p "$SSL_DIR"
    sudo mkdir -p "$SSL_DIR/live/$DOMAIN"
    sudo mkdir -p "$SSL_DIR/archive/$DOMAIN"
    sudo mkdir -p "/var/log/letsencrypt"
    
    # Set proper permissions
    sudo chown -R root:root "$SSL_DIR"
    sudo chmod -R 755 "$SSL_DIR"
    
    success "SSL directories created"
}

# Generate temporary self-signed certificate for initial setup
generate_temp_certificate() {
    info "Generating temporary self-signed certificate..."
    
    local temp_cert_dir="$SSL_DIR/temp"
    sudo mkdir -p "$temp_cert_dir"
    
    # Generate private key
    sudo openssl genrsa -out "$temp_cert_dir/privkey.pem" 2048
    
    # Generate certificate
    sudo openssl req -new -x509 -key "$temp_cert_dir/privkey.pem" \
        -out "$temp_cert_dir/fullchain.pem" -days 1 \
        -subj "/C=US/ST=State/L=City/O=Organization/CN=$DOMAIN"
    
    # Copy to expected locations
    sudo cp "$temp_cert_dir/fullchain.pem" "$SSL_DIR/live/$DOMAIN/fullchain.pem"
    sudo cp "$temp_cert_dir/privkey.pem" "$SSL_DIR/live/$DOMAIN/privkey.pem"
    sudo cp "$temp_cert_dir/fullchain.pem" "$SSL_DIR/live/$DOMAIN/chain.pem"
    
    success "Temporary certificate generated"
}

# Update Nginx configuration for SSL
update_nginx_config() {
    info "Updating Nginx configuration for SSL..."
    
    # Copy SSL-enabled Nginx configuration
    local ssl_nginx_conf="$NGINX_CONF_DIR/ssl-nginx.conf"
    local nginx_conf="$NGINX_CONF_DIR/nginx.conf"
    
    if [[ -f "$ssl_nginx_conf" ]]; then
        # Update domain in SSL configuration
        sudo sed "s/server_name _;/server_name $DOMAIN;/g" "$ssl_nginx_conf" > "$nginx_conf.ssl"
        sudo sed -i "s/your-domain.com/$DOMAIN/g" "$nginx_conf.ssl"
        
        # Backup original configuration
        if [[ -f "$nginx_conf" ]]; then
            sudo cp "$nginx_conf" "$nginx_conf.backup.$(date +%Y%m%d_%H%M%S)"
        fi
        
        # Use SSL configuration
        sudo mv "$nginx_conf.ssl" "$nginx_conf"
        
        success "Nginx configuration updated for SSL"
    else
        error "SSL Nginx configuration not found: $ssl_nginx_conf"
        exit 1
    fi
}

# Update Docker Compose for SSL
update_docker_compose() {
    info "Updating Docker Compose configuration for SSL..."
    
    # Create SSL-enabled Docker Compose file
    local ssl_compose_file="$PROJECT_ROOT/docker/production/ssl-docker-compose.yml"
    
    cat > "$ssl_compose_file" << EOF
# Learning Center Production Docker Compose with SSL/TLS
# Extends resource-optimization.yml with SSL configuration

version: '3.8'

services:
  nginx:
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/conf.d/default.conf:ro
      - ./ssl:/etc/nginx/ssl:ro
      - nginx_logs:/var/log/nginx
    environment:
      - DOMAIN=$DOMAIN
    depends_on:
      - app

volumes:
  nginx_logs:
    driver: local
EOF
    
    success "Docker Compose configuration updated for SSL"
}

# Start services with temporary certificate
start_services_temp() {
    info "Starting services with temporary certificate..."
    
    # Stop existing services
    if docker-compose -f "$DOCKER_COMPOSE_FILE" ps -q | grep -q .; then
        docker-compose -f "$DOCKER_COMPOSE_FILE" down
    fi
    
    # Start with SSL configuration
    docker-compose -f "$DOCKER_COMPOSE_FILE" -f "$PROJECT_ROOT/docker/production/ssl-docker-compose.yml" up -d
    
    # Wait for services to be ready
    sleep 10
    
    success "Services started with temporary certificate"
}

# Obtain Let's Encrypt certificate
obtain_letsencrypt_certificate() {
    info "Obtaining Let's Encrypt certificate..."
    
    local certbot_args=(
        "certonly"
        "--webroot"
        "--webroot-path=/var/www/html/public"
        "--email" "$EMAIL"
        "--agree-tos"
        "--no-eff-email"
        "--domains" "$DOMAIN"
        "--non-interactive"
    )
    
    if [[ "$STAGING" == true ]]; then
        certbot_args+=("--staging")
        info "Using Let's Encrypt staging environment"
    fi
    
    if [[ "$FORCE_RENEWAL" == true ]]; then
        certbot_args+=("--force-renewal")
        info "Forcing certificate renewal"
    fi
    
    # Run certbot
    if sudo certbot "${certbot_args[@]}"; then
        success "Let's Encrypt certificate obtained successfully"
    else
        error "Failed to obtain Let's Encrypt certificate"
        return 1
    fi
    
    # Copy certificates to SSL directory
    sudo cp "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" "$SSL_DIR/live/$DOMAIN/fullchain.pem"
    sudo cp "/etc/letsencrypt/live/$DOMAIN/privkey.pem" "$SSL_DIR/live/$DOMAIN/privkey.pem"
    sudo cp "/etc/letsencrypt/live/$DOMAIN/chain.pem" "$SSL_DIR/live/$DOMAIN/chain.pem"
    
    success "Certificates copied to SSL directory"
}

# Restart services with real certificate
restart_services() {
    info "Restarting services with real certificate..."
    
    # Restart Nginx to load new certificate
    docker-compose -f "$DOCKER_COMPOSE_FILE" -f "$PROJECT_ROOT/docker/production/ssl-docker-compose.yml" restart nginx
    
    # Wait for service to be ready
    sleep 5
    
    success "Services restarted with real certificate"
}

# Test SSL configuration
test_ssl_configuration() {
    info "Testing SSL configuration..."
    
    # Test HTTPS connection
    local max_attempts=10
    local attempt=1
    
    while [[ $attempt -le $max_attempts ]]; do
        if curl -f -s "https://$DOMAIN/health" > /dev/null 2>&1; then
            success "HTTPS connection test passed"
            break
        fi
        
        if [[ $attempt -eq $max_attempts ]]; then
            error "HTTPS connection test failed after $max_attempts attempts"
            return 1
        fi
        
        info "HTTPS test attempt $attempt/$max_attempts failed, retrying in 5 seconds..."
        sleep 5
        ((attempt++))
    done
    
    # Test SSL certificate
    local ssl_info=$(echo | openssl s_client -connect "$DOMAIN:443" -servername "$DOMAIN" 2>/dev/null | openssl x509 -noout -dates 2>/dev/null || echo "")
    if [[ -n "$ssl_info" ]]; then
        success "SSL certificate information:"
        echo "$ssl_info"
    else
        warning "Could not retrieve SSL certificate information"
    fi
    
    # Test security headers
    local security_headers=("Strict-Transport-Security" "X-Content-Type-Options" "X-Frame-Options")
    for header in "${security_headers[@]}"; do
        if curl -s -I "https://$DOMAIN" | grep -qi "$header"; then
            success "Security header $header is present"
        else
            warning "Security header $header is missing"
        fi
    done
    
    success "SSL configuration test completed"
}

# Setup automatic renewal
setup_auto_renewal() {
    info "Setting up automatic certificate renewal..."
    
    # Create renewal script
    local renewal_script="/usr/local/bin/renew-ssl-learning-center.sh"
    
    sudo tee "$renewal_script" > /dev/null << EOF
#!/bin/bash
# Learning Center SSL Certificate Renewal Script

set -euo pipefail

# Renew certificate
certbot renew --quiet --no-self-upgrade

# Copy renewed certificates
if [[ -f "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" ]]; then
    cp "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" "$SSL_DIR/live/$DOMAIN/fullchain.pem"
    cp "/etc/letsencrypt/live/$DOMAIN/privkey.pem" "$SSL_DIR/live/$DOMAIN/privkey.pem"
    cp "/etc/letsencrypt/live/$DOMAIN/chain.pem" "$SSL_DIR/live/$DOMAIN/chain.pem"
    
    # Restart Nginx
    docker-compose -f "$DOCKER_COMPOSE_FILE" -f "$PROJECT_ROOT/docker/production/ssl-docker-compose.yml" restart nginx
    
    echo "SSL certificate renewed and services restarted"
fi
EOF
    
    sudo chmod +x "$renewal_script"
    
    # Add cron job for automatic renewal
    local cron_job="0 3 * * * $renewal_script >> /var/log/ssl-renewal.log 2>&1"
    
    # Check if cron job already exists
    if ! sudo crontab -l 2>/dev/null | grep -q "$renewal_script"; then
        (sudo crontab -l 2>/dev/null; echo "$cron_job") | sudo crontab -
        success "Automatic renewal cron job added"
    else
        success "Automatic renewal cron job already exists"
    fi
    
    success "Automatic certificate renewal setup completed"
}

# Display SSL summary
ssl_summary() {
    info "SSL/TLS Setup Summary"
    echo "====================="
    echo "Domain: $DOMAIN"
    echo "Email: $EMAIL"
    echo "Staging: $STAGING"
    echo "SSL Directory: $SSL_DIR"
    echo "Certificate Path: $SSL_DIR/live/$DOMAIN/"
    echo ""
    
    info "Certificate Information:"
    if [[ -f "$SSL_DIR/live/$DOMAIN/fullchain.pem" ]]; then
        openssl x509 -in "$SSL_DIR/live/$DOMAIN/fullchain.pem" -noout -text | grep -E "(Subject:|Issuer:|Not Before:|Not After:)"
    fi
    echo ""
    
    info "Next Steps:"
    echo "1. Update your DNS to point $DOMAIN to this server"
    echo "2. Test your SSL configuration at: https://www.ssllabs.com/ssltest/"
    echo "3. Monitor certificate expiration and renewal logs"
    echo ""
    
    success "SSL/TLS setup completed successfully!"
}

# Cleanup function
cleanup() {
    if [[ -d "$SSL_DIR/temp" ]]; then
        sudo rm -rf "$SSL_DIR/temp"
    fi
}

# Main function
main() {
    info "Starting Learning Center SSL/TLS Setup"
    info "======================================"
    
    # Set trap for cleanup
    trap cleanup EXIT
    
    validate_inputs
    install_certbot
    create_ssl_directories
    generate_temp_certificate
    update_nginx_config
    update_docker_compose
    start_services_temp
    
    if obtain_letsencrypt_certificate; then
        restart_services
        test_ssl_configuration
        setup_auto_renewal
        ssl_summary
    else
        error "SSL certificate generation failed"
        exit 1
    fi
    
    success "SSL/TLS setup completed successfully!"
}

# Parse arguments and run main function
parse_args "$@"
main