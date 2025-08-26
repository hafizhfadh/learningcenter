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

# Optional network connectivity test using netcat/nslookup
test_network_connectivity() {
  if command -v nc >/dev/null 2>&1; then
    echo -e "${YELLOW}🔍 Checking network connectivity to ${DB_HOST}:${DB_PORT:-5432}...${NC}"
    if nc -z -w5 "$DB_HOST" "${DB_PORT:-5432}" 2>/dev/null; then
      echo -e "${GREEN}✅ Network connectivity to $DB_HOST:${DB_PORT:-5432} OK${NC}"
    else
      echo -e "${RED}❌ Cannot connect to $DB_HOST:${DB_PORT:-5432}${NC}"
      return 1
    fi
  fi
  if command -v nslookup >/dev/null 2>&1; then
    if nslookup "$DB_HOST" >/dev/null 2>&1; then
      echo -e "${GREEN}✅ DNS resolution for $DB_HOST OK${NC}"
    else
      echo -e "${RED}❌ DNS resolution failed for $DB_HOST${NC}"
      return 1
    fi
  fi
  return 0
}

wait_for_db() {
  echo -e "${YELLOW}⏳ Waiting for PostgreSQL connection...${NC}"
  test_network_connectivity || echo -e "${YELLOW}⚠️  Network test skipped or failed; will try DB directly...${NC}"
  local max_attempts=30
  for (( attempt=1; attempt<=max_attempts; attempt++ )); do
    echo -e "${YELLOW}⏳ Attempt $attempt/$max_attempts: Connecting to $DB_HOST:${DB_PORT:-5432}/$DB_DATABASE as $DB_USERNAME (sslmode=${DB_SSLMODE:-prefer})...${NC}"
    # Use plain PHP to test DB connection via Laravel's DB facade
    if php -r "
      require 'vendor/autoload.php';
      \$app = require 'bootstrap/app.php';
      \$app->make(Illuminate\Contracts\Console\Kernel::class);
      try {
        \$pdo = \$app['db']->connection()->getPdo();
        exit(0);
      } catch (\Throwable \$e) {
        fwrite(STDERR, \$e->getMessage());
        exit(1);
      }" > /dev/null 2>&1; then
      echo -e "${GREEN}✅ PostgreSQL cluster connection established${NC}"
      return 0
    fi
    sleep 3
  done
  echo -e "${RED}❌ Could not connect to PostgreSQL after $max_attempts attempts${NC}"
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
