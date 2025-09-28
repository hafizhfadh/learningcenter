# Infrastructure Documentation

This document provides comprehensive guidance for deploying, monitoring, and maintaining the Laravel application infrastructure in production.

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Prerequisites](#prerequisites)
- [Initial Server Setup](#initial-server-setup)
- [Docker Deployment](#docker-deployment)
- [Blue-Green Deployment](#blue-green-deployment)
- [Monitoring and Logging](#monitoring-and-logging)
- [Security Configuration](#security-configuration)
- [Backup and Recovery](#backup-and-recovery)
- [Maintenance and Updates](#maintenance-and-updates)
- [Troubleshooting](#troubleshooting)

## Architecture Overview

The production infrastructure consists of:

- **Application Layer**: Laravel application running in Docker containers
- **Web Server**: Nginx as reverse proxy and static file server
- **Database**: PostgreSQL for primary data storage
- **Cache**: Redis for caching, sessions, and queues
- **Queue Processing**: Laravel Horizon for queue management
- **Monitoring**: Prometheus, Grafana, Loki for observability
- **Security**: Fail2ban, UFW, AppArmor for protection

### Container Architecture

```
┌─────────────────┐    ┌─────────────────┐
│     Nginx       │    │     Caddy       │
│   (Port 80/443) │    │   (Port 443)    │
└─────────┬───────┘    └─────────┬───────┘
          │                      │
          └──────────┬───────────┘
                     │
          ┌─────────────────┐
          │  Laravel App    │
          │   (Port 9000)   │
          └─────────┬───────┘
                    │
    ┌───────────────┼───────────────┐
    │               │               │
┌───▼────┐    ┌────▼────┐    ┌────▼────┐
│PostgreSQL│    │  Redis  │    │ Horizon │
│(Port 5432)│    │(Port 6379)│    │ Queues  │
└────────┘    └─────────┘    └─────────┘
```

## Prerequisites

### Server Requirements

- **OS**: Ubuntu 20.04 LTS or later
- **CPU**: 4+ cores (8+ recommended for production)
- **RAM**: 8GB minimum (16GB+ recommended)
- **Storage**: 100GB+ SSD storage
- **Network**: Static IP address, domain name configured

### Software Dependencies

- Docker Engine 20.10+
- Docker Compose 2.0+
- Git
- Make (optional, for Makefile commands)

## Initial Server Setup

### 1. Server Preparation

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install essential packages
sudo apt install -y curl wget git unzip software-properties-common

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Logout and login to apply Docker group membership
```

### 2. Security Hardening

Run the automated security setup script:

```bash
# Clone the repository
git clone <repository-url> /opt/laravel-app
cd /opt/laravel-app

# Run security setup (as root)
sudo ./docker/production/security/security-setup.sh
```

This script will:
- Install and configure Fail2ban
- Set up UFW firewall rules
- Harden SSH configuration
- Configure AppArmor profiles
- Set up audit logging
- Configure automatic security updates
- Install intrusion detection tools

### 3. Environment Configuration

```bash
# Copy environment file
cp .env.production.example .env.production

# Edit environment variables
nano .env.production
```

Key variables to configure:
- `APP_URL`: Your application domain
- `DB_*`: Database credentials
- `REDIS_*`: Redis configuration
- `MAIL_*`: Email service settings
- `AWS_*`: S3 storage credentials (if using)

## Docker Deployment

### Standard Deployment

```bash
# Build and start services
docker-compose -f docker-compose.production.yml up -d

# Run initial setup
docker-compose -f docker-compose.production.yml exec app php artisan migrate
docker-compose -f docker-compose.production.yml exec app php artisan config:cache
docker-compose -f docker-compose.production.yml exec app php artisan route:cache
docker-compose -f docker-compose.production.yml exec app php artisan view:cache
```

### Using Deployment Script

```bash
# Make deployment script executable
chmod +x deploy.sh

# Deploy application
./deploy.sh deploy

# Check deployment status
./deploy.sh status

# View logs
./deploy.sh logs
```

## Blue-Green Deployment

For zero-downtime deployments, use the blue-green deployment strategy:

```bash
# Make blue-green script executable
chmod +x blue-green-deploy.sh

# Deploy to inactive environment
./blue-green-deploy.sh deploy

# Check status
./blue-green-deploy.sh status

# Switch traffic (after verification)
./blue-green-deploy.sh switch

# Rollback if needed
./blue-green-deploy.sh rollback
```

### Blue-Green Process

1. **Detect Active Environment**: Script determines current active environment
2. **Deploy to Inactive**: Build and deploy to the inactive environment
3. **Health Checks**: Verify new deployment is healthy
4. **Switch Traffic**: Update Nginx configuration to route to new environment
5. **Cleanup**: Stop old environment containers

## Monitoring and Logging

### Starting Monitoring Stack

```bash
# Start monitoring services
docker-compose -f docker-compose.monitoring.yml up -d

# Check monitoring services
docker-compose -f docker-compose.monitoring.yml ps
```

### Accessing Monitoring Tools

- **Grafana**: http://your-server:3000 (admin/admin)
- **Prometheus**: http://your-server:9090
- **Alertmanager**: http://your-server:9093

### Key Metrics to Monitor

1. **Application Metrics**:
   - Response time
   - Error rate
   - Request volume
   - Queue length

2. **Infrastructure Metrics**:
   - CPU usage
   - Memory usage
   - Disk space
   - Network I/O

3. **Database Metrics**:
   - Connection count
   - Query performance
   - Lock waits
   - Replication lag

### Log Management

Logs are collected by Promtail and stored in Loki:

```bash
# View application logs
docker-compose logs app

# View Nginx logs
docker-compose logs nginx

# Query logs in Grafana
# Use LogQL queries in Grafana Explore
```

### Health Monitoring

```bash
# Run health checks
./health-check.sh

# Automated health monitoring (add to cron)
# */5 * * * * /opt/laravel-app/health-check.sh
```

## Security Configuration

### Firewall Rules

The UFW firewall is configured with:
- SSH (port 22) - rate limited
- HTTP (port 80)
- HTTPS (port 443)
- Monitoring ports (restricted to internal IPs)

```bash
# Check firewall status
sudo ufw status

# Add custom rules
sudo ufw allow from 10.0.0.0/8 to any port 9090
```

### Fail2ban Protection

Fail2ban monitors logs and bans malicious IPs:

```bash
# Check Fail2ban status
sudo fail2ban-client status

# Check specific jail
sudo fail2ban-client status sshd

# Unban IP
sudo fail2ban-client set sshd unbanip 192.168.1.100
```

### SSL/TLS Configuration

For SSL certificates, use Let's Encrypt with Caddy:

```bash
# Caddy automatically handles SSL certificates
# Ensure your domain points to the server IP
# Certificates are stored in Docker volumes
```

### Security Scanning

Regular security scans:

```bash
# Run Lynis security audit
sudo lynis audit system

# Check for rootkits
sudo rkhunter --check

# File integrity monitoring
sudo aide --check
```

## Backup and Recovery

### Database Backups

```bash
# Manual backup
docker-compose exec postgres pg_dump -U laravel laravel > backup_$(date +%Y%m%d_%H%M%S).sql

# Automated backup (add to cron)
# 0 2 * * * /opt/laravel-app/scripts/backup-database.sh
```

### Application Backups

```bash
# Backup application files
tar -czf app_backup_$(date +%Y%m%d_%H%M%S).tar.gz /opt/laravel-app

# Backup storage directory
docker-compose exec app tar -czf /tmp/storage_backup.tar.gz storage/
docker cp $(docker-compose ps -q app):/tmp/storage_backup.tar.gz ./
```

### Recovery Procedures

```bash
# Restore database
docker-compose exec -T postgres psql -U laravel laravel < backup_file.sql

# Restore application files
tar -xzf app_backup.tar.gz -C /

# Restart services
docker-compose restart
```

## Maintenance and Updates

### Application Updates

```bash
# Pull latest code
git pull origin main

# Update dependencies
docker-compose exec app composer install --no-dev --optimize-autoloader

# Run migrations
docker-compose exec app php artisan migrate

# Clear caches
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

### System Updates

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Update Docker images
docker-compose pull
docker-compose up -d

# Clean up old images
docker system prune -f
```

### Scheduled Maintenance

Add to crontab (`sudo crontab -e`):

```bash
# Daily backup at 2 AM
0 2 * * * /opt/laravel-app/scripts/backup.sh

# Weekly security scan at 3 AM Sunday
0 3 * * 0 /opt/laravel-app/scripts/security-scan.sh

# Monthly system update at 4 AM first day of month
0 4 1 * * /opt/laravel-app/scripts/system-update.sh

# Health check every 5 minutes
*/5 * * * * /opt/laravel-app/health-check.sh
```

## Troubleshooting

### Common Issues

#### 1. Container Won't Start

```bash
# Check container logs
docker-compose logs [service-name]

# Check container status
docker-compose ps

# Restart specific service
docker-compose restart [service-name]
```

#### 2. Database Connection Issues

```bash
# Check PostgreSQL logs
docker-compose logs postgres

# Test database connection
docker-compose exec app php artisan tinker
# DB::connection()->getPdo();
```

#### 3. High Memory Usage

```bash
# Check memory usage
free -h
docker stats

# Restart services to free memory
docker-compose restart
```

#### 4. SSL Certificate Issues

```bash
# Check Caddy logs
docker-compose logs caddy

# Verify domain DNS
nslookup your-domain.com

# Check certificate status
openssl s_client -connect your-domain.com:443
```

### Performance Optimization

#### 1. Database Optimization

```bash
# Analyze slow queries
docker-compose exec postgres psql -U laravel laravel
# SELECT * FROM pg_stat_statements ORDER BY total_time DESC LIMIT 10;

# Optimize database
docker-compose exec app php artisan optimize:clear
docker-compose exec app php artisan config:cache
```

#### 2. Cache Optimization

```bash
# Clear all caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Rebuild caches
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

#### 3. Queue Optimization

```bash
# Check queue status
docker-compose exec app php artisan horizon:status

# Restart queue workers
docker-compose exec app php artisan horizon:terminate
docker-compose restart horizon
```

### Log Analysis

#### Application Logs

```bash
# View Laravel logs
docker-compose exec app tail -f storage/logs/laravel.log

# View specific log level
docker-compose exec app grep "ERROR" storage/logs/laravel.log
```

#### System Logs

```bash
# View system logs
sudo journalctl -f

# View Docker logs
sudo journalctl -u docker.service

# View specific service logs
sudo journalctl -u fail2ban.service
```

### Emergency Procedures

#### 1. Service Outage

```bash
# Quick restart all services
docker-compose restart

# Check service health
./health-check.sh

# Switch to backup environment
./blue-green-deploy.sh rollback
```

#### 2. Security Incident

```bash
# Check for intrusions
sudo rkhunter --check
sudo chkrootkit

# Review access logs
sudo grep "Failed password" /var/log/auth.log
sudo fail2ban-client status

# Block suspicious IPs
sudo ufw deny from [suspicious-ip]
```

#### 3. Data Recovery

```bash
# Stop services
docker-compose down

# Restore from backup
./scripts/restore-backup.sh [backup-file]

# Start services
docker-compose up -d

# Verify data integrity
docker-compose exec app php artisan migrate:status
```

## Support and Contacts

- **System Administrator**: admin@yourcompany.com
- **Development Team**: dev@yourcompany.com
- **Emergency Contact**: +1-XXX-XXX-XXXX

For additional support, refer to:
- Laravel Documentation: https://laravel.com/docs
- Docker Documentation: https://docs.docker.com
- Prometheus Documentation: https://prometheus.io/docs