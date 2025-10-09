#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../../.." && pwd)"
COMPOSE_FILE="${PROJECT_ROOT}/deploy/production/docker-compose.yml"
ENV_FILE="${PROJECT_ROOT}/deploy/production/secrets/.env.production"
LOG_DIR="${PROJECT_ROOT}/deploy/production/logs"
mkdir -p "${LOG_DIR}"
LOG_FILE="${LOG_DIR}/deploy-$(date +%Y%m%d-%H%M%S).log"
HEALTH_TIMEOUT_SECONDS=${HEALTH_TIMEOUT_SECONDS:-180}

exec > >(tee -a "${LOG_FILE}")
exec 2> >(tee -a "${LOG_FILE}" >&2)

trap 'on_error $LINENO' ERR

on_error() {
  local line=$1
  printf '\n[\033[0;31mERROR\033[0m] Deployment failed at line %s. See %s for details.\n' "${line}" "${LOG_FILE}" >&2
}

log() {
  printf '[%(%Y-%m-%d %H:%M:%S)T] %s\n' -1 "$*"
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

if ! docker compose version >/dev/null 2>&1; then
  printf '[ERROR] Docker Compose plugin is not installed.\n' >&2
  exit 1
fi

log "Step 1/5: Fetching the latest code"
cd "${PROJECT_ROOT}"
git fetch --tags --prune
GIT_MERGE_AUTOEDIT=no git pull --rebase --autostash

log "Step 2/5: Syncing container images"
compose pull --quiet

log "Step 3/5: Recreating services"
compose up -d --remove-orphans

wait_for_health app "${HEALTH_TIMEOUT_SECONDS}"
wait_for_health traefik "${HEALTH_TIMEOUT_SECONDS}"

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
