#!/bin/sh
# === Shell Compatibility Wrapper ===
# This script requires bash but may be executed with sh
# Re-execute with bash if not already running in bash
if [ -z "${BASH_VERSION:-}" ]; then
    if command -v bash >/dev/null 2>&1; then
        printf "Re-executing with bash...\n" >&2
        exec bash "$0" "$@"
    else
        printf "ERROR: bash is required but not found in PATH.\n" >&2
        printf "Please install bash or run with: bash %s\n" "$0" >&2
        exit 1
    fi
fi

# === Bash Script Starts Here ===
# From this point on, we're guaranteed to be running in bash

# Ensure we're running bash 4.0 or later for full feature support
if [ "${BASH_VERSINFO[0]}" -lt 4 ]; then
    printf "WARNING: This script is designed for bash 4.0+. Current version: %s\n" "${BASH_VERSION}" >&2
    printf "Some features may not work correctly.\n" >&2
fi

set -Eeuo pipefail

# === Path Configuration ===
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
<<<<<<< HEAD
<<<<<<< Updated upstream
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../../.." && pwd)"
=======

# Robust PROJECT_ROOT resolution with fallback and validation
if PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../../.." 2>/dev/null && pwd)"; then
    # Successful cd - use absolute path
    :
else
    # Fallback: resolve manually using realpath or readlink
    if command -v realpath >/dev/null 2>&1; then
        PROJECT_ROOT="$(realpath "${SCRIPT_DIR}/../../..")"
    elif command -v readlink >/dev/null 2>&1; then
        PROJECT_ROOT="$(readlink -f "${SCRIPT_DIR}/../../..")"
    else
        # Last resort: manual resolution
        PROJECT_ROOT="$(dirname "$(dirname "$(dirname "${SCRIPT_DIR}")")")"
    fi
fi

# Validate PROJECT_ROOT exists and contains expected structure
if [[ ! -d "${PROJECT_ROOT}" ]] || [[ ! -d "${PROJECT_ROOT}/deploy" ]]; then
    echo "ERROR: Invalid PROJECT_ROOT: ${PROJECT_ROOT}" >&2
    echo "Expected structure: PROJECT_ROOT/deploy/production/bin/deploy.sh" >&2
    exit 1
fi

>>>>>>> refs/remotes/origin/main
DEPLOY_DIR="${PROJECT_ROOT}/deploy/production"

# === File Paths ===
COMPOSE_FILE="${DEPLOY_DIR}/docker-compose.yml"
ENV_FILE="${DEPLOY_DIR}/secrets/.env.production"

# === Logging Configuration ===
LOG_DIR="${DEPLOY_DIR}/logs"
=======
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
COMPOSE_FILE="${PROJECT_ROOT}/deploy/production/docker-compose.yml"
ENV_FILE="${PROJECT_ROOT}/deploy/production/secrets/.env.production"
LOG_DIR="${PROJECT_ROOT}/deploy/production/logs"
>>>>>>> Stashed changes
mkdir -p "${LOG_DIR}"
LOG_FILE="${LOG_DIR}/deploy-$(date +%Y%m%d-%H%M%S).log"

# === Runtime Configuration ===
HEALTH_TIMEOUT_SECONDS=${HEALTH_TIMEOUT_SECONDS:-180}

exec > >(tee -a "${LOG_FILE}")
exec 2> >(tee -a "${LOG_FILE}" >&2)

trap 'on_error $LINENO' ERR

on_error() {
  local line=$1
  printf '\n[\033[0;31mERROR\033[0m] Deployment failed at line %s. See %s for details.\n' "${line}" "${LOG_FILE}" >&2
}

log() {
  printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"
}

ensure_registry_login() {
  local registry=$1

  local sanitized=${registry//[^a-zA-Z0-9]/_}
  local uppercase=${sanitized^^}
  local username_var="${uppercase}_USERNAME"
  local token_var="${uppercase}_TOKEN"
  local token_file_var="${uppercase}_TOKEN_FILE"
  local username_hint=${username_var}
  local token_hint=${token_var}
  local token_file_hint=${token_file_var}

  if [[ "${registry}" == "ghcr.io" ]]; then
    username_hint="GHCR_USERNAME"
    token_hint="GHCR_TOKEN"
    token_file_hint="GHCR_TOKEN_FILE"
  fi

  # shellcheck disable=SC2086
  local username=${!username_var:-${GHCR_USERNAME:-}}
  # shellcheck disable=SC2086
  local token=${!token_var:-${GHCR_TOKEN:-}}
  # shellcheck disable=SC2086
  local token_file=${!token_file_var:-${GHCR_TOKEN_FILE:-}}

  if [[ -n "${token_file}" && -f "${token_file}" ]]; then
    token=$(<"${token_file}")
  fi

  if [[ -z "${username}" || -z "${token}" ]]; then
    printf '[ERROR] Missing credentials for %s. Set %s/%s or provide %s.\n' \
      "${registry}" "${username_hint}" "${token_hint}" "${token_file_hint}" >&2
    exit 1
  fi

  log "Logging into ${registry} as ${username}"
  if ! printf '%s' "${token}" | docker login "${registry}" --username "${username}" --password-stdin >/dev/null 2>&1; then
    printf '[ERROR] Docker login to %s failed.\n' "${registry}" >&2
    exit 1
  fi

  log "Successfully authenticated with ${registry}"
}

require_command() {
  local command_name=$1
  if ! command -v "${command_name}" >/dev/null 2>&1; then
    printf '[ERROR] Required command "%s" is not available in PATH.\n' "${command_name}" >&2
    exit 1
  fi
}

compose() {
  docker compose --project-directory "${PROJECT_ROOT}" --env-file "${ENV_FILE}" -f "${COMPOSE_FILE}" "$@"
}

wait_for_health() {
  local service=$1
  local timeout=${2:-${HEALTH_TIMEOUT_SECONDS}}
  local elapsed=0
  local step=5
  local container_id

  log "Waiting for ${service} container health (${timeout}s timeout)"
  while (( elapsed <= timeout )); do
    container_id=$(compose ps -q "${service}" || true)
    if [[ -z "${container_id}" ]]; then
      sleep "${step}"
      elapsed=$((elapsed + step))
      continue
    fi

    local status
    status=$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "${container_id}" 2>/dev/null || true)
    case "${status}" in
      healthy|running)
        log "${service} container is ${status}"
        return 0
        ;;
      exited|dead)
        log "${service} container is in ${status} state"
        docker logs "${container_id}" | tail -n 50 >&2 || true
        return 1
        ;;
      *)
        sleep "${step}"
        elapsed=$((elapsed + step))
        ;;
    esac
  done

  log "Timed out waiting for ${service} container health"
  compose logs "${service}" | tail -n 50 >&2 || true
  return 1
}

log "Starting LearningCenter production deployment"
log "Log file: ${LOG_FILE}"

require_command git
require_command docker
require_command python3

if ! docker compose version >/dev/null 2>&1; then
  printf '[ERROR] Docker Compose plugin is not installed.\n' >&2
  exit 1
fi

log "Step 1/5: Fetching the latest code"
cd "${PROJECT_ROOT}"
git fetch --tags --prune
GIT_MERGE_AUTOEDIT=no git pull --rebase --autostash

log "Step 2/5: Syncing container images"
if [[ "${APP_IMAGE:-ghcr.io/hafizhfadh/learningcenter:latest}" == ghcr.io/* ]]; then
  ensure_registry_login "ghcr.io"
fi
compose pull --quiet

log "Step 3/5: Recreating services"
compose up -d --remove-orphans

wait_for_health app "${HEALTH_TIMEOUT_SECONDS}"
wait_for_health traefik "${HEALTH_TIMEOUT_SECONDS}"

log "🔍 Verifying PHP extensions in deployed image..."

# Test with opcache.preload disabled to avoid preload file issues during testing
local extensions_count=$(compose exec -T app php -d opcache.preload= -m | grep -E "(intl|pcntl|pdo_pgsql)" | wc -l)
local opcache_count=$(compose exec -T app php -d opcache.preload= -m | grep -i "zend opcache" | wc -l)

if [ "$extensions_count" -eq "3" ] && [ "$opcache_count" -ge "1" ]; then
  log "✅ All required PHP extensions found (intl, pcntl, pdo_pgsql, opcache)"
else
  log "⚠️  Warning: Missing required PHP extensions"
  log "   Found $extensions_count/3 basic extensions and $opcache_count opcache extensions"
fi

if [[ "${RUN_MIGRATIONS:-1}" == "1" ]]; then
  log "Step 4/5: Running database migrations"
  compose exec -T app php artisan migrate --force
else
  log "Step 4/5: Skipping database migrations (RUN_MIGRATIONS=${RUN_MIGRATIONS})"
fi

log "Step 5/5: Optimizing Laravel caches"
compose exec -T app php artisan optimize:clear
compose exec -T app php artisan optimize
compose exec -T app php artisan event:cache
compose exec -T app php artisan route:cache
compose exec -T app php artisan config:cache
compose exec -T app php artisan queue:restart

log "Verifying container health checks"
compose ps --format 'table {{.Service}}\t{{.Status}}'

log "Deployment completed successfully"
