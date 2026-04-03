<?php
/**
 * Movie API endpoint.
 *
 * @package AllI1D
 */

namespace AllI1D\Api;

use AllI1D\Interfaces\Api;
use AllI1D\Models\Repositories\MovieRepository;
use WP_Error;

class MovieApi implements Api {

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
	private $current_namespace = 'movie';

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
			'movie'          => rest_url( $this->get_namespace() ),
			'movie_run_cron' => rest_url( $this->get_namespace() . '/cron' ),
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
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_movie_list' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
			);
		register_rest_route(
			$this->route_namespace,
			$this->current_namespace,
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'set_movie' ],
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
	 * Schedule a movie processing cron job.
	 *
	 * @return \WP_REST_Response
	 */
	public function run_cron() {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'Scheduling Movie cron job' );
		wp_schedule_single_event( time(), 'alli1d_process_movies' );
		return rest_ensure_response(
			[
				'success' => true,
				'message' => 'Cron scheduled successfully.',
			]
			);
	}

	/**
	 * Get all movies.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_movie_list() {
		$movie_repository = MovieRepository::get_instance();
		$movies           = $movie_repository->get_all_movies();
		return rest_ensure_response( $movies );
	}

	/**
	 * Update a movie from request data.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function set_movie( $request ) {
		try {
			$movie_repository = MovieRepository::get_instance();
			$movie            = $movie_repository->get_by_id( (int) $request->get_param( 'movieId' ) );
			$movie->set_search_title( (string) $request->get_param( 'movieSearchTitle' ) )
				->set_status( (string) $request->get_param( 'movieStatus' ) );
			$movie_repository->save_movie( $movie );
			return rest_ensure_response(
				[
					'success' => true,
					'message' => 'Saved successfully.',
				]
				);
		} catch ( \Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Error saving Movie: ' . $e->getMessage() );
			return new WP_Error( 'error', $e->getMessage(), [ 'status' => 500 ] );
		}
	}
}
