#!/bin/bash

# Health Check Script for Production Environment
# This script performs comprehensive health checks on all services

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
DOCKER_COMPOSE_FILE="${DOCKER_COMPOSE_FILE:-docker-compose.production.yml}"
HEALTH_CHECK_TIMEOUT="${HEALTH_CHECK_TIMEOUT:-30}"
SLACK_WEBHOOK_URL="${SLACK_WEBHOOK_URL:-}"
EMAIL_RECIPIENT="${EMAIL_RECIPIENT:-}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Health check results
HEALTH_RESULTS=()
FAILED_CHECKS=0
TOTAL_CHECKS=0

# Logging functions
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Add health check result
add_result() {
    local service="$1"
    local status="$2"
    local message="$3"
    
    HEALTH_RESULTS+=("$service|$status|$message")
    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
    
    if [[ "$status" == "FAIL" ]]; then
        FAILED_CHECKS=$((FAILED_CHECKS + 1))
        error "$service: $message"
    else
        success "$service: $message"
    fi
}

# Check if Docker containers are running
check_containers() {
    log "Checking Docker containers..."
    
    local services=("nginx" "caddy" "app" "postgres" "redis" "horizon" "scheduler")
    
    for service in "${services[@]}"; do
        if docker-compose -f "$DOCKER_COMPOSE_FILE" ps "$service" | grep -q "Up"; then
            add_result "$service" "PASS" "Container is running"
        else
            add_result "$service" "FAIL" "Container is not running"
        fi
    done
}

# Check application health endpoint
check_app_health() {
    log "Checking application health endpoint..."
    
    local health_url="http://localhost/health"
    
    if curl -f -s -m "$HEALTH_CHECK_TIMEOUT" "$health_url" > /dev/null; then
        local response=$(curl -s -m "$HEALTH_CHECK_TIMEOUT" "$health_url")
        if echo "$response" | grep -q '"status":"ok"'; then
            add_result "app-health" "PASS" "Health endpoint responding correctly"
        else
            add_result "app-health" "FAIL" "Health endpoint returned unexpected response"
        fi
    else
        add_result "app-health" "FAIL" "Health endpoint not accessible"
    fi
}

# Check database connectivity
check_database() {
    log "Checking database connectivity..."
    
    if docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T postgres pg_isready -U laravel > /dev/null 2>&1; then
        add_result "database" "PASS" "Database is accepting connections"
        
        # Check database size and connections
        local db_info=$(docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T postgres psql -U laravel -d laravel_production -t -c "SELECT pg_database_size('laravel_production'), count(*) FROM pg_stat_activity WHERE datname='laravel_production';")
        add_result "database-stats" "PASS" "Database stats: $db_info"
    else
        add_result "database" "FAIL" "Database is not accepting connections"
    fi
}

# Check Redis connectivity
check_redis() {
    log "Checking Redis connectivity..."
    
    if docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T redis redis-cli ping | grep -q "PONG"; then
        add_result "redis" "PASS" "Redis is responding to ping"
        
        # Check Redis memory usage
        local redis_info=$(docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T redis redis-cli info memory | grep used_memory_human)
        add_result "redis-memory" "PASS" "Redis memory: $redis_info"
    else
        add_result "redis" "FAIL" "Redis is not responding"
    fi
}

# Check queue workers
check_queues() {
    log "Checking queue workers..."
    
    # Check Horizon status
    if docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T horizon php artisan horizon:status | grep -q "running"; then
        add_result "horizon" "PASS" "Horizon is running"
    else
        add_result "horizon" "FAIL" "Horizon is not running"
    fi
    
    # Check failed jobs
    local failed_jobs=$(docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T app php artisan queue:failed --format=json | jq length 2>/dev/null || echo "0")
    if [[ "$failed_jobs" -eq 0 ]]; then
        add_result "failed-jobs" "PASS" "No failed jobs"
    else
        add_result "failed-jobs" "WARN" "$failed_jobs failed jobs found"
    fi
}

# Check SSL certificates
check_ssl() {
    log "Checking SSL certificates..."
    
    local domain="${APP_DOMAIN:-localhost}"
    
    if command -v openssl &> /dev/null; then
        local cert_info=$(echo | openssl s_client -servername "$domain" -connect "$domain:443" 2>/dev/null | openssl x509 -noout -dates 2>/dev/null)
        if [[ -n "$cert_info" ]]; then
            local expiry=$(echo "$cert_info" | grep "notAfter" | cut -d= -f2)
            local expiry_timestamp=$(date -d "$expiry" +%s 2>/dev/null || date -j -f "%b %d %H:%M:%S %Y %Z" "$expiry" +%s 2>/dev/null || echo "0")
            local current_timestamp=$(date +%s)
            local days_until_expiry=$(( (expiry_timestamp - current_timestamp) / 86400 ))
            
            if [[ "$days_until_expiry" -gt 30 ]]; then
                add_result "ssl-cert" "PASS" "SSL certificate valid for $days_until_expiry days"
            elif [[ "$days_until_expiry" -gt 7 ]]; then
                add_result "ssl-cert" "WARN" "SSL certificate expires in $days_until_expiry days"
            else
                add_result "ssl-cert" "FAIL" "SSL certificate expires in $days_until_expiry days"
            fi
        else
            add_result "ssl-cert" "FAIL" "Could not retrieve SSL certificate information"
        fi
    else
        add_result "ssl-cert" "SKIP" "OpenSSL not available for certificate check"
    fi
}

# Check disk space
check_disk_space() {
    log "Checking disk space..."
    
    local disk_usage=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
    
    if [[ "$disk_usage" -lt 80 ]]; then
        add_result "disk-space" "PASS" "Disk usage: ${disk_usage}%"
    elif [[ "$disk_usage" -lt 90 ]]; then
        add_result "disk-space" "WARN" "Disk usage: ${disk_usage}%"
    else
        add_result "disk-space" "FAIL" "Disk usage: ${disk_usage}%"
    fi
}

# Check memory usage
check_memory() {
    log "Checking memory usage..."
    
    local memory_info=$(free | grep Mem)
    local total=$(echo "$memory_info" | awk '{print $2}')
    local used=$(echo "$memory_info" | awk '{print $3}')
    local usage_percent=$((used * 100 / total))
    
    if [[ "$usage_percent" -lt 80 ]]; then
        add_result "memory" "PASS" "Memory usage: ${usage_percent}%"
    elif [[ "$usage_percent" -lt 90 ]]; then
        add_result "memory" "WARN" "Memory usage: ${usage_percent}%"
    else
        add_result "memory" "FAIL" "Memory usage: ${usage_percent}%"
    fi
}

# Check load average
check_load() {
    log "Checking system load..."
    
    local load_avg=$(uptime | awk -F'load average:' '{print $2}' | awk '{print $1}' | sed 's/,//')
    local cpu_cores=$(nproc)
    local load_percent=$(echo "$load_avg * 100 / $cpu_cores" | bc -l | cut -d. -f1)
    
    if [[ "$load_percent" -lt 70 ]]; then
        add_result "load-avg" "PASS" "Load average: $load_avg (${load_percent}% of $cpu_cores cores)"
    elif [[ "$load_percent" -lt 90 ]]; then
        add_result "load-avg" "WARN" "Load average: $load_avg (${load_percent}% of $cpu_cores cores)"
    else
        add_result "load-avg" "FAIL" "Load average: $load_avg (${load_percent}% of $cpu_cores cores)"
    fi
}

# Check log files for errors
check_logs() {
    log "Checking recent log files for errors..."
    
    local error_count=0
    
    # Check Laravel logs
    if [[ -f "$PROJECT_ROOT/storage/logs/laravel.log" ]]; then
        error_count=$(tail -n 1000 "$PROJECT_ROOT/storage/logs/laravel.log" | grep -c "ERROR\|CRITICAL\|EMERGENCY" || echo "0")
    fi
    
    # Check Docker logs for errors
    local docker_errors=$(docker-compose -f "$DOCKER_COMPOSE_FILE" logs --tail=100 2>&1 | grep -c "ERROR\|CRITICAL\|FATAL" || echo "0")
    error_count=$((error_count + docker_errors))
    
    if [[ "$error_count" -eq 0 ]]; then
        add_result "logs" "PASS" "No recent errors in logs"
    elif [[ "$error_count" -lt 10 ]]; then
        add_result "logs" "WARN" "$error_count errors found in recent logs"
    else
        add_result "logs" "FAIL" "$error_count errors found in recent logs"
    fi
}

# Send notification
send_notification() {
    local status="$1"
    local message="$2"
    
    # Send Slack notification
    if [[ -n "$SLACK_WEBHOOK_URL" ]]; then
        local color="good"
        if [[ "$status" == "FAIL" ]]; then
            color="danger"
        elif [[ "$status" == "WARN" ]]; then
            color="warning"
        fi
        
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"attachments\":[{\"color\":\"$color\",\"text\":\"$message\"}]}" \
            "$SLACK_WEBHOOK_URL" > /dev/null 2>&1 || true
    fi
    
    # Send email notification
    if [[ -n "$EMAIL_RECIPIENT" ]] && command -v mail &> /dev/null; then
        echo "$message" | mail -s "Health Check Alert - $status" "$EMAIL_RECIPIENT" || true
    fi
}

# Generate health report
generate_report() {
    log "Generating health report..."
    
    local report_file="/tmp/health-report-$(date +%Y%m%d_%H%M%S).txt"
    
    {
        echo "Health Check Report - $(date)"
        echo "=================================="
        echo ""
        echo "Summary: $((TOTAL_CHECKS - FAILED_CHECKS))/$TOTAL_CHECKS checks passed"
        echo ""
        
        for result in "${HEALTH_RESULTS[@]}"; do
            IFS='|' read -r service status message <<< "$result"
            printf "%-20s %-6s %s\n" "$service" "$status" "$message"
        done
    } > "$report_file"
    
    cat "$report_file"
    
    # Send notification if there are failures
    if [[ "$FAILED_CHECKS" -gt 0 ]]; then
        local notification_message="Health check failed: $FAILED_CHECKS/$TOTAL_CHECKS checks failed. See full report: $report_file"
        send_notification "FAIL" "$notification_message"
    fi
}

# Main health check function
run_health_checks() {
    log "Starting comprehensive health checks..."
    
    check_containers
    check_app_health
    check_database
    check_redis
    check_queues
    check_ssl
    check_disk_space
    check_memory
    check_load
    check_logs
    
    generate_report
    
    if [[ "$FAILED_CHECKS" -eq 0 ]]; then
        success "All health checks passed!"
        exit 0
    else
        error "$FAILED_CHECKS health checks failed!"
        exit 1
    fi
}

# Script usage
usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --containers     Check Docker containers only"
    echo "  --app           Check application health only"
    echo "  --database      Check database only"
    echo "  --redis         Check Redis only"
    echo "  --queues        Check queue workers only"
    echo "  --ssl           Check SSL certificates only"
    echo "  --system        Check system resources only"
    echo "  --logs          Check log files only"
    echo "  --all           Run all health checks (default)"
    echo "  -h, --help      Show this help message"
}

# Main script logic
case "${1:-all}" in
    --containers)
        check_containers
        ;;
    --app)
        check_app_health
        ;;
    --database)
        check_database
        ;;
    --redis)
        check_redis
        ;;
    --queues)
        check_queues
        ;;
    --ssl)
        check_ssl
        ;;
    --system)
        check_disk_space
        check_memory
        check_load
        ;;
    --logs)
        check_logs
        ;;
    --all|all)
        run_health_checks
        ;;
    -h|--help)
        usage
        ;;
    *)
        run_health_checks
        ;;
esac