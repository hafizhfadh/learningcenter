# Production Deployment Guide

Comprehensive guide for deploying Laravel Learning Center to production with external PostgreSQL clusters, GitHub Container Registry, and Docker.

## 🏗️ Architecture Overview

The Laravel Learning Center is designed for production deployment with:
- **External PostgreSQL cluster** (managed database service)
- **Docker containerization** with FrankenPHP
- **GitHub Container Registry** for image distribution
- **Caddy web server** with automatic HTTPS
- **Redis** for caching and sessions

## 🚀 Quick Deployment

### Prerequisites

1. **Server Requirements**
   - Docker and Docker Compose installed
   - Minimum 2GB RAM, 2 CPU cores
   - 20GB+ disk space
   - Ports 80, 443 open for web traffic

2. **External Services**
   - PostgreSQL cluster (managed service recommended)
   - GitHub Personal Access Token for GHCR
   - Domain name with DNS configured

### Environment Setup

```bash
# Required PostgreSQL Cluster Variables
export DB_HOST="your-cluster-host.example.com"
export DB_PORT="5432"
export DB_DATABASE="learningcenter_production"
export DB_USERNAME="laravel_user"
export DB_PASSWORD="your_secure_password"
export DB_SSLMODE="require"

# GitHub Container Registry
export GITHUB_TOKEN="ghp_xxxxxxxxxxxxxxxxxxxx"
export GITHUB_REPOSITORY_OWNER="your-username"
export APP_NAME="learningcenter"
export IMAGE_NAME="learningcenter"

# Application
export APP_KEY="base64:$(openssl rand -base64 32)"
export APP_URL="https://your-domain.com"
export REDIS_PASSWORD="$(openssl rand -base64 32)"
```

### Deployment Commands

```bash
# Test database connectivity
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT version();"

# Deploy with GHCR upload
./scripts/deploy-production.sh v1.0.0 --push-to-ghcr

# Or deploy without GHCR
./scripts/deploy-production.sh v1.0.0

# Manual deployment
docker-compose -f docker-compose.production.yml up -d
```

## 🐳 Docker Configuration

### Key Files

| File | Purpose | Description |
|------|---------|-------------|
| `Dockerfile.frankenphp.improved` | Application container | Multi-stage build with security optimizations |
| `docker-compose.production.yml` | Orchestration | Production container orchestration |
| `docker/startup.sh` | Runtime initialization | Database connectivity and Laravel setup |
| `.env.production.example` | Environment template | Production environment variables |

### Container Features

- **Multi-stage build** for optimized image size
- **Non-root user** execution for security
- **Health checks** for container monitoring
- **Graceful shutdown** handling
- **PostgreSQL client tools** for database operations
- **Security options** enabled

## 🗄️ External PostgreSQL Setup

### Database Configuration

```bash
# Create database and user
CREATE DATABASE learningcenter_production;
CREATE USER laravel_user WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE learningcenter_production TO laravel_user;

# Enable required extensions
\c learningcenter_production;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
```

### Connection Testing

```bash
# Test connectivity
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT 1;"

# Test SSL connection
psql "postgresql://$DB_USERNAME:$DB_PASSWORD@$DB_HOST:$DB_PORT/$DB_DATABASE?sslmode=$DB_SSLMODE"
```

### Required Environment Variables

```bash
DB_CONNECTION=pgsql
DB_HOST=your-cluster-host.example.com
DB_PORT=5432
DB_DATABASE=learningcenter_production
DB_USERNAME=laravel_user
DB_PASSWORD=your_secure_password
DB_SSLMODE=require
```

## 📦 GitHub Container Registry (GHCR)

### Setup

1. **Create GitHub Personal Access Token**
   - Go to GitHub Settings → Developer settings → Personal access tokens
   - Create token with `write:packages` and `read:packages` permissions

2. **Configure Environment**
   ```bash
   export GITHUB_TOKEN="ghp_xxxxxxxxxxxxxxxxxxxx"
   export GITHUB_REPOSITORY_OWNER="your-username"
   ```

### Usage

```bash
# Login to GHCR
echo $GITHUB_TOKEN | docker login ghcr.io -u $GITHUB_REPOSITORY_OWNER --password-stdin

# Deploy with GHCR upload
./scripts/deploy-production.sh v1.0.0 --push-to-ghcr

# Pull image from GHCR
docker pull ghcr.io/$GITHUB_REPOSITORY_OWNER/learningcenter:v1.0.0
```

### Image Naming Convention

```
ghcr.io/{owner}/{repository}:{version}
```

Examples:
- `ghcr.io/your-username/learningcenter:v1.0.0`
- `ghcr.io/your-username/learningcenter:latest`
- `ghcr.io/your-username/learningcenter:dev-20240115`

## 🔧 Configuration Files

### docker-compose.production.yml

```yaml
version: '3.8'
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile.frankenphp.improved
    ports:
      - "80:80"
      - "443:443"
      - "443:443/udp"
    environment:
      - APP_ENV=production
      - DB_HOST=${DB_HOST}
      - DB_PASSWORD=${DB_PASSWORD}
    volumes:
      - storage_data:/var/www/html/storage
    depends_on:
      - redis
    restart: unless-stopped

  redis:
    image: redis:7-alpine
    command: redis-server --requirepass ${REDIS_PASSWORD}
    volumes:
      - redis_data:/data
    restart: unless-stopped

volumes:
  storage_data:
  redis_data:
```

### Caddyfile

```
{
    email your-email@example.com
    admin off
}

your-domain.com {
    root * /var/www/html/public
    encode gzip
    php_fastcgi unix//var/run/php/php-fpm.sock
    file_server
}
```

## 🔍 Health Checks and Monitoring

### Application Health

```bash
# Health endpoint
curl -f https://your-domain.com/health

# Container status
docker-compose -f docker-compose.production.yml ps

# Application logs
docker-compose -f docker-compose.production.yml logs -f app
```

### Database Monitoring

```bash
# Database connectivity from container
docker-compose -f docker-compose.production.yml exec app php artisan tinker
# In tinker: DB::connection()->getPdo();

# Database performance
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "
  SELECT query, calls, total_time, mean_time 
  FROM pg_stat_statements 
  ORDER BY total_time DESC 
  LIMIT 10;"
```

### Container Monitoring

```bash
# Resource usage
docker stats --no-stream

# Disk usage
docker system df

# Network status
docker network ls
```

## 🚨 Troubleshooting Guide

### Database Connection Issues

**Symptom: Connection Refused**
```bash
# Check network connectivity
telnet $DB_HOST $DB_PORT

# Verify firewall rules
sudo ufw status
sudo iptables -L -n

# Test from container
docker-compose exec app psql -h $DB_HOST -p $DB_PORT -U $DB_USERNAME -d $DB_DATABASE
```

**Symptom: Authentication Failed**
```bash
# Verify credentials
echo "Host: $DB_HOST"
echo "Username: $DB_USERNAME"
echo "Database: $DB_DATABASE"

# Test credentials
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT current_user;"
```

**Symptom: SSL Connection Failed**
```bash
# Check SSL mode
echo "SSL Mode: $DB_SSLMODE"

# Test SSL connection
psql "postgresql://$DB_USERNAME:$DB_PASSWORD@$DB_HOST:$DB_PORT/$DB_DATABASE?sslmode=require"
```

### Docker Container Issues

**Container Won't Start**
```bash
# Check container logs
docker-compose logs app

# Check resource usage
docker stats

# Rebuild container
docker-compose down
docker-compose up -d --build
```

**Container Not Responding**
```bash
# Check health status
docker-compose ps

# Access container shell
docker-compose exec app bash

# Check processes
docker-compose exec app ps aux
```

### Network Configuration Issues

**DNS Resolution Failures**
```bash
# Test DNS resolution
nslookup $DB_HOST
dig $DB_HOST @8.8.8.8

# Check /etc/hosts
docker-compose exec app cat /etc/hosts
```

**Port Access Issues**
```bash
# Check port binding
sudo netstat -tlnp | grep -E ':(80|443)'

# Test local connectivity
curl -I http://localhost/
curl -I -k https://localhost/
```

### GHCR Issues

**Authentication Failed**
```bash
# Verify token permissions
curl -H "Authorization: token $GITHUB_TOKEN" https://api.github.com/user

# Re-login to GHCR
docker logout ghcr.io
echo $GITHUB_TOKEN | docker login ghcr.io -u $GITHUB_REPOSITORY_OWNER --password-stdin
```

**Image Push/Pull Failed**
```bash
# Check repository name
echo $GITHUB_REPOSITORY_OWNER
echo $IMAGE_NAME

# Verify image exists locally
docker images | grep learningcenter

# Check package visibility in GitHub
# Go to GitHub → Your Repository → Packages
```

## 🔒 Security Considerations

### Environment Variables
- Never commit secrets to version control
- Use environment-specific configurations
- Rotate passwords and tokens regularly
- Use GitHub Actions secrets for CI/CD

### Network Security
- Enable firewall rules for required ports only
- Use SSL/TLS for all database connections
- Configure proper security groups in cloud providers
- Enable HTTPS with automatic certificate management

### Container Security
- Run containers as non-root user
- Enable security options (`no-new-privileges:true`)
- Scan images for vulnerabilities
- Keep base images updated
- Remove development tools from production images

### Database Security
- Use strong passwords
- Enable SSL/TLS connections
- Restrict database access by IP
- Regular security updates
- Monitor access logs

## ⚡ Performance Optimization

### Laravel Optimization

```bash
# Cache configuration
docker-compose exec app php artisan config:cache

# Cache routes
docker-compose exec app php artisan route:cache

# Cache views
docker-compose exec app php artisan view:cache

# Optimize autoloader
docker-compose exec app composer install --optimize-autoloader --no-dev
```

### Database Optimization

```bash
# Create indexes for better performance
CREATE INDEX CONCURRENTLY idx_users_email ON users(email);
CREATE INDEX CONCURRENTLY idx_posts_created_at ON posts(created_at);

# Analyze tables for query optimization
ANALYZE;

# Monitor slow queries
SELECT query, calls, total_time, mean_time 
FROM pg_stat_statements 
ORDER BY total_time DESC 
LIMIT 10;
```

### Caching Strategy

```bash
# Redis configuration
REDIS_HOST=redis
REDIS_PASSWORD=secure_redis_password
REDIS_PORT=6379
REDIS_DB=0

# Cache driver
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## 🔄 Backup and Recovery

### Database Backup

```bash
# Create backup
PGPASSWORD="$DB_PASSWORD" pg_dump \
  -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" \
  > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore backup
PGPASSWORD="$DB_PASSWORD" psql \
  -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" \
  < backup_file.sql
```

### Application Backup

```bash
# Backup storage directory
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/

# Backup environment
cp .env.production .env.backup.$(date +%Y%m%d)
```

## 🔧 Maintenance and Updates

### Application Updates

```bash
# Pull latest code
git pull origin main

# Rebuild and deploy
./scripts/deploy-production.sh v1.1.0 --push-to-ghcr

# Run migrations
docker-compose exec app php artisan migrate
```

### Container Updates

```bash
# Update base images
docker-compose pull

# Rebuild with latest dependencies
docker-compose up -d --build

# Clean up old images
docker image prune -f
```

### Database Maintenance

```bash
# Update statistics
ANALYZE;

# Vacuum tables
VACUUM ANALYZE;

# Check database size
SELECT pg_size_pretty(pg_database_size('learningcenter_production'));
```

## 📞 Support and Emergency Procedures

### Log Locations

```bash
# Application logs
docker-compose logs app

# Laravel logs
docker-compose exec app tail -f storage/logs/laravel.log

# Web server logs
docker-compose exec app tail -f /var/log/caddy/access.log

# Database logs (if accessible)
tail -f /var/log/postgresql/postgresql.log
```

### Common Commands

```bash
# Restart application
docker-compose restart app

# Clear application cache
docker-compose exec app php artisan cache:clear

# Run database migrations
docker-compose exec app php artisan migrate

# Check application status
curl -f https://your-domain.com/health
```

### Emergency Procedures

**Application Down**
1. Check container status: `docker-compose ps`
2. Check logs: `docker-compose logs app`
3. Restart containers: `docker-compose restart`
4. If persistent, rollback: `docker pull ghcr.io/user/learningcenter:previous-version`

**Database Issues**
1. Test connectivity: `psql -h $DB_HOST -U $DB_USERNAME -d $DB_DATABASE`
2. Check database status with provider
3. Verify network connectivity
4. Check SSL/TLS configuration

**Performance Issues**
1. Check resource usage: `docker stats`
2. Monitor database performance
3. Clear application caches
4. Scale containers if needed

## 🎯 Success Criteria

A successful deployment should have:

- ✅ Application accessible via HTTPS
- ✅ Database connectivity working
- ✅ Redis caching functional
- ✅ Health checks passing
- ✅ SSL certificates auto-renewed
- ✅ Logs properly configured
- ✅ Backup procedures in place
- ✅ Monitoring alerts configured

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Filament Documentation](https://filamentphp.com/docs)
- [Docker Documentation](https://docs.docker.com/)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Caddy Documentation](https://caddyserver.com/docs/)
- [GitHub Container Registry](https://docs.github.com/en/packages/working-with-a-github-packages-registry/working-with-the-container-registry)

---

**For quick reference commands and troubleshooting, see the main [README.md](README.md)**