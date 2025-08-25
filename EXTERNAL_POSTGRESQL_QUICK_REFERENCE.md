# External PostgreSQL Deployment - Quick Reference

## 🚀 Quick Start

### 1. Set Environment Variables
```bash
# Required PostgreSQL Cluster Variables
export DB_HOST="your-cluster-host.example.com"
export DB_PORT="5432"
export DB_DATABASE="learningcenter_production"
export DB_USERNAME="laravel_user"
export DB_PASSWORD="your_secure_password"
export DB_SSLMODE="require"

# Other required variables
export APP_KEY="base64:$(openssl rand -base64 32)"
export REDIS_PASSWORD="$(openssl rand -base64 32)"
```

### 2. Test Connectivity
```bash
# Quick connectivity test
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT version();"
```

### 3. Deploy
```bash
# Automated deployment
./scripts/deploy-production.sh v1.0.0

# OR Manual deployment
docker-compose -f docker-compose.production.yml up -d
```

## 🔧 Key Configuration Files

| File | Purpose | Key Changes |
|------|---------|-------------|
| `docker-compose.production.yml` | Orchestration | Removed PostgreSQL service, external DB config |
| `startup.sh` | Runtime initialization | External DB connectivity checks |
| `deploy-production.sh` | Deployment automation | PostgreSQL cluster validation |
| `.env.production.example` | Environment template | External cluster variables |

## 🔍 Health Checks

```bash
# Application health
curl -f https://your-app.example.com/health

# Container status
docker-compose -f docker-compose.production.yml ps

# Database connectivity from container
docker-compose -f docker-compose.production.yml exec app php artisan tinker
# In tinker: DB::connection()->getPdo();
```

## 🚨 Troubleshooting

### Connection Issues
```bash
# Test network connectivity
telnet $DB_HOST $DB_PORT

# Test PostgreSQL connection
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT 1;"

# Check application logs
docker-compose -f docker-compose.production.yml logs -f app
```

### Common Error Solutions

| Error | Solution |
|-------|----------|
| `Connection refused` | Check firewall, cluster status, network connectivity |
| `SSL connection failed` | Verify `DB_SSLMODE`, check certificates |
| `Authentication failed` | Verify username/password, check user permissions |
| `Database does not exist` | Create database, verify `DB_DATABASE` variable |

## 🔒 Security Checklist

- [ ] Strong database passwords
- [ ] SSL/TLS enabled (`DB_SSLMODE=require`)
- [ ] Firewall rules restricting cluster access
- [ ] Environment variables injected at runtime (not baked in image)
- [ ] Non-root container execution
- [ ] Regular security updates

## 📊 Monitoring Commands

```bash
# View real-time logs
docker-compose -f docker-compose.production.yml logs -f

# Check resource usage
docker stats

# Database performance
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "
  SELECT query, calls, total_time, mean_time 
  FROM pg_stat_statements 
  ORDER BY total_time DESC 
  LIMIT 10;"
```

## 🔄 Backup & Recovery

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

## 🎯 Performance Tips

- Use connection pooling for high traffic
- Monitor slow queries with `pg_stat_statements`
- Create appropriate indexes
- Regular `ANALYZE` for query optimization
- Consider read replicas for read-heavy workloads

## 📞 Emergency Contacts

- **Application Issues**: Check logs, health endpoints
- **Database Issues**: Verify cluster status, connectivity
- **Network Issues**: Test firewall rules, DNS resolution
- **SSL Issues**: Validate certificates, SSL mode configuration

---

**📖 Full Documentation**: See `EXTERNAL_POSTGRESQL_DEPLOYMENT.md` for comprehensive guide.