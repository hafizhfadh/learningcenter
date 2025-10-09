# Production Deployment with Traefik & Octane

This directory contains the production infrastructure used to deploy the Laravel Octane
application on a single VPS while retaining the speed and reliability of a traditional
server setup. The stack consists of:

- **FrankenPHP** running Laravel Octane for the application, queue, scheduler, and Horizon workers.
- **Traefik v3** acting as the public reverse proxy, handling HTTPS termination via Cloudflare's DNS challenge.
- Supporting volumes for persistent storage and cache directories.

The deployment workflow is orchestrated by `bin/deploy.sh`, which covers code retrieval,
container updates, cache optimization, and health verification.

## Prerequisites

- Docker Engine >= 24 and the Docker Compose plugin.
- Git installed on the VPS.
- GitHub Container Registry credentials with `read:packages` scope and an active `docker login ghcr.io` session.
- Cloudflare API token with DNS edit permissions for the deployment zone (stored in `CF_DNS_API_TOKEN`).
- Populated `deploy/production/secrets/.env.production` file that mirrors the application
  `.env`, including the variables referenced below.
- `deploy/production/traefik/acme.json` present with `chmod 600` applied. The repository
  includes an empty placeholder that can be reused.

## Environment Variables

| Variable | Description |
| --- | --- |
| `APP_IMAGE` | GHCR image tag to deploy. Defaults to `ghcr.io/hafizhfadh/learningcenter:latest`. |
| `APP_HOST` | Primary domain routed to the Octane application (default `learning.csi-academy.id`). |
| `CF_DNS_API_TOKEN` | Cloudflare token used by Traefik for the DNS-01 challenge. |
| `TRAEFIK_ACME_EMAIL` | Email registered with Let's Encrypt. |
| `TRAEFIK_DOCKER_NETWORK` | (Optional) Override the Docker network Traefik watches. |
| `TRAEFIK_LOG_LEVEL` | Adjust Traefik log verbosity (`INFO`, `WARN`, `ERROR`, `DEBUG`). |
| `RUN_MIGRATIONS` | Set to `0` to skip migrations during deployment. |
| `HEALTH_TIMEOUT_SECONDS` | Maximum wait time for container health checks (default `180`). |

All other Laravel-specific environment keys (database, Redis, mail, Sentry, etc.) must
also be configured in `.env.production`.

## DNS Configuration

Point the desired hostnames to the VPS before executing the deployment:

1. Create an `A` (and optionally `AAAA`) record for `APP_HOST` targeting the server IP.
2. Ensure the Cloudflare zone matches the `APP_HOST` domain and the API token has
   permissions for `Zone:DNS:Edit` and `Zone:Read`.
3. Disable the orange-cloud proxy in Cloudflare so Traefik can complete DNS challenges
   directly via the API token.

## Deployment Steps

1. **Pull the latest code & images** — `bin/deploy.sh` performs `git pull --rebase` and
   `docker compose pull` automatically. If the GHCR image is private, authenticate first:
   ```bash
   echo "$GHCR_TOKEN" | docker login ghcr.io -u <github-username> --password-stdin
   ```
   (Set `SKIP_IMAGE_SYNC=1` when rerunning the script to reuse locally cached images.)
2. **Traefik bootstrapping** — the script recreates the stack using
   `deploy/production/docker-compose.yml`. Traefik provisions/renews certificates via the
   Cloudflare DNS challenge and exposes the Octane app on ports 80/443.
3. **Run migrations & optimize caches** — migrations execute by default (set
   `RUN_MIGRATIONS=0` to skip). Afterwards, Laravel caches for config, routes, events, and
   compiled files are refreshed for peak performance.
4. **Health verification** — the script emits container statuses and relies on the baked-in
   health checks (Octane endpoint for the app, `traefik healthcheck --ping`, etc.).

Execute the deployment from the repository root:

```bash
./deploy/production/bin/deploy.sh
```

Logs are stored under `deploy/production/logs/` with timestamps, enabling historical audits.

## Troubleshooting & Operations

- **Traefik certificates** are persisted in `traefik/acme.json`. If rate limits are hit,
  verify DNS propagation and API token permissions, then remove the file to request a new
  certificate set.
- **Queue restarts** occur automatically at the end of deployment via `php artisan queue:restart`.
- **Scaling workers**: adjust the `OCTANE_WORKERS`, `OCTANE_TASK_WORKERS`, and resource
  reservations in `docker-compose.yml` to suit server capacity. The deploy script will
  pick up new values on the next run.
- **Profiles**: the deployment uses only the core services (app workloads plus Traefik)
  to keep the footprint minimal. Additional services can be added via Compose profiles
  if future requirements emerge.

For observability (metrics, traces, and logs), refer to the resources in the
`deploy/production/observability/` directory.
