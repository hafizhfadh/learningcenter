# SSL/TLS Troubleshooting Guide

## Problem Summary

You encountered the following SSL/TLS error when trying to access your domain:

```
curl: (35) LibreSSL/3.3.6: error:1404B438:SSL routines:ST_CONNECT:tlsv1 alert internal error
```

## Root Cause Analysis

The error occurs because:

1. **Local Development vs Production Domain**: Your Caddyfile is configured for the production domain `learning.csi-academy.id`, but when running locally, Caddy cannot obtain a valid SSL certificate for this domain.

2. **Let's Encrypt Validation Failure**: Let's Encrypt requires the domain to:
   - Resolve to a publicly accessible IP address
   - Be reachable from the internet on ports 80 and 443
   - Pass domain ownership validation

3. **Certificate Acquisition Issues**: When these conditions aren't met, Caddy fails to obtain certificates, leading to SSL handshake failures.

## Solutions Provided

### 1. Local Development Configuration

**File**: `etc/frankenphp/Caddyfile.local`

- Disables automatic HTTPS (`auto_https off`)
- Configures HTTP-only access via `localhost`
- Enables admin API for debugging
- Includes optional self-signed certificate configuration

**Usage**:
```bash
# Use the SSL fix script
./scripts/fix-ssl-issues.sh
# Choose option 1 for local development
```

### 2. Production Configuration

**File**: `etc/frankenphp/Caddyfile` (updated)

- Proper Let's Encrypt email configuration
- Security headers (HSTS, XSS protection, etc.)
- Automatic HTTPS with certificate acquisition
- WWW to non-WWW redirects
- Optimized caching and compression

**Usage**:
```bash
# Use the SSL fix script
./scripts/fix-ssl-issues.sh
# Choose option 2 for production deployment
```

## Quick Fixes

### For Local Development

```bash
# Stop current containers
docker-compose -f docker-compose.production.yml down

# Use local configuration
cp etc/frankenphp/Caddyfile.local etc/frankenphp/Caddyfile

# Start containers
docker-compose -f docker-compose.production.yml up -d

# Access via HTTP (no SSL)
curl http://localhost
```

### For Production Deployment

**Prerequisites**:
1. Domain `learning.csi-academy.id` must resolve to your server's public IP
2. Ports 80 and 443 must be open and accessible from the internet
3. No firewall blocking Let's Encrypt validation

```bash
# Ensure production Caddyfile is in place
cp etc/frankenphp/Caddyfile.production etc/frankenphp/Caddyfile  # if needed

# Deploy
docker-compose -f docker-compose.production.yml down
docker-compose -f docker-compose.production.yml up -d

# Wait for certificate acquisition (up to 2 minutes)
# Check certificate status
docker exec learningcenter_app ls /data/caddy/certificates
```

## Diagnostic Commands

### Check Container Status
```bash
docker-compose -f docker-compose.production.yml ps
```

### View Application Logs
```bash
docker-compose -f docker-compose.production.yml logs app
```

### Check Certificate Directory
```bash
docker exec learningcenter_app ls -la /data/caddy/certificates
```

### Test Local Connectivity
```bash
# HTTP (should work)
curl -I http://localhost

# HTTPS with certificate validation disabled
curl -I -k https://localhost
```

### Test Production Connectivity
```bash
# Test domain resolution
nslookup learning.csi-academy.id

# Test HTTP (should redirect to HTTPS)
curl -I http://learning.csi-academy.id

# Test HTTPS
curl -I https://learning.csi-academy.id
```

## Common Issues and Solutions

### Issue: "Port already allocated"
**Solution**: Stop conflicting containers
```bash
docker-compose down  # Stop development containers
docker-compose -f docker-compose.production.yml up -d
```

### Issue: "DNS resolution failed"
**Solution**: 
1. Check domain DNS settings
2. Ensure domain points to correct IP
3. Wait for DNS propagation (up to 48 hours)

### Issue: "Certificate acquisition timeout"
**Solution**:
1. Verify domain is publicly accessible
2. Check firewall settings
3. Ensure ports 80/443 are open
4. Check Let's Encrypt rate limits

### Issue: "Caddyfile formatting warning"
**Solution**: The warning is cosmetic and doesn't affect functionality, but you can fix it:
```bash
# Format the Caddyfile (if caddy is available locally)
caddy fmt --overwrite etc/frankenphp/Caddyfile
```

## Security Considerations

### Production Security Headers
The updated production Caddyfile includes:
- **HSTS**: Forces HTTPS connections
- **X-Frame-Options**: Prevents clickjacking
- **X-Content-Type-Options**: Prevents MIME sniffing
- **Referrer-Policy**: Controls referrer information

### Certificate Management
- Certificates are automatically renewed by Caddy
- Stored in `/data/caddy/certificates` inside the container
- Backed up via Docker volumes

## Monitoring and Maintenance

### Certificate Expiry Monitoring
```bash
# Check certificate expiry
docker exec learningcenter_app caddy list-certificates
```

### Log Monitoring
```bash
# Monitor real-time logs
docker-compose -f docker-compose.production.yml logs -f app
```

### Health Checks
```bash
# Application health
curl https://learning.csi-academy.id/health

# Container health
docker-compose -f docker-compose.production.yml ps
```

## Next Steps

1. **For Local Development**: Use the local configuration and access via `http://localhost`
2. **For Production**: Ensure DNS is properly configured and deploy with the production configuration
3. **Monitoring**: Set up monitoring for certificate expiry and application health
4. **Backup**: Ensure certificate data is backed up (handled by Docker volumes)

## Support

If issues persist:
1. Run the diagnostic script: `./scripts/fix-ssl-issues.sh`
2. Check the detailed logs
3. Verify DNS and network configuration
4. Ensure all prerequisites are met for production deployment