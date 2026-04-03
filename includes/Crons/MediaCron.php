<?php
/**
 * MediaCron class file.
 */

namespace AllI1D\Crons;

use AllI1D\Models\Movie;
use AllI1D\Models\TvShow;
use AllI1D\Models\Repositories\MovieRepository;
use AllI1D\Models\Repositories\TvShowRepository;
use AllI1D\Models\Repositories\MediaRepository;
use AllI1D\Actions\Logs;

class MediaCron {
	/**
	 * Schedule the cron.
	 */
	public static function schedule_cron(): void {
		if ( ! wp_next_scheduled( 'alli1d_process_medias' ) ) {
			wp_schedule_event( time(), 'hourly', 'alli1d_process_medias' );
		}
	}

	/**
	 * Unschedule the cron.
	 */
	public static function unschedule_cron(): void {
		$timestamp = wp_next_scheduled( 'alli1d_process_medias' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'alli1d_process_medias' );
		}
	}

	/**
	 * Fonction principale pour traiter les médias.
	 *
	 * @return void
	 */
	public static function process_medias() {
		set_transient( 'alli1d_media_cron_running', true, 60 * 60 ); // 1 hour
		do_action( 'alli1d_log', 'MediaCron started.', Logs::NOTICE, Logs::MEDIAS_LOG );
		$media_repository   = MediaRepository::get_instance();
		$movie_repository   = MovieRepository::get_instance();
		$tv_show_repository = TvShowRepository::get_instance();
		$medias             = $media_repository->get_all_urls();
		do_action( 'alli1d_log', count( $medias ) . ' Medias to process.', Logs::DEBUG, Logs::MEDIAS_LOG );
		foreach ( $medias as $media ) {
			// Logique pour parcourir chaque média.
			do_action( 'alli1d_log', 'Processing media: ' . $media->url, Logs::DEBUG, Logs::MEDIAS_LOG );
			$retour = apply_filters( 'alli1d_process_media', $media );
			if ( 'true' === $retour->found ) {
				switch ( $media->type ) {
					case 'movie':
						$existing_movies = $movie_repository->get_all_movies( [ 'title' => [ '=', $retour->title ] ] );
						if ( count( $existing_movies ) > 0 ) {
							do_action( 'alli1d_log', 'Movie already exists: ' . $retour->title, Logs::DEBUG, Logs::MEDIAS_LOG );
							$current_movie = $existing_movies[0];
						} else {
							$current_movie = new Movie( (array) $retour );
							$current_movie->set_id( null );
							do_action( 'alli1d_log', 'Movie created: ' . $retour->title, Logs::DEBUG, Logs::MEDIAS_LOG );
						}
						$current_movie->add_url( $media->url );
						$movie_repository->save_movie( $current_movie );
						do_action( 'alli1d_log', 'Movie updated: ' . $retour->title, Logs::DEBUG, Logs::MEDIAS_LOG );
						$media_repository->delete_url( $media );
						break;
					case 'tv_show':
						$existing_tv_shows = $tv_show_repository->get_all_tv_shows( [ 'title' => [ '=', $retour->title ] ] );
						if ( count( $existing_tv_shows ) > 0 ) {
							do_action( 'alli1d_log', 'TV Show already exists: ' . $retour->title, Logs::DEBUG, Logs::MEDIAS_LOG );
							$current_tv_show = $existing_tv_shows[0];
						} else {
							do_action( 'alli1d_log', 'TV Show created: ' . $retour->title, Logs::DEBUG, Logs::MEDIAS_LOG );
							$current_tv_show = new TvShow( (array) $retour );
							$current_tv_show->set_id( null );
							$current_tv_show->init_data();
						}
						$current_tv_show->add_url( $media->url );
						$tv_show_repository->save_tv_show( $current_tv_show );
						do_action( 'alli1d_log', 'TV Show updated: ' . $retour->title, Logs::DEBUG, Logs::MEDIAS_LOG );
						$media_repository->delete_url( $media );
						break;
				}
			}
		}
		do_action( 'alli1d_log', 'MediaCron finished.', Logs::NOTICE, Logs::MEDIAS_LOG );
		delete_transient( 'alli1d_media_cron_running' );
	}
}
