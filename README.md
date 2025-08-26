# Laravel Learning Center

<p align="center">
<a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a>
</p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## 🎓 About Laravel Learning Center

Laravel Learning Center is a modern, production-ready educational platform built with Laravel 12, designed for high-performance learning management. The application features a robust admin panel powered by Filament 4 and is optimized for deployment with external PostgreSQL clusters.

### ✨ Key Features

- **🚀 High Performance**: Built with FrankenPHP and Laravel Octane for maximum throughput
- **🛡️ Security First**: Comprehensive authentication, authorization, and security measures
- **📊 Admin Dashboard**: Powerful Filament 4 admin panel with advanced resource management
- **🔄 Scalable Architecture**: Designed for horizontal scaling with external database clusters
- **🐳 Container Ready**: Docker-optimized with multi-stage builds and production configurations
- **📱 Modern UI**: Responsive design with Tailwind CSS v4 and Alpine.js
- **🔍 Advanced Search**: Full-text search capabilities with PostgreSQL
- **📈 Monitoring**: Built-in health checks and performance monitoring

## 🏗️ Technology Stack

### Backend
- **Framework**: Laravel 12 (PHP 8.3+)
- **Database**: PostgreSQL 14+ (External Cluster Support)
- **Cache**: Redis for sessions and application caching
- **Runtime**: FrankenPHP with Laravel Octane
- **Admin Panel**: Filament 4 with advanced resources and widgets

### Frontend
- **CSS Framework**: Tailwind CSS v4
- **JavaScript**: Alpine.js for micro-interactions
- **Build Tool**: Vite for asset compilation
- **Icons**: Heroicons and custom SVG icons

### Infrastructure
- **Containerization**: Docker with multi-stage builds
- **Web Server**: Caddy with HTTP/3 and automatic HTTPS
- **Orchestration**: Docker Compose for development and production
- **Monitoring**: Built-in health checks and logging

## 🚀 Quick Start

### Prerequisites

- PHP 8.3 or higher
- Composer 2.0+
- Node.js 18+ and npm
- Docker and Docker Compose V2
- PostgreSQL 14+ (local or external cluster)
- Redis 6+

### Local Development Setup

```bash
# Clone the repository
git clone https://github.com/your-org/learningcenter.git
cd learningcenter

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your database in .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=learningcenter
# DB_USERNAME=your_username
# DB_PASSWORD=your_password

# Run database migrations
php artisan migrate

# Seed the database (optional)
php artisan db:seed

# Build frontend assets
npm run build

# Start the development server
php artisan serve
```

### Docker Development Setup

```bash
# Clone the repository
git clone https://github.com/your-org/learningcenter.git
cd learningcenter

# Start development environment
docker compose up -d

# Install dependencies
docker compose exec app composer install
docker compose exec app npm install

# Generate application key
docker compose exec app php artisan key:generate

# Run migrations
docker compose exec app php artisan migrate

# Build assets
docker compose exec app npm run build
```

Access the application at `http://localhost:8000`

## 📚 Documentation

### Core Documentation
- **[Production Deployment Guide](DEPLOYMENT.md)** - Comprehensive production deployment instructions
- **[API Documentation](docs/api.md)** - REST API endpoints and usage
- **[Admin Panel Guide](docs/admin.md)** - Filament admin panel documentation
- **[Development Guide](docs/development.md)** - Local development setup and guidelines

### Architecture Documentation
- **[Database Schema](docs/database.md)** - Database structure and relationships
- **[Security Guide](docs/security.md)** - Security best practices and implementation
- **[Performance Guide](docs/performance.md)** - Optimization strategies and monitoring
- **[Testing Guide](docs/testing.md)** - Testing strategies and test suite

## 🔧 Configuration

### Environment Variables

Key environment variables for configuration:

```bash
# Application
APP_NAME="Laravel Learning Center"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=learningcenter
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Cache & Sessions
CACHE_STORE=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
```

### Admin Panel Access

Create an admin user:

```bash
php artisan make:filament-user
```

Access the admin panel at `/admin`

## 🧪 Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run tests with coverage
php artisan test --coverage

# Run tests in parallel
php artisan test --parallel
```

### Test Structure

- **Unit Tests**: `tests/Unit/` - Test individual classes and methods
- **Feature Tests**: `tests/Feature/` - Test application features and workflows
- **Browser Tests**: `tests/Browser/` - Laravel Dusk browser automation tests

## 🚀 Production Deployment

### Quick Production Deployment

For detailed production deployment instructions, see **[DEPLOYMENT.md](DEPLOYMENT.md)**.

```bash
# On your production server
cd /srv/learningcenter

# Configure .env.production with your settings
# Run automated deployment
./deploy.sh v1.0.0
```

### Production Features

- **🔒 Security**: SSL/TLS encryption, CSRF protection, input validation
- **⚡ Performance**: FrankenPHP + Octane for high concurrency
- **📊 Monitoring**: Health checks, logging, and performance metrics
- **🔄 Scalability**: Horizontal scaling with external PostgreSQL clusters
- **🛡️ Reliability**: Automated backups and disaster recovery procedures

## 🤝 Contributing

### Development Workflow

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes and add tests
4. Run the test suite: `php artisan test`
5. Run code quality checks: `composer run-script cs-fix`
6. Commit your changes: `git commit -m 'Add amazing feature'`
7. Push to the branch: `git push origin feature/amazing-feature`
8. Open a Pull Request

### Code Standards

- Follow PSR-12 coding standards
- Write comprehensive tests for new features
- Update documentation for API changes
- Use meaningful commit messages

### Development Tools

```bash
# Code formatting
composer run-script cs-fix

# Static analysis
composer run-script analyse

# Security audit
composer audit

# Dependency updates
composer update
```

## 📊 Performance

### Optimization Features

- **Laravel Octane**: High-performance application server
- **Redis Caching**: Application and session caching
- **Database Optimization**: Query optimization and indexing
- **Asset Optimization**: Vite for efficient asset bundling
- **HTTP/3 Support**: Modern protocol support via Caddy

### Monitoring

- Health check endpoint: `/health`
- Application metrics: Built-in Laravel monitoring
- Database performance: Query logging and analysis
- Container metrics: Docker stats and resource monitoring

## 🔒 Security

### Security Features

- **Authentication**: Laravel Sanctum for API authentication
- **Authorization**: Role-based access control with policies
- **Input Validation**: Comprehensive request validation
- **CSRF Protection**: Cross-site request forgery protection
- **SQL Injection Prevention**: Eloquent ORM with prepared statements
- **XSS Protection**: Output escaping and content security policies

### Security Best Practices

- Regular security updates
- Environment variable protection
- Secure session configuration
- HTTPS enforcement in production
- Database connection encryption

## 📞 Support

### Getting Help

- **Documentation**: Check the [docs](docs/) directory
- **Issues**: Report bugs on [GitHub Issues](https://github.com/your-org/learningcenter/issues)
- **Discussions**: Join [GitHub Discussions](https://github.com/your-org/learningcenter/discussions)
- **Security**: Report security issues to security@your-domain.com

### Troubleshooting

Common issues and solutions:

1. **Database Connection Issues**: Check [DEPLOYMENT.md](DEPLOYMENT.md#database-connection-issues)
2. **Docker Problems**: See [Docker troubleshooting](DEPLOYMENT.md#container-issues)
3. **Performance Issues**: Review [Performance Guide](docs/performance.md)
4. **Security Concerns**: Check [Security Guide](docs/security.md)

## 📄 License

The Laravel Learning Center is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🙏 Acknowledgments

- **Laravel Team**: For the amazing Laravel framework
- **Filament Team**: For the powerful admin panel
- **FrankenPHP Team**: For the high-performance PHP server
- **Community Contributors**: For their valuable contributions

---

<p align="center">
Built with ❤️ using Laravel, Filament, and FrankenPHP
</p>
