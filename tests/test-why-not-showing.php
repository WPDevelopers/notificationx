<?php
/**
 * "Why isn't it showing?" (includes/Core/WhyNotShowing.php): the reasons,
 * the REST route (admin only, read-only, no-store) and the add-on filter.
 *
 * @package Notificationx
 */

use NotificationX\Admin\Entries;
use NotificationX\Core\Database;
use NotificationX\Core\Helper;
use NotificationX\Core\PostType;
use NotificationX\Core\REST;
use NotificationX\Core\WhyNotShowing;

class Test_Why_Not_Showing extends WP_UnitTestCase {

	protected $server;

	public function setUp(): void {
		parent::setUp();
		REST::get_instance();
		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );
	}

	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;
		remove_all_filters( 'nx_why_not_showing_reasons' );
		parent::tearDown();
	}

	/**
	 * A notification row written straight to the table (no save hooks).
	 */
	protected function notification( $data = [], $columns = [] ) {
		$data = array_merge(
			[
				'type'            => 'comments',
				'source'          => 'wp_comments',
				'themes'          => 'comments_theme-one',
				'position'        => 'bottom_left',
				'show_on'         => 'everywhere',
				'show_on_display' => 'always',
				'hide_on_desktop' => true,
				'hide_on_tab'     => true,
				'hide_on_mobile'  => true,
				'display_from'    => 30,
				'delay_before'    => 5,
				'loop'            => true,
			],
			$data
		);
		$now  = Helper::mysql_time();
		$id   = Database::get_instance()->insert_post(
			Database::$table_posts,
			array_merge(
				[
					'title'        => 'WNS test',
					'type'         => $data['type'],
					'source'       => $data['source'],
					'theme'        => $data['themes'],
					'is_inline'    => '',
					'global_queue' => ! empty( $data['global_queue'] ),
					'enabled'      => true,
					'data'         => $data,
					'created_at'   => $now,
					'updated_at'   => $now,
				],
				$columns
			)
		);
		$this->assertGreaterThan( 0, $id );
		return (int) $id;
	}

	protected function entry( $nx_id, $days_ago, $source = 'wp_comments' ) {
		$ts = time() - $days_ago * DAY_IN_SECONDS;
		Entries::get_instance()->insert_entry(
			[
				'nx_id'     => $nx_id,
				'source'    => $source,
				'entry_key' => 'k' . wp_rand(),
				'data'      => [ 'name' => 'Ann', 'title' => 'Hello', 'timestamp' => $ts, 'link' => '' ],
			]
		);
	}

	protected function reasons( $id ) {
		return WhyNotShowing::get_instance()->reasons( PostType::get_instance()->get_post( $id ) );
	}

	protected function by_key( $reasons, $key ) {
		foreach ( $reasons as $reason ) {
			if ( $key === $reason['key'] ) {
				return $reason;
			}
		}
		return null;
	}

	protected function level( $id, $key ) {
		$reason = $this->by_key( $this->reasons( $id ), $key );
		return $reason ? $reason['level'] : null;
	}

	public function test_the_route_is_admin_only_and_never_cached() {
		$id      = $this->notification();
		$request = new WP_REST_Request( 'GET', "/notificationx/v1/why-not-showing/$id" );

		wp_set_current_user( 0 );
		$this->assertSame( 401, $this->server->dispatch( $request )->get_status() );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$this->assertSame( 403, $this->server->dispatch( $request )->get_status() );

		$admin = self::factory()->user->create( [ 'role' => 'administrator' ] );
		get_user_by( 'id', $admin )->add_cap( 'edit_notificationx' );
		wp_set_current_user( $admin );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'no-store', $response->get_headers()['Cache-Control'] );
		$data = $response->get_data();
		$this->assertSame( $id, $data['id'] );
		$this->assertSame( 'WNS test', $data['title'] );
		$this->assertNotEmpty( $data['reasons'] );
		$this->assertSame( [ "notificationx_$id", "nx_bar_$id", "nx_exit_intent_$id" ], $data['browser']['cookies'] );

		$missing = new WP_REST_Request( 'GET', '/notificationx/v1/why-not-showing/999999' );
		$this->assertSame( 404, $this->server->dispatch( $missing )->get_status() );
	}

	public function test_it_reads_only() {
		global $wpdb;
		$id = $this->notification();
		$this->entry( $id, 1 );
		$snapshot = function () use ( $wpdb ) {
			return [
				$wpdb->get_results( 'SELECT * FROM ' . Database::$table_posts, ARRAY_A ),
				$wpdb->get_results( 'SELECT * FROM ' . Database::$table_entries, ARRAY_A ),
				// Transients are caches (reading any notification fills the builder-fields one).
				$wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name NOT LIKE '\\_transient%' ORDER BY option_name", ARRAY_A ),
			];
		};
		$before = $snapshot();
		$this->reasons( $id );
		$this->assertSame( $before, $snapshot() );
	}

	public function test_switched_off_comes_first_with_a_link_to_the_disabled_list() {
		$id      = $this->notification( [], [ 'enabled' => false ] );
		$reasons = $this->reasons( $id );
		$this->assertSame( 'enabled', $reasons[0]['key'] );
		$this->assertSame( 'blocked', $reasons[0]['level'] );
		$this->assertStringEndsWith( 'admin.php?page=nx-admin&status=disabled', $reasons[0]['fix']['url'] );
	}

	public function test_no_data_yet() {
		$id     = $this->notification();
		$reason = $this->by_key( $this->reasons( $id ), 'data' );
		$this->assertSame( 'blocked', $reason['level'] );
		$this->assertStringContainsString( "page=nx-edit&id=$id&tab=source_tab", $reason['fix']['url'] );
	}

	public function test_data_outside_the_chosen_period() {
		$id = $this->notification( [ 'display_from' => 7 ] );
		$this->entry( $id, 20 );
		$this->entry( $id, 40 );
		$reason = $this->by_key( $this->reasons( $id ), 'data' );
		$this->assertSame( 'blocked', $reason['level'] );
		$this->assertSame( 'Nothing in the chosen period', $reason['title'] );
		$this->assertStringContainsString( '2 entries', $reason['detail'] );
		$this->assertStringContainsString( 'tab=customize_tab', $reason['fix']['url'] );
	}

	public function test_data_in_the_period_is_fine() {
		$id = $this->notification( [ 'display_from' => 7 ] );
		$this->entry( $id, 1 );
		$reason = $this->by_key( $this->reasons( $id ), 'data' );
		$this->assertSame( 'ok', $reason['level'] );
		$this->assertSame( '1 entry to show', $reason['title'] );
	}

	public function test_sources_without_entries_skip_the_data_check() {
		$id = $this->notification( [ 'type' => 'notification_bar', 'source' => 'press_bar', 'themes' => 'press_bar_theme-one', 'position' => 'top' ] );
		$this->assertNull( $this->by_key( $this->reasons( $id ), 'data' ) );
	}

	public function test_page_rules() {
		$this->assertSame( 'ok', $this->level( $this->notification(), 'pages' ) );
		$this->assertSame( 'blocked', $this->level( $this->notification( [ 'show_on' => 'on_selected', 'all_locations' => [] ] ), 'pages' ) );
		$id     = $this->notification( [ 'show_on' => 'on_selected', 'all_locations' => [ 'is_front_page' ] ] );
		$reason = $this->by_key( $this->reasons( $id ), 'pages' );
		$this->assertSame( 'limited', $reason['level'] );
		$this->assertStringContainsString( 'tab=display_tab', $reason['fix']['url'] );
		$this->assertSame( 'limited', $this->level( $this->notification( [ 'show_on' => 'hide_on_selected', 'all_locations' => [ 'is_front_page' ] ] ), 'pages' ) );
		$this->assertSame( 'limited', $this->level( $this->notification( [ 'show_on' => 'only_shortcode' ] ), 'pages' ) );
	}

	public function test_audience() {
		$this->assertSame( 'ok', $this->level( $this->notification(), 'audience' ) );
		$this->assertSame( 'limited', $this->level( $this->notification( [ 'show_on_display' => 'logged_in_user' ] ), 'audience' ) );
		$this->assertSame( 'limited', $this->level( $this->notification( [ 'show_on_display' => 'logged_out_user' ] ), 'audience' ) );
		$roles = $this->by_key( $this->reasons( $this->notification( [ 'targeting_user_roles' => [ 'editor', 'guest' ] ] ) ), 'roles' );
		$this->assertSame( 'limited', $roles['level'] );
		$this->assertStringContainsString( 'Editor, logged-out visitors', $roles['detail'] );
		$this->assertNull( $this->by_key( $this->reasons( $this->notification( [ 'targeting_user_roles' => [ 'all_users' ] ] ) ), 'roles' ) );
		$countries = $this->by_key( $this->reasons( $this->notification( [ 'country_targeting' => [ [ 'value' => 'bd', 'label' => 'Bangladesh' ] ] ] ) ), 'countries' );
		$this->assertSame( 'limited', $countries['level'] );
		$this->assertStringContainsString( 'Bangladesh', $countries['detail'] );
		$this->assertNull( $this->by_key( $this->reasons( $this->notification( [ 'country_targeting' => [ [ 'value' => 'all', 'label' => 'All' ] ] ] ) ), 'countries' ) );
	}

	public function test_devices_follow_the_front_ends_rule() {
		$this->assertSame( 'ok', $this->level( $this->notification(), 'devices' ) );
		$some = $this->by_key( $this->reasons( $this->notification( [ 'hide_on_mobile' => false ] ) ), 'devices' );
		$this->assertSame( 'limited', $some['level'] );
		$this->assertSame( 'Not on mobile.', $some['detail'] );
		$this->assertSame( 'blocked', $this->level( $this->notification( [ 'hide_on_mobile' => false, 'hide_on_tab' => false, 'hide_on_desktop' => false ] ), 'devices' ) );
	}

	public function test_already_closed_rules() {
		$reason = $this->by_key( $this->reasons( $this->notification( [ 'notification_reappearance' => 'dont_show_notification', 'cache_duration_for_dont_show' => 3 ] ) ), 'seen' );
		$this->assertSame( 'limited', $reason['level'] );
		$this->assertStringContainsString( '3 days', $reason['detail'] );
		$this->assertSame( 'note', $this->level( $this->notification( [ 'notification_reappearance' => 'show_notification_next_visit' ] ), 'seen' ) );
		$this->assertSame( 'ok', $this->level( $this->notification( [ 'notification_reappearance' => 'show_notification_every_page' ] ), 'seen' ) );
		$bar = $this->by_key( $this->reasons( $this->notification( [ 'type' => 'notification_bar', 'source' => 'press_bar', 'bar_reappearance' => 'dont_show_welcomebar', 'bar_cache_duration_for_dont_show' => 1 ] ) ), 'seen' );
		$this->assertStringContainsString( '1 day.', $bar['detail'] );
		$this->assertStringContainsString( 'tab=display_tab', $bar['fix']['url'] );
	}

	public function test_bar_schedule_and_ended_countdown() {
		$bar = [ 'type' => 'notification_bar', 'source' => 'press_bar', 'themes' => 'press_bar_theme-one', 'position' => 'top' ];
		$id  = $this->notification(
			$bar + [
				'schedule_type'    => 'custom',
				'custom_schedule'  => [ 'startDate' => '2020-01-01', 'endDate' => '2020-01-02' ],
				'custom_from_time' => '09:00',
				'custom_to_time'   => '17:00',
			]
		);
		$reason = $this->by_key( $this->reasons( $id ), 'schedule' );
		$this->assertSame( 'blocked', $reason['level'] );
		$this->assertStringContainsString( '2020-01-01', $reason['detail'] );

		$ended = $this->notification( $bar + [ 'enable_countdown' => true, 'close_after_expire' => true, 'countdown_end_date' => '2020-01-01 10:00:00' ] );
		$this->assertSame( 'blocked', $this->level( $ended, 'countdown' ) );
		$evergreen = $this->notification( $bar + [ 'enable_countdown' => true, 'evergreen_timer' => true, 'close_after_expire' => true, 'countdown_end_date' => '2020-01-01 10:00:00' ] );
		$this->assertNull( $this->by_key( $this->reasons( $evergreen ), 'countdown' ) );
	}

	public function test_a_future_start_time() {
		$id = $this->notification( [], [ 'updated_at' => Helper::mysql_time( time() + 3 * HOUR_IN_SECONDS ) ] );
		$this->assertSame( 'blocked', $this->level( $id, 'schedule' ) );
	}

	public function test_pro_sources_without_pro() {
		if ( \NotificationX\NotificationX::is_pro() ) {
			$this->markTestSkipped( 'Pro is active.' );
		}
		$id     = $this->notification( [ 'type' => 'email_subscription', 'source' => 'convertkit', 'themes' => 'subs_theme-one' ] );
		$reason = $this->by_key( $this->reasons( $id ), 'pro' );
		$this->assertSame( 'blocked', $reason['level'] );
		$this->assertStringContainsString( 'ConvertKit', $reason['detail'] );
	}

	public function test_a_source_this_site_does_not_have() {
		$id      = $this->notification( [ 'type' => 'page_analytics', 'source' => 'not_on_this_site', 'themes' => 'x' ] );
		$reasons = $this->reasons( $id );
		$reason  = $this->by_key( $reasons, 'pro' );
		$this->assertSame( 'blocked', $reason['level'] );
		$this->assertStringContainsString( 'not_on_this_site', $reason['detail'] );
		$this->assertNull( $this->by_key( $reasons, 'data' ), 'Data is not judged without its source.' );
	}

	public function test_timing_and_other_notifications_in_the_same_place() {
		$id = $this->notification( [ 'delay_before' => 90, 'loop' => false ] );
		$this->assertSame( 'limited', $this->level( $id, 'timing' ) );
		$this->assertSame( 'note', $this->level( $id, 'loop' ) );

		$this->notification( [ 'position' => 'bottom_right' ] );
		$this->assertSame( 'ok', $this->level( $id, 'slot' ) );
		$other = $this->notification( [], [ 'title' => 'Neighbour' ] );
		$slot  = $this->by_key( $this->reasons( $id ), 'slot' );
		$this->assertSame( 'note', $slot['level'] );
		$this->assertStringContainsString( "“Neighbour” (#$other)", $slot['detail'] );
		$untitled = $this->notification( [], [ 'title' => '' ] );
		$this->assertStringContainsString( ", #$untitled", $this->by_key( $this->reasons( $id ), 'slot' )['detail'], 'Untitled ones by number.' );
		$this->notification( [], [ 'title' => 'Off', 'enabled' => false ] );
		$this->assertStringNotContainsString( 'Off', $this->by_key( $this->reasons( $id ), 'slot' )['detail'] );
	}

	public function test_add_ons_add_reasons_and_bad_ones_are_dropped() {
		$id = $this->notification( [], [ 'enabled' => false ] );
		add_filter(
			'nx_why_not_showing_reasons',
			function ( $reasons ) {
				$reasons[] = WhyNotShowing::reason( 'addon', 'limited', 'From an add-on', 'Detail', [ 'label' => 'Fix', 'url' => 'javascript:alert(1)' ] );
				$reasons[] = [ 'key' => 'bad', 'level' => 'whatever', 'title' => 'Bad' ];
				$reasons[] = [ 'level' => 'blocked' ];
				return $reasons;
			}
		);
		$reasons = $this->reasons( $id );
		$keys    = wp_list_pluck( $reasons, 'key' );
		$this->assertContains( 'addon', $keys );
		$this->assertNotContains( 'bad', $keys );
		$this->assertSame( '', $this->by_key( $reasons, 'addon' )['fix']['url'], 'Unsafe links are dropped.' );
		// Most serious first.
		$order  = [ 'blocked' => 0, 'limited' => 1, 'note' => 2, 'ok' => 3 ];
		$levels = array_map( function ( $r ) use ( $order ) { return $order[ $r['level'] ]; }, $reasons );
		$sorted = $levels;
		sort( $sorted );
		$this->assertSame( $sorted, $levels );
	}
}
