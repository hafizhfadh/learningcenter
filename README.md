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
