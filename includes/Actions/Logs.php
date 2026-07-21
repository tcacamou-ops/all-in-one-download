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

	public const MAX_LOG_LINES = 5000;

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'alli1d_log', [ $this, 'log_message' ], 10, 3 );
		add_action( 'alli1d_get_log_content', [ $this, 'get_log_content' ], 10, 2 );
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

	/**
	 * Get the content of a log file.
	 *
	 * @param string   $log_file  The log file name.
	 * @param int|null $num_lines The number of lines to retrieve from the end of the log file. If null, retrieves the entire log file.
	 * @return string The content of the log file.
	 */
	public function get_log_content( string $log_file = self::MEDIAS_LOG, ?int $num_lines = null ): string {
		if ( null !== $num_lines ) {
			$num_lines = min( abs( $num_lines ), self::MAX_LOG_LINES );
		}

		$log_path = wp_upload_dir()['basedir'] . '/alli1d/logs/' . $log_file;
		if ( file_exists( $log_path ) ) {
			if ( null !== $num_lines ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
				$fp     = fopen( $log_path, 'rb' );
				$buffer = '';
				$found  = 0;
				$pos    = filesize( $log_path );

				while ( $pos > 0 && $found <= $num_lines ) {
					$chunk_size = min( 4096, $pos );
					$pos       -= $chunk_size;
					fseek( $fp, $pos );
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
					$buffer = fread( $fp, $chunk_size ) . $buffer;
					$found  = substr_count( $buffer, "\n" );
				}

				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
				fclose( $fp );

				$lines = explode( "\n", $buffer );
				// Supprimer une éventuelle ligne vide finale.
				if ( end( $lines ) === '' ) {
					array_pop( $lines );
				}

				return implode( "\n", array_slice( $lines, -$num_lines ) );
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			return file_get_contents( $log_path );
		}
		return '';
	}
}
