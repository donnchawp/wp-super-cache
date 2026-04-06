<?php
/**
 * Tests for wp_cache_replace_line() — the core function that modifies
 * the wp-cache-config.php settings file. This is the single most important
 * function to test: it handles atomic file writes, pattern matching,
 * and line insertion.
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

// Stub dependencies that wp_cache_replace_line needs.
if ( ! function_exists( 'is_writeable_ACLSafe' ) ) {
	function is_writeable_ACLSafe( $path ) {
		return is_writable( $path );
	}
}

if ( ! function_exists( 'wp_cache_debug' ) ) {
	function wp_cache_debug( $message, $level = 1 ) {}
}

if ( ! function_exists( 'wp_rand' ) ) {
	function wp_rand( $min = 0, $max = 0 ) {
		return random_int( $min, $max );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

// Now define the function under test, copied from wp-cache-phase2.php.
// This avoids loading the entire file with its side effects.
if ( ! function_exists( 'wp_cache_replace_line' ) ) {
	function wp_cache_replace_line( $old, $new, $my_file ) {
		if ( ! is_string( $my_file ) || @is_file( $my_file ) === false ) {
			if ( function_exists( 'set_transient' ) ) {
				set_transient( 'wpsc_config_error', 'config_file_missing', 10 );
			}
			return false;
		}
		if ( ! is_writeable_ACLSafe( $my_file ) ) {
			if ( function_exists( 'set_transient' ) ) {
				set_transient( 'wpsc_config_error', 'config_file_ro', 10 );
			}
			trigger_error( "Error: file $my_file is not writable." );
			return false;
		}

		$found  = false;
		$loaded = false;
		$c      = 0;
		$lines  = array();
		while ( ! $loaded ) {
			$lines = file( $my_file );
			if ( ! empty( $lines ) && is_array( $lines ) ) {
				$loaded = true;
			} else {
				++$c;
				if ( $c > 100 ) {
					if ( function_exists( 'set_transient' ) ) {
						set_transient( 'wpsc_config_error', 'config_file_not_loaded', 10 );
					}
					trigger_error( "wp_cache_replace_line: Error  - file $my_file could not be loaded." );
					return false;
				}
			}
		}
		foreach ( (array) $lines as $line ) {
			if (
				trim( $new ) != '' &&
				trim( $new ) == trim( $line )
			) {
				wp_cache_debug( "wp_cache_replace_line: setting not changed - $new" );
				return true;
			} elseif ( preg_match( "/$old/", $line ) ) {
				wp_cache_debug( 'wp_cache_replace_line: changing line ' . trim( $line ) . " to *$new*" );
				$found = true;
			}
		}

		$tmp_config_filename = tempnam( $GLOBALS['cache_path'], md5( (string) wp_rand( 0, 9999 ) ) );
		if ( file_exists( $tmp_config_filename . '.php' ) ) {
			unlink( $tmp_config_filename . '.php' );
			if ( file_exists( $tmp_config_filename . '.php' ) ) {
				die( __( 'WARNING: attempt to intercept updating of config file.', 'wp-super-cache' ) );
			}
		}
		rename( $tmp_config_filename, $tmp_config_filename . '.php' );
		$tmp_config_filename .= '.php';
		$fd = fopen( $tmp_config_filename, 'w' );
		if ( ! $fd ) {
			if ( function_exists( 'set_transient' ) ) {
				set_transient( 'wpsc_config_error', 'config_file_ro', 10 );
			}
			trigger_error( "wp_cache_replace_line: Error  - could not write to $my_file" );
			return false;
		}
		if ( $found ) {
			foreach ( (array) $lines as $line ) {
				if ( ! preg_match( "/$old/", $line ) ) {
					fwrite( $fd, $line );
				} elseif ( $new != '' ) {
					fwrite( $fd, "$new\n" );
				}
			}
		} else {
			$done = false;
			foreach ( (array) $lines as $line ) {
				if ( $done || ! preg_match( '/^(if\ \(\ \!\ )?define|\$|\?>/', $line ) ) {
					fwrite( $fd, $line );
				} else {
					fwrite( $fd, "$new\n" );
					fwrite( $fd, $line );
					$done = true;
				}
			}
		}
		fclose( $fd );

		$my_file_permissions = fileperms( $my_file );
		rename( $tmp_config_filename, $my_file );
		if ( false !== $my_file_permissions ) {
			chmod( $my_file, $my_file_permissions );
		}

		if ( function_exists( 'opcache_invalidate' ) ) {
			@opcache_invalidate( $my_file );
		}

		return true;
	}
}

class ReplaceLineTest extends TestCase {

	private string $config_file;
	private string $temp_dir;

	protected function set_up() {
		parent::set_up();
		$this->temp_dir = sys_get_temp_dir() . '/wpsc-test-' . uniqid();
		mkdir( $this->temp_dir );
		$GLOBALS['cache_path'] = $this->temp_dir . '/';
		$this->config_file     = $this->temp_dir . '/wp-cache-config.php';
	}

	protected function tear_down() {
		// Clean up temp files.
		$files = glob( $this->temp_dir . '/*' );
		if ( $files ) {
			array_map( 'unlink', $files );
		}
		rmdir( $this->temp_dir );
		parent::tear_down();
	}

	private function write_config( string $content ): void {
		file_put_contents( $this->config_file, $content );
	}

	private function read_config(): string {
		return file_get_contents( $this->config_file );
	}

	// --- Replacing existing lines ---

	public function test_replaces_matching_line() {
		$this->write_config( "<?php\n\$cache_enabled = 0;\n\$other = 1;\n" );

		$result = wp_cache_replace_line(
			'^ *\$cache_enabled',
			'$cache_enabled = 1;',
			$this->config_file
		);

		$this->assertTrue( $result );
		$contents = $this->read_config();
		$this->assertStringContainsString( '$cache_enabled = 1;', $contents );
		$this->assertStringNotContainsString( '$cache_enabled = 0;', $contents );
		$this->assertStringContainsString( '$other = 1;', $contents );
	}

	public function test_preserves_other_lines() {
		$this->write_config( "<?php\n\$a = 1;\n\$b = 2;\n\$c = 3;\n" );

		wp_cache_replace_line( '^ *\$b', '$b = 99;', $this->config_file );

		$contents = $this->read_config();
		$this->assertStringContainsString( '$a = 1;', $contents );
		$this->assertStringContainsString( '$b = 99;', $contents );
		$this->assertStringContainsString( '$c = 3;', $contents );
	}

	public function test_removes_line_when_new_is_empty() {
		$this->write_config( "<?php\n\$keep = 1;\n\$remove_me = 1;\n\$also_keep = 1;\n" );

		wp_cache_replace_line( '^ *\$remove_me', '', $this->config_file );

		$contents = $this->read_config();
		$this->assertStringContainsString( '$keep = 1;', $contents );
		$this->assertStringContainsString( '$also_keep = 1;', $contents );
		$this->assertStringNotContainsString( 'remove_me', $contents );
	}

	// --- Inserting new lines (pattern not found) ---

	public function test_inserts_new_line_before_first_variable() {
		$this->write_config( "<?php\n\$existing = 1;\n" );

		wp_cache_replace_line(
			'^ *\$brand_new_setting',
			'$brand_new_setting = 42;',
			$this->config_file
		);

		$contents = $this->read_config();
		$this->assertStringContainsString( '$brand_new_setting = 42;', $contents );
		$this->assertStringContainsString( '$existing = 1;', $contents );
	}

	// --- No-op when value unchanged ---

	public function test_returns_true_when_value_unchanged() {
		$this->write_config( "<?php\n\$cache_enabled = 1;\n" );

		$result = wp_cache_replace_line(
			'^ *\$cache_enabled',
			'$cache_enabled = 1;',
			$this->config_file
		);

		$this->assertTrue( $result );
	}

	// --- Error handling ---

	public function test_returns_false_for_nonexistent_file() {
		$result = wp_cache_replace_line(
			'^ *\$foo',
			'$foo = 1;',
			'/nonexistent/path/config.php'
		);

		$this->assertFalse( $result );
	}

	public function test_returns_false_for_non_string_file() {
		$result = wp_cache_replace_line(
			'^ *\$foo',
			'$foo = 1;',
			null
		);

		$this->assertFalse( $result );
	}

	// --- Real-world config file scenarios ---

	public function test_updates_cache_max_time_in_realistic_config() {
		$config = <<<'PHP'
<?php
$cache_enabled = true;
$cache_max_time = 3600;
$cache_path = '/var/www/cache/';
$wp_cache_mod_rewrite = 0;
PHP;
		$this->write_config( $config );

		wp_cache_replace_line(
			'^ *\$cache_max_time',
			'$cache_max_time = 7200;',
			$this->config_file
		);

		$contents = $this->read_config();
		$this->assertStringContainsString( '$cache_max_time = 7200;', $contents );
		$this->assertStringNotContainsString( '$cache_max_time = 3600;', $contents );
		$this->assertStringContainsString( '$cache_enabled = true;', $contents );
		$this->assertStringContainsString( '$wp_cache_mod_rewrite = 0;', $contents );
	}
}
