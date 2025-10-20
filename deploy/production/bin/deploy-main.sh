#!/usr/bin/env bash
# === Main Deployment Script (Bash Required) ===
# This script contains bash-specific features and should only be called by deploy.sh

# Ensure we're running bash 4.0 or later for full feature support
if [ "${BASH_VERSINFO[0]}" -lt 4 ]; then
    printf "WARNING: This script is designed for bash 4.0+. Current version: %s\n" "${BASH_VERSION}" >&2
    printf "Some features may not work correctly.\n" >&2
fi

set -Eeuo pipefail

# === Path Configuration ===
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT=""

# Robust path resolution with multiple fallbacks
resolve_project_root() {
    local current_dir="$SCRIPT_DIR"
    local max_depth=10
    local depth=0
    
    while [ $depth -lt $max_depth ]; do
        if [ -d "${current_dir}/deploy" ] && [ -f "${current_dir}/composer.json" ]; then
            PROJECT_ROOT="$current_dir"
            return 0
        fi
        
        local parent_dir
        if command -v realpath >/dev/null 2>&1; then
            parent_dir="$(realpath "${current_dir}/..")"
        elif command -v readlink >/dev/null 2>&1; then
            parent_dir="$(readlink -f "${current_dir}/..")"
        else
            parent_dir="$(cd "${current_dir}/.." && pwd)"
        fi
        
        if [ "$parent_dir" = "$current_dir" ]; then
            break
        fi
        
        current_dir="$parent_dir"
        depth=$((depth + 1))
    done
    
    return 1
}

if ! resolve_project_root; then
    echo "ERROR: Could not find project root (looking for directory with 'deploy' and 'composer.json')" >&2
    echo "Current script location: $SCRIPT_DIR" >&2
    exit 1
fi

if [ ! -d "${PROJECT_ROOT}" ] || [ ! -d "${PROJECT_ROOT}/deploy" ]; then
    echo "ERROR: Invalid project structure detected" >&2
    echo "PROJECT_ROOT: ${PROJECT_ROOT}" >&2
    echo "Expected: ${PROJECT_ROOT}/deploy directory" >&2
    exit 1
fi

DEPLOY_DIR="${PROJECT_ROOT}/deploy/production"
COMPOSE_FILE="${DEPLOY_DIR}/docker-compose.yml"
ENV_FILE_ABSOLUTE="${DEPLOY_DIR}/secrets/.env.production"

# === Logging Configuration ===
LOG_DIR="${DEPLOY_DIR}/logs"
mkdir -p "${LOG_DIR}"
LOG_FILE="${LOG_DIR}/deploy-$(date +%Y%m%d-%H%M%S).log"

# === Runtime Configuration ===
HEALTH_TIMEOUT_SECONDS=${HEALTH_TIMEOUT_SECONDS:-180}

exec > >(tee -a "${LOG_FILE}")
exec 2> >(tee -a "${LOG_FILE}" >&2)

trap 'on_error $LINENO' ERR

on_error() {
  local line=$1
  echo "ERROR: Script failed at line $line"
  exit 1
}

log() {
  echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

# === Registry Authentication Functions ===
has_registry_login() {
  local registry=$1
  local docker_config="${DOCKER_CONFIG:-${HOME}/.docker}/config.json"
  
  if [ ! -f "${docker_config}" ]; then
    return 1
  fi
  
  python3 - "${registry}" "${docker_config}" << 'PYTHON'
import json
import sys

registry = sys.argv[1]
config_file = sys.argv[2]

try:
    with open(config_file, 'r') as f:
        config = json.load(f)
    
    auths = config.get('auths', {})
    
    # Check for exact match
    if registry in auths:
        sys.exit(0)
    
    # Check for registry with protocol
    for auth_registry in auths:
        if auth_registry.endswith(registry) or registry in auth_registry:
            sys.exit(0)
    
    sys.exit(1)
except Exception:
    sys.exit(1)
PYTHON
}

ensure_registry_login() {
  local registry=$1
  
  if has_registry_login "${registry}"; then
    log "Credentials for ${registry} already configured"
    return 0
  fi
  
  # Generate environment variable names from registry
  local sanitized=${registry//[^a-zA-Z0-9]/_}
  local uppercase=$(echo "${sanitized}" | tr '[:lower:]' '[:upper:]')
  local username_var="${uppercase}_USERNAME"
  local token_var="${uppercase}_TOKEN"
  local token_file_var="${uppercase}_TOKEN_FILE"
  local username_hint=${username_var}
  local token_hint=${token_var}
  local token_file_hint=${token_file_var}
  
  if [ "${registry}" = "ghcr.io" ]; then
    username_hint="GHCR_USERNAME (GitHub username)"
    token_hint="GHCR_TOKEN (GitHub Personal Access Token with packages:read scope)"
    token_file_hint="GHCR_TOKEN_FILE (path to file containing GitHub token)"
  fi
  
  local username=${!username_var:-${GHCR_USERNAME:-}}
  
  local token=${!token_var:-${GHCR_TOKEN:-}}
  
  local token_file=${!token_file_var:-${GHCR_TOKEN_FILE:-}}
  
  if [ -n "${token_file}" ] && [ -f "${token_file}" ]; then
    token=$(cat "${token_file}")
  fi
  
  if [ -z "${username}" ] || [ -z "${token}" ]; then
    echo "ERROR: Registry credentials not found for ${registry}" >&2
    echo "Please set one of the following environment variable combinations:" >&2
    echo "  ${username_hint} and ${token_hint}" >&2
    echo "  ${username_hint} and ${token_file_hint}" >&2
    exit 1
  fi
  
  log "Logging into ${registry}..."
  echo "${token}" | docker login "${registry}" --username "${username}" --password-stdin
}

# === Utility Functions ===
require_command() {
  local command_name=$1
  if ! command -v "${command_name}" >/dev/null 2>&1; then
    echo "ERROR: Required command '${command_name}' not found" >&2
    exit 1
  fi
}

compose() {
  docker compose -f "${COMPOSE_FILE}" --env-file "${ENV_FILE_ABSOLUTE}" "$@"
}

wait_for_health() {
  local service=$1
  local timeout=${2:-${HEALTH_TIMEOUT_SECONDS}}
  local elapsed=0
  local step=5
  local container_id
  
  log "Waiting for ${service} to be healthy (timeout: ${timeout}s)..."
  
  while [ $elapsed -lt $timeout ]; do
    container_id=$(compose ps -q "${service}" 2>/dev/null || echo "")
    if [ -z "${container_id}" ]; then
      log "Container ${service} not found, waiting..."
      sleep $step
      elapsed=$((elapsed + step))
      continue
    fi
    
    local status
    status=$(docker inspect --format='{{.State.Health.Status}}' "${container_id}" 2>/dev/null || echo "none")
    
    case "${status}" in
      "healthy")
        log "${service} is healthy"
        return 0
        ;;
      "unhealthy")
        log "ERROR: ${service} is unhealthy"
        return 1
        ;;
      "starting"|"none")
        log "${service} health status: ${status}, waiting..."
        ;;
      *)
        log "Unknown health status for ${service}: ${status}"
        ;;
    esac
    
    sleep $step
    elapsed=$((elapsed + step))
  done
  
  log "ERROR: Timeout waiting for ${service} to be healthy"
  return 1
}

# === Main Deployment Logic ===
main() {
  log "Starting LearningCenter production deployment"
  log "Log file: ${LOG_FILE}"
  
  # Validate environment
  require_command docker
  require_command python3
  
  # Step 1: Fetch latest code
  log "Step 1/5: Fetching the latest code"
  cd "${PROJECT_ROOT}"
  git fetch origin
  # git reset --hard origin/main
  
  # Step 2: Registry authentication
  log "Step 2/5: Authenticating with container registry"
  case "${APP_IMAGE:-ghcr.io/hafizhfadh/learningcenter:latest}" in
    ghcr.io/*)
      ensure_registry_login "ghcr.io"
      ;;
  esac
  
  # Step 3: Update services
  log "Step 3/5: Recreating services"
  compose down --remove-orphans
  compose pull
  compose up -d --force-recreate
  
  # Step 4: Health checks
  log "Step 4/5: Performing health checks"
  wait_for_health "app"
  
  # Step 5: Verify deployment
  log "Step 5/5: Verifying deployment"
  local extensions_count=$(compose exec -T app php -d opcache.preload= -m | grep -E "(intl|pcntl|pdo_pgsql)" | wc -l)
  local opcache_count=$(compose exec -T app php -d opcache.preload= -m | grep -i "zend opcache" | wc -l)
  
  if [ "${extensions_count}" -lt 3 ]; then
    log "WARNING: Some required PHP extensions may be missing"
  fi
  
  if [ "${opcache_count}" -lt 1 ]; then
    log "WARNING: OPcache extension not detected"
  fi
  
  # Run migrations if enabled
  if [ "${RUN_MIGRATIONS:-1}" = "1" ]; then
    log "Running database migrations..."
    compose exec -T app php artisan migrate --force
  fi
  
  log "✅ Deployment completed successfully!"
  log "Application should be available at the configured URL"
}

# Execute main function
main "$@"