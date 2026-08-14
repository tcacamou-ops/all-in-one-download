<?php
/**
 * FeedCatalogRefreshCron class file.
 */

namespace AllI1D\Crons;

/**
 * Schedules the generic catalog-refresh broadcast: the core does not know
 * which provider add-ons are active, it only fires
 * `alli1d_refresh_feed_catalog` on a schedule and lets each active provider
 * fetch its own latest items and index them via `alli1d_index_feed_catalog()`.
 * No handler is registered by the core itself — WP-Cron calls `do_action()`
 * on the scheduled hook directly, exactly like `alli1d_process_movie`/
 * `alli1d_search_providers` are broadcast today.
 */
class FeedCatalogRefreshCron {

	/**
	 * Schedule the cron.
	 */
	public static function schedule_cron(): void {
		if ( ! wp_next_scheduled( 'alli1d_refresh_feed_catalog' ) ) {
			wp_schedule_event( time(), 'hourly', 'alli1d_refresh_feed_catalog' );
		}
	}

	/**
	 * Unschedule the cron.
	 */
	public static function unschedule_cron(): void {
		$timestamp = wp_next_scheduled( 'alli1d_refresh_feed_catalog' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'alli1d_refresh_feed_catalog' );
		}
	}
}
