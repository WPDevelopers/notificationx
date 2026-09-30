<?php
/**
 * Tests for the free-side seams of the free/Pro code separation (free 3.3.3).
 *
 * - `notificationx-public` depends on `wp-hooks`, so the frontend runtime can
 *   read add-on filters from `window.wp.hooks` (see
 *   nxdev/notificationx/frontend/core/hooks.ts; the JS side is covered by
 *   `npm run test:js`).
 * - The admin notice that asks for NotificationX Pro >= Admin::MIN_PRO_VERSION.
 *
 * @package Notificationx
 */

use NotificationX\Admin\Admin;
use NotificationX\FrontEnd\FrontEnd;

class Test_Pro_Separation_Seams extends WP_UnitTestCase {

	public function tear_down() {
		remove_all_filters( 'nx_frontend_script_deps' );
		wp_deregister_script( 'notificationx-public' );
		parent::tear_down();
	}

	public function test_script_dependencies_default_to_wp_hooks() {
		$this->assertSame( [ 'wp-hooks' ], FrontEnd::get_instance()->get_script_dependencies() );
	}

	public function test_script_dependencies_filter_can_add_handles() {
		add_filter(
			'nx_frontend_script_deps',
			function ( $deps ) {
				$deps[] = 'my-addon';
				return $deps;
			}
		);
		$this->assertSame( [ 'wp-hooks', 'my-addon' ], FrontEnd::get_instance()->get_script_dependencies() );
	}

	public function test_script_dependencies_always_keep_wp_hooks() {
		add_filter(
			'nx_frontend_script_deps',
			function () {
				return [ 'my-addon' ];
			}
		);
		$this->assertSame( [ 'wp-hooks', 'my-addon' ], FrontEnd::get_instance()->get_script_dependencies() );
	}

	public function test_script_dependencies_survive_a_broken_filter() {
		add_filter(
			'nx_frontend_script_deps',
			function () {
				return 'not-an-array';
			}
		);
		$this->assertSame( [ 'wp-hooks' ], FrontEnd::get_instance()->get_script_dependencies() );

		remove_all_filters( 'nx_frontend_script_deps' );
		add_filter(
			'nx_frontend_script_deps',
			function () {
				return [ 'wp-hooks', 42, null, 'my-addon', 'my-addon' ];
			}
		);
		$this->assertSame( [ 'wp-hooks', 'my-addon' ], FrontEnd::get_instance()->get_script_dependencies() );
	}

	public function test_public_script_is_registered_with_wp_hooks() {
		FrontEnd::get_instance()->enqueue_scripts();
		$script = wp_scripts()->query( 'notificationx-public', 'registered' );
		$this->assertNotFalse( $script, 'notificationx-public is registered' );
		$this->assertContains( 'wp-hooks', $script->deps );
	}

	/**
	 * @dataProvider pro_versions
	 */
	public function test_pro_needs_update( $version, $expected ) {
		$this->assertSame( $expected, Admin::pro_needs_update( $version ) );
	}

	public function pro_versions() {
		return [
			'older patch'        => [ '3.2.2', true ],
			'older minor'        => [ '3.1.5', true ],
			'minimum'            => [ '3.2.3', false ],
			'newer'              => [ '3.3.0', false ],
			'empty version'      => [ '', false ],
			'non-string version' => [ 3.1, false ],
		];
	}

	public function test_min_pro_version_is_the_release_with_steps_1_to_3() {
		$this->assertSame( '3.2.3', Admin::MIN_PRO_VERSION );
	}

	public function test_pro_needs_update_is_false_without_pro() {
		if ( class_exists( '\NotificationXPro\NotificationX' ) ) {
			$this->markTestSkipped( 'NotificationX Pro is loaded in this test run.' );
		}
		$this->assertFalse( Admin::pro_needs_update() );
	}

	public function test_notice_is_silent_without_pro() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		if ( is_multisite() ) {
			grant_super_admin( get_current_user_id() );
		}
		$admin = ( new ReflectionClass( Admin::class ) )->newInstanceWithoutConstructor();

		ob_start();
		$admin->pro_version_notice();
		$this->assertSame( '', ob_get_clean() );
	}

	/**
	 * Old Pro is simulated by defining its class and version constant, which
	 * cannot be undone, so this runs in its own process.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_notice_shows_for_old_pro_and_only_to_users_who_can_update() {
		if ( ! class_exists( '\NotificationXPro\NotificationX' ) ) {
			eval( 'namespace NotificationXPro; class NotificationX {}' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- test double for the Pro main class.
		}
		if ( ! defined( 'NOTIFICATIONX_PRO_VERSION' ) ) {
			define( 'NOTIFICATIONX_PRO_VERSION', '3.2.2' );
		}
		$this->assertTrue( Admin::pro_needs_update() );

		$admin = ( new ReflectionClass( Admin::class ) )->newInstanceWithoutConstructor();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );
		ob_start();
		$admin->pro_version_notice();
		$this->assertSame( '', ob_get_clean(), 'Hidden from users who cannot update plugins.' );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		if ( is_multisite() ) {
			grant_super_admin( get_current_user_id() );
		}
		ob_start();
		$admin->pro_version_notice();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'nx-pro-version-notice', $html );
		$this->assertStringContainsString( '3.2.2', $html );
		$this->assertStringContainsString( Admin::MIN_PRO_VERSION, $html );
		$this->assertStringContainsString( 'plugin_status=upgrade', $html );
		$this->assertStringNotContainsString( 'is-dismissible', $html );
	}
}
