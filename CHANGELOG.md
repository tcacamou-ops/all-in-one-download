# Changelog

All notable changes to All-in-one Download will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-03-30

### Added
- Initial release of All-in-one Download plugin
- Movie management system with full CRUD operations
- TV show management with episode and season tracking
- Automatic URL processing and media detection
- WordPress cron-based automation (MediaCron, MovieCron, TvShowCron)
- Centralized administration dashboard
- Search and filtering capabilities
- REST API endpoints for all media types
- Comprehensive logging system with multiple log levels
- Status tracking (active, inactive, downloaded)
- Metadata management (titles, cover images, audio formats)
- Duplicate prevention system
- User capability management
- Security features (nonces, sanitization, validation)
- PHPUnit test suite with Brain Monkey
- WordPress Coding Standards compliance
- PHPStan static analysis configuration

### Features
- **Media Management**: Add URLs and automatically process them into movies or TV shows
- **Automation**: Hourly cron jobs to handle pending URLs
- **Dashboard**: Clean, intuitive interface for managing all media
- **API**: Full REST API for external integrations
- **Logging**: Detailed logs for debugging and monitoring
- **Security**: Built-in security measures following WordPress best practices

### Technical
- PHP 7.4+ compatibility
- WordPress 5.0+ compatibility
- PSR-4 autoloading via Composer
- Repository pattern for data access
- Modern PHP architecture with namespaces
- Extensive hooks and filters for extensibility

[1.0.0]: https://github.com/tcacamou-ops/all-in-one-download/releases/tag/1.0.0
