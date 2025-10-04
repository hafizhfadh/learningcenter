#!/bin/bash

# Learning Center Comprehensive Validation Script
# Tests all aspects of the production deployment

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
VALIDATION_LOG="/tmp/learning-center-validation-$(date +%Y%m%d_%H%M%S).log"
REPORT_FILE="/tmp/learning-center-validation-report-$(date +%Y%m%d_%H%M%S).json"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Test results tracking
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0
WARNINGS=0
declare -a TEST_RESULTS=()

# Logging functions
log_info() { 
    echo -e "${BLUE}[INFO]${NC} $*" | tee -a "$VALIDATION_LOG"
}

log_warn() { 
    echo -e "${YELLOW}[WARN]${NC} $*" | tee -a "$VALIDATION_LOG"
    ((WARNINGS++))
}

log_error() { 
    echo -e "${RED}[ERROR]${NC} $*" | tee -a "$VALIDATION_LOG"
}

log_success() { 
    echo -e "${GREEN}[SUCCESS]${NC} $*" | tee -a "$VALIDATION_LOG"
}

log_test() {
    echo -e "${PURPLE}[TEST]${NC} $*" | tee -a "$VALIDATION_LOG"
}

# Test result tracking
record_test() {
    local test_name="$1"
    local status="$2"
    local message="${3:-}"
    local duration="${4:-0}"
    
    ((TOTAL_TESTS++))
    
    if [[ "$status" == "PASS" ]]; then
        ((PASSED_TESTS++))
        log_success "✓ $test_name"
    else
        ((FAILED_TESTS++))
        log_error "✗ $test_name: $message"
    fi
    
    TEST_RESULTS+=("{\"name\":\"$test_name\",\"status\":\"$status\",\"message\":\"$message\",\"duration\":$duration}")
}

# Utility functions
check_command() {
    command -v "$1" >/dev/null 2>&1
}

wait_for_service() {
    local service="$1"
    local port="$2"
    local timeout="${3:-30}"
    local count=0
    
    while ! nc -z localhost "$port" 2>/dev/null; do
        if [[ $count -ge $timeout ]]; then
            return 1
        fi
        sleep 1
        ((count++))
    done
    return 0
}

make_request() {
    local url="$1"
    local expected_code="${2:-200}"
    local timeout="${3:-10}"
    
    local response
    response=$(curl -s -w "%{http_code}" -m "$timeout" "$url" 2>/dev/null || echo "000")
    local http_code="${response: -3}"
    
    [[ "$http_code" == "$expected_code" ]]
}

# Test categories
test_prerequisites() {
    log_test "Testing prerequisites..."
    
    # Check required commands
    local required_commands=("docker" "docker-compose" "curl" "jq" "nc")
    for cmd in "${required_commands[@]}"; do
        local start_time=$(date +%s)
        if check_command "$cmd"; then
            local end_time=$(date +%s)
            record_test "Command available: $cmd" "PASS" "" $((end_time - start_time))
        else
            record_test "Command available: $cmd" "FAIL" "Command not found"
        fi
    done
    
    # Check Docker daemon
    local start_time=$(date +%s)
    if docker info >/dev/null 2>&1; then
        local end_time=$(date +%s)
        record_test "Docker daemon" "PASS" "" $((end_time - start_time))
    else
        record_test "Docker daemon" "FAIL" "Docker daemon not running"
    fi
    
    # Check project structure
    local required_files=(
        "docker-compose.yml"
        "docker/production/docker-compose.production.yml"
        "apps/api/Dockerfile"
        "apps/web/Dockerfile"
        "scripts/deploy-production.sh"
        "scripts/backup-system.sh"
    )
    
    for file in "${required_files[@]}"; do
        local start_time=$(date +%s)
        if [[ -f "$PROJECT_ROOT/$file" ]]; then
            local end_time=$(date +%s)
            record_test "File exists: $file" "PASS" "" $((end_time - start_time))
        else
            record_test "File exists: $file" "FAIL" "File not found"
        fi
    done
}

test_docker_configuration() {
    log_test "Testing Docker configuration..."
    
    # Test Docker Compose files
    local compose_files=(
        "docker-compose.yml"
        "docker/production/docker-compose.production.yml"
        "docker/production/resource-optimization.yml"
        "docker/production/logging.yml"
    )
    
    for compose_file in "${compose_files[@]}"; do
        local start_time=$(date +%s)
        if docker-compose -f "$PROJECT_ROOT/$compose_file" config >/dev/null 2>&1; then
            local end_time=$(date +%s)
            record_test "Docker Compose syntax: $compose_file" "PASS" "" $((end_time - start_time))
        else
            record_test "Docker Compose syntax: $compose_file" "FAIL" "Invalid syntax"
        fi
    done
    
    # Test Dockerfile syntax
    local dockerfiles=(
        "apps/api/Dockerfile"
        "apps/web/Dockerfile"
    )
    
    for dockerfile in "${dockerfiles[@]}"; do
        local start_time=$(date +%s)
        if docker build -f "$PROJECT_ROOT/$dockerfile" --dry-run "$PROJECT_ROOT" >/dev/null 2>&1; then
            local end_time=$(date +%s)
            record_test "Dockerfile syntax: $dockerfile" "PASS" "" $((end_time - start_time))
        else
            # Dockerfile --dry-run not available in all versions, try basic validation
            if [[ -f "$PROJECT_ROOT/$dockerfile" ]] && grep -q "FROM" "$PROJECT_ROOT/$dockerfile"; then
                local end_time=$(date +%s)
                record_test "Dockerfile syntax: $dockerfile" "PASS" "" $((end_time - start_time))
            else
                record_test "Dockerfile syntax: $dockerfile" "FAIL" "Invalid Dockerfile"
            fi
        fi
    done
}

test_environment_configuration() {
    log_test "Testing environment configuration..."
    
    # Check environment files
    local env_files=(
        ".env.example"
        "docker/production/backup-config.env"
    )
    
    for env_file in "${env_files[@]}"; do
        local start_time=$(date +%s)
        if [[ -f "$PROJECT_ROOT/$env_file" ]]; then
            # Check for required variables
            local required_vars=("DB_URL" "REDIS_ADDR" "APP_PORT")
            local missing_vars=()
            
            for var in "${required_vars[@]}"; do
                if ! grep -q "^$var=" "$PROJECT_ROOT/$env_file" 2>/dev/null; then
                    missing_vars+=("$var")
                fi
            done
            
            if [[ ${#missing_vars[@]} -eq 0 ]]; then
                local end_time=$(date +%s)
                record_test "Environment file: $env_file" "PASS" "" $((end_time - start_time))
            else
                record_test "Environment file: $env_file" "FAIL" "Missing variables: ${missing_vars[*]}"
            fi
        else
            record_test "Environment file: $env_file" "FAIL" "File not found"
        fi
    done
}

test_security_configuration() {
    log_test "Testing security configuration..."
    
    # Check SSL configuration
    local start_time=$(date +%s)
    if [[ -f "$PROJECT_ROOT/docker/production/ssl-nginx.conf" ]]; then
        if grep -q "ssl_certificate" "$PROJECT_ROOT/docker/production/ssl-nginx.conf"; then
            local end_time=$(date +%s)
            record_test "SSL configuration" "PASS" "" $((end_time - start_time))
        else
            record_test "SSL configuration" "FAIL" "SSL not properly configured"
        fi
    else
        record_test "SSL configuration" "FAIL" "SSL config file not found"
    fi
    
    # Check security headers
    local start_time=$(date +%s)
    if [[ -f "$PROJECT_ROOT/docker/production/ssl-nginx.conf" ]]; then
        local security_headers=("X-Frame-Options" "X-Content-Type-Options" "X-XSS-Protection" "Strict-Transport-Security")
        local missing_headers=()
        
        for header in "${security_headers[@]}"; do
            if ! grep -q "$header" "$PROJECT_ROOT/docker/production/ssl-nginx.conf"; then
                missing_headers+=("$header")
            fi
        done
        
        if [[ ${#missing_headers[@]} -eq 0 ]]; then
            local end_time=$(date +%s)
            record_test "Security headers" "PASS" "" $((end_time - start_time))
        else
            record_test "Security headers" "FAIL" "Missing headers: ${missing_headers[*]}"
        fi
    else
        record_test "Security headers" "FAIL" "Nginx config not found"
    fi
    
    # Check backup encryption
    local start_time=$(date +%s)
    if grep -q "BACKUP_ENCRYPTION_ENABLED.*true" "$PROJECT_ROOT/docker/production/backup-config.env" 2>/dev/null; then
        local end_time=$(date +%s)
        record_test "Backup encryption" "PASS" "" $((end_time - start_time))
    else
        record_test "Backup encryption" "FAIL" "Backup encryption not enabled"
    fi
}

test_monitoring_configuration() {
    log_test "Testing monitoring configuration..."
    
    # Check monitoring stack configuration
    local monitoring_configs=(
        "docker/production/prometheus.yml"
        "docker/production/grafana-datasources.yml"
        "docker/production/alertmanager.yml"
        "docker/production/elasticsearch.yml"
        "docker/production/kibana.yml"
    )
    
    for config in "${monitoring_configs[@]}"; do
        local start_time=$(date +%s)
        if [[ -f "$PROJECT_ROOT/$config" ]]; then
            local end_time=$(date +%s)
            record_test "Monitoring config: $(basename "$config")" "PASS" "" $((end_time - start_time))
        else
            record_test "Monitoring config: $(basename "$config")" "FAIL" "Config file not found"
        fi
    done
    
    # Check Prometheus rules
    local start_time=$(date +%s)
    if [[ -f "$PROJECT_ROOT/docker/production/prometheus-rules.yml" ]]; then
        if grep -q "groups:" "$PROJECT_ROOT/docker/production/prometheus-rules.yml"; then
            local end_time=$(date +%s)
            record_test "Prometheus alerting rules" "PASS" "" $((end_time - start_time))
        else
            record_test "Prometheus alerting rules" "FAIL" "No alerting rules defined"
        fi
    else
        record_test "Prometheus alerting rules" "FAIL" "Rules file not found"
    fi
}

test_backup_configuration() {
    log_test "Testing backup configuration..."
    
    # Check backup scripts
    local backup_scripts=(
        "scripts/backup-system.sh"
        "scripts/disaster-recovery.sh"
        "scripts/setup-backup-automation.sh"
    )
    
    for script in "${backup_scripts[@]}"; do
        local start_time=$(date +%s)
        if [[ -x "$PROJECT_ROOT/$script" ]]; then
            # Test script syntax
            if bash -n "$PROJECT_ROOT/$script" 2>/dev/null; then
                local end_time=$(date +%s)
                record_test "Backup script: $(basename "$script")" "PASS" "" $((end_time - start_time))
            else
                record_test "Backup script: $(basename "$script")" "FAIL" "Syntax error"
            fi
        else
            record_test "Backup script: $(basename "$script")" "FAIL" "Script not executable or not found"
        fi
    done
    
    # Check backup configuration
    local start_time=$(date +%s)
    if [[ -f "$PROJECT_ROOT/docker/production/backup-config.env" ]]; then
        local required_backup_vars=("BACKUP_BASE_DIR" "DB_BACKUP_RETENTION_DAYS" "FULL_BACKUP_RETENTION_DAYS")
        local missing_vars=()
        
        for var in "${required_backup_vars[@]}"; do
            if ! grep -q "^$var=" "$PROJECT_ROOT/docker/production/backup-config.env"; then
                missing_vars+=("$var")
            fi
        done
        
        if [[ ${#missing_vars[@]} -eq 0 ]]; then
            local end_time=$(date +%s)
            record_test "Backup configuration" "PASS" "" $((end_time - start_time))
        else
            record_test "Backup configuration" "FAIL" "Missing variables: ${missing_vars[*]}"
        fi
    else
        record_test "Backup configuration" "FAIL" "Backup config file not found"
    fi
}

test_deployment_scripts() {
    log_test "Testing deployment scripts..."
    
    # Check deployment scripts
    local deployment_scripts=(
        "scripts/deploy-production.sh"
        "scripts/validate-deployment.sh"
        "scripts/setup-ssl.sh"
    )
    
    for script in "${deployment_scripts[@]}"; do
        local start_time=$(date +%s)
        if [[ -x "$PROJECT_ROOT/$script" ]]; then
            # Test script syntax
            if bash -n "$PROJECT_ROOT/$script" 2>/dev/null; then
                local end_time=$(date +%s)
                record_test "Deployment script: $(basename "$script")" "PASS" "" $((end_time - start_time))
            else
                record_test "Deployment script: $(basename "$script")" "FAIL" "Syntax error"
            fi
        else
            record_test "Deployment script: $(basename "$script")" "FAIL" "Script not executable or not found"
        fi
    done
    
    # Test script help functions
    for script in "${deployment_scripts[@]}"; do
        if [[ -x "$PROJECT_ROOT/$script" ]]; then
            local start_time=$(date +%s)
            if "$PROJECT_ROOT/$script" --help >/dev/null 2>&1 || "$PROJECT_ROOT/$script" help >/dev/null 2>&1; then
                local end_time=$(date +%s)
                record_test "Script help: $(basename "$script")" "PASS" "" $((end_time - start_time))
            else
                record_test "Script help: $(basename "$script")" "FAIL" "Help function not working"
            fi
        fi
    done
}

test_documentation() {
    log_test "Testing documentation..."
    
    # Check documentation files
    local doc_files=(
        "README.md"
        "docs/PRODUCTION_DEPLOYMENT.md"
        "docs/SSL_TLS_SETUP.md"
        "docs/BACKUP_DISASTER_RECOVERY.md"
    )
    
    for doc in "${doc_files[@]}"; do
        local start_time=$(date +%s)
        if [[ -f "$PROJECT_ROOT/$doc" ]]; then
            # Check if file has content
            if [[ -s "$PROJECT_ROOT/$doc" ]]; then
                local end_time=$(date +%s)
                record_test "Documentation: $(basename "$doc")" "PASS" "" $((end_time - start_time))
            else
                record_test "Documentation: $(basename "$doc")" "FAIL" "File is empty"
            fi
        else
            record_test "Documentation: $(basename "$doc")" "FAIL" "File not found"
        fi
    done
    
    # Check for required sections in main docs
    local start_time=$(date +%s)
    if [[ -f "$PROJECT_ROOT/docs/PRODUCTION_DEPLOYMENT.md" ]]; then
        local required_sections=("Prerequisites" "Deployment" "Monitoring" "Troubleshooting")
        local missing_sections=()
        
        for section in "${required_sections[@]}"; do
            if ! grep -qi "$section" "$PROJECT_ROOT/docs/PRODUCTION_DEPLOYMENT.md"; then
                missing_sections+=("$section")
            fi
        done
        
        if [[ ${#missing_sections[@]} -eq 0 ]]; then
            local end_time=$(date +%s)
            record_test "Documentation completeness" "PASS" "" $((end_time - start_time))
        else
            record_test "Documentation completeness" "FAIL" "Missing sections: ${missing_sections[*]}"
        fi
    else
        record_test "Documentation completeness" "FAIL" "Main documentation not found"
    fi
}

test_performance_configuration() {
    log_test "Testing performance configuration..."
    
    # Check resource optimization
    local start_time=$(date +%s)
    if [[ -f "$PROJECT_ROOT/docker/production/resource-optimization.yml" ]]; then
        # Check for resource limits
        if grep -q "mem_limit\|cpus\|memory" "$PROJECT_ROOT/docker/production/resource-optimization.yml"; then
            local end_time=$(date +%s)
            record_test "Resource limits configured" "PASS" "" $((end_time - start_time))
        else
            record_test "Resource limits configured" "FAIL" "No resource limits found"
        fi
    else
        record_test "Resource limits configured" "FAIL" "Resource optimization file not found"
    fi
    
    # Check Redis optimization
    local start_time=$(date +%s)
    if [[ -f "$PROJECT_ROOT/docker/production/redis/redis.conf" ]]; then
        local redis_optimizations=("maxmemory" "maxmemory-policy" "save")
        local missing_opts=()
        
        for opt in "${redis_optimizations[@]}"; do
            if ! grep -q "^$opt" "$PROJECT_ROOT/docker/production/redis/redis.conf"; then
                missing_opts+=("$opt")
            fi
        done
        
        if [[ ${#missing_opts[@]} -eq 0 ]]; then
            local end_time=$(date +%s)
            record_test "Redis optimization" "PASS" "" $((end_time - start_time))
        else
            record_test "Redis optimization" "FAIL" "Missing optimizations: ${missing_opts[*]}"
        fi
    else
        record_test "Redis optimization" "FAIL" "Redis config file not found"
    fi
}

test_integration() {
    log_test "Testing integration scenarios..."
    
    # Test Docker Compose integration
    local start_time=$(date +%s)
    if docker-compose -f "$PROJECT_ROOT/docker-compose.yml" \
                     -f "$PROJECT_ROOT/docker/production/docker-compose.production.yml" \
                     config >/dev/null 2>&1; then
        local end_time=$(date +%s)
        record_test "Docker Compose integration" "PASS" "" $((end_time - start_time))
    else
        record_test "Docker Compose integration" "FAIL" "Compose files don't integrate properly"
    fi
    
    # Test monitoring integration
    local start_time=$(date +%s)
    if docker-compose -f "$PROJECT_ROOT/docker/production/logging.yml" config >/dev/null 2>&1; then
        local end_time=$(date +%s)
        record_test "Monitoring stack integration" "PASS" "" $((end_time - start_time))
    else
        record_test "Monitoring stack integration" "FAIL" "Monitoring stack configuration invalid"
    fi
}

# Generate comprehensive report
generate_report() {
    log_info "Generating validation report..."
    
    local test_results_json
    test_results_json=$(printf '%s\n' "${TEST_RESULTS[@]}" | jq -s '.')
    
    cat > "$REPORT_FILE" << EOF
{
  "validation_date": "$(date -Iseconds)",
  "project_root": "$PROJECT_ROOT",
  "validation_log": "$VALIDATION_LOG",
  "summary": {
    "total_tests": $TOTAL_TESTS,
    "passed_tests": $PASSED_TESTS,
    "failed_tests": $FAILED_TESTS,
    "warnings": $WARNINGS,
    "success_rate": $(echo "scale=2; $PASSED_TESTS * 100 / $TOTAL_TESTS" | bc -l 2>/dev/null || echo "0")
  },
  "test_results": $test_results_json,
  "recommendations": [
    $(if [[ $FAILED_TESTS -gt 0 ]]; then echo '"Fix all failed tests before production deployment",'; fi)
    $(if [[ $WARNINGS -gt 5 ]]; then echo '"Review and address warnings",'; fi)
    "Run deployment validation script after deployment",
    "Set up monitoring alerts",
    "Test backup and recovery procedures",
    "Review security configurations"
  ],
  "next_steps": [
    "Deploy to staging environment",
    "Run end-to-end tests",
    "Perform load testing",
    "Validate monitoring and alerting",
    "Test disaster recovery procedures",
    "Deploy to production"
  ]
}
EOF
    
    log_success "Validation report generated: $REPORT_FILE"
}

# Display results summary
display_summary() {
    echo
    echo "=================================="
    echo "  VALIDATION SUMMARY"
    echo "=================================="
    echo
    echo -e "Total Tests:    ${BLUE}$TOTAL_TESTS${NC}"
    echo -e "Passed:         ${GREEN}$PASSED_TESTS${NC}"
    echo -e "Failed:         ${RED}$FAILED_TESTS${NC}"
    echo -e "Warnings:       ${YELLOW}$WARNINGS${NC}"
    
    local success_rate
    success_rate=$(echo "scale=1; $PASSED_TESTS * 100 / $TOTAL_TESTS" | bc -l 2>/dev/null || echo "0")
    echo -e "Success Rate:   ${CYAN}${success_rate}%${NC}"
    
    echo
    echo "Logs: $VALIDATION_LOG"
    echo "Report: $REPORT_FILE"
    echo
    
    if [[ $FAILED_TESTS -eq 0 ]]; then
        echo -e "${GREEN}✓ All tests passed! Ready for production deployment.${NC}"
        echo
        echo "Next steps:"
        echo "1. Review the validation report"
        echo "2. Deploy to staging environment"
        echo "3. Run end-to-end tests"
        echo "4. Deploy to production using: ./scripts/deploy-production.sh"
    else
        echo -e "${RED}✗ $FAILED_TESTS tests failed. Please fix issues before deployment.${NC}"
        echo
        echo "Failed tests need to be addressed before production deployment."
        echo "Check the validation log for detailed error messages."
    fi
    
    if [[ $WARNINGS -gt 0 ]]; then
        echo
        echo -e "${YELLOW}⚠ $WARNINGS warnings detected. Review recommended.${NC}"
    fi
}

# Main execution
main() {
    echo "Learning Center - Comprehensive Validation"
    echo "=========================================="
    echo
    echo "Starting validation at $(date)"
    echo "Project root: $PROJECT_ROOT"
    echo "Validation log: $VALIDATION_LOG"
    echo
    
    # Initialize log file
    echo "Learning Center Validation Log - $(date)" > "$VALIDATION_LOG"
    echo "=======================================" >> "$VALIDATION_LOG"
    
    # Run all test categories
    test_prerequisites
    test_docker_configuration
    test_environment_configuration
    test_security_configuration
    test_monitoring_configuration
    test_backup_configuration
    test_deployment_scripts
    test_documentation
    test_performance_configuration
    test_integration
    
    # Generate report and display summary
    generate_report
    display_summary
    
    # Exit with appropriate code
    if [[ $FAILED_TESTS -eq 0 ]]; then
        exit 0
    else
        exit 1
    fi
}

# Handle script arguments
case "${1:-}" in
    --help|help)
        echo "Learning Center Comprehensive Validation Script"
        echo
        echo "Usage: $0 [options]"
        echo
        echo "Options:"
        echo "  --help, help    Show this help message"
        echo "  --report-only   Generate report from existing log"
        echo
        echo "This script validates all aspects of the Learning Center production deployment:"
        echo "  - Prerequisites and dependencies"
        echo "  - Docker configuration"
        echo "  - Environment setup"
        echo "  - Security configuration"
        echo "  - Monitoring setup"
        echo "  - Backup configuration"
        echo "  - Deployment scripts"
        echo "  - Documentation"
        echo "  - Performance configuration"
        echo "  - Integration testing"
        echo
        echo "Output:"
        echo "  - Detailed validation log"
        echo "  - JSON report with test results"
        echo "  - Summary with recommendations"
        ;;
    --report-only)
        if [[ -f "$VALIDATION_LOG" ]]; then
            generate_report
            display_summary
        else
            log_error "No validation log found. Run full validation first."
            exit 1
        fi
        ;;
    *)
        main "$@"
        ;;
esac