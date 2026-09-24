<?php
/**
 * Regression tests for inline notifications whose `inline_location` is not an array.
 *
 * `inline_location` is a multi-select field with `'default' => ''`. A notification
 * created through MCP create-notification or the Quick Builder without that field
 * stored '' (an empty string). The inline extensions' show_on_exclude() callbacks
 * run on the shared `nx_show_on_exclude` filter for every enabled notification on
 * every front-end page load, and passed that string to array_diff(). On PHP 8
 * that is a TypeError, so the entire front end returned a 500.
 *
 * Covered here:
 *  - every inline extension excludes its own notifications from the popup loop
 *    whatever type `inline_location` holds, and leaves other sources alone;
 *  - NotificationX::normalize_post() always returns multi-select fields as arrays;
 *  - MCP create-notification backfills the theme's default `inline_location`.
 *
 * @package Notificationx
 */

use NotificationX\Abilities\BuilderInfo;
use NotificationX\Extensions\EDD\EDDInline;
use NotificationX\Extensions\LearnDash\LearnDashInline;
use NotificationX\Extensions\LearnPress\LearnPressInline;
use NotificationX\Extensions\Tutor\TutorInline;
use NotificationX\Extensions\WooCommerce\WooInline;
use NotificationX\NotificationX;

class Test_Inline_Location extends WP_UnitTestCase {

	/**
	 * Inline extension classes and the source id each one owns.
	 *
	 * @return array
	 */
	public function inline_extensions() {
		return array(
			'woocommerce' => array( WooInline::class ),
			'edd'         => array( EDDInline::class ),
			'learndash'   => array( LearnDashInline::class ),
			'learnpress'  => array( LearnPressInline::class ),
			'tutor'       => array( TutorInline::class ),
		);
	}

	/**
	 * Values `inline_location` can hold in stored settings.
	 *
	 * @return array
	 */
	public function location_values() {
		return array(
			'empty string'  => array( '' ),
			'null'          => array( null ),
			'plain string'  => array( 'woocommerce_before_add_to_cart_form' ),
			'empty array'   => array( array() ),
			'valid array'   => array( array( 'woocommerce_before_add_to_cart_form', 'edd_single' ) ),
		);
	}

	/**
	 * Settings for a notification that belongs to the given extension.
	 *
	 * @param object $extension Inline extension instance.
	 * @return array
	 */
	protected function settings_for( $extension ) {
		return array(
			'nx_id'  => 1,
			'type'   => 'inline',
			'source' => $extension->id,
		);
	}

	/**
	 * Each extension must exclude its own notifications from the popup loop,
	 * for every stored shape of `inline_location`, without throwing.
	 *
	 * @dataProvider inline_extensions
	 *
	 * @param string $class Extension class.
	 */
	public function test_show_on_exclude_never_throws_and_excludes_own_source( $class ) {
		$extension = $class::get_instance();

		foreach ( $this->location_values() as $label => $args ) {
			$settings                    = $this->settings_for( $extension );
			$settings['inline_location'] = $args[0];

			$this->assertTrue(
				$extension->show_on_exclude( false, $settings ),
				"{$class} did not exclude its own notification when inline_location is {$label}."
			);
		}

		// A missing key must not throw either.
		$this->assertTrue( $extension->show_on_exclude( false, $this->settings_for( $extension ) ) );
	}

	/**
	 * Notifications from another source pass through unchanged.
	 *
	 * @dataProvider inline_extensions
	 *
	 * @param string $class Extension class.
	 */
	public function test_show_on_exclude_ignores_other_sources( $class ) {
		$extension = $class::get_instance();
		$settings  = array(
			'type'            => 'inline',
			'source'          => 'some_other_source',
			'inline_location' => '',
		);

		$this->assertFalse( $extension->show_on_exclude( false, $settings ) );
		$this->assertTrue( $extension->show_on_exclude( true, $settings ) );
	}

	/**
	 * The shared filter, as FrontEnd::get_notifications_ids() calls it, must not
	 * throw for any inline source with a string `inline_location`.
	 *
	 * @dataProvider inline_extensions
	 *
	 * @param string $class Extension class.
	 */
	public function test_shared_filter_survives_string_inline_location( $class ) {
		$extension                   = $class::get_instance();
		$settings                    = $this->settings_for( $extension );
		$settings['inline_location'] = '';

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- consuming core NotificationX hook.
		$this->assertTrue( (bool) apply_filters( 'nx_show_on_exclude', false, $settings ) );
	}

	/**
	 * normalize_multiple_value() always returns an array.
	 */
	public function test_normalize_multiple_value() {
		$nx = NotificationX::get_instance();

		$this->assertSame( array(), $nx->normalize_multiple_value( '' ) );
		$this->assertSame( array(), $nx->normalize_multiple_value( null ) );
		$this->assertSame( array(), $nx->normalize_multiple_value( false ) );
		$this->assertSame( array( 'edd_single' ), $nx->normalize_multiple_value( 'edd_single' ) );
		$this->assertSame( array( 0 ), $nx->normalize_multiple_value( 0 ) );
		$this->assertSame( array( 'a', 'b' ), $nx->normalize_multiple_value( array( 'a', 'b' ) ) );
	}

	/**
	 * normalize_post() turns stored '' / missing multi-select values into arrays,
	 * so every consumer can pass them to array functions.
	 */
	public function test_normalize_post_returns_arrays_for_multiple_fields() {
		$nx       = NotificationX::get_instance();
		$multiple = array();
		foreach ( $nx->get_field_names() as $name => $field ) {
			if ( ! empty( $field['multiple'] ) ) {
				$multiple[] = $name;
			}
		}
		$this->assertNotEmpty( $multiple, 'No multi-select fields found in the builder config.' );

		$stored = array();
		foreach ( $multiple as $i => $name ) {
			// Alternate between an empty string and a missing key.
			if ( 0 === $i % 2 ) {
				$stored[ $name ] = '';
			}
		}

		$normalized = $nx->normalize_post( $stored );
		foreach ( $multiple as $name ) {
			$this->assertIsArray( $normalized[ $name ], "{$name} was not normalized to an array." );
		}
	}

	/**
	 * Array values and array defaults are left as they are.
	 */
	public function test_normalize_post_keeps_arrays() {
		$nx     = NotificationX::get_instance();
		$fields = $nx->get_field_names();
		if ( empty( $fields['all_locations']['multiple'] ) ) {
			$this->markTestSkipped( 'all_locations is not a multi-select field in this build.' );
		}

		$normalized = $nx->normalize_post( array( 'all_locations' => array( 'is_front_page' ) ) );
		$this->assertSame( array( 'is_front_page' ), $normalized['all_locations'] );
	}

	/**
	 * MCP create-notification uses the theme's default inline locations, the same
	 * ones the admin builder applies through nx_themes_trigger.
	 */
	public function test_default_inline_location_for_theme() {
		$location = BuilderInfo::default_inline_location_for_theme( 'woo_inline_conv-theme-seven' );
		if ( empty( $location ) ) {
			$this->markTestSkipped( 'woo_inline_conv-theme-seven is not registered in this environment.' );
		}
		$this->assertSame( array( 'woocommerce_before_add_to_cart_form' ), $location );

		$this->assertSame( array(), BuilderInfo::default_inline_location_for_theme( '' ) );
		$this->assertSame( array(), BuilderInfo::default_inline_location_for_theme( 'no-such-theme' ) );
		$this->assertSame( array(), BuilderInfo::default_inline_location_for_theme( array( 'x' ) ) );
	}
}
