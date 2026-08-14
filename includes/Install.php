<?php
/**
 * Plugin installation handler.
 *
 * @package AllI1D
 */

namespace AllI1D;

use AllI1D\Models\Repositories\MediaRepository;
use AllI1D\Models\Repositories\TvShowRepository;
use AllI1D\Models\Repositories\MovieRepository;
use AllI1D\Models\Repositories\FeedCatalogRepository;
use AllI1D\Crons\MediaCron;
use AllI1D\Crons\TvShowCron;
use AllI1D\Crons\MovieCron;
use AllI1D\Crons\LogRotationCron;
use AllI1D\Crons\FeedCatalogRefreshCron;
use AllI1D\Crons\FeedCatalogPurgeCron;

class Install {

	/**
	 * Current database schema version. Bump this and re-run create_table()
	 * via maybe_upgrade() whenever a table definition changes.
	 */
	private const DB_VERSION = '1.1.2';

	/**
	 * Run plugin activation tasks.
	 */
	public static function activate(): void {
		self::create_table();
		self::add_roles();
		update_option( 'alli1d_db_version', self::DB_VERSION );
		MediaCron::schedule_cron();
		TvShowCron::schedule_cron();
		MovieCron::schedule_cron();
		LogRotationCron::schedule_cron();
		FeedCatalogRefreshCron::schedule_cron();
		FeedCatalogPurgeCron::schedule_cron();
	}

	/**
	 * Re-run the (idempotent) table creation when the plugin has been
	 * updated to a version with a newer schema, without requiring
	 * deactivation/reactivation.
	 */
	public static function maybe_upgrade(): void {
		if ( get_option( 'alli1d_db_version' ) === self::DB_VERSION ) {
			return;
		}
		self::create_table();
		update_option( 'alli1d_db_version', self::DB_VERSION );
	}

	/**
	 * Run plugin deactivation tasks.
	 */
	public static function deactivate(): void {
		$movie_repository = MovieRepository::get_instance();
		// $movie_repository->drop_table();

		$repository = MediaRepository::get_instance();
		// $repository->drop_table();

		$tv_show_repository = TvShowRepository::get_instance();
		// $tv_show_repository->drop_table();

		$feed_catalog_repository = FeedCatalogRepository::get_instance();
		// $feed_catalog_repository->drop_table();
		MediaCron::unschedule_cron();
		TvShowCron::unschedule_cron();
		MovieCron::unschedule_cron();
		LogRotationCron::unschedule_cron();
		FeedCatalogRefreshCron::unschedule_cron();
		FeedCatalogPurgeCron::unschedule_cron();
	}

	/**
	 * Create all plugin database tables.
	 */
	private static function create_table(): void {
		$movie_repository = MovieRepository::get_instance();
		$movie_repository->create_table();

		$repository = MediaRepository::get_instance();
		$repository->create_table();

		$tv_show_repository = TvShowRepository::get_instance();
		$tv_show_repository->create_table();

		$feed_catalog_repository = FeedCatalogRepository::get_instance();
		$feed_catalog_repository->create_table();
	}

	/**
	 * Add plugin roles and capabilities.
	 */
	private static function add_roles(): void {
		// Add plugin capabilities to administrator.
		$role = get_role( 'administrator' );
		if ( is_object( $role ) && method_exists( $role, 'add_cap' ) ) {
			$role->add_cap( 'alli1d' );
			$role->add_cap( 'alli1d_admin' );
		}
		// Add the "all-in-one-download-user" role with specific capabilities.
		add_role(
			'all-in-one-download-user',
			__( 'All-in-one Download User', 'all-in-one-download' ),
			[
				'alli1d' => true,
				'read'   => true,
			]
		);
		add_role(
			'all-in-one-download-admin',
			__( 'All-in-one Download Admin', 'all-in-one-download' ),
			[
				'alli1d'       => true,
				'alli1d_admin' => true,
				'read'         => true,
			]
		);
	}
}
