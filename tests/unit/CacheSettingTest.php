<?php
/**
 * Tests for wp_cache_setting() — writes typed values to the config file.
 * Dispatches to wp_cache_replace_line with correct PHP syntax for the value type.
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

// wp_cache_replace_line is already defined from ReplaceLineTest if run in suite,
// but we need it here for standalone runs too.
require_once __DIR__ . '/ReplaceLineTest.php';

if ( ! function_exists( 'wp_cache_setting' ) ) {
	function wp_cache_setting( $field, $value ) {
		global $wp_cache_config_file;

		$GLOBALS[ $field ] = $value;
		if ( is_numeric( $value ) ) {
			return wp_cache_replace_line( '^ *\$' . $field, "\$$field = $value;", $wp_cache_config_file );
		} elseif ( is_bool( $value ) ) {
			$output_value = $value === true ? 'true' : 'false';
			return wp_cache_replace_line( '^ *\$' . $field, "\$$field = $output_value;", $wp_cache_config_file );
		} elseif ( is_object( $value ) || is_array( $value ) ) {
			$text = var_export( $value, true );
			$text = preg_replace( '/[\s]+/', ' ', $text );
			return wp_cache_replace_line( '^ *\$' . $field, "\$$field = $text;", $wp_cache_config_file );
		} else {
			return wp_cache_replace_line( '^ *\$' . $field, "\$$field = '$value';", $wp_cache_config_file );
		}
	}
}

class CacheSettingTest extends TestCase {

	private string $config_file;
	private string $temp_dir;

	protected function set_up() {
		parent::set_up();
		$this->temp_dir = sys_get_temp_dir() . '/wpsc-setting-test-' . uniqid();
		mkdir( $this->temp_dir );
		$GLOBALS['cache_path'] = $this->temp_dir . '/';

		$this->config_file = $this->temp_dir . '/wp-cache-config.php';
		$GLOBALS['wp_cache_config_file'] = $this->config_file;
	}

	protected function tear_down() {
		$files = glob( $this->temp_dir . '/*' );
		if ( $files ) {
			array_map( 'unlink', $files );
		}
		rmdir( $this->temp_dir );
		unset( $GLOBALS['wp_cache_config_file'] );
		parent::tear_down();
	}

	private function write_config( string $content ): void {
		file_put_contents( $this->config_file, $content );
	}

	private function read_config(): string {
		return file_get_contents( $this->config_file );
	}

	// --- Numeric values ---

	public function test_writes_integer_value() {
		$this->write_config( "<?php\n\$cache_max_time = 3600;\n" );

		wp_cache_setting( 'cache_max_time', 7200 );

		$this->assertStringContainsString( '$cache_max_time = 7200;', $this->read_config() );
	}

	public function test_writes_zero() {
		$this->write_config( "<?php\n\$cache_enabled = 1;\n" );

		wp_cache_setting( 'cache_enabled', 0 );

		$this->assertStringContainsString( '$cache_enabled = 0;', $this->read_config() );
	}

	// --- Boolean values ---

	public function test_writes_true_boolean() {
		$this->write_config( "<?php\n\$some_flag = false;\n" );

		wp_cache_setting( 'some_flag', true );

		$this->assertStringContainsString( '$some_flag = true;', $this->read_config() );
	}

	public function test_writes_false_boolean() {
		$this->write_config( "<?php\n\$some_flag = true;\n" );

		wp_cache_setting( 'some_flag', false );

		$this->assertStringContainsString( '$some_flag = false;', $this->read_config() );
	}

	// --- String values ---

	public function test_writes_string_value() {
		$this->write_config( "<?php\n\$cache_path = '/old/path/';\n" );

		wp_cache_setting( 'cache_path', '/new/path/' );

		$this->assertStringContainsString( "\$cache_path = '/new/path/';", $this->read_config() );
	}

	// --- Array values ---

	public function test_writes_array_value() {
		$this->write_config( "<?php\n\$wp_cache_pages = array();\n" );

		wp_cache_setting( 'wp_cache_pages', array( 'search' => 1, 'feed' => 0 ) );

		$contents = $this->read_config();
		$this->assertStringContainsString( '$wp_cache_pages', $contents );
		$this->assertStringContainsString( "'search'", $contents );
	}

	// --- Sets the global ---

	public function test_sets_global_variable() {
		$this->write_config( "<?php\n\$my_setting = 0;\n" );

		wp_cache_setting( 'my_setting', 42 );

		$this->assertSame( 42, $GLOBALS['my_setting'] );
	}

	// --- Type priority: numeric strings are numeric, not string ---

	public function test_numeric_string_treated_as_numeric() {
		$this->write_config( "<?php\n\$cache_time = 100;\n" );

		wp_cache_setting( 'cache_time', '200' );

		// is_numeric('200') is true, so it should NOT be quoted
		$contents = $this->read_config();
		$this->assertStringContainsString( '$cache_time = 200;', $contents );
		$this->assertStringNotContainsString( "'200'", $contents );
	}
}
