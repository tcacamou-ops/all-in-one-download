<?php
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Définir les constantes du plugin.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/wordpress/' );
}
if ( ! defined( 'ALLI1D_DIR' ) ) {
	define( 'ALLI1D_DIR', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'ALLI1D_URL' ) ) {
	define( 'ALLI1D_URL', 'http://localhost/' );
}
if ( ! defined( 'AUTH_KEY' ) ) {
	define( 'AUTH_KEY', 'phpunit-test-auth-key' );
}

require_once __DIR__ . '/Support/wp-error-stub.php';

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS );
}
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS );
}
