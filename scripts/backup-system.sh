#!/bin/bash

# Learning Center Backup System (Optimized for External PostgreSQL)
# Supports Redis, application files, and configurations
# PostgreSQL backups are handled separately on database servers

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
BACKUP_BASE_DIR="${BACKUP_BASE_DIR:-/var/backups/learning-center}"
RETENTION_DAYS="${RETENTION_DAYS:-30}"
COMPRESSION_LEVEL="${COMPRESSION_LEVEL:-6}"
ENCRYPTION_KEY_FILE="${ENCRYPTION_KEY_FILE:-}"
S3_BUCKET="${S3_BUCKET:-}"
S3_PREFIX="${S3_PREFIX:-learning-center-backups}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging
LOG_FILE="${BACKUP_BASE_DIR}/logs/backup-$(date +%Y%m%d).log"

log() {
    local level="$1"
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${timestamp} [${level}] ${message}" | tee -a "$LOG_FILE"
}

log_info() { log "INFO" "$@"; }
log_warn() { log "WARN" "$@"; }
log_error() { log "ERROR" "$@"; }
log_success() { log "SUCCESS" "$@"; }

# Error handling
cleanup() {
    local exit_code=$?
    if [[ $exit_code -ne 0 ]]; then
        log_error "Backup failed with exit code $exit_code"
        send_alert "BACKUP_FAILED" "Backup process failed with exit code $exit_code"
    fi
    exit $exit_code
}

trap cleanup EXIT

# Help function
show_help() {
    cat << EOF
Learning Center Backup System (Optimized for External PostgreSQL)

USAGE:
    $0 [OPTIONS]

OPTIONS:
    --type TYPE             Backup type: app-only, redis-only, config-only, all (default: all)
    --retention DAYS        Retention period in days (default: 30)
    --compression LEVEL     Compression level 1-9 (default: 6)
    --encrypt               Enable encryption (requires ENCRYPTION_KEY_FILE)
    --upload                Upload to S3 (requires S3_BUCKET)
    --quick                 Quick backup (skip verification)
    --dry-run               Show what would be backed up without doing it
    -h, --help              Show this help message

EXAMPLES:
    $0 --type all --retention 7
    $0 --type app-only --quick
    $0 --type redis-only --upload

ENVIRONMENT VARIABLES:
    BACKUP_BASE_DIR         Base backup directory (default: /var/backups/learning-center)
    RETENTION_DAYS          Backup retention in days (default: 30)
    COMPRESSION_LEVEL       Compression level 1-9 (default: 6)
    ENCRYPTION_KEY_FILE     Path to encryption key file
    S3_BUCKET              S3 bucket for remote backups
    S3_PREFIX              S3 prefix for backups (default: learning-center-backups)

NOTE: PostgreSQL backups are handled separately on database servers.

EOF
}

# Parse command line arguments
parse_args() {
    BACKUP_TYPE="all"
    ENABLE_ENCRYPTION=false
    UPLOAD_TO_S3=false
    QUICK_BACKUP=false
    DRY_RUN=false

    while [[ $# -gt 0 ]]; do
        case $1 in
            --type)
                BACKUP_TYPE="$2"
                shift 2
                ;;
            --retention)
                RETENTION_DAYS="$2"
                shift 2
                ;;
            --compression)
                COMPRESSION_LEVEL="$2"
                shift 2
                ;;
            --encrypt)
                ENABLE_ENCRYPTION=true
                shift
                ;;
            --upload)
                UPLOAD_TO_S3=true
                shift
                ;;
            --quick)
                QUICK_BACKUP=true
                shift
                ;;
            --dry-run)
                DRY_RUN=true
                shift
                ;;
            -h|--help)
                show_help
                exit 0
                ;;
            *)
                log_error "Unknown option: $1"
                show_help
                exit 1
                ;;
        esac
    done
}

# Initialize backup environment
init_backup() {
    log_info "Initializing backup environment..."
    
    # Create backup directories
    mkdir -p "$BACKUP_BASE_DIR"/{data,logs,temp}
    
    # Check available disk space
    local available_space=$(df -BG "$BACKUP_BASE_DIR" | awk 'NR==2{print $4}' | sed 's/G//')
    if [[ $available_space -lt 5 ]]; then
        log_error "Insufficient disk space: ${available_space}GB available, 5GB required"
        exit 1
    fi
    
    # Check required tools
    local required_tools=("docker" "docker-compose" "gzip" "tar")
    for tool in "${required_tools[@]}"; do
        if ! command -v "$tool" &> /dev/null; then
            log_error "Required tool not found: $tool"
            exit 1
        fi
    done
    
    log_success "Backup environment initialized"
}

# Get container ID by service name
get_container_id() {
    local service="$1"
    docker-compose -f "$PROJECT_ROOT/docker-compose.production.yml" ps -q "$service" 2>/dev/null || echo ""
}

# Check service health
check_service_health() {
    local service="$1"
    local container_id=$(get_container_id "$service")
    
    if [[ -z "$container_id" ]]; then
        return 1
    fi
    
    docker inspect "$container_id" --format='{{.State.Health.Status}}' 2>/dev/null | grep -q "healthy" || \
    docker inspect "$container_id" --format='{{.State.Status}}' 2>/dev/null | grep -q "running"
}

# Backup Redis data
backup_redis() {
    local backup_dir="$1"
    local timestamp="$2"
    
    log_info "Starting Redis backup..."
    
    if ! check_service_health "redis"; then
        log_error "Redis service is not healthy, skipping backup"
        return 1
    fi
    
    local redis_container=$(get_container_id "redis")
    local backup_file="$backup_dir/redis-${timestamp}.rdb"
    
    # Create Redis backup
    if docker exec "$redis_container" redis-cli BGSAVE; then
        # Wait for backup to complete
        while docker exec "$redis_container" redis-cli LASTSAVE | grep -q "$(docker exec "$redis_container" redis-cli LASTSAVE)"; do
            sleep 1
        done
        
        # Copy backup file
        docker cp "$redis_container:/data/dump.rdb" "$backup_file"
        
        # Compress backup
        local compressed_file="${backup_file}.gz"
        gzip -"$COMPRESSION_LEVEL" "$backup_file"
        
        local size=$(du -h "$compressed_file" | cut -f1)
        log_success "Redis backup completed: $compressed_file ($size)"
        echo "$compressed_file"
    else
        log_error "Redis backup failed"
        return 1
    fi
}

# Backup application files
backup_application() {
    local backup_dir="$1"
    local timestamp="$2"
    
    log_info "Starting application files backup..."
    
    local app_container=$(get_container_id "app")
    if [[ -z "$app_container" ]]; then
        log_error "Application container not found"
        return 1
    fi
    
    local backup_file="$backup_dir/application-${timestamp}.tar.gz"
    
    # Backup application files (storage, logs, etc.)
    local temp_dir="$BACKUP_BASE_DIR/temp/app-$timestamp"
    mkdir -p "$temp_dir"
    
    # Copy important application directories
    docker cp "$app_container:/var/www/html/storage" "$temp_dir/" 2>/dev/null || true
    docker cp "$app_container:/var/www/html/bootstrap/cache" "$temp_dir/" 2>/dev/null || true
    
    # Create tarball
    if tar -czf "$backup_file" -C "$temp_dir" . 2>/dev/null; then
        rm -rf "$temp_dir"
        local size=$(du -h "$backup_file" | cut -f1)
        log_success "Application backup completed: $backup_file ($size)"
        echo "$backup_file"
    else
        rm -rf "$temp_dir"
        log_error "Application backup failed"
        return 1
    fi
}

# Backup configuration files
backup_configuration() {
    local backup_dir="$1"
    local timestamp="$2"
    
    log_info "Starting configuration backup..."
    
    local backup_file="$backup_dir/configuration-${timestamp}.tar.gz"
    local temp_dir="$BACKUP_BASE_DIR/temp/config-$timestamp"
    mkdir -p "$temp_dir"
    
    # Copy configuration files
    cp -r "$PROJECT_ROOT/docker" "$temp_dir/" 2>/dev/null || true
    cp "$PROJECT_ROOT/.env.production" "$temp_dir/" 2>/dev/null || true
    cp "$PROJECT_ROOT/docker-compose.production.yml" "$temp_dir/" 2>/dev/null || true
    
    # Copy SSL certificates if they exist
    if [[ -d "/etc/letsencrypt" ]]; then
        sudo cp -r /etc/letsencrypt "$temp_dir/" 2>/dev/null || true
    fi
    
    # Create tarball
    if tar -czf "$backup_file" -C "$temp_dir" . 2>/dev/null; then
        rm -rf "$temp_dir"
        local size=$(du -h "$backup_file" | cut -f1)
        log_success "Configuration backup completed: $backup_file ($size)"
        echo "$backup_file"
    else
        rm -rf "$temp_dir"
        log_error "Configuration backup failed"
        return 1
    fi
}

# Encrypt backup file
encrypt_backup() {
    local file="$1"
    
    if [[ "$ENABLE_ENCRYPTION" == "true" && -n "$ENCRYPTION_KEY_FILE" && -f "$ENCRYPTION_KEY_FILE" ]]; then
        log_info "Encrypting backup: $(basename "$file")"
        
        if openssl enc -aes-256-cbc -salt -in "$file" -out "${file}.enc" -pass file:"$ENCRYPTION_KEY_FILE"; then
            rm "$file"
            log_success "Backup encrypted: ${file}.enc"
            echo "${file}.enc"
        else
            log_error "Encryption failed for: $file"
            echo "$file"
        fi
    else
        echo "$file"
    fi
}

# Verify backup integrity
verify_backup() {
    local file="$1"
    local type="$2"
    
    if [[ "$QUICK_BACKUP" == "true" ]]; then
        return 0
    fi
    
    log_info "Verifying backup: $(basename "$file")"
    
    case "$type" in
        "redis")
            # Verify Redis backup by checking file format
            if file "$file" | grep -q "gzip compressed"; then
                log_success "Redis backup verification passed"
                return 0
            fi
            ;;
        "application"|"configuration")
            # Verify tar.gz files
            if tar -tzf "$file" >/dev/null 2>&1; then
                log_success "$(echo "$type" | tr '[:lower:]' '[:upper:]') backup verification passed"
                return 0
            fi
            ;;
    esac
    
    log_error "Backup verification failed: $file"
    return 1
}

# Upload backup to S3
upload_to_s3() {
    local file="$1"
    
    if [[ "$UPLOAD_TO_S3" == "true" && -n "$S3_BUCKET" ]]; then
        log_info "Uploading to S3: $(basename "$file")"
        
        local s3_key="$S3_PREFIX/$(date +%Y/%m/%d)/$(basename "$file")"
        
        if aws s3 cp "$file" "s3://$S3_BUCKET/$s3_key"; then
            log_success "Upload completed: s3://$S3_BUCKET/$s3_key"
        else
            log_error "S3 upload failed: $file"
        fi
    fi
}

# Clean old backups
cleanup_old_backups() {
    log_info "Cleaning up backups older than $RETENTION_DAYS days..."
    
    local deleted_count=0
    
    # Clean local backups
    while IFS= read -r -d '' file; do
        rm "$file"
        ((deleted_count++))
    done < <(find "$BACKUP_BASE_DIR/data" -name "*.gz" -o -name "*.enc" -type f -mtime +$RETENTION_DAYS -print0 2>/dev/null)
    
    # Clean S3 backups if configured
    if [[ "$UPLOAD_TO_S3" == "true" && -n "$S3_BUCKET" ]]; then
        local cutoff_date=$(date -d "$RETENTION_DAYS days ago" +%Y-%m-%d)
        aws s3 ls "s3://$S3_BUCKET/$S3_PREFIX/" --recursive | \
        awk -v cutoff="$cutoff_date" '$1 < cutoff {print $4}' | \
        while read -r key; do
            aws s3 rm "s3://$S3_BUCKET/$key"
            ((deleted_count++))
        done
    fi
    
    log_success "Cleaned up $deleted_count old backup files"
}

# Send alert notification
send_alert() {
    local alert_type="$1"
    local message="$2"
    
    # Add notification logic here (email, Slack, etc.)
    log_info "Alert: $alert_type - $message"
}

# Generate backup report
generate_report() {
    local backup_files=("$@")
    local report_file="$BACKUP_BASE_DIR/logs/backup-report-$(date +%Y%m%d_%H%M%S).json"
    
    cat > "$report_file" << EOF
{
  "backup_session": {
    "timestamp": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
    "type": "$BACKUP_TYPE",
    "status": "completed",
    "duration": "$((SECONDS))s"
  },
  "files": [
$(printf '    "%s"' "${backup_files[@]}" | sed 's/$/,/' | sed '$s/,$//')
  ],
  "configuration": {
    "retention_days": $RETENTION_DAYS,
    "compression_level": $COMPRESSION_LEVEL,
    "encryption_enabled": $ENABLE_ENCRYPTION,
    "s3_upload_enabled": $UPLOAD_TO_S3
  },
  "environment": {
    "postgresql": "external",
    "redis": "containerized",
    "application": "containerized"
  }
}
EOF
    
    log_success "Backup report generated: $report_file"
}

# Main backup function
perform_backup() {
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_dir="$BACKUP_BASE_DIR/data/$timestamp"
    local backup_files=()
    local failed_backups=()
    
    if [[ "$DRY_RUN" == "true" ]]; then
        log_info "DRY RUN: Would create backup in $backup_dir"
        log_info "DRY RUN: Backup type: $BACKUP_TYPE"
        return 0
    fi
    
    mkdir -p "$backup_dir"
    
    log_info "Starting backup session: $timestamp"
    log_info "Backup type: $BACKUP_TYPE"
    
    # Perform backups based on type
    case "$BACKUP_TYPE" in
        "all"|"redis-only")
            if file=$(backup_redis "$backup_dir" "$timestamp"); then
                file=$(encrypt_backup "$file")
                verify_backup "$file" "redis"
                upload_to_s3 "$file"
                backup_files+=("$file")
            else
                failed_backups+=("redis")
            fi
            ;&
        "all"|"app-only")
            if [[ "$BACKUP_TYPE" == "redis-only" ]]; then
                :  # Skip application backup for redis-only
            elif file=$(backup_application "$backup_dir" "$timestamp"); then
                file=$(encrypt_backup "$file")
                verify_backup "$file" "application"
                upload_to_s3 "$file"
                backup_files+=("$file")
            else
                failed_backups+=("application")
            fi
            ;&
        "all"|"config-only")
            if [[ "$BACKUP_TYPE" == "redis-only" ]]; then
                :  # Skip config backup for redis-only
            elif file=$(backup_configuration "$backup_dir" "$timestamp"); then
                file=$(encrypt_backup "$file")
                verify_backup "$file" "configuration"
                upload_to_s3 "$file"
                backup_files+=("$file")
            else
                failed_backups+=("configuration")
            fi
            ;;
    esac
    
    # Report results
    if [[ ${#failed_backups[@]} -eq 0 ]]; then
        log_success "All backups completed successfully"
        generate_report "${backup_files[@]}"
    else
        log_error "Some backups failed: ${failed_backups[*]}"
        send_alert "PARTIAL_BACKUP_FAILURE" "Failed backups: ${failed_backups[*]}"
        exit 1
    fi
}

# Main function
main() {
    log_info "Learning Center Backup System (Optimized for External PostgreSQL)"
    log_info "=================================================================="
    
    parse_args "$@"
    init_backup
    perform_backup
    cleanup_old_backups
    
    log_success "Backup session completed successfully"
}

# Run main function
main "$@"