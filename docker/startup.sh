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

# Normalize FrankenPHP / Octane environment defaults so they stay in sync with
# config/octane.php and docker-compose based deployments.
FRANKENPHP_WORKERS=${OCTANE_FRANKENPHP_WORKERS:-${OCTANE_WORKERS:-auto}}
FRANKENPHP_ADMIN_PORT=${OCTANE_FRANKENPHP_ADMIN_PORT:-2019}
FRANKENPHP_ENABLE_HTTPS=${OCTANE_FRANKENPHP_HTTPS:-${OCTANE_HTTPS:-true}}
FRANKENPHP_HTTP_REDIRECT=${OCTANE_FRANKENPHP_HTTP_REDIRECT:-true}
FRANKENPHP_CADDYFILE=${OCTANE_FRANKENPHP_CADDYFILE:-${FRANKENPHP_CONFIG_PATH:-/etc/frankenphp/Caddyfile}}
HTTP_PORT=${OCTANE_HTTP_PORT:-80}
HTTPS_PORT=${OCTANE_PORT:-443}

workers_flag=()
if [[ -n "${FRANKENPHP_WORKERS}" && "${FRANKENPHP_WORKERS}" != "auto" ]]; then
  workers_flag+=(--workers="${FRANKENPHP_WORKERS}")
fi

admin_flag=(--admin-port="${FRANKENPHP_ADMIN_PORT}")

https_enabled=false
case "${FRANKENPHP_ENABLE_HTTPS,,}" in
  1|true|yes|on) https_enabled=true ;;
esac

http_redirect_enabled=false
case "${FRANKENPHP_HTTP_REDIRECT,,}" in
  1|true|yes|on) http_redirect_enabled=true ;;
esac

caddy_flags=()
if [[ -n "${FRANKENPHP_CADDYFILE}" ]]; then
  caddy_flags+=(--caddyfile="${FRANKENPHP_CADDYFILE}")
fi

command=(php artisan octane:frankenphp --host=0.0.0.0)
if (( ${#workers_flag[@]} )); then
  command+=("${workers_flag[@]}")
fi
command+=("${admin_flag[@]}")

# Check if we're in local development mode (no HTTPS)
if [[ "${APP_ENV:-production}" == "local" ]] || [[ $https_enabled == false ]]; then
  echo -e "${YELLOW}🔧 Starting in HTTP mode for local development (no Caddyfile)${NC}"
  command+=(--port="${HTTP_PORT}")
  exec "${command[@]}"
else
  echo -e "${GREEN}🔒 Starting in HTTPS mode for production${NC}"
  command+=(--port="${HTTPS_PORT}" --https)
  if [[ $http_redirect_enabled == true ]]; then
    command+=(--http-redirect)
  fi
  if (( ${#caddy_flags[@]} )); then
    command+=("${caddy_flags[@]}")
  fi
  exec "${command[@]}"
fi
