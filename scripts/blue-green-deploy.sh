#!/bin/bash

# Blue-Green Deployment Script for Learning Center SaaS
# Optimized for 4vCPU/4GB RAM Ubuntu 24.04 VPS
# Zero-downtime deployment with health checks and rollback

set -euo pipefail

# ===========================================
# CONFIGURATION
# ===========================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
COMPOSE_FILE="$PROJECT_ROOT/docker-compose.blue-green.yml"
NGINX_CONFIG="$PROJECT_ROOT/infra/nginx/nginx.conf"
ENV_FILE="$PROJECT_ROOT/.env"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Deployment settings
HEALTH_CHECK_TIMEOUT=120
HEALTH_CHECK_INTERVAL=5
GRACEFUL_SHUTDOWN_TIMEOUT=30

# ===========================================
# LOGGING FUNCTIONS
# ===========================================

log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] ✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] ⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ❌ $1${NC}"
}

# ===========================================
# UTILITY FUNCTIONS
# ===========================================

check_dependencies() {
    log "Checking dependencies..."
    
    if ! command -v docker &> /dev/null; then
        log_error "Docker is not installed"
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose is not installed"
        exit 1
    fi
    
    if [[ ! -f "$ENV_FILE" ]]; then
        log_error ".env file not found at $ENV_FILE"
        exit 1
    fi
    
    log_success "All dependencies are available"
}

get_current_environment() {
    # Check which environment is currently active
    if docker-compose -f "$COMPOSE_FILE" --profile blue ps -q app-blue 2>/dev/null | grep -q .; then
        if [[ $(docker-compose -f "$COMPOSE_FILE" --profile blue ps app-blue --format "table {{.State}}" | tail -n +2 | grep -c "Up") -gt 0 ]]; then
            echo "blue"
            return
        fi
    fi
    
    if docker-compose -f "$COMPOSE_FILE" --profile green ps -q app-green 2>/dev/null | grep -q .; then
        if [[ $(docker-compose -f "$COMPOSE_FILE" --profile green ps app-green --format "table {{.State}}" | tail -n +2 | grep -c "Up") -gt 0 ]]; then
            echo "green"
            return
        fi
    fi
    
    echo "none"
}

get_target_environment() {
    local current="$1"
    case "$current" in
        "blue") echo "green" ;;
        "green") echo "blue" ;;
        "none") echo "blue" ;;
        *) echo "blue" ;;
    esac
}

wait_for_health() {
    local environment="$1"
    local container_name="learning_app_$environment"
    local elapsed=0
    
    log "Waiting for $environment environment to be healthy..."
    
    while [[ $elapsed -lt $HEALTH_CHECK_TIMEOUT ]]; do
        if docker exec "$container_name" curl -f -s http://localhost:8000/health > /dev/null 2>&1; then
            log_success "$environment environment is healthy"
            return 0
        fi
        
        sleep $HEALTH_CHECK_INTERVAL
        elapsed=$((elapsed + HEALTH_CHECK_INTERVAL))
        echo -n "."
    done
    
    echo ""
    log_error "$environment environment failed health check after ${HEALTH_CHECK_TIMEOUT}s"
    return 1
}

update_nginx_upstream() {
    local target_environment="$1"
    local backup_config="$NGINX_CONFIG.backup.$(date +%s)"
    
    log "Updating Nginx upstream to $target_environment environment..."
    
    # Backup current config
    cp "$NGINX_CONFIG" "$backup_config"
    
    # Update upstream configuration
    if [[ "$target_environment" == "blue" ]]; then
        sed -i.tmp 's/server learning_app_green:8000/server learning_app_blue:8000/' "$NGINX_CONFIG"
        sed -i.tmp 's/# server learning_app_blue:8000 backup;/server learning_app_green:8000 backup;/' "$NGINX_CONFIG"
        sed -i.tmp 's/server learning_app_blue:8000 backup;/# server learning_app_blue:8000 backup;/' "$NGINX_CONFIG"
    else
        sed -i.tmp 's/server learning_app_blue:8000/server learning_app_green:8000/' "$NGINX_CONFIG"
        sed -i.tmp 's/# server learning_app_green:8000 backup;/server learning_app_blue:8000 backup;/' "$NGINX_CONFIG"
        sed -i.tmp 's/server learning_app_green:8000 backup;/# server learning_app_green:8000 backup;/' "$NGINX_CONFIG"
    fi
    
    rm -f "$NGINX_CONFIG.tmp"
    
    # Reload Nginx configuration
    if docker exec learning_nginx_lb nginx -t; then
        docker exec learning_nginx_lb nginx -s reload
        log_success "Nginx configuration updated and reloaded"
    else
        log_error "Nginx configuration test failed, restoring backup"
        cp "$backup_config" "$NGINX_CONFIG"
        return 1
    fi
}

run_migrations() {
    local environment="$1"
    local container_name="learning_app_$environment"
    
    log "Running database migrations in $environment environment..."
    
    if docker exec "$container_name" php artisan migrate --force; then
        log_success "Database migrations completed"
    else
        log_error "Database migrations failed"
        return 1
    fi
}

clear_caches() {
    local environment="$1"
    local container_name="learning_app_$environment"
    
    log "Clearing caches in $environment environment..."
    
    docker exec "$container_name" php artisan config:cache
    docker exec "$container_name" php artisan route:cache
    docker exec "$container_name" php artisan view:cache
    docker exec "$container_name" php artisan event:cache
    
    log_success "Caches cleared and warmed up"
}

graceful_shutdown() {
    local environment="$1"
    
    if [[ "$environment" == "none" ]]; then
        return 0
    fi
    
    log "Gracefully shutting down $environment environment..."
    
    # Stop accepting new requests (handled by Nginx upstream change)
    # Wait for existing requests to complete
    sleep $GRACEFUL_SHUTDOWN_TIMEOUT
    
    # Stop the environment
    docker-compose -f "$COMPOSE_FILE" --profile "$environment" stop
    docker-compose -f "$COMPOSE_FILE" --profile "$environment" rm -f
    
    log_success "$environment environment shut down gracefully"
}

# ===========================================
# DEPLOYMENT FUNCTIONS
# ===========================================

deploy() {
    local git_ref="${1:-HEAD}"
    
    log "Starting blue-green deployment..."
    log "Git reference: $git_ref"
    
    # Check dependencies
    check_dependencies
    
    # Determine current and target environments
    local current_env
    current_env=$(get_current_environment)
    local target_env
    target_env=$(get_target_environment "$current_env")
    
    log "Current environment: $current_env"
    log "Target environment: $target_env"
    
    # Ensure shared services are running
    log "Starting shared services..."
    docker-compose -f "$COMPOSE_FILE" up -d postgres redis nginx-lb
    
    # Wait for shared services to be healthy
    log "Waiting for shared services to be ready..."
    sleep 10
    
    # Build and start target environment
    log "Building and starting $target_env environment..."
    docker-compose -f "$COMPOSE_FILE" --profile "$target_env" build --no-cache
    docker-compose -f "$COMPOSE_FILE" --profile "$target_env" up -d
    
    # Wait for target environment to be healthy
    if ! wait_for_health "$target_env"; then
        log_error "Deployment failed: $target_env environment is not healthy"
        log "Cleaning up failed deployment..."
        docker-compose -f "$COMPOSE_FILE" --profile "$target_env" down
        exit 1
    fi
    
    # Run database migrations
    if ! run_migrations "$target_env"; then
        log_error "Deployment failed: database migrations failed"
        log "Cleaning up failed deployment..."
        docker-compose -f "$COMPOSE_FILE" --profile "$target_env" down
        exit 1
    fi
    
    # Clear and warm up caches
    clear_caches "$target_env"
    
    # Update load balancer to point to new environment
    if ! update_nginx_upstream "$target_env"; then
        log_error "Deployment failed: could not update load balancer"
        log "Cleaning up failed deployment..."
        docker-compose -f "$COMPOSE_FILE" --profile "$target_env" down
        exit 1
    fi
    
    # Wait a bit to ensure traffic is flowing to new environment
    log "Verifying traffic routing..."
    sleep 10
    
    # Final health check through load balancer
    if ! curl -f -s http://localhost/health > /dev/null; then
        log_error "Deployment failed: health check through load balancer failed"
        rollback "$current_env" "$target_env"
        exit 1
    fi
    
    # Gracefully shutdown old environment
    graceful_shutdown "$current_env"
    
    log_success "Deployment completed successfully!"
    log_success "Active environment: $target_env"
}

rollback() {
    local previous_env="$1"
    local failed_env="$2"
    
    log_warning "Rolling back deployment..."
    
    if [[ "$previous_env" != "none" ]]; then
        # Restore load balancer to previous environment
        update_nginx_upstream "$previous_env"
        log_success "Load balancer restored to $previous_env environment"
    fi
    
    # Clean up failed environment
    docker-compose -f "$COMPOSE_FILE" --profile "$failed_env" down
    log_success "Failed $failed_env environment cleaned up"
    
    log_success "Rollback completed"
}

status() {
    log "Deployment Status:"
    echo ""
    
    local current_env
    current_env=$(get_current_environment)
    
    echo "Current active environment: $current_env"
    echo ""
    
    echo "Container Status:"
    docker-compose -f "$COMPOSE_FILE" ps
    echo ""
    
    echo "Resource Usage:"
    docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.MemPerc}}"
}

# ===========================================
# MAIN SCRIPT
# ===========================================

case "${1:-}" in
    "deploy")
        deploy "${2:-HEAD}"
        ;;
    "status")
        status
        ;;
    "rollback")
        current_env=$(get_current_environment)
        target_env=$(get_target_environment "$current_env")
        rollback "$target_env" "$current_env"
        ;;
    *)
        echo "Usage: $0 {deploy|status|rollback} [git-ref]"
        echo ""
        echo "Commands:"
        echo "  deploy [git-ref]  - Deploy application (default: HEAD)"
        echo "  status           - Show deployment status"
        echo "  rollback         - Rollback to previous environment"
        exit 1
        ;;
esac