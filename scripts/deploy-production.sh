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

# GitHub Container Registry Configuration
GHCR_REGISTRY="ghcr.io"
GHCR_NAMESPACE="${GITHUB_REPOSITORY_OWNER:-$(whoami)}"
GHCR_IMAGE="${GHCR_REGISTRY}/${GHCR_NAMESPACE}/${APP_NAME}"

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
    if ! docker compose version >/dev/null 2>&1; then
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

load_environment() {
    log_info "Loading environment variables..."
    
    # Load .env.production if it exists
    if [[ -f "$ENV_FILE" ]]; then
        log_info "Loading environment from $ENV_FILE"
        set -a  # automatically export all variables
        source "$ENV_FILE"
        set +a  # stop automatically exporting
        log_success "Environment variables loaded from $ENV_FILE"
    else
        log_warning "Environment file $ENV_FILE not found"
    fi
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

# Function to test network connectivity to PostgreSQL cluster
test_network_connectivity() {
    echo "🔍 Testing network connectivity to PostgreSQL cluster..."
    
    # Test basic network connectivity
    if command -v nc >/dev/null 2>&1; then
        if nc -z -w5 "$DB_HOST" "${DB_PORT:-5432}" 2>/dev/null; then
            echo "✅ Network connectivity to $DB_HOST:${DB_PORT:-5432} successful"
        else
            echo "❌ Network connectivity to $DB_HOST:${DB_PORT:-5432} failed"
            echo "❌ This indicates a network/firewall issue"
            return 1
        fi
    else
        echo "⚠️  netcat not available, skipping network test"
    fi
    
    # Test DNS resolution
    if command -v nslookup >/dev/null 2>&1; then
        if nslookup "$DB_HOST" >/dev/null 2>&1; then
            echo "✅ DNS resolution for $DB_HOST successful"
        else
            echo "❌ DNS resolution for $DB_HOST failed"
            return 1
        fi
    fi
    
    return 0
}

# Function to test PostgreSQL cluster connectivity
test_postgresql_cluster() {
    echo "🔍 Testing PostgreSQL cluster connectivity..."
    
    # First test network connectivity
    if ! test_network_connectivity; then
        echo "❌ Network connectivity test failed"
        return 1
    fi
    
    if ! command -v psql >/dev/null 2>&1; then
        echo "❌ psql command not found. Please install PostgreSQL client."
        return 1
    fi
    
    # Test connection with timeout and detailed output
    echo "🔗 Connecting to: postgresql://$DB_USERNAME:***@$DB_HOST:${DB_PORT:-5432}/$DB_DATABASE?sslmode=${DB_SSLMODE:-prefer}"
    
    if timeout 15 PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT version(), current_database(), current_user;" >/dev/null 2>&1; then
        echo "✅ PostgreSQL cluster connection successful"
        return 0
    else
        echo "❌ PostgreSQL cluster connection failed"
        echo "❌ Please verify:"
        echo "❌   - Host: $DB_HOST"
        echo "❌   - Port: ${DB_PORT:-5432}"
        echo "❌   - Database: $DB_DATABASE"
        echo "❌   - Username: $DB_USERNAME"
        echo "❌   - Password is correct"
        echo "❌   - SSL mode: ${DB_SSLMODE:-prefer}"
        echo "❌   - Network connectivity from this host"
        echo "❌   - PostgreSQL cluster is accepting connections"
        return 1
    fi
}

# Function to test PostgreSQL cluster connectivity from container
test_postgresql_cluster_from_container() {
    echo "🔍 Testing PostgreSQL cluster connectivity from container..."
    
    if docker compose -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="
        try {
            \$pdo = DB::connection()->getPdo();
            echo 'Database connected successfully';
            echo 'Server version: ' . \$pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (Exception \$e) {
            echo 'Connection failed: ' . \$e->getMessage();
            throw \$e;
        }
    " 2>&1; then
        echo "✅ PostgreSQL cluster connectivity from container successful"
        return 0
    else
        echo "❌ PostgreSQL cluster connectivity from container failed"
        echo "❌ This indicates Docker networking issues"
        echo "❌ Troubleshooting steps:"
        echo "❌   1. Check if container can resolve DNS: docker compose exec app nslookup $DB_HOST"
        echo "❌   2. Check network connectivity: docker compose exec app nc -zv $DB_HOST ${DB_PORT:-5432}"
        echo "❌   3. Check container network: docker compose exec app ip addr show"
        echo "❌   4. Verify environment variables: docker compose exec app env | grep DB_"
        echo "❌   5. Check PostgreSQL logs on the cluster"
        return 1
    fi
}

# Function to run comprehensive troubleshooting
run_troubleshooting() {
    echo "🔧 Running comprehensive troubleshooting..."
    
    # Test host connectivity
    echo "\n📋 Host-level diagnostics:"
    test_postgresql_cluster
    
    # Test container connectivity if deployment exists
    if docker compose -f "$COMPOSE_FILE" ps app | grep -q "Up"; then
        echo "\n📋 Container-level diagnostics:"
        test_postgresql_cluster_from_container
        
        echo "\n📋 Container environment check:"
        docker compose -f "$COMPOSE_FILE" exec -T app env | grep -E "^(DB_|APP_|REDIS_)" | sort
        
        echo "\n📋 Container network diagnostics:"
        docker compose -f "$COMPOSE_FILE" exec -T app ip route show
        docker compose -f "$COMPOSE_FILE" exec -T app cat /etc/resolv.conf
    else
        echo "\n⚠️  Application container not running, skipping container diagnostics"
    fi
    
    echo "\n📋 Docker network information:"
    docker network ls
    
    echo "\n📋 Docker compose configuration:"
    docker compose -f "$COMPOSE_FILE" config --services
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
    
    # Validate PostgreSQL cluster connectivity (unless skipped)
    if [[ "$SKIP_NETWORK_TESTS" == "true" ]]; then
        echo "⚠️  Skipping network connectivity tests (--skip-network-tests flag set)"
    else
        if ! test_postgresql_cluster; then
            echo "❌ PostgreSQL cluster connectivity validation failed"
            exit 1
        fi
    fi
    
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

push_to_ghcr() {
    log_info "Pushing image to GitHub Container Registry..."
    
    # Check if GITHUB_TOKEN is set
    if [[ -z "${GITHUB_TOKEN:-}" ]]; then
        log_error "GITHUB_TOKEN is required for GHCR push"
        log_error "Please set GITHUB_TOKEN with packages:write scope"
        exit 1
    fi
    
    # Login to GHCR
    echo "$GITHUB_TOKEN" | docker login "$GHCR_REGISTRY" -u "$GHCR_NAMESPACE" --password-stdin
    
    # Tag image for GHCR
    docker tag "${APP_NAME}:${IMAGE_TAG}" "${GHCR_IMAGE}:${IMAGE_TAG}"
    docker tag "${APP_NAME}:${IMAGE_TAG}" "${GHCR_IMAGE}:latest"
    
    # Push to GHCR
    docker push "${GHCR_IMAGE}:${IMAGE_TAG}"
    docker push "${GHCR_IMAGE}:latest"
    
    log_success "Image pushed to GHCR: ${GHCR_IMAGE}:${IMAGE_TAG}"
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
    docker compose -f "$COMPOSE_FILE" pull redis
    
    # Start the application
    docker compose -f "$COMPOSE_FILE" up -d
    
    log_success "Application deployment started"
}

wait_for_health() {
    log_info "Waiting for application to become healthy..."
    
    max_attempts=30
    attempt=1
    
    while [[ $attempt -le $max_attempts ]]; do
        if docker compose -f "$COMPOSE_FILE" ps app | grep -q "healthy"; then
            log_success "Application is healthy"
            return 0
        fi
        
        log_info "Attempt $attempt/$max_attempts: Waiting for health check..."
        sleep 10
        ((attempt++))
    done
    
    log_error "Application failed to become healthy within expected time"
    log_info "Checking application logs..."
    docker compose -f "$COMPOSE_FILE" logs app
    exit 1
}

run_post_deployment_tests() {
    log_info "Running post-deployment tests..."
    
    # Test PostgreSQL cluster connectivity from container
    if docker compose -f "$COMPOSE_FILE" exec -T app php artisan migrate:status >/dev/null 2>&1; then
        log_success "PostgreSQL cluster connectivity test passed"
    else
        log_error "PostgreSQL cluster connectivity test failed"
        log_error "Check network connectivity between container and cluster"
        return 1
    fi
    
    # Test Redis connectivity
    if docker compose -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="Redis::ping()" | grep -q "PONG"; then
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
    docker compose -f "$COMPOSE_FILE" ps
    echo
    echo "Useful Commands:"
    echo "  - View logs: docker compose -f $COMPOSE_FILE logs -f"
    echo "  - Access shell: docker compose -f $COMPOSE_FILE exec app bash"
    echo "  - Stop application: docker compose -f $COMPOSE_FILE down"
    echo "  - Update application: $0 <new-tag>"
}

# Main deployment flow
main() {
    log_info "Starting production deployment for $APP_NAME:$IMAGE_TAG"
    
    check_prerequisites
    load_environment
    generate_secrets
    validate_environment
    build_image
    
    # Push to GHCR if requested
    if [[ "$PUSH_TO_GHCR" == "true" ]]; then
        push_to_ghcr
    fi
    
    run_security_scan
    backup_existing_deployment
    deploy_application
    wait_for_health
    run_post_deployment_tests
    cleanup_old_images
    show_deployment_info
    
    log_success "Production deployment completed successfully!"
}

# Parse command line arguments
PUSH_TO_GHCR=false
SKIP_NETWORK_TESTS=false

# Check for help first
if [[ "${1:-}" == "--help" || "${1:-}" == "-h" ]]; then
    echo "Usage: $0 [IMAGE_TAG] [options]"
    echo
    echo "Deploy Laravel Learning Center to production"
    echo
    echo "Arguments:"
    echo "  IMAGE_TAG    Docker image tag (default: latest)"
    echo
    echo "Options:"
    echo "  --push-to-ghcr       Push built image to GitHub Container Registry"
    echo "  --skip-network-tests Skip network connectivity tests (for testing)"
    echo "  --troubleshoot       Run comprehensive troubleshooting diagnostics"
    echo "  --help, -h           Show this help message"
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
    echo "  DB_SSLMODE   PostgreSQL SSL mode (default: prefer)"
    echo "  REDIS_PASSWORD Redis password"
    echo
    echo "Optional Environment Variables (for GHCR push):"
    echo "  GITHUB_TOKEN          GitHub Personal Access Token with packages:write scope"
    echo "  GITHUB_REPOSITORY_OWNER  GitHub username/organization (defaults to current user)"
    echo
    echo "Examples:"
    echo "  $0                           # Deploy with default settings"
    echo "  $0 v1.0.0 --push-to-ghcr     # Deploy specific version and push to registry"
    echo "  $0 --troubleshoot            # Run diagnostics for connectivity issues"
    echo "  export APP_URL=https://learningcenter.example.com"
    echo "  export DB_HOST=postgres-cluster.example.com"
    echo "  export DB_DATABASE=learningcenter"
    echo "  export DB_USERNAME=laravel"
    echo "  export DB_SSLMODE=require"
    echo "  $0 v1.0.0 --push-to-ghcr"
    exit 0
fi

# Parse arguments
args=()
for arg in "$@"; do
    case $arg in
        --push-to-ghcr)
            PUSH_TO_GHCR=true
            ;;
        --skip-network-tests)
            SKIP_NETWORK_TESTS=true
            ;;
        --dry-run)
            # Dry run flag - just ignore for now
            ;;
        --troubleshoot)
            load_environment
            run_troubleshooting
            exit 0
            ;;
        --help|-h)
            # Already handled above
            ;;
        --*)
            # Unknown flag - ignore
            ;;
        *)
            args+=("$arg")
            ;;
    esac
done

# Set IMAGE_TAG from first non-option argument
IMAGE_TAG="${args[0]:-latest}"

# Run main deployment
main