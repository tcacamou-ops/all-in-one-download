<?php
/**
 * FeedCatalogPurgeCron class file.
 */

namespace AllI1D\Crons;

use AllI1D\Actions\Logs;
use AllI1D\Models\Repositories\FeedCatalogRepository;

class FeedCatalogPurgeCron {

	/**
	 * Schedule the cron.
	 */
	public static function schedule_cron(): void {
		if ( ! wp_next_scheduled( 'alli1d_purge_feed_catalog' ) ) {
			wp_schedule_event( time(), 'daily', 'alli1d_purge_feed_catalog' );
		}
	}

	/**
	 * Unschedule the cron.
	 */
	public static function unschedule_cron(): void {
		$timestamp = wp_next_scheduled( 'alli1d_purge_feed_catalog' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'alli1d_purge_feed_catalog' );
		}
	}

	/**
	 * Purge catalog entries that haven't been seen in a refresh cycle for
	 * longer than `ALLI1D_FEED_CATALOG_STALE_TTL` (likely removed from the
	 * tracker).
	 */
	public static function purge_feed_catalog(): void {
		$removed = FeedCatalogRepository::get_instance()->purge_stale( ALLI1D_FEED_CATALOG_STALE_TTL );
		do_action( 'alli1d_log', "Feed catalog purge: {$removed} stale entries removed.", Logs::NOTICE, Logs::MEDIAS_LOG );
	}
}
