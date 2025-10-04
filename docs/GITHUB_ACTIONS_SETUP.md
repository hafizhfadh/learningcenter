# GitHub Actions Setup Guide

This document outlines the setup requirements for the Laravel Learning Center CI/CD pipeline using GitHub Actions.

## 🔧 Required GitHub Secrets

Configure the following secrets in your GitHub repository settings (`Settings > Secrets and variables > Actions`):

### **Production Deployment Secrets**
```
PRODUCTION_HOST          # Production server IP/hostname
PRODUCTION_USER          # SSH username for production server
PRODUCTION_SSH_KEY       # Private SSH key for production access
```

### **Staging Deployment Secrets**
```
STAGING_HOST             # Staging server IP/hostname
STAGING_USER             # SSH username for staging server
STAGING_SSH_KEY          # Private SSH key for staging access
```

### **Notification Secrets (Optional)**
```
SLACK_WEBHOOK_URL        # Slack webhook for deployment notifications
```

## 🚀 Workflow Overview

The GitHub Actions workflow (`build-and-deploy.yml`) includes:

### **1. Test Job**
- **PHP Setup**: PHP 8.2 with required extensions
- **Database**: PostgreSQL 17 + Redis 7
- **Dependencies**: Composer + NPM packages
- **Testing**: PHPUnit tests with coverage
- **Security**: Composer audit + NPM audit
- **Static Analysis**: PHPStan analysis

### **2. Build Job**
- **Docker Build**: Multi-platform (amd64/arm64)
- **Registry**: GitHub Container Registry (ghcr.io)
- **Security**: SBOM generation
- **Caching**: GitHub Actions cache

### **3. Security Scan Job**
- **Vulnerability Scanning**: Trivy scanner
- **SARIF Upload**: GitHub Security tab integration

### **4. Deploy Staging Job**
- **Trigger**: Push to `develop` branch
- **Method**: SSH deployment with Docker Compose
- **Health Check**: Automated endpoint verification

### **5. Deploy Production Job**
- **Trigger**: Git tags starting with `v*`
- **Method**: Blue-green deployment via SSH
- **Health Check**: Automated endpoint verification
- **Release**: Automatic GitHub release creation

### **6. Notification Job**
- **Slack Integration**: Deployment status notifications
- **Status Tracking**: Success/failure reporting

## 🔐 SSH Key Setup

### **Generate SSH Keys**
```bash
# Generate SSH key pair for production
ssh-keygen -t ed25519 -C "github-actions-production" -f ~/.ssh/github_actions_prod

# Generate SSH key pair for staging
ssh-keygen -t ed25519 -C "github-actions-staging" -f ~/.ssh/github_actions_staging
```

### **Add Public Keys to Servers**
```bash
# Copy public key to production server
ssh-copy-id -i ~/.ssh/github_actions_prod.pub user@production-server

# Copy public key to staging server
ssh-copy-id -i ~/.ssh/github_actions_staging.pub user@staging-server
```

### **Add Private Keys to GitHub Secrets**
```bash
# Copy private key content for GitHub secrets
cat ~/.ssh/github_actions_prod     # Add to PRODUCTION_SSH_KEY
cat ~/.ssh/github_actions_staging  # Add to STAGING_SSH_KEY
```

## 🏗️ Server Prerequisites

### **Directory Structure**
```bash
/var/www/learning-center/
├── docker-compose.production.yml
├── .env.production
├── scripts/
│   └── blue-green-deploy.sh
└── storage/
    ├── app/
    ├── logs/
    └── framework/
```

### **Required Scripts**
Ensure the following scripts exist and are executable:
- `scripts/blue-green-deploy.sh` - Blue-green deployment logic
- `scripts/health-check.sh` - Health check validation

### **Docker Setup**
```bash
# Install Docker and Docker Compose on servers
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

## 🌐 Environment Configuration

### **Production Environment Variables**
Create `.env.production` on production server:
```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-app-key-here
APP_URL=https://learning-center.example.com

DB_CONNECTION=pgsql
DB_HOST=your-postgres-host
DB_PORT=5432
DB_DATABASE=learning_center_prod
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=your-redis-password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-user
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
```

### **Staging Environment Variables**
Create `.env.staging` on staging server with similar configuration but staging-specific values.

## 🔍 Health Check Endpoints

Ensure your Laravel application provides health check endpoints:

### **Basic Health Check**
```php
// routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'version' => config('app.version', '1.0.0'),
    ]);
});
```

### **Detailed Health Check**
```php
// routes/web.php
Route::get('/health/detailed', function () {
    return response()->json([
        'status' => 'ok',
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'redis' => Redis::ping() ? 'connected' : 'disconnected',
        'storage' => Storage::disk('local')->exists('test') ? 'writable' : 'readonly',
        'timestamp' => now(),
    ]);
});
```

## 🚨 Troubleshooting

### **Common Issues**

1. **SSH Connection Failed**
   - Verify SSH key is correctly added to GitHub secrets
   - Ensure public key is in server's `~/.ssh/authorized_keys`
   - Check server firewall allows SSH connections

2. **Docker Build Failed**
   - Verify Dockerfile path: `./docker/production/Dockerfile`
   - Check Docker build context includes all required files
   - Review build logs for missing dependencies

3. **Health Check Failed**
   - Verify health check endpoint returns 200 status
   - Check application logs for startup errors
   - Ensure database and Redis connections are working

4. **Deployment Timeout**
   - Increase health check wait times
   - Verify server resources (CPU, memory, disk)
   - Check Docker container startup logs

### **Debug Commands**
```bash
# Check workflow logs in GitHub Actions tab
# SSH into server to debug deployment
ssh user@server-host

# Check Docker containers
docker ps -a
docker logs learning-center-app

# Check application logs
tail -f /var/www/learning-center/storage/logs/laravel.log

# Test health endpoint
curl -f https://your-domain.com/health
```

## 📋 Deployment Checklist

Before enabling the workflow:

- [ ] All GitHub secrets configured
- [ ] SSH keys generated and deployed
- [ ] Server prerequisites installed
- [ ] Environment files configured
- [ ] Health check endpoints implemented
- [ ] Blue-green deployment script ready
- [ ] Backup procedures in place
- [ ] Monitoring and alerting configured

## 🔄 Workflow Triggers

- **Pull Request**: Runs tests only
- **Push to `develop`**: Tests + Build + Deploy to Staging
- **Push to `main`**: Tests + Build (no deployment)
- **Tag `v*`**: Tests + Build + Deploy to Production

## 📊 Monitoring

The workflow provides:
- **Test Coverage**: Uploaded to Codecov
- **Security Scanning**: Results in GitHub Security tab
- **SBOM**: Software Bill of Materials artifacts
- **Deployment Status**: Slack notifications
- **Release Notes**: Automatic GitHub releases

## 🔒 Security Considerations

- SSH keys are environment-specific and rotated regularly
- Container images are scanned for vulnerabilities
- Dependencies are audited for security issues
- Secrets are never logged or exposed
- Production deployments require manual approval via GitHub environments