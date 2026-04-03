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
use AllI1D\Crons\MediaCron;
use AllI1D\Crons\TvShowCron;
use AllI1D\Crons\MovieCron;

class Install {

	/**
	 * Run plugin activation tasks.
	 */
	public static function activate(): void {
		self::create_table();
		self::add_roles();
		MediaCron::schedule_cron();
		TvShowCron::schedule_cron();
		MovieCron::schedule_cron();
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
		MediaCron::unschedule_cron();
		TvShowCron::unschedule_cron();
		MovieCron::unschedule_cron();
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
	}
}
