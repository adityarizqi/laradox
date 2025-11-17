# Laradox Quick Start Guide

Get your Laravel application running with Docker in less than 5 minutes!

## Prerequisites

- Docker and Docker Compose installed
- PHP 8.2+ and Composer installed locally
- [mkcert](https://github.com/FiloSottile/mkcert/releases) downloaded

## Quick Start (5 Steps)

### 1. Install Laradox

```bash
composer require laradox/laradox --dev
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

> **Note**: Make sure mkcert is installed first! Download from https://github.com/FiloSottile/mkcert/releases

### 5. Start Docker Containers

```bash
php artisan laradox:up --detach
```

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

Open your browser: **https://laravel.docker.localhost**

## Common Commands

```bash
# Start containers
php artisan laradox:up --detach

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

## Switching to Production

1. Update `.env`:
   ```env
   LARADOX_ENV=production
   ```

2. Start production containers:
   ```bash
   php artisan laradox:up --env=production --build --detach
   ```

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

## Custom Domain

1. Update `.env`:
   ```env
   LARADOX_DOMAIN=myapp.test
   ```

2. Generate SSL:
   ```bash
   php artisan laradox:setup-ssl --domain=myapp.test
   ```

3. Edit `docker/nginx/conf.d/app.conf` and replace `laravel.docker.localhost` with `myapp.test`

4. Add to `/etc/hosts`:
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
- ✅ FrankenPHP with Laravel Octane on port 8080
- ✅ Node.js for asset compilation
- ✅ Laravel Scheduler (dev) or Supercronic (prod)
- ✅ Queue Workers with Supervisor (prod only)

---

**That's it!** You now have a fully functional Docker environment for your Laravel application. 🎉
