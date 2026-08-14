<?php
/**
 * Feed catalog indexer status filter.
 *
 * @package AllI1D
 */

namespace AllI1D\Filters;

use AllI1D\Components\FeedCatalogStatus as FeedCatalogStatusComponent;
use AllI1D\Models\Repositories\FeedCatalogRepository;

/**
 * Hooks the feed catalog indexer summary into the core status page
 * (`alli1d_process_status`) and registers its detailed breakdown as a
 * settings modal (`alli1d_provider_settings_modals`).
 */
class FeedCatalogStatus {

	/**
	 * Add the feed catalog indexer summary to the status list.
	 *
	 * @param array<string, mixed> $status Status list, keyed by provider.
	 * @return array<string, mixed>
	 */
	public static function process_status( array $status ): array {
		$repository      = FeedCatalogRepository::get_instance();
		$wired_providers = apply_filters( 'alli1d_feed_catalog_providers', [] );
		$by_type         = $repository->count_by_type();

		$retour = [
			'wired_providers' => count( $wired_providers ),
			'total_indexed'   => $repository->count_all(),
			'movies_indexed'  => $by_type['movie'] ?? 0,
			'tvshows_indexed' => $by_type['tvshow'] ?? 0,
		];

		if ( empty( $wired_providers ) ) {
			$retour['error'] = __( 'No provider add-on is wired to the feed catalog indexer yet.', 'all-in-one-download' );
		} else {
			$retour['status'] = 'connected';
		}

		$status['Feed Catalog'] = $retour;
		return $status;
	}

	/**
	 * Register the feed catalog indexer settings modal.
	 *
	 * @param array<string, mixed> $modals Modals list, keyed by provider.
	 * @return array<string, mixed>
	 */
	public static function register_modal( array $modals ): array {
		$component = new FeedCatalogStatusComponent();

		$modals['Feed Catalog'] = [
			'title' => __( 'Feed Catalog Indexer Status', 'all-in-one-download' ),
			'html'  => $component->get_html(),
		];

		return $modals;
	}
}
