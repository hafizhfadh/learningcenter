#!/bin/bash

# Security Validation Script for Learning Center Production Environment
# This script validates all security configurations and measures

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DOMAIN="${DOMAIN:-learningcenter.com}"
APP_PORT="${APP_PORT:-8080}"
HTTPS_PORT="${HTTPS_PORT:-8443}"
LOG_FILE="/var/log/security-validation.log"

# Functions
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}" | tee -a "$LOG_FILE"
}

warn() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}" | tee -a "$LOG_FILE"
    return 1
}

info() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] INFO: $1${NC}" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] ✓ $1${NC}" | tee -a "$LOG_FILE"
}

fail() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ✗ $1${NC}" | tee -a "$LOG_FILE"
    return 1
}

# Test functions
test_docker_security() {
    info "Testing Docker security configurations..."
    
    # Check if containers are running as non-root
    if docker exec learningcenter-app id | grep -q "uid=1001"; then
        success "Application container running as non-root user"
    else
        fail "Application container running as root - security risk!"
    fi
    
    # Check security options
    if docker inspect learningcenter-app | grep -q "no-new-privileges"; then
        success "No-new-privileges security option enabled"
    else
        fail "No-new-privileges security option not enabled"
    fi
    
    # Check read-only filesystem
    if docker inspect learningcenter-app | grep -q '"ReadonlyRootfs": true'; then
        success "Read-only root filesystem enabled"
    else
        warn "Read-only root filesystem not enabled"
    fi
    
    # Check capability drops
    if docker inspect learningcenter-app | grep -q "CapDrop"; then
        success "Capabilities properly dropped"
    else
        fail "Capabilities not properly restricted"
    fi
}

test_network_security() {
    info "Testing network security..."
    
    # Test HTTPS redirect
    if curl -s -I "http://localhost:$APP_PORT" | grep -q "301\|302"; then
        success "HTTP to HTTPS redirect working"
    else
        warn "HTTP to HTTPS redirect not configured"
    fi
    
    # Test security headers
    local headers=$(curl -s -I "https://localhost:$HTTPS_PORT" 2>/dev/null || curl -s -I "http://localhost:$APP_PORT")
    
    if echo "$headers" | grep -q "Strict-Transport-Security"; then
        success "HSTS header present"
    else
        fail "HSTS header missing"
    fi
    
    if echo "$headers" | grep -q "Content-Security-Policy"; then
        success "CSP header present"
    else
        fail "CSP header missing"
    fi
    
    if echo "$headers" | grep -q "X-Frame-Options"; then
        success "X-Frame-Options header present"
    else
        fail "X-Frame-Options header missing"
    fi
    
    if echo "$headers" | grep -q "X-Content-Type-Options"; then
        success "X-Content-Type-Options header present"
    else
        fail "X-Content-Type-Options header missing"
    fi
}

test_rate_limiting() {
    info "Testing rate limiting..."
    
    # Test rate limiting by making multiple requests
    local rate_limit_triggered=false
    for i in {1..60}; do
        response=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:$APP_PORT/api/test" 2>/dev/null || echo "000")
        if [ "$response" = "429" ]; then
            rate_limit_triggered=true
            break
        fi
        sleep 0.1
    done
    
    if [ "$rate_limit_triggered" = true ]; then
        success "Rate limiting is working"
    else
        warn "Rate limiting may not be properly configured"
    fi
}

test_attack_protection() {
    info "Testing attack protection..."
    
    # Test SQL injection protection
    local sql_response=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:$APP_PORT/?id=1' OR '1'='1" 2>/dev/null || echo "000")
    if [ "$sql_response" = "403" ] || [ "$sql_response" = "400" ]; then
        success "SQL injection protection working"
    else
        warn "SQL injection protection may not be working (response: $sql_response)"
    fi
    
    # Test XSS protection
    local xss_response=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:$APP_PORT/?search=<script>alert('xss')</script>" 2>/dev/null || echo "000")
    if [ "$xss_response" = "403" ] || [ "$xss_response" = "400" ]; then
        success "XSS protection working"
    else
        warn "XSS protection may not be working (response: $xss_response)"
    fi
    
    # Test path traversal protection
    local path_response=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:$APP_PORT/../../../etc/passwd" 2>/dev/null || echo "000")
    if [ "$path_response" = "403" ] || [ "$path_response" = "404" ]; then
        success "Path traversal protection working"
    else
        warn "Path traversal protection may not be working (response: $path_response)"
    fi
}

test_file_permissions() {
    info "Testing file permissions..."
    
    # Check application file permissions
    if docker exec learningcenter-app find /app -type f -perm /o+w | grep -q .; then
        fail "World-writable files found in application directory"
    else
        success "No world-writable files in application directory"
    fi
    
    # Check sensitive file permissions
    if docker exec learningcenter-app test -r /app/.env; then
        if docker exec learningcenter-app stat -c "%a" /app/.env | grep -q "^600$\|^640$"; then
            success "Environment file has secure permissions"
        else
            fail "Environment file has insecure permissions"
        fi
    else
        warn "Environment file not found or not readable"
    fi
}

test_logging_security() {
    info "Testing security logging..."
    
    # Check if security logs are being created
    if docker exec learningcenter-app test -d /var/log/security; then
        success "Security log directory exists"
        
        # Check log file permissions
        if docker exec learningcenter-app find /var/log/security -type f -perm /o+r | grep -q .; then
            fail "Security log files are world-readable"
        else
            success "Security log files have proper permissions"
        fi
    else
        fail "Security log directory not found"
    fi
}

test_ssl_configuration() {
    info "Testing SSL/TLS configuration..."
    
    # Test SSL certificate (if available)
    if command -v openssl >/dev/null 2>&1; then
        local ssl_info=$(echo | openssl s_client -connect "localhost:$HTTPS_PORT" -servername "$DOMAIN" 2>/dev/null | openssl x509 -noout -text 2>/dev/null || echo "")
        
        if echo "$ssl_info" | grep -q "TLS Web Server Authentication"; then
            success "SSL certificate is valid for web server authentication"
        else
            warn "SSL certificate validation failed or not available"
        fi
        
        # Check for weak ciphers
        local weak_ciphers=$(echo | openssl s_client -connect "localhost:$HTTPS_PORT" -cipher 'RC4:MD5:DES' 2>/dev/null | grep -c "Cipher is" || echo "0")
        if [ "$weak_ciphers" -eq 0 ]; then
            success "No weak ciphers detected"
        else
            fail "Weak ciphers detected in SSL configuration"
        fi
    else
        warn "OpenSSL not available for SSL testing"
    fi
}

test_database_security() {
    info "Testing database security..."
    
    # Check if database is accessible from outside
    if docker exec learningcenter-postgres pg_isready -h localhost -p 5432 >/dev/null 2>&1; then
        success "Database is accessible internally"
        
        # Check if database is not accessible externally (should fail)
        if timeout 5 nc -z localhost 5432 2>/dev/null; then
            warn "Database port may be exposed externally"
        else
            success "Database port is not exposed externally"
        fi
    else
        fail "Database is not accessible"
    fi
}

test_redis_security() {
    info "Testing Redis security..."
    
    # Check Redis authentication
    if docker exec learningcenter-redis redis-cli ping >/dev/null 2>&1; then
        success "Redis is accessible internally"
        
        # Check if Redis requires authentication
        if docker exec learningcenter-redis redis-cli --no-auth-warning info server >/dev/null 2>&1; then
            warn "Redis may not require authentication"
        else
            success "Redis requires authentication"
        fi
    else
        fail "Redis is not accessible"
    fi
}

generate_security_report() {
    info "Generating security report..."
    
    local report_file="/tmp/security-report-$(date +%Y%m%d_%H%M%S).txt"
    
    cat > "$report_file" << EOF
Security Validation Report
Generated: $(date)
Domain: $DOMAIN
Application Port: $APP_PORT
HTTPS Port: $HTTPS_PORT

=== SUMMARY ===
This report contains the results of security validation tests
performed on the Learning Center production environment.

=== RECOMMENDATIONS ===
1. Regularly update all dependencies and base images
2. Monitor security logs for suspicious activities
3. Perform regular security audits and penetration testing
4. Keep SSL certificates up to date
5. Review and update security configurations quarterly

=== LOG ANALYSIS ===
Recent security events can be found in:
- /var/log/security/security.log
- /var/log/security/auth_failures.log
- /var/log/security/suspicious.log

=== NEXT STEPS ===
1. Address any failed security tests
2. Implement additional monitoring if needed
3. Schedule regular security validation runs
4. Update incident response procedures

EOF

    log "Security report generated: $report_file"
    cat "$report_file"
}

# Main execution
main() {
    log "Starting security validation for Learning Center..."
    
    # Create log file
    touch "$LOG_FILE"
    chmod 640 "$LOG_FILE"
    
    # Run all security tests
    test_docker_security || true
    test_network_security || true
    test_rate_limiting || true
    test_attack_protection || true
    test_file_permissions || true
    test_logging_security || true
    test_ssl_configuration || true
    test_database_security || true
    test_redis_security || true
    
    # Generate report
    generate_security_report
    
    log "Security validation completed. Check the report for details."
}

# Check if script is run directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi