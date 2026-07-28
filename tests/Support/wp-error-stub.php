<?php
// Minimal WP_Error stand-in for unit tests: the real class lives in
// wordpress/wp-includes, which is gitignored and not present in this
// test environment.
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private array $errors = [];

		public function __construct( $code = '', $message = '', $data = '' ) {
			if ( '' !== $code ) {
				$this->errors[ $code ][] = $message;
			}
		}

		public function get_error_message() {
			$first = reset( $this->errors );
			return $first[0] ?? '';
		}
	}
}
