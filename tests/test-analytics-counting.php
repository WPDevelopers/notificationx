<?php
/**
 * Tests for how views and clicks are counted (Core\Analytics, POST /analytics).
 *
 * @package Notificationx
 */

use NotificationX\Core\Analytics;
use NotificationX\Core\Database;
use NotificationX\Core\PostType;

class Test_Analytics_Counting extends WP_UnitTestCase {

	protected $nx_id;

	public function setUp(): void {
		parent::setUp();
		global $wpdb;
		$wpdb->query( 'DELETE FROM ' . Database::$table_stats );
		$saved       = PostType::get_instance()->save_post(
			array(
				'type'    => 'notification_bar',
				'source'  => 'press_bar',
				'themes'  => 'press_bar_theme-one',
				'enabled' => true,
				'title'   => 'counting test bar',
			)
		);
		$this->nx_id = (int) $saved['nx_id'];
	}

	public function test_first_click_of_the_day_does_not_add_a_view() {
		global $wpdb;
		Analytics::get_instance()->insert_analytics( $this->nx_id, 'clicks' );
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT views, clicks FROM ' . Database::$table_stats . ' WHERE nx_id = %d', $this->nx_id ), ARRAY_A );
		$this->assertSame( 0, (int) $row['views'] );
		$this->assertSame( 1, (int) $row['clicks'] );
	}

	public function test_click_endpoint_rejects_unknown_notifications_and_derived_types() {
		global $wpdb;
		$request = new WP_REST_Request( 'POST', '/notificationx/v1/analytics' );
		$request->set_param( 'nx_id', 999999 );
		$this->assertSame( 404, rest_get_server()->dispatch( $request )->get_status() );

		$request = new WP_REST_Request( 'POST', '/notificationx/v1/analytics' );
		$request->set_param( 'nx_id', $this->nx_id );
		$request->set_param( 'type', 'ctr' );
		$this->assertSame( 200, rest_get_server()->dispatch( $request )->get_status() );
		// 'ctr' is treated as a click, never as a column name.
		$this->assertSame( 1, (int) $wpdb->get_var( $wpdb->prepare( 'SELECT SUM(clicks) FROM ' . Database::$table_stats . ' WHERE nx_id = %d', $this->nx_id ) ) );
	}
}
