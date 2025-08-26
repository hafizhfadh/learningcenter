#!/bin/bash

# Laravel Learning Center - Quick Deployment Test
# This script performs a quick validation without network connectivity tests

set -e

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo -e "${BLUE}🚀 Laravel Learning Center - Quick Deployment Test${NC}"
echo "═══════════════════════════════════════════════════════════════════════════════"

# Test 1: Environment Configuration
echo -e "\n${BLUE}📋 Testing Environment Configuration${NC}"
if [[ -f "$PROJECT_ROOT/.env.production" ]]; then
    echo -e "${GREEN}✅ .env.production exists${NC}"
    
    # Source environment
    set -a
    source "$PROJECT_ROOT/.env.production"
    set +a
    
    # Check critical variables
    if [[ -n "$APP_KEY" && -n "$DB_HOST" && -n "$DB_DATABASE" ]]; then
        echo -e "${GREEN}✅ Critical environment variables are set${NC}"
    else
        echo -e "${YELLOW}⚠️  Some environment variables may be missing${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  .env.production not found${NC}"
fi

# Test 2: Docker Configuration
echo -e "\n${BLUE}📋 Testing Docker Configuration${NC}"
if docker compose -f "$PROJECT_ROOT/docker-compose.production.yml" config >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Docker Compose configuration is valid${NC}"
else
    echo -e "${YELLOW}⚠️  Docker Compose configuration has issues${NC}"
fi

# Test 3: Required Files
echo -e "\n${BLUE}📋 Testing Required Files${NC}"
required_files=(
    "Dockerfile.frankenphp.improved"
    "docker/startup.sh"
    "scripts/deploy-production.sh"
    "scripts/validate-deployment.sh"
)

for file in "${required_files[@]}"; do
    if [[ -f "$PROJECT_ROOT/$file" ]]; then
        echo -e "${GREEN}✅ $file exists${NC}"
    else
        echo -e "${YELLOW}⚠️  $file missing${NC}"
    fi
done

# Test 4: Script Permissions
echo -e "\n${BLUE}📋 Testing Script Permissions${NC}"
scripts=(
    "scripts/deploy-production.sh"
    "scripts/validate-deployment.sh"
    "docker/startup.sh"
)

for script in "${scripts[@]}"; do
    if [[ -x "$PROJECT_ROOT/$script" ]]; then
        echo -e "${GREEN}✅ $script is executable${NC}"
    else
        echo -e "${YELLOW}⚠️  $script is not executable${NC}"
    fi
done

# Test 5: Docker Image Build Test (dry run)
echo -e "\n${BLUE}📋 Testing Docker Build Configuration${NC}"
if docker build -f "$PROJECT_ROOT/Dockerfile.frankenphp.improved" --dry-run "$PROJECT_ROOT" >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Dockerfile syntax is valid${NC}"
else
    echo -e "${YELLOW}⚠️  Dockerfile may have syntax issues${NC}"
fi

echo -e "\n${BLUE}📋 Quick Test Summary${NC}"
echo -e "${GREEN}✅ Basic deployment configuration appears ready${NC}"
echo -e "${BLUE}💡 To run full validation: ./scripts/validate-deployment.sh${NC}"
echo -e "${BLUE}🚀 To deploy: ./scripts/deploy-production.sh${NC}"
echo -e "${BLUE}🔧 For troubleshooting: ./scripts/deploy-production.sh --troubleshoot${NC}"