# Backup and Disaster Recovery Guide

## Overview

The Learning Center application includes a comprehensive backup and disaster recovery system designed to ensure business continuity and data protection. This guide covers backup strategies, disaster recovery procedures, and operational best practices.

## Table of Contents

1. [Backup System Overview](#backup-system-overview)
2. [Backup Types and Schedules](#backup-types-and-schedules)
3. [Setup and Configuration](#setup-and-configuration)
4. [Manual Backup Operations](#manual-backup-operations)
5. [Disaster Recovery Procedures](#disaster-recovery-procedures)
6. [Monitoring and Alerting](#monitoring-and-alerting)
7. [Testing and Validation](#testing-and-validation)
8. [Troubleshooting](#troubleshooting)
9. [Security Considerations](#security-considerations)
10. [Compliance and Retention](#compliance-and-retention)

## Backup System Overview

### Components

- **PostgreSQL Database**: Full and incremental backups with point-in-time recovery
- **Redis Cache**: Data snapshots and AOF backups
- **Application Files**: Source code, configurations, and user uploads
- **System Configuration**: Docker configs, SSL certificates, environment files

### Features

- **Automated Scheduling**: Systemd timers and cron jobs
- **Encryption**: AES-256 encryption for all backup data
- **Compression**: Gzip compression to reduce storage requirements
- **Remote Storage**: Support for S3, Azure Blob, Google Cloud Storage
- **Integrity Verification**: Checksums and validation for all backups
- **Monitoring**: Real-time alerts and health checks
- **Retention Policies**: Configurable retention with automatic cleanup

## Backup Types and Schedules

### Full System Backup
- **Frequency**: Weekly (Sunday 2:00 AM)
- **Includes**: Database, Redis, application files, configurations
- **Retention**: 4 weeks (configurable)
- **Storage**: Local + Remote

### Database Backup
- **Frequency**: Every 6 hours
- **Type**: pg_dump with custom format
- **Features**: Point-in-time recovery capability
- **Retention**: 30 days (configurable)

### Incremental Backup
- **Frequency**: Daily
- **Includes**: Changed files only
- **Base**: Latest full backup
- **Retention**: 7 days

### Configuration Backup
- **Frequency**: On change detection
- **Includes**: Docker configs, SSL certs, environment files
- **Retention**: 90 days

## Setup and Configuration

### Prerequisites

```bash
# Required packages
sudo apt-get update
sudo apt-get install -y postgresql-client redis-tools awscli jq curl

# For email alerts
sudo apt-get install -y mailutils postfix
```

### Initial Setup

1. **Configure backup settings**:
   ```bash
   # Edit backup configuration
   cp docker/production/backup-config.env.example docker/production/backup-config.env
   nano docker/production/backup-config.env
   ```

2. **Set up automation** (requires root):
   ```bash
   sudo ./scripts/setup-backup-automation.sh
   ```

3. **Configure remote storage**:
   ```bash
   # For AWS S3
   aws configure
   
   # For Azure (install Azure CLI first)
   az login
   
   # For Google Cloud (install gcloud first)
   gcloud auth login
   ```

### Configuration Options

Key settings in `backup-config.env`:

```bash
# Backup locations
BACKUP_BASE_DIR="/var/backups/learning-center"
REMOTE_BACKUP_ENABLED="true"
S3_BUCKET="learning-center-backups"

# Encryption
BACKUP_ENCRYPTION_ENABLED="true"
ENCRYPTION_KEY_FILE="/etc/learning-center/backup-encryption.key"

# Retention policies
FULL_BACKUP_RETENTION_DAYS="28"
DB_BACKUP_RETENTION_DAYS="30"
INCREMENTAL_BACKUP_RETENTION_DAYS="7"

# Monitoring
WEBHOOK_ENABLED="true"
WEBHOOK_URL="https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK"
EMAIL_ALERTS_ENABLED="true"
ALERT_EMAIL="admin@yourcompany.com"
```

## Manual Backup Operations

### Create Backups

```bash
# Full system backup
./scripts/backup-system.sh backup full

# Database only
./scripts/backup-system.sh backup database

# Application files only
./scripts/backup-system.sh backup files

# Configuration only
./scripts/backup-system.sh backup config
```

### List Backups

```bash
# List all backups
./scripts/backup-system.sh list

# List with details
./scripts/backup-system.sh list --detailed

# Filter by type
./scripts/backup-system.sh list --type database
```

### Verify Backups

```bash
# Verify specific backup
./scripts/backup-system.sh verify BACKUP_ID

# Verify all recent backups
./scripts/validate-backups.sh

# Test restore (dry run)
./scripts/backup-system.sh test-restore BACKUP_ID
```

### Cleanup Operations

```bash
# Clean old backups (respects retention policies)
./scripts/backup-system.sh cleanup

# Force cleanup (removes all backups older than specified days)
./scripts/backup-system.sh cleanup --force --days 7

# Clean remote backups only
./scripts/backup-system.sh cleanup --remote-only
```

## Disaster Recovery Procedures

### Emergency Response

1. **Assess the situation**:
   ```bash
   # Check system health
   ./scripts/disaster-recovery.sh health
   
   # Generate emergency report
   ./scripts/disaster-recovery.sh emergency-report
   ```

2. **Alert stakeholders**:
   ```bash
   # Send emergency alerts
   ./scripts/disaster-recovery.sh alert emergency "System failure detected"
   ```

### Recovery Scenarios

#### Complete System Failure

```bash
# 1. Prepare new environment
./scripts/disaster-recovery.sh prepare-environment

# 2. Restore from latest backup
./scripts/disaster-recovery.sh restore full --backup-id LATEST

# 3. Verify restoration
./scripts/disaster-recovery.sh verify-restore

# 4. Start services
./scripts/disaster-recovery.sh start-services
```

#### Database Corruption

```bash
# 1. Stop application services
./scripts/disaster-recovery.sh stop-services --services api,web

# 2. Restore database
./scripts/disaster-recovery.sh restore database --backup-id BACKUP_ID

# 3. Verify database integrity
./scripts/disaster-recovery.sh verify-database

# 4. Restart services
./scripts/disaster-recovery.sh start-services
```

#### Data Center Failover

```bash
# 1. Initiate failover to secondary site
./scripts/disaster-recovery.sh failover --target secondary-site

# 2. Update DNS records
./scripts/disaster-recovery.sh update-dns --target secondary-site

# 3. Verify services
./scripts/disaster-recovery.sh health --target secondary-site
```

### Recovery Time Objectives (RTO)

| Scenario | Target RTO | Actual RTO |
|----------|------------|------------|
| Database restore | 30 minutes | 15-25 minutes |
| Full system restore | 2 hours | 1-1.5 hours |
| Failover to secondary | 15 minutes | 10-12 minutes |
| File corruption recovery | 1 hour | 30-45 minutes |

### Recovery Point Objectives (RPO)

| Data Type | Target RPO | Backup Frequency |
|-----------|------------|------------------|
| Database | 6 hours | Every 6 hours |
| Application files | 24 hours | Daily |
| User uploads | 24 hours | Daily |
| Configuration | 1 hour | On change |

## Monitoring and Alerting

### Health Checks

The system performs continuous health monitoring:

```bash
# Manual health check
./scripts/backup-monitor.sh

# Check specific components
./scripts/disaster-recovery.sh health --component database
./scripts/disaster-recovery.sh health --component redis
./scripts/disaster-recovery.sh health --component storage
```

### Alert Types

1. **Backup Alerts**:
   - Backup failure
   - Stale backups (no backup in 25+ hours)
   - Backup validation failure
   - Storage space issues

2. **System Alerts**:
   - Service unavailability
   - High resource usage
   - Disk space warnings
   - Network connectivity issues

3. **Security Alerts**:
   - Unauthorized access attempts
   - Encryption key issues
   - Backup integrity violations

### Alert Channels

- **Slack**: Real-time notifications
- **Email**: Detailed reports and summaries
- **Webhooks**: Integration with external systems
- **SMS**: Critical alerts only (via webhook)

## Testing and Validation

### Regular Testing Schedule

| Test Type | Frequency | Scope |
|-----------|-----------|-------|
| Backup validation | Daily | Latest 3 backups |
| Restore testing | Weekly | Database restore |
| Full DR drill | Monthly | Complete system |
| Failover testing | Quarterly | Secondary site |

### Validation Procedures

```bash
# Automated validation
./scripts/validate-backups.sh

# Manual validation steps
./scripts/backup-system.sh verify BACKUP_ID
./scripts/backup-system.sh test-restore BACKUP_ID --dry-run

# DR drill
./scripts/disaster-recovery.sh drill --scenario database-failure
./scripts/disaster-recovery.sh drill --scenario complete-failure
```

### Test Documentation

All tests are logged and reported:

- Test execution logs: `/var/log/learning-center/`
- Test reports: Generated in JSON format
- Compliance reports: Monthly summaries

## Troubleshooting

### Common Issues

#### Backup Failures

```bash
# Check backup logs
tail -f /var/log/learning-center/backup.log

# Check disk space
df -h /var/backups/learning-center

# Check database connectivity
docker exec learning-center-postgres pg_isready

# Check Redis connectivity
docker exec learning-center-redis redis-cli ping
```

#### Restore Issues

```bash
# Verify backup integrity
./scripts/backup-system.sh verify BACKUP_ID

# Check restore logs
tail -f /var/log/learning-center/restore.log

# Test database connection after restore
docker exec learning-center-postgres psql -U postgres -d learning_center -c "SELECT version();"
```

#### Performance Issues

```bash
# Check backup performance
./scripts/backup-system.sh stats

# Monitor resource usage during backup
htop
iotop
```

### Error Codes

| Code | Description | Resolution |
|------|-------------|------------|
| 1001 | Database connection failed | Check database service and credentials |
| 1002 | Backup storage full | Clean old backups or increase storage |
| 1003 | Encryption key missing | Regenerate encryption key |
| 1004 | Remote storage unavailable | Check network and credentials |
| 1005 | Backup validation failed | Re-run backup and verify integrity |

## Security Considerations

### Encryption

- **At Rest**: All backups encrypted with AES-256
- **In Transit**: TLS 1.3 for remote transfers
- **Key Management**: Separate key storage and rotation

### Access Control

```bash
# Backup directory permissions
chmod 700 /var/backups/learning-center

# Encryption key permissions
chmod 600 /etc/learning-center/backup-encryption.key

# Script permissions
chmod 750 /path/to/backup-scripts/
```

### Audit Trail

All backup operations are logged with:
- Timestamp
- User/process
- Operation type
- Success/failure status
- File checksums

### Compliance

The backup system supports:
- **GDPR**: Data retention and deletion policies
- **SOX**: Audit trails and integrity verification
- **HIPAA**: Encryption and access controls
- **PCI DSS**: Secure storage and transmission

## Compliance and Retention

### Retention Policies

| Data Type | Retention Period | Compliance Requirement |
|-----------|------------------|------------------------|
| Database backups | 30 days | Business requirement |
| Full system backups | 28 days | Business requirement |
| Audit logs | 7 years | SOX compliance |
| Security logs | 1 year | Security policy |

### Data Lifecycle

1. **Creation**: Automated backup creation
2. **Storage**: Local and remote storage
3. **Validation**: Regular integrity checks
4. **Retention**: Policy-based retention
5. **Deletion**: Secure deletion after retention period

### Compliance Reporting

```bash
# Generate compliance report
./scripts/backup-system.sh compliance-report --period monthly

# Audit trail export
./scripts/backup-system.sh audit-export --start-date 2024-01-01 --end-date 2024-01-31
```

## Emergency Contacts

### Primary Contacts

- **System Administrator**: admin@yourcompany.com
- **Database Administrator**: dba@yourcompany.com
- **Security Team**: security@yourcompany.com

### Escalation Matrix

| Severity | Response Time | Contacts |
|----------|---------------|----------|
| Critical | 15 minutes | All teams + Management |
| High | 1 hour | Technical teams |
| Medium | 4 hours | System administrators |
| Low | Next business day | System administrators |

### External Vendors

- **Cloud Provider Support**: Available 24/7
- **Database Vendor**: Business hours support
- **Backup Software Vendor**: 24/7 support

---

## Quick Reference

### Essential Commands

```bash
# Emergency backup
sudo ./scripts/backup-system.sh backup full --priority high

# Emergency restore
sudo ./scripts/disaster-recovery.sh restore full --backup-id LATEST

# Health check
./scripts/disaster-recovery.sh health

# Alert test
./scripts/disaster-recovery.sh alert test "Test message"
```

### Configuration Files

- Backup config: `docker/production/backup-config.env`
- Systemd services: `/etc/systemd/system/learning-center-*.service`
- Cron jobs: `/etc/cron.d/learning-center-backup`
- Log rotation: `/etc/logrotate.d/learning-center-backup`

### Log Locations

- Backup logs: `/var/log/learning-center/backup*.log`
- Health checks: `/var/log/learning-center/health-check.log`
- Monitoring: `/var/log/learning-center/backup-monitor.log`
- System logs: `journalctl -u learning-center-backup.service`

For additional support, refer to the [Production Deployment Guide](PRODUCTION_DEPLOYMENT.md) and [SSL/TLS Setup Guide](SSL_TLS_SETUP.md).