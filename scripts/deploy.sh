#!/bin/bash

# Production Deployment Script with Zero-Downtime
# This script handles blue-green deployments for the Laravel application

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
DEPLOY_USER="${DEPLOY_USER:-deploy}"
DEPLOY_HOST="${DEPLOY_HOST:-}"
DEPLOY_PATH="${DEPLOY_PATH:-/var/www/laravel}"
DOCKER_COMPOSE_FILE="${DOCKER_COMPOSE_FILE:-docker-compose.production.yml}"
HEALTH_CHECK_URL="${HEALTH_CHECK_URL:-http://localhost/health}"
HEALTH_CHECK_TIMEOUT="${HEALTH_CHECK_TIMEOUT:-300}"
BACKUP_RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-7}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
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

# Check if running as root
check_user() {
    if [[ $EUID -eq 0 ]]; then
        error "This script should not be run as root"
        exit 1
    fi
}

# Check required environment variables
check_environment() {
    log "Checking environment variables..."
    
    if [[ -z "$DEPLOY_HOST" ]]; then
        error "DEPLOY_HOST environment variable is required"
        exit 1
    fi
    
    if [[ ! -f "$PROJECT_ROOT/.env.production" ]]; then
        error "Production environment file not found: $PROJECT_ROOT/.env.production"
        exit 1
    fi
    
    success "Environment check passed"
}

# Check if Docker and Docker Compose are available
check_dependencies() {
    log "Checking dependencies..."
    
    if ! command -v docker &> /dev/null; then
        error "Docker is not installed or not in PATH"
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        error "Docker Compose is not installed or not in PATH"
        exit 1
    fi
    
    success "Dependencies check passed"
}

# Create backup of current deployment
create_backup() {
    log "Creating backup of current deployment..."
    
    local backup_dir="$PROJECT_ROOT/backups/$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$backup_dir"
    
    # Backup database
    if docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T postgres pg_dump -U laravel laravel_production > "$backup_dir/database.sql"; then
        success "Database backup created"
    else
        error "Failed to create database backup"
        return 1
    fi
    
    # Backup storage files
    if [[ -d "$PROJECT_ROOT/storage" ]]; then
        cp -r "$PROJECT_ROOT/storage" "$backup_dir/"
        success "Storage backup created"
    fi
    
    # Backup current Docker images
    docker-compose -f "$DOCKER_COMPOSE_FILE" config --services | while read -r service; do
        local image=$(docker-compose -f "$DOCKER_COMPOSE_FILE" config | grep -A 5 "^  $service:" | grep "image:" | awk '{print $2}')
        if [[ -n "$image" ]]; then
            docker save "$image" | gzip > "$backup_dir/${service}_image.tar.gz"
        fi
    done
    
    echo "$backup_dir" > "$PROJECT_ROOT/.last_backup"
    success "Backup created at $backup_dir"
}

# Clean old backups
cleanup_backups() {
    log "Cleaning up old backups..."
    
    find "$PROJECT_ROOT/backups" -type d -mtime +$BACKUP_RETENTION_DAYS -exec rm -rf {} + 2>/dev/null || true
    
    success "Old backups cleaned up"
}

# Build new Docker images
build_images() {
    log "Building new Docker images..."
    
    # Build with build args for cache busting
    docker-compose -f "$DOCKER_COMPOSE_FILE" build \
        --build-arg BUILD_DATE="$(date -u +'%Y-%m-%dT%H:%M:%SZ')" \
        --build-arg VCS_REF="$(git rev-parse HEAD)" \
        --no-cache app
    
    success "Docker images built successfully"
}

# Health check function
health_check() {
    local url="$1"
    local timeout="$2"
    local start_time=$(date +%s)
    
    log "Performing health check on $url (timeout: ${timeout}s)..."
    
    while true; do
        local current_time=$(date +%s)
        local elapsed=$((current_time - start_time))
        
        if [[ $elapsed -gt $timeout ]]; then
            error "Health check timed out after ${timeout}s"
            return 1
        fi
        
        if curl -f -s -o /dev/null "$url"; then
            success "Health check passed"
            return 0
        fi
        
        log "Health check failed, retrying in 5 seconds... (${elapsed}s elapsed)"
        sleep 5
    done
}

# Deploy new version
deploy_new_version() {
    log "Deploying new version..."
    
    # Stop and remove old containers
    docker-compose -f "$DOCKER_COMPOSE_FILE" down --remove-orphans
    
    # Start new containers
    docker-compose -f "$DOCKER_COMPOSE_FILE" up -d
    
    # Wait for containers to be ready
    log "Waiting for containers to start..."
    sleep 30
    
    # Run database migrations
    log "Running database migrations..."
    docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T app php artisan migrate --force
    
    # Clear and cache config
    log "Optimizing application..."
    docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T app php artisan config:cache
    docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T app php artisan route:cache
    docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T app php artisan view:cache
    docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T app php artisan event:cache
    
    # Restart Horizon
    docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T horizon php artisan horizon:terminate
    
    success "New version deployed"
}

# Rollback to previous version
rollback() {
    error "Deployment failed, initiating rollback..."
    
    if [[ ! -f "$PROJECT_ROOT/.last_backup" ]]; then
        error "No backup found for rollback"
        exit 1
    fi
    
    local backup_dir=$(cat "$PROJECT_ROOT/.last_backup")
    
    if [[ ! -d "$backup_dir" ]]; then
        error "Backup directory not found: $backup_dir"
        exit 1
    fi
    
    log "Rolling back to backup: $backup_dir"
    
    # Stop current containers
    docker-compose -f "$DOCKER_COMPOSE_FILE" down --remove-orphans
    
    # Restore Docker images
    for image_file in "$backup_dir"/*_image.tar.gz; do
        if [[ -f "$image_file" ]]; then
            log "Restoring image: $(basename "$image_file")"
            docker load < "$image_file"
        fi
    done
    
    # Restore database
    if [[ -f "$backup_dir/database.sql" ]]; then
        log "Restoring database..."
        docker-compose -f "$DOCKER_COMPOSE_FILE" up -d postgres
        sleep 10
        docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T postgres psql -U laravel -d laravel_production < "$backup_dir/database.sql"
    fi
    
    # Restore storage
    if [[ -d "$backup_dir/storage" ]]; then
        log "Restoring storage..."
        rm -rf "$PROJECT_ROOT/storage"
        cp -r "$backup_dir/storage" "$PROJECT_ROOT/"
    fi
    
    # Start containers
    docker-compose -f "$DOCKER_COMPOSE_FILE" up -d
    
    success "Rollback completed"
}

# Main deployment function
deploy() {
    log "Starting deployment process..."
    
    # Pre-deployment checks
    check_user
    check_environment
    check_dependencies
    
    # Create backup
    create_backup
    
    # Build new images
    build_images
    
    # Deploy new version
    if ! deploy_new_version; then
        rollback
        exit 1
    fi
    
    # Health check
    if ! health_check "$HEALTH_CHECK_URL" "$HEALTH_CHECK_TIMEOUT"; then
        rollback
        exit 1
    fi
    
    # Cleanup
    cleanup_backups
    
    success "Deployment completed successfully!"
}

# Script usage
usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  deploy          Deploy the application"
    echo "  rollback        Rollback to the last backup"
    echo "  health-check    Perform health check"
    echo "  backup          Create backup only"
    echo "  cleanup         Cleanup old backups"
    echo "  -h, --help      Show this help message"
    echo ""
    echo "Environment Variables:"
    echo "  DEPLOY_HOST              Target deployment host"
    echo "  DEPLOY_PATH              Deployment path (default: /var/www/laravel)"
    echo "  HEALTH_CHECK_URL         Health check URL (default: http://localhost/health)"
    echo "  HEALTH_CHECK_TIMEOUT     Health check timeout in seconds (default: 300)"
    echo "  BACKUP_RETENTION_DAYS    Backup retention in days (default: 7)"
}

# Main script logic
case "${1:-}" in
    deploy)
        deploy
        ;;
    rollback)
        rollback
        ;;
    health-check)
        health_check "$HEALTH_CHECK_URL" "$HEALTH_CHECK_TIMEOUT"
        ;;
    backup)
        create_backup
        ;;
    cleanup)
        cleanup_backups
        ;;
    -h|--help)
        usage
        ;;
    *)
        usage
        exit 1
        ;;
esac