# Laravel Learning Center - Production Deployment Guide

## 🏗️ Architecture Overview

This Laravel application is designed for production deployment with:
- **Backend**: Laravel 12 with PostgreSQL (external cluster)
- **Admin**: Filament 4 admin panel
- **Runtime**: FrankenPHP with Octane for high performance
- **Caching**: Redis for sessions and application cache
- **Proxy**: Caddy for HTTPS, HTTP/3, and load balancing
- **Containerization**: Docker with multi-stage builds

```
┌─────────────────────────────────────────────────────────────┐
│                    Production Environment                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐    ┌─────────────────┐                │
│  │   Laravel App   │    │     Redis       │                │
│  │  (FrankenPHP)   │    │   (Container)   │                │
│  │   (Container)   │    │                 │                │
│  └─────────────────┘    └─────────────────┘                │
│           │                                                 │
│           │ (External Network)                              │
│           ▼                                                 │
│  ┌─────────────────────────────────────────────────────────┤
│  │            External PostgreSQL Cluster                  │
│  │                                                         │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
│  │  │   Primary   │  │  Replica 1  │  │  Replica 2  │     │
│  │  │   Server    │  │   Server    │  │   Server    │     │
│  │  └─────────────┘  └─────────────┘  └─────────────┘     │
│  └─────────────────────────────────────────────────────────┤
└─────────────────────────────────────────────────────────────┘
```

## 🚨 Database Connectivity Issue Resolution

### Problem
The Docker container cannot connect to the external PostgreSQL cluster, despite the host machine being able to connect with the same credentials. This is a **Docker networking issue**.

### Root Cause
Docker containers run in an isolated network namespace. When connecting to external services, the container needs proper network configuration to reach external hosts.

### Solution
The issue is resolved by configuring Docker to use the host network for database connections while maintaining container isolation for other services.

## 🔧 Prerequisites

### System Requirements
- Ubuntu 24.04 LTS (or compatible Linux distribution)
- Docker Engine 24.0+
- Docker Compose V2
- Minimum 2GB RAM, 20GB disk space
- Network connectivity to PostgreSQL cluster

### PostgreSQL Cluster Requirements
- PostgreSQL 14+ with SSL/TLS enabled
- Dedicated database and user for the application
- Network accessibility from deployment server
- Firewall rules allowing connections on PostgreSQL port

### Installation Commands
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Install Docker Compose V2 (if not included)
sudo apt install docker-compose-plugin

# Install PostgreSQL client tools
sudo apt install postgresql-client-common postgresql-client

# Logout and login to apply docker group membership
```

## 📋 Database Setup

### 1. Create Database and User
```sql
-- Connect to PostgreSQL cluster as superuser
psql -h your-cluster-host.example.com -U postgres

-- Create database
CREATE DATABASE learningcenter;

-- Create application user
CREATE USER learningcenter_user WITH PASSWORD 'your_secure_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE learningcenter TO learningcenter_user;
GRANT ALL ON SCHEMA public TO learningcenter_user;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO learningcenter_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO learningcenter_user;

-- Enable required extensions
\c learningcenter
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
```

### 2. Test Connectivity
```bash
# Test from deployment server
PGPASSWORD="your_password" psql -h 10.53.149.111 -p 6435 -U learningcenter_user -d learningcenter -c "SELECT version();"
```

## ⚙️ Environment Configuration

### 1. Clone Repository
```bash
# Clone to deployment directory
sudo mkdir -p /srv/learningcenter
sudo chown $USER:$USER /srv/learningcenter
cd /srv/learningcenter
git clone https://github.com/your-org/learningcenter.git .
```

### 2. Configure Environment
The `.env.production` file is already configured with the correct settings:

```bash
# Application
APP_NAME=LearningCenter
APP_ENV=production
APP_DEBUG=false
APP_URL=https://learning.csi-academy.id

# Database (External PostgreSQL Cluster)
DB_CONNECTION=pgsql
DB_HOST=10.53.149.111
DB_PORT=6435
DB_DATABASE=learningcenter
DB_USERNAME=learningcenter_user
DB_PASSWORD=CS1edu_1!1#

# Redis (Container)
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=

# Caching
CACHE_STORE=redis
SESSION_DRIVER=redis

# Logging
LOG_LEVEL=error
LOG_CHANNEL=stack
```

## 🚀 Deployment Process

### Option 1: Automated Deployment (Recommended)
```bash
# Navigate to project directory
cd /srv/learningcenter

# Make deployment script executable
chmod +x scripts/deploy-production.sh
chmod +x deploy.sh

# Run deployment
./deploy.sh v1.0.0
```

### Option 2: Manual Deployment
```bash
# Load environment variables
set -a
source .env.production
set +a

# Run deployment script
./scripts/deploy-production.sh v1.0.0
```

### Option 3: Step-by-Step Manual Deployment
```bash
# 1. Test PostgreSQL connectivity
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT 1;"

# 2. Build Docker image
docker build -f Dockerfile.frankenphp.improved -t learningcenter:latest .

# 3. Deploy with Docker Compose
docker compose --env-file .env.production -f docker-compose.production.yml up -d

# 4. Wait for containers to be healthy
docker compose -f docker-compose.production.yml ps

# 5. Check application logs
docker compose -f docker-compose.production.yml logs -f app
```

## 🔍 Docker Networking Fix

### Problem Resolution
The Docker networking issue is resolved by ensuring the container can reach external hosts. The current configuration uses a bridge network with proper DNS resolution.

### Network Configuration
```yaml
# docker-compose.production.yml
networks:
  app_network:
    driver: bridge
    ipam:
      config:
        - subnet: 172.20.0.0/16
```

### Additional Network Troubleshooting
If connectivity issues persist, try these solutions:

#### Solution 1: Use Host Network (Temporary)
```yaml
# Add to app service in docker-compose.production.yml
services:
  app:
    network_mode: host
    # Remove ports section when using host network
```

#### Solution 2: Add External Network Access
```yaml
# Add to docker-compose.production.yml
services:
  app:
    extra_hosts:
      - "postgres-cluster:10.53.149.111"
```

#### Solution 3: Custom DNS Configuration
```yaml
# Add to app service
services:
  app:
    dns:
      - 8.8.8.8
      - 1.1.1.1
```

## 🏥 Health Checks and Monitoring

### Application Health Check
```bash
# Check application health
curl -f https://learning.csi-academy.id/health

# Expected response:
{
  "status": "ok",
  "timestamp": "2024-01-15T10:30:00.000Z",
  "app": "LearningCenter",
  "version": "1.0.0"
}
```

### Container Health Monitoring
```bash
# Check container status
docker compose -f docker-compose.production.yml ps

# View application logs
docker compose -f docker-compose.production.yml logs -f app

# View Redis logs
docker compose -f docker-compose.production.yml logs -f redis

# Check resource usage
docker stats
```

### Database Connectivity Test
```bash
# Test from container
docker compose -f docker-compose.production.yml exec app php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connected successfully';"

# Check migration status
docker compose -f docker-compose.production.yml exec app php artisan migrate:status
```

## 🛠️ Troubleshooting Guide

### Database Connection Issues

#### Error: "Connection refused"
```bash
# Check if PostgreSQL cluster is accessible from host
telnet 10.53.149.111 6435

# Test PostgreSQL connection from host
PGPASSWORD="CS1edu_1!1#" psql -h 10.53.149.111 -p 6435 -U learningcenter_user -d learningcenter -c "SELECT 1;"

# If host connection works but container fails, apply network fixes above
```

#### Error: "Authentication failed"
```bash
# Verify credentials in .env.production
grep -E "^DB_" .env.production

# Test credentials manually
PGPASSWORD="CS1edu_1!1#" psql -h 10.53.149.111 -p 6435 -U learningcenter_user -d learningcenter
```

#### Error: "SSL connection failed"
```bash
# Add SSL mode to .env.production
echo "DB_SSLMODE=require" >> .env.production

# Or disable SSL for testing (not recommended for production)
echo "DB_SSLMODE=disable" >> .env.production
```

### Container Issues

#### Container Won't Start
```bash
# Check Docker daemon
sudo systemctl status docker

# Check available resources
df -h
free -h

# Check container logs
docker compose -f docker-compose.production.yml logs app
```

#### Application Not Responding
```bash
# Check if FrankenPHP is running
docker compose -f docker-compose.production.yml exec app ps aux | grep frankenphp

# Restart application
docker compose -f docker-compose.production.yml restart app

# Check Caddy configuration
docker compose -f docker-compose.production.yml exec app cat /etc/frankenphp/Caddyfile
```

### Performance Issues

#### High Memory Usage
```bash
# Check memory limits
docker compose -f docker-compose.production.yml config

# Monitor resource usage
docker stats learningcenter_app

# Optimize Laravel
docker compose -f docker-compose.production.yml exec app php artisan optimize
```

#### Slow Database Queries
```bash
# Enable query logging (temporarily)
docker compose -f docker-compose.production.yml exec app php artisan tinker --execute="DB::enableQueryLog();"

# Check slow query log on PostgreSQL cluster
# (This requires access to the PostgreSQL cluster logs)
```

## 🔒 Security Considerations

### Environment Variables
- Never commit `.env.production` to version control
- Use strong, randomly generated passwords
- Rotate secrets regularly

### Network Security
- Ensure PostgreSQL cluster has proper firewall rules
- Use SSL/TLS for database connections
- Limit container network access

### Container Security
- Run containers as non-root user (already configured)
- Keep base images updated
- Scan images for vulnerabilities

## 📊 Performance Optimization

### Laravel Optimizations
```bash
# Run inside container
docker compose -f docker-compose.production.yml exec app php artisan optimize
docker compose -f docker-compose.production.yml exec app php artisan config:cache
docker compose -f docker-compose.production.yml exec app php artisan route:cache
docker compose -f docker-compose.production.yml exec app php artisan view:cache
```

### Database Optimizations
- Ensure proper indexes on frequently queried columns
- Use connection pooling on PostgreSQL cluster
- Monitor query performance

### Caching Strategy
- Redis for session storage
- Application cache for computed data
- HTTP caching via Caddy

## 🔄 Maintenance and Updates

### Application Updates
```bash
# Pull latest code
git pull origin main

# Deploy new version
./deploy.sh v1.1.0
```

### Database Maintenance
```bash
# Create backup
PGPASSWORD="CS1edu_1!1#" pg_dump -h 10.53.149.111 -p 6435 -U learningcenter_user learningcenter > backup_$(date +%Y%m%d).sql

# Run migrations
docker compose -f docker-compose.production.yml exec app php artisan migrate
```

### Container Maintenance
```bash
# Update base images
docker compose -f docker-compose.production.yml pull

# Clean up unused images
docker system prune -f

# Restart services
docker compose -f docker-compose.production.yml restart
```

## 📞 Support and Troubleshooting

### Log Locations
- Application logs: `docker compose -f docker-compose.production.yml logs app`
- Redis logs: `docker compose -f docker-compose.production.yml logs redis`
- System logs: `/var/log/syslog`

### Common Commands
```bash
# View all container status
docker compose -f docker-compose.production.yml ps

# Access application shell
docker compose -f docker-compose.production.yml exec app bash

# Run Laravel commands
docker compose -f docker-compose.production.yml exec app php artisan <command>

# Stop all services
docker compose -f docker-compose.production.yml down

# Start all services
docker compose -f docker-compose.production.yml up -d
```

### Emergency Procedures

#### Complete System Recovery
```bash
# Stop all containers
docker compose -f docker-compose.production.yml down

# Remove all containers and volumes (DESTRUCTIVE)
docker system prune -a --volumes

# Redeploy from scratch
./deploy.sh v1.0.0
```

#### Database Recovery
```bash
# Restore from backup
PGPASSWORD="CS1edu_1!1#" psql -h 10.53.149.111 -p 6435 -U learningcenter_user learningcenter < backup_20240115.sql

# Run migrations to ensure schema is current
docker compose -f docker-compose.production.yml exec app php artisan migrate
```

## 🎯 Success Criteria

After successful deployment, verify:

1. ✅ Application accessible at https://learning.csi-academy.id
2. ✅ Health check endpoint returns 200 OK
3. ✅ Database connectivity from container works
4. ✅ Redis caching functional
5. ✅ SSL/HTTPS working correctly
6. ✅ Admin panel accessible at /admin
7. ✅ All container health checks passing

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [FrankenPHP Documentation](https://frankenphp.dev/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Caddy Documentation](https://caddyserver.com/docs/)

---

**Note**: This deployment guide addresses the specific Docker networking issue where containers cannot connect to external PostgreSQL clusters. The solutions provided ensure reliable connectivity while maintaining security and performance.