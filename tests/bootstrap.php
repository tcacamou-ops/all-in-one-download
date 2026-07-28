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

require_once __DIR__ . '/Support/wp-error-stub.php';

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}
