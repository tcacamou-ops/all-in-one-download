<?php
namespace AllI1D\Crons;

use AllI1D\Models\TvShow;
use AllI1D\Models\Repositories\TvShowRepository;
use AllI1D\Actions\Logs;

class TvShowCron {
	/**
	 * Schedule the cron.
	 */
	public static function schedule_cron(): void {
		if ( ! wp_next_scheduled( 'alli1d_process_tv_shows' ) ) {
			wp_schedule_event( time(), 'daily', 'alli1d_process_tv_shows' );
		}
	}

	/**
	 * Unschedule the cron.
	 */
	public static function unschedule_cron(): void {
		$timestamp = wp_next_scheduled( 'alli1d_process_tv_shows' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'alli1d_process_tv_shows' );
		}
	}

	/**
	 * Process TV shows.
	 */
	public static function process_tv_shows(): void {
		set_transient( 'alli1d_tv_shows_cron_running', true, 60 * 60 ); // 1 hour
		do_action( 'alli1d_log', 'TvShow Cron running.', Logs::NOTICE, Logs::SERIES_LOG );
		$tv_show_repository = TvShowRepository::get_instance();
		$tv_shows           = $tv_show_repository->get_all_tv_shows( [ 'status' => [ '=', 'actif' ] ] );
		foreach ( $tv_shows as $tv_show ) {
			$saisons = $tv_show->get_saisons();
			do_action( 'alli1d_log', 'Processing TV Show: ' . $tv_show->get_title(), Logs::DEBUG, Logs::SERIES_LOG );
			if ( empty( $saisons ) ) {
				do_action( 'alli1d_log', 'No seasons found for TV Show: ' . $tv_show->get_title(), Logs::WARNING, Logs::SERIES_LOG );
				continue;
			}
			foreach ( $saisons as $key => $saison ) {
				// Logique pour parcourir chaque saison.
				if ( 'actif' !== $saison['status'] ) {
					do_action( 'alli1d_log', 'Saison ' . $saison['id'] . ' is not active.', Logs::WARNING, Logs::SERIES_LOG );
					continue;
				}
				do_action( 'alli1d_log', 'Processing Season: ' . $saison['id'], Logs::DEBUG, Logs::SERIES_LOG );
				$episode = $saison['lastepisode'];
				$what    = [
					'title'        => $tv_show->get_search_title(),
					'saison'       => $saison['id'],
					'audio_format' => $tv_show->get_audio_format(),
					'found'        => false,
					'results'      => [],
				];
				if ( 0 === $episode ) {
					do_action( 'alli1d_log', 'The beginning we try a full saison', Logs::DEBUG, Logs::SERIES_LOG );
					$what['episode'] = $episode;
					$retour          = apply_filters( 'alli1d_process_tvshow', $what );
					// do_action('alli1d_log', wp_json_encode($what), Logs::DEBUG, Logs::SERIES_LOG).
					if ( true === $retour['found'] ) {
						$download_item                   = $retour['results'][0];
						$download_item['downloaded']     = false;
						$download_item['dest_directory'] = $tv_show->get_download_directory( $saison['id'] );
						$downloaded                      = apply_filters( 'alli1d_process_' . $download_item['type'], $download_item );
						do_action( 'alli1d_log', 'Download Item : ' . wp_json_encode( $download_item ), Logs::DEBUG, Logs::SERIES_LOG );
						if ( true === $downloaded['downloaded'] ) {
							$tv_show->next_saison( $saison['id'] )->enable_saison( $saison['id'], false );
							do_action( 'alli1d_log', 'Download launch : ' . $saison['id'] . ' - ' . $episode, Logs::NOTICE, Logs::SERIES_LOG );
							$tv_show_repository->save_tv_show( $tv_show );
							continue;
						} else {
							do_action( 'alli1d_log', 'Download failed : ' . $saison['id'] . ' - ' . $episode, Logs::ERROR, Logs::SERIES_LOG );
						}
					}
				}
				++$episode;
				do_action( 'alli1d_log', 'Episode: ' . $episode, Logs::DEBUG, Logs::SERIES_LOG );
				$what['episode'] = $episode;
				$what['found']   = false;
				$what['results'] = [];
				$retour          = apply_filters( 'alli1d_process_tvshow', $what );
				// do_action('alli1d_log', wp_json_encode($what), Logs::DEBUG, Logs::SERIES_LOG).
				if ( true === $retour['found'] ) {
					$download_item                   = $retour['results'][0];
					$download_item['downloaded']     = false;
					$download_item['dest_directory'] = $tv_show->get_download_directory( $saison['id'] );
					$downloaded                      = apply_filters( 'alli1d_process_torrent', $download_item );
					if ( true === $downloaded['downloaded'] ) {
						$tv_show = $tv_show->next_episode( $saison['id'], $episode );
						$tv_show = $tv_show->next_saison( $saison['id'] );
						do_action( 'alli1d_log', 'Download launch : ' . $saison['id'] . ' - ' . $episode, Logs::NOTICE, Logs::SERIES_LOG );
					} else {
						do_action( 'alli1d_log', 'Download failed : ' . $saison['id'] . ' - ' . $episode, Logs::ERROR, Logs::SERIES_LOG );
					}
					do_action( 'alli1d_log', 'Download Result : ' . wp_json_encode( $downloaded ), Logs::DEBUG, Logs::SERIES_LOG );
				}
				$tv_show_repository->save_tv_show( $tv_show );
				sleep( 3 ); // Pour éviter de surcharger le serveur avec trop de requêtes en même temps.
			}
		}
		do_action( 'alli1d_log', 'TvShow Cron finished.', Logs::NOTICE, Logs::SERIES_LOG );
		delete_transient( 'alli1d_tv_shows_cron_running' );
	}
}
