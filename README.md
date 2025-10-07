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
- **Reverse Proxy**: Nginx
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
| **Application VPS** | Runs Octane-powered Laravel app, Horizon dashboard, dedicated queue worker, scheduler, and the Caddy reverse proxy | Docker (single host, 4 vCPU / 4 GB RAM)
| **Database VPS** | Managed PostgreSQL 16 and Redis 7 instances with PITR backups | Bare metal, hardened OS
| **GitHub Actions** | CI/CD runner for building, testing, and deploying container images | GitHub-hosted runners
| **GitHub Container Registry (GHCR)** | Stores versioned application images and shared base images | GitHub Packages
| **Object Storage (optional)** | Stores backups, artifacts, and static assets | S3-compatible bucket

Key characteristics:

- **Octane + FrankenPHP Runtime**: The Laravel app runs through FrankenPHP workers managed by Octane to maximise concurrency in the limited VPS footprint.
- **Service Isolation**: Each runtime concern (web, queue worker, scheduler, Horizon) runs in its own container so that workload spikes cannot starve critical paths while still respecting the tight 4 vCPU / 4 GB RAM budget.
- **Network Hardening**: The Docker host communicates with the database VPS across a private network segment or IP-allowlisted TLS connection; inbound traffic terminates at Caddy with automatic certificate management for every tenant domain.

### Deployment Workflow

1. **Build & Test (CI)**
   - `.github/workflows/ci.yml` runs on pushes to `main`, semantic tags (`v*`), and pull requests.
   - The **tests** job installs PHP dependencies with Composer, boots a testing `.env`, and executes the full PHPUnit suite (`php artisan test`) that now covers unit, integration, and end-to-end scenarios.
   - The **docker** job only runs on push events; it builds the production image with `deploy/production/Dockerfile`, embedding the Vite build artefacts, and pushes tags to GHCR (`ghcr.io/hafizhfadh/learningcenter:latest`, `:<git-sha>`, and `:<tag>` when applicable).

2. **Deploy (CD)**
   - After an image is published, log into the VPS and run `make prod-pull prod-up` to pull and rollout the new container set with zero-downtime updates (the compose file uses `start-first` updates for the Octane service).
   - Secrets live in `deploy/production/secrets/.env.production` and are mounted into each container; rotate values by editing the file and rerunning `make prod-up`.
   - Post-deploy checks include `docker compose --env-file deploy/production/secrets/.env.production -f deploy/production/docker-compose.yml ps` and hitting the `/health` endpoint to confirm Octane worker readiness.

3. **Rollback**
   - Previous image tags remain in GHCR. Rolling back is as simple as redeploying with the prior tag via the `workflow_dispatch` input `image_tag`.

### Domain Management & Multi-Tenant Routing

The application uses Caddy as the single entry point for all domains:

1. **DNS Setup**: Point wildcard A/AAAA records (`*.csi-academy.id`, `*.nf-testingcenter.org`) to the application VPS IP. Explicit records (e.g., `admin.csi-academy.id`) can coexist.
2. **Dynamic Site Configuration**: Caddy runs in Docker with a volume-mounted `/etc/caddy/Caddyfile`. The Caddyfile delegates certificate management to Let's Encrypt and proxies to the Octane service. Example:

   ```caddyfile
   {
     email admin@csi-academy.id
     acme_dns cloudflare {env.CLOUDFLARE_API_TOKEN}
   }

   *.csi-academy.id {
     reverse_proxy app:9000
   }

   *.nf-testingcenter.org {
     reverse_proxy app:9000
   }

   admin.csi-academy.id {
     reverse_proxy admin:9000
   }
   ```

3. **Tenant Discovery**: Middleware within Laravel resolves tenant context based on the requested host, allowing `schoolone.csi-academy.id` or other client subdomains to map to tenant records.
4. **Certificate Management**: Caddy automatically obtains and renews certificates. Staging/live toggles are handled via environment variables so that new domains are available without redeployment.

### Environment Configuration

- **Secret Management**: Copy `deploy/production/secrets/.env.production.example` to `deploy/production/secrets/.env.production`, populate the required keys (`DB_*`, `REDIS_*`, `APP_KEY`, `CADDY_*`, `APP_IMAGE`), and keep the file only on the VPS with `chmod 600`. Git tracks the template but ignores the real secret file.
- **Octane Configuration**: Tune `OCTANE_WORKERS`, `OCTANE_TASK_WORKERS`, and `OCTANE_MAX_REQUESTS` inside the env file so that the total worker count fits within 4 vCPU. The defaults (`4`, `2`, `500`) map cleanly to the provided compose resource limits.
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
4. **SSL Renewal Verification**: Caddy renews certificates automatically, but a cron job (`caddy reload --config /etc/caddy/Caddyfile`) ensures configuration reload without downtime when domains are added.

### Troubleshooting

| Symptom | Check | Resolution |
| --- | --- | --- |
| Requests timing out | `docker compose logs caddy`, `docker compose logs app` | Ensure Octane workers are healthy; run `docker compose restart app` for a graceful restart |
| Queues not processing | Horizon dashboard or `docker compose logs queue` | Verify Redis connectivity and that queue workers have sufficient memory; scale replicas if needed |
| Domain not resolving | DNS propagation via `dig`, Caddy logs | Confirm DNS record points to VPS IP and Caddy reloaded configuration |
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
