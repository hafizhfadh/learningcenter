# External Services Configuration

This document describes the configuration for external PostgreSQL and Redis servers used in the production environment.

## Overview

The production deployment has been configured to use external PostgreSQL and Redis servers instead of containerized versions. This provides better performance, scalability, and separation of concerns.

## Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Node 1        │    │   Node 2        │    │   Node N        │
│                 │    │                 │    │                 │
│ ┌─────────────┐ │    │ ┌─────────────┐ │    │ ┌─────────────┐ │
│ │   Nginx     │ │    │ │   Nginx     │ │    │ │   Nginx     │ │
│ └─────────────┘ │    │ └─────────────┘ │    │ └─────────────┘ │
│ ┌─────────────┐ │    │ ┌─────────────┐ │    │ ┌─────────────┐ │
│ │ Docker      │ │    │ │ Docker      │ │    │ │ Docker      │ │
│ │ - Caddy     │ │    │ │ - Caddy     │ │    │ │ - Caddy     │ │
│ │ - App       │ │    │ │ - App       │ │    │ │ - App       │ │
│ │ - Horizon   │ │    │ │ - Horizon   │ │    │ │ - Horizon   │ │
│ │ - Scheduler │ │    │ │ - Scheduler │ │    │ │ - Scheduler │ │
│ └─────────────┘ │    │ └─────────────┘ │    │ └─────────────┘ │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 │
         ┌───────────────────────┼───────────────────────┐
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│ PostgreSQL      │    │ Redis Server    │    │ Other Services  │
│ Server          │    │                 │    │ (Monitoring,    │
│                 │    │                 │    │  Logging, etc.) │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## Configuration

### Environment Variables

Update your `.env.production` file with the external server details:

```bash
# External Database Configuration (PostgreSQL Server)
DB_CONNECTION=pgsql
DB_HOST=your-postgres-server.example.com
DB_PORT=5432
DB_DATABASE=laravel_production
DB_USERNAME=laravel
DB_PASSWORD=your-secure-database-password
DB_SSLMODE=require

# External Redis Configuration (Redis Server)
REDIS_HOST=your-redis-server.example.com
REDIS_PASSWORD=your-secure-redis-password
REDIS_PORT=6379
REDIS_DB=0
```

### Docker Compose Changes

The production Docker Compose file now only includes:
- **Caddy**: Reverse proxy and load balancer
- **App**: Laravel application container
- **Horizon**: Queue worker
- **Scheduler**: Task scheduler

Removed services:
- PostgreSQL (now external)
- Redis (now external)
- Nginx (now configured at node level)
- SSL/Certbot (handled by node-level Nginx)

## Prerequisites

### PostgreSQL Server Setup

1. **Install PostgreSQL** on your database server
2. **Create database and user**:
   ```sql
   CREATE DATABASE laravel_production;
   CREATE USER laravel WITH ENCRYPTED PASSWORD 'your-secure-password';
   GRANT ALL PRIVILEGES ON DATABASE laravel_production TO laravel;
   ```
3. **Configure SSL** (recommended for production)
4. **Configure firewall** to allow connections from application nodes
5. **Tune performance** settings based on your workload

### Redis Server Setup

1. **Install Redis** on your cache server
2. **Configure authentication**:
   ```
   requirepass your-secure-redis-password
   ```
3. **Configure persistence** (if needed)
4. **Configure firewall** to allow connections from application nodes
5. **Tune memory** and performance settings

### Node-level Nginx Setup

Each application node should have Nginx installed and configured:

1. **Install Nginx**:
   ```bash
   sudo apt update
   sudo apt install nginx
   ```

2. **Configure upstream** (handled by deployment scripts):
   ```nginx
   upstream laravel_app {
       server 127.0.0.1:8001 max_fails=3 fail_timeout=30s;
       keepalive 32;
   }
   ```

3. **Configure SSL certificates** using Let's Encrypt or your preferred method

## Deployment Scripts

The following scripts have been updated for external services:

### deploy.sh
- Database backup now connects to external PostgreSQL server
- Database restore uses external server connection
- Loads environment variables for connection details

### health-check.sh
- Checks external PostgreSQL and Redis connectivity
- Removed Docker container health checks for database services
- Uses external server credentials for health checks

### blue-green-deploy.sh
- Removed PostgreSQL and Redis service definitions
- Updated dependencies to remove database services
- Modified Nginx configuration to use node-level setup

## Security Considerations

1. **Network Security**:
   - Use private networks for database connections
   - Configure firewalls to restrict access
   - Use VPN or private networking when possible

2. **Authentication**:
   - Use strong passwords for database and Redis
   - Consider certificate-based authentication
   - Rotate credentials regularly

3. **Encryption**:
   - Enable SSL/TLS for PostgreSQL connections
   - Use Redis AUTH for authentication
   - Encrypt data in transit and at rest

4. **Monitoring**:
   - Monitor database and Redis server performance
   - Set up alerts for connection failures
   - Log access attempts and failures

## Troubleshooting

### Database Connection Issues

1. **Check connectivity**:
   ```bash
   PGPASSWORD="password" pg_isready -h host -p 5432 -U username
   ```

2. **Test connection**:
   ```bash
   PGPASSWORD="password" psql -h host -p 5432 -U username -d database
   ```

3. **Check firewall rules**
4. **Verify SSL configuration**

### Redis Connection Issues

1. **Check connectivity**:
   ```bash
   redis-cli -h host -p 6379 -a password ping
   ```

2. **Test authentication**:
   ```bash
   redis-cli -h host -p 6379 -a password info
   ```

3. **Check firewall rules**
4. **Verify Redis configuration**

## Performance Optimization

### PostgreSQL
- Configure `shared_buffers`, `work_mem`, and `maintenance_work_mem`
- Set up connection pooling (PgBouncer)
- Monitor slow queries and optimize indexes
- Configure appropriate `max_connections`

### Redis
- Configure `maxmemory` and eviction policies
- Use appropriate persistence settings
- Monitor memory usage and key expiration
- Consider Redis Cluster for high availability

## Backup and Recovery

### Database Backups
- Automated backups are handled by deployment scripts
- Backups connect directly to external PostgreSQL server
- Consider setting up streaming replication for high availability

### Redis Backups
- Configure Redis persistence (RDB/AOF)
- Set up regular snapshots
- Consider Redis Sentinel for high availability

## Monitoring

Ensure your monitoring stack includes:
- Database server metrics (CPU, memory, disk, connections)
- Redis server metrics (memory usage, hit rate, connections)
- Network connectivity between services
- Application-level database and cache performance metrics