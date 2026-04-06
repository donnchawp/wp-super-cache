<?php
/**
 * Minimal stubs for WordPress functions/classes used in testable code.
 * Only add stubs here when a test actually needs them.
 */

// WordPress core stubs
if ( ! class_exists( 'WP_REST_Controller' ) ) {
	class WP_REST_Controller {}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {}
}

if ( ! function_exists( 'rest_ensure_response' ) ) {
	function rest_ensure_response( $data ) {
		return $data;
	}
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() . '/wpsc-tests/' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}
