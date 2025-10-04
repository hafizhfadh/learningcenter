# Production Deployment Guide

## Overview

This guide provides comprehensive instructions for deploying the Learning Center application to production. The application uses a containerized architecture with Docker Compose, featuring Laravel backend, Nginx reverse proxy, Redis caching, and integrated monitoring.

## Architecture Overview

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Load Balancer │────│  Nginx Proxy    │────│  Laravel App    │
│   (External)    │    │  (Port 80/443)  │    │  (Port 8000)    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                                │                       │
                                │                       │
                       ┌─────────────────┐    ┌─────────────────┐
                       │  Redis Cache    │    │  PostgreSQL DB  │
                       │  (Port 6379)    │    │  (External)     │
                       └─────────────────┘    └─────────────────┘
                                │
                       ┌─────────────────┐
                       │  Monitoring     │
                       │  Stack          │
                       └─────────────────┘
```

## Prerequisites

### System Requirements
- **OS**: Ubuntu 20.04+ or CentOS 8+
- **CPU**: 4+ cores (recommended)
- **RAM**: 4GB+ (8GB recommended)
- **Storage**: 50GB+ SSD
- **Network**: Static IP with domain name

### Software Dependencies
- Docker 24.0+
- Docker Compose 2.0+
- Git 2.30+
- SSL certificates (Let's Encrypt recommended)

### External Services
- PostgreSQL database (managed service recommended)
- SMTP service for email notifications
- Domain name with DNS access

## Pre-Deployment Setup

### 1. Server Preparation

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verify installations
docker --version
docker-compose --version
```

### 2. Security Hardening

```bash
# Run security setup script
sudo ./docker/production/security/security-setup.sh

# Configure firewall
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable
```

### 3. SSL Certificate Setup

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Obtain SSL certificate
sudo certbot certonly --standalone -d yourdomain.com -d www.yourdomain.com

# Copy certificates to project
sudo cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem ./docker/production/ssl/
sudo cp /etc/letsencrypt/live/yourdomain.com/privkey.pem ./docker/production/ssl/
sudo chown $USER:$USER ./docker/production/ssl/*
```

## Environment Configuration

### 1. Create Production Environment File

```bash
cp .env.production.example .env.production
```

### 2. Configure Environment Variables

Edit `.env.production` with your production values:

```bash
# Application
APP_NAME="Learning Center"
APP_ENV=production
APP_KEY=base64:your-32-character-key-here
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database (External PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=your-db-host.com
DB_PORT=5432
DB_DATABASE=learning_center_prod
DB_USERNAME=your-db-user
DB_PASSWORD=your-secure-password

# Redis
REDIS_PASSWORD=your-redis-password

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host.com
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls

# GitHub Container Registry
GITHUB_REPOSITORY=your-username/learning-center
IMAGE_TAG=latest
```

### 3. Generate Application Key

```bash
# Generate a new application key
php artisan key:generate --show

# Copy the generated key to your .env.production file
```

## Deployment Process

### 1. Clone Repository

```bash
git clone https://github.com/your-username/learning-center.git
cd learning-center
git checkout main
```

### 2. Build and Deploy

```bash
# Make deployment script executable
chmod +x scripts/deploy-production.sh

# Run deployment
./scripts/deploy-production.sh
```

### 3. Manual Deployment Steps

If you prefer manual deployment:

```bash
# Pull latest images
docker-compose -f docker-compose.production.yml pull

# Start services
docker-compose -f docker-compose.production.yml up -d

# Run database migrations
docker-compose -f docker-compose.production.yml exec app php artisan migrate --force

# Clear and cache configurations
docker-compose -f docker-compose.production.yml exec app php artisan config:cache
docker-compose -f docker-compose.production.yml exec app php artisan route:cache
docker-compose -f docker-compose.production.yml exec app php artisan view:cache

# Set proper permissions
docker-compose -f docker-compose.production.yml exec app chown -R www-data:www-data /var/www/html/storage
docker-compose -f docker-compose.production.yml exec app chmod -R 775 /var/www/html/storage
```

## Post-Deployment Verification

### 1. Health Checks

```bash
# Check service status
docker-compose -f docker-compose.production.yml ps

# Test application health
curl -f http://localhost/health

# Test SSL configuration
curl -f https://yourdomain.com/health
```

### 2. Monitoring Setup

Access monitoring dashboards:
- **Prometheus**: http://yourdomain.com:9090
- **Node Exporter**: http://yourdomain.com:9100
- **cAdvisor**: http://yourdomain.com:8080
- **Redis Exporter**: http://yourdomain.com:9121

### 3. Log Verification

```bash
# Check application logs
docker-compose -f docker-compose.production.yml logs app

# Check Nginx logs
docker-compose -f docker-compose.production.yml logs nginx

# Check Redis logs
docker-compose -f docker-compose.production.yml logs redis
```

## Backup Configuration

### 1. Database Backup

```bash
# Configure backup automation
cp docker/production/backup-config.env.example docker/production/backup-config.env

# Edit backup configuration
nano docker/production/backup-config.env

# Run backup setup
./scripts/setup-backup-automation.sh
```

### 2. Application Data Backup

```bash
# Backup application storage
docker run --rm -v learning-center_app_storage:/data -v $(pwd)/backups:/backup alpine tar czf /backup/app-storage-$(date +%Y%m%d).tar.gz -C /data .

# Backup SSL certificates
tar czf backups/ssl-certs-$(date +%Y%m%d).tar.gz docker/production/ssl/
```

## Maintenance Operations

### 1. Updates and Upgrades

```bash
# Pull latest application image
docker-compose -f docker-compose.production.yml pull app

# Restart with new image
docker-compose -f docker-compose.production.yml up -d app

# Run any new migrations
docker-compose -f docker-compose.production.yml exec app php artisan migrate --force
```

### 2. SSL Certificate Renewal

```bash
# Renew certificates (automated via cron)
sudo certbot renew --quiet

# Update certificates in project
sudo cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem ./docker/production/ssl/
sudo cp /etc/letsencrypt/live/yourdomain.com/privkey.pem ./docker/production/ssl/

# Restart Nginx
docker-compose -f docker-compose.production.yml restart nginx
```

### 3. Scaling Operations

```bash
# Scale application containers
docker-compose -f docker-compose.production.yml up -d --scale app=3

# Update load balancer configuration (if using external LB)
# Configure your load balancer to distribute traffic across multiple app instances
```

## Troubleshooting

### Common Issues

#### 1. Application Won't Start
```bash
# Check logs
docker-compose -f docker-compose.production.yml logs app

# Common fixes:
# - Verify database connectivity
# - Check environment variables
# - Ensure proper file permissions
```

#### 2. SSL Certificate Issues
```bash
# Verify certificate files
ls -la docker/production/ssl/

# Test SSL configuration
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com
```

#### 3. Database Connection Issues
```bash
# Test database connectivity
docker-compose -f docker-compose.production.yml exec app php artisan tinker
# In tinker: DB::connection()->getPdo();
```

#### 4. Redis Connection Issues
```bash
# Check Redis status
docker-compose -f docker-compose.production.yml exec redis redis-cli ping

# Test Redis authentication
docker-compose -f docker-compose.production.yml exec redis redis-cli -a your-redis-password ping
```

### Performance Optimization

#### 1. PHP-FPM Tuning
```bash
# Edit PHP configuration
nano docker/production/php.ini

# Key settings for production:
# memory_limit = 512M
# max_execution_time = 300
# upload_max_filesize = 100M
# post_max_size = 100M
```

#### 2. Nginx Optimization
```bash
# Edit Nginx configuration
nano docker/production/nginx/nginx.conf

# Key settings:
# worker_processes auto;
# worker_connections 1024;
# keepalive_timeout 65;
```

#### 3. Redis Optimization
```bash
# Edit Redis configuration
nano docker/production/redis/redis.conf

# Key settings:
# maxmemory 512mb
# maxmemory-policy allkeys-lru
```

## Security Considerations

### 1. Regular Security Updates
- Update Docker images monthly
- Apply OS security patches weekly
- Monitor security advisories for dependencies

### 2. Access Control
- Use strong passwords for all services
- Implement IP whitelisting for admin access
- Regular security audits

### 3. Data Protection
- Encrypt data at rest and in transit
- Regular backup testing
- Implement proper logging and monitoring

## Monitoring and Alerting

### 1. Key Metrics to Monitor
- Application response time
- Database connection pool usage
- Redis memory usage
- Disk space utilization
- SSL certificate expiration

### 2. Log Management
- Centralized logging with ELK stack (optional)
- Log rotation and retention policies
- Security event monitoring

## Support and Maintenance

### 1. Regular Maintenance Schedule
- **Daily**: Monitor system health and logs
- **Weekly**: Review security updates and apply patches
- **Monthly**: Update Docker images and dependencies
- **Quarterly**: Full security audit and backup testing

### 2. Emergency Procedures
- Disaster recovery plan
- Rollback procedures
- Emergency contact information

## Conclusion

This deployment guide provides a comprehensive approach to deploying the Learning Center application in production. Follow the steps carefully and maintain regular monitoring and maintenance schedules for optimal performance and security.

For additional support, refer to the troubleshooting section or contact the development team.