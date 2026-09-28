<?php
/**
 * Tests for the public `/bar-height` endpoint (FrontEnd\BarSpace).
 *
 * The endpoint is open to anonymous visitors, so a single client must not be
 * able to set the gap every visitor sees above the page.
 *
 * @package Notificationx
 */

use NotificationX\Core\PostType;
use NotificationX\FrontEnd\BarSpace;

class Test_Bar_Space extends WP_UnitTestCase {

	protected $nx_id;

	public function setUp(): void {
		parent::setUp();
		$saved       = PostType::get_instance()->save_post(
			array(
				'type'          => 'notification_bar',
				'source'        => 'press_bar',
				'themes'        => 'press_bar_theme-one',
				'enabled'       => true,
				'position'      => 'top',
				// Delayed bars reserve no space; the default delay is 5 seconds.
				'initial_delay' => 0,
				'title'         => 'bar space test',
			)
		);
		$this->nx_id = (int) $saved['nx_id'];
		delete_option( BarSpace::OPTION );
	}

	protected function report( $height, $ip, $width = 1500 ) {
		$_SERVER['REMOTE_ADDR'] = $ip;
		$request                = new WP_REST_Request( 'POST', '/notificationx/v1/bar-height' );
		$request->set_param( 'nx_id', $this->nx_id );
		$request->set_param( 'width', $width );
		$request->set_param( 'height', $height );
		return BarSpace::get_instance()->report_height( $request )->get_data();
	}

	protected function stored( $bucket = 'xl' ) {
		$heights = get_option( BarSpace::OPTION, array() );
		return isset( $heights[ $this->nx_id ]['h'][ $bucket ] ) ? $heights[ $this->nx_id ]['h'][ $bucket ] : null;
	}

	public function test_one_report_does_not_set_a_height() {
		$this->report( 60, '10.0.0.1' );
		$this->assertNull( $this->stored() );
	}

	public function test_agreeing_reports_set_the_height() {
		$this->report( 60, '10.0.0.1' );
		$this->report( 61, '10.0.0.2' );
		$result = $this->report( 60, '10.0.0.3' );
		$this->assertTrue( $result['saved'] );
		$this->assertSame( 60, $this->stored() );
	}

	public function test_same_ip_is_throttled() {
		$this->report( 60, '10.0.0.1' );
		$this->report( 60, '10.0.0.1' );
		$this->report( 60, '10.0.0.1' );
		$this->assertNull( $this->stored() );
	}

	public function test_single_attacker_cannot_replace_a_stored_height() {
		foreach ( array( '10.0.0.1', '10.0.0.2', '10.0.0.3' ) as $ip ) {
			$this->report( 60, $ip );
		}
		$this->report( 400, '10.0.0.9' );
		$this->assertSame( 60, $this->stored() );

		// Waiting out the throttle and reporting again only replaces the same
		// IP's own sample, so it never gets more than one vote in the median.
		for ( $i = 0; $i < 4; $i++ ) {
			delete_transient( 'nx_bar_height_' . md5( "10.0.0.9|{$this->nx_id}|xl" ) );
			$this->report( 400, '10.0.0.9' );
		}
		$heights = get_option( BarSpace::OPTION );
		$this->assertSame( array( 60, 60, 60, 400 ), $heights[ $this->nx_id ]['s']['xl'] );
		$this->assertSame( 60, $this->stored() );
	}

	public function test_height_is_replaced_once_reports_agree() {
		foreach ( array( '10.0.0.1', '10.0.0.2', '10.0.0.3' ) as $ip ) {
			$this->report( 60, $ip );
		}
		foreach ( array( '10.0.0.4', '10.0.0.5', '10.0.0.6' ) as $ip ) {
			$this->report( 90, $ip );
		}
		$this->assertSame( 90, $this->stored() );
	}

	public function test_option_is_autoloaded() {
		foreach ( array( '10.0.0.1', '10.0.0.2', '10.0.0.3' ) as $ip ) {
			$this->report( 60, $ip );
		}
		global $wpdb;
		$autoload = $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", BarSpace::OPTION ) );
		$this->assertContains( $autoload, array( 'yes', 'on', 'auto-on' ) );
	}

	public function test_reserve_prints_close_cookie_check() {
		foreach ( array( '10.0.0.1', '10.0.0.2', '10.0.0.3' ) as $ip ) {
			$this->report( 60, $ip );
		}
		ob_start();
		BarSpace::get_instance()->print_reserve( array( $this->nx_id ) );
		$html = ob_get_clean();
		$this->assertStringContainsString( 'id="nx-bar-reserve"', $html );
		$this->assertStringContainsString( '("notificationx_' . $this->nx_id, $html );
		$this->assertStringContainsString( 'nx-bar-reserve-off', $html );
	}
}
