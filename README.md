# Learning Center - Laravel Learning Management System

A modern learning management system built with Laravel, featuring course management, learning paths, progress tracking, and administrative tools powered by Filament.

## Architecture Overview

### Application Stack
- **Backend**: Laravel 12 with PHP 8.2+
- **Frontend**: Laravel Blade templates with Vite
- **Admin Panel**: Filament 4.0
- **Queue Processing**: Laravel Horizon
- **Performance**: Laravel Octane
- **Database**: PostgreSQL 16 (external servers)
- **Cache**: Redis (containerized)
- **Reverse Proxy**: Traefik v3 (DNS-01 via Cloudflare)
- **Monitoring**: Prometheus + lightweight exporters

### Core Features
- **Learning Paths**: Structured course sequences
- **Course Management**: Comprehensive course and lesson system
- **Progress Tracking**: User enrollment and completion tracking
- **Task System**: Interactive assignments and submissions
- **User Management**: Authentication and role-based access
- **Administrative Interface**: Filament-powered admin panel

### Deployment Model
- **Application Nodes**: Run Laravel app, Redis, and core monitoring
- **Database Servers**: Dedicated PostgreSQL instances (managed separately)
- **Container Registry**: GitHub Container Registry (GHCR) for pre-built images
- **Build Process**: GitHub Actions or local builds for deployment

## Quick Start

### Prerequisites
- Docker & Docker Compose
- External PostgreSQL database
- GitHub Container Registry access
- SSL certificates (Let's Encrypt recommended)

### Development Setup

```bash
# Start development environment with Laravel Sail
./vendor/bin/sail up -d

# Run migrations and seeders
./vendor/bin/sail artisan migrate --seed

# Access the application at http://localhost
```

4. **Initialize application**:
```bash
# Run migrations
docker-compose exec app php artisan migrate --force

# Create admin user
docker-compose exec app php artisan make:filament-user

# Start Horizon for queue processing
docker-compose exec app php artisan horizon
```

## Environment Variables

| Variable | Description | Default |
| --- | --- | --- |
| `OCTANE_WORKERS` | Number of Octane worker processes (`auto` lets Octane determine the optimal value). | `auto` |
| `OCTANE_TASK_WORKERS` | Dedicated task worker pool size. | `auto` |
| `OCTANE_HTTP_PORT` | HTTP port used when Octane runs without TLS (defaults to 80 inside the container). | `80` |
| `OCTANE_MAX_REQUESTS` | Maximum number of requests a worker should process before recycling. | `250` |
| `OCTANE_FRANKENPHP_WORKERS` | Overrides the FrankenPHP worker flag passed to `octane:frankenphp`. Falls back to `OCTANE_WORKERS` when unset. | `auto` |
| `OCTANE_FRANKENPHP_ADMIN_PORT` | Admin API port exposed by FrankenPHP (useful for Octane diagnostics). | `2019` |
| `OCTANE_FRANKENPHP_HTTPS` | Enables HTTPS mode for FrankenPHP in both the Octane command and Docker entrypoint. | `true` |
| `OCTANE_FRANKENPHP_HTTP_REDIRECT` | Toggles automatic HTTP→HTTPS redirection when HTTPS is enabled. | `true` |
| `OCTANE_FRANKENPHP_CADDYFILE` | Optional FrankenPHP configuration path for advanced setups (unused by default with Traefik). | `/etc/frankenphp/Caddyfile` |

## Application Structure

### Models & Relationships
- **User**: System users with authentication
- **Institution**: Organizations managing learning content
- **LearningPath**: Structured course sequences
- **Course**: Individual courses within learning paths
- **Lesson**: Course content units
- **LessonSection**: Subdivisions within lessons
- **Task**: Interactive assignments
- **TaskQuestion**: Questions within tasks
- **TaskSubmission**: User task submissions
- **Enrollment**: User course enrollments
- **ProgressLog**: Learning progress tracking

### Key Routes
- `/` - Welcome page
- `/health` - Health check endpoint
- `/login` - User authentication
- `/user/dashboard` - User dashboard
- `/user/learningPath` - Learning paths overview
- `/user/{path}/course` - Course listing
- `/user/{path}/{course}/lesson` - Lesson navigation
- `/admin` - Filament admin panel

## Development

### Local Development
```bash
# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations and seeders
php artisan migrate --seed

# Start development servers
php artisan serve
npm run dev

# Start Horizon (for queues)
php artisan horizon
```

### Testing
```bash
# Run PHP tests
php artisan test

# Run with coverage
php artisan test --coverage
```

The suite is split into three layers so you can target specific coverage:

- **Unit** tests live under `tests/Unit` (for example `Helpers/StorageHelperTest.php`) and exercise framework-agnostic helpers.
- **Integration** tests live under `tests/Feature` and boot Laravel with an in-memory SQLite database to validate services like the lesson progress engine against real models.
- **End-to-End** tests reside in `tests/Feature/EndToEnd` and drive complete HTTP flows (course self-initiation, enrollment, and progress tracking).

Run `composer test` to clear the config cache and execute the suite locally or inside CI; the GitHub Actions workflow uses the same command.

## Production CI/CD & Infrastructure

The project ships with a production-ready deployment pattern optimised for a multi-tenant SaaS delivered via Laravel Octane and FrankenPHP. This setup assumes a dedicated application VPS (4 vCPU / 4 GB RAM) that runs the compute workloads in Docker, and a separate bare-metal VPS that hosts PostgreSQL and Redis.

### Infrastructure Architecture

| Component | Purpose | Hosting |
| --- | --- | --- |
| **Application VPS** | Runs Octane-powered Laravel app, Horizon dashboard, dedicated queue worker, scheduler, and the Traefik reverse proxy | Docker (single host, 4 vCPU / 4 GB RAM)
| **Database VPS** | Managed PostgreSQL 16 and Redis 7 instances with PITR backups | Bare metal, hardened OS
| **GitHub Actions** | CI/CD runner for building, testing, and deploying container images | GitHub-hosted runners
| **GitHub Container Registry (GHCR)** | Stores versioned application images and shared base images | GitHub Packages
| **Object Storage (optional)** | Stores backups, artifacts, and static assets | S3-compatible bucket

Key characteristics:

- **Octane + FrankenPHP Runtime**: The Laravel app runs through FrankenPHP workers managed by Octane to maximise concurrency in the limited VPS footprint.
- **Service Isolation**: Each runtime concern (web, queue worker, scheduler, Horizon) runs in its own container so that workload spikes cannot starve critical paths while still respecting the tight 4 vCPU / 4 GB RAM budget.
- **Network Hardening**: The Docker host communicates with the database VPS across a private network segment or IP-allowlisted TLS connection; inbound traffic terminates at Traefik with automatic certificate management for every tenant domain.

### Deployment Workflow

1. **Build & Test (CI)**
   - `.github/workflows/ci.yml` runs on pushes to `main`, semantic tags (`v*`), and pull requests.
   - The **tests** job installs PHP dependencies with Composer, boots a testing `.env`, and executes the full PHPUnit suite (`php artisan test`) that now covers unit, integration, and end-to-end scenarios.
   - The **docker** job only runs on push events; it builds the production image with `deploy/production/Dockerfile`, embedding the Vite build artefacts, and pushes tags to GHCR (`ghcr.io/hafizhfadh/learningcenter:latest`, `:<git-sha>`, and `:<tag>` when applicable).
   - The production Dockerfile now uses a dedicated Composer builder, a frontend asset stage, and a slim FrankenPHP runtime layer so tests, docs, and tooling stay out of the final image while keeping build caching efficient.

2. **Deploy (CD)**
   - After an image is published, log into the VPS and execute `./deploy/production/bin/deploy.sh`. The script performs `git pull --rebase`, pulls the latest container images, recreates the stack, waits for health checks, and refreshes Laravel caches with zero-downtime updates.
   - Secrets live in `deploy/production/secrets/.env.production` and are mounted into each container; rotate values by editing the file and rerunning the deployment script.
   - Post-deploy checks are handled automatically, but you can still run `docker compose --env-file deploy/production/secrets/.env.production -f deploy/production/docker-compose.yml ps` or hit the `/health` endpoint to confirm Octane worker readiness.

3. **Rollback**
   - Previous image tags remain in GHCR. Rolling back is as simple as redeploying with the prior tag via the `workflow_dispatch` input `image_tag`.

### Hands-on VPS Deployment Procedure

Follow this checklist when shipping a new release to the production VPS:

1. **SSH into the VPS** with a user that can run Docker commands (typically the same user that performed the initial install).
2. **Install prerequisites** if they are missing: Docker Engine ≥ 24, the Docker Compose plugin, and Git.
3. **Pull the repository** (first-time clone or update):
   ```bash
   cd /opt
   git clone https://github.com/hafizhfadh/learningcenter.git
   cd learningcenter
   git checkout main
   ```
   For subsequent deployments simply run `git fetch --tags --prune`.
4. **Populate secrets** by copying `deploy/production/secrets/.env.production.example` to `deploy/production/secrets/.env.production` and filling in application credentials, the Cloudflare DNS token (`CF_DNS_API_TOKEN`), Traefik metadata such as `TRAEFIK_ACME_EMAIL`, and the target `APP_IMAGE`. Restrict the file with `chmod 600 deploy/production/secrets/.env.production`.
5. **Prepare Traefik state**: ensure `deploy/production/traefik/acme.json` exists (an empty file is committed) and run `chmod 600 deploy/production/traefik/acme.json` so Traefik can persist certificates.
6. **Configure DNS** so the `APP_HOST` record resolves to the VPS and the Cloudflare API token has `Zone:DNS:Edit` and `Zone:Read` permissions. Disable the Cloudflare orange cloud so Traefik can solve DNS challenges directly.
7. **Authenticate to GHCR** if the container image is private. Use a GitHub personal access token with the `read:packages` scope:
   ```bash
   echo "$GHCR_TOKEN" | docker login ghcr.io -u <github-username> --password-stdin
   ```
   You can rerun the deployment with `SKIP_IMAGE_SYNC=1 ./deploy/production/bin/deploy.sh` to reuse cached images when network access to GHCR is unavailable.
8. **Run the deployment script** from the repository root:
   ```bash
   ./deploy/production/bin/deploy.sh
   ```
   The script performs a `git pull --rebase`, pulls updated container images, recreates services, waits for health checks, runs database migrations (unless `RUN_MIGRATIONS=0`), and warms Laravel caches.
9. **Verify the rollout**:
   ```bash
   docker compose --env-file deploy/production/secrets/.env.production \
     -f deploy/production/docker-compose.yml ps
   docker compose --env-file deploy/production/secrets/.env.production \
     -f deploy/production/docker-compose.yml logs traefik app
   curl -I https://learning.csi-academy.id/health
   ```
   Replace the host in the final command with your `APP_HOST`. All logs from the deployment script are captured under `deploy/production/logs/` for later auditing.
10. **Rollback if required** by setting `APP_IMAGE` in `.env.production` to a previous GHCR tag and rerunning the deployment script. Traefik and Octane will recycle gracefully thanks to start-first updates.

### Domain Management & Multi-Tenant Routing

The application now relies on Traefik as the single entry point for all domains:

1. **DNS Setup**: Point wildcard A/AAAA records (`*.csi-academy.id`, `*.nf-testingcenter.org`) to the application VPS IP. Explicit records (e.g., `admin.csi-academy.id`) can coexist.
2. **Dynamic Site Configuration**: Traefik consumes Docker labels defined in `deploy/production/docker-compose.yml`. The `app` service registers a router, service, and middlewares via labels while `traefik/dynamic.yml` provides shared security/compression middleware. Example:

   ```yaml
   labels:
     - traefik.enable=true
     - traefik.http.routers.app.rule=Host(`${APP_HOST}`)
     - traefik.http.routers.app.entrypoints=websecure
     - traefik.http.routers.app.tls.certresolver=cloudflare
     - traefik.http.services.app.loadbalancer.server.port=9000
   ```

3. **Tenant Discovery**: Middleware within Laravel resolves tenant context based on the requested host, allowing `schoolone.csi-academy.id` or other client subdomains to map to tenant records.
4. **Certificate Management**: Traefik automatically obtains and renews certificates using the Cloudflare DNS challenge. ACME state lives in `deploy/production/traefik/acme.json`, and staging/live toggles are handled via environment variables so that new domains are available without redeployment.

### Environment Configuration

- **Secret Management**: Copy `deploy/production/secrets/.env.production.example` to `deploy/production/secrets/.env.production`, populate the required keys (`DB_*`, `REDIS_*`, `APP_KEY`, `CF_DNS_API_TOKEN`, `TRAEFIK_ACME_EMAIL`, `APP_IMAGE`), and keep the file only on the VPS with `chmod 600`. Git tracks the template but ignores the real secret file.
- **Octane Configuration**:
  - Tune `OCTANE_WORKERS`, `OCTANE_TASK_WORKERS`, `OCTANE_MAX_REQUESTS`, and `OCTANE_MAX_EXECUTION_TIME` to stay within the 4 vCPU / 4 GB VPS budget. The defaults (`4`, `2`, `500`, `60`) map cleanly to the compose resource limits.
  - `OCTANE_LISTEN` (or the explicit `OCTANE_HOST` / `OCTANE_PORT` overrides) defines where Octane binds. The updated `config/octane.php` helper reads these values so CLI invocations and the container entrypoint stay aligned.
- **FrankenPHP Runtime**: Configure `OCTANE_FRANKENPHP_CONFIG`, `OCTANE_FRANKENPHP_WORKER`, `OCTANE_FRANKENPHP_CADDYFILE`, `OCTANE_FRANKENPHP_ADMIN_SERVER`/`OCTANE_FRANKENPHP_ADMIN_PORT`, `OCTANE_FRANKENPHP_HTTP_REDIRECT`, and `OCTANE_FRANKENPHP_LOG_LEVEL` to control the worker script, optional FrankenPHP configuration file, admin interface, and HTTPS redirect behaviour. These feed the `config/octane.php` FrankenPHP block and the container entrypoint.
- **Database Connectivity**: Point `DB_HOST` at the private interface of the database VPS (or a managed PostgreSQL endpoint) and restrict access by IP allow lists + TLS. No WireGuard tunnel is required on the application host, simplifying memory consumption.
- **Queue/Horizon**: Dedicated containers run `php artisan horizon` and `php artisan queue:work` with the same code image but different entrypoints. Supervisor is unnecessary because Docker restarts failed containers.

### Monitoring, Logging & Maintenance

- **Monitoring Stack**: Keep the host lightweight by using `docker stats`, `docker compose ps`, and the built-in Horizon dashboard. When deeper insight is needed, ship container metrics to an external Prometheus/Grafana instance rather than running heavy agents locally.
- **Application Metrics**: Laravel Horizon and the `/health` endpoint provide the primary SLO signals. Consider enabling Laravel Pulse or a SaaS APM if additional tracing is required.
- **Logging**: All services emit JSON logs via Docker's `json-file` driver (rotated at 10 MB). Forward them to a remote destination with `docker logs` piping or a lightweight log agent installed on the host if long-term retention is needed.
- **Backups**: Database VPS performs nightly logical dumps and continuous WAL archiving. Object storage retains seven daily, four weekly, and six monthly snapshots. Application assets are synced to S3 via `rclone` scheduled tasks.
- **Security & Patching**: Apply OS security updates weekly via unattended upgrades. Rebuild application images monthly to incorporate upstream patches.
- **Disaster Recovery**: Document procedures for restoring from GHCR image tags, database backups, and DNS changes to a standby VPS.

### Maintenance Procedures

1. **Scaling Queues**: Adjust the `replicas` count for the queue worker service in `docker-compose.prod.yml` and redeploy. Monitor Horizon for throughput improvements.
2. **Rotating Secrets**: Update values in GitHub Actions secrets, rerun the deployment workflow, and confirm that the new environment variables propagated by checking `docker compose exec app php artisan env`.
3. **Database Maintenance**: Run `VACUUM ANALYZE` weekly via cron on the database VPS. Monitor disk usage and WAL retention to avoid storage exhaustion.
4. **SSL Renewal Verification**: Traefik renews certificates automatically via the DNS challenge. Monitor renewal status with `docker compose logs traefik` and rotate the `traefik/acme.json` file only when troubleshooting failed renewals.

### Troubleshooting

| Symptom | Check | Resolution |
| --- | --- | --- |
| Requests timing out | `docker compose logs traefik`, `docker compose logs app` | Ensure Octane workers are healthy; run `docker compose restart app` for a graceful restart |
| Queues not processing | Horizon dashboard or `docker compose logs queue` | Verify Redis connectivity and that queue workers have sufficient memory; scale replicas if needed |
| Domain not resolving | DNS propagation via `dig`, Traefik logs | Confirm DNS record points to VPS IP and Traefik obtained a certificate |
| Deployment failed | GitHub Actions logs | Re-run workflow with `image_tag` override after fixing underlying issue |
| Database connectivity issues | `psql` from app container, security group rules | Ensure the database IP/port is allow-listed for the VPS, rotate credentials if auth fails |

## Application Structure

## Development Environment

### Architecture Overview
The application uses Laravel Sail for local development:

- **Laravel Sail**: Docker-based development environment
- **Services**: PostgreSQL, Redis, Mailpit for local testing

### Development Tools
- **Health Endpoint**: `/health` for application status
- **Horizon Dashboard**: Queue monitoring via Filament
- **Laravel Telescope**: Debug and profiling tool
- **Mailpit**: Email testing interface

### Security Features
- **Authentication**: Laravel's built-in authentication
- **Authorization**: Role-based access control
- **CSRF Protection**: Enabled for all forms
- **SQL Injection Prevention**: Eloquent ORM protection
- **XSS Protection**: Blade template escaping
- **Rate Limiting**: API and route protection

## Documentation

For detailed development guidance, see:
- [Development Setup](docs/DEVELOPMENT.md)
- [API Documentation](docs/API.md)
- [Testing Guide](docs/TESTING.md)

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
