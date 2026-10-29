<?php
/**
 * REST-level regression tests for the Zapier / IFTTT webhook routes.
 *
 * `tests/test-security-integration-key.php` covers the seeding rules of
 * Integration::maybe_seed_from_upgrade() by calling Integration::is_valid_api_key()
 * directly. This file dispatches real requests through the REST server, so the
 * public `permission_callback`, the argument schema, the `api_key` handling in
 * Integration::save_response() and the entry actions are exercised end to end,
 * on both the versioned `notificationx/v1` route and the un-versioned
 * `notificationx` fallback that old Zaps still call.
 *
 * @package Notificationx
 */

use NotificationX\Core\Database;
use NotificationX\Core\Rest\Integration;

class Test_Security_Integration_Key_Rest extends WP_UnitTestCase {

	/** @var WP_REST_Server */
	protected $server;

	/** @var array Calls received by the `nx_api_response_success` action. */
	protected $received = array();

	public function setUp(): void {
		parent::setUp();
		foreach ( array( Integration::OPT_API_KEY, Integration::OPT_GRACE_STARTED_AT, Integration::OPT_LEGACY_KEY_LAST_USED, Integration::OPT_SEEDED_VERSION, Integration::OPT_GRACE_FIRST_SEED ) as $option ) {
			delete_option( $option );
		}
		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );
		wp_set_current_user( 0 );
		$this->received = array();
		add_action( 'nx_api_response_success', array( $this, 'record' ) );
	}

	public function tearDown(): void {
		remove_action( 'nx_api_response_success', array( $this, 'record' ) );
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tearDown();
	}

	public function record( $data ) {
		$this->received[] = $data;
	}

	protected function legacy_keys() {
		return array( md5( home_url( '', 'http' ) ), md5( home_url( '', 'https' ) ) );
	}

	protected function create_zapier_notification() {
		global $wpdb;
		$wpdb->insert(
			Database::$table_posts,
			array(
				'title'      => 'Zapier sales',
				'type'       => 'conversions',
				'source'     => 'zapier_conversions',
				'theme'      => 'conversions_theme-one',
				'enabled'    => 1,
				'data'       => '',
				'created_at' => current_time( 'mysql' ),
				'updated_at' => current_time( 'mysql' ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * Sends the webhook POST an integration (or an attacker) would send, logged out.
	 */
	protected function post_entry( $id, $api_key, $namespace = 'notificationx/v1' ) {
		$request = new WP_REST_Request( 'POST', "/{$namespace}/notification/{$id}" );
		$request->set_body_params(
			array(
				'api_key' => $api_key,
				'name'    => 'Test',
				'title'   => '<img src=x onerror=alert(1)>Test product',
			)
		);
		return $this->server->dispatch( $request );
	}

	protected function get_check( $id, $api_key, $namespace = 'notificationx/v1' ) {
		$request = new WP_REST_Request( 'GET', "/{$namespace}/notification/{$id}" );
		$request->set_query_params( array( 'api_key' => $api_key ) );
		return $this->server->dispatch( $request );
	}

	/**
	 * The reported scenario: a site that already has the random key adds a Zapier
	 * notification and then updates. The legacy key must be rejected on both routes.
	 */
	public function test_legacy_key_is_rejected_after_update_on_site_that_already_had_random_key() {
		update_option( Integration::OPT_SEEDED_VERSION, '3.3.4', false );
		Integration::get_api_key();
		$id = $this->create_zapier_notification();
		Integration::maybe_seed_from_upgrade();

		foreach ( array( 'notificationx/v1', 'notificationx' ) as $namespace ) {
			foreach ( array_merge( $this->legacy_keys(), array( '', 'wrong' ) ) as $key ) {
				$response = $this->post_entry( $id, $key, $namespace );
				$data     = $response->get_data();
				$this->assertSame( 200, $response->get_status(), "{$namespace} key={$key}" );
				$this->assertNotEmpty( $data['error'], "{$namespace} key={$key}" );
				$this->assertSame( '', $data['data'], "{$namespace} key={$key}" );
			}
		}
		$this->assertSame( array(), $this->received, 'no entry action fired' );
		$this->assertEmpty( get_option( Integration::OPT_LEGACY_KEY_LAST_USED ) );
	}

	public function test_random_key_is_accepted_and_payload_is_sanitized() {
		update_option( Integration::OPT_SEEDED_VERSION, '3.3.4', false );
		$key = Integration::get_api_key();
		$id  = $this->create_zapier_notification();
		Integration::maybe_seed_from_upgrade();

		$source_calls = array();
		add_action(
			'nx_api_response_success_zapier_conversions',
			function ( $data ) use ( &$source_calls ) {
				$source_calls[] = $data;
			}
		);

		$response = $this->post_entry( $id, $key );
		$data     = $response->get_data();

		$this->assertFalse( $data['error'] );
		$this->assertArrayNotHasKey( 'api_key', $data['data'], 'the key is never echoed back' );
		$this->assertSame( 'Test product', $data['data']['title'], 'markup is stripped before any extension sees it' );
		$this->assertCount( 1, $this->received );
		$this->assertCount( 1, $source_calls );
		$this->assertSame( (string) $id, (string) $source_calls[0]['id'] );
	}

	public function test_legacy_key_works_only_inside_a_window_opened_for_a_legacy_site() {
		// Pre-random-key site: no seed marker, no key, a Zapier notification.
		$id = $this->create_zapier_notification();
		Integration::maybe_seed_from_upgrade();

		foreach ( $this->legacy_keys() as $key ) {
			$response = $this->post_entry( $id, $key );
			$this->assertFalse( $response->get_data()['error'], "legacy key {$key} accepted inside the window" );
		}
		$this->assertCount( 2, $this->received );
		$this->assertNotEmpty( get_option( Integration::OPT_LEGACY_KEY_LAST_USED ) );

		// Window over.
		update_option( Integration::OPT_GRACE_STARTED_AT, time() - Integration::LEGACY_KEY_GRACE_DAYS * DAY_IN_SECONDS, false );
		$response = $this->post_entry( $id, $this->legacy_keys()[0] );
		$this->assertNotEmpty( $response->get_data()['error'] );
		$this->assertCount( 2, $this->received, 'nothing stored after the window closed' );
	}

	public function test_window_left_open_by_an_earlier_version_is_closed_by_the_update() {
		// What 3.2.11 - 3.3.5 left behind on a site that already had the random key.
		update_option( Integration::OPT_SEEDED_VERSION, '3.3.4', false );
		$key = Integration::get_api_key();
		$id  = $this->create_zapier_notification();
		update_option( Integration::OPT_GRACE_STARTED_AT, time() - 2 * DAY_IN_SECONDS, false );
		$this->assertFalse( $this->post_entry( $id, $this->legacy_keys()[0] )->get_data()['error'], 'vulnerable state before the update' );

		Integration::maybe_seed_from_upgrade();

		$this->assertNotEmpty( $this->post_entry( $id, $this->legacy_keys()[0] )->get_data()['error'] );
		$this->assertFalse( $this->post_entry( $id, $key )->get_data()['error'] );
		$this->assertCount( 2, $this->received );
	}

	public function test_unknown_notification_id_with_valid_key_raises_no_warning() {
		update_option( Integration::OPT_SEEDED_VERSION, '3.3.4', false );
		$key = Integration::get_api_key();
		Integration::maybe_seed_from_upgrade();

		// phpunit.xml.dist converts warnings to exceptions, so a null-offset
		// access in save_response() would fail this test.
		$response = $this->post_entry( 999999, $key );
		$data     = $response->get_data();

		$this->assertFalse( $data['error'] );
		$this->assertCount( 1, $this->received, 'the generic action (IFTTT) still fires' );
	}
}
