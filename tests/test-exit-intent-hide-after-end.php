<?php
/**
 * Exit Intent "Stop Showing After End Date" (free issue #211, pro issue #65).
 *
 * With `exit_intent_hide_after_end` on and `exit_intent_countdown_end` in the
 * past, the campaign is left out of the frontend list and of the `notice`
 * REST payload. The end time goes to the frontend as `exit_intent_end_ts`
 * (UTC, ms) so the popup can close itself and the Pro countdown themes agree
 * on the end moment.
 *
 * See docs/qa-guide/exit-intent-hide-after-end.md.
 *
 * @package Notificationx
 */

use NotificationX\Core\PostType;
use NotificationX\Extensions\ExitIntent\ExitIntentNotification;
use NotificationX\FrontEnd\FrontEnd;

class Test_Exit_Intent_Hide_After_End extends WP_UnitTestCase {

	const NOW = 1800000000; // 2027-01-15T08:00:00Z.

	protected function settings( $end, $hide = true ) {
		return [
			'source'                     => 'exit_intent_custom',
			'exit_intent_hide_after_end' => $hide,
			'exit_intent_countdown_end'  => $end,
		];
	}

	protected function insert_campaign( $end, $hide ) {
		$saved = PostType::get_instance()->save_post(
			[
				'type'                       => 'exit_intent',
				'source'                     => 'exit_intent_custom',
				'themes'                     => 'exit_intent_custom_theme-one',
				'enabled'                    => true,
				'title'                      => 'exit intent end date',
				'exit_intent_hide_after_end' => $hide,
				'exit_intent_countdown_end'  => $end,
			]
		);
		$this->assertNotEmpty( $saved['nx_id'] );
		return (int) $saved['nx_id'];
	}

	// --- get_end_timestamp() ---------------------------------------------------

	public function test_end_timestamp_reads_the_date_field_format() {
		// What the admin date field saves (moment().utc().format() / toJSON()).
		$this->assertSame( self::NOW, ExitIntentNotification::get_end_timestamp( $this->settings( '2027-01-15T08:00:00Z' ) ) );
		$this->assertSame( self::NOW, ExitIntentNotification::get_end_timestamp( $this->settings( '2027-01-15T08:00:00.000Z' ) ) );
		$this->assertSame( self::NOW, ExitIntentNotification::get_end_timestamp( $this->settings( '2027-01-15T14:00:00+06:00' ) ) );
	}

	public function test_end_timestamp_reads_a_value_without_timezone_as_utc() {
		$this->assertSame( self::NOW, ExitIntentNotification::get_end_timestamp( $this->settings( '2027-01-15 08:00:00' ) ) );
	}

	public function test_end_timestamp_is_null_for_empty_or_invalid_values() {
		foreach ( [ '', '   ', 'not a date', null, [ 'x' ], 123 ] as $value ) {
			$this->assertNull( ExitIntentNotification::get_end_timestamp( $this->settings( $value ) ), var_export( $value, true ) );
		}
		$this->assertNull( ExitIntentNotification::get_end_timestamp( [] ) );
	}

	// --- is_past_end() ---------------------------------------------------------

	public function test_past_end_only_when_toggle_on_and_date_passed() {
		$past   = '2027-01-15T07:59:59Z';
		$future = '2027-01-15T08:00:01Z';

		$this->assertTrue( ExitIntentNotification::is_past_end( $this->settings( $past ), self::NOW ) );
		$this->assertTrue( ExitIntentNotification::is_past_end( $this->settings( '2027-01-15T08:00:00Z' ), self::NOW ) );
		$this->assertFalse( ExitIntentNotification::is_past_end( $this->settings( $future ), self::NOW ) );

		// Toggle off keeps the old behavior.
		$this->assertFalse( ExitIntentNotification::is_past_end( $this->settings( $past, false ), self::NOW ) );
		$this->assertFalse( ExitIntentNotification::is_past_end( $this->settings( $past, 'false' ), self::NOW ) );
		$this->assertTrue( ExitIntentNotification::is_past_end( $this->settings( $past, '1' ), self::NOW ) );

		// No usable date: never hidden.
		$this->assertFalse( ExitIntentNotification::is_past_end( $this->settings( '' ), self::NOW ) );
		$this->assertFalse( ExitIntentNotification::is_past_end( $this->settings( 'garbage' ), self::NOW ) );
	}

	public function test_past_end_ignores_other_sources() {
		$settings           = $this->settings( '2000-01-01T00:00:00Z' );
		$settings['source'] = 'press_bar';
		$this->assertFalse( ExitIntentNotification::is_past_end( $settings ) );
	}

	// --- add_end_timestamp() (nx_filtered_post) -------------------------------

	public function test_filtered_post_gets_end_timestamp_in_ms() {
		$out = ExitIntentNotification::get_instance()->add_end_timestamp( $this->settings( '2027-01-15T08:00:00Z', '1' ), [] );
		$this->assertSame( self::NOW * 1000, $out['exit_intent_end_ts'] );
		$this->assertTrue( $out['exit_intent_hide_after_end'] );

		$out = ExitIntentNotification::get_instance()->add_end_timestamp( $this->settings( '', false ), [] );
		$this->assertArrayNotHasKey( 'exit_intent_end_ts', $out );
		$this->assertFalse( $out['exit_intent_hide_after_end'] );
	}

	public function test_filtered_post_leaves_other_sources_alone() {
		$in  = [ 'source' => 'press_bar', 'exit_intent_countdown_end' => '2027-01-15T08:00:00Z' ];
		$out = ExitIntentNotification::get_instance()->add_end_timestamp( $in, [] );
		$this->assertSame( $in, $out );
	}

	// --- FrontEnd ---------------------------------------------------------------

	public function test_frontend_skips_an_expired_campaign_only_when_toggle_is_on() {
		$past   = gmdate( 'c', time() - HOUR_IN_SECONDS );
		$future = gmdate( 'c', time() + HOUR_IN_SECONDS );

		$expired      = $this->insert_campaign( $past, true );
		$running      = $this->insert_campaign( $future, true );
		$toggle_off   = $this->insert_campaign( $past, false );

		// The IDs come back as strings from the database.
		$ids = array_map( 'intval', FrontEnd::get_instance()->get_notifications_ids()['exit_intent'] );
		$this->assertNotContains( $expired, $ids );
		$this->assertContains( $running, $ids );
		$this->assertContains( $toggle_off, $ids );

		// IDs from a cached page still reach the REST payload builder.
		$data = FrontEnd::get_instance()->get_notifications_data( [ 'exit_intent' => [ $expired, $running, $toggle_off ] ] );
		$this->assertArrayNotHasKey( $expired, $data['exit_intent'] );
		$this->assertArrayHasKey( $running, $data['exit_intent'] );
		$this->assertArrayHasKey( $toggle_off, $data['exit_intent'] );
	}

	// --- Campaigns saved with an empty end date (Siam's QA on #212) -------------

	protected function read( $nx_id, $context = '' ) {
		PostType::get_instance()->set_context( $context );
		$post = PostType::get_instance()->get_post( $nx_id );
		PostType::get_instance()->set_context( '' );
		return $post;
	}

	protected function assert_about_seven_days_ahead( $value ) {
		$ts = ExitIntentNotification::get_end_timestamp( [ 'exit_intent_countdown_end' => $value ] );
		$this->assertNotNull( $ts, "'$value' is a date" );
		$this->assertGreaterThan( time() + 6 * DAY_IN_SECONDS, $ts );
		$this->assertLessThanOrEqual( time() + 7 * DAY_IN_SECONDS, $ts );
	}

	public function test_builder_read_fills_an_empty_end_date() {
		$nx_id = $this->insert_campaign( '', false );
		$this->assert_about_seven_days_ahead( $this->read( $nx_id, 'edit' )['exit_intent_countdown_end'] );
		// The frontend read and the stored value are unchanged.
		$this->assertSame( '', $this->read( $nx_id )['exit_intent_countdown_end'] );
	}

	public function test_builder_read_keeps_a_set_end_date() {
		$nx_id = $this->insert_campaign( '2027-01-15T08:00:00Z', false );
		$this->assertSame( '2027-01-15T08:00:00Z', $this->read( $nx_id, 'edit' )['exit_intent_countdown_end'] );
	}

	public function test_save_with_the_toggle_on_never_stores_an_empty_end_date() {
		foreach ( [ '', '   ', 'not a date' ] as $end ) {
			$nx_id = $this->insert_campaign( $end, true );
			$this->assert_about_seven_days_ahead( $this->read( $nx_id )['exit_intent_countdown_end'] );
		}
		// Turning the toggle on later for a campaign saved with ''.
		$nx_id  = $this->insert_campaign( '', false );
		$stored = $this->read( $nx_id );
		$stored['exit_intent_hide_after_end'] = true;
		PostType::get_instance()->save_post( $stored );
		$this->assert_about_seven_days_ahead( $this->read( $nx_id )['exit_intent_countdown_end'] );
	}

	public function test_save_with_the_toggle_off_keeps_an_empty_end_date() {
		$nx_id = $this->insert_campaign( '', false );
		$this->assertSame( '', $this->read( $nx_id )['exit_intent_countdown_end'] );
	}

	public function test_save_keeps_a_set_end_date() {
		$nx_id = $this->insert_campaign( '2027-01-15T08:00:00Z', true );
		$this->assertSame( '2027-01-15T08:00:00Z', $this->read( $nx_id )['exit_intent_countdown_end'] );
	}
}
