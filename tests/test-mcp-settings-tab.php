<?php
/**
 * The MCP settings tab prints the connection token, which acts as the
 * administrator who paired it. Settings access can be delegated to other roles
 * (Role Management), so the tab must only exist for users who can use the MCP
 * routes (manage_options).
 *
 * @package Notificationx
 */

use NotificationX\Admin\Settings;
use NotificationX\MCP\Manager;
use NotificationX\MCP\Pairing;

class Test_MCP_Settings_Tab extends WP_UnitTestCase {

	protected $token;

	public function setUp(): void {
		parent::setUp();
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		Settings::get_instance()->set( 'settings.enable_mcp', true );
		Pairing::get_instance()->connect();
		$this->token = Pairing::get_instance()->site_token();
	}

	protected function tabs() {
		return Manager::get_instance()->register_settings_tab( array() );
	}

	public function test_administrator_gets_the_tab_without_the_token_in_the_markup() {
		$tabs = $this->tabs();
		$this->assertArrayHasKey( 'tab-mcp', $tabs );
		$this->assertNotEmpty( $this->token );
		// The panel fetches the token from GET /mcp/connection (manage_options);
		// the schema itself never carries it.
		$this->assertStringNotContainsString( $this->token, wp_json_encode( $tabs ) );
	}

	public function test_delegated_settings_role_gets_no_tab_and_no_token() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		get_user_by( 'id', $editor )->add_cap( 'edit_notificationx_settings' );
		wp_set_current_user( $editor );

		$tabs = $this->tabs();
		$this->assertArrayNotHasKey( 'tab-mcp', $tabs );
		$this->assertStringNotContainsString( $this->token, wp_json_encode( $tabs ) );
	}

	public function test_other_tabs_pass_through_for_delegated_roles() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$tabs = Manager::get_instance()->register_settings_tab( array( 'tab-general' => array( 'id' => 'tab-general' ) ) );
		$this->assertSame( array( 'tab-general' => array( 'id' => 'tab-general' ) ), $tabs );
	}

	public function test_panel_assets_are_not_printed_for_delegated_roles() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );
		set_current_screen( 'dashboard' );
		$_GET['page'] = 'nx-settings';

		ob_start();
		Manager::get_instance()->print_panel_assets();
		$out = ob_get_clean();
		unset( $_GET['page'] );

		$this->assertSame( '', $out );
	}
}
