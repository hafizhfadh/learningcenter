# Architecture & Infrastructure Overview

Back to [Home](Home.md)

## Application stack
- Backend: Laravel 12, PHP 8.3/8.4
- Frontend: Blade + Vite
- Admin: Filament 4
- Performance: Laravel Octane + FrankenPHP
- Database: PostgreSQL 16 (external servers)
- Cache/Queue: Redis
- Reverse Proxy: Traefik v3 (DNS-01 via Cloudflare)

## Core features
- Learning paths and course management
- Progress tracking and enrollments
- Task and submission system
- Filament-powered admin panel

## Deployment model
- Application nodes: app, horizon, queue, scheduler, reverse proxy
- Database servers: managed separately
- Container registry: GHCR for pre-built images
- Build process: GitHub Actions or local builds

### See also
- Deployment Guide: [docs/DEPLOYMENT.md](../docs/DEPLOYMENT.md) • GitHub view: https://github.com/hafizhfadh/learningcenter/blob/main/docs/DEPLOYMENT.md
- CI/CD Pipeline: [docs/CICD.md](../docs/CICD.md) • GitHub view: https://github.com/hafizhfadh/learningcenter/blob/main/docs/CICD.md