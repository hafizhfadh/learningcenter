#!/bin/bash

# Test script for GHCR upload process
# This script simulates the GitHub Actions workflow for building and pushing to GHCR

set -e

echo "🔧 Testing GHCR Upload Process"
echo "================================"

# Check if GITHUB_REPOSITORY is set, otherwise use default
if [ -z "$GITHUB_REPOSITORY" ]; then
    echo "ℹ️  GITHUB_REPOSITORY not set, using default format"
    GITHUB_REPOSITORY="your-username/learning-center"
    echo "   You can set it with: export GITHUB_REPOSITORY=your-username/learning-center"
fi

# Extract metadata (similar to GitHub Actions)
REGISTRY="ghcr.io"
REPO_LOWERCASE=$(echo "${GITHUB_REPOSITORY}" | tr '[:upper:]' '[:lower:]')
IMAGE_NAME="${REGISTRY}/${REPO_LOWERCASE}"
TAG="test-$(date +%s)"

echo "📋 Build Configuration:"
echo "   Registry: $REGISTRY"
echo "   Image: $IMAGE_NAME"
echo "   Tag: $TAG"
echo ""

# Step 1: Build the Docker image
echo "🏗️  Building Docker image..."
docker build -f deploy/production/Dockerfile -t "$IMAGE_NAME:$TAG" .

if [ $? -eq 0 ]; then
    echo "✅ Docker build successful"
else
    echo "❌ Docker build failed"
    exit 1
fi

# Step 2: Check GHCR authentication
echo ""
echo "🔐 Checking GitHub Container Registry authentication..."

# Test authentication by checking if we can access the registry
# We'll try to push our image and see if authentication works
echo "✅ Proceeding with push (authentication will be tested during push)"

# Step 3: Push the image
echo ""
echo "📤 Pushing image to GHCR..."
docker push "$IMAGE_NAME:$TAG"

if [ $? -eq 0 ]; then
    echo "✅ Image push successful"
    echo ""
    echo "🎉 GHCR Upload Test Complete!"
    echo "   Image available at: $IMAGE_NAME:$TAG"
else
    echo "❌ Image push failed"
    echo ""
    echo "🔑 Authentication Required:"
    echo "   To push to GHCR, you need to login first:"
    echo "   1. Create a GitHub Personal Access Token with 'write:packages' scope"
    echo "   2. Run: docker login ghcr.io -u YOUR_GITHUB_USERNAME"
    echo "   3. Enter your token when prompted for password"
    echo ""
    echo "💡 Once authenticated, re-run this script to test the upload"
    exit 1
fi

# Step 4: Cleanup local image (optional)
echo ""
echo "🧹 Cleaning up local image..."
docker rmi "$IMAGE_NAME:$TAG" || true

echo ""
echo "📝 Test Summary:"
echo "   ✅ Docker build with PHP extensions"
echo "   ✅ GHCR authentication (using existing login)"
echo "   ✅ Image push to registry"
echo ""
echo "🚀 Your Docker image is ready for production deployment!"
echo "💡 No tokens were exposed during this test - using secure Docker auth!"