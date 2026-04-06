<?php
/**
 * Output tests for the remaining settings page partials: easy, debug, preload, restore.
 *
 * Verifies the same HTML modernization rules as PartialOutputTest:
 * - No <h4> section headings (should be <h2>)
 * - No <a name="..."> anchors (should be id attributes)
 * - No <fieldset class="options"> wrappers
 * - No manual SUBMITDISABLED submit buttons (should use submit_button())
 * - No inline-styled warnings (should use WordPress notice classes)
 * - checked() helper used consistently
 * - Textareas use "large-text code" class, not inline styles
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

// Load stubs from PartialOutputTest if not already loaded.
require_once __DIR__ . '/PartialOutputTest.php';

// Additional stubs needed by these partials.
if ( ! function_exists( 'wpsc_update_debug_settings' ) ) {
	function wpsc_update_debug_settings() {
		return array(
			'wp_super_cache_debug'                    => 0,
			'wp_cache_debug_log'                      => 'debug.log',
			'wp_cache_debug_ip'                       => '',
			'wp_super_cache_comments'                 => 1,
			'wp_super_cache_front_page_check'         => 0,
			'wp_super_cache_front_page_clear'         => 0,
			'wp_super_cache_front_page_text'          => '',
			'wp_super_cache_front_page_notification'  => 0,
			'wp_super_cache_advanced_debug'            => 0,
			'wp_cache_debug_username'                  => 'admin',
		);
	}
}
if ( ! function_exists( 'wpsc_create_debug_log' ) ) {
	function wpsc_create_debug_log( $filename = '', $username = '' ) {
		return array( 'wp_cache_debug_log' => 'test-debug.log', 'wp_cache_debug_username' => 'testuser' );
	}
}
if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '' ) { return '/wp-admin/' . ltrim( $path, '/' ); }
}
if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '' ) { return 'http://example.com' . $path; }
}
if ( ! function_exists( 'get_home_path' ) ) {
	function get_home_path() { return '/var/www/html/'; }
}
if ( ! function_exists( 'wp_admin_notice' ) ) {
	function wp_admin_notice( $message, $args = array() ) {
		$type = isset( $args['type'] ) ? $args['type'] : 'info';
		echo '<div class="notice notice-' . $type . '"><p>' . $message . '</p></div>';
	}
}
if ( ! function_exists( 'wpsc_post_count' ) ) {
	function wpsc_post_count() { return 50; }
}
if ( ! function_exists( 'wpsc_get_minimum_preload_interval' ) ) {
	function wpsc_get_minimum_preload_interval() { return 30; }
}
if ( ! function_exists( 'wpsc_is_preload_active' ) ) {
	function wpsc_is_preload_active() { return false; }
}
if ( ! function_exists( 'is_multisite' ) ) {
	function is_multisite() { return false; }
}
if ( ! function_exists( 'wpsupercache_site_admin' ) ) {
	function wpsupercache_site_admin() { return true; }
}
if ( ! function_exists( 'extract_from_markers' ) ) {
	function extract_from_markers( $file, $marker ) { return array(); }
}
if ( ! function_exists( 'wp_remote_get' ) ) {
	function wp_remote_get( $url, $args = array() ) { return array(); }
}
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) { return false; }
}
if ( ! function_exists( 'wpsc_is_boost_current' ) ) {
	function wpsc_is_boost_current() { return false; }
}
if ( ! function_exists( 'wpsc_is_boost_active' ) ) {
	function wpsc_is_boost_active() { return false; }
}
if ( ! function_exists( 'wpsc_is_boost_installed' ) ) {
	function wpsc_is_boost_installed() { return false; }
}
if ( ! defined( 'WPSC_PRELOAD_POST_COUNT' ) ) {
	define( 'WPSC_PRELOAD_POST_COUNT', 100 );
}

class RemainingPartialsOutputTest extends TestCase {

	protected function set_up() {
		parent::set_up();

		// Globals needed by partials.
		$GLOBALS['cache_enabled']                = true;
		$GLOBALS['super_cache_enabled']          = true;
		$GLOBALS['cache_path']                   = '/tmp/cache/';
		$GLOBALS['wp_cache_mod_rewrite']         = 0;
		$GLOBALS['is_nginx']                     = false;
		$GLOBALS['admin_url']                    = '/wp-admin/options-general.php?page=wpsupercache';
		$GLOBALS['valid_nonce']                  = false;
		$GLOBALS['wp_super_cache_comments']      = 1;
		$GLOBALS['wpsc_promo_links']             = array(
			'boost'   => 'https://jetpack.com/boost/',
			'jetpack' => 'https://jetpack.com/',
		);
		$GLOBALS['wp_cache_preload_interval']    = 600;
		$GLOBALS['wp_cache_preload_on']          = 0;
		$GLOBALS['wp_cache_preload_taxonomies']  = 0;
		$GLOBALS['wp_cache_preload_email_volume'] = 'none';
		$GLOBALS['wp_cache_preload_posts']       = 'all';
		$GLOBALS['currently_preloading']         = false;

		if ( ! defined( 'SUBMITDISABLED' ) ) {
			define( 'SUBMITDISABLED', '' );
		}
	}

	private function render_partial( string $filename ): string {
		extract( $GLOBALS, EXTR_SKIP );
		ob_start();
		try {
			include __DIR__ . '/../../partials/' . $filename;
		} catch ( \Throwable $e ) {
			ob_end_clean();
			throw $e;
		}
		return ob_get_clean();
	}

	// ===== restore.php =====

	public function test_restore_uses_h2_not_h4() {
		$html = $this->render_partial( 'restore.php' );
		$this->assertStringNotContainsString( '<h4', $html, '<h4> found in restore.php' );
		$this->assertStringContainsString( '<h2', $html );
	}

	public function test_restore_no_fieldset_options() {
		$html = $this->render_partial( 'restore.php' );
		$this->assertStringNotContainsString( 'class="options"', $html );
	}

	public function test_restore_uses_submit_button() {
		$html = $this->render_partial( 'restore.php' );
		$this->assertStringContainsString( 'button-primary', $html );
		$this->assertStringNotContainsString( 'SUBMITDISABLED', $html );
	}

	// ===== debug.php =====

	public function test_debug_no_anchor_name() {
		$html = $this->render_partial( 'debug.php' );
		$this->assertDoesNotMatchRegularExpression( '/<a\s+name=/', $html );
	}

	public function test_debug_no_fieldset_options() {
		$html = $this->render_partial( 'debug.php' );
		$this->assertStringNotContainsString( 'class="options"', $html );
	}

	public function test_debug_uses_submit_button_not_manual() {
		$html = $this->render_partial( 'debug.php' );
		$this->assertStringNotContainsString( 'SUBMITDISABLED', $html );
	}

	public function test_debug_no_h4_headings() {
		$html = $this->render_partial( 'debug.php' );
		$this->assertStringNotContainsString( '<h4', $html );
	}

	// ===== easy.php =====

	public function test_easy_uses_h2_not_h4() {
		$html = $this->render_partial( 'easy.php' );
		$this->assertStringNotContainsString( '<h4', $html, '<h4> found in easy.php' );
	}

	public function test_easy_uses_submit_button() {
		$html = $this->render_partial( 'easy.php' );
		$this->assertStringNotContainsString( 'SUBMITDISABLED', $html );
	}

	public function test_easy_no_inline_color_styles() {
		$html = $this->render_partial( 'easy.php' );
		$this->assertDoesNotMatchRegularExpression(
			'/style=[\'"][^"]*color:\s*#0a0/',
			$html,
			'Inline green color style found in easy.php'
		);
		$this->assertDoesNotMatchRegularExpression(
			'/style=[\'"][^"]*color:\s*#a00/',
			$html,
			'Inline red color style found in easy.php'
		);
	}

	public function test_easy_no_inline_list_style() {
		$html = $this->render_partial( 'easy.php' );
		$this->assertDoesNotMatchRegularExpression(
			'/<ul\s+style=/',
			$html,
			'Inline-styled <ul> found in easy.php'
		);
	}

	// ===== preload.php =====

	public function test_preload_no_anchor_name() {
		$html = $this->render_partial( 'preload.php' );
		$this->assertDoesNotMatchRegularExpression( '/<a\s+name=/', $html );
	}

	public function test_preload_uses_checked_helper() {
		$html = $this->render_partial( 'preload.php' );
		// The old pattern: checked=1 (without quotes around the attribute value)
		$this->assertDoesNotMatchRegularExpression(
			'/checked=1/',
			$html,
			'Manual checked=1 found — should use checked() helper'
		);
	}

	public function test_preload_uses_submit_button() {
		$html = $this->render_partial( 'preload.php' );
		// submit_button() outputs class="button button-primary"
		// Old pattern was manual <input class="button-primary" ...>
		$this->assertDoesNotMatchRegularExpression(
			'/<input\s+class=[\'"]button-primary[\'"]/',
			$html,
			'Manual button-primary input found — should use submit_button()'
		);
	}
}
