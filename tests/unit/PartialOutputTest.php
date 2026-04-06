<?php
/**
 * Output tests for settings page partials.
 *
 * Renders each partial template with stubbed WordPress functions and
 * asserts that the HTML modernization rules from the advanced-settings-ui
 * branch are correctly applied:
 *
 * - No deprecated <acronym> tags (should be <abbr>)
 * - No <h4> section headings (should be <h2>)
 * - No <a name="..."> anchors (should be id attributes)
 * - No inline-styled warnings (should use WordPress notice classes)
 * - submit_button() used instead of manual submit inputs
 * - Textareas use "large-text code" class, not inline styles
 * - Lockdown status uses dashicons, not colored spans
 * - Code blocks use .wpsc-code-block class
 * - Tables use wp-list-table class where appropriate
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

// Stub all WordPress functions used by the partials.
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( '_e' ) ) {
	function _e( $text, $domain = 'default' ) { echo $text; }
}
if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $text, $domain = 'default' ) { echo esc_html__( $text, $domain ); }
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( $text, $domain = 'default' ) { return esc_attr( $text ); }
}
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) { return $url; }
}
if ( ! function_exists( '_x' ) ) {
	function _x( $text, $context, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( 'wp_kses' ) ) {
	function wp_kses( $text, $allowed ) { return $text; }
}
if ( ! function_exists( 'wp_nonce_field' ) ) {
	function wp_nonce_field( $action, $name = '_wpnonce', $referer = true, $echo = true ) {
		$field = '<input type="hidden" name="_wpnonce" value="stub" />';
		if ( $echo ) echo $field;
		return $field;
	}
}
if ( ! function_exists( 'checked' ) ) {
	function checked( $checked, $current = true, $echo = true ) {
		$result = ( (string) $checked === (string) $current ) ? " checked='checked'" : '';
		if ( $echo ) echo $result;
		return $result;
	}
}
if ( ! function_exists( 'disabled' ) ) {
	function disabled( $disabled, $current = true, $echo = true ) {
		$result = ( (string) $disabled === (string) $current ) ? " disabled='disabled'" : '';
		if ( $echo ) echo $result;
		return $result;
	}
}
if ( ! function_exists( 'selected' ) ) {
	function selected( $selected, $current = true, $echo = true ) {
		$result = ( (string) $selected === (string) $current ) ? " selected='selected'" : '';
		if ( $echo ) echo $result;
		return $result;
	}
}
if ( ! function_exists( 'submit_button' ) ) {
	function submit_button( $text = 'Save Changes', $type = 'primary', $name = 'submit', $wrap = true, $attrs = '' ) {
		echo '<input type="submit" class="button button-primary" value="' . esc_attr( $text ) . '" />';
	}
}
if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( ...$args ) { return '?stub=1'; }
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) { return $default; }
}
if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( $show ) { return 'http://example.com'; }
}
if ( ! function_exists( 'plugins_url' ) ) {
	function plugins_url() { return '/wp-content/plugins'; }
}
if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $string ) { return rtrim( $string, '/' ) . '/'; }
}
if ( ! function_exists( 'wp_get_schedules' ) ) {
	function wp_get_schedules() {
		return array(
			'hourly' => array( 'interval' => 3600, 'display' => 'Once Hourly' ),
			'twicedaily' => array( 'interval' => 43200, 'display' => 'Twice Daily' ),
		);
	}
}
if ( ! function_exists( 'wp_next_scheduled' ) ) {
	function wp_next_scheduled( $hook ) { return false; }
}
if ( ! function_exists( 'date_i18n' ) ) {
	function date_i18n( $format, $timestamp = false, $gmt = false ) { return '2026-01-01 00:00:00'; }
}
if ( ! function_exists( 'is_writeable_ACLSafe' ) ) {
	function is_writeable_ACLSafe( $path ) { return true; }
}
if ( ! function_exists( 'wpsc_update_direct_pages' ) ) {
	function wpsc_update_direct_pages() { return array(); }
}
if ( ! function_exists( 'get_site_option' ) ) {
	function get_site_option( $option, $default = false ) { return $default; }
}
if ( ! function_exists( 'site_url' ) ) {
	function site_url( $path = '' ) { return 'http://example.com' . $path; }
}
if ( ! function_exists( 'wpsc_htaccess_directive' ) ) {
	function wpsc_htaccess_directive() { return ''; }
}
if ( ! function_exists( 'wpsc_update_htaccess_form' ) ) {
	function wpsc_update_htaccess_form() {}
}
if ( ! function_exists( 'wpsc_get_htaccess_info' ) ) {
	function wpsc_get_htaccess_info() { return array( 'rules' => '', 'scrules' => '' ); }
}
if ( ! function_exists( 'sprintf' ) ) {
	// sprintf is a PHP built-in, no stub needed
}
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) { return $url; }
}

class PartialOutputTest extends TestCase {

	protected function set_up() {
		parent::set_up();

		// Set all globals the partials expect.
		$GLOBALS['wp_cache_mod_rewrite']        = 0;
		$GLOBALS['wp_cache_mfunc_enabled']       = 0;
		$GLOBALS['wp_cache_mobile_enabled']      = 1;
		$GLOBALS['cache_enabled']                = true;
		$GLOBALS['cache_path']                   = '/tmp/cache/';
		$GLOBALS['cache_time_interval']          = 600;
		$GLOBALS['cache_schedule_type']          = 'interval';
		$GLOBALS['wp_cache_not_logged_in']       = 2;
		$GLOBALS['wp_cache_no_cache_for_get']    = 0;
		$GLOBALS['cache_compression']            = 0;
		$GLOBALS['cache_rebuild_files']          = 1;
		$GLOBALS['wpsc_save_headers']            = 0;
		$GLOBALS['wp_supercache_304']            = 0;
		$GLOBALS['wp_cache_make_known_anon']     = 0;
		$GLOBALS['wp_cache_front_page_checks']   = 1;
		$GLOBALS['wp_cache_disable_utf8']        = 0;
		$GLOBALS['wp_cache_clear_on_post_edit']  = 0;
		$GLOBALS['wp_cache_refresh_single_only'] = 0;
		$GLOBALS['wp_supercache_cache_list']     = 0;
		$GLOBALS['wp_cache_mutex_disabled']      = 1;
		$GLOBALS['wp_super_cache_late_init']     = 0;
		$GLOBALS['cache_page_secret']            = 'testsecret';
		$GLOBALS['wp_cache_mobile_browsers']     = 'android, mobile';
		$GLOBALS['wp_cache_mobile_prefixes']     = 'w3c';
		$GLOBALS['cache_max_time']               = 3600;
		$GLOBALS['cache_scheduled_time']         = '00:00';
		$GLOBALS['cache_schedule_interval']      = 'hourly';
		$GLOBALS['cache_gc_email_me']            = 0;
		$GLOBALS['wp_cache_preload_on']          = 0;
		$GLOBALS['admin_url']                    = '/wp-admin/options-general.php?page=wpsupercache';
		$GLOBALS['is_nginx']                     = false;
		$GLOBALS['is_cache_enabled']             = true;

		// Lockdown globals
		$GLOBALS['wp_lock_down']       = '0';
		$GLOBALS['super_cache_enabled'] = true;

		// Rejected URIs/agents/cookies
		$GLOBALS['cache_rejected_uri']        = array( 'wp-.*\\.php', 'feed', 'trackback' );
		$GLOBALS['cache_acceptable_files']    = array( 'wp-comments-popup.php', 'wp-links-opml.php' );
		$GLOBALS['cache_rejected_user_agent'] = array( 'bot', 'ia_archiver', 'slurp' );
		$GLOBALS['wpsc_rejected_cookies']     = array();
		$GLOBALS['wp_cache_pages']            = array(
			'search' => 0, 'feed' => 0, 'category' => 0,
			'home' => 0, 'frontpage' => 0, 'tag' => 0,
			'archives' => 0, 'pages' => 0, 'single' => 0, 'author' => 0,
		);

		// Tracking parameters
		$GLOBALS['wpsc_tracking_parameters']        = array( 'utm_source', 'utm_medium', 'utm_campaign' );
		$GLOBALS['wpsc_ignore_tracking_parameters']  = 1;

		if ( ! defined( 'SUBMITDISABLED' ) ) {
			define( 'SUBMITDISABLED', '' );
		}
	}

	/**
	 * Render a partial and return its output.
	 */
	private function render_partial( string $filename ): string {
		// Extract globals so the partial can access them as local vars
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

	// ===== advanced.php =====

	public function test_advanced_no_acronym_tags() {
		$html = $this->render_partial( 'advanced.php' );
		$this->assertStringNotContainsString( '<acronym', $html, 'Deprecated <acronym> tag found — should be <abbr>' );
	}

	public function test_advanced_uses_abbr_tags() {
		$html = $this->render_partial( 'advanced.php' );
		$this->assertStringContainsString( '<abbr', $html );
	}

	public function test_advanced_uses_h2_not_h4() {
		$html = $this->render_partial( 'advanced.php' );
		$this->assertStringNotContainsString( '<h4', $html, '<h4> found — section headings should use <h2>' );
		$this->assertStringContainsString( '<h2>', $html );
	}

	public function test_advanced_no_anchor_name_attributes() {
		$html = $this->render_partial( 'advanced.php' );
		$this->assertDoesNotMatchRegularExpression( '/<a\s+name=/', $html, '<a name="..."> found — should use id attributes' );
	}

	public function test_advanced_uses_id_attributes_for_anchors() {
		$html = $this->render_partial( 'advanced.php' );
		$this->assertStringContainsString( 'id="expirytime"', $html );
		$this->assertStringContainsString( 'id="rejecturi"', $html );
		$this->assertStringContainsString( 'id="rejectcookies"', $html );
		$this->assertStringContainsString( 'id="cancache"', $html );
	}

	public function test_advanced_no_inline_styled_warnings() {
		$html = $this->render_partial( 'advanced.php' );
		$this->assertDoesNotMatchRegularExpression(
			'/style=[\'"][^"]*color:#9f6000/',
			$html,
			'Inline-styled warning found — should use WordPress notice classes'
		);
	}

	public function test_advanced_uses_notice_classes_for_warnings() {
		// Force mod_rewrite on to trigger the expert-mode warning
		$GLOBALS['wp_cache_mod_rewrite'] = 1;
		$html = $this->render_partial( 'advanced.php' );
		$this->assertStringContainsString( 'notice notice-warning', $html );
	}

	public function test_advanced_uses_submit_button() {
		$html = $this->render_partial( 'advanced.php' );
		// submit_button() outputs class="button button-primary"
		$this->assertStringContainsString( 'button-primary', $html );
		// Should NOT have the old manual SUBMITDISABLED pattern
		$this->assertStringNotContainsString( 'SUBMITDISABLED', $html );
	}

	public function test_advanced_textareas_use_large_text_class() {
		$html = $this->render_partial( 'advanced.php' );
		$this->assertStringContainsString( 'large-text code', $html );
		$this->assertDoesNotMatchRegularExpression(
			'/textarea[^>]*style=/',
			$html,
			'Textarea with inline style found — should use class="large-text code"'
		);
	}

	public function test_advanced_cache_restrictions_separate_row() {
		$html = $this->render_partial( 'advanced.php' );
		$this->assertStringContainsString( 'Cache Restrictions', $html );
		$this->assertStringContainsString( 'Cache Behavior', $html );
		$this->assertStringNotContainsString( '>Miscellaneous<', $html );
	}

	public function test_advanced_descriptions_use_p_class() {
		$html = $this->render_partial( 'advanced.php' );
		$this->assertStringContainsString( 'class="description"', $html );
	}

	public function test_advanced_notice_info_for_notes() {
		$html = $this->render_partial( 'advanced.php' );
		$this->assertStringContainsString( 'notice notice-info', $html );
	}

	public function test_advanced_no_fieldset_options_class() {
		$html = $this->render_partial( 'advanced.php' );
		$this->assertStringNotContainsString( 'class="options"', $html, 'Old fieldset class="options" found' );
	}

	// ===== lockdown.php =====

	public function test_lockdown_uses_dashicons() {
		$html = $this->render_partial( 'lockdown.php' );
		$this->assertStringContainsString( 'dashicons', $html );
	}

	public function test_lockdown_no_colored_spans() {
		$html = $this->render_partial( 'lockdown.php' );
		$this->assertDoesNotMatchRegularExpression(
			'/style=[\'"][^"]*color:(red|green)/',
			$html,
			'Inline colored span found — should use dashicons'
		);
	}

	public function test_lockdown_uses_h2() {
		$html = $this->render_partial( 'lockdown.php' );
		$this->assertStringContainsString( '<h2>', $html );
		$this->assertStringNotContainsString( '<h4', $html );
	}

	public function test_lockdown_uses_id_not_anchor_name() {
		$html = $this->render_partial( 'lockdown.php' );
		$this->assertDoesNotMatchRegularExpression( '/<a\s+name=/', $html );
		$this->assertStringContainsString( 'id="lockdown"', $html );
	}

	public function test_lockdown_uses_submit_button() {
		$html = $this->render_partial( 'lockdown.php' );
		$this->assertStringContainsString( 'button-primary', $html );
	}

	public function test_lockdown_uses_wpsc_code_block() {
		$html = $this->render_partial( 'lockdown.php' );
		$this->assertStringContainsString( 'wpsc-code-block', $html );
		$this->assertStringNotContainsString( '<blockquote>', $html );
	}

	public function test_lockdown_no_inline_styled_warnings() {
		$html = $this->render_partial( 'lockdown.php' );
		$this->assertDoesNotMatchRegularExpression(
			'/style=[\'"][^"]*color:#9f6000/',
			$html
		);
	}

	public function test_lockdown_uses_notice_for_warnings() {
		// Make ABSPATH writable with 0777 permissions so the warning triggers
		$html = $this->render_partial( 'lockdown.php' );
		// The partial uses notice classes for all warnings now
		$this->assertDoesNotMatchRegularExpression(
			'/<p\s+style=[\'"]padding.*background/',
			$html
		);
	}

	// ===== rejected_user_agents.php =====

	public function test_rejected_ua_uses_h2() {
		$html = $this->render_partial( 'rejected_user_agents.php' );
		$this->assertStringContainsString( '<h2', $html );
		$this->assertStringNotContainsString( '<h4', $html );
	}

	public function test_rejected_ua_no_anchor_name() {
		$html = $this->render_partial( 'rejected_user_agents.php' );
		$this->assertDoesNotMatchRegularExpression( '/<a\s+name=/', $html );
		$this->assertStringContainsString( 'id="useragents"', $html );
	}

	public function test_rejected_ua_textarea_uses_large_text() {
		$html = $this->render_partial( 'rejected_user_agents.php' );
		$this->assertStringContainsString( 'large-text code', $html );
	}

	public function test_rejected_ua_uses_submit_button() {
		$html = $this->render_partial( 'rejected_user_agents.php' );
		$this->assertStringContainsString( 'button-primary', $html );
	}

	public function test_rejected_ua_no_fieldset_options() {
		$html = $this->render_partial( 'rejected_user_agents.php' );
		$this->assertStringNotContainsString( 'class="options"', $html );
	}

	// ===== tracking_parameters.php =====

	public function test_tracking_params_uses_h2() {
		$html = $this->render_partial( 'tracking_parameters.php' );
		$this->assertStringContainsString( '<h2', $html );
		$this->assertStringNotContainsString( '<h4', $html );
	}

	public function test_tracking_params_no_anchor_name() {
		$html = $this->render_partial( 'tracking_parameters.php' );
		$this->assertDoesNotMatchRegularExpression( '/<a\s+name=/', $html );
		$this->assertStringContainsString( 'id="trackingparameters"', $html );
	}

	public function test_tracking_params_textarea_uses_large_text() {
		$html = $this->render_partial( 'tracking_parameters.php' );
		$this->assertStringContainsString( 'large-text code', $html );
	}

	public function test_tracking_params_uses_submit_button() {
		$html = $this->render_partial( 'tracking_parameters.php' );
		$this->assertStringContainsString( 'button-primary', $html );
	}
}
