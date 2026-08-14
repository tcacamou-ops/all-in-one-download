<?php
/**
 * WP-CLI command to manage the indexed feed/API providers catalog.
 *
 * @package AllI1D
 */

namespace AllI1D\Cli;

use AllI1D\Models\Repositories\FeedCatalogRepository;

/**
 * Manage the local catalog of indexed feed/API providers items.
 */
class FeedCatalogCommand {

	/**
	 * Flush indexed catalog entries.
	 *
	 * ## OPTIONS
	 *
	 * [--provider=<provider>]
	 * : Only flush entries for this provider slug (e.g. tr4ker, c411).
	 *
	 * [--type=<type>]
	 * : Only flush entries of this type (movie|tvshow).
	 *
	 * ## EXAMPLES
	 *
	 *     wp alli1d feed-catalog flush
	 *     wp alli1d feed-catalog flush --provider=c411
	 *     wp alli1d feed-catalog flush --type=tvshow
	 *
	 * @param array<int, string>    $args       Positional arguments (unused).
	 * @param array<string, string> $assoc_args Associative arguments (--provider, --type).
	 */
	public function flush( array $args, array $assoc_args ): void {
		$repository = FeedCatalogRepository::get_instance();
		$provider   = $assoc_args['provider'] ?? '';
		$type       = $assoc_args['type'] ?? '';

		if ( '' !== $provider ) {
			$removed = $repository->purge_by_provider( $provider );
			\WP_CLI::success( sprintf( '%d catalog entr%s purged for provider "%s".', $removed, 1 === $removed ? 'y' : 'ies', $provider ) );
			return;
		}

		if ( '' !== $type ) {
			$removed = $repository->purge_by_type( $type );
			\WP_CLI::success( sprintf( '%d catalog entr%s purged for type "%s".', $removed, 1 === $removed ? 'y' : 'ies', $type ) );
			return;
		}

		$removed = $repository->purge_stale( ALLI1D_FEED_CATALOG_STALE_TTL );
		\WP_CLI::success( sprintf( '%d stale catalog entr%s purged.', $removed, 1 === $removed ? 'y' : 'ies' ) );
	}

	/**
	 * Trigger an immediate catalog refresh, without waiting for the next
	 * scheduled `alli1d_refresh_feed_catalog` tick.
	 *
	 * ## EXAMPLES
	 *
	 *     wp alli1d feed-catalog refresh
	 */
	public function refresh(): void {
		do_action( 'alli1d_refresh_feed_catalog' );
		\WP_CLI::success( 'Feed catalog refresh triggered.' );
	}

	/**
	 * Display the feed catalog indexer status: how many provider add-ons are
	 * wired to it (via the `alli1d_feed_catalog_providers` filter), and how
	 * many items are indexed in total and per media type.
	 *
	 * ## EXAMPLES
	 *
	 *     wp alli1d feed-catalog status
	 */
	public function status(): void {
		$repository = FeedCatalogRepository::get_instance();

		/**
		 * Filter the list of provider slugs wired to the feed catalog indexer.
		 *
		 * Each provider add-on that listens to `alli1d_refresh_feed_catalog`
		 * and indexes its items via `alli1d_index_feed_catalog()` should
		 * append its slug here (e.g. 'tr4ker', 'c411').
		 *
		 * @param array<int, string> $providers Provider slugs wired to the indexer.
		 */
		$wired_providers = apply_filters( 'alli1d_feed_catalog_providers', [] );
		$total           = $repository->count_all();
		$by_type         = $repository->count_by_type();

		\WP_CLI::log( sprintf( '%d provider(s) wired to the feed catalog indexer.', count( $wired_providers ) ) );
		\WP_CLI::log( sprintf( '%d item(s) indexed in total.', $total ) );
		\WP_CLI::log( sprintf( '  - movie: %d', $by_type['movie'] ?? 0 ) );
		\WP_CLI::log( sprintf( '  - tvshow: %d', $by_type['tvshow'] ?? 0 ) );
	}
}
