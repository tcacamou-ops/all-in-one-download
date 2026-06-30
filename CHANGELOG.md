# Changelog

All notable changes to All-in-one Download will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2026-06-30

### Added
- Log rotation with 48h retention and daily archiving at midnight

### Changed
- Add-on settings pages replaced by modals on the Status page
- Better permissions and rights management
- Improved JS banner management

### Fixed
- JS fixes
- Security messages display

### Security
- Cap `num_lines` to `MAX_LOG_LINES` in log reader to prevent excessive reads
- Fix path traversal vulnerability in download directory sanitization
- Encrypt credentials at rest with AES-256-CBC
- Whitelist column names in repository filter methods to prevent SQL injection

## [1.0.0] - 2026-06-17

### Added
- Status page with submenu in the WordPress administration
- Full logging system with levels (DEBUG, NOTICE, ERROR) and categories (movies, series)
- REST API endpoint for log retrieval (`LogsApi`)
- `LogsManager` component (PHP + JS) for real-time log display
- Improved `CronsManager` component with finer cron management

### Changed
- Redesigned administration interface (toolbar, modals, media items)
- Improved modal styling (`modale.css`)
- Improved `MovieItem` and `TvShowItem` component styles

## [0.0.6] - 2026-04-15

### Fixed
- Fixed episode and season progression in `TvShowCron`: `next_episode` is now called before `next_saison` and returns the updated object
- Fixed type comparison in `TvShow::next_episode()` (explicit cast to `int`)
- Debug log now shows the download result instead of the submitted item

## [0.0.5] - 2026-04-09

### Added
- Settings page to configure movie and TV show download directories
- `DEFAULT_DIRECTORY` constants in `Movie` and `TvShow` models

### Changed
- Default directories changed to `/downloads/Movies` and `/downloads/TvShows` (removed machine-specific paths)

## [0.0.4] - 2026-04-03

### Changed
- Updated release workflow to exclude unnecessary files from the plugin ZIP package

## [0.0.3] - 2026-04-03

### Changed
- Fixed GitHub sync workflow (added Composer dependency installation step)

## [0.0.2] - 2026-04-03

### Changed
- Included `vendor/` folder in the release package (removed `.gitignore` and `.distignore`)

## [0.0.1] - 2026-04-02

### Added
- Initial release of All-in-one Download
- Movie management with full CRUD operations
- TV show management with episode and season tracking
- Automatic URL processing and media type detection
- WordPress cron-based automation (MediaCron, MovieCron, TvShowCron)
- Centralized administration dashboard
- Media search and filtering
- REST API endpoints for all media types
- Status tracking (active, inactive, downloaded)
- Metadata management (titles, cover images, audio formats)
- Duplicate prevention system
- User capability management
- WordPress security (nonces, sanitization, validation)
- PHPUnit test suite with Brain Monkey
- WordPress Coding Standards compliance
- PHPStan static analysis configuration
- Modern PHP architecture with namespaces and PSR-4 via Composer
- Repository pattern for data access
- GitHub Updater integration for automatic plugin updates

[1.0.1]: https://github.com/tcacamou-ops/all-in-one-download/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/tcacamou-ops/all-in-one-download/compare/0.0.6...1.0.0
[0.0.6]: https://github.com/tcacamou-ops/all-in-one-download/compare/0.0.5...0.0.6
[0.0.5]: https://github.com/tcacamou-ops/all-in-one-download/compare/0.0.4...0.0.5
[0.0.4]: https://github.com/tcacamou-ops/all-in-one-download/compare/0.0.3...0.0.4
[0.0.3]: https://github.com/tcacamou-ops/all-in-one-download/compare/0.0.2...0.0.3
[0.0.2]: https://github.com/tcacamou-ops/all-in-one-download/compare/0.0.1...0.0.2
[0.0.1]: https://github.com/tcacamou-ops/all-in-one-download/releases/tag/0.0.1
