#!/bin/bash
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

export HOME="${HOME:-/tmp}"
export XDG_CONFIG_HOME="${XDG_CONFIG_HOME:-/tmp}"
export XDG_CACHE_HOME="${XDG_CACHE_HOME:-/tmp}"
export XDG_DATA_HOME="${XDG_DATA_HOME:-/tmp}"

echo -e "${GREEN}🚀 Starting Laravel application with FrankenPHP...${NC}"

# Function to check if required environment variables are set
check_required_env() {
    local required_vars=("APP_KEY" "DB_CONNECTION" "DB_HOST" "DB_DATABASE" "DB_USERNAME" "DB_PASSWORD")
    local missing_vars=()
    
    for var in "${required_vars[@]}"; do
        if [[ -z "${!var}" ]]; then
            missing_vars+=("$var")
        fi
    done
    
    if [[ ${#missing_vars[@]} -gt 0 ]]; then
        echo -e "${RED}❌ Missing required environment variables:${NC}"
        printf '%s\n' "${missing_vars[@]}"
        echo -e "${YELLOW}💡 Please set these environment variables before starting the container.${NC}"
        exit 1
    fi
}

# Function to test network connectivity to PostgreSQL cluster
test_network_connectivity() {
    echo -e "${YELLOW}🔍 Testing network connectivity to PostgreSQL cluster...${NC}"
    
    # Test basic network connectivity
    if command -v nc >/dev/null 2>&1; then
        if nc -z -w5 "$DB_HOST" "${DB_PORT:-5432}" 2>/dev/null; then
            echo -e "${GREEN}✅ Network connectivity to $DB_HOST:${DB_PORT:-5432} successful${NC}"
        else
            echo -e "${RED}❌ Network connectivity to $DB_HOST:${DB_PORT:-5432} failed${NC}"
            echo -e "${RED}This indicates a Docker networking issue${NC}"
            return 1
        fi
    else
        echo -e "${YELLOW}⚠️  netcat not available, skipping network test${NC}"
    fi
    
    # Test DNS resolution
    if command -v nslookup >/dev/null 2>&1; then
        if nslookup "$DB_HOST" >/dev/null 2>&1; then
            echo -e "${GREEN}✅ DNS resolution for $DB_HOST successful${NC}"
        else
            echo -e "${RED}❌ DNS resolution for $DB_HOST failed${NC}"
            return 1
        fi
    fi
    
    return 0
}

# Function to wait for PostgreSQL cluster
wait_for_db() {
    echo -e "${YELLOW}⏳ Waiting for PostgreSQL cluster connection...${NC}"
    
    # First test network connectivity
    if ! test_network_connectivity; then
        echo -e "${RED}❌ Network connectivity test failed${NC}"
        echo -e "${RED}Please check Docker networking configuration${NC}"
        exit 1
    fi
    
    max_attempts=30
    attempt=1
    
    while [[ $attempt -le $max_attempts ]]; do
        echo -e "${YELLOW}⏳ Attempt $attempt/$max_attempts - Testing database connection...${NC}"
        echo -e "${YELLOW}Connecting to: $DB_HOST:${DB_PORT:-5432}/$DB_DATABASE as $DB_USERNAME${NC}"
        echo -e "${YELLOW}SSL Mode: ${DB_SSLMODE:-prefer}${NC}"
        
        # Test with more detailed error output
        if php -r "
            require 'vendor/autoload.php';
            \$app = require 'bootstrap/app.php';
            \$app->make(Illuminate\Contracts\Console\Kernel::class);
            \$app['db']->connection()->getPdo();
        "; then
            echo '✅ PostgreSQL cluster connection established';
            return 0
        fi

        
        sleep 3
        ((attempt++))
    done
    
    echo -e "${RED}❌ PostgreSQL cluster connection failed after $max_attempts attempts${NC}"
    echo -e "${RED}Please verify:${NC}"
    echo -e "${RED}  - Cluster host: $DB_HOST${NC}"
    echo -e "${RED}  - Cluster port: ${DB_PORT:-5432}${NC}"
    echo -e "${RED}  - Database: $DB_DATABASE${NC}"
    echo -e "${RED}  - Username: $DB_USERNAME${NC}"
    echo -e "${RED}  - SSL mode: ${DB_SSLMODE:-prefer}${NC}"
    echo -e "${RED}  - Network connectivity from container to cluster${NC}"
    echo -e "${RED}  - Docker networking configuration${NC}"
    
    # Additional debugging information
    echo -e "${YELLOW}🔍 Container networking debug info:${NC}"
    echo -e "${YELLOW}Container IP: $(hostname -i 2>/dev/null || echo 'unknown')${NC}"
    echo -e "${YELLOW}Container hostname: $(hostname 2>/dev/null || echo 'unknown')${NC}"
    if command -v ip >/dev/null 2>&1; then
        echo -e "${YELLOW}Network interfaces:${NC}"
        ip addr show 2>/dev/null || echo "IP command failed"
    fi
    
    exit 1
}

# Function to run database migrations
run_migrations() {
    echo -e "${YELLOW}🔄 Running database migrations...${NC}"
    
    if php artisan migrate --force --no-interaction; then
        echo -e "${GREEN}✅ Database migrations completed${NC}"
    else
        echo -e "${RED}❌ Database migrations failed${NC}"
        exit 1
    fi
}

# Function to optimize Laravel for production
optimize_laravel() {
    echo -e "${YELLOW}⚡ Optimizing Laravel for production...${NC}"
    
    # Clear any existing caches first
    php artisan config:clear || true
    php artisan route:clear || true
    php artisan view:clear || true
    
    # Generate optimized caches
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    echo -e "${GREEN}✅ Laravel optimization completed${NC}"
}

# Function to create health check endpoint
setup_health_check() {
    echo -e "${YELLOW}🏥 Setting up health check endpoint...${NC}"
    
    # Create a simple health check route if it doesn't exist
    if ! grep -q "Route::get('/health'" routes/web.php; then
        echo "\n// Health check endpoint" >> routes/web.php
        echo "Route::get('/health', function () {" >> routes/web.php
        echo "    return response()->json([" >> routes/web.php
        echo "        'status' => 'ok'," >> routes/web.php
        echo "        'timestamp' => now()->toISOString()," >> routes/web.php
        echo "        'app' => config('app.name')," >> routes/web.php
        echo "        'version' => config('app.version', '1.0.0')" >> routes/web.php
        echo "    ]);" >> routes/web.php
        echo "});" >> routes/web.php
    fi
    
    echo -e "${GREEN}✅ Health check endpoint ready${NC}"
}

# Function to handle graceful shutdown
handle_shutdown() {
    echo -e "${YELLOW}🛑 Received shutdown signal, gracefully stopping...${NC}"
    
    # Send SIGTERM to FrankenPHP process
    if [[ -n "$FRANKENPHP_PID" ]]; then
        kill -TERM "$FRANKENPHP_PID" 2>/dev/null || true
        wait "$FRANKENPHP_PID" 2>/dev/null || true
    fi
    
    echo -e "${GREEN}✅ Application stopped gracefully${NC}"
    exit 0
}

# Set up signal handlers for graceful shutdown
trap handle_shutdown SIGTERM SIGINT

# Main startup sequence
main() {
    echo -e "${GREEN}🔍 Checking environment...${NC}"
    check_required_env
    
    echo -e "${GREEN}🔗 Checking database connection...${NC}"
    wait_for_db
    
    echo -e "${GREEN}📊 Running database migrations...${NC}"
    run_migrations
    
    echo -e "${GREEN}⚡ Optimizing application...${NC}"
    optimize_laravel
    
    echo -e "${GREEN}🏥 Setting up health checks...${NC}"
    setup_health_check
    
    echo -e "${GREEN}🚀 Starting FrankenPHP with Octane...${NC}"
    
    # Determine if we should expose admin port based on environment
    ADMIN_PORT_ARG=""
    if [[ "${CADDY_ADMIN_ENABLED:-false}" == "true" ]]; then
        ADMIN_PORT_ARG="--admin-port=2019"
        echo -e "${YELLOW}⚠️  Caddy admin port enabled on 2019${NC}"
    fi
    
    # Start FrankenPHP with Octane
    exec php artisan octane:frankenphp \
        --host=0.0.0.0 \
        --port=443 \
        $ADMIN_PORT_ARG \
        --https \
        --http-redirect \
        --caddyfile=/etc/frankenphp/Caddyfile &
    
    FRANKENPHP_PID=$!
    
    echo -e "${GREEN}✅ Application started successfully (PID: $FRANKENPHP_PID)${NC}"
    echo -e "${GREEN}🌐 Application available at https://localhost${NC}"
    
    # Wait for the process to finish
    wait "$FRANKENPHP_PID"
}

# Run main function
main "$@"