<?php
/**
 * With "Enable Analytics" off the front end writes no views. The Analytics
 * page, the dashboard widget, the report email and the click route are off in
 * that state, so the rows were never read.
 *
 * @package NotificationX
 */

use NotificationX\Admin\Settings;
use NotificationX\Core\Analytics;
use NotificationX\Core\Database;
use NotificationX\Core\PostType;
use NotificationX\Core\Rest\Analytics as AnalyticsRoute;
use NotificationX\FrontEnd\FrontEnd;

class Test_Analytics_Disabled extends WP_UnitTestCase {

	/** @var mixed The setting before the test. */
	private $was_enabled;

	public function set_up() {
		parent::set_up();
		$this->was_enabled = Settings::get_instance()->get( 'settings.enable_analytics', true );
		// Inline notifications (Pro) switch counting off for the rest of a request.
		unset( $GLOBALS['wp_actions']['nx_ignore_analytics'] );
	}

	public function tear_down() {
		Settings::get_instance()->set( 'settings.enable_analytics', $this->was_enabled );
		parent::tear_down();
	}

	private function set_enabled( $enabled ) {
		Settings::get_instance()->set( 'settings.enable_analytics', $enabled );
	}

	/** An enabled Notification Bar with content. */
	private function insert_bar() {
		$saved = PostType::get_instance()->save_post(
			array(
				'type'          => 'notification_bar',
				'source'        => 'press_bar',
				'themes'        => 'press_bar_theme-one',
				'enabled'       => true,
				'title'         => 'analytics test',
				'press_content' => 'Analytics test bar',
			)
		);
		$this->assertNotEmpty( $saved['nx_id'] );
		return (int) $saved['nx_id'];
	}

	/** What a front-end `notice` request does for this bar. */
	private function request_notice( $nx_id ) {
		return FrontEnd::get_instance()->get_notifications_data( array( 'pressbar' => array( $nx_id ) ) );
	}

	private function stat_rows( $nx_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- plugin table name.
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . Database::$table_stats . ' WHERE nx_id = %d', $nx_id ) );
	}

	private function views( $nx_id ) {
		return (int) Analytics::get_instance()->get_count( $nx_id, 'views' );
	}

	public function test_enabled_counts_a_view_per_notice_request() {
		$this->set_enabled( true );
		$nx_id = $this->insert_bar();

		$result = $this->request_notice( $nx_id );
		$this->assertArrayHasKey( $nx_id, $result['pressbar'], 'The bar is not in the response, so the test proves nothing.' );
		$this->assertSame( 1, $this->views( $nx_id ) );

		$this->request_notice( $nx_id );
		$this->assertSame( 2, $this->views( $nx_id ) );
	}

	public function test_disabled_writes_no_view() {
		$this->set_enabled( false );
		$nx_id = $this->insert_bar();

		$result = $this->request_notice( $nx_id );

		$this->assertArrayHasKey( $nx_id, $result['pressbar'], 'Turning analytics off must not hide the notification.' );
		$this->assertSame( 0, $this->stat_rows( $nx_id ) );
	}

	public function test_disabled_runs_no_stats_query() {
		global $wpdb;
		$this->set_enabled( false );
		$nx_id = $this->insert_bar();

		$queries = array();
		$log     = function ( $query ) use ( &$queries ) {
			$queries[] = $query;
			return $query;
		};
		add_filter( 'query', $log );
		$this->request_notice( $nx_id );
		remove_filter( 'query', $log );

		$stats = array_filter(
			$queries,
			function ( $query ) {
				return false !== strpos( $query, Database::$table_stats );
			}
		);
		$this->assertSame( array(), array_values( $stats ) );
	}

	public function test_response_is_the_same_with_analytics_on_and_off() {
		$nx_id = $this->insert_bar();

		$this->set_enabled( true );
		$on = $this->request_notice( $nx_id );
		$this->set_enabled( false );
		$off = $this->request_notice( $nx_id );

		// The response says whether the front end should send clicks.
		$this->assertTrue( $on['settings']['enable_analytics'] );
		$this->assertFalse( $off['settings']['enable_analytics'] );
		unset( $on['settings'], $off['settings'] );
		$this->assertSame( $on, $off );
	}

	public function test_counting_resumes_when_enabled_again() {
		$nx_id = $this->insert_bar();

		$this->set_enabled( false );
		$this->request_notice( $nx_id );
		$this->set_enabled( true );
		$this->request_notice( $nx_id );

		$this->assertSame( 1, $this->views( $nx_id ) );
	}

	public function test_insert_analytics_reports_nothing_written_when_disabled() {
		$this->set_enabled( false );
		$nx_id = $this->insert_bar();

		$this->assertFalse( Analytics::get_instance()->insert_analytics( $nx_id, 'clicks' ) );
		$this->assertFalse( Analytics::get_instance()->insert_analytics( $nx_id, 'views' ) );
		$this->assertSame( 0, $this->stat_rows( $nx_id ) );
	}

	/** Unchanged, and the reason nothing reads the rows: the click route is closed too. */
	public function test_click_route_is_closed_when_disabled() {
		$route = AnalyticsRoute::get_instance();

		$this->set_enabled( false );
		$this->assertFalse( (bool) $route->can_insert_analytics( new WP_REST_Request() ) );
		$this->set_enabled( true );
		$this->assertTrue( (bool) $route->can_insert_analytics( new WP_REST_Request() ) );
	}

	public function test_is_enabled_follows_the_setting() {
		$this->set_enabled( true );
		$this->assertTrue( Analytics::get_instance()->is_enabled() );
		$this->set_enabled( false );
		$this->assertFalse( Analytics::get_instance()->is_enabled() );
	}
}
