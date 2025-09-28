#!/bin/bash

# Blue-Green Deployment Script
# This script implements true zero-downtime deployments using blue-green strategy

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
BLUE_COMPOSE_FILE="docker-compose.blue.yml"
GREEN_COMPOSE_FILE="docker-compose.green.yml"
NGINX_CONFIG_DIR="$PROJECT_ROOT/docker/production/nginx"
HEALTH_CHECK_URL_BLUE="http://localhost:8001/health"
HEALTH_CHECK_URL_GREEN="http://localhost:8002/health"
HEALTH_CHECK_TIMEOUT="${HEALTH_CHECK_TIMEOUT:-120}"
TRAFFIC_SWITCH_DELAY="${TRAFFIC_SWITCH_DELAY:-10}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Current active environment
ACTIVE_ENV=""
INACTIVE_ENV=""

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

# Determine current active environment
detect_active_environment() {
    log "Detecting current active environment..."
    
    if [[ -f "$PROJECT_ROOT/.active_env" ]]; then
        ACTIVE_ENV=$(cat "$PROJECT_ROOT/.active_env")
    else
        # Default to blue if no active environment is set
        ACTIVE_ENV="blue"
        echo "$ACTIVE_ENV" > "$PROJECT_ROOT/.active_env"
    fi
    
    if [[ "$ACTIVE_ENV" == "blue" ]]; then
        INACTIVE_ENV="green"
    else
        INACTIVE_ENV="blue"
    fi
    
    log "Active environment: $ACTIVE_ENV"
    log "Inactive environment: $INACTIVE_ENV"
}

# Generate Docker Compose files for blue-green deployment
generate_compose_files() {
    log "Generating Docker Compose files for blue-green deployment..."
    
    # Blue environment (port 8001)
    cat > "$PROJECT_ROOT/$BLUE_COMPOSE_FILE" << 'EOF'
version: '3.8'

services:
  app-blue:
    build:
      context: .
      dockerfile: docker/production/Dockerfile
    container_name: laravel-app-blue
    restart: unless-stopped
    ports:
      - "8001:8000"
    environment:
      - APP_ENV=production
      - CONTAINER_ROLE=app
      - OCTANE_SERVER=frankenphp
    volumes:
      - ./storage:/var/www/html/storage
      - ./bootstrap/cache:/var/www/html/bootstrap/cache
    networks:
      - laravel-blue
    depends_on:
      - postgres
      - redis
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8000/health"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

  horizon-blue:
    build:
      context: .
      dockerfile: docker/production/Dockerfile
    container_name: laravel-horizon-blue
    restart: unless-stopped
    environment:
      - APP_ENV=production
      - CONTAINER_ROLE=horizon
    volumes:
      - ./storage:/var/www/html/storage
      - ./bootstrap/cache:/var/www/html/bootstrap/cache
    networks:
      - laravel-blue
    depends_on:
      - postgres
      - redis

  scheduler-blue:
    build:
      context: .
      dockerfile: docker/production/Dockerfile
    container_name: laravel-scheduler-blue
    restart: unless-stopped
    environment:
      - APP_ENV=production
      - CONTAINER_ROLE=scheduler
    volumes:
      - ./storage:/var/www/html/storage
      - ./bootstrap/cache:/var/www/html/bootstrap/cache
    networks:
      - laravel-blue
    depends_on:
      - postgres
      - redis

  postgres:
    image: postgres:16-alpine
    container_name: laravel-postgres
    restart: unless-stopped
    environment:
      POSTGRES_DB: laravel_production
      POSTGRES_USER: laravel
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./docker/production/postgres/init:/docker-entrypoint-initdb.d
    networks:
      - laravel-blue
      - laravel-green
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U laravel"]
      interval: 30s
      timeout: 10s
      retries: 3

  redis:
    image: redis:7-alpine
    container_name: laravel-redis
    restart: unless-stopped
    command: redis-server /usr/local/etc/redis/redis.conf
    volumes:
      - redis_data:/data
      - ./docker/production/redis/redis.conf:/usr/local/etc/redis/redis.conf
    networks:
      - laravel-blue
      - laravel-green
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 30s
      timeout: 10s
      retries: 3

networks:
  laravel-blue:
    driver: bridge

volumes:
  postgres_data:
  redis_data:
EOF

    # Green environment (port 8002)
    cat > "$PROJECT_ROOT/$GREEN_COMPOSE_FILE" << 'EOF'
version: '3.8'

services:
  app-green:
    build:
      context: .
      dockerfile: docker/production/Dockerfile
    container_name: laravel-app-green
    restart: unless-stopped
    ports:
      - "8002:8000"
    environment:
      - APP_ENV=production
      - CONTAINER_ROLE=app
      - OCTANE_SERVER=frankenphp
    volumes:
      - ./storage:/var/www/html/storage
      - ./bootstrap/cache:/var/www/html/bootstrap/cache
    networks:
      - laravel-green
    depends_on:
      - postgres
      - redis
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8000/health"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

  horizon-green:
    build:
      context: .
      dockerfile: docker/production/Dockerfile
    container_name: laravel-horizon-green
    restart: unless-stopped
    environment:
      - APP_ENV=production
      - CONTAINER_ROLE=horizon
    volumes:
      - ./storage:/var/www/html/storage
      - ./bootstrap/cache:/var/www/html/bootstrap/cache
    networks:
      - laravel-green
    depends_on:
      - postgres
      - redis

  scheduler-green:
    build:
      context: .
      dockerfile: docker/production/Dockerfile
    container_name: laravel-scheduler-green
    restart: unless-stopped
    environment:
      - APP_ENV=production
      - CONTAINER_ROLE=scheduler
    volumes:
      - ./storage:/var/www/html/storage
      - ./bootstrap/cache:/var/www/html/bootstrap/cache
    networks:
      - laravel-green
    depends_on:
      - postgres
      - redis

  postgres:
    image: postgres:16-alpine
    container_name: laravel-postgres
    restart: unless-stopped
    environment:
      POSTGRES_DB: laravel_production
      POSTGRES_USER: laravel
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./docker/production/postgres/init:/docker-entrypoint-initdb.d
    networks:
      - laravel-blue
      - laravel-green
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U laravel"]
      interval: 30s
      timeout: 10s
      retries: 3

  redis:
    image: redis:7-alpine
    container_name: laravel-redis
    restart: unless-stopped
    command: redis-server /usr/local/etc/redis/redis.conf
    volumes:
      - redis_data:/data
      - ./docker/production/redis/redis.conf:/usr/local/etc/redis/redis.conf
    networks:
      - laravel-blue
      - laravel-green
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 30s
      timeout: 10s
      retries: 3

networks:
  laravel-green:
    driver: bridge

volumes:
  postgres_data:
  redis_data:
EOF

    success "Docker Compose files generated"
}

# Update Nginx configuration to point to active environment
update_nginx_config() {
    local target_env="$1"
    local target_port=""
    
    if [[ "$target_env" == "blue" ]]; then
        target_port="8001"
    else
        target_port="8002"
    fi
    
    log "Updating Nginx configuration to point to $target_env environment (port $target_port)..."
    
    # Update upstream configuration
    cat > "$NGINX_CONFIG_DIR/conf.d/upstream.conf" << EOF
upstream laravel_app {
    server 127.0.0.1:$target_port max_fails=3 fail_timeout=30s;
    keepalive 32;
}
EOF
    
    # Reload Nginx configuration
    if docker-compose -f docker-compose.production.yml exec nginx nginx -t; then
        docker-compose -f docker-compose.production.yml exec nginx nginx -s reload
        success "Nginx configuration updated and reloaded"
    else
        error "Nginx configuration test failed"
        return 1
    fi
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

# Deploy to inactive environment
deploy_to_inactive() {
    log "Deploying to $INACTIVE_ENV environment..."
    
    local compose_file=""
    local health_url=""
    
    if [[ "$INACTIVE_ENV" == "blue" ]]; then
        compose_file="$BLUE_COMPOSE_FILE"
        health_url="$HEALTH_CHECK_URL_BLUE"
    else
        compose_file="$GREEN_COMPOSE_FILE"
        health_url="$HEALTH_CHECK_URL_GREEN"
    fi
    
    # Stop inactive environment if running
    log "Stopping $INACTIVE_ENV environment..."
    docker-compose -f "$compose_file" down --remove-orphans || true
    
    # Build new images
    log "Building new images for $INACTIVE_ENV environment..."
    docker-compose -f "$compose_file" build --no-cache app-$INACTIVE_ENV
    
    # Start inactive environment
    log "Starting $INACTIVE_ENV environment..."
    docker-compose -f "$compose_file" up -d
    
    # Wait for containers to be ready
    log "Waiting for $INACTIVE_ENV environment to be ready..."
    sleep 30
    
    # Run database migrations
    log "Running database migrations in $INACTIVE_ENV environment..."
    docker-compose -f "$compose_file" exec -T app-$INACTIVE_ENV php artisan migrate --force
    
    # Optimize application
    log "Optimizing $INACTIVE_ENV environment..."
    docker-compose -f "$compose_file" exec -T app-$INACTIVE_ENV php artisan config:cache
    docker-compose -f "$compose_file" exec -T app-$INACTIVE_ENV php artisan route:cache
    docker-compose -f "$compose_file" exec -T app-$INACTIVE_ENV php artisan view:cache
    docker-compose -f "$compose_file" exec -T app-$INACTIVE_ENV php artisan event:cache
    
    # Health check
    if ! health_check "$health_url" "$HEALTH_CHECK_TIMEOUT"; then
        error "Health check failed for $INACTIVE_ENV environment"
        return 1
    fi
    
    success "$INACTIVE_ENV environment deployed and healthy"
}

# Switch traffic to new environment
switch_traffic() {
    log "Switching traffic from $ACTIVE_ENV to $INACTIVE_ENV..."
    
    # Update Nginx configuration
    if ! update_nginx_config "$INACTIVE_ENV"; then
        error "Failed to update Nginx configuration"
        return 1
    fi
    
    # Wait for traffic to settle
    log "Waiting ${TRAFFIC_SWITCH_DELAY}s for traffic to settle..."
    sleep "$TRAFFIC_SWITCH_DELAY"
    
    # Update active environment
    echo "$INACTIVE_ENV" > "$PROJECT_ROOT/.active_env"
    
    # Swap environment variables
    local temp_env="$ACTIVE_ENV"
    ACTIVE_ENV="$INACTIVE_ENV"
    INACTIVE_ENV="$temp_env"
    
    success "Traffic switched to $ACTIVE_ENV environment"
}

# Stop old environment
stop_old_environment() {
    log "Stopping old $INACTIVE_ENV environment..."
    
    local compose_file=""
    
    if [[ "$INACTIVE_ENV" == "blue" ]]; then
        compose_file="$BLUE_COMPOSE_FILE"
    else
        compose_file="$GREEN_COMPOSE_FILE"
    fi
    
    # Gracefully stop the old environment
    docker-compose -f "$compose_file" stop
    
    success "Old $INACTIVE_ENV environment stopped"
}

# Rollback to previous environment
rollback() {
    error "Deployment failed, initiating rollback..."
    
    log "Rolling back to $ACTIVE_ENV environment..."
    
    # Switch traffic back to active environment
    if ! update_nginx_config "$ACTIVE_ENV"; then
        error "Failed to rollback Nginx configuration"
        exit 1
    fi
    
    # Stop failed deployment
    local failed_compose_file=""
    if [[ "$INACTIVE_ENV" == "blue" ]]; then
        failed_compose_file="$BLUE_COMPOSE_FILE"
    else
        failed_compose_file="$GREEN_COMPOSE_FILE"
    fi
    
    docker-compose -f "$failed_compose_file" down --remove-orphans
    
    success "Rollback completed"
}

# Main blue-green deployment function
blue_green_deploy() {
    log "Starting blue-green deployment..."
    
    # Detect current environment
    detect_active_environment
    
    # Generate compose files
    generate_compose_files
    
    # Deploy to inactive environment
    if ! deploy_to_inactive; then
        rollback
        exit 1
    fi
    
    # Switch traffic
    if ! switch_traffic; then
        rollback
        exit 1
    fi
    
    # Stop old environment
    stop_old_environment
    
    success "Blue-green deployment completed successfully!"
    log "Active environment is now: $ACTIVE_ENV"
}

# Show current status
show_status() {
    detect_active_environment
    
    echo "Blue-Green Deployment Status"
    echo "============================"
    echo "Active Environment: $ACTIVE_ENV"
    echo "Inactive Environment: $INACTIVE_ENV"
    echo ""
    
    # Check if environments are running
    if docker-compose -f "$BLUE_COMPOSE_FILE" ps app-blue 2>/dev/null | grep -q "Up"; then
        echo "Blue Environment: RUNNING"
    else
        echo "Blue Environment: STOPPED"
    fi
    
    if docker-compose -f "$GREEN_COMPOSE_FILE" ps app-green 2>/dev/null | grep -q "Up"; then
        echo "Green Environment: RUNNING"
    else
        echo "Green Environment: STOPPED"
    fi
}

# Script usage
usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  deploy          Perform blue-green deployment"
    echo "  status          Show current deployment status"
    echo "  switch          Switch traffic between environments"
    echo "  rollback        Rollback to previous environment"
    echo "  stop-inactive   Stop inactive environment"
    echo "  -h, --help      Show this help message"
}

# Main script logic
case "${1:-}" in
    deploy)
        blue_green_deploy
        ;;
    status)
        show_status
        ;;
    switch)
        detect_active_environment
        switch_traffic
        ;;
    rollback)
        detect_active_environment
        rollback
        ;;
    stop-inactive)
        detect_active_environment
        stop_old_environment
        ;;
    -h|--help)
        usage
        ;;
    *)
        usage
        exit 1
        ;;
esac