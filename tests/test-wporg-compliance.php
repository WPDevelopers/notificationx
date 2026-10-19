<?php
/**
 * WordPress.org guideline fixes from issue #184 (free 3.3.5).
 *
 *  1. Exit Intent themes two, five, six and seven are NotificationX Pro code
 *     (Guideline 5). Free skips those notifications when Pro cannot render
 *     them (NotificationX::is_theme_unrenderable()).
 *  2. "Powered by" is opt-in (Guideline 10).
 *  3. The visitor country lookup goes over HTTPS (Guideline 7 disclosure in
 *     README.txt names the service).
 *
 * The tracking consent tests are in test-activation-insights.php.
 *
 * @package Notificationx
 */

use NotificationX\Admin\Settings;
use NotificationX\Core\Helper;
use NotificationX\FrontEnd\FrontEnd;
use NotificationX\NotificationX;

/**
 * @group wporg
 */
class Test_WPOrg_Compliance extends WP_UnitTestCase {

	public function tear_down() {
		remove_all_filters( 'pre_http_request' );
		parent::tear_down();
	}

	protected function skip_with_pro() {
		if ( NotificationX::is_pro() ) {
			$this->markTestSkipped( 'NotificationX Pro is loaded in this test run.' );
		}
	}

	public function test_pro_rendered_exit_intent_themes() {
		$this->assertSame(
			[
				'exit_intent_custom_theme-two',
				'exit_intent_custom_theme-five',
				'exit_intent_custom_theme-six',
				'exit_intent_custom_theme-seven',
			],
			NotificationX::PRO_RENDERED_THEMES
		);
	}

	/**
	 * @dataProvider pro_themes
	 */
	public function test_pro_theme_is_unrenderable_without_pro( $theme ) {
		$this->skip_with_pro();
		$this->assertTrue( NotificationX::is_theme_unrenderable( [ 'themes' => $theme ] ) );
	}

	public function pro_themes() {
		return array_map(
			function ( $theme ) {
				return [ $theme ];
			},
			NotificationX::PRO_RENDERED_THEMES
		);
	}

	/**
	 * @dataProvider free_themes
	 */
	public function test_free_theme_is_renderable( $settings ) {
		$this->assertFalse( NotificationX::is_theme_unrenderable( $settings ) );
	}

	public function free_themes() {
		return [
			'exit intent theme one'   => [ [ 'themes' => 'exit_intent_custom_theme-one' ] ],
			'exit intent theme three' => [ [ 'themes' => 'exit_intent_custom_theme-three' ] ],
			'exit intent theme four'  => [ [ 'themes' => 'exit_intent_custom_theme-four' ] ],
			'other type, same suffix' => [ [ 'themes' => 'popup_notification_theme-seven' ] ],
			'no theme'                => [ [] ],
			'non-string theme'        => [ [ 'themes' => [ 'exit_intent_custom_theme-two' ] ] ],
		];
	}

	/**
	 * An Exit Intent linked to an Elementor design renders the design, not
	 * its theme, so it is never skipped.
	 */
	public function test_elementor_exit_intent_is_renderable() {
		$this->assertFalse(
			NotificationX::is_theme_unrenderable(
				[
					'themes'       => 'exit_intent_custom_theme-seven',
					'elementor_id' => 12,
				]
			)
		);
	}

	public function test_powered_by_is_hidden_until_the_site_opts_in() {
		$settings = Settings::get_instance();
		$saved    = $settings->get( 'settings' );

		$without = (array) $saved;
		unset( $without['disable_powered_by'] );
		$settings->set( 'settings', $without );
		$this->assertTrue( (bool) FrontEnd::get_instance()->get_settings()['disable_powered_by'], 'Hidden when never saved.' );

		$settings->set( 'settings.disable_powered_by', false );
		$this->assertFalse( (bool) FrontEnd::get_instance()->get_settings()['disable_powered_by'], 'Shown after opting in.' );

		$settings->set( 'settings', $saved );
	}

	public function test_powered_by_field_defaults_to_hidden() {
		$field = $this->find_field( Settings::get_instance()->settings_form(), 'disable_powered_by' );
		$this->assertIsArray( $field );
		$this->assertSame( 1, $field['default'] );
	}

	protected function find_field( $fields, $name ) {
		if ( ! is_array( $fields ) ) {
			return null;
		}
		if ( isset( $fields['name'] ) && $name === $fields['name'] && isset( $fields['type'] ) && 'section' !== $fields['type'] ) {
			return $fields;
		}
		foreach ( $fields as $value ) {
			$found = $this->find_field( $value, $name );
			if ( $found ) {
				return $found;
			}
		}
		return null;
	}

	public function test_visitor_country_lookup_uses_https() {
		$requested = [];
		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) use ( &$requested ) {
				$requested[] = $url;
				return [
					'headers'  => [],
					'body'     => '{"ip":"203.0.113.9","country":"de"}',
					'response' => [ 'code' => 200, 'message' => 'OK' ],
					'cookies'  => [],
				];
			},
			10,
			3
		);
		$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
		delete_transient( 'nx_geo_' . md5( '203.0.113.9' ) );

		$country = Helper::nx_get_visitor_country_code();

		unset( $_SERVER['REMOTE_ADDR'] );
		delete_transient( 'nx_geo_' . md5( '203.0.113.9' ) );
		$this->assertSame( [ 'https://api.country.is/203.0.113.9' ], $requested );
		$this->assertSame( 'DE', $country );
	}

	public function test_no_plain_http_geolocation_in_free() {
		foreach ( [ 'includes/Core/Helper.php', 'includes/Admin/PluginInsights.php' ] as $file ) {
			$this->assertStringNotContainsString( 'http://ip-api.com', file_get_contents( NOTIFICATIONX_PATH . $file ), $file );
		}
	}

	/**
	 * Exit Intent defaults use the bundled images; notifications saved with
	 * the old notificationx.com URLs are served the bundled copies.
	 */
	public function test_exit_intent_default_images_are_bundled() {
		$source = file_get_contents( NOTIFICATIONX_PATH . 'includes/Extensions/ExitIntent/ExitIntentNotification.php' );
		$this->assertSame( 0, preg_match( "#'https://notificationx\\.com/wp-content/uploads/[^']+\\.(png|jpg|webp)'#", $source ) );

		$extension = \NotificationX\Extensions\ExitIntent\ExitIntentNotification::get_instance();
		$settings  = $extension->localize_default_image(
			[
				'source'                => 'exit_intent_custom',
				'exit_intent_image_url' => [ 'url' => 'https://notificationx.com/wp-content/uploads/2026/05/exit-intend-theme-five.png' ],
			],
			[]
		);
		$this->assertSame( NOTIFICATIONX_PUBLIC_URL . 'image/exit-intent/exit-intend-theme-five.webp', $settings['exit_intent_image_url']['url'] );

		$custom = [
			'source'                => 'exit_intent_custom',
			'exit_intent_image_url' => [ 'url' => 'https://example.com/mine.png' ],
		];
		$this->assertSame( $custom, $extension->localize_default_image( $custom, [] ) );
	}

	public function test_bundled_font_files_exist() {
		foreach ( [
			'assets/public/vendor/open-sans/css/open-sans.css',
			'assets/public/vendor/open-sans/fonts/open-sans-latin.woff2',
			'assets/public/vendor/font-awesome/css/font-awesome.min.css',
			'assets/public/vendor/font-awesome/fonts/fontawesome-webfont.woff2',
			'assets/admin/vendor/fonts/admin-fonts.css',
		] as $file ) {
			$this->assertFileExists( NOTIFICATIONX_PATH . $file );
		}
		foreach ( FrontEnd::get_instance()->get_external_styles() as $url ) {
			$this->assertStringStartsWith( NOTIFICATIONX_PUBLIC_URL . 'vendor/', $url );
		}
	}
}
