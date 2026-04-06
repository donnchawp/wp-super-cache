<?php
/**
 * Tests for wpsc_deep_replace() — a recursive string replacement function
 * used to sanitize file paths (removes ".." and "\" sequences).
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

// wpsc_deep_replace is a standalone function with no WordPress dependencies.
// We can safely require just the function definition.
if ( ! function_exists( 'wpsc_deep_replace' ) ) {
	// Define it inline from the source to avoid loading the entire phase2 file.
	function wpsc_deep_replace( $search, $subject ) {
		$subject = (string) $subject;
		$count = 1;
		while ( $count ) {
			$subject = str_replace( $search, '', $subject, $count );
		}
		return $subject;
	}
}

class DeepReplaceTest extends TestCase {

	public function test_removes_double_dots() {
		$result = wpsc_deep_replace( array( '..' ), 'foo/../bar' );
		$this->assertSame( 'foo//bar', $result );
	}

	public function test_removes_backslashes() {
		$result = wpsc_deep_replace( array( '\\' ), 'foo\\bar' );
		$this->assertSame( 'foobar', $result );
	}

	public function test_removes_both_traversal_patterns() {
		$result = wpsc_deep_replace( array( '..', '\\' ), '..\\..\\etc\\passwd' );
		$this->assertSame( 'etcpasswd', $result );
	}

	public function test_handles_nested_traversal() {
		// "....", after removing "..", becomes ".." which must also be removed
		$result = wpsc_deep_replace( array( '..' ), 'foo/..../bar' );
		$this->assertSame( 'foo//bar', $result );
	}

	public function test_no_op_on_clean_string() {
		$result = wpsc_deep_replace( array( '..', '\\' ), 'clean/path/here' );
		$this->assertSame( 'clean/path/here', $result );
	}

	public function test_empty_string() {
		$result = wpsc_deep_replace( array( '..' ), '' );
		$this->assertSame( '', $result );
	}

	public function test_casts_non_string_to_string() {
		$result = wpsc_deep_replace( array( '..' ), 12345 );
		$this->assertSame( '12345', $result );
	}
}
