<?php
/**
 * Indexing reset API endpoint.
 *
 * @package AllI1D
 */

namespace AllI1D\Api;

use AllI1D\Interfaces\Api;
use AllI1D\Models\Repositories\FeedCatalogRepository;
use AllI1D\Models\Repositories\MovieRepository;
use AllI1D\Models\Repositories\TvShowRepository;

class IndexingApi implements Api {

	/**
	 * The route namespace.
	 *
	 * @var string
	 */
	private string $route_namespace;

	/**
	 * The current namespace segment.
	 *
	 * @var string
	 */
	private string $current_namespace = 'indexing';

	/**
	 * Constructor.
	 *
	 * @param string $route_namespace The route namespace.
	 */
	public function __construct( string $route_namespace ) {
		$this->route_namespace = $route_namespace;
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Get the full namespace for this API.
	 *
	 * @return string
	 */
	public function get_namespace(): string {
		return $this->route_namespace . '/' . $this->current_namespace;
	}

	/**
	 * Check if the current user has permission.
	 *
	 * @return bool
	 */
	public function check_permissions(): bool {
		return current_user_can( 'alli1d' );
	}

	/**
	 * Get the registered routes.
	 *
	 * @return array<string, string>
	 */
	public function get_routes(): array {
		return [
			'indexing_reset' => rest_url( $this->get_namespace() . '/reset' ),
		];
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->route_namespace,
			$this->current_namespace . '/reset',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'reset' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);
	}

	/**
	 * Reset the feed catalog and all general_search_done flags.
	 *
	 * @return \WP_REST_Response
	 */
	public function reset() {
		$catalog_removed = FeedCatalogRepository::get_instance()->truncate_all();
		$movies_reset    = MovieRepository::get_instance()->reset_all_general_search_done();
		$tv_shows_reset  = TvShowRepository::get_instance()->reset_all_general_search_done();

		return rest_ensure_response(
			[
				'success'         => true,
				'catalog_removed' => $catalog_removed,
				'movies_reset'    => $movies_reset,
				'tv_shows_reset'  => $tv_shows_reset,
			]
		);
	}
}
