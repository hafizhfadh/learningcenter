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
- **Build Process**: GitHub Actions or local builds, not on production servers

## Quick Start

### Prerequisites
- Docker & Docker Compose
- External PostgreSQL database
- GitHub Container Registry access
- SSL certificates (Let's Encrypt recommended)

### Environment Setup

1. **Clone and configure**:
```bash
git clone <repository-url>
cd learningcenter
cp .env.example .env.production
```

2. **Configure environment variables**:
```bash
# Application
APP_NAME="Learning Center"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database (External PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=your-postgres-server
DB_PORT=5432
DB_DATABASE=learning_center
DB_USERNAME=your-db-user
DB_PASSWORD=your-secure-password

# Redis (Containerized)
REDIS_HOST=redis
REDIS_PASSWORD=your-redis-password

# Container Registry
GITHUB_REPOSITORY=your-org/learning-center
IMAGE_TAG=latest

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-server
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-email-password
```

3. **Deploy**:
```bash
# Quick deployment
./scripts/deploy-production.sh

# Or manual deployment
docker-compose -f docker-compose.production.yml up -d
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

## Production Deployment

### Container Images
The application uses multi-stage Docker builds optimized for production:
- **Base**: PHP 8.2 with required extensions
- **Dependencies**: Composer and npm packages
- **Production**: Optimized runtime with Laravel Octane

### Monitoring & Health Checks
- **Health Endpoint**: `/health` for load balancer checks
- **Horizon Dashboard**: Queue monitoring via Filament
- **Prometheus Metrics**: Application and infrastructure monitoring
- **Log Aggregation**: Centralized logging with structured output

### Security Features
- **Authentication**: Laravel's built-in authentication
- **Authorization**: Role-based access control
- **CSRF Protection**: Enabled for all forms
- **SQL Injection Prevention**: Eloquent ORM protection
- **XSS Protection**: Blade template escaping
- **Rate Limiting**: API and route protection

## Documentation

For detailed deployment and operational guidance, see:
- [Production Deployment Guide](docs/PRODUCTION_DEPLOYMENT_GUIDE.md)
- [Infrastructure Documentation](docs/INFRASTRUCTURE.md)
- [Security Guidelines](docs/SECURITY.md)
- [Monitoring Setup](docs/MONITORING.md)
- [Backup & Recovery](docs/BACKUP_DISASTER_RECOVERY.md)

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
