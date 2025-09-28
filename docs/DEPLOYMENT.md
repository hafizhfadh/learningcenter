# Deployment Guide

This guide provides detailed instructions for deploying the Laravel application to production environments.

## Table of Contents

- [Deployment Overview](#deployment-overview)
- [Pre-deployment Checklist](#pre-deployment-checklist)
- [Environment Setup](#environment-setup)
- [Standard Deployment](#standard-deployment)
- [Blue-Green Deployment](#blue-green-deployment)
- [Rollback Procedures](#rollback-procedures)
- [Post-deployment Verification](#post-deployment-verification)
- [Automated Deployment](#automated-deployment)
- [Troubleshooting](#troubleshooting)

## Deployment Overview

The application supports multiple deployment strategies:

1. **Standard Deployment**: Direct deployment with brief downtime
2. **Blue-Green Deployment**: Zero-downtime deployment using parallel environments
3. **Rolling Deployment**: Gradual replacement of containers (future enhancement)

### Deployment Architecture

```
Development → Staging → Production
     ↓           ↓          ↓
   Testing → Integration → Live Traffic
```

## Pre-deployment Checklist

### Code Quality Checks

- [ ] All tests pass (`php artisan test`)
- [ ] Code style checks pass (`./vendor/bin/pint`)
- [ ] Static analysis passes (`./vendor/bin/phpstan analyse`)
- [ ] Security scan completed (`composer audit`)
- [ ] Dependencies updated and tested

### Infrastructure Checks

- [ ] Server resources sufficient (CPU, RAM, Disk)
- [ ] Database backup completed
- [ ] SSL certificates valid
- [ ] Monitoring systems operational
- [ ] Backup systems functional

### Configuration Verification

- [ ] Environment variables configured
- [ ] Database migrations reviewed
- [ ] Queue workers configured
- [ ] Cache settings optimized
- [ ] Log rotation configured

## Environment Setup

### 1. Server Preparation

```bash
# Connect to production server
ssh user@production-server

# Navigate to application directory
cd /opt/laravel-app

# Ensure proper permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 2. Environment Configuration

```bash
# Copy production environment file
cp .env.production.example .env.production

# Edit environment variables
nano .env.production
```

#### Critical Environment Variables

```bash
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secure_password

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=redis_password
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password

# AWS S3 (if using)
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_bucket_name

# Monitoring
SENTRY_LARAVEL_DSN=your_sentry_dsn
```

### 3. SSL Certificate Setup

```bash
# For Caddy (automatic SSL)
# Ensure domain points to server IP
# Caddy will automatically obtain Let's Encrypt certificates

# For manual SSL setup
sudo mkdir -p /etc/ssl/certs /etc/ssl/private
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/nginx-selfsigned.key \
    -out /etc/ssl/certs/nginx-selfsigned.crt
```

## Standard Deployment

### 1. Pull Latest Code

```bash
# Backup current version
git tag backup-$(date +%Y%m%d_%H%M%S)

# Pull latest changes
git fetch origin
git checkout main
git pull origin main
```

### 2. Build and Deploy

```bash
# Using deployment script
./deploy.sh deploy

# Or manual deployment
docker-compose -f docker-compose.production.yml down
docker-compose -f docker-compose.production.yml build --no-cache
docker-compose -f docker-compose.production.yml up -d
```

### 3. Run Migrations and Optimizations

```bash
# Run database migrations
docker-compose exec app php artisan migrate --force

# Clear and cache configurations
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Optimize autoloader
docker-compose exec app composer dump-autoload --optimize
```

### 4. Restart Services

```bash
# Restart queue workers
docker-compose exec app php artisan queue:restart

# Restart Horizon (if using)
docker-compose exec app php artisan horizon:terminate
docker-compose restart horizon
```

## Blue-Green Deployment

### 1. Prepare Blue-Green Environment

```bash
# Make script executable
chmod +x blue-green-deploy.sh

# Check current environment
./blue-green-deploy.sh status
```

### 2. Deploy to Inactive Environment

```bash
# Deploy new version to inactive environment
./blue-green-deploy.sh deploy

# Monitor deployment progress
docker-compose -f docker-compose.blue.yml logs -f app
# or
docker-compose -f docker-compose.green.yml logs -f app
```

### 3. Verify New Deployment

```bash
# Check health of new environment
curl -f http://localhost:8081/health  # Blue environment
# or
curl -f http://localhost:8082/health  # Green environment

# Run application tests against new environment
./blue-green-deploy.sh test
```

### 4. Switch Traffic

```bash
# Switch traffic to new environment
./blue-green-deploy.sh switch

# Verify traffic is flowing to new environment
curl -I https://your-domain.com
```

### 5. Cleanup Old Environment

```bash
# Stop old environment (automatic after successful switch)
# Or manually if needed
./blue-green-deploy.sh cleanup
```

## Rollback Procedures

### Quick Rollback (Blue-Green)

```bash
# Rollback to previous environment
./blue-green-deploy.sh rollback

# Verify rollback
curl -I https://your-domain.com
./health-check.sh
```

### Git-based Rollback

```bash
# Find previous stable version
git log --oneline -10

# Rollback to specific commit
git checkout [commit-hash]

# Redeploy
./deploy.sh deploy

# Run any necessary database rollbacks
docker-compose exec app php artisan migrate:rollback --step=1
```

### Database Rollback

```bash
# Backup current database state
docker-compose exec postgres pg_dump -U laravel laravel > rollback_backup.sql

# Rollback migrations (if needed)
docker-compose exec app php artisan migrate:rollback --step=5

# Or restore from backup
docker-compose exec -T postgres psql -U laravel laravel < previous_backup.sql
```

## Post-deployment Verification

### 1. Health Checks

```bash
# Run comprehensive health check
./health-check.sh

# Check specific services
curl -f https://your-domain.com/health
curl -f https://your-domain.com/api/health
```

### 2. Application Verification

```bash
# Test critical functionality
curl -X POST https://your-domain.com/api/test
curl -f https://your-domain.com/login

# Check database connectivity
docker-compose exec app php artisan tinker
# DB::connection()->getPdo();
```

### 3. Performance Verification

```bash
# Check response times
curl -w "@curl-format.txt" -o /dev/null -s https://your-domain.com

# Monitor resource usage
docker stats

# Check queue processing
docker-compose exec app php artisan horizon:status
```

### 4. Log Verification

```bash
# Check for errors in logs
docker-compose logs app | grep ERROR
docker-compose logs nginx | grep error

# Monitor real-time logs
docker-compose logs -f app
```

## Automated Deployment

### CI/CD Pipeline Example (GitHub Actions)

```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup SSH
      uses: webfactory/ssh-agent@v0.7.0
      with:
        ssh-private-key: ${{ secrets.SSH_PRIVATE_KEY }}
    
    - name: Deploy to server
      run: |
        ssh -o StrictHostKeyChecking=no user@${{ secrets.SERVER_HOST }} '
          cd /opt/laravel-app &&
          git pull origin main &&
          ./blue-green-deploy.sh deploy &&
          ./blue-green-deploy.sh switch
        '
    
    - name: Verify deployment
      run: |
        curl -f https://${{ secrets.DOMAIN }}/health
```

### Deployment Hooks

Create deployment hooks for automated tasks:

```bash
# scripts/pre-deploy.sh
#!/bin/bash
echo "Running pre-deployment tasks..."
./health-check.sh
docker-compose exec postgres pg_dump -U laravel laravel > pre_deploy_backup.sql

# scripts/post-deploy.sh
#!/bin/bash
echo "Running post-deployment tasks..."
./health-check.sh
curl -f https://your-domain.com/health
# Send notification to Slack/email
```

### Monitoring Integration

```bash
# scripts/deployment-notification.sh
#!/bin/bash
DEPLOYMENT_STATUS=$1
WEBHOOK_URL="https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK"

curl -X POST -H 'Content-type: application/json' \
    --data "{\"text\":\"Deployment ${DEPLOYMENT_STATUS}: $(date)\"}" \
    $WEBHOOK_URL
```

## Troubleshooting

### Common Deployment Issues

#### 1. Container Build Failures

```bash
# Check build logs
docker-compose build --no-cache app

# Debug build issues
docker run -it --rm laravel-app:latest /bin/bash

# Clear Docker cache
docker system prune -a
```

#### 2. Database Migration Failures

```bash
# Check migration status
docker-compose exec app php artisan migrate:status

# Run migrations manually
docker-compose exec app php artisan migrate --step

# Rollback problematic migration
docker-compose exec app php artisan migrate:rollback --step=1
```

#### 3. Permission Issues

```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache

# Fix log permissions
docker-compose exec app chmod 666 storage/logs/laravel.log
```

#### 4. SSL Certificate Issues

```bash
# Check certificate status
openssl s_client -connect your-domain.com:443 -servername your-domain.com

# Renew Let's Encrypt certificate (Caddy)
docker-compose restart caddy

# Check Caddy logs
docker-compose logs caddy
```

#### 5. Load Balancer Issues

```bash
# Check Nginx configuration
docker-compose exec nginx nginx -t

# Reload Nginx configuration
docker-compose exec nginx nginx -s reload

# Check upstream health
curl -f http://localhost:9000/health
```

### Performance Issues

#### 1. Slow Response Times

```bash
# Enable query logging
docker-compose exec app php artisan telescope:install

# Check slow queries
docker-compose exec postgres psql -U laravel laravel
# SELECT * FROM pg_stat_statements ORDER BY total_time DESC LIMIT 10;

# Optimize application
docker-compose exec app php artisan optimize
```

#### 2. High Memory Usage

```bash
# Check memory usage
docker stats

# Optimize PHP memory settings
# Edit docker/production/php/php.ini
memory_limit = 512M
opcache.memory_consumption = 256

# Restart containers
docker-compose restart app
```

#### 3. Queue Backlog

```bash
# Check queue status
docker-compose exec app php artisan horizon:status

# Scale queue workers
docker-compose up -d --scale horizon=3

# Clear failed jobs
docker-compose exec app php artisan queue:flush
```

### Emergency Procedures

#### 1. Immediate Rollback

```bash
# Quick rollback using blue-green
./blue-green-deploy.sh rollback

# Or stop current deployment
docker-compose down
git checkout previous-stable-tag
docker-compose up -d
```

#### 2. Database Recovery

```bash
# Stop application
docker-compose stop app horizon

# Restore database
docker-compose exec -T postgres psql -U laravel laravel < emergency_backup.sql

# Start application
docker-compose start app horizon
```

#### 3. Service Recovery

```bash
# Restart all services
docker-compose restart

# Or restart specific service
docker-compose restart app

# Check service health
./health-check.sh
```

## Best Practices

### 1. Deployment Safety

- Always backup before deployment
- Use blue-green deployment for critical applications
- Test deployments in staging environment first
- Monitor application during and after deployment
- Have rollback plan ready

### 2. Security

- Use secure environment variables
- Rotate secrets regularly
- Monitor for security vulnerabilities
- Keep dependencies updated
- Use HTTPS for all communications

### 3. Performance

- Optimize Docker images
- Use multi-stage builds
- Cache dependencies
- Monitor resource usage
- Scale services as needed

### 4. Monitoring

- Set up comprehensive monitoring
- Configure alerting for critical issues
- Monitor deployment metrics
- Track application performance
- Log all deployment activities

## Support

For deployment issues:
- Check logs: `docker-compose logs [service]`
- Run health checks: `./health-check.sh`
- Contact: devops@yourcompany.com
- Emergency: +1-XXX-XXX-XXXX