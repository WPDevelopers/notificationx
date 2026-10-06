<?php
/**
 * Tests for the Analytics dashboard reports (Core\Rest\AnalyticsReports) and
 * the counting fixes that came with them.
 *
 * @package Notificationx
 */

use NotificationX\Core\Analytics;
use NotificationX\Core\Database;
use NotificationX\Core\PostType;
use NotificationX\Core\Rest\AnalyticsReports;

class Test_Analytics_Reports extends WP_UnitTestCase {

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
				'title'   => 'reports test bar',
			)
		);
		$this->nx_id = (int) $saved['nx_id'];
		$admin       = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
	}

	/** Run the reports as Pro (true), Free (false) or the real check (null). */
	protected function set_pro( $pro ) {
		$prop = new ReflectionProperty( AnalyticsReports::class, 'pro_override' );
		$prop->setAccessible( true );
		$prop->setValue( null, $pro );
	}

	public function tearDown(): void {
		$this->set_pro( null );
		parent::tearDown();
	}


	protected function add_stats( $date, $views, $clicks, $nx_id = null ) {
		global $wpdb;
		$wpdb->insert(
			Database::$table_stats,
			array(
				'nx_id'      => $nx_id ? $nx_id : $this->nx_id,
				'views'      => (string) $views,
				'clicks'     => (string) $clicks,
				'created_at' => $date,
			)
		);
	}

	protected function get( $route, $params = array() ) {
		$request = new WP_REST_Request( 'GET', '/notificationx/v1/analytics/report/' . $route );
		foreach ( $params as $k => $v ) {
			$request->set_param( $k, $v );
		}
		return rest_get_server()->dispatch( $request );
	}

	public function test_window_presets_include_today() {
		$w = AnalyticsReports::get_instance()->resolve_window( '7' );
		$this->assertSame( gmdate( 'Y-m-d' ), $w['end'] );
		$this->assertSame( gmdate( 'Y-m-d', strtotime( '-6 days' ) ), $w['start'] );
		$this->assertSame( 7, $w['days'] );
	}

	public function test_free_falls_back_to_seven_days_for_pro_ranges() {
		// The test environment runs without Pro.
		foreach ( array( '90', 'all', 'custom:2026-01-01:2026-01-31', 'nonsense' ) as $token ) {
			$this->assertSame( '7', AnalyticsReports::get_instance()->resolve_window( $token )['token'], $token );
		}
		$this->assertSame( '30', AnalyticsReports::get_instance()->resolve_window( '30' )['token'] );
	}

	public function test_previous_window_has_the_same_length_and_ends_the_day_before() {
		$r    = AnalyticsReports::get_instance();
		$w    = $r->resolve_window( '30' );
		$prev = $r->previous_window( $w );
		$this->assertSame( 30, $prev['days'] );
		$this->assertSame( gmdate( 'Y-m-d', strtotime( $w['start'] . ' -1 day' ) ), $prev['end'] );
	}

	public function test_change_is_null_without_a_baseline_and_ctr_is_a_percent() {
		$r = AnalyticsReports::get_instance();
		$this->assertNull( $r->change( 10, 0 ) );
		$this->assertSame( 50.0, $r->change( 15, 10 ) );
		$this->assertSame( -25.0, $r->change( 30, 40 ) );
		$this->assertSame( 5.0, $r->ctr( 5, 100 ) );
		// Two decimals, like the all-time CTR card (6.09%).
		$this->assertSame( 6.09, $r->ctr( 1075, 17662 ) );
		$this->assertSame( 0, $r->ctr( 5, 0 ) );
	}

	public function test_summary_totals_match_the_rows_inside_the_window_only() {
		$today = gmdate( 'Y-m-d' );
		$this->add_stats( $today, 40, 2 );
		$this->add_stats( gmdate( 'Y-m-d', strtotime( '-3 days' ) ), 60, 3 );
		$this->add_stats( gmdate( 'Y-m-d', strtotime( '-20 days' ) ), 900, 90 ); // Outside 7 days.
		$data = $this->get( 'summary', array( 'range' => '7' ) )->get_data();
		$this->assertSame( 100, $data['totals']['views'] );
		$this->assertSame( 5, $data['totals']['clicks'] );
		$this->assertSame( 5.0, $data['totals']['ctr'] );
		$this->assertCount( 7, $data['series'] );
		$this->assertSame( 40, end( $data['series'] )['views'] );
		$this->assertNull( $data['changes'] ); // Compare is Pro.
		// Free: basic totals and trend only.
		$this->assertSame( array(), $data['top'] );
		$this->assertSame( array(), $data['types'] );
		$this->assertTrue( $data['locked'] );
		// Pro gets the breakdowns.
		$this->set_pro( true );
		$data = $this->get( 'summary', array( 'range' => '7' ) )->get_data();
		$this->assertSame( $this->nx_id, $data['top'][0]['nx_id'] );
		$this->assertFalse( $data['locked'] );
	}

	public function test_free_ignores_the_notification_filter() {
		$this->add_stats( gmdate( 'Y-m-d' ), 10, 1 );
		$this->add_stats( gmdate( 'Y-m-d' ), 30, 3, 999 );
		$this->assertSame( 40, $this->get( 'summary', array( 'nx_id' => $this->nx_id ) )->get_data()['totals']['views'] );
		$this->set_pro( true );
		$this->assertSame( 10, $this->get( 'summary', array( 'nx_id' => $this->nx_id ) )->get_data()['totals']['views'] );
	}

	public function test_reports_need_the_analytics_capability() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertSame( 403, $this->get( 'summary' )->get_status() );
		$this->assertSame( 403, $this->get( 'notifications' )->get_status() );
	}

	public function test_export_is_pro_only() {
		$this->assertSame( 403, $this->get( 'export' )->get_status() );
	}

	public function test_csv_cells_neutralise_formulas() {
		$r = AnalyticsReports::get_instance();
		$this->assertSame( '"\'=HYPERLINK(""x"")"', $r->csv_cell( '=HYPERLINK("x")' ) );
		$this->assertSame( '"-5"', $r->csv_cell( '-5' ) );
		$this->assertSame( '"Bar ""A"""', $r->csv_cell( 'Bar "A"' ) );
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
