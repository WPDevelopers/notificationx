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

	public function tearDown(): void {
		unset( $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_X_REAL_IP'] );
		parent::tearDown();
	}

	protected function report( $height, $ip, $width = 1500, $forwarded = null ) {
		$_SERVER['REMOTE_ADDR'] = $ip;
		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		if ( null !== $forwarded ) {
			$_SERVER['HTTP_X_FORWARDED_FOR'] = $forwarded;
		}
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

		// Waiting out the throttle and reporting again does not help either: a
		// report far above the reserved height never gets a vote.
		for ( $i = 0; $i < 4; $i++ ) {
			delete_transient( BarSpace::throttle_key( '10.0.0.9', $this->nx_id, 'xl' ) );
			$this->report( 400, '10.0.0.9' );
		}
		$heights = get_option( BarSpace::OPTION );
		$this->assertSame( array( 60, 60, 60 ), $heights[ $this->nx_id ]['s']['xl'] );
		$this->assertSame( 60, $this->stored() );
	}

	public function test_several_attackers_cannot_raise_a_stored_height() {
		foreach ( array( '10.0.0.1', '10.0.0.2', '10.0.0.3' ) as $ip ) {
			$this->report( 60, $ip );
		}
		foreach ( array( '10.0.9.1', '10.0.9.2', '10.0.9.3', '10.0.9.4' ) as $ip ) {
			$this->report( 400, $ip );
		}
		$this->assertSame( 60, $this->stored() );
	}

	public function test_lower_reports_still_replace_a_stored_height() {
		foreach ( array( '10.0.0.1', '10.0.0.2', '10.0.0.3' ) as $ip ) {
			$this->report( 60, $ip );
		}
		foreach ( array( '10.0.0.4', '10.0.0.5', '10.0.0.6' ) as $ip ) {
			$this->report( 40, $ip );
		}
		$this->assertSame( 40, $this->stored() );
	}

	public function test_saving_the_bar_forgets_its_heights() {
		foreach ( array( '10.0.0.1', '10.0.0.2', '10.0.0.3' ) as $ip ) {
			$this->report( 60, $ip );
		}
		$this->assertSame( 60, $this->stored() );

		$post = PostType::get_instance()->get_post( $this->nx_id );
		PostType::get_instance()->save_post( array_merge( $post, array( 'nx_id' => $this->nx_id, 'title' => 'edited' ) ) );

		$heights = get_option( BarSpace::OPTION, array() );
		$this->assertArrayNotHasKey( $this->nx_id, $heights );
	}

	public function test_visitors_behind_a_private_proxy_are_counted_separately() {
		// Every request reaches PHP from the proxy; the proxy appends the visitor.
		$this->report( 60, '127.0.0.1', 1500, '198.51.100.1' );
		$this->report( 60, '127.0.0.1', 1500, '198.51.100.2' );
		$this->report( 60, '127.0.0.1', 1500, '198.51.100.3' );
		$this->assertSame( 60, $this->stored() );
	}

	public function test_only_the_proxy_appended_hop_is_trusted() {
		// A client can prepend anything; only the last hop (added by the proxy) counts.
		$this->report( 60, '10.1.1.1', 1500, '203.0.113.1, 198.51.100.9' );
		$this->report( 60, '10.1.1.1', 1500, '203.0.113.2, 198.51.100.9' );
		$this->report( 60, '10.1.1.1', 1500, '203.0.113.3, 198.51.100.9' );
		$this->assertNull( $this->stored() );
	}

	public function test_forwarded_headers_are_ignored_from_a_public_address() {
		$this->report( 60, '8.8.4.4', 1500, '198.51.100.1' );
		$this->report( 60, '8.8.4.4', 1500, '198.51.100.2' );
		$this->report( 60, '8.8.4.4', 1500, '198.51.100.3' );
		$this->assertNull( $this->stored() );
	}

	public function test_client_ip_can_be_filtered() {
		$filter = function () {
			static $n = 0;
			return '198.51.100.' . ( ++$n );
		};
		add_filter( 'nx_bar_height_client_ip', $filter );
		$this->report( 60, '8.8.4.4' );
		$this->report( 60, '8.8.4.4' );
		$this->report( 60, '8.8.4.4' );
		remove_filter( 'nx_bar_height_client_ip', $filter );
		$this->assertSame( 60, $this->stored() );
	}

	public function test_samples_do_not_store_the_address() {
		$this->report( 60, '10.0.0.1' );
		$heights = get_option( BarSpace::OPTION );
		$this->assertNotSame( substr( md5( '10.0.0.1' ), 0, 8 ), $heights[ $this->nx_id ]['i']['xl'][0] );
	}

	public function test_legacy_option_is_removed_on_write() {
		update_option( 'notificationx_bar_heights', array( $this->nx_id => array( 'h' => array( 'xl' => 99 ) ) ) );
		foreach ( array( '10.0.0.1', '10.0.0.2', '10.0.0.3' ) as $ip ) {
			$this->report( 60, $ip );
		}
		$this->assertFalse( get_option( 'notificationx_bar_heights' ) );
	}

	public function test_reserve_applies_only_when_scripting_is_enabled() {
		foreach ( array( '10.0.0.1', '10.0.0.2', '10.0.0.3' ) as $ip ) {
			$this->report( 60, $ip );
		}
		ob_start();
		BarSpace::get_instance()->print_reserve( array( $this->nx_id ) );
		$html = ob_get_clean();
		$this->assertStringContainsString( '@media (scripting:enabled) and (min-width:1440px){html:not(.nx-bar-reserve-off) body{padding-top:60px}}', $html );
		$this->assertStringNotContainsString( '}html:not(', $html );
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
