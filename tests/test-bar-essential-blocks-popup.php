<?php
/**
 * The Notification Bar's Gutenberg themes five, six and seven need Essential
 * Blocks. Picking one without it opens a popup whose button installs and
 * activates the plugin (proAlert() reads `install_plugin`). It used to be a
 * link to a plugin search, so bars were published without the plugin and
 * showed as stacked blocks on the site.
 *
 * @package NotificationX
 */

use NotificationX\CoreInstaller;
use NotificationX\Extensions\PressBar\PressBar;

class Test_Bar_Essential_Blocks_Popup extends WP_UnitTestCase {

	const EB = 'essential-blocks/essential-blocks.php';

	private $active;

	public function set_up() {
		parent::set_up();
		$this->active = get_option( 'active_plugins', array() );
	}

	public function tear_down() {
		update_option( 'active_plugins', $this->active );
		wp_cache_delete( 'plugins', 'plugins' );
		remove_all_filters( 'plugins_api' );
		parent::tear_down();
	}

	/** Make get_plugins() list Essential Blocks as installed. */
	private function fake_installed() {
		wp_cache_set( 'plugins', array( '' => array( self::EB => array( 'Name' => 'Essential Blocks', 'Version' => '5.0.0' ) ) ), 'plugins' );
	}

	private function popup() {
		$bar = PressBar::get_instance();
		$bar->load_plugin_dependencies();
		return $bar->popup;
	}

	public function test_not_installed_offers_a_real_install() {
		wp_cache_set( 'plugins', array( '' => array() ), 'plugins' );
		$popup = $this->popup();

		$this->assertSame( 'essential-blocks', $popup['install_plugin']['slug'] );
		$this->assertSame( 'essential-blocks.php', $popup['install_plugin']['file'] );
		$this->assertFalse( $popup['install_plugin']['is_installed'] );
		$this->assertSame( 'Install Essential Blocks', $popup['denyButtonText'] );
		$this->assertTrue( $popup['forced'] );
		// The button is the action itself, not a link to a plugin search.
		$this->assertStringNotContainsString( '<a', $popup['denyButtonText'] );
		$this->assertStringNotContainsString( '%2520', wp_json_encode( $popup ) );
		// Its own copy, not the Growth Alert text.
		$this->assertStringNotContainsString( 'growth alert', strtolower( $popup['html'] ) );
		$this->assertStringContainsString( 'Install and activate Essential Blocks', $popup['html'] );
		// A failed install still points to the Add Plugins screen.
		$this->assertStringContainsString( 'plugin-install.php?s=Essential+Blocks', $popup['install_plugin']['failed'] );
	}

	public function test_installed_but_inactive_offers_activate() {
		$this->fake_installed();
		$popup = $this->popup();

		$this->assertTrue( $popup['install_plugin']['is_installed'] );
		$this->assertSame( 'Activate Essential Blocks', $popup['denyButtonText'] );
		$this->assertStringContainsString( 'Activate Essential Blocks so', $popup['html'] );
	}

	public function test_active_plugin_needs_no_popup() {
		$this->fake_installed();
		$this->popup();
		update_option( 'active_plugins', array_merge( (array) $this->active, array( self::EB ) ) );
		$this->assertNull( $this->popup() );
	}

	public function test_install_returns_the_error_when_wordpress_org_is_unreachable() {
		add_filter(
			'plugins_api',
			function () {
				return new WP_Error( 'plugins_api_failed', 'An unexpected error occurred.' );
			}
		);
		$result = CoreInstaller::get_instance()->install_plugin( 'nx-test-missing-plugin', 'nx-test-missing-plugin.php' );
		$this->assertWPError( $result );
		$this->assertSame( 'plugins_api_failed', $result->get_error_code() );
	}
}
