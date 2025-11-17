# Changelog

All notable changes to `laradox` will be documented in this file.

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
