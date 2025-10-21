# Development

Back to [Home](Home.md)

## Prerequisites
- Docker and Docker Compose plugin (Docker Engine ≥ 24 recommended)
- Node.js 20+ and npm (pnpm/bun optional)
- Git
- Optional: OrbStack (if you use the local dev domain label)

## Setup
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

## Environment configuration (local)
Key variables for local development (set in `.env`):
- `APP_URL=http://localhost`
- `DB_HOST=pgsql`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (match compose vars)
- `REDIS_HOST=redis`, `REDIS_PASSWORD` (optional for local)
- `MAIL_HOST=mailpit`, `MAIL_PORT=1025`, `MAIL_USERNAME=`, `MAIL_PASSWORD=`
- Optional: `SAIL_XDEBUG_MODE=off` and `SAIL_XDEBUG_CONFIG=client_host=host.docker.internal`

## Local workflow
- Start/stop containers: `make up`, `make down`, `make logs`
- Container shell: `make shell:app`
- Rebuild and update: `make update`
- Frontend: `npm run dev` (live reload) and `npm run build` (production assets)

## Testing
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

### See also
- Deployment Guide: [docs/DEPLOYMENT.md](../docs/DEPLOYMENT.md) • GitHub view: https://github.com/hafizhfadh/learningcenter/blob/main/docs/DEPLOYMENT.md
- CI/CD Pipeline: [docs/CICD.md](../docs/CICD.md) • GitHub view: https://github.com/hafizhfadh/learningcenter/blob/main/docs/CICD.md