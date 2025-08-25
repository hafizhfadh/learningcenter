#!/bin/bash

# Production Deployment Script for Laravel Learning Center
# This script demonstrates secure deployment with proper secret management

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="learningcenter"
DOCKER_REGISTRY="your-registry.com"
IMAGE_TAG="${1:-latest}"
ENV_FILE=".env.production"
COMPOSE_FILE="docker-compose.production.yml"

# Functions
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

check_prerequisites() {
    log_info "Checking prerequisites..."
    
    # Check if Docker is running
    if ! docker info >/dev/null 2>&1; then
        log_error "Docker is not running. Please start Docker and try again."
        exit 1
    fi
    
    # Check if Docker Compose is available
    if ! command -v docker-compose >/dev/null 2>&1; then
        log_error "Docker Compose is not installed. Please install it and try again."
        exit 1
    fi
    
    # Check if required files exist
    if [[ ! -f "$COMPOSE_FILE" ]]; then
        log_error "Docker Compose file '$COMPOSE_FILE' not found."
        exit 1
    fi
    
    if [[ ! -f "Dockerfile.frankenphp.improved" ]]; then
        log_error "Improved Dockerfile not found. Please ensure 'Dockerfile.frankenphp.improved' exists."
        exit 1
    fi
    
    log_success "Prerequisites check passed"
}

generate_secrets() {
    log_info "Generating application secrets..."
    
    # Generate APP_KEY if not set
    if [[ -z "${APP_KEY:-}" ]]; then
        export APP_KEY="base64:$(openssl rand -base64 32)"
        log_success "Generated APP_KEY"
    fi
    
    # Generate database password if not set
    if [[ -z "${DB_PASSWORD:-}" ]]; then
        export DB_PASSWORD="$(openssl rand -base64 32)"
        log_success "Generated DB_PASSWORD"
    fi
    
    # Generate Redis password if not set
    if [[ -z "${REDIS_PASSWORD:-}" ]]; then
        export REDIS_PASSWORD="$(openssl rand -base64 32)"
        log_success "Generated REDIS_PASSWORD"
    fi
    
    log_success "Secrets generation completed"
}

validate_environment() {
    log_info "Validating environment variables..."
    
    # Required environment variables
    required_vars=(
        "APP_NAME"
        "APP_ENV"
        "APP_KEY"
        "APP_URL"
        "DB_HOST"
        "DB_DATABASE"
        "DB_USERNAME"
        "DB_PASSWORD"
        "REDIS_PASSWORD"
    )
    
    missing_vars=()
    
    for var in "${required_vars[@]}"; do
        if [[ -z "${!var:-}" ]]; then
            missing_vars+=("$var")
        fi
    done
    
    if [[ ${#missing_vars[@]} -gt 0 ]]; then
        log_error "Missing required environment variables:"
        printf ' - %s\n' "${missing_vars[@]}"
        log_error "Please set these variables and try again."
        exit 1
    fi
    
    # Validate PostgreSQL cluster connectivity
    log_info "Testing PostgreSQL cluster connectivity..."
    if ! PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT 1;" >/dev/null 2>&1; then
        log_error "Cannot connect to PostgreSQL cluster at $DB_HOST:${DB_PORT:-5432}"
        log_error "Please verify:"
        log_error "  - Database host and port are correct"
        log_error "  - Database credentials are valid"
        log_error "  - Network connectivity to the cluster"
        log_error "  - SSL configuration if required"
        exit 1
    fi
    log_success "PostgreSQL cluster connectivity verified"
    
    log_success "Environment validation passed"
}

build_image() {
    log_info "Building Docker image..."
    
    # Build the improved Docker image
    docker build \
        -f Dockerfile.frankenphp.improved \
        -t "${APP_NAME}:${IMAGE_TAG}" \
        -t "${APP_NAME}:latest" \
        .
    
    log_success "Docker image built successfully"
}

run_security_scan() {
    log_info "Running security scan on Docker image..."
    
    # Check if trivy is available for security scanning
    if command -v trivy >/dev/null 2>&1; then
        trivy image "${APP_NAME}:${IMAGE_TAG}" --severity HIGH,CRITICAL
        log_success "Security scan completed"
    else
        log_warning "Trivy not found. Skipping security scan. Consider installing Trivy for production deployments."
    fi
}

backup_existing_deployment() {
    log_info "Creating backup of existing deployment..."
    
    # Create backup directory with timestamp
    backup_dir="backups/$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$backup_dir"
    
    # Backup external PostgreSQL database
    log_info "Backing up PostgreSQL cluster database..."
    if PGPASSWORD="$DB_PASSWORD" pg_dump \
        -h "$DB_HOST" \
        -p "${DB_PORT:-5432}" \
        -U "$DB_USERNAME" \
        -d "$DB_DATABASE" \
        --no-password \
        > "$backup_dir/database_backup.sql" 2>/dev/null; then
        log_success "Database backup created"
    else
        log_warning "Database backup failed - continuing with deployment"
        log_warning "Ensure pg_dump is installed and cluster is accessible"
    fi
    
    # Backup application storage
    if docker volume ls | grep -q "${APP_NAME}_app_storage"; then
        log_info "Backing up application storage..."
        docker run --rm \
            -v "${APP_NAME}_app_storage:/source:ro" \
            -v "$(pwd)/$backup_dir:/backup" \
            alpine tar czf /backup/storage_backup.tar.gz -C /source .
        log_success "Storage backup created"
    fi
    
    log_success "Backup completed in $backup_dir"
}

deploy_application() {
    log_info "Deploying application..."
    
    # Pull latest images for dependencies (Redis only - PostgreSQL is external)
    docker-compose -f "$COMPOSE_FILE" pull redis
    
    # Start the application
    docker-compose -f "$COMPOSE_FILE" up -d
    
    log_success "Application deployment started"
}

wait_for_health() {
    log_info "Waiting for application to become healthy..."
    
    max_attempts=30
    attempt=1
    
    while [[ $attempt -le $max_attempts ]]; do
        if docker-compose -f "$COMPOSE_FILE" ps app | grep -q "healthy"; then
            log_success "Application is healthy"
            return 0
        fi
        
        log_info "Attempt $attempt/$max_attempts: Waiting for health check..."
        sleep 10
        ((attempt++))
    done
    
    log_error "Application failed to become healthy within expected time"
    log_info "Checking application logs..."
    docker-compose -f "$COMPOSE_FILE" logs app
    exit 1
}

run_post_deployment_tests() {
    log_info "Running post-deployment tests..."
    
    # Test PostgreSQL cluster connectivity from container
    if docker-compose -f "$COMPOSE_FILE" exec -T app php artisan migrate:status >/dev/null 2>&1; then
        log_success "PostgreSQL cluster connectivity test passed"
    else
        log_error "PostgreSQL cluster connectivity test failed"
        log_error "Check network connectivity between container and cluster"
        return 1
    fi
    
    # Test Redis connectivity
    if docker-compose -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="Redis::ping()" | grep -q "PONG"; then
        log_success "Redis connectivity test passed"
    else
        log_error "Redis connectivity test failed"
        return 1
    fi
    
    # Test HTTP endpoint
    if curl -f -s "${APP_URL}/health" >/dev/null; then
        log_success "HTTP endpoint test passed"
    else
        log_error "HTTP endpoint test failed"
        return 1
    fi
    
    log_success "All post-deployment tests passed"
}

cleanup_old_images() {
    log_info "Cleaning up old Docker images..."
    
    # Remove dangling images
    docker image prune -f
    
    # Remove old versions of the application image (keep last 3)
    docker images "${APP_NAME}" --format "table {{.Tag}}\t{{.ID}}" | \
        tail -n +4 | \
        awk '{print $2}' | \
        xargs -r docker rmi
    
    log_success "Cleanup completed"
}

show_deployment_info() {
    log_success "Deployment completed successfully!"
    echo
    echo "Application Information:"
    echo "  - Name: $APP_NAME"
    echo "  - Image Tag: $IMAGE_TAG"
    echo "  - URL: $APP_URL"
    echo
    echo "Container Status:"
    docker-compose -f "$COMPOSE_FILE" ps
    echo
    echo "Useful Commands:"
    echo "  - View logs: docker-compose -f $COMPOSE_FILE logs -f"
    echo "  - Access shell: docker-compose -f $COMPOSE_FILE exec app bash"
    echo "  - Stop application: docker-compose -f $COMPOSE_FILE down"
    echo "  - Update application: $0 <new-tag>"
}

# Main deployment flow
main() {
    log_info "Starting production deployment for $APP_NAME:$IMAGE_TAG"
    
    check_prerequisites
    generate_secrets
    validate_environment
    build_image
    run_security_scan
    backup_existing_deployment
    deploy_application
    wait_for_health
    run_post_deployment_tests
    cleanup_old_images
    show_deployment_info
    
    log_success "Production deployment completed successfully!"
}

# Handle script arguments
case "${1:-}" in
    "--help" | "-h")
        echo "Usage: $0 [IMAGE_TAG]"
        echo
        echo "Deploy Laravel Learning Center to production"
        echo
        echo "Arguments:"
        echo "  IMAGE_TAG    Docker image tag (default: latest)"
        echo
        echo "Environment Variables:"
        echo "  APP_NAME     Application name"
        echo "  APP_ENV      Application environment (production)"
        echo "  APP_KEY      Application encryption key"
        echo "  APP_URL      Application URL"
        echo "  DB_HOST      PostgreSQL cluster host"
        echo "  DB_PORT      PostgreSQL cluster port (default: 5432)"
        echo "  DB_DATABASE  Database name"
        echo "  DB_USERNAME  Database username"
        echo "  DB_PASSWORD  Database password"
        echo "  DB_SSLMODE   PostgreSQL SSL mode (default: require)"
        echo "  REDIS_PASSWORD Redis password"
        echo
        echo "Example:"
        echo "  export APP_URL=https://learningcenter.example.com"
        echo "  export DB_HOST=postgres-cluster.example.com"
        echo "  export DB_DATABASE=learningcenter"
        echo "  export DB_USERNAME=laravel"
        echo "  export DB_SSLMODE=require"
        echo "  $0 v1.0.0"
        exit 0
        ;;
    *)
        main
        ;;
esac