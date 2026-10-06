<?php
/**
 * Tests for event tracking (Core\Tracker, POST /track) and the Audience report.
 *
 * @package Notificationx
 */

use NotificationX\Core\Database;
use NotificationX\Core\PostType;
use NotificationX\Core\Tracker;
use NotificationX\Core\Rest\AnalyticsReports;

class Test_Analytics_Tracker extends WP_UnitTestCase {

	protected $nx_id;
	protected $off_id;

	public function setUp(): void {
		parent::setUp();
		global $wpdb;
		$wpdb->query( 'DELETE FROM ' . Database::$table_events );
		$wpdb->query( 'DELETE FROM ' . Database::$table_stats_daily );
		delete_option( 'nx_analytics_rolled_at' );
		$this->nx_id  = $this->notification( true );
		$this->off_id = $this->notification( false );
		$_SERVER['REMOTE_ADDR']     = '203.0.113.' . wp_rand( 1, 250 );
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh) Chrome/124';
		unset( $_SERVER['HTTP_CF_IPCOUNTRY'], $_SERVER['HTTP_X_FORWARDED_FOR'] );
		// The Audience report is Pro; collecting events is not.
		$this->set_pro( true );
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


	protected function notification( $enabled ) {
		$saved = PostType::get_instance()->save_post(
			array(
				'type'    => 'notification_bar',
				'source'  => 'press_bar',
				'themes'  => 'press_bar_theme-one',
				'enabled' => $enabled,
				'title'   => 'tracker test',
			)
		);
		global $wpdb;
		// save_post() may keep only one enabled notification on Free; set it directly.
		$wpdb->update( Database::$table_posts, array( 'enabled' => $enabled ? 1 : 0 ), array( 'nx_id' => $saved['nx_id'] ) );
		return (int) $saved['nx_id'];
	}

	protected function post( $body ) {
		$request = new WP_REST_Request( 'POST', '/notificationx/v1/track' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );
		return rest_get_server()->dispatch( $request );
	}

	protected function batch( $events, $extra = array() ) {
		return array_merge(
			array(
				't'  => Tracker::get_instance()->token(),
				'p'  => 'https://example.org/shop/?utm_campaign=x',
				'r'  => '',
				'w'  => 1280,
				'ev' => $events,
			),
			$extra
		);
	}

	protected function event_count() {
		global $wpdb;
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Database::$table_events );
	}

	public function test_rejects_a_wrong_token() {
		$this->assertSame( 403, $this->post( $this->batch( array( array( 'n' => $this->nx_id, 'e' => 'view' ) ), array( 't' => 'nope' ) ) )->get_status() );
		$this->assertSame( 0, $this->event_count() );
	}

	public function test_stores_valid_events_only_for_enabled_notifications() {
		$res = $this->post(
			$this->batch(
				array(
					array( 'n' => $this->nx_id, 'e' => 'view' ),
					array( 'n' => $this->nx_id, 'e' => 'click' ),
					array( 'n' => $this->off_id, 'e' => 'view' ), // Disabled.
					array( 'n' => 999999, 'e' => 'view' ),        // Missing.
					array( 'n' => $this->nx_id, 'e' => 'hack' ),  // Unknown event.
				)
			)
		);
		$this->assertSame( 202, $res->get_status() );
		$this->assertSame( 2, $res->get_data()['stored'] );

		global $wpdb;
		$row = $wpdb->get_row( 'SELECT * FROM ' . Database::$table_events . ' WHERE event = 1', ARRAY_A );
		$this->assertSame( '/shop/', $row['page'] );
		$this->assertSame( 'desktop', $row['device'] );
		$this->assertSame( 'direct', $row['channel'] );
		$this->assertSame( 16, strlen( $row['visitor'] ) );
		// The IP never reaches the table.
		$this->assertStringNotContainsString( $_SERVER['REMOTE_ADDR'], implode( '|', $row ) );
	}

	public function test_text_plain_beacon_bodies_are_accepted() {
		$request = new WP_REST_Request( 'POST', '/notificationx/v1/track' );
		$request->set_header( 'Content-Type', 'text/plain' );
		$request->set_body( wp_json_encode( $this->batch( array( array( 'n' => $this->nx_id, 'e' => 'view' ) ) ) ) );
		$this->assertSame( 202, rest_get_server()->dispatch( $request )->get_status() );
		$this->assertSame( 1, $this->event_count() );
	}

	public function test_a_batch_is_capped_and_a_visitor_is_rate_limited() {
		$many = array_fill( 0, 50, array( 'n' => $this->nx_id, 'e' => 'click' ) );
		$this->post( $this->batch( $many ) );
		$this->assertSame( Tracker::MAX_BATCH, $this->event_count() );
		for ( $i = 0; $i < 5; $i++ ) {
			$this->post( $this->batch( $many ) );
		}
		$this->assertSame( 120, $this->event_count() );
		$this->assertSame( 429, $this->post( $this->batch( $many ) )->get_status() );
	}

	public function test_classifies_where_visitors_came_from() {
		$t = Tracker::get_instance();
		$this->assertSame( array( 'chatgpt.com', 'ai' ), $t->classify( '', 'https://example.org/?utm_source=chatgpt.com' ) );
		$this->assertSame( array( 'perplexity.ai', 'ai' ), $t->classify( 'https://www.perplexity.ai/search?q=x' ) );
		$this->assertSame( 'Gemini', $t->ai_name( 'gemini.google.com' ) );
		$this->assertSame( array( 'google.co.uk', 'search' ), $t->classify( 'https://www.google.co.uk/' ) );
		$this->assertSame( array( 'l.facebook.com', 'social' ), $t->classify( 'https://l.facebook.com/l.php' ) );
		$this->assertSame( array( 'news.example.net', 'referral' ), $t->classify( 'https://news.example.net/a' ) );
		$this->assertSame( 'internal', $t->classify( home_url( '/blog/' ) )[1] );
		$this->assertSame( array( '', 'direct' ), $t->classify( '' ) );
		$this->assertSame( 'email', $t->classify( '', 'https://example.org/?utm_source=newsletter&utm_medium=email' )[1] );
	}

	public function test_country_comes_from_headers_only() {
		$this->assertSame( '', Tracker::get_instance()->country() );
		$_SERVER['HTTP_CF_IPCOUNTRY'] = 'bd';
		$this->assertSame( 'BD', Tracker::get_instance()->country() );
		$_SERVER['HTTP_CF_IPCOUNTRY'] = 'XX';
		$this->assertSame( '', Tracker::get_instance()->country() );
	}

	public function test_visitor_hash_changes_with_the_daily_salt() {
		$t     = Tracker::get_instance();
		$today = $t->visitor_hash();
		$this->assertSame( $today, $t->visitor_hash() );
		update_option( 'nx_analytics_salt', array( 'day' => '2000-01-01', 'key' => 'old' ) );
		$this->assertNotSame( $today, $t->visitor_hash() );
	}

	public function test_rollup_is_idempotent_and_feeds_the_audience_report() {
		$_SERVER['HTTP_CF_IPCOUNTRY'] = 'BD';
		$this->post( $this->batch( array( array( 'n' => $this->nx_id, 'e' => 'view' ), array( 'n' => $this->nx_id, 'e' => 'click' ) ), array( 'r' => 'https://chatgpt.com/', 'w' => 390 ) ) );
		$_SERVER['REMOTE_ADDR']       = '198.51.100.9';
		$_SERVER['HTTP_CF_IPCOUNTRY'] = 'DE';
		$this->post( $this->batch( array( array( 'n' => $this->nx_id, 'e' => 'view' ), array( 'n' => $this->nx_id, 'e' => 'close' ) ) ) );

		$today = gmdate( 'Y-m-d' );
		Tracker::get_instance()->rollup( $today, $today );
		Tracker::get_instance()->rollup( $today, $today ); // Twice: no double counting.

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$request = new WP_REST_Request( 'GET', '/notificationx/v1/analytics/report/audience' );
		$request->set_param( 'range', '7' );
		$data = rest_get_server()->dispatch( $request )->get_data();

		$this->assertSame( 2, $data['totals']['seen'] );
		$this->assertSame( 1, $data['totals']['clicks'] );
		$this->assertSame( 1, $data['totals']['closes'] );
		$this->assertSame( 2, $data['totals']['visitors'] );
		$this->assertSame( 50.0, $data['totals']['ctr'] );
		$this->assertSame( $today, $data['since'] );
		$this->assertSame( 2, end( $data['series'] )['seen'] );

		$devices = wp_list_pluck( $data['devices'], 'seen', 'key' );
		$this->assertSame( array( 'mobile' => 1, 'desktop' => 1 ), array_intersect_key( $devices, array( 'mobile' => 0, 'desktop' => 0 ) ) );
		$this->assertEqualsCanonicalizing( array( 'BD', 'DE' ), wp_list_pluck( $data['countries'], 'key' ) );
		$this->assertSame( 'Bangladesh', wp_list_pluck( $data['countries'], 'label', 'key' )['BD'] );
		$this->assertSame( 1, $data['ai']['seen'] );
		$this->assertSame( 'ChatGPT', $data['ai']['assistants'][0]['key'] );
		$this->assertSame( 50.0, $data['ai']['share'] );
		$this->assertSame( '/shop/', $data['pages'][0]['key'] );
		$this->assertSame( 'chatgpt.com', $data['sources'][0]['key'] );

		// The notifications report shows the same impressions as "seen".
		$request = new WP_REST_Request( 'GET', '/notificationx/v1/analytics/report/notifications' );
		$rows    = wp_list_pluck( rest_get_server()->dispatch( $request )->get_data()['rows'], 'seen', 'nx_id' );
		$this->assertSame( 2, $rows[ $this->nx_id ] );
	}

	public function test_audience_needs_the_analytics_capability() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$request = new WP_REST_Request( 'GET', '/notificationx/v1/analytics/report/audience' );
		$this->assertSame( 403, rest_get_server()->dispatch( $request )->get_status() );
	}

	public function test_purge_drops_old_raw_events_only() {
		global $wpdb;
		$wpdb->insert( Database::$table_events, array( 'nx_id' => $this->nx_id, 'event' => 1, 'created_at' => gmdate( 'Y-m-d H:i:s', strtotime( '-40 days' ) ) ) );
		$wpdb->insert( Database::$table_events, array( 'nx_id' => $this->nx_id, 'event' => 1, 'created_at' => gmdate( 'Y-m-d H:i:s' ) ) );
		Tracker::get_instance()->purge();
		$this->assertSame( 1, $this->event_count() );
	}

	protected function daily_row( $day, $nx_id, $views = 1 ) {
		global $wpdb;
		$wpdb->insert( Database::$table_stats_daily, array( 'day' => $day, 'nx_id' => $nx_id, 'dim' => 'all', 'val' => '', 'views' => $views ) );
	}

	public function test_free_keeps_ninety_days_of_daily_data() {
		global $wpdb;
		$this->assertSame( 90, Tracker::get_instance()->retention_days() ); // Tests run without Pro.
		$this->daily_row( gmdate( 'Y-m-d', strtotime( '-89 days' ) ), $this->nx_id );
		$this->daily_row( gmdate( 'Y-m-d', strtotime( '-91 days' ) ), $this->nx_id );
		Tracker::get_instance()->purge();
		$this->assertSame( array( gmdate( 'Y-m-d', strtotime( '-89 days' ) ) ), $wpdb->get_col( 'SELECT day FROM ' . Database::$table_stats_daily ) );
	}

	public function test_retention_filter_zero_keeps_everything() {
		global $wpdb;
		add_filter( 'nx_analytics_retention_days', '__return_zero' );
		$this->daily_row( '2020-01-01', $this->nx_id );
		Tracker::get_instance()->purge();
		remove_filter( 'nx_analytics_retention_days', '__return_zero' );
		$this->assertSame( 1, (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Database::$table_stats_daily ) );
	}

	public function test_reset_one_notification_or_everything() {
		global $wpdb;
		$other = $this->notification( true );
		foreach ( array( $this->nx_id, $other ) as $id ) {
			$this->daily_row( gmdate( 'Y-m-d' ), $id );
			$wpdb->insert( Database::$table_events, array( 'nx_id' => $id, 'event' => 1, 'created_at' => gmdate( 'Y-m-d H:i:s' ) ) );
			$wpdb->insert( Database::$table_stats, array( 'nx_id' => $id, 'views' => '3', 'clicks' => '1', 'created_at' => gmdate( 'Y-m-d' ) ) );
		}
		$count = function ( $table ) use ( $wpdb ) {
			return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore
		};

		// A subscriber can't reset.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$request = new WP_REST_Request( 'DELETE', '/notificationx/v1/analytics/report/data' );
		$this->assertSame( 403, rest_get_server()->dispatch( $request )->get_status() );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$request = new WP_REST_Request( 'DELETE', '/notificationx/v1/analytics/report/data' );
		$request->set_param( 'nx_id', $this->nx_id );
		$this->assertSame( 200, rest_get_server()->dispatch( $request )->get_status() );
		foreach ( array( Database::$table_events, Database::$table_stats_daily, Database::$table_stats ) as $table ) {
			$this->assertSame( 1, $count( $table ), $table );
			$this->assertSame( $other, (int) $wpdb->get_var( "SELECT nx_id FROM {$table}" ) ); // phpcs:ignore
		}

		$request = new WP_REST_Request( 'DELETE', '/notificationx/v1/analytics/report/data' );
		rest_get_server()->dispatch( $request );
		foreach ( array( Database::$table_events, Database::$table_stats_daily, Database::$table_stats ) as $table ) {
			$this->assertSame( 0, $count( $table ), $table );
		}
	}

	public function test_deleting_a_notification_deletes_its_tracked_data() {
		global $wpdb;
		$this->daily_row( gmdate( 'Y-m-d' ), $this->nx_id );
		PostType::get_instance()->delete_post( $this->nx_id );
		$this->assertSame( 0, (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Database::$table_stats_daily ) );
	}

	public function test_type_filter_limits_the_audience_report() {
		$saved = PostType::get_instance()->save_post(
			array(
				'type'   => 'gdpr',
				'source' => 'gdpr_notification',
				'themes' => 'gdpr_theme-light-one',
				'title'  => 'cookie',
			)
		);
		$gdpr = (int) $saved['nx_id'];
		$this->daily_row( gmdate( 'Y-m-d' ), $this->nx_id, 5 );
		$this->daily_row( gmdate( 'Y-m-d' ), $gdpr, 2 );
		update_option( 'nx_analytics_rolled_at', time() ); // Keep the rows above.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$get = function ( $params ) {
			$request = new WP_REST_Request( 'GET', '/notificationx/v1/analytics/report/audience' );
			foreach ( $params as $k => $v ) {
				$request->set_param( $k, $v );
			}
			return rest_get_server()->dispatch( $request )->get_data()['totals']['seen'];
		};
		$this->assertSame( 7, $get( array() ) );
		$this->assertSame( 2, $get( array( 'type' => 'gdpr' ) ) );
		$this->assertSame( 5, $get( array( 'type' => 'notification_bar' ) ) );
		$this->assertSame( 0, $get( array( 'type' => 'no_such_type' ) ) );
		$this->assertSame( 5, $get( array( 'type' => 'gdpr', 'nx_id' => $this->nx_id ) ) ); // nx_id wins.
	}

	public function test_hovers_and_site_wide_visitors_reach_the_audience_report() {
		$other = $this->notification( true );
		// One visitor sees and hovers two notifications.
		$this->post( $this->batch( array( array( 'n' => $this->nx_id, 'e' => 'view' ), array( 'n' => $other, 'e' => 'view' ), array( 'n' => $this->nx_id, 'e' => 'hover' ) ) ) );
		// A second visitor sees one.
		$_SERVER['REMOTE_ADDR'] = '198.51.100.77';
		$this->post( $this->batch( array( array( 'n' => $this->nx_id, 'e' => 'view' ) ) ) );
		$today = gmdate( 'Y-m-d' );
		Tracker::get_instance()->rollup( $today, $today );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$get = function ( $params = array() ) {
			$request = new WP_REST_Request( 'GET', '/notificationx/v1/analytics/report/audience' );
			foreach ( $params as $k => $v ) {
				$request->set_param( $k, $v );
			}
			return rest_get_server()->dispatch( $request )->get_data()['totals'];
		};
		$all = $get();
		$this->assertSame( 3, $all['seen'] );
		$this->assertSame( 1, $all['hovers'] );
		$this->assertSame( 33.3, $all['engagement'] );
		// Unique per day across notifications: 2 people, not 3 notification-visitors.
		$this->assertSame( 2, $all['visitors'] );
		$this->assertSame( 1.5, $all['per_visitor'] );
		// Filtered to one notification: its own uniques.
		$this->assertSame( 2, $get( array( 'nx_id' => $this->nx_id ) )['visitors'] );
		$this->assertSame( 1, $get( array( 'nx_id' => $other ) )['visitors'] );
	}

	public function test_client_config_carries_the_ga_switch() {
		$this->assertFalse( Tracker::get_instance()->client_config()['ga'] );
	}

	public function test_free_still_collects_but_the_reports_are_pro() {
		$this->set_pro( false );
		$this->assertSame( 202, $this->post( $this->batch( array( array( 'n' => $this->nx_id, 'e' => 'view' ) ) ) )->get_status() );
		$this->assertSame( 1, $this->event_count() );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		foreach ( array( 'audience', 'leads', 'notifications', 'notification/' . $this->nx_id ) as $route ) {
			$res = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/notificationx/v1/analytics/report/' . $route ) );
			$this->assertSame( 403, $res->get_status(), $route );
			$this->assertSame( 'nx_pro_required', $res->get_data()['code'], $route );
		}
	}

	public function test_leads_report_links_to_the_entries_tab() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$get = function ( $params = array() ) {
			$request = new WP_REST_Request( 'GET', '/notificationx/v1/analytics/report/leads' );
			foreach ( $params as $k => $v ) {
				$request->set_param( $k, $v );
			}
			return rest_get_server()->dispatch( $request )->get_data()['entries_url'];
		};
		$this->assertSame( admin_url( 'admin.php?page=nx-settings&tab=entries' ), $get() );
		$this->assertSame( admin_url( 'admin.php?page=nx-settings&tab=entries&notification_id=' . $this->nx_id ), $get( array( 'nx_id' => $this->nx_id ) ) );
		// A user who can read analytics but not edit settings gets no link.
		$user = self::factory()->user->create( array( 'role' => 'editor' ) );
		get_user_by( 'id', $user )->add_cap( 'read_notificationx_analytics' );
		wp_set_current_user( $user );
		$this->assertNull( $get() );
	}
}
