<?php
/**
 * Logs REST API.
 *
 * @package AllI1D
 */

namespace AllI1D\Api;

use AllI1D\Interfaces\Api;
use AllI1D\Actions\Logs;
use WP_HTTP_Response;

class LogsApi implements Api {

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
	private $current_namespace = 'logs';

	/**
	 * Logs action instance.
	 *
	 * @var Logs
	 */
	private Logs $logs;

	/**
	 * Constructor.
	 *
	 * @param string $route_namespace The REST API namespace.
	 */
	public function __construct( string $route_namespace ) {
		$this->route_namespace = $route_namespace;
		$this->logs            = new Logs();
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
			'get_log' => rest_url( $this->get_namespace() ),
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
				'callback'            => [ $this, 'get_log' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'file'      => [
						'default'           => Logs::MEDIAS_LOG,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => static function ( $value ) {
							return in_array( $value, [ Logs::MEDIAS_LOG, Logs::SERIES_LOG, Logs::FILMS_LOG ], true );
						},
					],
					'num_lines' => [
						'type'              => 'integer',
						'default'           => 100,
						'minimum'           => 1,
						'maximum'           => Logs::MAX_LOG_LINES,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	/**
	 * Return the content of a log file.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return WP_HTTP_Response
	 */
	public function get_log( $request ): WP_HTTP_Response {
		$file      = $request->get_param( 'file' );
		$num_lines = (int) $request->get_param( 'num_lines' );

		$content = $this->logs->get_log_content( $file, $num_lines );

		return new WP_HTTP_Response(
			[ 'content' => $content ],
			200,
			[ 'Content-Type' => 'application/json; charset=UTF-8' ]
		);
	}
}
