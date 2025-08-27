#!/bin/bash
set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'

echo -e "${GREEN}🚀 Starting Laravel application with FrankenPHP...${NC}"

# Ensure writable config for PsySH if ever used
export HOME="${HOME:-/tmp}"
export XDG_CONFIG_HOME="${XDG_CONFIG_HOME:-/tmp}"

required=(APP_KEY DB_CONNECTION DB_HOST DB_DATABASE DB_USERNAME DB_PASSWORD)
missing=()
for v in "${required[@]}"; do
  [[ -z "${!v:-}" ]] && missing+=("$v")
done
if (( ${#missing[@]} )); then
  echo -e "${RED}❌ Missing env vars:${NC} ${missing[*]}"; exit 1
fi

wait_for_db() {
  echo "Waiting for PostgreSQL connection..."
  for i in {1..30}; do
    if command -v pg_isready >/dev/null 2>&1; then
      if pg_isready -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "$DB_USERNAME" -d "$DB_DATABASE" >/dev/null 2>&1; then
        echo "PostgreSQL is accepting connections"; return 0
      fi
    elif command -v psql >/dev/null 2>&1; then
      if PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT 1;" >/dev/null 2>&1; then
        echo "PostgreSQL accepted a connection"; return 0
      fi
    fi
    sleep 3
  done
  echo -e "${RED}❌ Could not connect to PostgreSQL after retries${NC}"; exit 1
}

run_migrations() {
  echo -e "${YELLOW}📦 Running database migrations...${NC}"
  php artisan migrate --force --no-interaction || { echo -e "${RED}❌ Migrations failed${NC}"; exit 1; }
  echo -e "${GREEN}✅ Database migrations completed${NC}"
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
  if ! grep -q "Route::get('/health'" routes/web.php; then
    cat <<'EOF' >> routes/web.php

// Health check endpoint
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'ts' => now()->toISOString()]);
});
EOF
  fi
}

wait_for_db
run_migrations
optimize_laravel
setup_health_check

echo -e "${GREEN}🚀 Launching Octane with FrankenPHP...${NC}"

# Check if we're in local development mode (no HTTPS)
if [[ "${APP_ENV:-production}" == "local" ]] || [[ "${OCTANE_HTTPS:-true}" == "false" ]]; then
  echo -e "${YELLOW}🔧 Starting in HTTP mode for local development (no Caddyfile)${NC}"
  exec php artisan octane:frankenphp \
    --host=0.0.0.0 \
    --port=80 \
    --admin-port=2019
else
  echo -e "${GREEN}🔒 Starting in HTTPS mode for production${NC}"
  exec php artisan octane:frankenphp \
    --host=0.0.0.0 \
    --port=443 \
    --admin-port=2019 \
    --https \
    --http-redirect \
    --caddyfile=/etc/frankenphp/Caddyfile
fi
