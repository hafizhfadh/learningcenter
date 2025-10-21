# Contribution Guidelines

Back to [Home](Home.md)

## Code style and standards
- PSR-12
- Format code with Laravel Pint before committing: `./vendor/bin/pint`
- Keep routes, controllers, services, and models consistent with existing patterns

## Branching strategy
- Trunk-based development on `main`
- Create feature branches prefixed by type, e.g., `feat/...`, `fix/...`, `chore/...`
- Use Conventional Commits for messages: `feat:`, `fix:`, `docs:`, `refactor:`, `perf:`, `test:`, `ci:`

## Pull request process
- Ensure `composer test` passes locally and in CI
- Apply code formatting (`./vendor/bin/pint`)
- Update documentation when necessary (README, `docs/DEPLOYMENT.md`, etc.)
- For UI changes, include screenshots or short descriptions
- Link related issues and call out migrations or environment changes explicitly

## Issue reporting format
Include the following:
- Summary of the problem
- Steps to reproduce
- Expected vs actual behavior
- Environment details (OS, Docker/Compose versions, app version)
- Logs or stack traces (attach files or paste relevant excerpts)
- Impact assessment (e.g., blocks enrollment, breaks progress tracking)

For security issues, do not open a public issue—contact the maintainers privately.

### See also
- Agent Guidelines: [AGENTS.md](../AGENTS.md) • GitHub view: https://github.com/hafizhfadh/learningcenter/blob/main/AGENTS.md