<?php
/**
 * The "Token connection" row under Connected apps appears once a client has
 * used the connection token (Pairing `last_used`). NotificationX's own "Test
 * connection" authenticates with that token too, but it is not a client, so it
 * must not make the row appear: a stray row invites an admin to revoke the real
 * token.
 *
 * @package Notificationx
 */

use NotificationX\Admin\Settings;
use NotificationX\MCP\Manager;
use NotificationX\MCP\Pairing;
use NotificationX\MCP\SelfTest;
use NotificationX\MCP\Server;

class Test_MCP_Token_Usage extends WP_UnitTestCase {

	protected $token;

	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		Settings::get_instance()->set( 'settings.enable_mcp', true );
		Pairing::get_instance()->disconnect();
		Pairing::get_instance()->connect();
		$this->token = Pairing::get_instance()->site_token();
		delete_transient( SelfTest::MARKER_TRANSIENT );
	}

	protected function call( $marker = '' ) {
		$request = new WP_REST_Request( 'POST', '/notificationx/v1/mcp' );
		$request->set_header( 'authorization', 'Bearer ' . $this->token );
		if ( '' !== $marker ) {
			$request->set_header( SelfTest::MARKER_HEADER, $marker );
		}
		$request->set_body( wp_json_encode( array( 'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list', 'params' => (object) array() ) ) );
		return Server::get_instance()->handle( $request );
	}

	protected function last_used() {
		$state = Pairing::get_instance()->state();
		return (int) ( $state['last_used'] ?? 0 );
	}

	protected function app_types() {
		$method = new ReflectionMethod( Manager::class, 'get_connected_apps' );
		$method->setAccessible( true );
		return wp_list_pluck( $method->invoke( Manager::get_instance() ), 'type' );
	}

	protected function arm_marker() {
		$marker = wp_generate_password( 40, false, false );
		set_transient( SelfTest::MARKER_TRANSIENT, hash( 'sha256', $marker ), 30 );
		return $marker;
	}

	public function test_a_client_using_the_token_shows_the_row() {
		$this->assertSame( 200, $this->call()->get_status() );
		$this->assertGreaterThan( 0, $this->last_used() );
		$this->assertContains( 'pairing', $this->app_types() );
	}

	public function test_the_self_test_authenticates_but_does_not_count_as_usage() {
		$response = $this->call( $this->arm_marker() );
		$this->assertSame( 200, $response->get_status() );
		$this->assertNotEmpty( $response->get_data()['result']['tools'] );
		$this->assertSame( 0, $this->last_used() );
		$this->assertNotContains( 'pairing', $this->app_types() );
	}

	public function test_the_marker_is_single_use() {
		$marker = $this->arm_marker();
		$this->call( $marker );
		$this->assertFalse( get_transient( SelfTest::MARKER_TRANSIENT ) );
		$this->call( $marker ); // Replayed: a normal client call now.
		$this->assertGreaterThan( 0, $this->last_used() );
	}

	public function test_a_forged_marker_still_counts_as_usage() {
		$this->call( 'not-the-servers-marker' );
		$this->assertGreaterThan( 0, $this->last_used() );
	}

	public function test_rotating_the_token_clears_its_usage() {
		$this->call();
		$this->assertGreaterThan( 0, $this->last_used() );
		Pairing::get_instance()->rotate();
		$this->assertSame( 0, $this->last_used() );
		$this->assertNotContains( 'pairing', $this->app_types() );
	}
}
