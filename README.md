# All-in-one Download

A WordPress plugin to manage and automate movie and TV show downloads.

## Description

All-in-one Download centralizes and automates the management of media downloads (films et séries TV). It provides an admin dashboard, an automatic URL processing system, and a REST API for external integrations.

## Key Features

### Media Management
- **Movies & TV Shows**: full support for both types, including seasons and episodes
- **URL processing**: add a download link and let the cron handle the rest
- **Auto-detection**: identifies media type automatically
- **Metadata**: title, cover image, audio format, video quality preference (any / 720p / 1080p / 2160p, multi-select), status
- **Statuses**: active, inactive, downloaded

### Admin Dashboard
- **Responsive UI**: built with Tailwind CSS, works on any screen size
- **Search & filtering**: by title and media type
- **Edit modals**: inline editing of movies and TV shows (title, search title, status, seasons)
- **Media counters**: visual indicators per type

### Cron Automation
- **MediaCron** — processes all pending URLs (hourly)
- **MovieCron** — movie-specific processing
- **TvShowCron** — TV show processing, including next-episode detection
- Duplicate prevention: new URLs are merged into existing entries
- Manual trigger available from the dashboard

### Log Viewer
- Per-type log files: `medias.log`, `series.log`, `films.log`
- Levels: `DEBUG`, `NOTICE`, `WARNING`, `ERROR`
- Tail-based reading (last N lines) to avoid memory issues on large files
- Accessible via the dashboard log viewer modal

### REST API (`/wp-json/all-i1d/v1/`)

| Endpoint | Description |
|---|---|
| `GET /logs` | Fetch log content (`file`, `num_lines` params) |
| `GET /listing/refresh` | Refresh the media listing HTML |
| `GET /listing/movie` | Get a movie item HTML |
| `GET /listing/tvshow` | Get a TV show item HTML |
| `POST /media` | Add a new media URL |
| `GET /movie` | Movie operations |
| `GET /tvshow` | TV show operations |
| `POST /indexing/reset` | Reset all indexing state — empties the feed catalog and clears `general_search_done` for every movie and TV show |

All routes require the `alli1d` capability.

## Installation

### From a release

1. Download the latest ZIP from the [releases page](https://github.com/tcacamou-ops/all-in-one-download/releases)
2. WordPress Admin → Plugins → Add New → Upload Plugin
3. Activate and navigate to the **Downloads** menu

### From source (development)

```bash
git clone <repo> wp-content/plugins/all-in-one-download
cd wp-content/plugins/all-in-one-download
composer install
```

Activate the plugin in WordPress, then open the **Downloads** menu.

## Requirements

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+ / MariaDB 10.0+
- Composer (development only)

## Project Structure

```
all-in-one-download/
├── assets/
│   ├── css/
│   │   └── components/       # Per-component stylesheets (modale, toolbar, banners…)
│   └── js/
│       └── components/       # Per-component JS (listing, crons, logs, modale…)
├── includes/
│   ├── Actions/              # Logs service
│   ├── Api/                  # REST API classes (LogsApi, MediaApi, MovieApi…)
│   ├── Components/           # HTML components (MovieItem, TvShowItem, CronsManager…)
│   ├── Crons/                # WordPress cron handlers
│   ├── Interfaces/           # PHP interfaces (Api)
│   ├── Models/               # Entities and repositories
│   └── Pages/                # Admin page renderers (Dashboard, Settings, Status)
├── tests/                    # PHPUnit tests
└── vendor/                   # Composer dependencies
```

## Development

### Code quality

```bash
composer phpcs        # Check WordPress coding standards
composer phpcs:fix    # Auto-fix violations
composer phpstan      # Static analysis
```

### Tests

```bash
composer test
```

Uses PHPUnit with Brain Monkey for WordPress function mocking.

## Architecture

### Data flow

1. A URL is submitted via the admin form or the REST API
2. It is stored as a **Media** entry
3. The cron picks it up, detects the type, fetches metadata, and creates a **Movie** or **TvShow**
4. Logs trace every step under `wp-content/uploads/alli1d/logs/`

### Key patterns

- **Repository pattern** — `MovieRepository`, `TvShowRepository`, `MediaRepository`
- **PSR-4 autoloading** via Composer (`AllI1D\` namespace)
- **REST API interface** — all API classes implement `AllI1D\Interfaces\Api`
- **Component rendering** — each UI block is a self-contained PHP class with a `render()` method

## Security

- Capability check (`alli1d`) on all REST routes
- Input sanitization and output escaping throughout
- Nonce-based CSRF protection
- Direct file access blocked

## Changelog

### 0.0.7
- Feat: `LogsApi` REST endpoint — fetch last N lines of any log file
- Feat: responsive log viewer modal with syntax coloring per log level
- Feat: shared dark floating toolbar for Crons and Logs toggles
- Feat: responsive bottom banners (crons & logs) with frosted-glass style
- Feat: fully responsive edit modals for movies and TV shows

### 0.0.6
- Fix: next episode detection in TV show cron

### 0.0.5
- Feat: default folder management

### 0.0.4
- Official release

## License

Proprietary — all rights reserved.
