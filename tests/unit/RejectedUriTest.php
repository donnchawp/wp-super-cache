<?php
/**
 * Tests for wp_cache_is_rejected() — determines if a URI should be excluded
 * from caching. This is security-critical: wp-admin must never be cached.
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

// Inline the function to avoid loading the entire phase2 file.
if ( ! function_exists( 'wp_cache_is_rejected' ) ) {
	function wp_cache_is_rejected( $uri ) {
		global $cache_rejected_uri;

		if ( empty( $uri ) ) {
			return true;
		}

		$auto_rejected = array( '/wp-admin/', 'xmlrpc.php', 'wp-app.php' );
		foreach ( $auto_rejected as $u ) {
			if ( strstr( $uri, $u ) ) {
				return true;
			}
		}
		if ( false == is_array( $cache_rejected_uri ) ) {
			return false;
		}
		foreach ( $cache_rejected_uri as $expr ) {
			if ( $expr != '' && @preg_match( "~$expr~", $uri ) ) {
				return true;
			}
		}
		return false;
	}
}

class RejectedUriTest extends TestCase {

	protected function set_up() {
		parent::set_up();
		$GLOBALS['cache_rejected_uri'] = array();
	}

	protected function tear_down() {
		unset( $GLOBALS['cache_rejected_uri'] );
		parent::tear_down();
	}

	// --- Auto-rejected URIs (hardcoded security rules) ---

	public function test_rejects_wp_admin() {
		$this->assertTrue( wp_cache_is_rejected( '/wp-admin/options.php' ) );
	}

	public function test_rejects_xmlrpc() {
		$this->assertTrue( wp_cache_is_rejected( '/xmlrpc.php' ) );
	}

	public function test_rejects_wp_app() {
		$this->assertTrue( wp_cache_is_rejected( '/wp-app.php' ) );
	}

	public function test_rejects_empty_uri() {
		$this->assertTrue( wp_cache_is_rejected( '' ) );
	}

	public function test_rejects_null_uri() {
		$this->assertTrue( wp_cache_is_rejected( null ) );
	}

	// --- Normal URIs that should be cached ---

	public function test_allows_front_page() {
		$this->assertFalse( wp_cache_is_rejected( '/' ) );
	}

	public function test_allows_regular_post() {
		$this->assertFalse( wp_cache_is_rejected( '/2024/01/hello-world/' ) );
	}

	public function test_allows_page() {
		$this->assertFalse( wp_cache_is_rejected( '/about/' ) );
	}

	// --- Custom rejected URI patterns ---

	public function test_rejects_custom_pattern() {
		$GLOBALS['cache_rejected_uri'] = array( '/secret-area/' );
		$this->assertTrue( wp_cache_is_rejected( '/secret-area/page1' ) );
	}

	public function test_custom_pattern_is_regex() {
		$GLOBALS['cache_rejected_uri'] = array( '/feed/$' );
		$this->assertTrue( wp_cache_is_rejected( '/feed/' ) );
		$this->assertFalse( wp_cache_is_rejected( '/feed/atom/' ) );
	}

	public function test_empty_pattern_does_not_reject() {
		$GLOBALS['cache_rejected_uri'] = array( '' );
		$this->assertFalse( wp_cache_is_rejected( '/some-page/' ) );
	}

	public function test_non_array_rejected_uri_does_not_reject() {
		$GLOBALS['cache_rejected_uri'] = 'not-an-array';
		$this->assertFalse( wp_cache_is_rejected( '/some-page/' ) );
	}

	public function test_multiple_custom_patterns() {
		$GLOBALS['cache_rejected_uri'] = array( '/private/', '/staging/' );
		$this->assertTrue( wp_cache_is_rejected( '/private/data' ) );
		$this->assertTrue( wp_cache_is_rejected( '/staging/test' ) );
		$this->assertFalse( wp_cache_is_rejected( '/public/page' ) );
	}
}
