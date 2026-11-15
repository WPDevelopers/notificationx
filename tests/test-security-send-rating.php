<?php
/**
 * Regression tests for the public `send-rating` route.
 *
 * `POST /notificationx/v1/send-rating` emails the rating and feedback text to
 * support@wpdeveloper.com and sets `nx_feedback_shared`, which hides the
 * "Rate NotificationX" widget. It had no permission check, so any visitor could
 * make any site send that email and hide the widget.
 *
 * The fix: the route requires `read_notificationx`, and the option is set only
 * after the rating is validated.
 *
 * @package Notificationx
 */

use NotificationX\Admin\Rating\RatingEmail;

class Test_Security_Send_Rating extends WP_UnitTestCase {

	/**
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Emails wp_mail() was asked to send.
	 *
	 * @var array
	 */
	protected $mails = array();

	public function setUp(): void {
		parent::setUp();

		RatingEmail::get_instance();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );

		delete_option( 'nx_feedback_shared' );
		$this->mails = array();
		add_filter( 'pre_wp_mail', array( $this, 'capture_mail' ), 10, 2 );
	}

	public function tearDown(): void {
		remove_filter( 'pre_wp_mail', array( $this, 'capture_mail' ), 10 );
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tearDown();
	}

	public function capture_mail( $return, $atts ) {
		$this->mails[] = $atts;
		return true;
	}

	protected function send( $params ) {
		$request = new WP_REST_Request( 'POST', '/notificationx/v1/send-rating' );
		$request->set_body_params( $params );
		return $this->server->dispatch( $request );
	}

	protected function login_with( $cap = 'read_notificationx' ) {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$user    = get_user_by( 'id', $user_id );
		if ( $cap ) {
			$user->add_cap( $cap );
		}
		wp_set_current_user( $user_id );
		return $user_id;
	}

	public function test_anonymous_request_is_rejected_and_sends_nothing() {
		wp_set_current_user( 0 );

		$response = $this->send( array( 'rating' => 1, 'review' => 'fake feedback' ) );

		$this->assertSame( 401, $response->get_status() );
		$this->assertCount( 0, $this->mails );
		$this->assertFalse( get_option( 'nx_feedback_shared' ) );
	}

	public function test_user_without_notificationx_access_is_rejected() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$response = $this->send( array( 'rating' => 1, 'review' => 'fake feedback' ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertCount( 0, $this->mails );
		$this->assertFalse( get_option( 'nx_feedback_shared' ) );
	}

	public function test_notificationx_user_can_send_a_rating() {
		$this->login_with();

		$response = $this->send( array( 'rating' => 2, 'review' => 'Needs a dark theme' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, $this->mails );
		$this->assertSame( 'support@wpdeveloper.com', $this->mails[0]['to'] );
		$this->assertStringContainsString( 'Needs a dark theme', $this->mails[0]['message'] );
		$this->assertTrue( (bool) get_option( 'nx_feedback_shared' ) );
	}

	public function test_five_star_rating_without_text_is_sent() {
		$this->login_with();

		$response = $this->send( array( 'rating' => 5 ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, $this->mails );
	}

	public function test_invalid_rating_does_not_hide_the_widget() {
		$this->login_with();

		$response = $this->send( array( 'rating' => 0 ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertCount( 0, $this->mails );
		$this->assertFalse( get_option( 'nx_feedback_shared' ) );
	}
}
