#!/bin/bash

# Learning Center Production Deployment Script (GHCR-based)
# Optimized for external PostgreSQL and pre-built images

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
COMPOSE_FILE="$PROJECT_ROOT/docker-compose.production.yml"
ENV_FILE="$PROJECT_ROOT/.env.production"

# Default values
DEFAULT_IMAGE_TAG="latest"
DEFAULT_GITHUB_REPOSITORY=""
BACKUP_BEFORE_DEPLOY=true
HEALTH_CHECK_TIMEOUT=300
ROLLBACK_ON_FAILURE=true

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Help function
show_help() {
    cat << EOF
Learning Center Production Deployment Script

USAGE:
    $0 [OPTIONS]

OPTIONS:
    -t, --tag TAG           Image tag to deploy (default: latest)
    -r, --repo REPO         GitHub repository (owner/repo format)
    -e, --env-file FILE     Environment file path (default: .env.production)
    --no-backup             Skip backup before deployment
    --no-rollback           Don't rollback on failure
    --timeout SECONDS       Health check timeout (default: 300)
    -h, --help              Show this help message

EXAMPLES:
    $0 --tag v1.2.3 --repo myorg/learning-center
    $0 --tag latest --no-backup
    $0 --env-file /path/to/.env.prod

ENVIRONMENT VARIABLES:
    GITHUB_REPOSITORY       GitHub repository (owner/repo)
    IMAGE_TAG              Image tag to deploy
    GHCR_TOKEN             GitHub Container Registry token
    DB_HOST                External PostgreSQL host
    DB_DATABASE            Database name
    DB_USERNAME            Database username
    DB_PASSWORD            Database password
    REDIS_PASSWORD         Redis password
    APP_KEY                Laravel application key

EOF
}

# Parse command line arguments
parse_args() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            -t|--tag)
                IMAGE_TAG="$2"
                shift 2
                ;;
            -r|--repo)
                GITHUB_REPOSITORY="$2"
                shift 2
                ;;
            -e|--env-file)
                ENV_FILE="$2"
                shift 2
                ;;
            --no-backup)
                BACKUP_BEFORE_DEPLOY=false
                shift
                ;;
            --no-rollback)
                ROLLBACK_ON_FAILURE=false
                shift
                ;;
            --timeout)
                HEALTH_CHECK_TIMEOUT="$2"
                shift 2
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

# Validate prerequisites
validate_prerequisites() {
    log_info "Validating prerequisites..."
    
    # Check required commands
    local required_commands=("docker" "docker-compose" "curl" "jq")
    for cmd in "${required_commands[@]}"; do
        if ! command -v "$cmd" &> /dev/null; then
            log_error "Required command not found: $cmd"
            exit 1
        fi
    done
    
    # Check environment file
    if [[ ! -f "$ENV_FILE" ]]; then
        log_error "Environment file not found: $ENV_FILE"
        exit 1
    fi
    
    # Load environment variables
    set -a
    source "$ENV_FILE"
    set +a
    
    # Set defaults from environment or arguments
    IMAGE_TAG="${IMAGE_TAG:-$DEFAULT_IMAGE_TAG}"
    GITHUB_REPOSITORY="${GITHUB_REPOSITORY:-$DEFAULT_GITHUB_REPOSITORY}"
    
    # Validate required variables
    local required_vars=("GITHUB_REPOSITORY" "DB_HOST" "DB_DATABASE" "DB_USERNAME" "DB_PASSWORD" "REDIS_PASSWORD" "APP_KEY")
    for var in "${required_vars[@]}"; do
        if [[ -z "${!var:-}" ]]; then
            log_error "Required environment variable not set: $var"
            exit 1
        fi
    done
    
    # Check Docker daemon
    if ! docker info &> /dev/null; then
        log_error "Docker daemon is not running"
        exit 1
    fi
    
    log_success "Prerequisites validated"
}

# Authenticate with GHCR
authenticate_ghcr() {
    log_info "Authenticating with GitHub Container Registry..."
    
    if [[ -n "${GHCR_TOKEN:-}" ]]; then
        echo "$GHCR_TOKEN" | docker login ghcr.io -u "$GITHUB_USER" --password-stdin
        log_success "Authenticated with GHCR"
    else
        log_warning "GHCR_TOKEN not set, assuming already authenticated"
    fi
}

# Pull latest images
pull_images() {
    log_info "Pulling latest images from GHCR..."
    
    local image="ghcr.io/$GITHUB_REPOSITORY/learning-center:$IMAGE_TAG"
    
    if ! docker pull "$image"; then
        log_error "Failed to pull image: $image"
        exit 1
    fi
    
    log_success "Images pulled successfully"
}

# Backup current deployment
backup_deployment() {
    if [[ "$BACKUP_BEFORE_DEPLOY" == "true" ]]; then
        log_info "Creating backup before deployment..."
        
        local backup_script="$SCRIPT_DIR/backup-system.sh"
        if [[ -f "$backup_script" ]]; then
            if "$backup_script" --type app-only --quick; then
                log_success "Backup completed"
            else
                log_warning "Backup failed, continuing with deployment"
            fi
        else
            log_warning "Backup script not found, skipping backup"
        fi
    fi
}

# Deploy services
deploy_services() {
    log_info "Deploying services..."
    
    # Export variables for docker-compose
    export IMAGE_TAG
    export GITHUB_REPOSITORY
    
    # Deploy with docker-compose
    if docker-compose -f "$COMPOSE_FILE" up -d --remove-orphans; then
        log_success "Services deployed successfully"
    else
        log_error "Failed to deploy services"
        if [[ "$ROLLBACK_ON_FAILURE" == "true" ]]; then
            rollback_deployment
        fi
        exit 1
    fi
}

# Health check
perform_health_check() {
    log_info "Performing health checks..."
    
    local start_time=$(date +%s)
    local timeout=$HEALTH_CHECK_TIMEOUT
    
    # Wait for services to be ready
    while true; do
        local current_time=$(date +%s)
        local elapsed=$((current_time - start_time))
        
        if [[ $elapsed -gt $timeout ]]; then
            log_error "Health check timeout after ${timeout}s"
            if [[ "$ROLLBACK_ON_FAILURE" == "true" ]]; then
                rollback_deployment
            fi
            exit 1
        fi
        
        # Check application health
        if curl -f -s http://localhost/health > /dev/null 2>&1; then
            log_success "Application is healthy"
            break
        fi
        
        log_info "Waiting for application to be ready... (${elapsed}s/${timeout}s)"
        sleep 10
    done
    
    # Check individual services
    local services=("app" "redis" "nginx" "prometheus")
    for service in "${services[@]}"; do
        if docker-compose -f "$COMPOSE_FILE" ps "$service" | grep -q "Up"; then
            log_success "Service $service is running"
        else
            log_error "Service $service is not running"
            if [[ "$ROLLBACK_ON_FAILURE" == "true" ]]; then
                rollback_deployment
            fi
            exit 1
        fi
    done
}

# Rollback deployment
rollback_deployment() {
    log_warning "Rolling back deployment..."
    
    # Stop current services
    docker-compose -f "$COMPOSE_FILE" down
    
    # Restore from backup if available
    local restore_script="$SCRIPT_DIR/disaster-recovery.sh"
    if [[ -f "$restore_script" ]]; then
        "$restore_script" --restore-type minimal --auto-confirm
    fi
    
    log_warning "Rollback completed"
}

# Cleanup old images
cleanup_images() {
    log_info "Cleaning up old images..."
    
    # Remove dangling images
    docker image prune -f
    
    # Remove old versions (keep last 3)
    local repo="ghcr.io/$GITHUB_REPOSITORY/learning-center"
    local old_images=$(docker images "$repo" --format "table {{.Repository}}:{{.Tag}}" | tail -n +2 | tail -n +4)
    
    if [[ -n "$old_images" ]]; then
        echo "$old_images" | xargs -r docker rmi
        log_success "Old images cleaned up"
    else
        log_info "No old images to clean up"
    fi
}

# Post-deployment tasks
post_deployment() {
    log_info "Running post-deployment tasks..."
    
    # Run Laravel migrations (if needed)
    docker-compose -f "$COMPOSE_FILE" exec -T app php artisan migrate --force
    
    # Clear caches
    docker-compose -f "$COMPOSE_FILE" exec -T app php artisan config:cache
    docker-compose -f "$COMPOSE_FILE" exec -T app php artisan route:cache
    docker-compose -f "$COMPOSE_FILE" exec -T app php artisan view:cache
    
    # Restart queue workers
    docker-compose -f "$COMPOSE_FILE" restart app
    
    log_success "Post-deployment tasks completed"
}

# Generate deployment report
generate_report() {
    log_info "Generating deployment report..."
    
    local report_file="/tmp/deployment-report-$(date +%Y%m%d_%H%M%S).json"
    
    cat > "$report_file" << EOF
{
  "deployment": {
    "timestamp": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
    "image_tag": "$IMAGE_TAG",
    "repository": "$GITHUB_REPOSITORY",
    "environment": "production",
    "status": "success"
  },
  "services": {
    "app": "$(docker-compose -f "$COMPOSE_FILE" ps app --format json | jq -r '.State')",
    "redis": "$(docker-compose -f "$COMPOSE_FILE" ps redis --format json | jq -r '.State')",
    "nginx": "$(docker-compose -f "$COMPOSE_FILE" ps nginx --format json | jq -r '.State')",
    "prometheus": "$(docker-compose -f "$COMPOSE_FILE" ps prometheus --format json | jq -r '.State')"
  },
  "health_checks": {
    "application": "healthy",
    "database_connection": "external",
    "redis_connection": "healthy"
  }
}
EOF
    
    log_success "Deployment report generated: $report_file"
}

# Main deployment function
main() {
    log_info "Starting Learning Center production deployment..."
    
    parse_args "$@"
    validate_prerequisites
    authenticate_ghcr
    pull_images
    backup_deployment
    deploy_services
    perform_health_check
    cleanup_images
    post_deployment
    generate_report
    
    log_success "Deployment completed successfully!"
    log_info "Application is available at: ${APP_URL:-http://localhost}"
    log_info "Monitoring is available at: http://localhost:9090"
}

# Run main function
main "$@"