<?php
/**
 * Exit Intent Show / Hide animations (NotificationX Pro): an Animation section
 * on the Customize tab with the other types' options, "None" by default, for
 * Free's themes and Elementor / Gutenberg designs only.
 *
 * Saved campaigns read "None", so they look as before; without Pro the
 * animations read "None" too. The front end is covered by
 * tests/js/frontend/exit-intent-motion*.test.js.
 *
 * See docs/qa-guide/exit-intent-improvements.md.
 *
 * @package Notificationx
 */

use NotificationX\Core\PostType;
use NotificationX\FrontEnd\FrontEnd;
use NotificationX\NotificationX;

class Test_Exit_Intent_Animation extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		delete_transient( 'nx_builder_fields' );
	}

	public function tear_down() {
		delete_transient( 'nx_builder_fields' );
		parent::tear_down();
	}

	/**
	 * A section of the builder by name, anywhere in the tabs.
	 */
	protected function section( $name, $fields = null ) {
		if ( null === $fields ) {
			$fields = NotificationX::get_instance()->get_tab()['tabs'];
		}
		foreach ( (array) $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			if ( isset( $field['type'], $field['name'] ) && 'section' === $field['type'] && $name === $field['name'] ) {
				return $field;
			}
			foreach ( array( 'fields', 'tabs' ) as $child ) {
				if ( isset( $field[ $child ] ) ) {
					$found = $this->section( $name, $field[ $child ] );
					if ( $found ) {
						return $found;
					}
				}
			}
		}
		return null;
	}

	protected function json( $value ) {
		return json_decode( wp_json_encode( $value ), true );
	}

	protected function insert_legacy( $data ) {
		$data = array_merge( array( 'type' => 'exit_intent', 'source' => 'exit_intent_custom', 'themes' => 'exit_intent_custom_theme-one', 'enabled' => true ), $data );
		return (int) PostType::get_instance()->insert_post(
			array(
				'type'         => 'exit_intent',
				'source'       => 'exit_intent_custom',
				'theme'        => $data['themes'],
				'global_queue' => false,
				'enabled'      => true,
				'is_inline'    => false,
				'title'        => 'legacy',
				'data'         => $data,
			)
		);
	}

	public function test_animation_section_has_show_and_hide_with_none_by_default() {
		$section = $this->section( 'exit_intent_animation' );
		$this->assertNotNull( $section );
		$this->assertSame( 'Animation', $section['label'] );
		$this->assertSame( array( 'exit_intent_animation_show', 'exit_intent_animation_hide' ), array_keys( $section['fields'] ) );
		foreach ( $section['fields'] as $field ) {
			$this->assertSame( 'select', $field['type'] );
			$this->assertSame( 'default', $field['default'] );
			$this->assertSame( 'None', $field['options']['default']['label'] );
		}
		$this->assertSame( 'Show Animation', $section['fields']['exit_intent_animation_show']['label'] );
		$this->assertSame( 'Hide Animation', $section['fields']['exit_intent_animation_hide']['label'] );
	}

	public function test_options_are_the_other_types_options() {
		$global = $this->section( 'animation' )['fields'];
		$mine   = $this->section( 'exit_intent_animation' )['fields'];
		foreach ( array( 'exit_intent_animation_show' => 'animation_notification_show', 'exit_intent_animation_hide' => 'animation_notification_hide' ) as $name => $from ) {
			$this->assertSame( array_keys( $global[ $from ]['options'] ), array_keys( $mine[ $name ]['options'] ) );
			foreach ( $global[ $from ]['options'] as $key => $option ) {
				if ( 'default' === $key ) {
					continue;
				}
				$this->assertSame( $option, $mine[ $name ]['options'][ $key ], $key );
			}
			// Greyed out without Pro, exactly like the other types.
			$this->assertSame( $global[ $from ]['classes'], $mine[ $name ]['classes'] );
		}
		$this->assertTrue( $mine['exit_intent_animation_show']['options']['animate__zoomIn']['disabled'], 'Free alone (the test site has no Pro).' );
	}

	public function test_the_other_types_animation_section_is_unchanged() {
		$global = $this->section( 'animation' );
		$this->assertSame( 'Default', $global['fields']['animation_notification_show']['options']['default']['label'] );
		$this->assertSame( 'Default', $global['fields']['animation_notification_hide']['options']['default']['label'] );
		// Still hidden for Exit Intent.
		$this->assertStringContainsString( '["!is","source","exit_intent_custom"]', wp_json_encode( $global['rules'] ) );
	}

	public function test_shown_for_free_themes_and_builder_designs_only() {
		$this->assertSame(
			array(
				'and',
				array( 'is', 'source', 'exit_intent_custom' ),
				array(
					'or',
					array( 'includes', 'themes', array( 'exit_intent_custom_theme-one', 'exit_intent_custom_theme-three', 'exit_intent_custom_theme-four' ) ),
					array( 'isOfType', 'elementor_id', 'number' ),
					array( 'isOfType', 'gutenberg_id', 'number' ),
				),
			),
			$this->json( $this->section( 'exit_intent_animation' )['rules'] )
		);
	}

	public function test_a_campaign_saved_before_reads_none() {
		$nx_id = $this->insert_legacy( array() );
		$post  = PostType::get_instance()->get_post( $nx_id );
		$this->assertSame( 'default', $post['exit_intent_animation_show'] );
		$this->assertSame( 'default', $post['exit_intent_animation_hide'] );
		$data = FrontEnd::get_instance()->get_notifications_data( array( 'exit_intent' => array( $nx_id ) ) );
		$this->assertSame( 'default', $data['exit_intent'][ $nx_id ]['post']['exit_intent_animation_show'] );
		$this->assertSame( 'default', $data['exit_intent'][ $nx_id ]['post']['exit_intent_animation_hide'] );
	}

	public function test_without_pro_a_saved_animation_reads_none() {
		$this->assertFalse( NotificationX::is_pro() );
		$nx_id = $this->insert_legacy( array( 'exit_intent_animation_show' => 'animate__zoomIn', 'exit_intent_animation_hide' => 'animate__zoomOut' ) );
		$post  = PostType::get_instance()->get_post( $nx_id );
		$this->assertSame( 'default', $post['exit_intent_animation_show'] );
		$this->assertSame( 'default', $post['exit_intent_animation_hide'] );
	}

	public function test_other_types_keep_their_values() {
		$popup = array( 'source' => 'popup_notification', 'exit_intent_animation_show' => 'animate__zoomIn' );
		$this->assertSame( $popup, \NotificationX\Extensions\ExitIntent\ExitIntentNotification::get_instance()->animation_without_pro( $popup ) );
	}
}
