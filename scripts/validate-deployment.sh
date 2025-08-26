#!/bin/bash

# Laravel Learning Center - Deployment Validation Script
# This script validates the entire deployment process and configuration

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
COMPOSE_FILE="$PROJECT_ROOT/docker-compose.production.yml"
ENV_FILE="$PROJECT_ROOT/.env.production"
DOCKERFILE="$PROJECT_ROOT/Dockerfile.frankenphp.improved"
DEPLOY_SCRIPT="$SCRIPT_DIR/deploy-production.sh"

# Validation results
VALIDATION_ERRORS=0
VALIDATION_WARNINGS=0

# Logging functions
log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
    ((VALIDATION_WARNINGS++))
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
    ((VALIDATION_ERRORS++))
}

log_section() {
    echo -e "\n${BLUE}📋 $1${NC}"
    echo "═══════════════════════════════════════════════════════════════════════════════"
}

# Function to check if file exists and is readable
check_file() {
    local file="$1"
    local description="$2"
    
    if [[ -f "$file" && -r "$file" ]]; then
        log_success "$description exists and is readable"
        return 0
    else
        log_error "$description missing or not readable: $file"
        return 1
    fi
}

# Function to check if command exists
check_command() {
    local cmd="$1"
    local description="$2"
    
    if command -v "$cmd" >/dev/null 2>&1; then
        log_success "$description is available"
        return 0
    else
        log_error "$description not found: $cmd"
        return 1
    fi
}

# Function to validate environment file
validate_env_file() {
    log_section "Environment Configuration Validation"
    
    if ! check_file "$ENV_FILE" "Environment file (.env.production)"; then
        return 1
    fi
    
    # Source environment file
    set -a
    source "$ENV_FILE"
    set +a
    
    # Required environment variables
    local required_vars=(
        "APP_NAME"
        "APP_ENV"
        "APP_KEY"
        "APP_URL"
        "DB_CONNECTION"
        "DB_HOST"
        "DB_PORT"
        "DB_DATABASE"
        "DB_USERNAME"
        "DB_PASSWORD"
        "REDIS_HOST"
        "REDIS_PORT"
    )
    
    local missing_vars=()
    for var in "${required_vars[@]}"; do
        if [[ -z "${!var}" ]]; then
            missing_vars+=("$var")
        else
            log_success "$var is set"
        fi
    done
    
    if [[ ${#missing_vars[@]} -gt 0 ]]; then
        log_error "Missing required environment variables: ${missing_vars[*]}"
        return 1
    fi
    
    # Validate specific values
    if [[ "$APP_ENV" != "production" ]]; then
        log_warning "APP_ENV is not set to 'production' (current: $APP_ENV)"
    fi
    
    if [[ "$APP_DEBUG" == "true" ]]; then
        log_warning "APP_DEBUG is enabled in production"
    fi
    
    if [[ "$DB_CONNECTION" != "pgsql" ]]; then
        log_warning "DB_CONNECTION is not 'pgsql' (current: $DB_CONNECTION)"
    fi
    
    # Validate APP_KEY format
    if [[ ! "$APP_KEY" =~ ^base64: ]]; then
        log_warning "APP_KEY does not appear to be base64 encoded"
    fi
    
    log_success "Environment configuration validation completed"
}

# Function to validate Docker configuration
validate_docker_config() {
    log_section "Docker Configuration Validation"
    
    # Check required files
    check_file "$DOCKERFILE" "Dockerfile"
    check_file "$COMPOSE_FILE" "Docker Compose file"
    
    # Check Docker and Docker Compose
    check_command "docker" "Docker"
    check_command "docker" "Docker Compose (docker compose)"
    
    # Validate Docker Compose file syntax
    if docker compose -f "$COMPOSE_FILE" config >/dev/null 2>&1; then
        log_success "Docker Compose file syntax is valid"
    else
        log_error "Docker Compose file syntax validation failed"
        return 1
    fi
    
    # Check if required services are defined
    local services
    services=$(docker compose -f "$COMPOSE_FILE" config --services 2>/dev/null || echo "")
    
    if echo "$services" | grep -q "app"; then
        log_success "App service is defined"
    else
        log_error "App service not found in Docker Compose file"
    fi
    
    if echo "$services" | grep -q "redis"; then
        log_success "Redis service is defined"
    else
        log_error "Redis service not found in Docker Compose file"
    fi
    
    log_success "Docker configuration validation completed"
}

# Function to validate network connectivity
validate_network_connectivity() {
    log_section "Network Connectivity Validation"
    
    # Source environment for DB variables
    set -a
    source "$ENV_FILE"
    set +a
    
    # Test DNS resolution (skip for IP addresses)
    if command -v nslookup >/dev/null 2>&1; then
        if [[ "$DB_HOST" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
            log_success "DB_HOST is an IP address ($DB_HOST), skipping DNS resolution"
        elif nslookup "$DB_HOST" >/dev/null 2>&1; then
            log_success "DNS resolution for $DB_HOST successful"
        else
            log_error "DNS resolution for $DB_HOST failed"
        fi
    else
        log_warning "nslookup not available, skipping DNS test"
    fi
    
    # Test network connectivity
    if command -v nc >/dev/null 2>&1; then
        if nc -z -w5 "$DB_HOST" "$DB_PORT" 2>/dev/null; then
            log_success "Network connectivity to $DB_HOST:$DB_PORT successful"
        else
            log_error "Network connectivity to $DB_HOST:$DB_PORT failed"
        fi
    else
        log_warning "netcat not available, skipping network connectivity test"
    fi
    
    # Test PostgreSQL connectivity
    if command -v psql >/dev/null 2>&1; then
        if timeout 10 PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT 1;" >/dev/null 2>&1; then
            log_success "PostgreSQL cluster connectivity successful"
        else
            log_error "PostgreSQL cluster connectivity failed"
        fi
    else
        log_warning "psql not available, skipping PostgreSQL connectivity test"
    fi
    
    log_success "Network connectivity validation completed"
}

# Function to validate deployment script
validate_deployment_script() {
    log_section "Deployment Script Validation"
    
    if ! check_file "$DEPLOY_SCRIPT" "Deployment script"; then
        return 1
    fi
    
    # Check if script is executable
    if [[ -x "$DEPLOY_SCRIPT" ]]; then
        log_success "Deployment script is executable"
    else
        log_warning "Deployment script is not executable (run: chmod +x $DEPLOY_SCRIPT)"
    fi
    
    # Test script syntax
    if bash -n "$DEPLOY_SCRIPT" 2>/dev/null; then
        log_success "Deployment script syntax is valid"
    else
        log_error "Deployment script syntax validation failed"
        return 1
    fi
    
    log_success "Deployment script validation completed"
}

# Function to validate security configuration
validate_security_config() {
    log_section "Security Configuration Validation"
    
    # Source environment for security checks
    set -a
    source "$ENV_FILE"
    set +a
    
    # Check APP_DEBUG
    if [[ "$APP_DEBUG" == "false" ]]; then
        log_success "APP_DEBUG is disabled"
    else
        log_error "APP_DEBUG should be disabled in production"
    fi
    
    # Check APP_KEY
    if [[ -n "$APP_KEY" && "$APP_KEY" != "" ]]; then
        log_success "APP_KEY is set"
    else
        log_error "APP_KEY is not set"
    fi
    
    # Check for default passwords
    if [[ "$DB_PASSWORD" == "password" || "$DB_PASSWORD" == "secret" ]]; then
        log_error "Database password appears to be a default value"
    else
        log_success "Database password is not a default value"
    fi
    
    # Check SSL configuration
    if [[ "$DB_SSLMODE" == "require" || "$DB_SSLMODE" == "prefer" ]]; then
        log_success "SSL is configured for database connection"
    else
        log_warning "SSL is not properly configured for database connection"
    fi
    
    # Check HTTPS configuration
    if [[ "$APP_URL" =~ ^https:// ]]; then
        log_success "HTTPS is configured for application URL"
    else
        log_warning "HTTPS is not configured for application URL"
    fi
    
    log_success "Security configuration validation completed"
}

# Function to run comprehensive validation
run_validation() {
    echo -e "${GREEN}🔍 Laravel Learning Center - Deployment Validation${NC}"
    echo -e "${GREEN}═══════════════════════════════════════════════════════════════════════════════${NC}"
    
    validate_env_file
    validate_docker_config
    validate_network_connectivity
    validate_deployment_script
    validate_security_config
    
    # Summary
    log_section "Validation Summary"
    
    if [[ $VALIDATION_ERRORS -eq 0 ]]; then
        log_success "All critical validations passed!"
    else
        log_error "$VALIDATION_ERRORS critical validation(s) failed"
    fi
    
    if [[ $VALIDATION_WARNINGS -gt 0 ]]; then
        log_warning "$VALIDATION_WARNINGS warning(s) found"
    fi
    
    echo -e "\n${BLUE}📋 Next Steps:${NC}"
    if [[ $VALIDATION_ERRORS -eq 0 ]]; then
        echo -e "${GREEN}✅ Your deployment configuration is ready!${NC}"
        echo -e "${GREEN}   Run: $DEPLOY_SCRIPT${NC}"
    else
        echo -e "${RED}❌ Please fix the validation errors before deploying${NC}"
        echo -e "${RED}   Review the error messages above${NC}"
    fi
    
    if [[ $VALIDATION_WARNINGS -gt 0 ]]; then
        echo -e "${YELLOW}⚠️  Consider addressing the warnings for optimal security and performance${NC}"
    fi
    
    # Exit with appropriate code
    if [[ $VALIDATION_ERRORS -gt 0 ]]; then
        exit 1
    else
        exit 0
    fi
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Validate Laravel Learning Center deployment configuration"
    echo ""
    echo "Options:"
    echo "  --help, -h    Show this help message"
    echo ""
    echo "This script validates:"
    echo "  - Environment configuration (.env.production)"
    echo "  - Docker configuration (Dockerfile, docker-compose.yml)"
    echo "  - Network connectivity to PostgreSQL cluster"
    echo "  - Deployment script configuration"
    echo "  - Security configuration"
    echo ""
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --help|-h)
            show_usage
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            show_usage
            exit 1
            ;;
    esac
done

# Run validation
run_validation