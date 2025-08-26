#!/bin/bash

# Simple deployment wrapper for Ubuntu production server
# This script ensures environment variables are properly loaded

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if .env.production exists
if [[ ! -f ".env.production" ]]; then
    log_error ".env.production file not found!"
    log_error "Please create .env.production with your production environment variables."
    exit 1
fi

# Load environment variables from .env.production
log_info "Loading environment variables from .env.production..."
set -a  # automatically export all variables
source .env.production
set +a  # stop automatically exporting
log_success "Environment variables loaded"

# Validate required variables are set
required_vars=(
    "APP_NAME"
    "APP_ENV"
    "APP_KEY"
    "APP_URL"
    "DB_HOST"
    "DB_DATABASE"
    "DB_USERNAME"
    "DB_PASSWORD"
)

missing_vars=()
for var in "${required_vars[@]}"; do
    if [[ -z "${!var:-}" ]]; then
        missing_vars+=("$var")
    fi
done

if [[ ${#missing_vars[@]} -gt 0 ]]; then
    log_error "Missing required environment variables in .env.production:"
    printf ' - %s\n' "${missing_vars[@]}"
    exit 1
fi

log_success "All required environment variables are set"

# Run the actual deployment script
log_info "Starting deployment..."
exec ./scripts/deploy-production.sh "${1:-v1.0.0}"