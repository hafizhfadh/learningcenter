# Learning Center Development Guidelines

## Project Overview
This is a **Learning Management System (LMS)** built with Laravel, featuring course management, learning paths, progress tracking, and administrative tools powered by Filament. The system supports multi-tenant architecture with domain-based routing.

### Core Domain Models
- **Courses**: Individual learning units with lessons and sections
- **Learning Paths**: Structured sequences of courses
- **Lessons**: Content units (video, pages, quiz) within courses
- **Enrollments**: User course registrations with progress tracking
- **Tasks**: Interactive assignments and submissions
- **Institutions**: Multi-tenant organization support

## Project Structure & Organization

### Application Architecture
```
app/
├── Models/           # Domain entities (Course, Lesson, LearningPath, Enrollment, etc.)
├── Services/         # Business logic (LessonService, CaddyService)
├── Http/Controllers/ # Request handling (User/LessonController)
├── Filament/         # Admin panel resources and widgets
└── Helpers/          # Utility classes (StorageHelper)
```

### Key Directories
- **`routes/`**: HTTP endpoints (`web.php`, `console.php`) - align new routes with existing patterns
- **`resources/`**: Frontend assets (`views/` for Blade, `js/` and `css/` for Vite builds)
- **`database/`**: Migrations, factories, and seeders for learning domain
- **`tests/`**: Unit, Feature, and EndToEnd test suites
- **`deploy/production/`**: Production deployment configuration with Docker Compose and Traefik
- **`docs/`**: Project documentation (when needed)

## Development Environment

### Setup Commands
```bash
# Install dependencies
composer install && npm install

# Start development environment
./vendor/bin/sail up -d    # or: make up

# Run development server with all services
composer dev               # Starts server, queue, logs, and Vite concurrently
```

### Available Make Commands
- `make up` / `make down`: Control development containers
- `make shell:app`: Access application container shell
- `make logs`: View all container logs
- `make prod-up` / `make prod-down`: Control production stack
- `make prod-pull`: Update production images from GHCR

### Build & Asset Management
- `npm run dev`: Development asset compilation with Vite
- `npm run build`: Production asset compilation
- Assets use Tailwind CSS for styling

## Coding Standards & Conventions

### PHP Standards
- **PSR-12** compliance with 4-space indentation
- **Format code**: `./vendor/bin/pint` before commits
- **Class naming**: StudlyCase (`LessonService`, `EnrollmentResource`)
- **File naming**: Match class names (`app/Services/LessonService.php`)

### Database Conventions
- **Migrations**: snake_case with timestamps (`2025_07_25_072352_create_courses_table.php`)
- **Model relationships**: Use proper Eloquent relationships with pivot tables
- **Factories**: Provide realistic test data for learning domain

### Frontend Conventions
- **Blade views**: kebab-case (`resources/views/user/learning-path/index.blade.php`)
- **JavaScript**: lowerCamelCase for variables and functions
- **CSS**: Tailwind utility classes, custom styles in `resources/css/app.css`

### Environment & Security
- **Environment variables**: Store in `.env`, never hard-code secrets
- **Multi-tenant**: Use domain-based tenant resolution
- **File storage**: Support multiple disks (public, S3-compatible via StorageHelper)

## Testing Strategy

### Test Execution
```bash
composer test              # Clears config cache, runs PHPUnit
php artisan test --coverage # Generate coverage reports
```

### Test Organization
- **Unit tests**: `tests/Unit/` - Test individual classes and methods
- **Feature tests**: `tests/Feature/` - Test application features and integrations
- **EndToEnd tests**: `tests/Feature/EndToEnd/` - Full user workflow testing

### Test Naming & Structure
- **Descriptive names**: `LessonServiceTest::test_it_enforces_course_prerequisites()`
- **Learning domain focus**: Cover enrollment flows, progress tracking, course navigation
- **Database**: Tests use SQLite in-memory, reset with `php artisan migrate --graceful`

## Git Workflow & Collaboration

### Commit Standards
- **Conventional Commits**: `feat:`, `fix:`, `docs:`, `refactor:`, `test:`
- **Examples**: 
  - `feat: add lesson completion tracking`
  - `fix: prevent duplicate enrollments`
  - `refactor: optimize course query performance`

### Pull Request Guidelines
- **Summary**: Clear description of changes and business impact
- **Context**: Link related issues, mention affected learning flows
- **Database changes**: Note any migrations or seeder updates
- **UI changes**: Include screenshots for Blade template modifications
- **Testing**: Verify `composer test` passes and include relevant test updates

### Pre-commit Checklist
- [ ] `composer test` passes
- [ ] `./vendor/bin/pint` formatting applied
- [ ] New features have appropriate tests
- [ ] Database changes include migrations
- [ ] Environment variables documented in `.env.example`

## Production Deployment

### Architecture
- **Application**: Laravel with Octane (FrankenPHP)
- **Database**: External PostgreSQL 16 clusters
- **Cache/Queue**: Redis with TLS
- **Reverse Proxy**: Traefik v3 with automatic SSL (Cloudflare DNS-01)
- **Monitoring**: Prometheus with lightweight exporters

### Deployment Process
- **Images**: Built and stored in GitHub Container Registry (GHCR)
- **Secrets**: Managed in `deploy/production/secrets/.env.production`
- **Updates**: `make prod-pull prod-up` for rolling updates
- **Multi-tenant**: Domain-based routing through Traefik labels

### Configuration Management
- **Environment consistency**: Ensure queue, Horizon, and Octane settings align across environments
- **Security**: Rotate secrets with releases, use TLS for all external connections
- **Performance**: Leverage Redis caching, database connection pooling, and CDN for assets
