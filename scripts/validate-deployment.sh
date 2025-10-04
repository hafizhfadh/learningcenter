#!/bin/bash

# Learning Center Deployment Validation Script
# Tests all aspects of the production deployment
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
APP_URL="http://localhost:8080"
DOCKER_COMPOSE_FILE="$PROJECT_ROOT/docker/production/resource-optimization.yml"

# Test results
TESTS_PASSED=0
TESTS_FAILED=0
FAILED_TESTS=()

# Logging functions
info() {
    echo -e "${BLUE}[INFO]${NC} $*"
}

success() {
    echo -e "${GREEN}[PASS]${NC} $*"
    ((TESTS_PASSED++))
}

error() {
    echo -e "${RED}[FAIL]${NC} $*"
    ((TESTS_FAILED++))
    FAILED_TESTS+=("$*")
}

warning() {
    echo -e "${YELLOW}[WARN]${NC} $*"
}

# Test Docker containers are running
test_containers_running() {
    info "Testing Docker containers..."
    
    local expected_containers=("learning-center-app" "learning-center-nginx" "learning-center-postgres" "learning-center-redis")
    
    for container in "${expected_containers[@]}"; do
        if docker ps --format "{{.Names}}" | grep -q "^${container}$"; then
            success "Container $container is running"
        else
            error "Container $container is not running"
        fi
    done
}

# Test container health
test_container_health() {
    info "Testing container health..."
    
    # Check container health status
    local containers=$(docker ps --format "{{.Names}}")
    
    for container in $containers; do
        local health_status=$(docker inspect --format='{{.State.Health.Status}}' "$container" 2>/dev/null || echo "no-health-check")
        
        if [[ "$health_status" == "healthy" ]]; then
            success "Container $container is healthy"
        elif [[ "$health_status" == "no-health-check" ]]; then
            warning "Container $container has no health check configured"
        else
            error "Container $container health status: $health_status"
        fi
    done
}

# Test application endpoints
test_application_endpoints() {
    info "Testing application endpoints..."
    
    # Test health endpoint
    if curl -f -s "$APP_URL/health" > /dev/null; then
        success "Health endpoint is accessible"
    else
        error "Health endpoint is not accessible"
    fi
    
    # Test main application
    local response_code=$(curl -s -o /dev/null -w "%{http_code}" "$APP_URL" || echo "000")
    if [[ "$response_code" == "200" ]]; then
        success "Main application endpoint returns 200"
    else
        error "Main application endpoint returns $response_code"
    fi
    
    # Test API endpoints (if available)
    local api_response=$(curl -s -o /dev/null -w "%{http_code}" "$APP_URL/api/health" || echo "000")
    if [[ "$api_response" == "200" ]]; then
        success "API health endpoint returns 200"
    elif [[ "$api_response" == "404" ]]; then
        warning "API health endpoint not found (may not be implemented)"
    else
        error "API health endpoint returns $api_response"
    fi
}

# Test database connectivity
test_database_connectivity() {
    info "Testing database connectivity..."
    
    # Test PostgreSQL connection
    if docker exec learning-center-postgres pg_isready -U postgres > /dev/null 2>&1; then
        success "PostgreSQL is accepting connections"
    else
        error "PostgreSQL is not accepting connections"
    fi
    
    # Test database exists
    local db_exists=$(docker exec learning-center-postgres psql -U postgres -lqt | cut -d \| -f 1 | grep -w learning_center | wc -l)
    if [[ "$db_exists" -gt 0 ]]; then
        success "Learning Center database exists"
    else
        warning "Learning Center database may not exist"
    fi
}

# Test Redis connectivity
test_redis_connectivity() {
    info "Testing Redis connectivity..."
    
    # Test Redis ping
    if docker exec learning-center-redis redis-cli ping | grep -q "PONG"; then
        success "Redis is responding to ping"
    else
        error "Redis is not responding to ping"
    fi
    
    # Test Redis memory usage
    local memory_usage=$(docker exec learning-center-redis redis-cli info memory | grep used_memory_human | cut -d: -f2 | tr -d '\r')
    if [[ -n "$memory_usage" ]]; then
        success "Redis memory usage: $memory_usage"
    else
        error "Could not retrieve Redis memory usage"
    fi
}

# Test resource limits
test_resource_limits() {
    info "Testing resource limits..."
    
    # Check container resource usage
    local containers=$(docker ps --format "{{.Names}}")
    
    for container in $containers; do
        local cpu_usage=$(docker stats --no-stream --format "{{.CPUPerc}}" "$container" | sed 's/%//')
        local mem_usage=$(docker stats --no-stream --format "{{.MemPerc}}" "$container" | sed 's/%//')
        
        if (( $(echo "$cpu_usage < 80" | bc -l) )); then
            success "Container $container CPU usage: ${cpu_usage}%"
        else
            warning "Container $container high CPU usage: ${cpu_usage}%"
        fi
        
        if (( $(echo "$mem_usage < 80" | bc -l) )); then
            success "Container $container memory usage: ${mem_usage}%"
        else
            warning "Container $container high memory usage: ${mem_usage}%"
        fi
    done
}

# Test security configurations
test_security_configurations() {
    info "Testing security configurations..."
    
    # Test containers are not running as root
    local containers=$(docker ps --format "{{.Names}}")
    
    for container in $containers; do
        local user=$(docker exec "$container" whoami 2>/dev/null || echo "unknown")
        if [[ "$user" != "root" ]]; then
            success "Container $container is not running as root (user: $user)"
        else
            error "Container $container is running as root"
        fi
    done
    
    # Test security headers
    local security_headers=("X-Content-Type-Options" "X-Frame-Options" "X-XSS-Protection")
    
    for header in "${security_headers[@]}"; do
        if curl -s -I "$APP_URL" | grep -qi "$header"; then
            success "Security header $header is present"
        else
            warning "Security header $header is missing"
        fi
    done
}

# Test SSL/TLS configuration (if enabled)
test_ssl_configuration() {
    info "Testing SSL/TLS configuration..."
    
    # Check if HTTPS is configured
    local https_response=$(curl -s -o /dev/null -w "%{http_code}" "https://localhost" 2>/dev/null || echo "000")
    
    if [[ "$https_response" == "200" ]]; then
        success "HTTPS is configured and working"
        
        # Test SSL certificate
        local ssl_info=$(echo | openssl s_client -connect localhost:443 2>/dev/null | openssl x509 -noout -dates 2>/dev/null || echo "")
        if [[ -n "$ssl_info" ]]; then
            success "SSL certificate information retrieved"
        else
            warning "Could not retrieve SSL certificate information"
        fi
    else
        warning "HTTPS is not configured or not working (this may be expected in development)"
    fi
}

# Test backup functionality
test_backup_functionality() {
    info "Testing backup functionality..."
    
    # Check if backup directory exists
    if [[ -d "/var/backups/learning-center" ]]; then
        success "Backup directory exists"
        
        # Check recent backups
        local recent_backups=$(find /var/backups/learning-center -name "backup_*.sql" -mtime -1 2>/dev/null | wc -l)
        if [[ "$recent_backups" -gt 0 ]]; then
            success "Recent backups found: $recent_backups"
        else
            warning "No recent backups found"
        fi
    else
        warning "Backup directory does not exist"
    fi
}

# Test monitoring setup
test_monitoring_setup() {
    info "Testing monitoring setup..."
    
    # Check if monitoring script exists
    if [[ -f "/usr/local/bin/resource-monitor.sh" ]]; then
        success "Resource monitor script is installed"
    else
        warning "Resource monitor script is not installed"
    fi
    
    # Check if monitoring service is running
    if systemctl is-active --quiet learning-center-monitor 2>/dev/null; then
        success "Monitoring service is running"
    else
        warning "Monitoring service is not running"
    fi
    
    # Check monitoring logs
    if [[ -f "/var/log/resource-monitor.log" ]]; then
        local log_size=$(stat -f%z "/var/log/resource-monitor.log" 2>/dev/null || stat -c%s "/var/log/resource-monitor.log" 2>/dev/null || echo "0")
        if [[ "$log_size" -gt 0 ]]; then
            success "Monitoring logs are being generated"
        else
            warning "Monitoring logs are empty"
        fi
    else
        warning "Monitoring log file does not exist"
    fi
}

# Test performance benchmarks
test_performance_benchmarks() {
    info "Testing performance benchmarks..."
    
    # Simple load test
    local start_time=$(date +%s.%N)
    for i in {1..10}; do
        curl -s "$APP_URL/health" > /dev/null || true
    done
    local end_time=$(date +%s.%N)
    local duration=$(echo "$end_time - $start_time" | bc)
    local avg_response=$(echo "scale=3; $duration / 10" | bc)
    
    if (( $(echo "$avg_response < 1.0" | bc -l) )); then
        success "Average response time: ${avg_response}s"
    else
        warning "Slow average response time: ${avg_response}s"
    fi
    
    # Check system load
    local load_avg=$(uptime | awk -F'load average:' '{print $2}' | awk '{print $1}' | sed 's/,//')
    if (( $(echo "$load_avg < 2.0" | bc -l) )); then
        success "System load average: $load_avg"
    else
        warning "High system load average: $load_avg"
    fi
}

# Generate test report
generate_report() {
    echo ""
    echo "=============================================="
    echo "           DEPLOYMENT VALIDATION REPORT"
    echo "=============================================="
    echo "Test Date: $(date)"
    echo "Total Tests: $((TESTS_PASSED + TESTS_FAILED))"
    echo "Passed: $TESTS_PASSED"
    echo "Failed: $TESTS_FAILED"
    echo ""
    
    if [[ $TESTS_FAILED -eq 0 ]]; then
        success "All tests passed! Deployment is healthy."
        echo ""
        info "System Summary:"
        echo "- Application URL: $APP_URL"
        echo "- Docker Containers: $(docker ps --format "{{.Names}}" | wc -l) running"
        echo "- System Load: $(uptime | awk -F'load average:' '{print $2}' | awk '{print $1}' | sed 's/,//')"
        echo "- Memory Usage: $(free -h | awk 'NR==2{printf "%.1f%%", $3/$2*100}')"
        echo "- Disk Usage: $(df -h / | awk 'NR==2{print $5}')"
    else
        error "Some tests failed. Please review the following issues:"
        for failed_test in "${FAILED_TESTS[@]}"; do
            echo "  - $failed_test"
        done
        echo ""
        warning "Please address these issues before considering the deployment complete."
    fi
    
    echo "=============================================="
}

# Main function
main() {
    info "Starting Learning Center Deployment Validation"
    info "=============================================="
    
    test_containers_running
    test_container_health
    test_application_endpoints
    test_database_connectivity
    test_redis_connectivity
    test_resource_limits
    test_security_configurations
    test_ssl_configuration
    test_backup_functionality
    test_monitoring_setup
    test_performance_benchmarks
    
    generate_report
    
    # Exit with appropriate code
    if [[ $TESTS_FAILED -eq 0 ]]; then
        exit 0
    else
        exit 1
    fi
}

# Parse command line arguments
case "${1:-all}" in
    "all")
        main
        ;;
    "containers")
        test_containers_running
        test_container_health
        ;;
    "endpoints")
        test_application_endpoints
        ;;
    "database")
        test_database_connectivity
        ;;
    "redis")
        test_redis_connectivity
        ;;
    "security")
        test_security_configurations
        test_ssl_configuration
        ;;
    "performance")
        test_performance_benchmarks
        ;;
    "monitoring")
        test_monitoring_setup
        ;;
    *)
        echo "Usage: $0 {all|containers|endpoints|database|redis|security|performance|monitoring}"
        echo ""
        echo "Test Categories:"
        echo "  all         - Run all validation tests (default)"
        echo "  containers  - Test Docker containers"
        echo "  endpoints   - Test application endpoints"
        echo "  database    - Test database connectivity"
        echo "  redis       - Test Redis connectivity"
        echo "  security    - Test security configurations"
        echo "  performance - Test performance benchmarks"
        echo "  monitoring  - Test monitoring setup"
        exit 1
        ;;
esac