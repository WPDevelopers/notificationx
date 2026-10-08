<?php
/**
 * Tests for step 7 of the free/Pro code separation (free 3.3.4).
 *
 * Free no longer ships working Pro code: the Cart Peek Type, the Inline and
 * inline shortcode classes, the Flashing Tab script and icons, and the
 * Discount Alert / Cart Peek rendering (the JS side is covered by
 * `npm run test:js`). NotificationX Pro 3.2.4+ registers and renders them.
 *
 * Also covers the `notice` endpoint's add-on script list, which lets the
 * frontend runtime load Pro's rendering script on Cross Domain Notice sites.
 *
 * See docs/api/frontend-js-hooks.md.
 *
 * @package Notificationx
 */

use NotificationX\Extensions\FlashingTab\FlashingTab;
use NotificationX\FrontEnd\FrontEnd;
use NotificationX\Types\TypeFactory;

class Test_Pro_Code_Removed extends WP_UnitTestCase {

	public function tear_down() {
		remove_all_filters( 'nx_frontend_addon_scripts' );
		remove_all_filters( 'nx_types_classes' );
		parent::tear_down();
	}

	/**
	 * Autoloading a removed class must not include a missing file: the
	 * Composer classmap has to be regenerated with the files deleted.
	 */
	public function test_removed_classes_are_gone_from_the_autoloader() {
		$this->assertFalse( class_exists( 'NotificationX\Types\WooCommerceCartPeek' ) );
		$this->assertFalse( class_exists( 'NotificationX\Core\Inline' ) );
		$this->assertFalse( class_exists( 'NotificationX\Core\ShortcodeInline' ) );
	}

	/**
	 * The one rule that keeps the removal fatal-free: TypeFactory has no
	 * class_exists() guard, so a Type entry must never outlive its class.
	 */
	public function test_type_factory_has_no_cart_peek_entry() {
		$factory = new TypeFactory();
		$this->assertArrayNotHasKey( 'woocommerce_cart_peek', $factory->types );
		foreach ( $factory->types as $id => $class ) {
			$this->assertTrue( class_exists( $class ), "Type '{$id}' maps to a missing class {$class}" );
		}
	}

	/**
	 * NotificationX Pro adds Cart Peek back through `nx_types_classes`.
	 */
	public function test_an_add_on_can_register_the_cart_peek_type() {
		add_filter(
			'nx_types_classes',
			function ( $types ) {
				$types['woocommerce_cart_peek'] = 'NotificationX\Types\Conversions';
				return $types;
			}
		);
		$factory = new TypeFactory();
		$this->assertSame( 'NotificationX\Types\Conversions', $factory->types['woocommerce_cart_peek'] );
	}

	/**
	 * The free Cart Peek source stub stays (Pro's extension extends it), but
	 * its Type now comes from Pro. Without Pro, the stub must not hook an
	 * invalid callback for its missing Type.
	 */
	public function test_cart_peek_stub_hooks_no_callback_for_its_missing_type() {
		$extension = \NotificationX\Extensions\WooCommerce\WooCommerceCartPeek::get_instance();
		$this->assertEmpty( $extension->get_type() );

		$extension->admin_actions();

		global $wp_filter;
		$hook = 'nx_can_entry_woocommerce_cart_peek';
		$this->assertArrayHasKey( $hook, $wp_filter );
		foreach ( $wp_filter[ $hook ]->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$this->assertIsCallable( $callback['function'] );
			}
		}
	}

	public function test_free_does_not_register_the_inline_shortcode() {
		$this->assertFalse( shortcode_exists( 'notificationx_inline' ) );
	}

	public function test_flashing_tab_and_announcement_files_are_deleted() {
		$this->assertFileDoesNotExist( NOTIFICATIONX_PATH . 'assets/public/js/flashing-tab.js' );
		$this->assertDirectoryDoesNotExist( NOTIFICATIONX_PATH . 'assets/public/image/flashing-tab' );
		$this->assertFileDoesNotExist( NOTIFICATIONX_PATH . 'nxdev/notificationx/frontend/flashing-tab.ts' );
		$this->assertDirectoryDoesNotExist( NOTIFICATIONX_PATH . 'nxdev/notificationx/frontend/flashing' );
		$this->assertDirectoryDoesNotExist( NOTIFICATIONX_PATH . 'nxdev/notificationx/frontend/themes/announcements' );
		$this->assertFileDoesNotExist( NOTIFICATIONX_PATH . 'nxdev/notificationx/frontend/themes/helpers/Button.js' );
		$this->assertFileDoesNotExist( NOTIFICATIONX_PATH . 'nxdev/notificationx/frontend/scss/_themes/_announcement.scss' );
	}

	/**
	 * The Flashing Tab stub stores bare icon file names, which Pro resolves
	 * against its own copy of the icons. A URL would point at the deleted
	 * free icons.
	 */
	public function test_flashing_tab_stub_icons_are_bare_file_names() {
		$extension = FlashingTab::get_instance();
		$extension->init_extension();
		$icons = [];
		foreach ( $extension->themes as $theme ) {
			array_walk_recursive(
				$theme['defaults'],
				function ( $value, $key ) use ( &$icons ) {
					if ( in_array( $key, [ 'icon', 'icon-one', 'icon-two' ], true ) ) {
						$icons[] = $value;
					}
				}
			);
		}
		$this->assertNotEmpty( $icons );
		foreach ( $icons as $icon ) {
			$this->assertMatchesRegularExpression( '/^theme-\d+-icon-\d+\.png$/', $icon );
		}
	}

	public function test_addon_scripts_are_empty_by_default() {
		$this->assertSame( [], FrontEnd::get_instance()->get_addon_scripts() );
	}

	public function test_addon_scripts_are_sanitized() {
		add_filter(
			'nx_frontend_addon_scripts',
			function () {
				return [
					[
						'handle' => 'My-Addon',
						'src'    => 'https://example.com/addon.js?ver=1',
						'data'   => [
							'name'  => 'myAddonData',
							'value' => [ 'a' => 1 ],
						],
					],
					[
						'handle' => 'no-data',
						'src'    => 'https://example.com/b.js',
						'data'   => [
							'name'  => 'bad name;alert(1)',
							'value' => 1,
						],
					],
					[ 'handle' => 'js-url', 'src' => 'javascript:alert(1)' ],
					[ 'handle' => '', 'src' => 'https://example.com/c.js' ],
					[ 'src' => 'https://example.com/d.js' ],
					'not-an-array',
				];
			}
		);

		$this->assertSame(
			[
				[
					'handle' => 'my-addon',
					'src'    => 'https://example.com/addon.js?ver=1',
					'data'   => [
						'name'  => 'myAddonData',
						'value' => [ 'a' => 1 ],
					],
				],
				[
					'handle' => 'no-data',
					'src'    => 'https://example.com/b.js',
					'data'   => null,
				],
			],
			FrontEnd::get_instance()->get_addon_scripts()
		);
	}

	public function test_addon_scripts_survive_a_broken_filter() {
		add_filter(
			'nx_frontend_addon_scripts',
			function () {
				return 'not-an-array';
			}
		);
		$this->assertSame( [], FrontEnd::get_instance()->get_addon_scripts() );
	}

	public function test_notice_endpoint_lists_addon_scripts_only_when_asked() {
		add_filter(
			'nx_frontend_addon_scripts',
			function ( $scripts ) {
				$scripts[] = [
					'handle' => 'my-addon',
					'src'    => 'https://example.com/addon.js',
				];
				return $scripts;
			}
		);

		$without = $this->notice( [ 'all_active' => true ] );
		$this->assertArrayNotHasKey( 'addon_scripts', $without );

		$off = $this->notice( [ 'all_active' => true, 'addon_scripts' => false ] );
		$this->assertArrayNotHasKey( 'addon_scripts', $off );

		$with = $this->notice( [ 'all_active' => true, 'addon_scripts' => true ] );
		$this->assertArrayHasKey( 'addon_scripts', $with );
		$this->assertSame( 'my-addon', $with['addon_scripts'][0]['handle'] );
		$this->assertArrayHasKey( 'settings', $with );
	}

	private function notice( $body ) {
		$request = new WP_REST_Request( 'POST', '/notificationx/v1/notice' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		return $response->get_data();
	}
}
