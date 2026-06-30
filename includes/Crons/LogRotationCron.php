<?php
/**
 * LogRotationCron class file.
 */

namespace AllI1D\Crons;

use AllI1D\Actions\Logs;

class LogRotationCron {

	/**
	 * Schedule the cron to run daily at midnight (WordPress timezone).
	 */
	public static function schedule_cron(): void {
		if ( ! wp_next_scheduled( 'alli1d_rotate_logs' ) ) {
			$midnight = new \DateTime( 'tomorrow midnight', wp_timezone() );
			wp_schedule_event( $midnight->getTimestamp(), 'daily', 'alli1d_rotate_logs' );
		}
	}

	/**
	 * Unschedule the cron.
	 */
	public static function unschedule_cron(): void {
		$timestamp = wp_next_scheduled( 'alli1d_rotate_logs' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'alli1d_rotate_logs' );
		}
	}

	/**
	 * Rotate log files: archive the current log with yesterday's date, then purge files older than 48 h.
	 */
	public static function rotate_logs(): void {
		$upload_dir = wp_upload_dir();
		$logs_dir   = $upload_dir['basedir'] . '/alli1d/logs';

		if ( ! is_dir( $logs_dir ) ) {
			return;
		}

		$timezone    = wp_timezone();
		$yesterday   = new \DateTime( 'yesterday', $timezone );
		$date_suffix = $yesterday->format( 'Y-m-d' );

		foreach ( [ Logs::MEDIAS_LOG, Logs::SERIES_LOG, Logs::FILMS_LOG ] as $log_file ) {
			self::archive_log_file( $logs_dir, $log_file, $date_suffix );
		}

		self::purge_old_logs( $logs_dir );
	}

	/**
	 * Rename the current log file to its dated archive name and recreate an empty current file.
	 *
	 * @param string $logs_dir    Absolute path to the logs directory.
	 * @param string $log_file    Log file name (e.g. "medias.log").
	 * @param string $date_suffix Date string to append (e.g. "2026-06-28").
	 */
	private static function archive_log_file( string $logs_dir, string $log_file, string $date_suffix ): void {
		$current_path = $logs_dir . '/' . $log_file;

		if ( ! file_exists( $current_path ) || 0 === filesize( $current_path ) ) {
			return;
		}

		$base          = pathinfo( $log_file, PATHINFO_FILENAME );
		$archived_path = $logs_dir . '/' . $base . '-' . $date_suffix . '.log';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
		rename( $current_path, $archived_path );

		// Recreate an empty current log file so writers never hit a missing file.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch
		touch( $current_path );
	}

	/**
	 * Delete archived log files whose date is older than 48 h (i.e. more than 2 days ago).
	 *
	 * Only files matching the pattern <name>-YYYY-MM-DD.log are considered; the current
	 * log files (no date suffix) are always preserved.
	 *
	 * @param string $logs_dir Absolute path to the logs directory.
	 */
	private static function purge_old_logs( string $logs_dir ): void {
		$timezone    = wp_timezone();
		$cutoff_date = new \DateTime( '-2 days midnight', $timezone );
		$files       = glob( $logs_dir . '/*.log' );

		if ( ! $files ) {
			return;
		}

		foreach ( $files as $file ) {
			if ( ! preg_match( '/^[\w]+-(\d{4}-\d{2}-\d{2})\.log$/', basename( $file ), $matches ) ) {
				continue;
			}
			$file_date = new \DateTime( $matches[1], $timezone );
			if ( $file_date < $cutoff_date ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				unlink( $file );
			}
		}
	}
}
