# Changelog

All notable changes to `laradox` will be documented in this file.

## [Unreleased]

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
