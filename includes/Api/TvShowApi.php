<?php
namespace AllI1D\Api;

use AllI1D\Interfaces\Api;
use AllI1D\Models\Repositories\TvShowRepository;
use AllI1D\Services\CoverImageUploader;
use AllI1D\Services\TorrentMetadataParser;
use WP_Error;

class TvShowApi implements Api {

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
	private $current_namespace = 'tvshow';

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
	 * Check if the current user has permission to delete this resource.
	 *
	 * @return bool
	 */
	public function check_delete_permissions(): bool {
		return current_user_can( 'alli1d_admin' );
	}

	/**
	 * Get routes.
	 *
	 * @return array<string, string>
	 */
	public function get_routes(): array {
		return [
			'tvshow'          => rest_url( $this->get_namespace() ),
			'tvshow_run_cron' => rest_url( $this->get_namespace() . '/cron' ),
		];
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->route_namespace,
			$this->current_namespace,
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_tv_show_list' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
			);
		register_rest_route(
			$this->route_namespace,
			$this->current_namespace,
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'set_tv_show' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
			);
		register_rest_route(
			$this->route_namespace,
			$this->current_namespace,
			[
				'methods'             => 'DELETE',
				'callback'            => [ $this, 'delete_tv_show' ],
				'permission_callback' => [ $this, 'check_delete_permissions' ],
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
	 * Run the cron job.
	 *
	 * @return \WP_REST_Response
	 */
	public function run_cron() {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'Scheduling cron job' );
		wp_schedule_single_event( time(), 'alli1d_process_tv_shows' );
		return rest_ensure_response(
			[
				'success' => true,
				'message' => 'Cron scheduled successfully.',
			]
			);
	}

	/**
	 * Delete a TV show by ID.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_tv_show( $request ) {
		$tv_show_id = (int) $request->get_param( 'tvShowId' );
		if ( $tv_show_id <= 0 ) {
			return new WP_Error( 'invalid_id', __( 'Identifiant invalide.', 'all-in-one-download' ), [ 'status' => 400 ] );
		}

		TvShowRepository::get_instance()->delete_tv_show( $tv_show_id );
		CoverImageUploader::delete_if_exists( 'tvshow', $tv_show_id );

		return rest_ensure_response(
			[
				'success' => true,
				'message' => 'Deleted successfully.',
			]
			);
	}

	/**
	 * Get all TV shows.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_tv_show_list() {
		$tv_show_repository = TvShowRepository::get_instance();
		$tv_shows           = $tv_show_repository->get_all_tv_shows();
		return rest_ensure_response( $tv_shows );
	}

	/**
	 * Set TV show data.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function set_tv_show( $request ) {
		try {
			$tv_show_repository = TvShowRepository::get_instance();
			$tv_show            = $tv_show_repository->get_tv_show_by_id( (int) $request->get_param( 'tvShowId' ) );
			$tv_show->set_search_title( (string) $request->get_param( 'tvShowSearchTitle' ) )
				->set_status( (string) $request->get_param( 'tvShowStatus' ) )
				->set_saisons( (array) $request->get_param( 'tvShowSeasons' ) );

			$audio_format = (string) $request->get_param( 'tvShowAudioFormat' );
			if ( in_array( $audio_format, [ 'VF', 'VOSTFR' ], true ) ) {
				$tv_show->set_audio_format( $audio_format );
			}

			$quality = (array) $request->get_param( 'tvShowQuality' );
			if ( in_array( 'any', $quality, true ) ) {
				$tv_show->set_quality( 'any' );
			} else {
				$quality = array_values( array_intersect( $quality, TorrentMetadataParser::SELECTABLE_QUALITIES ) );
				$tv_show->set_quality( empty( $quality ) ? TorrentMetadataParser::DEFAULT_QUALITY : implode( ',', $quality ) );
			}

			$tv_show_repository->save_tv_show( $tv_show );
			return rest_ensure_response(
				[
					'success' => true,
					'message' => 'Tv Show saved successfully.',
				]
				);
		} catch ( \Throwable $th ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Error saving TV show: ' . $th->getMessage() );
			$retour = new WP_Error( 'internal_error', __( 'Une erreur est survenue lors de l\'enregistrement.', 'all-in-one-download' ), [ 'status' => 500 ] );
			return rest_ensure_response( $retour );
		}
	}
}
