<?php
/**
 * Media API endpoint.
 *
 * @package AllI1D
 */

namespace AllI1D\Api;

use AllI1D\Interfaces\Api;
use AllI1D\Models\Media;
use AllI1D\Models\Repositories\MediaRepository;

class MediaApi implements Api {

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
	private $current_namespace = 'media';

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
			'media'          => rest_url( $this->get_namespace() ),
			'media_run_cron' => rest_url( $this->get_namespace() . '/cron' ),
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
				'callback'            => [ $this, 'add_media' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
			);
		register_rest_route(
			$this->route_namespace,
			$this->current_namespace,
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_media_list' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
			);
		register_rest_route(
			$this->route_namespace,
			$this->current_namespace . '/cron',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'run_cron' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
			);
	}

	/**
	 * Add a new media URL.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function add_media( $request ) {
		$url = $request->get_param( 'url' );

		if ( empty( $url ) ) {
			return rest_ensure_response( new \WP_Error( 'missing_url', 'URL manquante', [ 'status' => 400 ] ) );
		}

		if ( ! $this->is_valid_media_url( $url ) ) {
			return rest_ensure_response( new \WP_Error( 'invalid_url', 'URL invalide', [ 'status' => 400 ] ) );
		}

		try {
			$media_repository = MediaRepository::get_instance();
			$media            = new Media( [ 'url' => $url ] );
			$media_repository->insert_url( $media );
			return rest_ensure_response( 'URL ajoutée avec succès' );
		} catch ( \Exception $e ) {
			return rest_ensure_response( new \WP_Error( 'invalid_url', 'URL invalide', [ 'status' => 400 ] ) );
		}
	}

	/**
	 * Validate that the submitted URL is well-formed and uses http(s).
	 *
	 * @param string $url The URL to validate.
	 * @return bool
	 */
	private function is_valid_media_url( $url ): bool {
		if ( ! is_string( $url ) ) {
			return false;
		}

		if ( false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );

		if ( ! in_array( strtolower( (string) $scheme ), [ 'http', 'https' ], true ) ) {
			return false;
		}

		return null !== wp_http_validate_url( $url );
	}

	/**
	 * Get all media URLs.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_media_list() {
		$media_repository = MediaRepository::get_instance();
		$media            = $media_repository->get_all_urls();
		return rest_ensure_response( $media );
	}

	/**
	 * Schedule a media processing cron job.
	 *
	 * @return \WP_REST_Response
	 */
	public function run_cron() {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'Scheduling cron job' );
		wp_schedule_single_event( time(), 'alli1d_process_medias' );
		return rest_ensure_response(
			[
				'success' => true,
				'message' => 'Cron scheduled successfully.',
			]
			);
	}
}
