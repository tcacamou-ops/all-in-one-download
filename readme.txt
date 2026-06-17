=== All-in-one Download ===
Contributors: tcacamou
Tags: download, media, movies, tv-shows, automation
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: Proprietary
License URI: 

A professional WordPress plugin to manage and automate movie and TV show downloads.

== Description ==

All-in-one Download is a comprehensive WordPress plugin designed to centralize and automate the management of media downloads, specifically movies and TV shows. It provides an intuitive administration interface combined with a powerful automatic URL processing system.

= Key Features =

* **Multi-format Support** - Handles both movies and TV shows seamlessly
* **Automated URL Processing** - Add download links and let the system process them automatically
* **Smart Media Detection** - Automatically identifies whether a URL is for a movie or TV series
* **Rich Metadata Management** - Titles, cover images, audio formats, and more
* **Status Tracking** - Monitor download states (active, inactive, downloaded)
* **Centralized Dashboard** - Overview of all your media in one place
* **Advanced Search & Filtering** - Find media quickly by title or type
* **Automated Cron Jobs** - WordPress crons for automatic processing
* **REST API** - Full API for external integrations
* **Comprehensive Logging** - Track all operations with detailed logs

= Automation Features =

The plugin leverages WordPress's built-in cron system to automate media processing:

* **MediaCron** - Processes all pending URLs hourly
* **MovieCron** - Specifically handles movie processing
* **TvShowCron** - Manages TV show processing
* Automatic duplicate detection
* Intelligent retry mechanisms

= Developer Friendly =

* Modern PHP architecture with namespaces
* PSR-4 autoloading via Composer
* Repository pattern for data access
* Comprehensive REST API
* Extensive hooks and filters
* Full PHPUnit test coverage
* Follows WordPress coding standards

== Installation ==

= Automatic Installation =

1. Download the latest release from the [GitHub releases page](https://github.com/tcacamou-ops/all-in-one-download/releases)
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin" and select the downloaded ZIP file
4. Click "Install Now" and then "Activate"
5. Access the plugin via the "Downloads" menu in the admin panel

= Manual Installation =

1. Download the plugin ZIP file
2. Extract it to `/wp-content/plugins/all-in-one-download/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure the plugin settings as needed

= For Developers =

1. Clone the repository
2. Run `composer install --no-dev` in the plugin directory
3. Activate the plugin in WordPress

== Frequently Asked Questions ==

= What types of media does the plugin support? =

The plugin currently supports movies and TV shows with automatic detection and processing.

= How does the automatic processing work? =

Once you add a URL, the plugin's cron system automatically processes it, detects the media type, extracts metadata, and creates the appropriate entry in your database.

= Can I manually trigger processing? =

Yes, the dashboard includes a cron manager that allows you to manually trigger processing tasks.

= Is there an API available? =

Yes, the plugin includes a comprehensive REST API for managing media, movies, and TV shows programmatically.

= How are duplicates handled? =

The system automatically detects existing media by title and adds new URLs to existing entries rather than creating duplicates.

== Screenshots ==

1. Main dashboard with media overview
2. URL input form for adding new media
3. Media listing with search and filters
4. Cron manager interface
5. Detailed media view with metadata

== Changelog ==

= 1.0.0 =
* Added: Status page with submenu in the WordPress administration
* Added: Full logging system with levels (DEBUG, NOTICE, ERROR) and categories (movies, series)
* Added: REST API endpoint for log retrieval
* Added: LogsManager component (PHP + JS) for real-time log display
* Added: Improved CronsManager component with finer cron management
* Changed: Redesigned administration interface (toolbar, modals, media items)

= 0.0.6 =
* Fixed: Episode and season progression in TvShowCron (next_episode now called before next_saison)
* Fixed: Type comparison in TvShow::next_episode() (explicit cast to int)
* Fixed: Debug log now shows the download result instead of the submitted item

= 0.0.5 =
* Added: Settings page to configure movie and TV show download directories
* Changed: Default directories changed to /downloads/Movies and /downloads/TvShows

= 0.0.4 =
* Changed: Updated release workflow to exclude unnecessary files from the plugin ZIP package

= 0.0.3 =
* Changed: Fixed GitHub sync workflow (added Composer dependency installation step)

= 0.0.2 =
* Changed: Included vendor/ folder in the release package

= 0.0.1 =
* Initial release

== Technical Details ==

= System Requirements =
* WordPress 5.0 or higher
* PHP 7.4 or higher
* MySQL 5.6 or higher / MariaDB 10.0 or higher
* Composer (for development)

= Architecture =
* Namespace: AllI1D
* Autoloading: PSR-4 via Composer
* Database: WordPress custom tables
* API: WordPress REST API
* Cron: WordPress cron system

= Development =
* Coding Standards: WordPress Coding Standards (WPCS)
* Static Analysis: PHPStan
* Testing: PHPUnit with Brain Monkey
* Version Control: Git

== Support ==

For bug reports, feature requests, or general support, please visit:
https://github.com/tcacamou-ops/all-in-one-download/issues

== Privacy ==

This plugin does not collect or transmit any user data. All processing happens locally on your WordPress installation.
