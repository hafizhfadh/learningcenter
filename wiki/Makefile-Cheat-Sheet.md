# Makefile Cheat Sheet

Back to [Home](Home.md)

- `make up`: start local development containers
- `make down`: stop local containers
- `make logs`: stream logs from all local services
- `make shell:app`: shell into the app container
- `make update`: rebuild and update local containers
- `make prod-up`: start the production stack (requires `.env.production`)
- `make prod-pull`: pull latest production images from GHCR
- `make prod-down`: stop the production stack
- `make prod-logs`: tail production logs