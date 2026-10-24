<?php
/**
 * Regression tests for the legacy Zapier / IFTTT API key grace window.
 *
 * `POST /notificationx/v1/notification/{id}` accepts the random key from
 * Integration::get_api_key(), and the old `md5( home_url() )` key only during a
 * 14-day grace window. The window opened on the first update after which the
 * site had any Zapier / IFTTT notification, so a site that already had the
 * random key and added a Zapier notification later got a fresh window on its
 * next update. Anyone can compute md5( home_url() ), so for 14 days any visitor
 * could add entries to any notification.
 *
 * The fix: Integration::maybe_seed_from_upgrade() opens the window only on the
 * site's first seeding (no seed marker and no random key yet), i.e. the upgrade
 * that introduces the random key.
 *
 * @package Notificationx
 */

use NotificationX\Core\Database;
use NotificationX\Core\Rest\Integration;

class Test_Security_Integration_Key extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		delete_option( Integration::OPT_API_KEY );
		delete_option( Integration::OPT_GRACE_STARTED_AT );
		delete_option( Integration::OPT_LEGACY_KEY_LAST_USED );
		delete_option( Integration::OPT_SEEDED_VERSION );
	}

	protected function legacy_key() {
		return md5( home_url( '', 'http' ) );
	}

	/**
	 * A Zapier notification row. Inserted directly so the test does not need
	 * NotificationX Pro, which owns the Zapier source.
	 */
	protected function create_zapier_notification() {
		global $wpdb;
		$wpdb->insert(
			Database::$table_posts,
			array(
				'title'      => 'Zapier sales',
				'type'       => 'conversions',
				'source'     => 'zapier_conversions',
				'theme'      => 'conversions_theme-one',
				'enabled'    => 1,
				'data'       => '',
				'created_at' => current_time( 'mysql' ),
				'updated_at' => current_time( 'mysql' ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	public function test_fresh_install_never_opens_grace() {
		Integration::maybe_seed_from_upgrade();

		$this->assertNotEmpty( get_option( Integration::OPT_API_KEY ) );
		$this->assertEmpty( get_option( Integration::OPT_GRACE_STARTED_AT ) );
		$this->assertFalse( Integration::is_valid_api_key( $this->legacy_key() ) );
	}

	public function test_upgrade_from_legacy_site_opens_grace() {
		// Before the random key existed: no seed marker, no key, a Zapier notification.
		$this->create_zapier_notification();

		Integration::maybe_seed_from_upgrade();

		$this->assertNotEmpty( get_option( Integration::OPT_GRACE_STARTED_AT ) );
		$this->assertTrue( Integration::is_valid_api_key( $this->legacy_key() ) );
		$this->assertTrue( Integration::is_valid_api_key( Integration::get_api_key() ) );
	}

	public function test_later_update_with_new_zapier_notification_keeps_legacy_key_rejected() {
		// The site already had the random key and no Zapier notification.
		update_option( Integration::OPT_SEEDED_VERSION, '3.3.4', false );
		$key = Integration::get_api_key();

		// A Zapier notification is added later, then the plugin updates.
		$this->create_zapier_notification();
		Integration::maybe_seed_from_upgrade();

		$this->assertEmpty( get_option( Integration::OPT_GRACE_STARTED_AT ) );
		$this->assertFalse( Integration::is_valid_api_key( $this->legacy_key() ) );
		$this->assertTrue( Integration::is_valid_api_key( $key ) );
		$this->assertEmpty( get_option( Integration::OPT_LEGACY_KEY_LAST_USED ) );
	}

	public function test_open_grace_window_survives_later_updates() {
		// A legacy site in its grace window updates again: the window is kept.
		$this->create_zapier_notification();
		update_option( Integration::OPT_SEEDED_VERSION, '3.3.4', false );
		Integration::get_api_key();
		$started = time() - 3 * DAY_IN_SECONDS;
		update_option( Integration::OPT_GRACE_STARTED_AT, $started, false );

		Integration::maybe_seed_from_upgrade();

		$this->assertSame( $started, (int) get_option( Integration::OPT_GRACE_STARTED_AT ) );
		$this->assertTrue( Integration::is_valid_api_key( $this->legacy_key() ) );
	}

	public function test_expired_grace_window_rejects_legacy_key() {
		$this->create_zapier_notification();
		update_option( Integration::OPT_SEEDED_VERSION, '3.3.4', false );
		Integration::get_api_key();
		update_option( Integration::OPT_GRACE_STARTED_AT, time() - 15 * DAY_IN_SECONDS, false );

		Integration::maybe_seed_from_upgrade();

		$this->assertFalse( Integration::is_valid_api_key( $this->legacy_key() ) );
	}

	public function test_missing_key_on_seeded_site_does_not_open_grace() {
		// The seed marker exists but the key option was removed: the key is
		// regenerated, and the legacy key does not start working.
		$this->create_zapier_notification();
		update_option( Integration::OPT_SEEDED_VERSION, '3.3.4', false );

		Integration::maybe_seed_from_upgrade();

		$this->assertNotEmpty( get_option( Integration::OPT_API_KEY ) );
		$this->assertEmpty( get_option( Integration::OPT_GRACE_STARTED_AT ) );
		$this->assertFalse( Integration::is_valid_api_key( $this->legacy_key() ) );
	}

	public function test_same_version_is_a_no_op() {
		update_option( Integration::OPT_SEEDED_VERSION, NOTIFICATIONX_VERSION, false );
		$this->create_zapier_notification();

		Integration::maybe_seed_from_upgrade();

		$this->assertEmpty( get_option( Integration::OPT_API_KEY ) );
		$this->assertEmpty( get_option( Integration::OPT_GRACE_STARTED_AT ) );
	}
}
