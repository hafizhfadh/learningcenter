#!/bin/bash

# SSL/TLS Issues Troubleshooting Script
# This script diagnoses and fixes SSL certificate issues with Caddy

set -e

echo "🔍 SSL/TLS Issues Diagnostic and Fix Script"
echo "==========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running in production or development
check_environment() {
    print_status "Checking environment..."
    
    if [[ -f ".env.production" ]]; then
        ENV_TYPE="production"
        print_status "Production environment detected"
    else
        ENV_TYPE="development"
        print_status "Development environment detected"
    fi
}

# Check DNS resolution
check_dns() {
    print_status "Checking DNS resolution for learning.csi-academy.id..."
    
    if nslookup learning.csi-academy.id > /dev/null 2>&1; then
        IP=$(nslookup learning.csi-academy.id | grep -A1 "Name:" | tail -1 | awk '{print $2}')
        print_success "DNS resolves to: $IP"
        
        # Check if it resolves to localhost/private IP
        if [[ $IP == "127.0.0.1" || $IP == "localhost" || $IP =~ ^192\.168\. || $IP =~ ^10\. || $IP =~ ^172\.(1[6-9]|2[0-9]|3[0-1])\. ]]; then
            print_warning "Domain resolves to local/private IP. SSL certificates cannot be obtained from Let's Encrypt."
            return 1
        fi
    else
        print_error "DNS resolution failed for learning.csi-academy.id"
        return 1
    fi
}

# Check if containers are running
check_containers() {
    print_status "Checking container status..."
    
    if docker compose -f docker-compose.production.yml ps | grep -q "learningcenter_app.*Up"; then
        print_success "Production containers are running"
    else
        print_error "Production containers are not running"
        return 1
    fi
}

# Check Caddy logs for SSL errors
check_caddy_logs() {
    print_status "Checking Caddy logs for SSL/certificate errors..."
    
    LOGS=$(docker compose -f docker-compose.production.yml logs app 2>&1 | tail -20)
    
    if echo "$LOGS" | grep -q "certificate"; then
        print_warning "Certificate-related messages found in logs:"
        echo "$LOGS" | grep "certificate"
    fi
    
    if echo "$LOGS" | grep -q "ACME"; then
        print_warning "ACME (Let's Encrypt) messages found in logs:"
        echo "$LOGS" | grep "ACME"
    fi
    
    if echo "$LOGS" | grep -q "tls"; then
        print_warning "TLS-related messages found in logs:"
        echo "$LOGS" | grep "tls"
    fi
}

# Test local connectivity
test_local_connectivity() {
    print_status "Testing local connectivity..."
    
    # Test HTTP (should redirect to HTTPS)
    if curl -s -I http://localhost | grep -q "308\|301"; then
        print_success "HTTP redirect is working"
    else
        print_error "HTTP redirect is not working"
    fi
    
    # Test HTTPS with self-signed certificate acceptance
    if curl -s -k -I https://localhost > /dev/null 2>&1; then
        print_success "HTTPS connection works (ignoring certificate validation)"
    else
        print_error "HTTPS connection failed even with certificate validation disabled"
    fi
}

# Fix for local development
fix_local_development() {
    print_status "Setting up local development configuration..."
    
    # Stop current containers
    print_status "Stopping current containers..."
    docker compose -f docker-compose.production.yml down
    
    # Copy local Caddyfile
    print_status "Using local development Caddyfile..."
    cp etc/frankenphp/Caddyfile.local etc/frankenphp/Caddyfile
    
    # Start containers
    print_status "Starting containers with local configuration..."
    docker compose --env-file .env.production -f docker-compose.production.yml up -d
    
    # Wait for startup
    sleep 10
    
    # Test local connection
    print_status "Testing local HTTP connection..."
    if curl -s http://localhost | grep -q "Laravel\|Filament\|<!DOCTYPE"; then
        print_success "Local development setup complete! Access via http://localhost"
    else
        print_error "Local setup failed. Check logs with: docker compose -f docker-compose.production.yml logs app"
    fi
}

# Fix for production deployment
fix_production_deployment() {
    print_status "Setting up production configuration..."
    
    # Ensure production Caddyfile is in place
    if [[ ! -f "etc/frankenphp/Caddyfile" ]]; then
        print_error "Production Caddyfile not found!"
        return 1
    fi
    
    # Check if domain resolves correctly
    if ! check_dns; then
        print_error "Cannot proceed with production setup - DNS issues detected"
        print_status "Please ensure:"
        echo "  1. learning.csi-academy.id resolves to your server's public IP"
        echo "  2. Ports 80 and 443 are open and accessible from the internet"
        echo "  3. No firewall is blocking Let's Encrypt validation"
        return 1
    fi
    
    # Stop current containers
    print_status "Stopping current containers..."
    docker compose --env-file .env.production -f docker-compose.production.yml down
    
    # Start containers
    print_status "Starting containers with production configuration..."
    docker compose --env-file .env.production -f docker-compose.production.yml up -d
    
    # Wait for startup and certificate acquisition
    print_status "Waiting for SSL certificate acquisition (this may take up to 2 minutes)..."
    sleep 30
    
    # Check certificate acquisition
    for i in {1..4}; do
        if docker exec learningcenter_app ls /data/caddy/certificates 2>/dev/null | grep -q "learning.csi-academy.id"; then
            print_success "SSL certificate acquired successfully!"
            break
        else
            print_status "Waiting for certificate acquisition... ($i/4)"
            sleep 30
        fi
    done
    
    # Test production connection
    print_status "Testing production HTTPS connection..."
    if curl -s -I https://learning.csi-academy.id | grep -q "200\|301\|302"; then
        print_success "Production deployment successful! Access via https://learning.csi-academy.id"
    else
        print_warning "Production connection test failed. This might be normal if DNS propagation is still in progress."
    fi
}

# Main execution
main() {
    check_environment
    
    if ! check_containers; then
        print_status "Starting containers first..."
        docker compose --env-file .env.production -f docker-compose.production.yml up -d
        sleep 10
    fi
    
    check_caddy_logs
    test_local_connectivity
    
    echo ""
    echo "🔧 Available fixes:"
    echo "1. Setup for local development (HTTP only, no SSL)"
    echo "2. Setup for production deployment (with Let's Encrypt SSL)"
    echo "3. View detailed logs"
    echo "4. Exit"
    echo ""
    
    read -p "Choose an option (1-4): " choice
    
    case $choice in
        1)
            fix_local_development
            ;;
        2)
            fix_production_deployment
            ;;
        3)
            echo "\n📋 Recent application logs:"
            docker compose --env-file .env.production -f docker-compose.production.yml logs --tail=50 app
            ;;
        4)
            print_status "Exiting..."
            exit 0
            ;;
        *)
            print_error "Invalid option selected"
            exit 1
            ;;
    esac
}

# Run main function
main