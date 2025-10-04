#!/bin/bash

# Fix Composer Synchronization Issues
# This script resolves composer.json and composer.lock synchronization problems
# following best practices from https://getcomposer.org/doc/articles/resolving-merge-conflicts.md

set -e

echo "🔧 Laravel Learning Center - Composer Synchronization Fix"
echo "========================================================"

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

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    print_error "composer.json not found. Please run this script from the project root."
    exit 1
fi

# Backup current composer.lock
if [ -f "composer.lock" ]; then
    print_status "Creating backup of current composer.lock..."
    cp composer.lock composer.lock.backup.$(date +%Y%m%d_%H%M%S)
    print_success "Backup created"
fi

# Check if composer is available
if ! command -v composer &> /dev/null; then
    print_error "Composer is not installed or not in PATH"
    print_status "Please install Composer first: https://getcomposer.org/download/"
    exit 1
fi

# Validate composer.json syntax
print_status "Validating composer.json syntax..."
if ! composer validate --no-check-lock --quiet; then
    print_error "composer.json has syntax errors. Please fix them first."
    composer validate --no-check-lock
    exit 1
fi
print_success "composer.json syntax is valid"

# Check for lock file synchronization issues
print_status "Checking composer.lock synchronization..."
if ! composer validate --quiet; then
    print_warning "composer.lock is not synchronized with composer.json"
    
    # Show what packages are missing or outdated
    print_status "Analyzing synchronization issues..."
    composer validate
    
    # Option 1: Try to install missing packages first
    print_status "Attempting to resolve missing packages..."
    
    # Check specifically for laravel/horizon
    if grep -q '"laravel/horizon"' composer.json; then
        if ! grep -q '"laravel/horizon"' composer.lock 2>/dev/null; then
            print_warning "laravel/horizon is missing from composer.lock"
            print_status "Re-requiring laravel/horizon to ensure proper installation..."
            
            # Extract the version constraint from composer.json
            HORIZON_VERSION=$(grep '"laravel/horizon"' composer.json | sed 's/.*"laravel\/horizon": *"\([^"]*\)".*/\1/')
            print_status "Found laravel/horizon version constraint: $HORIZON_VERSION"
            
            # Re-require the package to ensure it's properly locked
            composer require "laravel/horizon:$HORIZON_VERSION" --no-update
        fi
    fi
    
    # Option 2: Update the lock file to match composer.json
    print_status "Updating composer.lock to match composer.json..."
    composer update --lock
    
    # Verify the fix
    print_status "Verifying synchronization after update..."
    if composer validate --quiet; then
        print_success "composer.lock is now synchronized with composer.json"
    else
        print_warning "Some issues may remain. Running full dependency resolution..."
        
        # Option 3: Full update if needed (with confirmation)
        read -p "Do you want to run 'composer update' to fully resolve dependencies? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            print_status "Running full composer update..."
            composer update
            print_success "Full update completed"
        else
            print_warning "Skipping full update. Manual intervention may be required."
        fi
    fi
else
    print_success "composer.lock is already synchronized with composer.json"
fi

# Final validation
print_status "Performing final validation..."
if composer validate; then
    print_success "All composer files are valid and synchronized"
else
    print_error "Validation failed. Manual intervention required."
    exit 1
fi

# Check for security vulnerabilities
print_status "Checking for security vulnerabilities..."
if composer audit --quiet; then
    print_success "No security vulnerabilities found"
else
    print_warning "Security vulnerabilities detected. Run 'composer audit' for details."
fi

# Summary
echo ""
echo "🎉 Composer Synchronization Fix Complete!"
echo "=========================================="
print_success "composer.json and composer.lock are now synchronized"
print_status "Next steps for GitHub Actions:"
print_status "1. Commit the updated composer.lock file"
print_status "2. Push changes to trigger the workflow"
print_status "3. The --no-suggest flag has been removed from the workflow"

# Show what changed
if [ -f "composer.lock.backup."* ]; then
    echo ""
    print_status "To see what changed in composer.lock:"
    echo "git diff composer.lock.backup.* composer.lock"
fi

echo ""
print_status "For more information about composer merge conflicts:"
echo "https://getcomposer.org/doc/articles/resolving-merge-conflicts.md"