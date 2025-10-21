# Production Deployment

Back to [Home](Home.md)

## Requirements
- VPS or server with Docker Engine ≥ 24 and Docker Compose plugin
- Python 3 (used by the deployment script for environment validation)
- A GitHub account with access to the repository and GHCR (GitHub Container Registry)
- External PostgreSQL 16 server and Redis
- Cloudflare DNS API token for automatic SSL via Traefik DNS-01 challenge

## Infrastructure dependencies
The production stack in `deploy/production/docker-compose.yml` includes:
- App (Octane + FrankenPHP), Horizon, Queue Worker, Scheduler
- Traefik v3 reverse proxy with ACME DNS-01 via Cloudflare
- Redis and Redis Exporter (for metrics)
- Persistent volumes for `storage/` and `bootstrap/cache`
- Two Docker networks: `edge` (public) and `services` (internal)

The app containers run with `read_only: true` for improved security. Write paths are limited to `storage/` and `bootstrap/cache` via named volumes. The FrankenPHP worker script is pre-copied into `public/frankenphp-worker.php` at build time so Octane does not attempt any writes at runtime.

## Build & publish (CI)
The GitHub Actions workflow `.github/workflows/ci.yml` runs on pushes to `main` and semantic tags (`v*`):
- `tests`: Composer install, Node build, and `php artisan test`
- `docker`: Multi-arch image build from `deploy/production/Dockerfile`, validation of PHP extensions, and push to GHCR
- `release`: Automated Release creation using `softprops/action-gh-release@v2` with semantic versioning rules

GHCR authentication:
- Preferred: `GHCR_PAT` secret (PAT) for orgs with SAML/SSO or cross-org pushes
- Fallback: `GITHUB_TOKEN` works when pushing to the same owner/org as the repo

## Hands-on deployment steps (VPS)
1. SSH to the server and install Docker, Compose, Python 3, and Git
2. Clone the repository
   ```bash
   cd /opt
   git clone https://github.com/hafizhfadh/learningcenter.git
   cd learningcenter
   git checkout main
   ```
3. Populate secrets:
   - Copy `deploy/production/secrets/.env.production.example` to `deploy/production/secrets/.env.production`
   - Fill `APP_KEY`, `DB_*`, `REDIS_*`, `MAIL_*`, `CF_DNS_API_TOKEN`, `TRAEFIK_ACME_EMAIL`, and `APP_IMAGE`
   - Restrict permissions: `chmod 600 deploy/production/secrets/.env.production`
4. Prepare Traefik state:
   - Ensure `deploy/production/traefik/acme.json` exists
   - Restrict: `chmod 600 deploy/production/traefik/acme.json`
5. Configure DNS:
   - Point your `APP_HOST` domain to the VPS IP
   - Cloudflare token must have Zone:DNS:Edit and Zone:Read
6. Deploy:
   ```bash
   ./deploy/production/bin/deploy.sh
   ```
   The script performs image pulls, service recreation, health checks, optional migrations, and Laravel cache warming.
7. Verify:
   ```bash
   docker compose --env-file deploy/production/secrets/.env.production \
     -f deploy/production/docker-compose.yml ps
   docker compose --env-file deploy/production/secrets/.env.production \
     -f deploy/production/docker-compose.yml logs traefik app
   curl -I https://<your-app-host>/health
   ```
8. Rollback:
   - Set `APP_IMAGE` in `.env.production` to a previous GHCR tag and rerun the deployment script

## Configuration management
- Secrets: `deploy/production/secrets/.env.production` (tracked template, real file ignored)
- Required keys include: `APP_KEY`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `REDIS_PASSWORD`, `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `CF_DNS_API_TOKEN`
- Octane tuning: `OCTANE_WORKERS`, `OCTANE_TASK_WORKERS`, `OCTANE_MAX_REQUESTS`
- Traefik: `TRAEFIK_ACME_EMAIL` and Cloudflare DNS token; ACME state in `deploy/production/traefik/acme.json`

## Performance considerations
- Octane workers and task workers sized to the VPS (defaults map to ~4 vCPU / 4 GB RAM)
- OPcache enabled with a preload stub (`bootstrap/cache/preload.php`); customize if needed
- Read-only root filesystem with write volumes for Laravel cache and storage
- Traefik compression and security middleware enabled via `traefik/dynamic.yml`

## Monitoring & logging
- Health endpoint: `/health` served by the app container
- Horizon dashboard for queues
- Docker `json-file` logs with rotation (10 MB, 5 files)
- Redis Exporter emits metrics on port 9121 (internal network)

### See also
- Deployment Guide: [docs/DEPLOYMENT.md](../docs/DEPLOYMENT.md) • GitHub view: https://github.com/hafizhfadh/learningcenter/blob/main/docs/DEPLOYMENT.md
- CI/CD Pipeline: [docs/CICD.md](../docs/CICD.md) • GitHub view: https://github.com/hafizhfadh/learningcenter/blob/main/docs/CICD.md
- Production Dockerfile: https://github.com/hafizhfadh/learningcenter/blob/main/deploy/production/Dockerfile