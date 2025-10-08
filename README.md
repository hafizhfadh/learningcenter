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

## Environment Variables

| Variable | Description | Default |
| --- | --- | --- |
| `OCTANE_WORKERS` | Number of Octane worker processes (`auto` lets Octane determine the optimal value). | `auto` |
| `OCTANE_TASK_WORKERS` | Dedicated task worker pool size. | `auto` |
| `OCTANE_HTTP_PORT` | HTTP port used when Octane runs without TLS (defaults to 80 inside the container). | `80` |
| `OCTANE_MAX_REQUESTS` | Maximum number of requests a worker should process before recycling. | `250` |
| `OCTANE_FRANKENPHP_WORKERS` | Overrides the FrankenPHP worker flag passed to `octane:frankenphp`. Falls back to `OCTANE_WORKERS` when unset. | `auto` |
| `OCTANE_FRANKENPHP_ADMIN_PORT` | Admin API port exposed by FrankenPHP (used by Caddy). | `2019` |
| `OCTANE_FRANKENPHP_HTTPS` | Enables HTTPS mode for FrankenPHP in both the Octane command and Docker entrypoint. | `true` |
| `OCTANE_FRANKENPHP_HTTP_REDIRECT` | Toggles automatic HTTP→HTTPS redirection when HTTPS is enabled. | `true` |
| `OCTANE_FRANKENPHP_CADDYFILE` | Path to the FrankenPHP / Caddy configuration file mounted in the container. | `/etc/frankenphp/Caddyfile` |

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

## Production CI/CD & Infrastructure

The project ships with a production-ready deployment pattern optimised for a multi-tenant SaaS delivered via Laravel Octane and FrankenPHP. This setup assumes a dedicated application VPS (4 vCPU / 4 GB RAM) that runs the compute workloads in Docker, and a separate bare-metal VPS that hosts PostgreSQL and Redis.

### Infrastructure Architecture

| Component | Purpose | Hosting |
| --- | --- | --- |
| **Application VPS** | Runs Octane-powered Laravel app, Horizon, queues, scheduler, Caddy reverse proxy, log shipper, and supporting services | Docker (single host)
| **Database VPS** | Managed PostgreSQL 16 and Redis 7 instances with PITR backups | Bare metal, hardened OS
| **GitHub Actions** | CI/CD runner for building, testing, and deploying container images | GitHub-hosted runners
| **GitHub Container Registry (GHCR)** | Stores versioned application images and shared base images | GitHub Packages
| **Object Storage (optional)** | Stores backups, artifacts, and static assets | S3-compatible bucket

Key characteristics:

- **Octane + FrankenPHP Runtime**: The Laravel app runs through FrankenPHP workers managed by Octane to maximise concurrency in the limited VPS footprint.
- **Service Isolation**: Each runtime concern (web, queue, scheduler, Horizon) is encapsulated in its own container for lifecycle management and horizontal scaling.
- **Secure Network Segmentation**: Application containers communicate with the database VPS via WireGuard or Tailscale for encrypted east-west traffic, while Caddy terminates TLS for all tenant domains.

### Deployment Workflow

1. **Build & Test (CI)**
   - A GitHub Actions workflow triggers on pushes to `main` or version tags.
   - Steps include installing PHP dependencies with Composer, running linters and PHPUnit, executing front-end builds, and collecting coverage reports.
   - If tests pass, the workflow builds a multi-stage Docker image (`docker/octane/Dockerfile`) that bundles the application, Octane, and FrankenPHP.
   - The resulting image is tagged (`ghcr.io/<org>/<app>:<git-sha>` and `:latest`) and pushed to GHCR.

2. **Deploy (CD)**
   - A separate job uses an OIDC federated credential or deploy key to SSH into the application VPS (through a `deploy` user with limited privileges).
   - Secrets (WireGuard keys, database credentials, app key) are stored as encrypted GitHub Action secrets and templated into a `.env.production` file rendered on the runner.
   - The workflow updates the remote host via `docker compose pull` and `docker compose up -d --remove-orphans`, ensuring zero downtime by leveraging Octane's graceful worker reloads and Caddy's request buffering.
   - After deployment, the workflow runs health checks (`/health`, Horizon queue status) and posts notifications to the team channel.

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

- **Secret Management**: Keep runtime secrets in GitHub Actions encrypted variables. The CI/CD workflow writes them into a `secrets/.env.production` file on the runner, then securely copies it to the VPS using `scp` with `chmod 600` applied server-side. On the server, a `docker secrets`-style pattern is used: the `.env` file lives outside version control and is mounted into containers.
- **Octane Configuration**: Set `OCTANE_SERVER=frankenphp`, configure worker counts (`OCTANE_WORKERS=4`, `OCTANE_MAX_REQUESTS=500`) to match the VPS CPU cores, and enable task queue integration with Redis.
- **Database Connectivity**: The `.env.production` file contains the private WireGuard endpoint of the database VPS. Use managed users with least privilege, enforce SSL/TLS connections, and rotate credentials quarterly.
- **Queue/Horizon**: Dedicated containers run `php artisan horizon` and `php artisan queue:work` with the same code image but different entrypoints. Supervisor is unnecessary because Docker restarts failed containers.

### Monitoring, Logging & Maintenance

- **Monitoring Stack**: Install the Prometheus Node Exporter and cAdvisor on the VPS to gather host/container metrics. Use Grafana (hosted or self-managed) to visualise application, queue, and database health.
- **Application Metrics**: Laravel Horizon exposes queue stats; schedule a cron container to post metrics to Prometheus via pushgateway or StatsD.
- **Logging**: Containers emit JSON logs collected by a `vector` or `fluent-bit` sidecar, which forwards to an ELK stack or a managed log service (e.g., Logtail). Caddy access logs are parsed into the same pipeline for tenant-level observability.
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
| Database connectivity issues | `psql` from app container, WireGuard status | Restart tunnel (`systemctl restart wg-quick@prod`) or update firewall rules |

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
