<?php
/**
 * Exit Intent "Do Not Show Again For": a toggle in front of the days field.
 *
 * - New campaigns: the toggle is off by default; the days only apply when it's on.
 * - Campaigns saved before the toggle existed keep their behaviour: they read
 *   as "on" when they have a days value, in the builder and on the front end.
 * - Other types are untouched (the Announcement's own "Cookie Duration" is a
 *   different field).
 *
 * See docs/qa-guide/exit-intent-improvements.md.
 *
 * @package Notificationx
 */

use NotificationX\Core\Database;
use NotificationX\Core\PostType;
use NotificationX\Extensions\ExitIntent\ExitIntentNotification;
use NotificationX\FrontEnd\FrontEnd;
use NotificationX\NotificationX;

class Test_Exit_Intent_Dismiss_Toggle extends WP_UnitTestCase {

	protected function section() {
		$fields = ExitIntentNotification::get_instance()->customize_fields( array() );
		return $fields['exit_intent_popup_settings']['fields'];
	}

	protected function save( $data ) {
		$saved = PostType::get_instance()->save_post(
			array_merge(
				array(
					'type'    => 'exit_intent',
					'source'  => 'exit_intent_custom',
					'themes'  => 'exit_intent_custom_theme-one',
					'enabled' => true,
					'title'   => 'exit intent toggle',
				),
				$data
			)
		);
		$this->assertNotEmpty( $saved['nx_id'] );
		return (int) $saved['nx_id'];
	}

	/**
	 * A row the way the plugin stored it before the toggle existed.
	 */
	protected function insert_legacy( $data, $source = 'exit_intent_custom', $type = 'exit_intent' ) {
		$data = array_merge(
			array(
				'type'    => $type,
				'source'  => $source,
				'themes'  => $source . '_theme-one',
				'enabled' => true,
			),
			$data
		);
		// Stored as is, without the save filters (what a row from before looks like).
		return (int) PostType::get_instance()->insert_post(
			array(
				'type'         => $type,
				'source'       => $source,
				'theme'        => $data['themes'],
				'global_queue' => false,
				'enabled'      => true,
				'is_inline'    => false,
				'title'        => 'legacy',
				'data'         => $data,
			)
		);
	}

	protected function read( $nx_id ) {
		return PostType::get_instance()->get_post( $nx_id );
	}

	protected function stored( $nx_id ) {
		$row = Database::get_instance()->get_post( Database::$table_posts, array( 'nx_id' => $nx_id ) );
		return $row['data'];
	}

	protected function payload( $nx_id ) {
		$data = FrontEnd::get_instance()->get_notifications_data( array( 'exit_intent' => array( $nx_id ) ) );
		$this->assertArrayHasKey( $nx_id, $data['exit_intent'] );
		return $data['exit_intent'][ $nx_id ]['post'];
	}

	// --- builder fields --------------------------------------------------------

	public function test_toggle_sits_in_front_of_the_days_and_is_off_by_default() {
		$fields = $this->section();
		$keys   = array_keys( $fields );
		$toggle = array_search( 'exit_intent_cookie_enabled', $keys, true );
		$this->assertNotFalse( $toggle );
		$this->assertSame( $toggle + 1, array_search( 'exit_intent_cookie_days', $keys, true ), 'The toggle comes right before the days field.' );
		$this->assertSame( 'toggle', $fields['exit_intent_cookie_enabled']['type'] );
		$this->assertFalse( $fields['exit_intent_cookie_enabled']['default'] );
	}

	public function test_days_show_only_with_the_toggle_on() {
		$days = $this->section()['exit_intent_cookie_days'];
		$this->assertSame( array( 'is', 'exit_intent_cookie_enabled', true ), json_decode( wp_json_encode( $days['rules'] ), true ) );
		$this->assertSame( 7, $days['default'] );
		$this->assertSame( 'Do Not Show Again For', $days['label'] );
	}

	public function test_the_days_field_belongs_to_exit_intent_only() {
		$owners = array();
		foreach ( NotificationX::get_instance()->get_field_names() as $name => $field ) {
			if ( false !== strpos( $name, 'cookie_d' ) ) {
				$owners[] = $name;
			}
		}
		sort( $owners );
		// The Announcement's "Convert to Exit Intent" has its own field.
		$this->assertSame( array( 'exit_intent_cookie_days', 'exit_intent_cookie_duration' ), $owners );
	}

	// --- campaigns saved before the toggle --------------------------------------

	public function legacy_days_provider() {
		return array(
			'default 7'    => array( 7, true, 7 ),
			'30 days'      => array( 30, true, 30 ),
			'as a string'  => array( '14', true, 14 ),
			'zero'         => array( 0, false, 0 ),
			'empty string' => array( '', false, 0 ),
		);
	}

	/**
	 * @dataProvider legacy_days_provider
	 */
	public function test_a_campaign_saved_before_reads_on_when_it_has_days( $days, $on, $read_days ) {
		$nx_id = $this->insert_legacy( array( 'exit_intent_cookie_days' => $days ) );
		$post  = $this->read( $nx_id );
		$this->assertSame( $on, $post['exit_intent_cookie_enabled'] );
		$this->assertEquals( $read_days, $post['exit_intent_cookie_days'] );
		// The same on the front end.
		$this->assertSame( $on, $this->payload( $nx_id )['exit_intent_cookie_enabled'] );
		// Nothing is written by reading.
		$this->assertArrayNotHasKey( 'exit_intent_cookie_enabled', $this->stored( $nx_id ) );
	}

	public function test_a_campaign_saved_before_without_days_reads_on_like_today() {
		// Today the read fills the default 7 and the popup writes a 7-day cookie.
		$nx_id = $this->insert_legacy( array() );
		$post  = $this->read( $nx_id );
		$this->assertSame( 7, $post['exit_intent_cookie_days'] );
		$this->assertTrue( $post['exit_intent_cookie_enabled'] );
	}

	public function test_re_saving_a_campaign_saved_before_keeps_it_on() {
		$nx_id = $this->insert_legacy( array( 'exit_intent_cookie_days' => 10 ) );
		PostType::get_instance()->save_post( $this->read( $nx_id ) );
		$this->assertTrue( $this->stored( $nx_id )['exit_intent_cookie_enabled'] );
		$this->assertTrue( $this->read( $nx_id )['exit_intent_cookie_enabled'] );
		$this->assertEquals( 10, $this->read( $nx_id )['exit_intent_cookie_days'] );
	}

	// --- campaigns saved with the toggle ----------------------------------------

	public function test_a_saved_off_stays_off_even_with_days() {
		$nx_id = $this->save( array( 'exit_intent_cookie_enabled' => false, 'exit_intent_cookie_days' => 7 ) );
		$this->assertFalse( $this->read( $nx_id )['exit_intent_cookie_enabled'] );
		$this->assertFalse( $this->payload( $nx_id )['exit_intent_cookie_enabled'] );
	}

	public function test_a_saved_on_stays_on() {
		$nx_id = $this->save( array( 'exit_intent_cookie_enabled' => true, 'exit_intent_cookie_days' => 3 ) );
		$this->assertTrue( $this->read( $nx_id )['exit_intent_cookie_enabled'] );
		$this->assertTrue( $this->payload( $nx_id )['exit_intent_cookie_enabled'] );
		$this->assertEquals( 3, $this->payload( $nx_id )['exit_intent_cookie_days'] );
	}

	public function test_a_new_campaign_without_the_toggle_or_days_is_off() {
		// What a REST / MCP create without these fields stores.
		$nx_id = $this->save( array() );
		$this->assertFalse( $this->read( $nx_id )['exit_intent_cookie_enabled'] );
	}

	public function test_a_save_with_days_but_no_toggle_is_on() {
		// An export from before the toggle, imported now; or a REST call that sets days.
		$nx_id = $this->save( array( 'exit_intent_cookie_days' => 5 ) );
		$this->assertTrue( $this->stored( $nx_id )['exit_intent_cookie_enabled'] );
		$this->assertTrue( $this->read( $nx_id )['exit_intent_cookie_enabled'] );
	}

	// --- other types ------------------------------------------------------------

	public function test_other_types_get_no_toggle() {
		$nx_id = $this->insert_legacy( array( 'exit_intent_cookie_duration' => 7, 'convert_to_exit_intent' => true ), 'popup_notification', 'popup' );
		$post  = $this->read( $nx_id );
		// normalize_post() gives every field's default to every type (as it
		// already does for the days); the read filter leaves other types alone.
		$this->assertFalse( $post['exit_intent_cookie_enabled'] );
		$this->assertArrayNotHasKey( 'exit_intent_cookie_toggle_saved', $post );
		$saved = PostType::get_instance()->save_post( array_merge( $post, array( 'title' => 'popup' ) ) );
		$this->assertArrayNotHasKey( 'exit_intent_cookie_toggle_saved', $this->stored( $saved['nx_id'] ) );
	}
}
