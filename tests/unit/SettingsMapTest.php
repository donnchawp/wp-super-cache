<?php

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

require_once __DIR__ . '/../../rest/class.wp-super-cache-settings-map.php';

class SettingsMapTest extends TestCase {

	/**
	 * Every entry in the settings map must define at least one accessor:
	 * 'get', 'set', 'global', or 'option'.
	 */
	public function test_every_entry_has_at_least_one_accessor() {
		$valid_keys = array( 'get', 'set', 'global', 'option' );

		foreach ( WP_Super_Cache_Settings_Map::$map as $name => $entry ) {
			$has_accessor = false;
			foreach ( $valid_keys as $key ) {
				if ( ! empty( $entry[ $key ] ) ) {
					$has_accessor = true;
					break;
				}
			}
			$this->assertTrue( $has_accessor, "Settings map entry '$name' has no accessor (get/set/global/option)." );
		}
	}

	/**
	 * Core cache settings must be present in the map.
	 */
	public function test_core_settings_exist() {
		$required = array(
			'is_cache_enabled',
			'is_super_cache_enabled',
			'cache_type',
			'cache_compression',
			'cache_max_time',
			'cache_path',
			'is_mobile_enabled',
			'dont_cache_logged_in',
			'preload_interval',
			'preload_on',
		);

		foreach ( $required as $setting ) {
			$this->assertArrayHasKey(
				$setting,
				WP_Super_Cache_Settings_Map::$map,
				"Required setting '$setting' missing from settings map."
			);
		}
	}

	/**
	 * Settings with a 'global' key should reference a non-empty string.
	 */
	public function test_global_references_are_non_empty_strings() {
		foreach ( WP_Super_Cache_Settings_Map::$map as $name => $entry ) {
			if ( isset( $entry['global'] ) ) {
				$this->assertIsString( $entry['global'], "Settings map '$name' global must be a string." );
				$this->assertNotEmpty( $entry['global'], "Settings map '$name' global must not be empty." );
			}
		}
	}

	/**
	 * Settings with a 'get' key should reference a non-empty string (method/function name).
	 */
	public function test_getter_references_are_non_empty_strings() {
		foreach ( WP_Super_Cache_Settings_Map::$map as $name => $entry ) {
			if ( isset( $entry['get'] ) ) {
				$this->assertIsString( $entry['get'], "Settings map '$name' get must be a string." );
				$this->assertNotEmpty( $entry['get'], "Settings map '$name' get must not be empty." );
			}
		}
	}

	/**
	 * No two map entries should reference the same global variable.
	 */
	public function test_no_duplicate_global_references() {
		$globals = array();
		foreach ( WP_Super_Cache_Settings_Map::$map as $name => $entry ) {
			if ( ! empty( $entry['global'] ) ) {
				$global = $entry['global'];
				$existing = isset( $globals[ $global ] ) ? $globals[ $global ] : '';
				$this->assertArrayNotHasKey(
					$global,
					$globals,
					"Global '\$$global' is referenced by both '$existing' and '$name'."
				);
				$globals[ $global ] = $name;
			}
		}
	}
}
