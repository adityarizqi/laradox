# Laradox Package Structure

This document provides an overview of the Laradox package structure and how to use it.

## Package Overview

**Laradox** is a plug-and-play Docker environment library for Laravel applications with FrankenPHP, Nginx, and Octane support. It provides a complete Docker setup that can be installed via Composer.

## Directory Structure

```
laradox/
├── config/
│   └── laradox.php                          # Configuration file
├── src/
│   ├── Console/
│   │   ├── DownCommand.php                  # Stop containers command
│   │   ├── InstallCommand.php               # Installation command
│   │   ├── SetupSSLCommand.php              # SSL setup command
│   │   └── UpCommand.php                    # Start containers command
│   └── LaradoxServiceProvider.php           # Laravel service provider
├── stubs/
│   ├── docker/
│   │   ├── nginx/                           # Nginx configuration files
│   │   │   ├── nginx.conf
│   │   │   ├── conf.d/app.conf
│   │   │   └── ssl/
│   │   └── php/                             # PHP/FrankenPHP configuration
│   │       ├── php.dockerfile
│   │       ├── supervisord.conf
│   │       ├── laravel-worker.conf
│   │       └── config/schedule.cron
│   ├── docker-compose.development.yml       # Development Docker Compose
│   ├── docker-compose.production.yml        # Production Docker Compose
│   ├── composer                             # Composer helper script
│   ├── npm                                  # NPM helper script
│   └── php                                  # PHP/Artisan helper script
├── .gitignore
├── CHANGELOG.md
├── composer.json
├── CONTRIBUTING.md
├── LICENSE
└── README.md
```

## Installation in Laravel Projects

### 1. Install via Composer

```bash
composer require adityarizqi/laradox --dev
```

### 2. Run Installation Command

```bash
php artisan laradox:install
```

This will publish all necessary files to your Laravel project:
- `docker/` directory with all Docker configurations
- `docker-compose.development.yml` and `docker-compose.production.yml`
- Helper scripts: `composer`, `npm`, `php`
- Configuration file: `config/laradox.php`

### 3. Setup SSL

```bash
php artisan laradox:setup-ssl
```

### 4. Start Containers

```bash
php artisan laradox:up --detach
```

## Available Artisan Commands

| Command | Description |
|---------|-------------|
| `laradox:install` | Install Laradox files into your project |
| `laradox:setup-ssl` | Generate SSL certificates using mkcert |
| `laradox:up` | Start Docker containers |
| `laradox:down` | Stop Docker containers |

## Services Included

1. **nginx** - Reverse proxy with SSL termination
2. **php** - FrankenPHP with Laravel Octane
3. **node** - Node.js for asset compilation
4. **scheduler** - Laravel scheduler (dev) / Supercronic (prod)
5. **queue** - Laravel queue workers with Supervisor

## Configuration

All configuration can be done via:
- Environment variables in `.env`
- Configuration file at `config/laradox.php`

### Key Configuration Options

```php
'domain' => 'laravel.docker.localhost',
'environment' => 'development',
'ports' => [
    'http' => 80,
    'https' => 443,
    'frankenphp' => 8080,
],
'queue_workers' => 2,
'php' => [
    'version' => '8.4',
    'user_id' => 1000,
    'group_id' => 1000,
],
```

## Publishing Individual Components

You can also publish components separately:

```bash
# Publish only config
php artisan vendor:publish --tag=laradox-config

# Publish only Docker files
php artisan vendor:publish --tag=laradox-docker

# Publish only Docker Compose files
php artisan vendor:publish --tag=laradox-compose

# Publish only helper scripts
php artisan vendor:publish --tag=laradox-scripts

# Publish everything
php artisan vendor:publish --tag=laradox
```

## Development Workflow

1. Install Laradox in your Laravel project
2. Generate SSL certificates
3. Start containers
4. Use helper scripts to run commands:
   - `./composer install`
   - `./npm install && ./npm run dev`
   - `./php artisan migrate`

## Production Deployment

1. Set environment to production:
   ```env
   LARADOX_ENV=production
   ```

2. Start containers:
   ```bash
   php artisan laradox:up --env=production --build --detach
   ```

3. The production setup includes:
   - Optimized Docker images
   - Supervisor for queue workers
   - Supercronic for scheduled tasks
   - Logging with rotation
   - Health checks

## Helper Scripts

The helper scripts allow running commands inside containers without entering them:

```bash
./composer require vendor/package
./npm install
./php artisan migrate
```

These scripts use the development Docker Compose file by default.

## Customization

After publishing, you can customize:
- Docker Compose files for additional services
- Nginx configuration for custom domains or SSL settings
- PHP Dockerfile for additional extensions
- Supervisor configuration for queue workers
- Cron schedule for Laravel scheduler

## Repository Publishing

To publish this package to Packagist:

1. Create a GitHub repository named `laradox`
2. Push this code to the repository
3. Create a release/tag (e.g., `v1.0.0`)
4. Submit the package to [Packagist.org](https://packagist.org)

Then users can install it with:
```bash
composer require adityarizqi/laradox --dev
```

## License

MIT License - see LICENSE file for details.
