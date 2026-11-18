<?php
/**
 * Click-open popups: the Announcement popup's "When to Open" setting
 * (includes/Extensions/PopupNotification/PopupNotification.php).
 *
 * @package Notificationx
 */

use NotificationX\Core\PostType;
use NotificationX\Extensions\GlobalFields;
use NotificationX\Extensions\Popup\PopupNotification;

class Test_Popup_Click_Open extends WP_UnitTestCase {

	protected function customize_fields() {
		delete_transient( 'nx_builder_fields' );
		$tabs = GlobalFields::get_instance()->tabs();
		return $tabs['tabs']['customize_tab']['fields'];
	}

	public function test_the_setting_sits_next_to_the_popups_triggers_for_announcements_only() {
		$fields  = $this->customize_fields();
		$section = $fields['popup_open_trigger'];
		$this->assertSame( 'When to Open', $section['label'] );
		$rule = $section['rules'];
		$this->assertSame( [ 'is', 'source', 'popup_notification' ], [ $rule->condition, $rule->name, $rule->value ], 'Only the Announcement popup.' );
		$this->assertLessThan( $fields['exit_intent_settings']['priority'], $section['priority'], 'Just above Exit Intent Popup.' );

		$open = $section['fields']['popup_open_on'];
		$this->assertSame( 'auto', $open['default'], 'Existing popups keep opening as before.' );
		$this->assertSame( [ 'auto', 'click', 'both' ], array_values( wp_list_pluck( $open['options'], 'value' ) ) );

		$target = $section['fields']['popup_click_selector'];
		$this->assertSame( 'text', $target['type'] );
		$rule = $target['rules'];
		$this->assertSame( [ 'includes', 'popup_open_on', [ 'click', 'both' ] ], [ $rule->condition, $rule->name, $rule->value ] );
		$this->assertStringContainsString( '#nx-popup-ID', $target['help'] );
	}

	public function test_existing_popup_settings_are_unchanged() {
		$fields = $this->customize_fields();
		foreach ( [ 'popup_settings', 'exit_intent_settings', 'timing' ] as $key ) {
			$this->assertArrayHasKey( $key, $fields );
		}
		$this->assertSame( [ 'show_close_button', 'close_on_button_click', 'close_button_position' ], array_keys( $fields['popup_settings']['fields'] ) );
		$this->assertSame( [ 'convert_to_exit_intent', 'exit_intent_cookie_duration' ], array_keys( $fields['exit_intent_settings']['fields'] ) );
		$this->assertArrayHasKey( 'delay_before', $fields['timing']['fields'] );
	}

	public function test_click_selectors_keep_only_classes_and_ids() {
		$this->assertSame( '.open-offer, #promo, .bare-name', PopupNotification::click_selectors( ' .open-offer ,#promo, bare-name, .open-offer ' ) );
		$this->assertSame( '', PopupNotification::click_selectors( 'a[href*=x], div > .x, .x:hover, <script>, #1abc, ' ) );
		$this->assertSame( '.ok', PopupNotification::click_selectors( '.ok, body *' ) );
		$this->assertSame( '', PopupNotification::click_selectors( [ '.x' ] ) );
		$many = implode( ',', array_map( function ( $i ) { return ".c$i"; }, range( 1, 15 ) ) );
		$this->assertCount( 10, explode( ', ', PopupNotification::click_selectors( $many ) ) );
	}

	public function test_saving_checks_the_values_for_this_source_only() {
		$popup = PopupNotification::get_instance();
		$row   = [ 'data' => [ 'popup_open_on' => 'hover', 'popup_click_selector' => '.go, body > div' ] ];
		$this->assertSame( [ 'data' => [ 'popup_open_on' => 'auto', 'popup_click_selector' => '.go' ] ], $popup->sanitize_click_trigger( $row ) );
		$row = [ 'data' => [ 'popup_open_on' => 'click', 'popup_click_selector' => '#buy' ] ];
		$this->assertSame( $row, $popup->sanitize_click_trigger( $row ) );
		$this->assertSame( [ 'data' => [ 'title' => 'x' ] ], $popup->sanitize_click_trigger( [ 'data' => [ 'title' => 'x' ] ] ), 'A popup saved without the setting stays as it was.' );
		$this->assertSame( 'not a row', $popup->sanitize_click_trigger( 'not a row' ) );
		$this->assertFalse( has_filter( 'nx_save_post_woocommerce', [ $popup, 'sanitize_click_trigger' ] ) );
		$this->assertNotFalse( has_filter( 'nx_save_post_popup_notification', [ $popup, 'sanitize_click_trigger' ] ) );
	}

	public function test_a_saved_popup_keeps_its_trigger_and_the_selector_is_cleaned() {
		$saved = PostType::get_instance()->save_post(
			[
				'type'                 => 'popup',
				'source'               => 'popup_notification',
				'themes'               => 'popup_notification_theme-one',
				'enabled'              => true,
				'title'                => 'click open',
				'popup_open_on'        => 'click',
				'popup_click_selector' => '.open-offer, a[href]',
			]
		);
		$post = PostType::get_instance()->get_post( $saved['nx_id'] );
		$this->assertSame( 'click', $post['popup_open_on'] );
		$this->assertSame( '.open-offer', $post['popup_click_selector'] );

		$old  = PostType::get_instance()->save_post(
			[
				'type'    => 'popup',
				'source'  => 'popup_notification',
				'themes'  => 'popup_notification_theme-one',
				'enabled' => true,
				'title'   => 'saved before',
			]
		);
		$post = PostType::get_instance()->get_post( $old['nx_id'] );
		$this->assertSame( 'auto', $post['popup_open_on'] ?? 'auto', 'Without the setting it opens automatically, as before.' );
	}
}
