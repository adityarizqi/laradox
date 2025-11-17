# Laradox

[![Tests](https://github.com/adityarizqi/laradox/workflows/Tests/badge.svg)](https://github.com/adityarizqi/laradox/actions)
[![Latest Stable Version](https://poser.pugx.org/adityarizqi/laradox/v)](https://packagist.org/packages/adityarizqi/laradox)
[![License](https://poser.pugx.org/adityarizqi/laradox/license)](https://packagist.org/packages/adityarizqi/laradox)

> **Plug-and-play Docker environment for Laravel with FrankenPHP, Nginx, and Octane support**

Laradox provides a production-ready Docker environment optimized for Laravel Octane with FrankenPHP. It's designed for both local development and production deployments, with automatic HTTPS support using mkcert.

## Features

- 🚀 **Laravel Octane** with FrankenPHP for blazing-fast performance
- 🔒 **HTTPS by default** using mkcert for local development
- 🐳 **Docker Compose** configurations for development and production
- ⚡ **Nginx** as reverse proxy with optimized settings
- 🔧 **Queue workers** with Supervisor
- ⏰ **Scheduler** with Supercronic
- 📦 **Helper scripts** for composer, npm, and php commands
- 🎯 **Easy installation** via Composer

## Performance

Comparison of performance measurements between *without* and *with* FrankenPHP under static test conditions:

| Without FrankenPHP | With FrankenPHP |
| --- | --- |
| ![Without FrankenPHP](https://dl.dropboxusercontent.com/scl/fi/lb72q5zzi6q2f6bdny5pn/with_out_franken_php.jpeg?rlkey=vew9og9gda25u7ofdq2vlsesd&e=1&st=d3nlrnvs&dl=0) | ![With FrankenPHP](https://dl.dropboxusercontent.com/scl/fi/ibskidxfhtgsx55ykrolw/with_franken_php.jpeg?rlkey=j9dnhycufuttrrcptjm4h786m&e=1&st=yqofcch2&dl=0) |

## Requirements

- PHP 8.2 or higher
- Laravel 10.x or higher
- Docker and Docker Compose
- [mkcert](https://github.com/FiloSottile/mkcert) (for SSL certificates)

## Installation

### Step 1: Install via Composer

```bash
composer require adityarizqi/laradox --dev
```

### Step 2: Install Laravel Octane

```bash
composer require laravel/octane
```

### Step 3: Install Laradox

```bash
php artisan laradox:install
```

This command will:
- Publish Docker configuration files
- Publish Docker Compose files for development and production
- Publish helper scripts (composer, npm, php)
- Create necessary directories
- Make scripts executable

### Step 4: Setup SSL Certificates

Install [mkcert](https://github.com/FiloSottile/mkcert/releases) and run:

```bash
php artisan laradox:setup-ssl
```

Or manually:

```bash
mkcert -install -cert-file ./docker/nginx/ssl/cert.pem -key-file ./docker/nginx/ssl/key.pem "*.docker.localhost" docker.localhost
```

> **Windows WSL2 Users**: Run the mkcert command on the Windows side to install certificates in your Windows trust store.

### Step 5: Start Docker Containers

**Development:**

```bash
php artisan laradox:up --detach
```

Or using Docker Compose directly:

```bash
docker compose -f docker-compose.development.yml up -d
```

**Production:**

```bash
php artisan laradox:up --environment=production --detach
```

### Step 6: Install Dependencies

```bash
./composer install
./npm install
./npm run dev
```

### Step 7: Setup Laravel

```bash
./php artisan key:generate
./php artisan migrate:fresh --seed
```

You're done! Open https://laravel.docker.localhost to view your application.

## Usage

### Artisan Commands

Laradox provides several artisan commands for managing your Docker environment:

```bash
# Install Laradox files
php artisan laradox:install [--force]

# Setup SSL certificates
php artisan laradox:setup-ssl [--domain=example.com]

# Start containers
php artisan laradox:up [--environment=development] [--detach] [--build]

# Stop containers
php artisan laradox:down [--environment=development] [--volumes]
```

### Helper Scripts

The helper scripts allow you to run commands inside containers without entering them:

```bash
# Run composer commands
./composer install
./composer update
./composer require vendor/package

# Run npm commands
./npm install
./npm run dev
./npm run build

# Run PHP/Artisan commands
./php artisan migrate
./php artisan queue:work
./php artisan tinker
```

### Docker Compose Commands

For direct control over Docker:

```bash
# Development
docker compose -f docker-compose.development.yml up -d
docker compose -f docker-compose.development.yml down

# Production
docker compose -f docker-compose.production.yml up -d --build
docker compose -f docker-compose.production.yml down

# View logs
docker compose -f docker-compose.development.yml logs -f

# Restart specific service
docker compose -f docker-compose.development.yml restart php
```

## Configuration

### Environment Variables

You can customize Laradox behavior using environment variables in your `.env` file:

```env
# Domain configuration
LARADOX_DOMAIN=laravel.docker.localhost

# Environment
LARADOX_ENV=development

# Ports
LARADOX_HTTP_PORT=80
LARADOX_HTTPS_PORT=443
LARADOX_FRANKENPHP_PORT=8080

# Queue workers (production)
QUEUE_WORKER_CPUS=2

# User IDs (for file permissions)
LARADOX_USER_ID=1000
LARADOX_GROUP_ID=1000
```

### Configuration File

Publish and customize the configuration file:

```bash
php artisan vendor:publish --tag=laradox-config
```

Edit `config/laradox.php` to customize domains, ports, SSL paths, and more.

## Services

Laradox includes the following services:

- **nginx**: Reverse proxy with SSL termination
- **php**: FrankenPHP with Laravel Octane
- **node**: Node.js for asset compilation
- **scheduler**: Laravel scheduler (development) or Supercronic (production)
- **queue**: Laravel queue worker with Supervisor (production only)

### Scheduler Configuration

The scheduler service handles Laravel's task scheduling differently based on environment:

**Development:**
- Uses `php artisan schedule:work` for real-time scheduling
- Automatically detects and runs scheduled tasks

**Production:**
- Uses [Supercronic](https://github.com/aptible/supercronic) for reliable cron execution
- Configuration file: `docker/php/config/schedule.cron`
- Runs `php artisan schedule:run` every minute

To modify the schedule in production, edit `docker/php/config/schedule.cron`:

```cron
* * * * * cd /srv && php artisan schedule:run >> /dev/null 2>&1
```

> **Note**: Define your actual scheduled tasks in `app/Console/Kernel.php` using Laravel's scheduler. The cron file only triggers Laravel's scheduler.

## Customization

### Custom Domain

To use a custom domain:

1. Update the domain in `config/laradox.php` or `.env`:
   ```env
   LARADOX_DOMAIN=myapp.test
   ```

2. Generate SSL certificate:
   ```bash
   php artisan laradox:setup-ssl --domain=myapp.test
   ```

3. Restart the containers to apply the domain change:
   ```bash
   php artisan laradox:down
   php artisan laradox:up --detach
   ```

4. Add domain to your `/etc/hosts` file (if not using .localhost)

> **Note**: The domain is automatically configured in Nginx using environment variables. You don't need to manually edit `docker/nginx/conf.d/app.conf`.

### Docker Configuration

You can customize the Docker setup by modifying the published files:

- `docker-compose.development.yml` - Development environment
- `docker-compose.production.yml` - Production environment
- `docker/php/php.dockerfile` - PHP/FrankenPHP image
- `docker/nginx/nginx.conf` - Nginx configuration
- `docker/nginx/conf.d/app.conf` - Application server block

## Troubleshooting

### Permission Issues

If you encounter permission issues, adjust the user IDs:

```env
LARADOX_USER_ID=1000
LARADOX_GROUP_ID=1000
```

Rebuild the containers:

```bash
php artisan laradox:down --volumes
php artisan laradox:up --build --detach
```

### SSL Certificate Issues

Reinstall mkcert and regenerate certificates:

```bash
mkcert -uninstall
php artisan laradox:setup-ssl
```

### Port Conflicts

If ports 80/443 are already in use, change them in `.env`:

```env
LARADOX_HTTP_PORT=8080
LARADOX_HTTPS_PORT=8443
```

Update `docker-compose.*.yml` files accordingly.

## License

Laradox is open-sourced software licensed under the [MIT license](LICENSE).

## Testing

Laradox includes a comprehensive test suite with 47 tests covering all functionality.

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage report
vendor/bin/phpunit --coverage-html build/coverage

# Run specific test file
vendor/bin/phpunit tests/Feature/InstallCommandTest.php
```

## Credits

Created by [Aditya Rizqi Januarta](https://github.com/adityarizqi)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
