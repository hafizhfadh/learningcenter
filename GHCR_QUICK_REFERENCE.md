# GHCR Deployment Quick Reference

## 🚀 Quick Start

### 1. Set Environment Variables
```bash
export GITHUB_TOKEN="ghp_xxxxxxxxxxxxxxxxxxxx"
export GITHUB_REPOSITORY_OWNER="your-username"
export APP_NAME="learningcenter"
export IMAGE_NAME="learningcenter"
```

### 2. Deploy with GHCR Upload
```bash
./scripts/deploy-production.sh v1.0.0 --push-to-ghcr
```

### 3. Deploy without GHCR Upload
```bash
./scripts/deploy-production.sh v1.0.0
```

## 📋 Required Environment Variables

| Variable | Description | Example |
|----------|-------------|----------|
| `GITHUB_TOKEN` | GitHub Personal Access Token | `ghp_xxxxxxxxxxxxxxxxxxxx` |
| `GITHUB_REPOSITORY_OWNER` | GitHub username/org | `your-username` |
| `APP_NAME` | Application name | `learningcenter` |
| `IMAGE_NAME` | Docker image name | `learningcenter` |
| `DB_HOST` | PostgreSQL cluster host | `postgres.example.com` |
| `DB_PORT` | PostgreSQL port | `5432` |
| `DB_DATABASE` | Database name | `learningcenter_production` |
| `DB_USERNAME` | Database user | `app_user` |
| `DB_PASSWORD` | Database password | `secure_password` |
| `DB_SSLMODE` | SSL mode | `require` |
| `REDIS_PASSWORD` | Redis password | `redis_password` |

## 🔧 Common Commands

### Docker Login to GHCR
```bash
echo $GITHUB_TOKEN | docker login ghcr.io -u $GITHUB_REPOSITORY_OWNER --password-stdin
```

### Pull Image from GHCR
```bash
docker pull ghcr.io/$GITHUB_REPOSITORY_OWNER/learningcenter:v1.0.0
```

### List Local Images
```bash
docker images | grep learningcenter
```

### Check Deployment Status
```bash
docker-compose ps
docker-compose logs app
```

## 🏷️ Image Tagging Convention

```
ghcr.io/{owner}/{repository}:{version}
```

**Examples:**
- `ghcr.io/your-username/learningcenter:v1.0.0`
- `ghcr.io/your-username/learningcenter:latest`
- `ghcr.io/your-username/learningcenter:dev-20240115`

## 🔍 Health Checks

### Application Health
```bash
curl -f http://localhost:8080/health
```

### Database Connectivity
```bash
psql -h $DB_HOST -p $DB_PORT -U $DB_USERNAME -d $DB_DATABASE -c "SELECT 1;"
```

### Redis Connectivity
```bash
redis-cli -h redis -p 6379 -a $REDIS_PASSWORD ping
```

### Docker Container Status
```bash
docker-compose ps
docker stats --no-stream
```

## 🚨 Troubleshooting

### Authentication Issues
```bash
# Test GitHub token
curl -H "Authorization: token $GITHUB_TOKEN" https://api.github.com/user

# Re-login to GHCR
docker logout ghcr.io
echo $GITHUB_TOKEN | docker login ghcr.io -u $GITHUB_REPOSITORY_OWNER --password-stdin
```

### Image Issues
```bash
# Check if image exists locally
docker images | grep learningcenter

# Check if image exists in GHCR
docker pull ghcr.io/$GITHUB_REPOSITORY_OWNER/learningcenter:latest

# Rebuild image
docker build -f Dockerfile.frankenphp.improved -t learningcenter:latest .
```

### Database Connection Issues
```bash
# Test database connection
psql -h $DB_HOST -p $DB_PORT -U $DB_USERNAME -d $DB_DATABASE -c "\l"

# Check SSL requirements
psql "postgresql://$DB_USERNAME:$DB_PASSWORD@$DB_HOST:$DB_PORT/$DB_DATABASE?sslmode=$DB_SSLMODE"
```

### Application Logs
```bash
# View application logs
docker-compose logs -f app

# View specific service logs
docker-compose logs redis

# View Laravel logs
docker-compose exec app tail -f storage/logs/laravel.log
```

## 🔒 Security Checklist

- [ ] GitHub token has minimal required permissions
- [ ] Environment variables are not committed to git
- [ ] Database uses SSL/TLS connection
- [ ] Redis is password protected
- [ ] Application runs as non-root user
- [ ] Images are scanned for vulnerabilities
- [ ] Secrets are injected at runtime
- [ ] HTTPS is enforced in production

## 📊 Monitoring Commands

### Resource Usage
```bash
# Container resource usage
docker stats --no-stream

# Disk usage
docker system df

# Network usage
docker network ls
```

### Application Metrics
```bash
# Laravel queue status
docker-compose exec app php artisan queue:work --once

# Cache status
docker-compose exec app php artisan cache:table

# Database migrations
docker-compose exec app php artisan migrate:status
```

## 🔄 Backup & Recovery

### Database Backup
```bash
# Create backup
pg_dump -h $DB_HOST -p $DB_PORT -U $DB_USERNAME -d $DB_DATABASE > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore backup
psql -h $DB_HOST -p $DB_PORT -U $DB_USERNAME -d $DB_DATABASE < backup_file.sql
```

### Application Backup
```bash
# Backup storage directory
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/

# Backup environment
cp .env .env.backup.$(date +%Y%m%d)
```

## ⚡ Performance Tips

### Docker Optimization
```bash
# Clean up unused images
docker image prune -f

# Clean up unused volumes
docker volume prune -f

# Clean up build cache
docker builder prune -f
```

### Laravel Optimization
```bash
# Clear and cache config
docker-compose exec app php artisan config:cache

# Clear and cache routes
docker-compose exec app php artisan route:cache

# Clear and cache views
docker-compose exec app php artisan view:cache
```

## 📞 Emergency Contacts

### Rollback Procedure
```bash
# Quick rollback to previous version
docker pull ghcr.io/$GITHUB_REPOSITORY_OWNER/learningcenter:v1.0.0
docker-compose up -d

# Check rollback status
curl -f http://localhost:8080/health
```

### Support Resources
- **GitHub Container Registry**: [GitHub Support](https://support.github.com/)
- **Docker Issues**: [Docker Documentation](https://docs.docker.com/)
- **Laravel Issues**: [Laravel Documentation](https://laravel.com/docs)
- **PostgreSQL Issues**: [PostgreSQL Documentation](https://www.postgresql.org/docs/)

---

**📖 For detailed information, see:** `GHCR_DEPLOYMENT_GUIDE.md`