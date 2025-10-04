# SSL/TLS Configuration Guide

This guide provides comprehensive instructions for setting up SSL/TLS certificates for the Learning Center application using Let's Encrypt.

## Table of Contents

- [Overview](#overview)
- [Prerequisites](#prerequisites)
- [Quick Setup](#quick-setup)
- [Manual Setup](#manual-setup)
- [Configuration Details](#configuration-details)
- [Security Features](#security-features)
- [Monitoring and Maintenance](#monitoring-and-maintenance)
- [Troubleshooting](#troubleshooting)
- [Advanced Configuration](#advanced-configuration)

## Overview

The Learning Center application supports secure HTTPS connections using:

- **Let's Encrypt** for free SSL/TLS certificates
- **Automatic certificate renewal** via cron jobs
- **Modern TLS configuration** (TLS 1.2 and 1.3)
- **Security headers** for enhanced protection
- **OCSP stapling** for improved performance
- **HTTP to HTTPS redirection** for all traffic

## Prerequisites

### Domain Requirements

- A registered domain name pointing to your server
- DNS A record configured to point to your server's IP address
- Port 80 and 443 accessible from the internet

### System Requirements

- Ubuntu 24.04 LTS (or compatible)
- Docker and Docker Compose installed
- Certbot (installed automatically by setup script)
- Root or sudo access

### Firewall Configuration

```bash
# Allow HTTP and HTTPS traffic
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw reload
```

## Quick Setup

### Automated Setup

Use the automated SSL setup script for the easiest installation:

```bash
# Basic setup
./scripts/setup-ssl.sh -d your-domain.com -e admin@your-domain.com

# Test with staging environment first (recommended)
./scripts/setup-ssl.sh -d your-domain.com -e admin@your-domain.com --staging

# Force renewal of existing certificate
./scripts/setup-ssl.sh -d your-domain.com -e admin@your-domain.com --force
```

### Script Options

| Option | Description | Required |
|--------|-------------|----------|
| `-d, --domain` | Domain name for SSL certificate | Yes |
| `-e, --email` | Email for Let's Encrypt registration | Yes |
| `-s, --staging` | Use staging environment (for testing) | No |
| `-f, --force` | Force certificate renewal | No |
| `-h, --help` | Show help message | No |

## Manual Setup

### 1. Install Certbot

```bash
# Install snapd
sudo apt update
sudo apt install -y snapd

# Install certbot
sudo snap install core; sudo snap refresh core
sudo snap install --classic certbot
sudo ln -sf /snap/bin/certbot /usr/bin/certbot
```

### 2. Create SSL Directory Structure

```bash
# Create SSL directories
sudo mkdir -p docker/production/ssl/live/your-domain.com
sudo mkdir -p docker/production/ssl/archive/your-domain.com
sudo chown -R root:root docker/production/ssl
sudo chmod -R 755 docker/production/ssl
```

### 3. Generate Temporary Certificate

```bash
# Generate temporary self-signed certificate
sudo openssl genrsa -out docker/production/ssl/live/your-domain.com/privkey.pem 2048
sudo openssl req -new -x509 \
    -key docker/production/ssl/live/your-domain.com/privkey.pem \
    -out docker/production/ssl/live/your-domain.com/fullchain.pem \
    -days 1 \
    -subj "/C=US/ST=State/L=City/O=Organization/CN=your-domain.com"
```

### 4. Update Nginx Configuration

```bash
# Copy SSL configuration
cp docker/production/ssl-nginx.conf docker/production/nginx.conf

# Update domain name
sed -i 's/server_name _;/server_name your-domain.com;/g' docker/production/nginx.conf
sed -i 's/your-domain.com/your-domain.com/g' docker/production/nginx.conf
```

### 5. Start Services

```bash
# Start with temporary certificate
docker-compose -f docker/production/resource-optimization.yml up -d
```

### 6. Obtain Let's Encrypt Certificate

```bash
# Get real certificate
sudo certbot certonly \
    --webroot \
    --webroot-path=/var/www/html/public \
    --email admin@your-domain.com \
    --agree-tos \
    --no-eff-email \
    --domains your-domain.com \
    --non-interactive

# Copy certificates
sudo cp /etc/letsencrypt/live/your-domain.com/fullchain.pem docker/production/ssl/live/your-domain.com/
sudo cp /etc/letsencrypt/live/your-domain.com/privkey.pem docker/production/ssl/live/your-domain.com/
sudo cp /etc/letsencrypt/live/your-domain.com/chain.pem docker/production/ssl/live/your-domain.com/
```

### 7. Restart Services

```bash
# Restart Nginx with real certificate
docker-compose -f docker/production/resource-optimization.yml restart nginx
```

## Configuration Details

### SSL/TLS Settings

The Nginx configuration includes modern SSL/TLS settings:

```nginx
# SSL Protocols
ssl_protocols TLSv1.2 TLSv1.3;

# Cipher Suites (Modern configuration)
ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384;

# Prefer server ciphers
ssl_prefer_server_ciphers off;

# Session settings
ssl_session_cache shared:SSL:10m;
ssl_session_timeout 1d;
ssl_session_tickets off;

# OCSP Stapling
ssl_stapling on;
ssl_stapling_verify on;
```

### Security Headers

The configuration includes comprehensive security headers:

```nginx
# HSTS (HTTP Strict Transport Security)
add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;

# Content Security Policy
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self'; media-src 'self'; object-src 'none'; child-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self';" always;

# Additional security headers
add_header X-Content-Type-Options nosniff always;
add_header X-Frame-Options DENY always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
```

## Security Features

### 1. Modern TLS Configuration

- **TLS 1.2 and 1.3 only** - Older protocols disabled
- **Strong cipher suites** - Only secure ciphers allowed
- **Perfect Forward Secrecy** - ECDHE key exchange
- **OCSP stapling** - Improved certificate validation

### 2. HTTP Security Headers

- **HSTS** - Forces HTTPS connections
- **CSP** - Prevents XSS attacks
- **X-Frame-Options** - Prevents clickjacking
- **X-Content-Type-Options** - Prevents MIME sniffing
- **Referrer-Policy** - Controls referrer information

### 3. Rate Limiting

- **Login endpoints** - 5 requests per minute
- **API endpoints** - 10 requests per second
- **General endpoints** - 1 request per second
- **Connection limiting** - 20 connections per IP

### 4. Content Security

- **File upload limits** - 50MB maximum
- **Request timeouts** - Prevents slow attacks
- **Buffer limits** - Prevents buffer overflow
- **Access controls** - Blocks sensitive files

## Monitoring and Maintenance

### Certificate Monitoring

```bash
# Check certificate expiration
openssl x509 -in docker/production/ssl/live/your-domain.com/fullchain.pem -noout -dates

# Check certificate details
openssl x509 -in docker/production/ssl/live/your-domain.com/fullchain.pem -noout -text

# Test SSL configuration
curl -I https://your-domain.com
```

### Automatic Renewal

The setup script creates an automatic renewal cron job:

```bash
# View renewal cron job
sudo crontab -l | grep renew-ssl

# Manual renewal test
sudo certbot renew --dry-run

# Check renewal logs
sudo tail -f /var/log/ssl-renewal.log
```

### SSL Testing

```bash
# Test SSL configuration online
# Visit: https://www.ssllabs.com/ssltest/

# Test with curl
curl -I https://your-domain.com

# Test security headers
curl -I https://your-domain.com | grep -E "(Strict-Transport|X-Frame|X-Content)"

# Test HTTP to HTTPS redirect
curl -I http://your-domain.com
```

## Troubleshooting

### Common Issues

#### 1. Certificate Generation Fails

```bash
# Check DNS resolution
dig your-domain.com

# Check domain accessibility
curl -I http://your-domain.com

# Check Let's Encrypt rate limits
# Visit: https://letsencrypt.org/docs/rate-limits/

# Use staging environment for testing
./scripts/setup-ssl.sh -d your-domain.com -e admin@your-domain.com --staging
```

#### 2. Certificate Not Loading

```bash
# Check certificate files
ls -la docker/production/ssl/live/your-domain.com/

# Check Nginx configuration
docker exec learning-center-nginx nginx -t

# Check Nginx logs
docker logs learning-center-nginx

# Restart Nginx
docker-compose -f docker/production/resource-optimization.yml restart nginx
```

#### 3. Mixed Content Warnings

```bash
# Check for HTTP resources in HTTPS pages
# Update all internal links to use HTTPS or relative URLs

# Check Content Security Policy
curl -I https://your-domain.com | grep Content-Security-Policy

# Update application configuration
# Ensure APP_URL in .env uses https://
```

#### 4. SSL Test Failures

```bash
# Check SSL configuration
openssl s_client -connect your-domain.com:443 -servername your-domain.com

# Check cipher suites
nmap --script ssl-enum-ciphers -p 443 your-domain.com

# Check OCSP stapling
openssl s_client -connect your-domain.com:443 -status
```

### Log Analysis

```bash
# Nginx access logs
docker logs learning-center-nginx | grep "GET /"

# Nginx error logs
docker logs learning-center-nginx | grep "error"

# SSL-specific logs
sudo tail -f /var/log/letsencrypt/letsencrypt.log

# Security logs
sudo tail -f /var/log/nginx/security-access.log
```

## Advanced Configuration

### Custom SSL Certificates

If you have your own SSL certificates:

```bash
# Copy your certificates
sudo cp your-certificate.crt docker/production/ssl/live/your-domain.com/fullchain.pem
sudo cp your-private-key.key docker/production/ssl/live/your-domain.com/privkey.pem
sudo cp your-ca-bundle.crt docker/production/ssl/live/your-domain.com/chain.pem

# Set proper permissions
sudo chown root:root docker/production/ssl/live/your-domain.com/*
sudo chmod 644 docker/production/ssl/live/your-domain.com/fullchain.pem
sudo chmod 600 docker/production/ssl/live/your-domain.com/privkey.pem
```

### Multiple Domains

For multiple domains or subdomains:

```bash
# Generate certificate for multiple domains
sudo certbot certonly \
    --webroot \
    --webroot-path=/var/www/html/public \
    --email admin@your-domain.com \
    --agree-tos \
    --no-eff-email \
    --domains your-domain.com,www.your-domain.com,api.your-domain.com \
    --non-interactive
```

### Custom Nginx Configuration

To customize the Nginx SSL configuration:

1. Edit `docker/production/ssl-nginx.conf`
2. Update SSL settings as needed
3. Test configuration: `docker exec learning-center-nginx nginx -t`
4. Restart Nginx: `docker-compose restart nginx`

### Performance Tuning

For high-traffic sites:

```nginx
# Increase SSL session cache
ssl_session_cache shared:SSL:50m;

# Enable SSL session tickets (if needed)
ssl_session_tickets on;

# Optimize worker connections
worker_connections 2048;

# Enable HTTP/2 push (if supported)
http2_push_preload on;
```

## Security Best Practices

1. **Regular Updates**
   - Update certificates before expiration
   - Keep Nginx and OpenSSL updated
   - Monitor security advisories

2. **Certificate Management**
   - Use strong private keys (2048-bit minimum)
   - Protect private key files (600 permissions)
   - Regular certificate rotation

3. **Monitoring**
   - Set up certificate expiration alerts
   - Monitor SSL test scores
   - Track security header compliance

4. **Backup**
   - Backup certificate files
   - Document renewal procedures
   - Test disaster recovery

---

**Last Updated**: $(date)
**Version**: 1.0.0
**Maintainer**: Learning Center Team