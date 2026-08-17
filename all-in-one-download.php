<?php
/**
 * Plugin Name: All-in-one Download
 * Plugin URI: https://github.com/tcacamou-ops/all-in-one-download
 * Description: A professional WordPress plugin to manage and automate movie and TV show downloads.
 * Version: 1.0.11
 * Author: tcacamou
 * Author URI: https://github.com/tcacamou-ops
 * Text Domain: all-in-one-download
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: Proprietary
 */

namespace AllI1D;

use AllI1D\Actions\Logs;
use AllI1D\Services\TorrentTitleMatcher;
use honemo\updater\Updater;

// Sécurité : empêcher l'accès direct au fichier.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Définir la constante du chemin absolu du plugin.
if ( ! defined( 'ALLI1D_DIR' ) ) {
	define( 'ALLI1D_DIR', plugin_dir_path( __FILE__ ) );
}

// Définir la constante de l'URL du plugin.
if ( ! defined( 'ALLI1D_URL' ) ) {
	define( 'ALLI1D_URL', plugin_dir_url( __FILE__ ) );
}

// Inclure l'autoloader de Composer.
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

// Inclure le point d'entrée générique du catalogue indexé de flux/API providers.
require_once plugin_dir_path( __FILE__ ) . 'includes/feed-catalog-functions.php';

// Hook d'activation et désactivation.
register_activation_hook( __FILE__, Install::class . '::activate' );
register_deactivation_hook( __FILE__, Install::class . '::deactivate' );

// Mettre à jour le schéma de la base de données si nécessaire (mise à jour du plugin sans réactivation).
add_action( 'plugins_loaded', Install::class . '::maybe_upgrade' );

// Hook scheduled events.
add_action( 'alli1d_process_medias', [ 'AllI1D\Crons\MediaCron', 'process_medias' ] );
add_action( 'alli1d_process_tv_shows', [ 'AllI1D\Crons\TvShowCron', 'process_tv_shows' ] );
add_action( 'alli1d_process_movies', [ 'AllI1D\Crons\MovieCron', 'process_movies' ] );
add_action( 'alli1d_rotate_logs', [ 'AllI1D\Crons\LogRotationCron', 'rotate_logs' ] );
add_action( 'alli1d_purge_feed_catalog', [ 'AllI1D\Crons\FeedCatalogPurgeCron', 'purge_feed_catalog' ] );
// Pas de handler du core sur `alli1d_refresh_feed_catalog` : c'est une action
// broadcast (comme `alli1d_process_movie`/`alli1d_search_providers`), câblée
// à un cron via `FeedCatalogRefreshCron::schedule_cron()`. Chaque add-on
// provider actif s'enregistre directement dessus.

// Statut du catalogue indexé de flux/API providers, affiché sur la page Statut.
add_filter( 'alli1d_process_status', [ 'AllI1D\Filters\FeedCatalogStatus', 'process_status' ] );
add_filter( 'alli1d_provider_settings_modals', [ 'AllI1D\Filters\FeedCatalogStatus', 'register_modal' ] );

// Commande WP-CLI de gestion du catalogue indexé de flux/API providers.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	\WP_CLI::add_command( 'alli1d feed-catalog', \AllI1D\Cli\FeedCatalogCommand::class );
}


class Plugin {
	/**
	 * Constructor.
	 */
	public function __construct() {
		// Initialiser l'administration.
		$this->initialize_admin();
		$this->initialize_api();
		$this->initialize_logs();
		$this->initialize_services();
	}

	/**
	 * Initialize admin.
	 */
	private function initialize_admin(): void {
		if ( is_admin() ) {
			new Admin();
			$updater = new Updater(
				__FILE__,                                      // Main plugin file.
				'https://github.com/tcacamou-ops/all-in-one-download'  // Repository URL.
			);

			$updater->init();
		}
	}

	/**
	 * Initialize API.
	 */
	private function initialize_api(): void {
		Api::get_instance();
	}

	/**
	 * Initialize logs.
	 */
	private function initialize_logs(): void {
		Logs::get_instance();
	}

	/**
	 * Initialize core services shared with add-ons via WordPress filters.
	 */
	private function initialize_services(): void {
		TorrentTitleMatcher::get_instance();
	}
}

// Initialiser le plugin.
new Plugin();
