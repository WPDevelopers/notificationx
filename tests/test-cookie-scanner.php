<?php
/**
 * Cookie Scanner status route (issue #224).
 *
 * The status route must give the builder an honest reply it can stop on:
 * a 400 for a missing or empty scan ID (before any call to the scanner API),
 * a 502 with a generic message when the API cannot be reached or gives no
 * usable reply, and the API's own status otherwise. A completed scan keeps
 * its reply shape and still adds one to `nx_scan_count`.
 *
 * The external scanner API is never called: every outgoing HTTP request is
 * answered by the `pre_http_request` stub below.
 *
 * @package Notificationx
 */

class Test_Cookie_Scanner extends WP_UnitTestCase {

	const ROUTE = '/notificationx/v1/scan/status';

	/** @var WP_REST_Server */
	protected $server;

	/** @var string[] URLs of the outgoing requests the stub answered. */
	protected $http_calls = array();

	/** @var array|WP_Error What the stub answers. */
	protected $http_reply;

	public function setUp(): void {
		parent::setUp();
		delete_option( 'nx_scan_count' );
		delete_option( 'nx_scan_date' );
		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );

		$this->http_calls = array();
		$this->http_reply = new WP_Error( 'http_request_failed', 'No reply stubbed.' );
		add_filter( 'pre_http_request', array( $this, 'fake_http' ), 10, 3 );

		$user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		get_user_by( 'id', $user )->add_cap( 'edit_notificationx' );
		wp_set_current_user( $user );
	}

	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'fake_http' ), 10 );
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tearDown();
	}

	public function fake_http( $pre, $args, $url ) {
		$this->http_calls[] = $url;
		return $this->http_reply;
	}

	protected function api_reply( $code, $body ) {
		return array(
			'headers'  => array(),
			'body'     => $body,
			'response' => array( 'code' => $code, 'message' => '' ),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	protected function check_status( $params ) {
		$request = new WP_REST_Request( 'POST', self::ROUTE );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $params ) );
		return $this->server->dispatch( $request );
	}

	public function test_missing_scan_id_is_rejected_without_calling_the_api() {
		$response = $this->check_status( array() );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( array(), $this->http_calls );
	}

	public function test_blank_or_too_long_scan_id_is_rejected_without_calling_the_api() {
		foreach ( array( '', '   ', str_repeat( 'a', 129 ), array( 'x' ) ) as $scan_id ) {
			$response = $this->check_status( array( 'scan_id' => $scan_id ) );
			$this->assertSame( 400, $response->get_status(), 'scan_id: ' . wp_json_encode( $scan_id ) );
		}
		$this->assertSame( array(), $this->http_calls );
	}

	public function test_scan_id_empty_after_cleaning_gets_the_failed_shape() {
		$response = $this->check_status( array( 'scan_id' => '<b></b>' ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'failed', $response->get_data()['data']['status'] );
		$this->assertSame( array(), $this->http_calls );
	}

	public function test_scan_id_is_url_encoded_in_the_api_url() {
		$this->http_reply = $this->api_reply( 200, '{"status":"pending"}' );

		$this->check_status( array( 'scan_id' => 'a b&c=d' ) );

		$this->assertCount( 1, $this->http_calls );
		$this->assertStringEndsWith( '/status.php?scan_id=a+b%26c%3Dd', $this->http_calls[0] );
	}

	public function test_numeric_scan_id_is_accepted() {
		$this->http_reply = $this->api_reply( 200, '{"status":"pending"}' );

		$response = $this->check_status( array( 'scan_id' => 12345 ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertStringEndsWith( 'scan_id=12345', $this->http_calls[0] );
	}

	public function test_unreachable_api_gives_502_with_a_generic_message() {
		$this->http_reply = new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out after 200001 milliseconds' );

		$response = $this->check_status( array( 'scan_id' => 'scan-1' ) );
		$data     = $response->get_data()['data'];

		$this->assertSame( 502, $response->get_status() );
		$this->assertSame( 'failed', $data['status'] );
		$this->assertStringNotContainsString( 'cURL', $data['message'] );
		$this->assertSame( array( 'status', 'message' ), array_keys( $data ) );
	}

	public function test_non_2xx_or_non_json_api_reply_gives_502() {
		$replies = array(
			$this->api_reply( 500, '{"status":"completed"}' ),
			$this->api_reply( 404, '{"status":"failed"}' ),
			$this->api_reply( 200, '<html>Bad gateway</html>' ),
			$this->api_reply( 200, '' ),
			$this->api_reply( 200, '"just a string"' ),
		);
		foreach ( $replies as $reply ) {
			$this->http_reply = $reply;
			$response         = $this->check_status( array( 'scan_id' => 'scan-1' ) );
			$this->assertSame( 502, $response->get_status(), 'reply: ' . $reply['response']['code'] . ' ' . $reply['body'] );
			$this->assertSame( 'failed', $response->get_data()['data']['status'] );
		}
		$this->assertFalse( get_option( 'nx_scan_count' ) );
	}

	public function test_api_status_other_than_completed_passes_through() {
		foreach ( array( 'pending', 'running', 'failed' ) as $status ) {
			$this->http_reply = $this->api_reply( 200, wp_json_encode( array( 'status' => $status ) ) );
			$response         = $this->check_status( array( 'scan_id' => 'scan-1' ) );
			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( array( 'status' => $status ), $response->get_data()['data'] );
		}
		$this->assertFalse( get_option( 'nx_scan_count' ) );
	}

	public function test_completed_scan_keeps_its_reply_and_adds_one_to_the_count() {
		$cookies          = array(
			array( 'name' => '_ga', 'domain' => 'example.com', 'expires' => -1 ),
			array( 'name' => 'wordpress_logged_in_x', 'domain' => 'example.com', 'expires' => -1 ),
		);
		$stats            = array( 'total_cookies' => 2 );
		$this->http_reply = $this->api_reply( 200, wp_json_encode( array( 'status' => 'completed', 'result' => $cookies, 'stats' => $stats ) ) );

		$response = $this->check_status( array( 'scan_id' => 'scan-1' ) );
		$data     = $response->get_data()['data'];

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'completed', $data['status'] );
		$this->assertSame( $cookies, $data['cookies'] );
		$this->assertSame( $stats, $data['stats'] );
		$this->assertSame( 2, $data['category_count'] );
		$this->assertSame( 1, $data['categorized']['analytics'] );
		$this->assertSame( 1, $data['categorized']['necessary'] );
		$this->assertNotEmpty( $data['last_scan_date'] );
		$this->assertEquals( 1, get_option( 'nx_scan_count' ) );
	}
}
