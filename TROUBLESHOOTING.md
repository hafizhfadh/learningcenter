# Troubleshooting Guide - Laravel Learning Center

This guide helps diagnose and resolve common issues with the Laravel Learning Center deployment, particularly database connectivity problems.

## Table of Contents

- [Quick Diagnostics](#quick-diagnostics)
- [Database Connectivity Issues](#database-connectivity-issues)
- [Docker Container Issues](#docker-container-issues)
- [Network Configuration Problems](#network-configuration-problems)
- [Environment Configuration Issues](#environment-configuration-issues)
- [SSL/TLS Certificate Issues](#ssltls-certificate-issues)
- [Performance Issues](#performance-issues)
- [Security Issues](#security-issues)
- [Logging and Monitoring](#logging-and-monitoring)
- [Common Error Messages](#common-error-messages)

## Quick Diagnostics

### Run Validation Script

First, run our comprehensive validation script:

```bash
./scripts/validate-deployment.sh
```

### Run Troubleshooting Script

For detailed diagnostics:

```bash
./scripts/deploy-production.sh --troubleshoot
```

### Check Container Status

```bash
docker compose -f docker-compose.production.yml ps
docker compose -f docker-compose.production.yml logs app
```

## Database Connectivity Issues

### Symptom: "Connection refused" or "Connection timeout"

**Possible Causes:**
1. PostgreSQL cluster is not accessible from Docker container
2. Network configuration issues
3. Firewall blocking connections
4. Incorrect database credentials

**Diagnostic Steps:**

1. **Test from host machine:**
   ```bash
   # Load environment variables
   source .env.production
   
   # Test PostgreSQL connectivity
   PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT 1;"
   ```

2. **Test DNS resolution:**
   ```bash
   nslookup $DB_HOST
   dig $DB_HOST
   ```

3. **Test network connectivity:**
   ```bash
   nc -zv $DB_HOST $DB_PORT
   telnet $DB_HOST $DB_PORT
   ```

4. **Test from container:**
   ```bash
   docker compose -f docker-compose.production.yml exec app bash
   # Inside container:
   nc -zv $DB_HOST $DB_PORT
   nslookup $DB_HOST
   ```

**Solutions:**

1. **Fix Docker networking:**
   - Ensure `docker-compose.production.yml` has proper network configuration
   - Add DNS servers if needed:
     ```yaml
     services:
       app:
         dns:
           - 8.8.8.8
           - 8.8.4.4
     ```

2. **Configure host networking (if needed):**
   ```yaml
   services:
     app:
       network_mode: host
   ```

3. **Add extra hosts:**
   ```yaml
   services:
     app:
       extra_hosts:
         - "database.example.com:10.53.149.111"
   ```

### Symptom: "Authentication failed"

**Diagnostic Steps:**

1. **Verify credentials:**
   ```bash
   grep -E "^DB_" .env.production
   ```

2. **Test credentials manually:**
   ```bash
   PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE"
   ```

**Solutions:**

1. **Update credentials in `.env.production`**
2. **Check PostgreSQL user permissions**
3. **Verify database exists**

### Symptom: "SSL connection required"

**Diagnostic Steps:**

1. **Check SSL configuration:**
   ```bash
   grep -E "^DB_SSL" .env.production
   ```

**Solutions:**

1. **Configure SSL in `.env.production`:**
   ```env
   DB_SSLMODE=require
   DB_SSLCERT=/path/to/client-cert.pem
   DB_SSLKEY=/path/to/client-key.pem
   DB_SSLROOTCERT=/path/to/ca-cert.pem
   ```

2. **Mount SSL certificates in Docker:**
   ```yaml
   services:
     app:
       volumes:
         - ./ssl:/app/ssl:ro
   ```

## Docker Container Issues

### Symptom: Container fails to start

**Diagnostic Steps:**

1. **Check container logs:**
   ```bash
   docker compose -f docker-compose.production.yml logs app
   ```

2. **Check container status:**
   ```bash
   docker compose -f docker-compose.production.yml ps
   ```

3. **Test container manually:**
   ```bash
   docker run -it --rm laravel-learning-center:latest bash
   ```

**Solutions:**

1. **Fix environment variables**
2. **Check file permissions**
3. **Verify Docker image build**

### Symptom: Health check failing

**Diagnostic Steps:**

1. **Check health endpoint:**
   ```bash
   curl -f http://localhost:8080/health
   ```

2. **Check from inside container:**
   ```bash
   docker compose -f docker-compose.production.yml exec app curl -f http://localhost:8080/health
   ```

**Solutions:**

1. **Fix application configuration**
2. **Check database connectivity**
3. **Verify Redis connectivity**

## Network Configuration Problems

### Symptom: DNS resolution fails

**Diagnostic Steps:**

1. **Test DNS from host:**
   ```bash
   nslookup $DB_HOST
   ```

2. **Test DNS from container:**
   ```bash
   docker compose -f docker-compose.production.yml exec app nslookup $DB_HOST
   ```

**Solutions:**

1. **Configure DNS in Docker Compose:**
   ```yaml
   services:
     app:
       dns:
         - 8.8.8.8
         - 1.1.1.1
   ```

2. **Use IP address instead of hostname**

### Symptom: Port not accessible

**Diagnostic Steps:**

1. **Check port binding:**
   ```bash
   netstat -tlnp | grep :8080
   ```

2. **Test port connectivity:**
   ```bash
   nc -zv localhost 8080
   ```

**Solutions:**

1. **Fix port mapping in Docker Compose**
2. **Check firewall rules**
3. **Verify application is listening on correct interface**

## Environment Configuration Issues

### Symptom: Missing environment variables

**Diagnostic Steps:**

1. **Check environment file:**
   ```bash
   cat .env.production
   ```

2. **Verify variables in container:**
   ```bash
   docker compose -f docker-compose.production.yml exec app env | grep DB_
   ```

**Solutions:**

1. **Add missing variables to `.env.production`**
2. **Verify file is mounted correctly**
3. **Check file permissions**

### Symptom: Invalid configuration values

**Diagnostic Steps:**

1. **Run validation script:**
   ```bash
   ./scripts/validate-deployment.sh
   ```

2. **Check Laravel configuration:**
   ```bash
   docker compose -f docker-compose.production.yml exec app php artisan config:show database
   ```

**Solutions:**

1. **Fix configuration values**
2. **Clear configuration cache:**
   ```bash
   docker compose -f docker-compose.production.yml exec app php artisan config:clear
   ```

## SSL/TLS Certificate Issues

### Symptom: SSL certificate verification failed

**Diagnostic Steps:**

1. **Test SSL connection:**
   ```bash
   openssl s_client -connect $DB_HOST:$DB_PORT -servername $DB_HOST
   ```

2. **Check certificate files:**
   ```bash
   ls -la ssl/
   openssl x509 -in ssl/client-cert.pem -text -noout
   ```

**Solutions:**

1. **Update certificate files**
2. **Configure SSL mode:**
   ```env
   DB_SSLMODE=prefer  # or 'require' for strict SSL
   ```

3. **Disable SSL verification (not recommended for production):**
   ```env
   DB_SSLMODE=disable
   ```

## Performance Issues

### Symptom: Slow database queries

**Diagnostic Steps:**

1. **Enable query logging:**
   ```env
   DB_LOG_QUERIES=true
   LOG_LEVEL=debug
   ```

2. **Check database performance:**
   ```sql
   SELECT * FROM pg_stat_activity WHERE state = 'active';
   ```

**Solutions:**

1. **Optimize database queries**
2. **Add database indexes**
3. **Configure connection pooling**
4. **Increase database resources**

### Symptom: High memory usage

**Diagnostic Steps:**

1. **Check container resources:**
   ```bash
   docker stats
   ```

2. **Monitor application memory:**
   ```bash
   docker compose -f docker-compose.production.yml exec app php artisan octane:status
   ```

**Solutions:**

1. **Increase container memory limits**
2. **Optimize application code**
3. **Configure Octane workers**

## Security Issues

### Symptom: Security warnings in logs

**Diagnostic Steps:**

1. **Check security configuration:**
   ```bash
   ./scripts/validate-deployment.sh
   ```

2. **Review application logs:**
   ```bash
   docker compose -f docker-compose.production.yml logs app | grep -i security
   ```

**Solutions:**

1. **Disable debug mode:**
   ```env
   APP_DEBUG=false
   ```

2. **Configure HTTPS:**
   ```env
   APP_URL=https://your-domain.com
   ```

3. **Set secure session configuration:**
   ```env
   SESSION_SECURE_COOKIE=true
   ```

## Logging and Monitoring

### Enable Debug Logging

```env
LOG_LEVEL=debug
DB_LOG_QUERIES=true
```

### View Application Logs

```bash
# All logs
docker compose -f docker-compose.production.yml logs -f

# App logs only
docker compose -f docker-compose.production.yml logs -f app

# Laravel logs
docker compose -f docker-compose.production.yml exec app tail -f storage/logs/laravel.log
```

### Monitor Container Health

```bash
# Container status
docker compose -f docker-compose.production.yml ps

# Resource usage
docker stats

# Health check
curl -f http://localhost:8080/health
```

## Common Error Messages

### "SQLSTATE[08006] [7] could not connect to server"

**Cause:** PostgreSQL server is not accessible

**Solution:**
1. Check network connectivity
2. Verify database server is running
3. Check firewall rules

### "SQLSTATE[08P01] [7] FATAL: password authentication failed"

**Cause:** Invalid database credentials

**Solution:**
1. Verify username and password in `.env.production`
2. Check PostgreSQL user permissions

### "SQLSTATE[08006] [7] FATAL: SSL connection is required"

**Cause:** SSL is required but not configured

**Solution:**
1. Configure SSL in `.env.production`
2. Mount SSL certificates in Docker container

### "Connection refused"

**Cause:** Network connectivity issue

**Solution:**
1. Check Docker networking configuration
2. Verify port accessibility
3. Check DNS resolution

### "No route to host"

**Cause:** Network routing issue

**Solution:**
1. Check network configuration
2. Verify host accessibility
3. Check firewall rules

## Getting Help

If you're still experiencing issues:

1. **Run the troubleshooting script:**
   ```bash
   ./scripts/deploy-production.sh --troubleshoot
   ```

2. **Collect diagnostic information:**
   - Container logs
   - Network configuration
   - Environment variables (sanitized)
   - Error messages

3. **Check the deployment documentation:**
   - [DEPLOYMENT.md](./DEPLOYMENT.md)
   - [README.md](./README.md)

4. **Contact support with:**
   - Detailed error messages
   - Steps to reproduce
   - Environment information
   - Diagnostic output

---

**Remember:** Always sanitize sensitive information (passwords, keys) before sharing logs or configuration files.