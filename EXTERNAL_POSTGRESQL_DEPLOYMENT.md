# External PostgreSQL Cluster Deployment Guide

This guide covers deploying the Laravel Learning Center application with an external PostgreSQL cluster instead of a containerized database.

## 🏗️ Architecture Overview

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
│           │                                                 │
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

## 🔧 Prerequisites

### PostgreSQL Cluster Requirements
- PostgreSQL 14+ cluster with SSL/TLS enabled
- Dedicated database and user for the application
- Network connectivity from Docker host to cluster
- Firewall rules allowing connections on PostgreSQL port (default: 5432)

### Local Requirements
- Docker and Docker Compose
- `psql` client for database operations
- Network access to PostgreSQL cluster

## 📋 Configuration Steps

### 1. Database Setup

Connect to your PostgreSQL cluster and create the application database:

```sql
-- Connect as superuser
psql -h your-cluster-host.example.com -U postgres

-- Create database
CREATE DATABASE learningcenter_production;

-- Create application user
CREATE USER laravel_user WITH PASSWORD 'your_secure_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE learningcenter_production TO laravel_user;
GRANT ALL ON SCHEMA public TO laravel_user;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO laravel_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO laravel_user;

-- Enable required extensions
\c learningcenter_production
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
```

### 2. SSL Configuration

Ensure your PostgreSQL cluster has SSL enabled. Common SSL modes:

- `disable` - No SSL (not recommended for production)
- `require` - SSL required, but certificate not verified
- `verify-ca` - SSL required, CA certificate verified
- `verify-full` - SSL required, full certificate verification

### 3. Network Configuration

Ensure network connectivity:

```bash
# Test connectivity from Docker host
telnet your-cluster-host.example.com 5432

# Test PostgreSQL connection
psql -h your-cluster-host.example.com -p 5432 -U laravel_user -d learningcenter_production -c "SELECT version();"
```

### 4. Environment Variables

Set the required environment variables:

```bash
# Application
export APP_NAME="LearningCenter"
export APP_ENV="production"
export APP_KEY="base64:$(openssl rand -base64 32)"
export APP_URL="https://learningcenter.example.com"

# PostgreSQL Cluster
export DB_HOST="your-cluster-host.example.com"
export DB_PORT="5432"
export DB_DATABASE="learningcenter_production"
export DB_USERNAME="laravel_user"
export DB_PASSWORD="your_secure_password"
export DB_SSLMODE="require"

# Redis
export REDIS_PASSWORD="$(openssl rand -base64 32)"

# Other required variables...
```

## 🚀 Deployment Process

### Option 1: Automated Deployment Script

```bash
# Set environment variables (see above)
# Run deployment script
./scripts/deploy-production.sh v1.0.0
```

The script will:
1. ✅ Validate PostgreSQL cluster connectivity
2. ✅ Create database backup
3. ✅ Build and deploy application
4. ✅ Run migrations
5. ✅ Test connectivity

### Option 2: Manual Deployment

```bash
# 1. Test cluster connectivity
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT 1;"

# 2. Build application image
docker build -f Dockerfile.frankenphp.improved -t learningcenter:latest .

# 3. Deploy with Docker Compose
docker-compose -f docker-compose.production.yml up -d

# 4. Run migrations
docker-compose -f docker-compose.production.yml exec app php artisan migrate --force

# 5. Optimize application
docker-compose -f docker-compose.production.yml exec app php artisan optimize
```

## 🔍 Monitoring & Troubleshooting

### Health Checks

The application includes health checks for:
- PostgreSQL cluster connectivity
- Redis connectivity
- Application responsiveness

```bash
# Check application health
curl -f https://learningcenter.example.com/health

# Check container health
docker-compose -f docker-compose.production.yml ps
```

### Common Issues

#### Connection Refused
```
ERROR: PostgreSQL cluster connection failed
```

**Solutions:**
1. Verify cluster host and port
2. Check firewall rules
3. Ensure cluster is running
4. Test network connectivity

#### SSL Connection Issues
```
ERROR: SSL connection failed
```

**Solutions:**
1. Verify SSL mode configuration
2. Check certificate validity
3. Update `DB_SSLMODE` environment variable

#### Authentication Failed
```
ERROR: Authentication failed for user
```

**Solutions:**
1. Verify username and password
2. Check user permissions
3. Ensure database exists

### Debugging Commands

```bash
# View application logs
docker-compose -f docker-compose.production.yml logs -f app

# Test database connection from container
docker-compose -f docker-compose.production.yml exec app php artisan tinker
# In tinker: DB::connection()->getPdo();

# Check migration status
docker-compose -f docker-compose.production.yml exec app php artisan migrate:status

# Test PostgreSQL connection directly
docker-compose -f docker-compose.production.yml exec app psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE"
```

## 🔒 Security Considerations

### Database Security
- ✅ Use strong passwords for database users
- ✅ Enable SSL/TLS for all connections
- ✅ Restrict network access to cluster
- ✅ Regular security updates for PostgreSQL
- ✅ Monitor database access logs

### Application Security
- ✅ Environment variables injected at runtime
- ✅ No secrets stored in Docker images
- ✅ Non-root container execution
- ✅ Regular security scans

### Network Security
- ✅ VPC/private network for cluster communication
- ✅ Firewall rules restricting access
- ✅ SSL certificate validation
- ✅ Connection pooling and rate limiting

## 📊 Performance Optimization

### Database Optimization
```sql
-- Create indexes for better performance
CREATE INDEX CONCURRENTLY idx_users_email ON users(email);
CREATE INDEX CONCURRENTLY idx_institutions_domain ON institutions(domain);
CREATE INDEX CONCURRENTLY idx_courses_institution_id ON courses(institution_id);

-- Analyze tables for query optimization
ANALYZE;
```

### Connection Pooling
Consider using connection pooling for high-traffic applications:

```bash
# Environment variables for connection pooling
export DB_POOL_MIN=5
export DB_POOL_MAX=20
export DB_POOL_TIMEOUT=30
```

### Monitoring Queries
```sql
-- Enable query logging (adjust as needed)
ALTER SYSTEM SET log_statement = 'all';
ALTER SYSTEM SET log_min_duration_statement = 1000; -- Log queries > 1s
SELECT pg_reload_conf();
```

## 🔄 Backup & Recovery

### Automated Backups
The deployment script automatically creates backups:

```bash
# Manual backup
PGPASSWORD="$DB_PASSWORD" pg_dump \
    -h "$DB_HOST" \
    -p "$DB_PORT" \
    -U "$DB_USERNAME" \
    -d "$DB_DATABASE" \
    --no-password \
    > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Recovery Process
```bash
# Restore from backup
PGPASSWORD="$DB_PASSWORD" psql \
    -h "$DB_HOST" \
    -p "$DB_PORT" \
    -U "$DB_USERNAME" \
    -d "$DB_DATABASE" \
    < backup_file.sql
```

## 📈 Scaling Considerations

### Horizontal Scaling
- Multiple application containers
- Load balancer configuration
- Session storage in Redis
- Stateless application design

### Database Scaling
- Read replicas for read-heavy workloads
- Connection pooling
- Query optimization
- Partitioning for large tables

## 🎯 Best Practices

1. **Environment Separation**: Use different clusters for staging/production
2. **Monitoring**: Implement comprehensive monitoring for cluster and application
3. **Backup Strategy**: Regular automated backups with tested recovery procedures
4. **Security Updates**: Keep PostgreSQL cluster updated with security patches
5. **Performance Monitoring**: Monitor query performance and optimize as needed
6. **Documentation**: Maintain up-to-date documentation for cluster configuration

## 📞 Support

For issues related to:
- **Application**: Check application logs and health endpoints
- **Database**: Verify cluster connectivity and permissions
- **Network**: Test connectivity and firewall rules
- **SSL**: Validate certificates and SSL configuration

Refer to the main deployment documentation and PostgreSQL cluster documentation for additional troubleshooting steps.