# Production Deployment Guide

## Issue Resolution: Missing Environment Variables

The error you encountered occurs because the deployment script expects environment variables to be loaded from the shell environment, but they weren't being read from the `.env.production` file.

### Solution

I've created two solutions for you:

#### Option 1: Use the Updated Deployment Script (Recommended)

The `scripts/deploy-production.sh` has been updated to automatically load environment variables from `.env.production`.

```bash
# On your Ubuntu server
cd /srv/learningcenter

# Make sure the script is executable
chmod +x scripts/deploy-production.sh

# Run the deployment
./scripts/deploy-production.sh v1.0.0
```

#### Option 2: Use the Simple Wrapper Script

I've created a `deploy.sh` wrapper script that loads the environment and calls the main deployment script:

```bash
# On your Ubuntu server
cd /srv/learningcenter

# Make the wrapper script executable
chmod +x deploy.sh

# Run the deployment
./deploy.sh v1.0.0
```

#### Option 3: Manual Environment Loading

If you prefer to load the environment manually:

```bash
# Load environment variables
set -a
source .env.production
set +a

# Run deployment
./scripts/deploy-production.sh v1.0.0
```

## Environment File Updates

I've updated your `.env.production` file with the following corrections:

- `APP_ENV=production` (was `local`)
- `APP_DEBUG=false` (was `true`)
- `LOG_LEVEL=error` (was `debug`)
- `REDIS_HOST=redis` (was `valkey`)
- `REDIS_PASSWORD=` (was `null`)

## Required Environment Variables

The deployment script validates these required variables:

- `APP_NAME`
- `APP_ENV`
- `APP_KEY`
- `APP_URL`
- `DB_HOST`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `REDIS_PASSWORD`

## Deployment Process

The updated deployment script will:

1. ✅ Load environment variables from `.env.production`
2. ✅ Generate missing secrets (APP_KEY, DB_PASSWORD, REDIS_PASSWORD)
3. ✅ Validate all required environment variables
4. ✅ Test PostgreSQL cluster connectivity
5. ✅ Build Docker image
6. ✅ Create backup of existing deployment
7. ✅ Deploy application
8. ✅ Wait for health checks
9. ✅ Run post-deployment tests
10. ✅ Clean up old images

## Troubleshooting

### If you still get missing environment variable errors:

1. Verify `.env.production` exists in the project root
2. Check that all required variables are set (not empty)
3. Ensure the script has read permissions on `.env.production`

### If PostgreSQL connection fails:

1. Verify your PostgreSQL cluster is accessible from the server
2. Check firewall rules
3. Verify SSL configuration if required
4. Test connection manually:
   ```bash
   PGPASSWORD="your_password" psql -h 10.53.149.111 -p 6435 -U learningcenter_user -d learningcenter -c "SELECT 1;"
   ```

### If Docker build fails:

1. Ensure Docker is running
2. Check available disk space
3. Verify network connectivity for package downloads

## Next Steps

After successful deployment:

1. Access your application at: `https://learning.csi-academy.id`
2. Monitor logs: `docker-compose -f docker-compose.production.yml logs -f`
3. Check container status: `docker-compose -f docker-compose.production.yml ps`

## Security Notes

- The deployment script generates secure random passwords for missing secrets
- All sensitive data is handled securely
- SSL/TLS is configured for PostgreSQL connections
- Production environment has debug mode disabled

For any issues, check the deployment logs and ensure all prerequisites are met.