<?php
/**
 * Minimal in-memory stand-in for \WP_CLI, sufficient for FeedCacheCommand.
 * Not loaded automatically by bootstrap.php: only required by tests that
 * exercise a WP-CLI command class.
 */
if ( ! class_exists( 'WP_CLI' ) ) {
	class WP_CLI {
		/** @var array<int, array{level: string, message: string}> */
		public static array $messages = [];

		public static function success( $message ): void {
			self::$messages[] = [
				'level'   => 'success',
				'message' => $message,
			];
		}

		public static function error( $message ): void {
			self::$messages[] = [
				'level'   => 'error',
				'message' => $message,
			];
		}

		public static function log( $message ): void {
			self::$messages[] = [
				'level'   => 'log',
				'message' => $message,
			];
		}
	}
}
