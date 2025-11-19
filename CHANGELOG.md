# Changelog

All notable changes to `laradox` will be documented in this file.

## [Unreleased]

### Changed
- **BREAKING**: Renamed `QUEUE_WORKER_CPUS` environment variable to `LARADOX_QUEUE_WORKERS` for consistency with LARADOX_* naming convention
- Updated all references in configuration files, Docker Compose files, and documentation

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

## 1.0.0 - 2024-08-12

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
