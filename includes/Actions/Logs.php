<?php
/**
 * Logging service.
 *
 * @package AllI1D
 */

namespace AllI1D\Actions;

class Logs {

	public const NOTICE  = 'notice';
	public const WARNING = 'warning';
	public const ERROR   = 'error';
	public const DEBUG   = 'debug';

	public const MEDIAS_LOG = 'medias.log';
	public const SERIES_LOG = 'series.log';
	public const FILMS_LOG  = 'films.log';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'alli1d_log', [ $this, 'log_message' ], 10, 3 );
		$this->ensure_alli1d_directory();
	}

	/**
	 * Ensure the alli1d log directory exists.
	 */
	private function ensure_alli1d_directory(): void {
		$upload_dir = wp_upload_dir();
		$alli1d_dir = $upload_dir['basedir'] . '/alli1d';

		if ( ! is_dir( $alli1d_dir ) ) {
			wp_mkdir_p( $alli1d_dir );
		}

		$logs_dir = $alli1d_dir . '/logs';
		if ( ! is_dir( $logs_dir ) ) {
			wp_mkdir_p( $logs_dir );
		}

		$this->ensure_log_files( $logs_dir );
	}

	/**
	 * Ensure log files exist.
	 *
	 * @param string $logs_dir The logs directory path.
	 */
	private function ensure_log_files( string $logs_dir ): void {
		$log_files = [
			self::MEDIAS_LOG,
			self::SERIES_LOG,
			self::FILMS_LOG,
		];

		foreach ( $log_files as $log_file ) {
			$log_path = $logs_dir . '/' . $log_file;
			if ( ! file_exists( $log_path ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch
				touch( $log_path );
			}
		}
	}

	/**
	 * Log a message to a log file.
	 *
	 * @param string $message     The message to log.
	 * @param string $type        The log level.
	 * @param string $destination The log file name.
	 */
	public function log_message( $message, $type = self::NOTICE, $destination = self::MEDIAS_LOG ): void {
		$timestamp   = gmdate( 'Y-m-d H:i:s' );
		$log_entry   = "[$timestamp] [$type] $message\n";
		$destination = wp_upload_dir()['basedir'] . '/alli1d/logs/' . $destination;
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $log_entry, 3, $destination );
	}
}
