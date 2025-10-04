#!/bin/bash

# Laravel Learning Center - Docker-based Composer Synchronization Fix
# This script uses Docker to fix Composer synchronization issues when Composer is not available locally

set -e

echo "🔧 Laravel Learning Center - Docker-based Composer Synchronization Fix"
echo "======================================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if Docker is available
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed or not in PATH"
    print_error "Please install Docker first: https://docs.docker.com/get-docker/"
    exit 1
fi

# Check if Docker is running
if ! docker info &> /dev/null; then
    print_error "Docker is not running. Please start Docker and try again."
    exit 1
fi

print_success "Docker is available and running"

# Create backup of current composer.lock
print_status "Creating backup of current composer.lock..."
if [ -f "composer.lock" ]; then
    cp composer.lock "composer.lock.backup.$(date +%Y%m%d_%H%M%S)"
    print_success "Backup created"
else
    print_warning "No composer.lock file found to backup"
fi

# Function to run composer commands in Docker
run_composer() {
    docker run --rm \
        -v "$(pwd):/app" \
        -w /app \
        composer:2.7 \
        composer "$@"
}

# Validate composer.json syntax
print_status "Validating composer.json syntax..."
if run_composer validate --no-check-lock; then
    print_success "composer.json syntax is valid"
else
    print_error "composer.json has syntax errors"
    exit 1
fi

# Check lock file synchronization
print_status "Checking composer.lock synchronization..."
if run_composer validate --quiet; then
    print_success "composer.json and composer.lock are synchronized"
    print_status "Running security audit..."
    run_composer audit || print_warning "Security audit completed with warnings"
    print_success "All checks passed!"
    exit 0
else
    print_warning "composer.lock is not synchronized with composer.json"
fi

# Show detailed validation output
print_status "Detailed validation output:"
run_composer validate

# Check for specific missing packages
print_status "Checking for missing laravel/horizon package..."
if grep -q "laravel/horizon" composer.json; then
    if run_composer show laravel/horizon &> /dev/null; then
        print_success "laravel/horizon is properly installed"
    else
        print_warning "laravel/horizon is in composer.json but not properly locked"
        print_status "Re-requiring laravel/horizon..."
        
        # Extract version constraint from composer.json
        HORIZON_VERSION=$(grep -A 1 "laravel/horizon" composer.json | grep -o '"[^"]*"' | tail -1 | tr -d '"')
        if [ -n "$HORIZON_VERSION" ]; then
            print_status "Re-requiring laravel/horizon:$HORIZON_VERSION..."
            run_composer require "laravel/horizon:$HORIZON_VERSION"
        else
            print_status "Re-requiring laravel/horizon with latest compatible version..."
            run_composer require laravel/horizon
        fi
        print_success "laravel/horizon re-required successfully"
    fi
fi

# Try to fix synchronization issues
print_status "Attempting to fix synchronization issues..."

# Option 1: Try updating lock file only
print_status "Trying to update lock file only..."
if run_composer update --lock; then
    print_success "Lock file updated successfully"
    
    # Verify the fix
    if run_composer validate --quiet; then
        print_success "Synchronization issues resolved!"
    else
        print_warning "Lock file update didn't fully resolve issues"
        
        # Option 2: Try full update
        print_status "Attempting full composer update..."
        if run_composer update; then
            print_success "Full update completed successfully"
        else
            print_error "Full update failed"
            exit 1
        fi
    fi
else
    print_error "Failed to update lock file"
    exit 1
fi

# Final validation
print_status "Performing final validation..."
if run_composer validate; then
    print_success "All Composer files are now valid and synchronized"
else
    print_error "Validation still failing after fixes"
    exit 1
fi

# Security audit
print_status "Running security audit..."
if run_composer audit; then
    print_success "Security audit passed"
else
    print_warning "Security audit found issues - please review"
fi

# Install dependencies to verify everything works
print_status "Testing dependency installation..."
if run_composer install --prefer-dist --no-progress --optimize-autoloader; then
    print_success "Dependencies installed successfully"
else
    print_error "Dependency installation failed"
    exit 1
fi

print_success "🎉 Composer synchronization fix completed successfully!"
print_status "Summary of actions taken:"
echo "  ✅ Validated composer.json syntax"
echo "  ✅ Fixed composer.lock synchronization"
echo "  ✅ Re-required missing packages (if any)"
echo "  ✅ Updated lock file"
echo "  ✅ Performed security audit"
echo "  ✅ Verified dependency installation"
echo ""
print_status "You can now commit the updated composer.lock file:"
echo "  git add composer.lock"
echo "  git commit -m 'Fix composer.lock synchronization'"