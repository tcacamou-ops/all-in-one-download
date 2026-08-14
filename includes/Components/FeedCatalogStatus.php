<?php
/**
 * Feed catalog indexer status component.
 *
 * @package AllI1D
 */

namespace AllI1D\Components;

use AllI1D\Models\Repositories\FeedCatalogRepository;

/**
 * Renders a detailed, read-only breakdown of the feed catalog indexer status:
 * which provider add-ons are wired to it and how many items are indexed, in
 * total and per media type. Used as the settings modal body for the
 * "Feed Catalog" status card.
 */
class FeedCatalogStatus {

	/**
	 * Render the component and return it as an HTML string.
	 */
	public function get_html(): string {
		ob_start();
		$this->render();
		return ob_get_clean() ?: '';
	}

	/**
	 * Render the component.
	 */
	public function render(): void {
		$repository      = FeedCatalogRepository::get_instance();
		$wired_providers = apply_filters( 'alli1d_feed_catalog_providers', [] );
		$total           = $repository->count_all();
		$by_type         = $repository->count_by_type();

		echo '<p>' . esc_html__( 'Provider add-ons wired to the indexer:', 'all-in-one-download' ) . ' <strong>' . esc_html( (string) count( $wired_providers ) ) . '</strong></p>';

		if ( ! empty( $wired_providers ) ) {
			echo '<ul>';
			foreach ( $wired_providers as $provider ) {
				echo '<li>' . esc_html( (string) $provider ) . '</li>';
			}
			echo '</ul>';
		}

		echo '<p>' . esc_html__( 'Items indexed in total:', 'all-in-one-download' ) . ' <strong>' . esc_html( (string) $total ) . '</strong></p>';

		echo '<ul>';
		echo '<li>' . esc_html__( 'Movies:', 'all-in-one-download' ) . ' ' . esc_html( (string) ( $by_type['movie'] ?? 0 ) ) . '</li>';
		echo '<li>' . esc_html__( 'TV shows:', 'all-in-one-download' ) . ' ' . esc_html( (string) ( $by_type['tvshow'] ?? 0 ) ) . '</li>';
		echo '</ul>';
	}
}
