#!/bin/bash
set -e

# Colour definitions for pretty output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}🚀 Starting Laravel application with FrankenPHP...${NC}"

check_required_env() {
  local required=("APP_KEY" "DB_CONNECTION" "DB_HOST" "DB_DATABASE" "DB_USERNAME" "DB_PASSWORD")
  local missing=()
  for var in "${required[@]}"; do
    if [[ -z "${!var}" ]]; then
      missing+=("$var")
    fi
  done
  if [[ ${#missing[@]} -gt 0 ]]; then
    echo -e "${RED}❌ Missing required environment variables:${NC}"
    printf '%s\n' "${missing[@]}"
    exit 1
  fi
}

# Ensure PsySH writes its config to a writable directory (avoid /config/psysh)
export HOME="${HOME:-/tmp}"
export XDG_CONFIG_HOME="${XDG_CONFIG_HOME:-/tmp}"

# Function to test network connectivity and DB readiness
test_network_connectivity() {
    echo -e "${YELLOW}🔍 Checking network connectivity to $DB_HOST:${DB_PORT:-5432}...${NC}"

    # Optional: basic TCP check
    if command -v nc >/dev/null 2>&1; then
        if nc -z -w5 "$DB_HOST" "${DB_PORT:-5432}" >/dev/null 2>&1; then
            echo -e "${GREEN}✅ Port $DB_PORT open on $DB_HOST${NC}"
        else
            echo -e "${RED}❌ Cannot reach $DB_HOST:${DB_PORT:-5432}${NC}"
            return 1
        fi
    fi

    # Check server readiness using pg_isready or psql
    if command -v pg_isready >/dev/null 2>&1; then
        if pg_isready -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "$DB_USERNAME" -d "$DB_DATABASE" >/dev/null 2>&1; then
            echo -e "${GREEN}✅ PostgreSQL server is ready${NC}"
        else
            echo -e "${RED}❌ PostgreSQL server not accepting connections${NC}"
            return 1
        fi
    elif command -v psql >/dev/null 2>&1; then
        if PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "${DB_PORT:-5432}" \
              -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT 1;" >/dev/null 2>&1; then
            echo -e "${GREEN}✅ PostgreSQL accepted a connection${NC}"
        else
            echo -e "${RED}❌ Could not connect to PostgreSQL with psql${NC}"
            return 1
        fi
    else
        echo -e "${YELLOW}⚠️  Neither pg_isready nor psql available; skipping DB probe${NC}"
    fi

    return 0
}

wait_for_db() {
  echo "Waiting for PostgreSQL connection..."
  for i in {1..30}; do
    if pg_isready -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "$DB_USERNAME" -d "$DB_DATABASE" >/dev/null 2>&1; then
      echo "PostgreSQL is accepting connections"
      return 0
    fi
    sleep 3
  done
  echo "Failed to connect to PostgreSQL"
  exit 1
}

run_migrations() {
  echo -e "${YELLOW}📦 Running database migrations...${NC}"
  if php artisan migrate --force --no-interaction; then
    echo -e "${GREEN}✅ Database migrations completed${NC}"
  else
    echo -e "${RED}❌ Database migrations failed${NC}"
    exit 1
  fi
}

optimize_laravel() {
  echo -e "${YELLOW}⚡ Optimizing Laravel for production...${NC}"
  php artisan config:clear || true
  php artisan route:clear || true
  php artisan view:clear || true
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  echo -e "${GREEN}✅ Laravel optimization complete${NC}"
}

setup_health_check() {
  # Add a health check route if it doesn't exist
  if ! grep -q "Route::get('/health'" routes/web.php; then
    echo -e "${YELLOW}🩺 Adding /health endpoint...${NC}"
    cat <<'EOF' >> routes/web.php

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
    ]);
});
EOF
  fi
}

# Main
check_required_env
wait_for_db
run_migrations
optimize_laravel
setup_health_check

echo -e "${GREEN}🚀 Launching Octane with FrankenPHP...${NC}"
exec php artisan octane:frankenphp \
  --host=0.0.0.0 \
  --port=443 \
  --admin-port=2019 \
  --https \
  --http-redirect \
  --caddyfile=/etc/frankenphp/Caddyfile
