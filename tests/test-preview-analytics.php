<?php
/**
 * A preview embeds `enable_analytics => false`, so the runtime never posts a
 * click for it, whatever the site's Enable Analytics setting.
 *
 * @package Notificationx
 */

use NotificationX\FrontEnd\Preview;

class Test_Preview_Analytics extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	public function tearDown(): void {
		$_POST = array();
		parent::tearDown();
	}

	protected function preview( $settings ) {
		$_POST = array(
			'nx-preview'       => base64_encode( wp_json_encode( $settings ) ),
			'nx-preview-nonce' => wp_create_nonce( Preview::NONCE_ACTION ),
		);
		return Preview::get_instance()->enqueue_scripts( false );
	}

	public function sources() {
		return array(
			'card'        => array( array( 'source' => 'woocommerce', 'type' => 'conversions', 'themes' => 'conv-theme-one' ), 'active', 'woocommerce' ),
			'popup'       => array( array( 'source' => 'popup_notification', 'type' => 'popup', 'themes' => 'popup_notification_theme-one' ), 'popup', 'popup_notification' ),
			'cookies'     => array( array( 'source' => 'gdpr_notification', 'type' => 'gdpr', 'themes' => 'gdpr_theme-light-one' ), 'gdpr', 'gdpr_notification' ),
			'exit intent' => array( array( 'source' => 'exit_intent_custom', 'type' => 'exit_intent', 'themes' => 'exit_intent_theme-one' ), 'exit_intent', 'exit_intent_custom' ),
		);
	}

	/**
	 * @dataProvider sources
	 */
	public function test_preview_embeds_analytics_off( $settings, $bucket, $source ) {
		$settings = array_merge(
			array(
				'global_queue'            => false,
				'show_notification_image' => 'none',
				'show_default_image'      => false,
				'enable_analytics'        => true,
			),
			$settings
		);
		$this->assertTrue( (bool) \NotificationX\Admin\Settings::get_instance()->get( 'settings.enable_analytics', true ) );

		$result = $this->preview( $settings );
		$this->assertTrue( $result['nxPreview'] );
		$this->assertFalse( $result['settings']['enable_analytics'] );
		$this->assertFalse( $result[ $bucket ][ $source ]['post']['enable_analytics'] );
		$this->assertTrue( $result[ $bucket ][ $source ]['post']['is_preview'] );
	}

	public function test_live_frontend_settings_are_unchanged() {
		$this->assertTrue( (bool) \NotificationX\FrontEnd\FrontEnd::get_instance()->get_settings()['enable_analytics'] );
	}
}
