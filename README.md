# Laravel Learning Center

A modern learning management system built with Laravel 12, Filament 4, and FrankenPHP.

## 🚀 Features

- **Modern Tech Stack**: Laravel 12, Filament 4, FrankenPHP, PostgreSQL
- **Admin Panel**: Comprehensive admin interface with Filament 4
- **High Performance**: FrankenPHP for optimal performance
- **Containerized**: Docker-ready for development
- **Database**: PostgreSQL with optimized configurations
- **Caching**: Redis for session and cache management
- **Security**: Built-in security best practices

## 🛠️ Technology Stack

- **Backend**: Laravel 12
- **Admin Panel**: Filament 4
- **Web Server**: FrankenPHP (PHP + Caddy)
- **Database**: PostgreSQL 16
- **Cache**: Redis 7
- **Containerization**: Docker & Docker Compose
- **Process Manager**: Supervisor


## 🚀 Quick Start

### Prerequisites

- PHP 8.3 or higher
- Composer 2.0+
- Node.js 18+ and npm
- Docker and Docker Compose V2
- PostgreSQL 14+
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



## 🔧 Quick Reference Commands

### Docker Commands
```bash
# Start development environment
docker-compose up -d

# View logs
docker-compose logs -f app

# Run artisan commands
docker-compose exec app php artisan migrate

# Access container shell
docker-compose exec app bash

# Check deployment status
docker-compose ps
```

### Health Checks
```bash
# Application health
curl -f http://localhost:8080/health

# Database connectivity
psql -h $DB_HOST -p $DB_PORT -U $DB_USERNAME -d $DB_DATABASE -c "SELECT 1;"

# Redis connectivity
redis-cli -h redis -p 6379 -a $REDIS_PASSWORD ping
```

## 🚨 Troubleshooting

### Common Issues

**Database Connection Issues**
```bash
# Test database connection
psql -h $DB_HOST -p $DB_PORT -U $DB_USERNAME -d $DB_DATABASE -c "\l"

# Check SSL requirements
psql "postgresql://$DB_USERNAME:$DB_PASSWORD@$DB_HOST:$DB_PORT/$DB_DATABASE?sslmode=$DB_SSLMODE"
```

**Docker Issues**
```bash
# Rebuild containers
docker-compose down
docker-compose up -d --build

# Clean up Docker resources
docker system prune -f
```



### Performance Optimization

**Laravel Optimization**
```bash
# Clear and cache config
docker-compose exec app php artisan config:cache

# Clear and cache routes
docker-compose exec app php artisan route:cache

# Clear and cache views
docker-compose exec app php artisan view:cache
```

**Docker Optimization**
```bash
# Clean up unused images
docker image prune -f

# Clean up unused volumes
docker volume prune -f
```

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

- Database connection encryption

## 📞 Support

### Getting Help

- **Documentation**: Check the [docs](docs/) directory
- **Issues**: Report bugs on [GitHub Issues](https://github.com/your-org/learningcenter/issues)
- **Discussions**: Join [GitHub Discussions](https://github.com/your-org/learningcenter/discussions)
- **Security**: Report security issues to security@your-domain.com

### Troubleshooting

Common issues and solutions:

1. **Database Connection Issues**: Check database configuration in .env
2. **Docker Problems**: Use `docker-compose logs` to debug issues
3. **Performance Issues**: Review [Performance Guide](docs/performance.md)
4. **Security Concerns**: Check [Security Guide](docs/security.md)

## 📄 License

The Laravel Learning Center is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🙏 Acknowledgments

- **Laravel Team**: For the amazing Laravel framework
- **Filament Team**: For the powerful admin panel

- **Community Contributors**: For their valuable contributions

---

<p align="center">
Built with ❤️ using Laravel and Filament
</p>
