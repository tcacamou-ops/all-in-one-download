<?php
namespace AllI1D\Crons;

use AllI1D\Models\Movie;
use AllI1D\Models\Repositories\MovieRepository;
use AllI1D\Actions\Logs;

class MovieCron {
	/**
	 * Schedule the cron.
	 */
	public static function schedule_cron(): void {
		if ( ! wp_next_scheduled( 'alli1d_process_movies' ) ) {
			wp_schedule_event( time(), 'daily', 'alli1d_process_movies' );
		}
	}

	/**
	 * Unschedule the cron.
	 */
	public static function unschedule_cron(): void {
		$timestamp = wp_next_scheduled( 'alli1d_process_movies' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'alli1d_process_movies' );
		}
	}

	/**
	 * Process movies.
	 */
	public static function process_movies(): void {
		set_transient( 'alli1d_movies_cron_running', true, 60 * 60 ); // 1 hour
		do_action( 'alli1d_log', 'Movie Cron running.', Logs::NOTICE, Logs::FILMS_LOG );
		$movies_repository = MovieRepository::get_instance();
		$movies            = $movies_repository->get_all_movies( [ 'status' => [ '=', 'actif' ] ] );
		do_action( 'alli1d_log', count( $movies ) . ' Movies to process.', Logs::DEBUG, Logs::FILMS_LOG );
		foreach ( $movies as $movie ) {
			do_action( 'alli1d_log', 'Processing movie: ' . $movie->get_search_title(), Logs::DEBUG, Logs::FILMS_LOG );
			$what = [
				'title'        => $movie->get_search_title(),
				'audio_format' => $movie->get_audio_format(),
				'found'        => false,
				'results'      => [],
			];
			// do_action('alli1d_log', print_r($what, true), Logs::DEBUG, Logs::FILMS_LOG).
			$retour = apply_filters( 'alli1d_process_movie', $what );
			if ( true === $retour['found'] ) {
				do_action( 'alli1d_log', 'Torrent Found ', Logs::DEBUG, Logs::FILMS_LOG );
				$download_item                   = $retour['results'][0];
				$download_item['downloaded']     = false;
				$download_item['dest_directory'] = $movie->get_download_directory();
				$downloaded                      = apply_filters( 'alli1d_process_torrent', $download_item );
				if ( true === $downloaded['downloaded'] ) {
					$movie->set_status( $movie::$downloaded );
					do_action( 'alli1d_log', 'Download launch : ' . $movie->get_title(), Logs::NOTICE, Logs::FILMS_LOG );
				} else {
					do_action( 'alli1d_log', 'Download failed : ' . $movie->get_title(), Logs::ERROR, Logs::FILMS_LOG );
				}
				$movies_repository->save_movie( $movie );
			} else {
				do_action( 'alli1d_log', 'No torrent found', Logs::DEBUG, Logs::FILMS_LOG );
			}
		}
		do_action( 'alli1d_log', 'Movie Cron finished.', Logs::NOTICE, Logs::FILMS_LOG );
		delete_transient( 'alli1d_movies_cron_running' );
	}
}
