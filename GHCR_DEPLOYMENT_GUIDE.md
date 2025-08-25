# GitHub Container Registry (GHCR) Deployment Guide

This guide explains how to use the GHCR upload feature in the production deployment script to publish Docker images to GitHub Container Registry.

## Overview

The `deploy-production.sh` script now includes functionality to automatically build, tag, and push Docker images to GitHub Container Registry (GHCR), making it easy to distribute and deploy your Laravel application across different environments.

## Prerequisites

### 1. GitHub Personal Access Token

Create a GitHub Personal Access Token with the following permissions:
- `write:packages` - To push images to GHCR
- `read:packages` - To pull images from GHCR
- `delete:packages` (optional) - To manage image lifecycle

**Steps to create token:**
1. Go to GitHub Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Click "Generate new token (classic)"
3. Select the required scopes above
4. Copy the token securely

### 2. Environment Variables

Set the following environment variables:

```bash
# Required for GHCR upload
export GITHUB_TOKEN="your_github_token_here"
export GITHUB_REPOSITORY_OWNER="your_github_username_or_org"

# Application configuration
export APP_NAME="learningcenter"
export IMAGE_NAME="learningcenter"
```

## Usage

### Manual Deployment with GHCR Upload

```bash
# Deploy and push to GHCR
./scripts/deploy-production.sh v1.0.0 --push-to-ghcr

# Deploy without GHCR upload
./scripts/deploy-production.sh v1.0.0
```

### Environment Variables for Production

```bash
# .env.production or CI/CD environment
GITHUB_TOKEN=ghp_xxxxxxxxxxxxxxxxxxxx
GITHUB_REPOSITORY_OWNER=your-username
APP_NAME=learningcenter
IMAGE_NAME=learningcenter

# Database (external PostgreSQL cluster)
DB_HOST=your-postgres-cluster.example.com
DB_PORT=5432
DB_DATABASE=learningcenter_production
DB_USERNAME=app_user
DB_PASSWORD=secure_password
DB_SSLMODE=require

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=redis_password
REDIS_PORT=6379

# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=base64:generated_key_here
```

## CI/CD Integration

### GitHub Actions Workflow

The included `.github/workflows/deploy-production.yml` demonstrates how to integrate GHCR uploads in your CI/CD pipeline:

```yaml
# Triggered on version tags or manual dispatch
on:
  push:
    tags:
      - 'v*.*.*'
  workflow_dispatch:
    inputs:
      push_to_ghcr:
        type: boolean
        default: true
```

### Required GitHub Secrets

Configure these secrets in your GitHub repository:

```
# Application
APP_URL=https://your-domain.com

# Database
DB_HOST=your-postgres-cluster.example.com
DB_PORT=5432
DB_DATABASE=learningcenter_production
DB_USERNAME=app_user
DB_PASSWORD=secure_password
DB_SSLMODE=require

# Redis
REDIS_PASSWORD=redis_password
```

**Note:** `GITHUB_TOKEN` is automatically provided by GitHub Actions.

## Image Management

### Image Naming Convention

Images are tagged with the following format:
```
ghcr.io/{owner}/{repository}:{version}
```

Example:
```
ghcr.io/your-username/learningcenter:v1.0.0
ghcr.io/your-username/learningcenter:latest
```

### Pulling Images

```bash
# Login to GHCR
echo $GITHUB_TOKEN | docker login ghcr.io -u your-username --password-stdin

# Pull specific version
docker pull ghcr.io/your-username/learningcenter:v1.0.0

# Pull latest
docker pull ghcr.io/your-username/learningcenter:latest
```

### Image Visibility

By default, GHCR images are private. To make them public:
1. Go to your GitHub repository
2. Navigate to Packages tab
3. Select your package
4. Go to Package settings
5. Change visibility to Public

## Deployment Scenarios

### Scenario 1: Development to Staging

```bash
# Build and push development image
./scripts/deploy-production.sh dev-$(date +%Y%m%d) --push-to-ghcr

# Deploy to staging from GHCR
docker pull ghcr.io/your-username/learningcenter:dev-20240115
docker-compose -f docker-compose.staging.yml up -d
```

### Scenario 2: Production Release

```bash
# Tag and release
git tag v1.0.0
git push origin v1.0.0

# GitHub Actions automatically:
# 1. Builds the image
# 2. Pushes to GHCR
# 3. Deploys to production
```

### Scenario 3: Hotfix Deployment

```bash
# Quick hotfix deployment
./scripts/deploy-production.sh hotfix-$(date +%H%M) --push-to-ghcr

# Rollback if needed
docker pull ghcr.io/your-username/learningcenter:v1.0.0
docker-compose up -d
```

## Security Best Practices

### 1. Token Management
- Use environment-specific tokens
- Rotate tokens regularly
- Never commit tokens to version control
- Use GitHub Actions secrets for CI/CD

### 2. Image Security
- Scan images with Trivy (included in deployment script)
- Use minimal base images
- Keep dependencies updated
- Remove development tools from production images

### 3. Access Control
- Limit package permissions
- Use organization-level access controls
- Monitor package downloads
- Enable audit logging

## Troubleshooting

### Common Issues

**1. Authentication Failed**
```bash
# Verify token permissions
echo $GITHUB_TOKEN | docker login ghcr.io -u your-username --password-stdin

# Check token scopes
curl -H "Authorization: token $GITHUB_TOKEN" https://api.github.com/user
```

**2. Image Push Failed**
```bash
# Check repository name
echo $GITHUB_REPOSITORY_OWNER
echo $IMAGE_NAME

# Verify image exists locally
docker images | grep learningcenter
```

**3. Package Not Found**
```bash
# Check package visibility
# Go to GitHub → Your Repository → Packages

# Verify image tag
docker pull ghcr.io/your-username/learningcenter:latest
```

### Debug Mode

Enable debug output in the deployment script:
```bash
# Add debug flag
DEBUG=1 ./scripts/deploy-production.sh v1.0.0 --push-to-ghcr
```

## Monitoring and Maintenance

### Image Cleanup

```bash
# List all images
gh api /user/packages/container/learningcenter/versions

# Delete old versions (requires delete:packages permission)
gh api --method DELETE /user/packages/container/learningcenter/versions/VERSION_ID
```

### Storage Monitoring

- Monitor package storage usage in GitHub billing
- Set up alerts for storage limits
- Implement automated cleanup policies

### Performance Optimization

- Use multi-stage builds to reduce image size
- Leverage Docker layer caching
- Optimize Dockerfile for faster builds
- Use .dockerignore to exclude unnecessary files

## Integration with External Services

### Kubernetes Deployment

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: learningcenter
spec:
  template:
    spec:
      containers:
      - name: app
        image: ghcr.io/your-username/learningcenter:v1.0.0
        imagePullPolicy: Always
      imagePullSecrets:
      - name: ghcr-secret
```

### Docker Swarm

```yaml
version: '3.8'
services:
  app:
    image: ghcr.io/your-username/learningcenter:v1.0.0
    deploy:
      replicas: 3
      update_config:
        parallelism: 1
        delay: 10s
```

## Next Steps

1. **Set up GitHub secrets** for your repository
2. **Test the deployment script** with GHCR upload
3. **Configure automated workflows** for different environments
4. **Implement image scanning** and security policies
5. **Set up monitoring** for package usage and performance
6. **Document your specific deployment procedures** for your team

## Support

For issues related to:
- **GitHub Container Registry**: [GitHub Support](https://support.github.com/)
- **Docker**: [Docker Documentation](https://docs.docker.com/)
- **Laravel Deployment**: Check the `DOCKERFILE_VALIDATION_REPORT.md` and `EXTERNAL_POSTGRESQL_DEPLOYMENT.md`