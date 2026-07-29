# Changelog

All notable changes to `laradox` will be documented in this file.

## [Unreleased]

### Added
- `laradox:status` — service health checks and monitoring
  - Reports state, healthcheck result, uptime and published ports for every service in the
    compose file, including services that have no container yet
  - `--stats` adds per-container CPU, memory and network usage
  - `--watch` (with `--interval`) keeps the report on screen
  - `--json` for machine-readable output, and a non-zero exit code when anything is not
    running or not healthy, so CI and deploy scripts can gate on it
- `laradox:optimize` — production optimization tooling
  - Dumps a class-map-authoritative Composer autoloader, caches config, routes, views and
    events, then reloads the Octane workers so the new bootstrap cache takes effect
  - `--clear` reverses the whole thing, `--skip-autoloader` / `--skip-reload` drop single steps
  - `--no-dev` is only passed to Composer for the production environment, and optimizing a
    development environment asks for confirmation first
- `laradox:deploy` — production deployment automation
  - Pull, optional maintenance mode, build, start, health gate, Composer install, asset build,
    migrations, cache warm-up, and back out of maintenance mode
  - `--dry-run` prints the plan without executing it; `--force` is required to deploy
    non-interactively
  - Every step can be skipped (`--no-pull`, `--no-build`, `--no-composer`, `--no-assets`,
    `--no-migrate`, `--no-optimize`); a failure takes the application back out of maintenance
    mode and reports where to look
- `laradox:benchmark` — performance benchmarking
  - Concurrent HTTP load generated from the host with cURL, no external tooling required
  - Reports throughput, success rate, transfer, status-code distribution and
    min/avg/p50/p90/p95/p99/max latency, with warm-up requests excluded from the results
  - `--json` output for tracking numbers between releases
- `InspectsContainers` concern shared by the new commands: container inspection, resource
  sampling, health polling and in-container command execution

## 2.0.8 - 2026-07-28

### Added
- Laravel 13.x support (`illuminate/support` and `illuminate/console` now allow `^13.0`)
  - Orchestra Testbench `^11.0` and PHPUnit `^12.0` accepted in `require-dev`
  - CI matrix covers Laravel 13.x on PHP 8.3 and 8.4 (Laravel 13 requires PHP >= 8.3)
- Laravel Pint for code style, with `composer lint` / `composer format` scripts and a CI job
- `pdo_mysql` extension in the PHP image, alongside the existing `pdo_pgsql`
- `gd` is now built with JPEG, WebP, and FreeType support
- Environment-specific OPcache tuning: timestamp validation in development, tracing JIT in production
- `FRANKENPHP_VERSION`, `PHP_VERSION`, and `SUPERCRONIC_VERSION` build args, so base
  versions can be overridden without editing the Dockerfile

### Fixed
- Production containers no longer start Octane with `--watch`. The environment check lived in
  `CMD`, which is evaluated by the container shell at runtime and could not see the
  `ENVIRONMENT` build arg, so every container took the development branch. The command is now
  defined per build stage.
- Octane now binds to `0.0.0.0` instead of Octane's `127.0.0.1` default, so Nginx can reach the
  `php` service.
- `LARADOX_FRANKENPHP_PORT` no longer carries a leading colon, which produced an invalid
  `--port=:8080`. The Nginx upstream is templated from the same variable, so changing the port
  now takes effect across the whole stack.
- The PHP image builds on `arm64` hosts (Apple Silicon). Supercronic was pinned to the
  `amd64` binary regardless of target architecture.
- `LARADOX_USER_ID` / `LARADOX_GROUP_ID` are passed through to the image build; previously the
  `USER_ID`/`GROUP_ID` build args always fell back to `1000`.
- `COMPOSER_HOME` and `COMPOSER_CACHE_DIR` now match the paths the development compose file
  mounts, so the host Composer cache and `auth.json` are actually used.

### Changed
- Base image updated from FrankenPHP 1.7 to 1.12
- Builder stage consolidated into fewer layers, and build-only packages whose extensions
  already ship with the base image (`mbstring`, `libxml2`, `oniguruma`, `curl-dev`) removed
- Supercronic updated from 0.2.29 to 0.2.48
- Coverage reporting moved out of `phpunit.xml` and onto the CI command, so local test runs no
  longer require a coverage driver

## 2.0.7 - 2026-01-24

### New Features
  1. Added --file option across all CLI commands to specify custom Docker Compose file paths.
  2. Improved Docker Compose version detection with support for both V2 and V1 with automatic fallback. 

### Bug Fixes
  1. Corrected environment variable naming in Docker Compose configurations.
  2. Enhanced error messaging with improved visual formatting and file path clarity.

## What's Changed
* docs: update changelog for release v2.0.6 by @github-actions[bot] in https://github.com/adityarizqi/laradox/pull/20
* Update README.md by @adityarizqi in https://github.com/adityarizqi/laradox/pull/21
* feat: enhance Docker Compose support and error handling by @adityarizqi in https://github.com/adityarizqi/laradox/pull/22


**Full Changelog**: https://github.com/adityarizqi/laradox/compare/v2.0.6...v2.0.7


## 2.0.6 - 2025-12-12

### Added
- New `laradox:shell` command for interactive container access
  - Enter any container interactively (php, nginx, node, scheduler, queue)
  - Support for multiple shells (sh, bash, zsh) with automatic detection and fallback
  - `--shell` option to specify preferred shell (defaults to sh)
  - `--user` option to run shell as specific user
  - `--environment` option for production/development environments
  - Automatic service validation (checks if service exists and is running)
  - Intelligent shell detection with graceful fallback to available shells
  - Comprehensive test suite (10 feature tests, 15 unit tests)
- Hosts file confirmation prompt for custom domains in `laradox:up` command
  - Automatically skips prompt for `.localhost` domains (work without hosts file modification)
  - For custom domains, displays instructions and confirmation prompt
  - Platform-specific guidance for macOS, Linux, and Windows
  - Prevents common deployment issues from forgotten hosts file entries
- Nginx configuration validation in `laradox:up` command
  - Verifies `app.conf` file exists after copying configuration
  - Prevents containers from starting with missing/invalid nginx config
  - Shows clear error messages with expected file path


## 2.0.5 - 2025-11-23

### Added
- New `laradox:logs` command for viewing container logs with filtering options
  - Support for viewing specific service logs (nginx, php, node, scheduler, queue)
  - `--follow` option to follow logs in real-time
  - `--tail` option to limit number of lines displayed
  - `--timestamps` option to show log timestamps
  - Automatic container running detection
- Comprehensive test suite for LogsCommand (19 feature tests, 11 unit tests)

### Fixed
- Helper scripts (`./php`, `./composer`, `./npm`) now properly default to development environment when `LARADOX_ENV` is not set in `.env`
- Removed incorrect user flags (`-u composer`, `-u node`) that caused "no matching entries in passwd file" errors
- Helper scripts now use correct container users (php container runs as `appuser` by default, node service already configured with `user: node`)

### Changed
- **BREAKING**: Renamed `QUEUE_WORKER_CPUS` environment variable to `LARADOX_QUEUE_WORKERS` for consistency with LARADOX_* naming convention
- FrankenPHP port configuration is now dynamic via `LARADOX_FRANKENPHP_PORT` environment variable
- Removed hardcoded `--host` and `--port` from Dockerfile CMD, now uses environment variables
- Production environment no longer uses `--watch` flag for better performance
- Updated all references in configuration files, Docker Compose files, and documentation

### Improved
- FrankenPHP configuration now fully customizable via `.env` file
- Enhanced error messages with troubleshooting tips for common issues (port conflicts, permissions)
- Updated documentation for `laradox:logs` command in README.md, QUICKSTART.md, and PACKAGE_STRUCTURE.md


## 2.0.3 - 2025-11-19

### Added
- Automatic Docker detection with installation prompts for missing Docker/Docker Compose
- Automatic mkcert detection with installation prompts for missing mkcert
- Support for automatic installation on Ubuntu, Debian, Fedora, CentOS, and macOS
- Architecture-safe package manager installations (apt-get, dnf, yum, brew)
- New `ChecksDocker` trait for Docker prerequisite validation
- Comprehensive unit tests for all commands (InstallCommand, UpCommand, DownCommand)
- OS detection system supporting Linux distributions, macOS, and Windows
- Installation guidance for Windows users (manual installation)

### Improved
- Enhanced user experience with automatic prerequisite detection
- Better error messages with actionable installation instructions
- Docker installation now uses modern GPG key handling for Ubuntu
- Commands now validate prerequisites before execution
- Test suite expanded with feature and unit tests covering all scenarios

### Fixed
- Removed architecture-specific binary downloads (security improvement)
- Container restart detection and prompting in `laradox:up` command
- SSL requirement enforcement for production environments

## 2.0.2 - 2025-11-18

### Added
- `--force-ssl` flag for `laradox:up` command with three modes:
  - Auto-detect (default): Checks for SSL certificates and prompts user
  - `--force-ssl=true`: Forces HTTPS, requires valid certificates
  - `--force-ssl=false`: Forces HTTP-only, bypasses SSL requirement
- SSL certificate validation before container startup
- Production environment now requires SSL certificates by default
- Option to bypass SSL requirement in production (not recommended)

### Changed
- Nginx configuration selection now based on SSL availability and `--force-ssl` flag
- Development environment prompts user when SSL certificates are missing
- Production environment fails by default when SSL certificates are missing

### Improved
- Better SSL configuration workflow and user guidance
- Enhanced flexibility for development vs production SSL requirements
- Clearer error messages for SSL-related issues

## 2.0.0 - 2025-11-17

### Changed
- **BREAKING**: Converted entire project to Composer package library
- Repository structure reorganized for library distribution
- Docker files moved to `stubs/` directory for publishing
- Installation now via Composer instead of manual file copying

### Added
- Laravel Service Provider (`LaradoxServiceProvider`)
- Artisan command: `laradox:install` for one-command installation
- Artisan command: `laradox:setup-ssl` for SSL certificate generation
- Artisan command: `laradox:up` for starting containers
- Artisan command: `laradox:down` for stopping containers
- Configuration file: `config/laradox.php` with extensive customization options
- Package auto-discovery support for Laravel
- Multiple publishing tags for granular control
- Comprehensive package documentation (QUICKSTART.md, PACKAGE_STRUCTURE.md, STATISTICS.md)
- CONTRIBUTING.md with contribution guidelines

### Improved
- Documentation rewritten for library usage
- Installation process simplified to 5 commands
- Better version control with semantic versioning
- Enhanced maintainability across multiple projects
- Standardized best practices for Laravel + Docker

## 1.0.0 - 2025-08-12

### Added
- Initial release
- Laravel Octane with FrankenPHP support
- Nginx reverse proxy with optimized configuration
- Docker Compose configurations for development and production
- SSL certificate generation with mkcert
- Helper scripts for composer, npm, and php commands
- Queue workers with Supervisor
- Scheduler with Supercronic
- Configurable domains, ports, and environment settings
- Multi-stage Dockerfile for PHP 8.4 with FrankenPHP
- Development and production environment separation
- Comprehensive README with setup instructions
