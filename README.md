# Laradox

[![Tests](https://github.com/adityarizqi/laradox/workflows/Tests/badge.svg)](https://github.com/adityarizqi/laradox/actions)
[![Latest Stable Version](https://poser.pugx.org/adityarizqi/laradox/v)](https://packagist.org/packages/adityarizqi/laradox)
[![License](https://poser.pugx.org/adityarizqi/laradox/license)](https://packagist.org/packages/adityarizqi/laradox)

> **Plug-and-play Docker environment for Laravel with FrankenPHP, Nginx, and Octane support**

Laradox provides a production-ready Docker environment optimized for Laravel Octane with FrankenPHP. It's designed for both local development and production deployments, with automatic HTTPS support using mkcert.

## Features

- **Laravel Octane** with FrankenPHP for blazing-fast performance
- **HTTPS support** - optional for development, **required for production**
- **Docker Compose** configurations for development and production
- **Nginx** as reverse proxy with optimized settings
- **Queue workers** with Supervisor
- **Scheduler** with Supercronic
- **Helper scripts** for composer, npm, and php commands
- **Easy installation** via Composer

## Performance

Comparison of performance measurements between *without* and *with* FrankenPHP under static test conditions:

| Without FrankenPHP | With FrankenPHP |
| --- | --- |
| ![Without FrankenPHP](https://dl.dropboxusercontent.com/scl/fi/lb72q5zzi6q2f6bdny5pn/with_out_franken_php.jpeg?rlkey=vew9og9gda25u7ofdq2vlsesd&e=1&st=d3nlrnvs&dl=0) | ![With FrankenPHP](https://dl.dropboxusercontent.com/scl/fi/ibskidxfhtgsx55ykrolw/with_franken_php.jpeg?rlkey=j9dnhycufuttrrcptjm4h786m&e=1&st=yqofcch2&dl=0) |

## Requirements

- PHP 8.2 or higher (PHP 8.3+ when using Laravel 13.x)
- Laravel 10.x, 11.x, 12.x, or 13.x
- Docker and Docker Compose (auto-detected, installation prompted if missing)
- [mkcert](https://github.com/FiloSottile/mkcert) (auto-detected, installation prompted if missing)

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

**For Development (Optional):**

Setup SSL certificates for trusted HTTPS:

```bash
php artisan laradox:setup-ssl
```

Laradox will automatically:
- Detect if mkcert is installed
- Prompt to install mkcert if missing (supports Ubuntu, Debian, Fedora, CentOS, and macOS)
- Guide you through the installation process
- Generate certificates once mkcert is available

Or manually:

```bash
mkcert -install -cert-file ./docker/nginx/ssl/cert.pem -key-file ./docker/nginx/ssl/key.pem "*.docker.localhost" docker.localhost
```

> **Development**: SSL is optional. You can run with HTTP only (port 80) without any certificates. Laradox will automatically use HTTP-only configuration.

**For Production (Required):**

SSL certificates are **mandatory** for production environments. The `laradox:up` command will refuse to start production containers without valid SSL certificates.

```bash
php artisan laradox:setup-ssl
# Or use --force-ssl=false to bypass (not recommended)
```

> **Windows Users**: mkcert installation is not automated on Windows. Please download from [mkcert releases](https://github.com/FiloSottile/mkcert/releases) and run manually.

> **WSL2 Users**: Run the mkcert command on the Windows side to install certificates in your Windows trust store.

### Step 5: Start Docker Containers

Laradox automatically checks for Docker and Docker Compose before starting containers.

**Development:**

```bash
php artisan laradox:up --detach
```

If Docker is not installed, Laradox will:
- Detect your operating system (Ubuntu, Debian, Fedora, CentOS, macOS, Windows)
- Provide installation instructions
- Prompt to install Docker automatically (Linux and macOS)
- Guide you through the installation process

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

You're done! Open https://laravel.docker.localhost to view your application (or http://laravel.docker.localhost if SSL is not configured).

## Usage

### Artisan Commands

Laradox provides several artisan commands for managing your Docker environment:

```bash
# Install Laradox files
php artisan laradox:install [--force]

# Setup SSL certificates
php artisan laradox:setup-ssl [--domain=example.com]

# Start containers (auto-detects SSL)
php artisan laradox:up [--environment=development] [--detach] [--build]

# Force HTTPS (requires SSL certificates)
php artisan laradox:up --force-ssl=true [--detach]

# Force HTTP only (no SSL)
php artisan laradox:up --force-ssl=false [--detach]

# Stop containers
php artisan laradox:down [--environment=development] [--volumes]

# View container logs
php artisan laradox:logs [service] [--follow] [--tail=100] [--timestamps]

# Enter container shell interactively
php artisan laradox:shell [service] [--environment=development] [--user=www-data] [--shell=bash]

# Show service health and resource usage
php artisan laradox:status [service] [--stats] [--watch] [--json]

# Build (or clear) the production caches inside the containers
php artisan laradox:optimize [--environment=production] [--clear]

# Deploy: pull, build, migrate, optimize and health-check
php artisan laradox:deploy [--environment=production] [--dry-run] [--force]

# Benchmark the application over HTTP
php artisan laradox:benchmark [url] [--requests=200] [--concurrency=10]
```

#### SSL Configuration Options

The `--force-ssl` flag controls SSL behavior:

- **Not specified (default)**: Auto-detects SSL certificates
  - Development: Prompts if missing, allows HTTP-only
  - Production: Requires SSL, fails if missing
- **`--force-ssl=true`**: Forces HTTPS, requires valid certificates
- **`--force-ssl=false`**: Forces HTTP-only, ignores certificates

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

### Interactive Shell Access

Enter containers interactively for debugging, exploration, or manual operations:

```bash
# Enter PHP container (default with sh shell)
php artisan laradox:shell

# Enter specific service
php artisan laradox:shell nginx
php artisan laradox:shell node

# Use different shell (automatically falls back to sh if unavailable)
php artisan laradox:shell --shell=bash
php artisan laradox:shell --shell=zsh

# Run as specific user
php artisan laradox:shell --user=www-data

# Production environment
php artisan laradox:shell --environment=production
```

Available services: `php`, `nginx`, `node`, `scheduler`, `queue`

### Service Health & Monitoring

`laradox:status` reports every service declared in the compose file — including the ones that
have no container yet — with its state, healthcheck result, uptime and published ports:

```bash
# One-off report
php artisan laradox:status

# Add CPU, memory and network usage per container
php artisan laradox:status --stats

# Keep the report on screen, refreshing every 5 seconds
php artisan laradox:status --watch --stats --interval=10

# A single service
php artisan laradox:status php

# Machine-readable output
php artisan laradox:status --json
```

The command exits with code `1` when any service is missing, stopped, still starting or
unhealthy, so it can gate a CI step or a deployment script:

```bash
php artisan laradox:status --environment=production || echo "Something is down"
```

### Production Optimization

`laradox:optimize` runs Laravel's cache warm-up **inside** the container, then recycles the
Octane workers so the new bootstrap cache is actually picked up:

```bash
# Autoloader + config/route/view/event caches + octane:reload
php artisan laradox:optimize

# Undo it (optimize:clear + reload)
php artisan laradox:optimize --clear

# Skip individual steps
php artisan laradox:optimize --skip-autoloader --skip-reload

# Target another environment or service
php artisan laradox:optimize --environment=development --service=php
```

The Composer autoloader is dumped with `--classmap-authoritative`, and `--no-dev` is added only
for the production environment. Optimizing a development environment asks for confirmation
first, because cached config freezes the current `.env`.

### Deployment

`laradox:deploy` runs the whole release in order and stops at the first failure:

1. `git pull --ff-only`
2. maintenance mode (optional)
3. `docker compose build`
4. `docker compose up -d --remove-orphans`
5. wait for every service to report healthy
6. `composer install --no-dev --optimize-autoloader`
7. `npm ci && npm run build`
8. `php artisan migrate --force`
9. `laradox:optimize`
10. leave maintenance mode

```bash
# Preview the plan, execute nothing
php artisan laradox:deploy --dry-run

# Deploy to production
php artisan laradox:deploy

# Non-interactive (CI), with a maintenance page while the release runs
php artisan laradox:deploy --force --maintenance

# Skip steps that do not apply to your setup
php artisan laradox:deploy --force --no-pull --no-assets --no-migrate
```

Every step can be skipped with `--no-pull`, `--no-build`, `--no-composer`, `--no-assets`,
`--no-migrate` and `--no-optimize`; starting the containers and the health gate always run.
If a step fails, the application is taken back out of maintenance mode and the command prints
where to look. Rolling back means checking out the previous revision and deploying again.

> **Note**: `--force` is required to deploy non-interactively, so an unattended run can never
> apply migrations by accident.

### Benchmarking

`laradox:benchmark` drives concurrent HTTP requests from the host and reports latency
percentiles. It needs no external load-testing tool — only PHP's cURL extension:

```bash
# Benchmark the configured domain (HTTPS when certificates exist)
php artisan laradox:benchmark

# Tune the load
php artisan laradox:benchmark --requests=1000 --concurrency=50 --warmup=25

# A specific URL, ignoring a self-signed development certificate
php artisan laradox:benchmark https://laravel.docker.localhost/api/health --insecure

# Machine-readable output, for tracking numbers between releases
php artisan laradox:benchmark --json > benchmark.json
```

The report covers throughput, success rate, transferred bytes, the status-code distribution and
min/avg/p50/p90/p95/p99/max latency. Warm-up requests are excluded so Octane's first-request
cost does not skew the percentiles.

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

### Nginx Configuration

Laradox automatically uses the appropriate nginx configuration based on your environment and SSL availability:

**Configuration Files:**
- `app-http.conf` - HTTP-only configuration (port 80)
- `app-https.conf` - HTTPS configuration with HTTP→HTTPS redirect
- `app.conf` - Active configuration (auto-generated)

**Automatic Selection:**
- **Development with SSL**: Uses `app-https.conf` (HTTPS enabled)
- **Development without SSL**: Prompts user, uses `app-http.conf` (HTTP-only)
- **Production**: Requires SSL, always uses `app-https.conf`
- **`--force-ssl=true`**: Always uses `app-https.conf`, fails if no certificates
- **`--force-ssl=false`**: Always uses `app-http.conf`, ignores certificates

The configuration is automatically selected and copied when you run `php artisan laradox:up`.

> **Note**: You don't need to manually edit nginx configuration files. Laradox handles this automatically.

**Tuning applied out of the box:**

| Setting | Why |
|---------|-----|
| `pcre_jit on` | Faster regex evaluation for location and rewrite matching |
| `http2 on` | HTTP/2 via the current directive, instead of the `listen ... http2` parameter deprecated in nginx 1.25.1 |
| `proxy_buffering` + 8×16k buffers | Frees the FrankenPHP worker as soon as the response is read, instead of holding it open for a slow client |
| `keepalive_requests 1000` on the upstream | Reuses upstream connections and spreads reconnects over time |
| `map $http_upgrade $connection_upgrade` | WebSockets and Vite HMR pass through, while normal requests keep the upstream connection pool alive |
| `server_tokens off` | The nginx version is not advertised in responses or error pages |
| `ssl_buffer_size 4k` | Lower time-to-first-byte on TLS connections |
| `NGINX_ENVSUBST_FILTER=LARADOX_` | Template substitution only touches `LARADOX_*`, so nginx's own runtime variables are left alone |

Gzip stays off on purpose: FrankenPHP/Caddy already compresses the response, and compressing
twice only burns CPU.

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
LARADOX_QUEUE_WORKERS=2

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

### PHP Image

The `php` service is built from `docker/php/php.dockerfile` — a multi-stage build where all
compilation happens in a throwaway `builder` stage, so the runtime image only carries the
shared libraries it actually needs.

**Bundled extensions:** `bcmath`, `excimer`, `gd` (with JPEG/WebP/FreeType), `intl`, `pcntl`,
`pdo_mysql`, `pdo_pgsql`, `redis`, `uv`, `zip`, plus everything in the FrankenPHP base image
(`opcache`, `mbstring`, `dom`, `curl`, `sqlite3`, …).

**OPcache** is tuned per environment: development validates timestamps so `--watch` reloads
pick up edits, while production disables timestamp validation and enables the tracing JIT.
Because production caches compiled code indefinitely, deploys need an image rebuild or
`php artisan octane:reload`.

**Build args** — override in the `build.args` block of your compose file:

| Arg | Default | Purpose |
|-----|---------|---------|
| `FRANKENPHP_VERSION` | `1.12` | FrankenPHP base image version |
| `PHP_VERSION` | `8.4` | PHP version of the base image |
| `ENVIRONMENT` | `development` | Selects the `development` or `production` stage |
| `USER_ID` / `GROUP_ID` | `1000` | Host uid/gid, so bind-mounted files stay writable |
| `SUPERCRONIC_VERSION` | `0.2.48` | Supercronic release to download |

The image is architecture-aware, so it builds on both `amd64` and `arm64` hosts
(Apple Silicon included).

**Image size** — the runtime image only carries what the selected environment actually runs:

- Compiled extensions are stripped of their symbol tables and their static archives dropped
  before they leave the builder stage.
- Supervisor and Supercronic are installed in the `production` stage only. Development runs the
  scheduler with `schedule:work` and the queue with `queue:work`, so neither is needed there.
- All compilation happens in the throwaway `builder` stage; the toolchain never reaches the
  runtime image.

Measured on `amd64` with FrankenPHP 1.12 / PHP 8.4, with an unchanged extension list:

| Stage | Before | After |
|-------|--------|-------|
| `development` | 281 MB | **212 MB** (−25%) |
| `production` | 281 MB | **278 MB** (−1%) |

Check your own build with:

```bash
docker images --format '{{.Repository}}:{{.Tag}} {{.Size}}' | grep php
```

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

Then restart the containers:

```bash
php artisan laradox:down
php artisan laradox:up --detach
```

### Containers Already Running

Laradox automatically detects if containers are already running and offers to restart them:

```bash
php artisan laradox:up
# Output: "⚠ Containers are already running!"
# Prompt: "Do you want to restart the containers?"
```

Or manually stop and start:

```bash
php artisan laradox:down
php artisan laradox:up --detach
```

## License

Laradox is open-sourced software licensed under the [MIT license](LICENSE).

## Testing

Laradox includes a comprehensive test suite covering all functionality. All tests must pass to ensure proper operation.

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage report
vendor/bin/phpunit --coverage-html build/coverage

# Run specific test suite
vendor/bin/phpunit tests/Feature/
vendor/bin/phpunit tests/Unit/

# Run specific test file
vendor/bin/phpunit tests/Feature/InstallCommandTest.php
vendor/bin/phpunit tests/Unit/UpCommandTest.php
```

## Credits

Created by [Aditya Rizqi Januarta](https://github.com/adityarizqi)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
