# All-in-one Download

A professional WordPress plugin to manage and automate movie and TV show downloads.

## Description

All-in-one Download is a WordPress plugin that centralizes and automates the management of media downloads (movies and TV shows). It provides an intuitive administration interface and an automatic URL processing system.

## Key Features

### Media Management
- **Multi-format support**: Movies and TV Shows
- **URL management**: Add and automatically process download links
- **Automatic detection**: Identifies media type (movie or series)
- **Metadata**: Manage titles, cover images, audio formats
- **Statuses**: Track download states (active, inactive, downloaded)

### Administration Interface
- **Centralized dashboard**: Overview of all media
- **Search and filtering**: Search by title and filter by type
- **Cron management**: Control automatic tasks
- **Visual indicators**: Media counters and statistics
- **Intuitive forms**: Quick URL addition

### Automation
- **WordPress Crons**: Automatic media processing
  - `MediaCron`: Processes all pending URLs (hourly)
  - `MovieCron`: Specifically processes movies
  - `TvShowCron`: Processes TV shows
- **Duplicate prevention**: Automatic detection of existing media
- **Detailed logs**: Complete operation tracking

### REST API
- **ListingApi**: Retrieve media lists
- **MediaApi**: Manage media URLs
- **MovieApi**: Movie operations
- **TvShowApi**: TV show operations

## Installation

### Recommended Method (Using Releases)

1. Download the latest release from [https://github.com/tcacamou-ops/all-in-one-download/releases](https://github.com/tcacamou-ops/all-in-one-download/releases)
2. Extract the archive to your `wp-content/plugins/` folder
3. Activate the plugin in WordPress administration
4. Access the "Downloads" menu in the administration

### Alternative Method (Development)

If you need the development version:

1. Clone the repository into the `wp-content/plugins/` folder
2. Install Composer dependencies:
   ```bash
   composer install --no-dev
   ```
3. Activate the plugin in WordPress administration
4. Access the "Downloads" menu in the administration

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Composer (for dependencies)

## Project Structure

```
all-in-one-download/
├── assets/
│   ├── css/          # Admin interface styles
│   └── js/           # JavaScript scripts and components
├── includes/
│   ├── Actions/      # Actions and logs
│   ├── Api/          # REST API endpoints
│   ├── Components/   # Interface components
│   ├── Crons/        # Automatic tasks
│   ├── Models/       # Data models and repositories
│   └── Pages/        # Administration pages
├── tests/            # PHPUnit unit tests
└── vendor/           # Composer dependencies
```

## Usage

### Adding Media

1. Access the "Downloads" dashboard
2. Enter the media URL in the form
3. The system automatically detects the type (movie or series)
4. The cron processes the URL and creates the corresponding entry

### Managing Automatic Tasks

The plugin uses the WordPress cron system to process media automatically. You can:
- View cron status in the dashboard
- Manually trigger processing
- Check logs to track activity

### Search and Filter

- Use the search bar to find media by title
- Filter by type (Movies or TV Shows)
- View details and URLs for each media

## Development

### Code Standards

The project follows WordPress coding standards:

```bash
# Check code
composer phpcs

# Auto-fix
composer phpcs:fix

# Static analysis
composer phpstan
```

### Unit Tests

```bash
composer test
```

Tests use PHPUnit with Brain Monkey to mock WordPress functions.

## Architecture

### Data Models

- **Media**: Represents a media URL pending processing
- **Movie**: Represents a movie with its metadata
- **TvShow**: Represents a TV show with episodes and seasons

### Repositories

- **MediaRepository**: Media URL management
- **MovieRepository**: CRUD for movies
- **TvShowRepository**: CRUD for TV shows

### Logging System

The plugin has a complete logging system to trace all operations:
- Media logs
- Movie logs
- TV show logs
- Levels: DEBUG, NOTICE, WARNING, ERROR

## Security

- User capability verification
- Input sanitization and validation
- CSRF protection with nonces
- Output escaping
- Direct file access prevention

## License

This plugin is distributed under a proprietary license.

## Support

For any questions or issues, please create an issue on the project repository.