<?php
/**
 * "Verified card" (vc) add-on hooks, PHP side (free x.x.x):
 *  - NOTIFICATIONX_VC_API, the marker an add-on checks before registering
 *    vc themes;
 *  - the nx_vc_theme_keys filter on GlobalFields::vc_theme_keys(): add-on keys
 *    are appended, built-in keys are always kept, invalid entries and
 *    duplicates are dropped, a non-array return is ignored;
 *  - the builder rule that hides Advanced Design and Button reads the
 *    filtered list.
 *
 * The add-on key is a made-up "Sample" theme (conversions_vc-sample).
 *
 * @package Notificationx
 */

use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;

/**
 * @group themes
 * @group vc-themes
 */
class Test_VC_Addon_Hooks extends WP_UnitTestCase {

	public function test_marker_is_defined() {
		$this->assertTrue( defined( 'NOTIFICATIONX_VC_API' ) );
		$this->assertSame( 1, NOTIFICATIONX_VC_API );
	}

	public function test_without_a_filter_the_list_is_the_built_in_list() {
		$this->assertSame( GlobalFields::vc_builtin_theme_keys(), GlobalFields::vc_theme_keys() );
		$this->assertCount( 20, GlobalFields::vc_builtin_theme_keys() );
	}

	public function test_an_add_on_key_is_appended() {
		add_filter(
			'nx_vc_theme_keys',
			function ( $keys ) {
				$keys[] = 'conversions_vc-sample';
				return $keys;
			}
		);
		$this->assertSame(
			array_merge( GlobalFields::vc_builtin_theme_keys(), array( 'conversions_vc-sample' ) ),
			GlobalFields::vc_theme_keys()
		);
	}

	public function test_built_in_keys_are_always_kept() {
		add_filter(
			'nx_vc_theme_keys',
			function () {
				return array( 'conversions_vc-sample' );
			}
		);
		$keys = GlobalFields::vc_theme_keys();
		foreach ( GlobalFields::vc_builtin_theme_keys() as $key ) {
			$this->assertContains( $key, $keys );
		}
		$this->assertContains( 'conversions_vc-sample', $keys );
	}

	public function test_invalid_entries_and_duplicates_are_dropped() {
		add_filter(
			'nx_vc_theme_keys',
			function ( $keys ) {
				return array_merge(
					$keys,
					array( 'conversions_theme-one', 'vc-sample', 'conversions_vc-Sample', 'conversions_vc-sample-two', 5, null, array(), 'conversions_vc-receipt', 'conversions_vc-sample', 'conversions_vc-sample' )
				);
			}
		);
		$this->assertSame(
			array_merge( GlobalFields::vc_builtin_theme_keys(), array( 'conversions_vc-sample' ) ),
			GlobalFields::vc_theme_keys()
		);
	}

	public function test_a_non_array_return_is_ignored() {
		add_filter( 'nx_vc_theme_keys', '__return_false' );
		$this->assertSame( GlobalFields::vc_builtin_theme_keys(), GlobalFields::vc_theme_keys() );
	}

	public function test_the_hide_rule_includes_the_add_on_key() {
		add_filter(
			'nx_vc_theme_keys',
			function ( $keys ) {
				$keys[] = 'conversions_vc-sample';
				return $keys;
			}
		);
		$rule = json_decode( wp_json_encode( Rules::includes( 'themes', GlobalFields::vc_theme_keys(), true ) ), true );
		$this->assertSame( '!includes', $rule[0] );
		$this->assertContains( 'conversions_vc-sample', $rule[2] );
		$this->assertContains( 'conversions_vc-receipt', $rule[2] );
	}
}
