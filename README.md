# Learning Center — Laravel Learning Management System

A modern Learning Management System (LMS) built with Laravel. It features course management, learning paths, progress tracking, and an administrative panel powered by Filament. The application is optimized for high concurrency using Laravel Octane atop FrankenPHP.

## Table of Contents

1. Development
2. Production Deployment
3. Contribution Guidelines
4. Architecture & Infrastructure Overview
5. Makefile Cheat Sheet
6. License

---

## 1) Development

### Prerequisites
- Docker and Docker Compose plugin (Docker Engine ≥ 24 recommended)
- Node.js 20+ and npm (pnpm/bun optional)
- Git
- Optional: OrbStack (if you use the local dev domain label)

### Setup

```bash
# Clone
git clone https://github.com/hafizhfadh/learningcenter.git
cd learningcenter

# Install dependencies
composer install
npm install

# Bootstrap environment
cp .env.example .env
php artisan key:generate

# Start local stack (choose one)
./vendor/bin/sail up -d
# or
make up

# Run migrations and seeders
php artisan migrate --seed

# Create an admin user for Filament
php artisan make:filament-user

# Start the full dev workflow (app server, queue listener, logs, Vite)
composer dev
```

The local stack is defined in `docker-compose.yml` and runs:
- `laravel.test`: PHP 8.4 container with Composer, Xdebug (optional), and Supervisor
- `pgsql`: PostgreSQL 17 (data persisted in a local Docker volume)
- `redis`: Redis (password optional in local dev)
- `mailpit`: SMTP sink + web UI for local email testing

Default ports (configurable via `.env`):
- App: `APP_PORT` (default 80)
- Vite: `VITE_PORT` (default 5173)
- PostgreSQL: `FORWARD_DB_PORT` (default 5432)
- Redis: `FORWARD_REDIS_PORT` (default 6379)
- Mailpit: `FORWARD_MAILPIT_PORT` (1025 SMTP), `FORWARD_MAILPIT_DASHBOARD_PORT` (8025 UI)

If you use OrbStack, the service exposes a label `dev.orbstack.domains=${APP_URL}`. Set `APP_URL=http://learning.local` (or similar) to get a friendly local domain.

### Environment configuration (local)
Key variables for local development (set in `.env`):
- `APP_URL=http://localhost`
- `DB_HOST=pgsql`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (match compose vars)
- `REDIS_HOST=redis`, `REDIS_PASSWORD` (optional for local)
- `MAIL_HOST=mailpit`, `MAIL_PORT=1025`, `MAIL_USERNAME=`, `MAIL_PASSWORD=`
- Optional: `SAIL_XDEBUG_MODE=off` and `SAIL_XDEBUG_CONFIG=client_host=host.docker.internal`

### Local workflow
- Start/stop containers: `make up`, `make down`, `make logs`
- Container shell: `make shell:app`
- Rebuild and update: `make update`
- Frontend: `npm run dev` (live reload) and `npm run build` (production assets)

### Testing
```bash
# Clear caches and run the full test suite
composer test

# PHPUnit directly
php artisan test --without-tty

# Coverage (HTML/text)
php artisan test --coverage
```
Tests are organized as:
- Unit: `tests/Unit/`
- Integration: `tests/Feature/`
- End-to-End: `tests/Feature/EndToEnd/`

The integration/E2E suite boots Laravel and typically uses an in-memory SQLite or configured Postgres depending on your `.env`. CI runs `composer test` to match local expectations.

---

## 2) Production Deployment

### Requirements
- VPS or server with Docker Engine ≥ 24 and Docker Compose plugin
- Python 3 (used by the deployment script for environment validation)
- A GitHub account with access to the repository and GHCR (GitHub Container Registry)
- External PostgreSQL 16 server and Redis
- Cloudflare DNS API token for automatic SSL via Traefik DNS-01 challenge

### Infrastructure dependencies
The production stack in `deploy/production/docker-compose.yml` includes:
- App (Octane + FrankenPHP), Horizon, Queue Worker, Scheduler
- Traefik v3 reverse proxy with ACME DNS-01 via Cloudflare
- Redis and Redis Exporter (for metrics)
- Persistent volumes for `storage/` and `bootstrap/cache`
- Two Docker networks: `edge` (public) and `services` (internal)

The app containers run with `read_only: true` for improved security. Write paths are limited to `storage/` and `bootstrap/cache` via named volumes. The FrankenPHP worker script is pre-copied into `public/frankenphp-worker.php` at build time so Octane does not attempt any writes at runtime.

### Build & publish (CI)
The GitHub Actions workflow `.github/workflows/ci.yml` runs on pushes to `main` and semantic tags (`v*`):
- `tests`: Composer install, Node build, and `php artisan test`
- `docker`: Multi-arch image build from `deploy/production/Dockerfile`, validation of PHP extensions, and push to GHCR
- `release`: Automated Release creation using `softprops/action-gh-release@v2` with semantic versioning rules

GHCR authentication:
- Preferred: `GHCR_PAT` secret (PAT) for orgs with SAML/SSO or cross-org pushes
- Fallback: `GITHUB_TOKEN` works when pushing to the same owner/org as the repo

### Hands-on deployment steps (VPS)
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

### Configuration management
- Secrets: `deploy/production/secrets/.env.production` (tracked template, real file ignored)
- Required keys include: `APP_KEY`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `REDIS_PASSWORD`, `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `CF_DNS_API_TOKEN`
- Octane tuning: `OCTANE_WORKERS`, `OCTANE_TASK_WORKERS`, `OCTANE_MAX_REQUESTS`
- Traefik: `TRAEFIK_ACME_EMAIL` and Cloudflare DNS token; ACME state in `deploy/production/traefik/acme.json`

### Performance considerations
- Octane workers and task workers sized to the VPS (defaults map to ~4 vCPU / 4 GB RAM)
- OPcache enabled with a preload stub (`bootstrap/cache/preload.php`); customize if needed
- Read-only root filesystem with write volumes for Laravel cache and storage
- Traefik compression and security middleware enabled via `traefik/dynamic.yml`

### Monitoring & logging
- Health endpoint: `/health` served by the app container
- Horizon dashboard for queues
- Docker `json-file` logs with rotation (10 MB, 5 files)
- Redis Exporter emits metrics on port 9121 (internal network)

---

## 3) Contribution Guidelines

### Code style and standards
- PSR-12
- Format code with Laravel Pint before committing: `./vendor/bin/pint`
- Keep routes, controllers, services, and models consistent with existing patterns

### Branching strategy
- Trunk-based development on `main`
- Create feature branches prefixed by type, e.g., `feat/...`, `fix/...`, `chore/...`
- Use Conventional Commits for messages: `feat:`, `fix:`, `docs:`, `refactor:`, `perf:`, `test:`, `ci:`

### Pull request process
- Ensure `composer test` passes locally and in CI
- Apply code formatting (`./vendor/bin/pint`)
- Update documentation when necessary (README, `docs/DEPLOYMENT.md`, etc.)
- For UI changes, include screenshots or short descriptions
- Link related issues and call out migrations or environment changes explicitly

### Issue reporting format
Include the following:
- Summary of the problem
- Steps to reproduce
- Expected vs actual behavior
- Environment details (OS, Docker/Compose versions, app version)
- Logs or stack traces (attach files or paste relevant excerpts)
- Impact assessment (e.g., blocks enrollment, breaks progress tracking)

For security issues, do not open a public issue—contact the maintainers privately.

---

## 4) Architecture & Infrastructure Overview

### Application stack
- Backend: Laravel 12, PHP 8.3/8.4
- Frontend: Blade + Vite
- Admin: Filament 4
- Performance: Laravel Octane + FrankenPHP
- Database: PostgreSQL 16 (external servers)
- Cache/Queue: Redis
- Reverse Proxy: Traefik v3 (DNS-01 via Cloudflare)

### Core features
- Learning paths and course management
- Progress tracking and enrollments
- Task and submission system
- Filament-powered admin panel

### Deployment model
- Application nodes: app, horizon, queue, scheduler, reverse proxy
- Database servers: managed separately
- Container registry: GHCR for pre-built images
- Build process: GitHub Actions or local builds

---

## 5) Makefile Cheat Sheet
- `make up`: start local development containers
- `make down`: stop local containers
- `make logs`: stream logs from all local services
- `make shell:app`: shell into the app container
- `make update`: rebuild and update local containers
- `make prod-up`: start the production stack (requires `.env.production`)
- `make prod-pull`: pull latest production images from GHCR
- `make prod-down`: stop the production stack
- `make prod-logs`: tail production logs

---

## 6) License

This project is open-sourced software licensed under the MIT license.
