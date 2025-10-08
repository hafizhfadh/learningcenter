# Repository Guidelines

## Project Structure & Module Organization
- `app/` contains domain logic (services, jobs, Filament resources); keep feature code close to its bounded context (e.g., `App/LearningPaths`).
- HTTP entry points live in `routes/` (`web.php`, `api.php`, `console.php`); align new endpoints with existing route files.
- UI assets reside in `resources/` (`views/` for Blade templates, `js/` for Vite builds, `css/` for Tailwind). Publish shared docs under `docs/`.
- Production automation sits in `deploy/production/` alongside `Makefile` recipes (`prod-up`, `prod-pull`). Tests belong to `tests/Unit`, `tests/Feature`, and `tests/Feature/EndToEnd`.

## Build, Test, and Development Commands
- `composer install && npm install` prepares PHP and Vite dependencies.
- `./vendor/bin/sail up -d` or `make up` brings up the Dockerized dev stack.
- `composer dev` runs the Laravel server, queue listener, log tail, and Vite watcher concurrently.
- `npm run build` compiles production assets; `vite build` behaves identically inside CI.
- `make prod-pull prod-up` refreshes the production stack after a new GHCR image ships.

## Coding Style & Naming Conventions
- Follow PSR-12 with 4-space indentation; format PHP with `./vendor/bin/pint`.
- Use descriptive class names in StudlyCase (`app/Services/LessonProgressService.php`) and snake_case migration files.
- Blade views follow kebab-case (`resources/views/learning-path/index.blade.php`); JavaScript modules use lowerCamelCase exports.
- Keep environment-specific values in `.env`; never hard-code secrets.

## Testing Guidelines
- Prefer `composer test` (clears config cache, then `php artisan test`) before pushes.
- Feature and end-to-end suites boot SQLite in-memory; reset state with `php artisan migrate --graceful`.
- Name tests with intent (`LessonServiceTest::test_it_enforces_prerequisites`) and cover main learning-path flows.
- Capture coverage locally via `php artisan test --coverage`; keep regressions above the CI baseline.

## Commit & Pull Request Guidelines
- Use Conventional Commit prefixes (`feat: add lesson bookmarking`, `fix: prevent null enrollment`). Scope optional, colon required.
- Each PR should summarize the change, link related issues, note migrations, and include UI screenshots when Blade updates touch UX.
- Verify `composer test` and linting before requesting review; reference any Sail or Docker steps needed for QA.

## Security & Configuration Tips
- Store deployment secrets under `deploy/production/secrets/.env.production`; rotate values alongside releases.
- Review `config/` overrides when adding services and ensure queues, Horizon workers, and Octane settings remain consistent across environments.
