# Laradox Quick Start Guide

Get your Laravel application running with Docker in less than 5 minutes!

## Prerequisites

- PHP 8.2+ and Composer installed locally
- Docker and Docker Compose (Laradox will detect and help install if missing)
- [mkcert](https://github.com/FiloSottile/mkcert) (Laradox will detect and help install if missing)

> **Note**: Laradox automatically detects missing prerequisites and guides you through installation on Linux and macOS.

## Quick Start (5 Steps)

### 1. Install Laradox

```bash
composer require adityarizqi/laradox --dev
```

### 2. Install Laravel Octane

```bash
composer require laravel/octane
```

### 3. Install Laradox Files

```bash
php artisan laradox:install
```

### 4. Setup SSL Certificates

```bash
php artisan laradox:setup-ssl
```

Laradox will automatically detect if mkcert is installed and prompt to install it if missing:
- **Linux (Ubuntu/Debian)**: Installs via `apt-get install mkcert`
- **Linux (Fedora)**: Installs via `dnf install mkcert`
- **Linux (CentOS)**: Installs via `yum install mkcert`
- **macOS**: Installs via `brew install mkcert`
- **Windows**: Provides download link (manual installation required)

> **Development**: SSL is optional. If you skip mkcert installation, Laradox will work with HTTP on port 80.
> 
> **Production**: SSL is **REQUIRED**. Laradox will guide you through mkcert installation or you can provide valid SSL certificates manually.

### 5. Start Docker Containers

```bash
php artisan laradox:up --detach
```

Laradox will automatically check for Docker and Docker Compose:
- Detects if Docker is installed and running
- Prompts to install Docker if missing (Linux and macOS)
- Provides installation instructions for your OS
- Guides you through the installation process

Supported automatic installations:
- **Ubuntu/Debian**: Installs Docker via official Docker repository
- **Fedora**: Installs via `dnf install docker docker-compose`
- **CentOS**: Installs via `yum install docker docker-compose`
- **macOS**: Guides to install Docker Desktop
- **Windows**: Guides to install Docker Desktop

## Post-Installation Setup

### Install Dependencies

```bash
./composer install
./npm install
./npm run dev
```

### Setup Laravel

```bash
./php artisan key:generate
./php artisan migrate:fresh --seed
```

## Access Your Application

Open your browser:
- **With SSL**: https://laravel.docker.localhost
- **Without SSL**: http://laravel.docker.localhost

## Common Commands

```bash
# Start containers (auto-detects SSL)
php artisan laradox:up --detach

# Start with HTTPS (requires SSL certificates)
php artisan laradox:up --force-ssl=true --detach

# Start with HTTP only (no SSL)
php artisan laradox:up --force-ssl=false --detach

# Stop containers
php artisan laradox:down

# View logs
docker compose -f docker-compose.development.yml logs -f

# Run artisan commands
./php artisan migrate
./php artisan tinker

# Install packages
./composer require vendor/package

# Build assets
./npm run build
```

## SSL Configuration Options

Laradox provides flexible SSL configuration:

### Auto-Detect (Default)
```bash
php artisan laradox:up
```
- Development: Prompts if SSL not found, allows HTTP-only
- Production: Requires SSL certificates, fails if missing

### Force HTTPS
```bash
php artisan laradox:up --force-ssl=true
```
- Always uses HTTPS configuration
- Requires valid SSL certificates
- Fails if certificates not found

### Force HTTP Only
```bash
php artisan laradox:up --force-ssl=false
```
- Always uses HTTP-only configuration
- Ignores SSL certificates even if present
- Can bypass production SSL requirement (not recommended)

## Switching to Production

1. Setup SSL certificates (required):
   ```bash
   php artisan laradox:setup-ssl
   ```

2. Update `.env`:
   ```env
   LARADOX_ENV=production
   ```

3. Start production containers:
   ```bash
   php artisan laradox:up --environment=production --build --detach
   ```

> **Important**: Production requires SSL certificates. Use `--force-ssl=false` to bypass (not recommended).

## Troubleshooting

### Ports already in use?

Change ports in `.env`:
```env
LARADOX_HTTP_PORT=8080
LARADOX_HTTPS_PORT=8443
```

### Permission issues?

Update user IDs in `.env`:
```env
LARADOX_USER_ID=1000
LARADOX_GROUP_ID=1000
```

Rebuild:
```bash
php artisan laradox:down --volumes
php artisan laradox:up --build --detach
```

### SSL certificate errors?

Reinstall mkcert:
```bash
mkcert -uninstall
php artisan laradox:setup-ssl
```

### Containers already running?

Laradox detects running containers and offers to restart them:
```bash
php artisan laradox:up
# Will prompt: "Do you want to restart the containers?"
```

Or manually stop and start:
```bash
php artisan laradox:down
php artisan laradox:up --detach
```

## Custom Domain

1. Update `.env`:
   ```env
   LARADOX_DOMAIN=myapp.test
   ```

2. Generate SSL:
   ```bash
   php artisan laradox:setup-ssl --domain=myapp.test
   ```

3. Add to `/etc/hosts`:
   ```
   127.0.0.1 myapp.test
   ```

## Need Help?

- Check the full documentation in `README.md`
- Review configuration options in `config/laradox.php`
- Open an issue on GitHub

## What's Running?

After `laradox:up`, you have:
- ✅ Nginx on ports 80 (HTTP) and 443 (HTTPS)
  - Auto-selects HTTP-only or HTTPS configuration
  - HTTP→HTTPS redirect when SSL enabled
- ✅ FrankenPHP with Laravel Octane on port 8080
- ✅ Node.js for asset compilation
- ✅ Laravel Scheduler (dev) or Supercronic (prod)
- ✅ Queue Workers with Supervisor (prod only)

---

**That's it!** You now have a fully functional Docker environment for your Laravel application. 🎉
