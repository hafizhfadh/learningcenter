#!/bin/bash

# Learning Center Disaster Recovery System
# Comprehensive disaster recovery and business continuity script
# Handles complete system restoration and failover procedures

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
BACKUP_BASE_DIR="${BACKUP_BASE_DIR:-/var/backups/learning-center}"
DR_CONFIG_FILE="${DR_CONFIG_FILE:-$PROJECT_ROOT/docker/production/disaster-recovery.conf}"
RECOVERY_LOG_FILE="${RECOVERY_LOG_FILE:-/var/log/learning-center-recovery.log}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging
log() {
    local level="$1"
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${timestamp} [${level}] ${message}" | tee -a "$RECOVERY_LOG_FILE"
}

log_info() { log "INFO" "$@"; }
log_warn() { log "WARN" "$@"; }
log_error() { log "ERROR" "$@"; }
log_success() { log "SUCCESS" "$@"; }

# Error handling
cleanup() {
    local exit_code=$?
    if [[ $exit_code -ne 0 ]]; then
        log_error "Disaster recovery failed with exit code $exit_code"
        send_alert "DR_FAILED" "Disaster recovery process failed with exit code $exit_code"
    fi
    exit $exit_code
}

trap cleanup EXIT

# Alert system
send_alert() {
    local alert_type="$1"
    local message="$2"
    
    # Critical alerts for disaster recovery
    if [[ -n "${EMERGENCY_WEBHOOK_URL:-}" ]]; then
        curl -s -X POST "$EMERGENCY_WEBHOOK_URL" \
            -H "Content-Type: application/json" \
            -d "{\"alert_type\":\"$alert_type\",\"message\":\"$message\",\"timestamp\":\"$(date -Iseconds)\",\"severity\":\"CRITICAL\"}" \
            || log_warn "Failed to send emergency webhook alert"
    fi
    
    if [[ -n "${EMERGENCY_EMAIL:-}" ]]; then
        echo "$message" | mail -s "CRITICAL: Learning Center DR Alert: $alert_type" "$EMERGENCY_EMAIL" \
            || log_warn "Failed to send emergency email alert"
    fi
    
    # SMS alerts for critical issues
    if [[ -n "${EMERGENCY_SMS_API:-}" && -n "${EMERGENCY_PHONE:-}" ]]; then
        curl -s -X POST "$EMERGENCY_SMS_API" \
            -d "to=$EMERGENCY_PHONE" \
            -d "message=CRITICAL: Learning Center DR Alert: $alert_type - $message" \
            || log_warn "Failed to send SMS alert"
    fi
}

# System health checks
check_system_health() {
    log_info "Performing system health checks..."
    
    local health_issues=()
    
    # Check disk space
    local disk_usage=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
    if [[ $disk_usage -gt 90 ]]; then
        health_issues+=("Disk usage critical: ${disk_usage}%")
    fi
    
    # Check memory
    local mem_usage=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
    if [[ $mem_usage -gt 90 ]]; then
        health_issues+=("Memory usage critical: ${mem_usage}%")
    fi
    
    # Check load average
    local load_avg=$(uptime | awk -F'load average:' '{print $2}' | awk '{print $1}' | sed 's/,//')
    local cpu_cores=$(nproc)
    if (( $(echo "$load_avg > $cpu_cores * 2" | bc -l) )); then
        health_issues+=("Load average critical: $load_avg (cores: $cpu_cores)")
    fi
    
    # Check Docker daemon
    if ! docker info >/dev/null 2>&1; then
        health_issues+=("Docker daemon not running")
    fi
    
    # Check network connectivity
    if ! ping -c 1 8.8.8.8 >/dev/null 2>&1; then
        health_issues+=("Network connectivity issues")
    fi
    
    if [[ ${#health_issues[@]} -gt 0 ]]; then
        log_warn "System health issues detected:"
        for issue in "${health_issues[@]}"; do
            log_warn "  - $issue"
        done
        return 1
    else
        log_success "System health checks passed"
        return 0
    fi
}

# Service health checks
check_service_health() {
    local service="$1"
    local max_retries="${2:-3}"
    local retry_delay="${3:-5}"
    
    for ((i=1; i<=max_retries; i++)); do
        case "$service" in
            "postgres")
                if docker-compose -f "$PROJECT_ROOT/docker-compose.production.yml" exec -T postgres pg_isready -U postgres >/dev/null 2>&1; then
                    log_success "PostgreSQL is healthy"
                    return 0
                fi
                ;;
            "redis")
                if docker-compose -f "$PROJECT_ROOT/docker-compose.production.yml" exec -T redis redis-cli ping | grep -q PONG; then
                    log_success "Redis is healthy"
                    return 0
                fi
                ;;
            "app")
                if curl -sf http://localhost:8080/health >/dev/null 2>&1; then
                    log_success "Application is healthy"
                    return 0
                fi
                ;;
            "nginx")
                if curl -sf http://localhost/health >/dev/null 2>&1; then
                    log_success "Nginx is healthy"
                    return 0
                fi
                ;;
        esac
        
        if [[ $i -lt $max_retries ]]; then
            log_warn "Service $service health check failed (attempt $i/$max_retries), retrying in ${retry_delay}s..."
            sleep $retry_delay
        fi
    done
    
    log_error "Service $service is not healthy after $max_retries attempts"
    return 1
}

# Backup validation
validate_backup() {
    local backup_id="$1"
    local backup_dir="$BACKUP_BASE_DIR/$backup_id"
    local manifest_file="$backup_dir/manifest-${backup_id}.json"
    
    log_info "Validating backup: $backup_id"
    
    if [[ ! -f "$manifest_file" ]]; then
        log_error "Backup manifest not found: $manifest_file"
        return 1
    fi
    
    # Verify all files exist and checksums match
    local files=$(jq -r '.files[] | @base64' "$manifest_file")
    for file_data in $files; do
        local file_info=$(echo "$file_data" | base64 --decode)
        local file_path=$(echo "$file_info" | jq -r '.path')
        local expected_checksum=$(echo "$file_info" | jq -r '.checksum')
        
        if [[ ! -f "$file_path" ]]; then
            log_error "Backup file missing: $file_path"
            return 1
        fi
        
        local actual_checksum=$(sha256sum "$file_path" | cut -d' ' -f1)
        if [[ "$actual_checksum" != "$expected_checksum" ]]; then
            log_error "Backup file corrupted: $file_path (checksum mismatch)"
            return 1
        fi
    done
    
    log_success "Backup validation passed: $backup_id"
    return 0
}

# Complete system restoration
restore_complete_system() {
    local backup_id="$1"
    local restore_mode="${2:-full}" # full, minimal, data-only
    
    log_info "Starting complete system restoration from backup: $backup_id"
    send_alert "DR_RESTORE_START" "Starting complete system restoration from backup: $backup_id"
    
    # Validate backup before proceeding
    if ! validate_backup "$backup_id"; then
        log_error "Backup validation failed, aborting restoration"
        return 1
    fi
    
    # Create restoration checkpoint
    local restore_checkpoint="/tmp/restore-checkpoint-$(date +%s)"
    echo "backup_id=$backup_id" > "$restore_checkpoint"
    echo "restore_mode=$restore_mode" >> "$restore_checkpoint"
    echo "start_time=$(date -Iseconds)" >> "$restore_checkpoint"
    
    # Stop all services
    log_info "Stopping all services..."
    docker-compose -f "$PROJECT_ROOT/docker-compose.production.yml" down || true
    
    # Restore based on mode
    case "$restore_mode" in
        "full")
            restore_configurations "$backup_id"
            restore_application_files "$backup_id"
            restore_database_services "$backup_id"
            ;;
        "minimal")
            restore_database_services "$backup_id"
            ;;
        "data-only")
            restore_postgresql_data "$backup_id"
            restore_redis_data "$backup_id"
            ;;
    esac
    
    # Start services in order
    log_info "Starting services..."
    start_services_ordered
    
    # Verify restoration
    if verify_restoration; then
        log_success "Complete system restoration completed successfully"
        send_alert "DR_RESTORE_SUCCESS" "Complete system restoration completed successfully from backup: $backup_id"
        rm -f "$restore_checkpoint"
        return 0
    else
        log_error "System restoration verification failed"
        send_alert "DR_RESTORE_FAILED" "System restoration verification failed for backup: $backup_id"
        return 1
    fi
}

# Restore individual components
restore_configurations() {
    local backup_id="$1"
    local backup_dir="$BACKUP_BASE_DIR/$backup_id"
    
    log_info "Restoring configurations..."
    
    # Find configuration backup file
    local config_file=$(find "$backup_dir" -name "configurations-*.tar.gz*" | head -1)
    if [[ -z "$config_file" ]]; then
        log_error "Configuration backup file not found"
        return 1
    fi
    
    # Extract configurations
    local temp_dir=$(mktemp -d)
    if [[ "$config_file" == *.gpg ]]; then
        gpg --quiet --batch --passphrase-file "$ENCRYPTION_KEY_FILE" --decrypt "$config_file" | tar -xzf - -C "$temp_dir"
    else
        tar -xzf "$config_file" -C "$temp_dir"
    fi
    
    # Restore configuration files
    cp -r "$temp_dir"/* "$PROJECT_ROOT/"
    rm -rf "$temp_dir"
    
    log_success "Configurations restored"
}

restore_application_files() {
    local backup_id="$1"
    local backup_dir="$BACKUP_BASE_DIR/$backup_id"
    
    log_info "Restoring application files..."
    
    # Find application backup file
    local app_file=$(find "$backup_dir" -name "application-*.tar.gz*" | head -1)
    if [[ -z "$app_file" ]]; then
        log_error "Application backup file not found"
        return 1
    fi
    
    # Create backup of current application
    local current_backup="/tmp/current-app-$(date +%s).tar.gz"
    tar -czf "$current_backup" -C "$PROJECT_ROOT" . 2>/dev/null || true
    
    # Extract application files
    if [[ "$app_file" == *.gpg ]]; then
        gpg --quiet --batch --passphrase-file "$ENCRYPTION_KEY_FILE" --decrypt "$app_file" | tar -xzf - -C "$PROJECT_ROOT"
    else
        tar -xzf "$app_file" -C "$PROJECT_ROOT"
    fi
    
    log_success "Application files restored (current backup: $current_backup)"
}

restore_database_services() {
    local backup_id="$1"
    
    log_info "Restoring database services..."
    
    # Start database services first
    docker-compose -f "$PROJECT_ROOT/docker-compose.production.yml" up -d postgres redis
    
    # Wait for services to be ready
    sleep 10
    
    # Restore PostgreSQL
    restore_postgresql_data "$backup_id"
    
    # Restore Redis
    restore_redis_data "$backup_id"
    
    log_success "Database services restored"
}

restore_postgresql_data() {
    local backup_id="$1"
    local backup_dir="$BACKUP_BASE_DIR/$backup_id"
    
    log_info "Restoring PostgreSQL data..."
    
    # Find PostgreSQL backup file
    local pg_file=$(find "$backup_dir" -name "postgresql-*.sql.gz*" | head -1)
    if [[ -z "$pg_file" ]]; then
        log_error "PostgreSQL backup file not found"
        return 1
    fi
    
    # Ensure PostgreSQL is running
    if ! check_service_health "postgres" 5 10; then
        log_error "PostgreSQL is not healthy, cannot restore"
        return 1
    fi
    
    # Restore database
    if [[ "$pg_file" == *.gpg ]]; then
        gpg --quiet --batch --passphrase-file "$ENCRYPTION_KEY_FILE" --decrypt "$pg_file" | \
        gunzip | \
        docker-compose -f "$PROJECT_ROOT/docker-compose.production.yml" exec -T postgres psql -U postgres
    else
        gunzip -c "$pg_file" | \
        docker-compose -f "$PROJECT_ROOT/docker-compose.production.yml" exec -T postgres psql -U postgres
    fi
    
    log_success "PostgreSQL data restored"
}

restore_redis_data() {
    local backup_id="$1"
    local backup_dir="$BACKUP_BASE_DIR/$backup_id"
    
    log_info "Restoring Redis data..."
    
    # Find Redis backup file
    local redis_file=$(find "$backup_dir" -name "redis-*.rdb.gz*" | head -1)
    if [[ -z "$redis_file" ]]; then
        log_error "Redis backup file not found"
        return 1
    fi
    
    # Stop Redis temporarily
    docker-compose -f "$PROJECT_ROOT/docker-compose.production.yml" stop redis
    
    # Restore RDB file
    local temp_rdb=$(mktemp)
    if [[ "$redis_file" == *.gpg ]]; then
        gpg --quiet --batch --passphrase-file "$ENCRYPTION_KEY_FILE" --decrypt "$redis_file" | gunzip > "$temp_rdb"
    else
        gunzip -c "$redis_file" > "$temp_rdb"
    fi
    
    # Copy RDB file to Redis data directory
    docker-compose -f "$PROJECT_ROOT/docker-compose.production.yml" run --rm -v "$temp_rdb:/tmp/dump.rdb" redis cp /tmp/dump.rdb /data/dump.rdb
    
    # Start Redis
    docker-compose -f "$PROJECT_ROOT/docker-compose.production.yml" start redis
    
    rm -f "$temp_rdb"
    log_success "Redis data restored"
}

# Service startup orchestration
start_services_ordered() {
    log_info "Starting services in proper order..."
    
    # Start infrastructure services first
    docker-compose -f "$PROJECT_ROOT/docker-compose.production.yml" up -d postgres redis
    
    # Wait for databases to be ready
    check_service_health "postgres" 10 5
    check_service_health "redis" 10 5
    
    # Start application services
    docker-compose -f "$PROJECT_ROOT/docker-compose.production.yml" up -d app
    
    # Wait for application to be ready
    check_service_health "app" 10 5
    
    # Start web services
    docker-compose -f "$PROJECT_ROOT/docker-compose.production.yml" up -d nginx
    
    # Wait for web server to be ready
    check_service_health "nginx" 5 3
    
    # Start monitoring services
    docker-compose -f "$PROJECT_ROOT/docker/production/logging.yml" up -d
    
    log_success "All services started successfully"
}

# Restoration verification
verify_restoration() {
    log_info "Verifying system restoration..."
    
    local verification_failed=false
    
    # Check all services are healthy
    for service in postgres redis app nginx; do
        if ! check_service_health "$service" 3 5; then
            verification_failed=true
        fi
    done
    
    # Check database connectivity and data integrity
    if ! verify_database_integrity; then
        verification_failed=true
    fi
    
    # Check application functionality
    if ! verify_application_functionality; then
        verification_failed=true
    fi
    
    # Check system resources
    if ! check_system_health; then
        verification_failed=true
    fi
    
    if [[ "$verification_failed" == "true" ]]; then
        log_error "System restoration verification failed"
        return 1
    else
        log_success "System restoration verification passed"
        return 0
    fi
}

verify_database_integrity() {
    log_info "Verifying database integrity..."
    
    # Check PostgreSQL
    local pg_result=$(docker-compose -f "$PROJECT_ROOT/docker-compose.production.yml" exec -T postgres psql -U postgres -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public';" 2>/dev/null | grep -E '^[0-9]+$' || echo "0")
    
    if [[ $pg_result -eq 0 ]]; then
        log_error "PostgreSQL integrity check failed: no tables found"
        return 1
    fi
    
    # Check Redis
    local redis_result=$(docker-compose -f "$PROJECT_ROOT/docker-compose.production.yml" exec -T redis redis-cli dbsize 2>/dev/null || echo "0")
    
    log_success "Database integrity verified (PG tables: $pg_result, Redis keys: $redis_result)"
    return 0
}

verify_application_functionality() {
    log_info "Verifying application functionality..."
    
    # Test health endpoint
    if ! curl -sf http://localhost/health >/dev/null 2>&1; then
        log_error "Application health endpoint not responding"
        return 1
    fi
    
    # Test API endpoints
    if ! curl -sf http://localhost/api/courses >/dev/null 2>&1; then
        log_error "API endpoints not responding"
        return 1
    fi
    
    log_success "Application functionality verified"
    return 0
}

# Failover procedures
initiate_failover() {
    local failover_type="${1:-automatic}" # automatic, manual
    local target_environment="${2:-secondary}"
    
    log_info "Initiating failover to $target_environment environment"
    send_alert "DR_FAILOVER_START" "Initiating $failover_type failover to $target_environment environment"
    
    # Pre-failover checks
    if ! pre_failover_checks "$target_environment"; then
        log_error "Pre-failover checks failed, aborting failover"
        return 1
    fi
    
    # Execute failover
    case "$target_environment" in
        "secondary")
            failover_to_secondary
            ;;
        "cloud")
            failover_to_cloud
            ;;
        "backup-site")
            failover_to_backup_site
            ;;
        *)
            log_error "Unknown failover target: $target_environment"
            return 1
            ;;
    esac
    
    # Post-failover verification
    if verify_failover "$target_environment"; then
        log_success "Failover to $target_environment completed successfully"
        send_alert "DR_FAILOVER_SUCCESS" "Failover to $target_environment completed successfully"
        return 0
    else
        log_error "Failover verification failed"
        send_alert "DR_FAILOVER_FAILED" "Failover to $target_environment verification failed"
        return 1
    fi
}

pre_failover_checks() {
    local target="$1"
    
    log_info "Performing pre-failover checks for $target..."
    
    # Check target environment availability
    case "$target" in
        "secondary")
            # Check secondary server connectivity
            if [[ -n "${SECONDARY_SERVER:-}" ]]; then
                if ! ssh -o ConnectTimeout=10 "$SECONDARY_SERVER" "echo 'Connection test'" >/dev/null 2>&1; then
                    log_error "Cannot connect to secondary server: $SECONDARY_SERVER"
                    return 1
                fi
            fi
            ;;
        "cloud")
            # Check cloud provider connectivity
            if ! curl -sf https://aws.amazon.com >/dev/null 2>&1; then
                log_error "Cannot connect to cloud provider"
                return 1
            fi
            ;;
    esac
    
    # Check recent backup availability
    local latest_backup=$(find "$BACKUP_BASE_DIR" -name "manifest-*.json" | sort -r | head -1)
    if [[ -z "$latest_backup" ]]; then
        log_error "No recent backups available for failover"
        return 1
    fi
    
    local backup_age=$(( $(date +%s) - $(stat -c %Y "$latest_backup") ))
    if [[ $backup_age -gt 86400 ]]; then # 24 hours
        log_warn "Latest backup is older than 24 hours"
    fi
    
    log_success "Pre-failover checks passed"
    return 0
}

# Generate disaster recovery report
generate_dr_report() {
    local report_file="/tmp/dr-report-$(date +%Y%m%d_%H%M%S).html"
    
    cat > "$report_file" << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>Learning Center Disaster Recovery Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background-color: #f0f0f0; padding: 10px; border-radius: 5px; }
        .section { margin: 20px 0; }
        .success { color: green; }
        .warning { color: orange; }
        .error { color: red; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Learning Center Disaster Recovery Report</h1>
        <p>Generated: $(date)</p>
    </div>
EOF

    # Add system status
    echo '<div class="section"><h2>System Status</h2>' >> "$report_file"
    if check_system_health >/dev/null 2>&1; then
        echo '<p class="success">✓ System health: GOOD</p>' >> "$report_file"
    else
        echo '<p class="error">✗ System health: ISSUES DETECTED</p>' >> "$report_file"
    fi
    echo '</div>' >> "$report_file"
    
    # Add service status
    echo '<div class="section"><h2>Service Status</h2><table>' >> "$report_file"
    echo '<tr><th>Service</th><th>Status</th></tr>' >> "$report_file"
    
    for service in postgres redis app nginx; do
        if check_service_health "$service" 1 1 >/dev/null 2>&1; then
            echo "<tr><td>$service</td><td class=\"success\">✓ Healthy</td></tr>" >> "$report_file"
        else
            echo "<tr><td>$service</td><td class=\"error\">✗ Unhealthy</td></tr>" >> "$report_file"
        fi
    done
    echo '</table></div>' >> "$report_file"
    
    # Add backup status
    echo '<div class="section"><h2>Backup Status</h2>' >> "$report_file"
    local backup_count=$(find "$BACKUP_BASE_DIR" -name "manifest-*.json" 2>/dev/null | wc -l)
    echo "<p>Available backups: $backup_count</p>" >> "$report_file"
    
    if [[ $backup_count -gt 0 ]]; then
        local latest_backup=$(find "$BACKUP_BASE_DIR" -name "manifest-*.json" | sort -r | head -1)
        local backup_date=$(jq -r '.backup_date' "$latest_backup" 2>/dev/null || echo "Unknown")
        echo "<p>Latest backup: $backup_date</p>" >> "$report_file"
    fi
    echo '</div>' >> "$report_file"
    
    echo '</body></html>' >> "$report_file"
    
    log_success "Disaster recovery report generated: $report_file"
    echo "$report_file"
}

# Usage information
show_usage() {
    cat << EOF
Learning Center Disaster Recovery System

Usage: $0 [COMMAND] [OPTIONS]

Commands:
  health                        - Check system and service health
  restore <backup_id> [mode]    - Restore system from backup
  failover [type] [target]      - Initiate failover procedure
  validate <backup_id>          - Validate backup for restoration
  report                        - Generate disaster recovery report
  test-dr                       - Run disaster recovery test

Restore Modes:
  full                          - Complete system restoration (default)
  minimal                       - Database services only
  data-only                     - Data restoration only

Failover Targets:
  secondary                     - Secondary server
  cloud                         - Cloud environment
  backup-site                   - Backup site

Environment Variables:
  BACKUP_BASE_DIR               - Base directory for backups
  DR_CONFIG_FILE                - Disaster recovery configuration file
  RECOVERY_LOG_FILE             - Recovery log file path
  EMERGENCY_WEBHOOK_URL         - Emergency webhook URL
  EMERGENCY_EMAIL               - Emergency email address
  EMERGENCY_SMS_API             - Emergency SMS API endpoint
  EMERGENCY_PHONE               - Emergency phone number
  SECONDARY_SERVER              - Secondary server address

Examples:
  $0 health                     - Check system health
  $0 restore 20240101_120000    - Full system restore
  $0 restore 20240101_120000 minimal - Minimal restore
  $0 failover automatic secondary - Automatic failover
  $0 report                     - Generate DR report

EOF
}

# Main script logic
main() {
    # Ensure log directory exists
    mkdir -p "$(dirname "$RECOVERY_LOG_FILE")"
    
    case "${1:-health}" in
        "health")
            check_system_health
            for service in postgres redis app nginx; do
                check_service_health "$service" 1 1 || true
            done
            ;;
        "restore")
            if [[ -z "${2:-}" ]]; then
                echo "Error: Backup ID required for restore"
                show_usage
                exit 1
            fi
            restore_complete_system "$2" "${3:-full}"
            ;;
        "failover")
            initiate_failover "${2:-automatic}" "${3:-secondary}"
            ;;
        "validate")
            if [[ -z "${2:-}" ]]; then
                echo "Error: Backup ID required for validation"
                show_usage
                exit 1
            fi
            validate_backup "$2"
            ;;
        "report")
            generate_dr_report
            ;;
        "test-dr")
            log_info "Running disaster recovery test..."
            # Implement DR test procedures
            ;;
        "help"|"-h"|"--help")
            show_usage
            ;;
        *)
            echo "Error: Unknown command: $1"
            show_usage
            exit 1
            ;;
    esac
}

# Run main function
main "$@"