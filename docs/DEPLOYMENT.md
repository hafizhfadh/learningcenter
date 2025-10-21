# Deployment Guide

## Quick Start

### Prerequisites
- Docker and Docker Compose installed
- Access to GitHub Container Registry (GHCR)
- Production environment configured

### One-Command Deployment
```bash
cd deploy/production
./bin/deploy.sh
```

## Deployment Methods

### 1. Automated CI/CD Deployment
**Trigger**: Push to `main` branch or create Git tag

```bash
# Deploy latest changes
git push origin main

# Deploy specific version
git tag v1.2.3
git push origin v1.2.3
```

**Process**:
1. GitHub Actions builds Docker image
2. Image pushed to GHCR with appropriate tags
3. Ready for production deployment

### 2. Manual Production Deployment

#### Standard Deployment
```bash
# Navigate to production directory
cd deploy/production

# Deploy latest image
./bin/deploy.sh
```

#### Specific Version Deployment
```bash
# Deploy specific image version
APP_IMAGE=ghcr.io/hafizhfadh/learningcenter:v1.2.3 ./bin/deploy.sh

# Deploy specific commit
APP_IMAGE=ghcr.io/hafizhfadh/learningcenter:sha-abc123 ./bin/deploy.sh
```

### 3. Local Development Deployment

#### Build and Run Locally
```bash
# Build production image locally
docker build -f deploy/production/Dockerfile -t learningcenter:local .

# Run with Docker Compose
cd deploy/production
APP_IMAGE=learningcenter:local docker compose up -d
```

#### Test GHCR Integration
```bash
# Test GHCR upload process
./test-ghcr-upload.sh

# Test with specific repository
GITHUB_REPOSITORY=hafizhfadh/learningcenter ./test-ghcr-upload.sh
```

## Environment Setup

### Production Environment Files

#### Required Files
```
deploy/production/secrets/
├── .env.production          # Application configuration
├── .env.database           # Database credentials
└── .env.redis              # Redis configuration
```

#### Sample Configuration
```bash
# .env.production
APP_ENV=production
APP_DEBUG=false
APP_URL=https://learningcenter.example.com
APP_KEY=base64:your-app-key-here

# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=learningcenter
DB_USERNAME=learningcenter
DB_PASSWORD=secure-password

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=redis-password
REDIS_PORT=6379

# Cache
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Docker Compose Configuration

#### Production Stack
```yaml
# deploy/production/docker-compose.yml
services:
  app:
    image: ${APP_IMAGE:-ghcr.io/hafizhfadh/learningcenter:latest}
    ports:
      - "8080:8080"
    env_file:
      - secrets/.env.production
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_healthy
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8080/health"]
      interval: 30s
      timeout: 10s
      retries: 3

  postgres:
    image: postgres:16
    env_file:
      - secrets/.env.database
    volumes:
      - postgres_data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME}"]
      interval: 10s
      timeout: 5s
      retries: 5

  redis:
    image: redis:7-alpine
    command: redis-server --requirepass ${REDIS_PASSWORD}
    env_file:
      - secrets/.env.redis
    volumes:
      - redis_data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 3
```

## Deployment Process Details

### Pre-deployment Checks
```bash
# Verify Docker and Compose
docker --version
docker compose version

# Check GHCR connectivity
docker pull ghcr.io/hafizhfadh/learningcenter:latest

# Validate environment files
ls -la deploy/production/secrets/
```

### Deployment Steps

#### 1. Environment Preparation
```bash
cd deploy/production

# Ensure secrets directory exists
mkdir -p secrets

# Verify environment files
cat secrets/.env.production | grep -E "^(APP_|DB_|REDIS_)"
```

#### 2. Image Management
```bash
# Pull latest image
docker pull ghcr.io/hafizhfadh/learningcenter:latest

# Verify image
docker run --rm --entrypoint="" ghcr.io/hafizhfadh/learningcenter:latest php --version
```

#### 3. Service Deployment
```bash
# Deploy services
docker compose --env-file secrets/.env.production up -d

# Wait for health checks
docker compose ps
```

#### 4. Application Setup
```bash
# Run database migrations
docker compose exec app php artisan migrate --force

# Clear and cache configuration
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

#### 5. Verification
```bash
# Check application health
curl -f http://localhost:8080/health

# Verify database connection
docker compose exec app php artisan tinker --execute="DB::connection()->getPdo();"

# Check logs
docker compose logs app --tail=50
```

## Rollback Procedures

### Quick Rollback
```bash
# Rollback to previous image
APP_IMAGE=ghcr.io/hafizhfadh/learningcenter:sha-previous ./bin/deploy.sh

# Or use specific version
APP_IMAGE=ghcr.io/hafizhfadh/learningcenter:v1.1.0 ./bin/deploy.sh
```

### Database Rollback
```bash
# If migrations need rollback
docker compose exec app php artisan migrate:rollback --step=1

# Check migration status
docker compose exec app php artisan migrate:status
```

### Complete Environment Reset
```bash
# Stop all services
docker compose down

# Remove volumes (CAUTION: Data loss)
docker compose down -v

# Redeploy from scratch
./bin/deploy.sh
```

## Monitoring & Health Checks

### Application Health
```bash
# Health endpoint
curl http://localhost:8080/health

# Application status
docker compose ps

# Resource usage
docker stats
```

### Service Health
```bash
# Database health
docker compose exec postgres pg_isready -U learningcenter

# Redis health
docker compose exec redis redis-cli ping

# Application logs
docker compose logs app --follow
```

### Performance Monitoring
```bash
# Container resource usage
docker compose top

# Application metrics
docker compose exec app php artisan horizon:status  # If using Horizon

# Database connections
docker compose exec postgres psql -U learningcenter -c "SELECT count(*) FROM pg_stat_activity;"
```

## Advanced: FrankenPHP/Caddy writable data and hardened health checks

### Writable `/data` volume (Option A)
Mount a named volume at `/data` for all app-derived services to resolve Caddy/FrankenPHP write warnings under a read-only root filesystem.

```yaml
services:
  app:
    volumes:
      - frankenphp_data:/data
  horizon:
    volumes:
      - frankenphp_data:/data
  queue:
    volumes:
      - frankenphp_data:/data
  scheduler:
    volumes:
      - frankenphp_data:/data

volumes:
  frankenphp_data:
    driver: local
```

Notes:
- Stores runtime metadata only (no secrets). Optional to back up.
- Root filesystem remains read-only; only volumes are writable.

### Hardened app health check
Validate both HTTP 200 and JSON content with short timeouts and an extended start period:

```yaml
services:
  app:
    healthcheck:
      test:
        - CMD-SHELL
        - code=$(curl -s -o /dev/null -w "%{http_code}" --max-time 3 http://127.0.0.1:9000/health) && [ "$code" -eq 200 ] && curl -s --max-time 3 http://127.0.0.1:9000/health | grep -q "\"status\":\"ok\"" || exit 1
      interval: 30s
      timeout: 5s
      retries: 5
      start_period: 60s
```

Non-HTTP checks:
- Horizon: `php artisan horizon:status | grep -q running`
- Scheduler: `php artisan schedule:list`

Troubleshooting commands:
```bash
docker compose exec app sh -lc 'curl -i http://127.0.0.1:9000/health'
docker compose exec app sh -lc 'wget -qO- http://127.0.0.1:9000/health'
```

## Troubleshooting

### Common Issues

#### Image Pull Failures
```bash
# Check GHCR authentication
docker login ghcr.io

# Verify image exists
docker manifest inspect ghcr.io/hafizhfadh/learningcenter:latest

# Use alternative registry
APP_IMAGE=learningcenter:local ./bin/deploy.sh
```

#### Database Connection Issues
```bash
# Check database container
docker compose logs postgres

# Test connection
docker compose exec app php artisan tinker --execute="DB::connection()->getPdo();"

# Verify environment variables
docker compose exec app env | grep DB_
```

#### Application Startup Failures
```bash
# Check application logs
docker compose logs app --tail=100

# Verify PHP extensions
docker compose exec app php -m | grep -E "(pdo_pgsql|redis|opcache)"

# Test configuration
docker compose exec app php artisan config:show
```

#### Performance Issues
```bash
# Check resource limits
docker compose exec app cat /proc/meminfo
docker compose exec app cat /proc/cpuinfo

# Monitor application performance
docker compose exec app php artisan horizon:status
docker compose exec app php artisan queue:work --once
```

### Debug Commands
```bash
# Enter application container
docker compose exec app bash

# Check file permissions
docker compose exec app ls -la /app

# Verify Laravel installation
docker compose exec app php artisan --version

# Test database migrations
docker compose exec app php artisan migrate:status

# Clear all caches
docker compose exec app php artisan optimize:clear
```

## Security Considerations

### Container Security
- Run containers as non-root user
- Use read-only filesystems where possible
- Limit container capabilities
- Regular security updates

### Network Security
- Use internal Docker networks
- Expose only necessary ports
- Implement proper firewall rules
- Use TLS for external communications

### Data Security
- Encrypt sensitive environment variables
- Use Docker secrets for production
- Regular database backups
- Secure Redis with authentication

## Backup & Recovery

### Database Backup
```bash
# Create database backup
docker compose exec postgres pg_dump -U learningcenter learningcenter > backup.sql

# Restore database
docker compose exec -T postgres psql -U learningcenter learningcenter < backup.sql
```

### Application Data Backup
```bash
# Backup uploaded files (if any)
docker compose exec app tar -czf /tmp/uploads.tar.gz /app/storage/app/public

# Copy backup from container
docker compose cp app:/tmp/uploads.tar.gz ./uploads-backup.tar.gz
```

### Complete Environment Backup
```bash
# Export environment configuration
cp -r deploy/production/secrets/ backup/secrets-$(date +%Y%m%d)

# Backup Docker volumes
docker run --rm -v postgres_data:/data -v $(pwd):/backup alpine tar -czf /backup/postgres-$(date +%Y%m%d).tar.gz /data
```

## Performance Optimization

### Application Performance
- Enable OPcache in production
- Use Redis for caching and sessions
- Optimize database queries
- Implement proper indexing

### Container Performance
- Use multi-stage Docker builds
- Optimize image layers
- Implement health checks
- Monitor resource usage

### Infrastructure Performance
- Use SSD storage for databases
- Implement proper networking
- Monitor and scale resources
- Use CDN for static assets

## Maintenance

### Regular Tasks
- Update base Docker images
- Apply security patches
- Monitor disk usage
- Review application logs

### Scheduled Maintenance
- Database optimization
- Log rotation
- Backup verification
- Performance monitoring

### Version Updates
- Test updates in staging
- Plan maintenance windows
- Communicate with stakeholders
- Document changes