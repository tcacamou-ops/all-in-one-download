<?php
/**
 * Feed catalog API endpoint.
 *
 * @package AllI1D
 */

namespace AllI1D\Api;

use AllI1D\Interfaces\Api;

class FeedCatalogApi implements Api {

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
	private $current_namespace = 'feed-catalog';

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
			'feed_catalog_run_cron' => rest_url( $this->get_namespace() . '/cron' ),
		];
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
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
	 * Schedule a feed catalog refresh cron job.
	 *
	 * @return \WP_REST_Response
	 */
	public function run_cron() {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'Scheduling feed catalog refresh cron job' );
		wp_schedule_single_event( time(), 'alli1d_refresh_feed_catalog' );
		return rest_ensure_response(
			[
				'success' => true,
				'message' => 'Cron scheduled successfully.',
			]
			);
	}
}
