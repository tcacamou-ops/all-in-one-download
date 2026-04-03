<?php
namespace AllI1D\Api;

use AllI1D\Interfaces\Api;
use WP_HTTP_Response;
use AllI1D\Components\Listing;
use AllI1D\Components\MovieItem;
use AllI1D\Components\TvShowItem;

class ListingApi implements Api {

	/**
	 * Route namespace.
	 *
	 * @var string
	 */
	private $route_namespace;

	/**
	 * Current namespace segment.
	 *
	 * @var string
	 */
	private $current_namespace = 'listing';

	/**
	 * Constructor.
	 *
	 * @param string $route_namespace The REST API namespace.
	 */
	public function __construct( string $route_namespace ) {
		$this->route_namespace = $route_namespace;
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Get the API namespace.
	 *
	 * @return string
	 */
	public function get_namespace(): string {
		return $this->route_namespace . '/' . $this->current_namespace;
	}

	/**
	 * Check permissions.
	 *
	 * @return bool
	 */
	public function check_permissions(): bool {
		return current_user_can( 'alli1d' );
	}

	/**
	 * Get routes.
	 *
	 * @return array<string, string>
	 */
	public function get_routes(): array {
		return [
			'listing_refresh' => rest_url( $this->get_namespace() . '/refresh' ),
			'movie_item'      => rest_url( $this->get_namespace() . '/movie' ),
			'tvshow_item'     => rest_url( $this->get_namespace() . '/tvshow' ),
		];
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->route_namespace,
			$this->current_namespace . '/refresh',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'refresh_listing' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
			);
		register_rest_route(
			$this->route_namespace,
			$this->current_namespace . '/movie',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_movie_item' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
			);
		register_rest_route(
			$this->route_namespace,
			$this->current_namespace . '/tvshow',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_tvshow_item' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
			);
	}

	/**
	 * Refresh the listing.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_HTTP_Response
	 */
	public function refresh_listing( $request ) {
		$search = empty( $request->get_param( 'search' ) ) ? '' : $request->get_param( 'search' );
		$type   = empty( $request->get_param( 'type' ) ) ? 'tvshow' : $request->get_param( 'type' );
		if ( ! in_array( $type, [ 'tvshow', 'movie' ], true ) ) {
			return new WP_HTTP_Response(
				'Invalid type parameter',
				400,
				[
					'Content-Type' => 'text/plain; charset=UTF-8',
				]
				);
		}
		$listing = new Listing( $type, $search );
		$retour  = $listing->render( false );
		return new WP_HTTP_Response(
			[ 'message' => $retour ],
			200,
			[
				'Content-Type' => 'text/html; charset=UTF-8',
			]
			);
	}

	/**
	 * Get a movie item HTML.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_HTTP_Response
	 */
	public function get_movie_item( $request ) {
		$item_id = $request->get_param( 'itemId' );
		if ( empty( $item_id ) ) {
			return new WP_HTTP_Response(
				'Item ID is required',
				400,
				[
					'Content-Type' => 'text/plain; charset=UTF-8',
				]
				);
		}

		$movie_item = new MovieItem( $item_id );
		$retour     = $movie_item->render( false );
		return new WP_HTTP_Response(
			[ 'message' => $retour ],
			200,
			[
				'Content-Type' => 'text/html; charset=UTF-8',
			]
			);
	}

	/**
	 * Get a TV show item HTML.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_HTTP_Response
	 */
	public function get_tvshow_item( $request ) {
		$item_id = $request->get_param( 'itemId' );
		if ( empty( $item_id ) ) {
			return new WP_HTTP_Response(
				'Item ID is required',
				400,
				[
					'Content-Type' => 'text/plain; charset=UTF-8',
				]
				);
		}
		$tvshow_item = new TvShowItem( $item_id );
		$retour      = $tvshow_item->render( false );
		return new WP_HTTP_Response(
			[ 'message' => $retour ],
			200,
			[
				'Content-Type' => 'text/html; charset=UTF-8',
			]
			);
	}
}
