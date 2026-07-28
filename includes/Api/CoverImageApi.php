<?php
/**
 * Cover image upload API endpoint.
 *
 * @package AllI1D
 */

namespace AllI1D\Api;

use AllI1D\Interfaces\Api;
use AllI1D\Models\Repositories\MovieRepository;
use AllI1D\Models\Repositories\TvShowRepository;
use AllI1D\Services\CoverImageUploader;
use WP_Error;

class CoverImageApi implements Api {

	/**
	 * The route namespace.
	 *
	 * @var string
	 */
	private $route_namespace;

	/**
	 * The current namespace segment.
	 *
	 * @var string
	 */
	private $current_namespace = 'cover-image';

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
			'cover_image' => rest_url( $this->get_namespace() ),
		];
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->route_namespace,
			$this->current_namespace,
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'upload_cover_image' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
			);
	}

	/**
	 * Handle a cover image upload for a movie or TV show fiche.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function upload_cover_image( $request ) {
		$type    = (string) $request->get_param( 'type' );
		$item_id = (int) $request->get_param( 'itemId' );

		if ( ! in_array( $type, [ 'movie', 'tvshow' ], true ) || $item_id <= 0 ) {
			return new WP_Error( 'invalid_params', __( 'Paramètres invalides.', 'all-in-one-download' ), [ 'status' => 400 ] );
		}

		$files = $request->get_file_params();
		$file  = $files['cover_image'] ?? null;

		if ( null === $file ) {
			return new WP_Error( 'missing_file', __( 'Aucun fichier reçu.', 'all-in-one-download' ), [ 'status' => 400 ] );
		}

		if ( 'movie' === $type ) {
			$repository = MovieRepository::get_instance();
			$item       = $repository->get_by_id( $item_id );
		} else {
			$repository = TvShowRepository::get_instance();
			$item       = $repository->get_tv_show_by_id( $item_id );
		}

		if ( null === $item ) {
			return new WP_Error( 'not_found', __( 'Fiche introuvable.', 'all-in-one-download' ), [ 'status' => 404 ] );
		}

		try {
			$url = CoverImageUploader::store( $file, $type, $item_id );
		} catch ( \InvalidArgumentException $e ) {
			return new WP_Error( 'invalid_file', $e->getMessage(), [ 'status' => 422 ] );
		}

		$item->set_cover_image( $url );

		if ( 'movie' === $type ) {
			$repository->save_movie( $item );
		} else {
			$repository->save_tv_show( $item );
		}

		return rest_ensure_response(
			[
				'success'     => true,
				'cover_image' => $url,
			]
			);
	}
}
