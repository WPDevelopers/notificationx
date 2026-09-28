<?php
/**
 * Tests for the `nx_status_updated` action.
 *
 * Enabling or disabling a notification from the list-page toggle or a bulk
 * action goes through PostType::update_status(), which fires no
 * `nx_saved_post`. Page-cache plugins need a signal there, because the
 * notifications a page renders (including GDPR notices) are printed into
 * its cached HTML. See docs/features/frontend-performance/.
 *
 * @package Notificationx
 */

use NotificationX\Core\PostType;

class Test_Status_Updated_Hook extends WP_UnitTestCase {

	/**
	 * Calls received by the `nx_status_updated` listener.
	 *
	 * @var array
	 */
	protected $calls = array();

	public function setUp(): void {
		parent::setUp();
		$this->calls = array();
		add_action( 'nx_status_updated', array( $this, 'record' ), 10, 3 );
	}

	public function tearDown(): void {
		remove_action( 'nx_status_updated', array( $this, 'record' ), 10 );
		remove_all_filters( 'nx_can_enable' );
		parent::tearDown();
	}

	public function record( $nx_id, $enabled, $source ) {
		$this->calls[] = array( $nx_id, $enabled, $source );
	}

	/**
	 * PostType caches the enabled-source map on the singleton, and that cache
	 * outlives the per-test DB rollback. Clear it so is_enabled() reads the DB.
	 */
	protected function reset_enabled_cache() {
		PostType::get_instance()->enabled_source = null;
	}

	protected function insert_notification( $enabled ) {
		$saved = PostType::get_instance()->save_post(
			array(
				'type'    => 'gdpr',
				'source'  => 'gdpr_notification',
				'themes'  => 'gdpr_theme-light-one',
				'enabled' => $enabled,
				'title'   => 'status hook test',
			)
		);
		$this->assertNotEmpty( $saved['nx_id'] );
		$this->reset_enabled_cache();
		return (int) $saved['nx_id'];
	}

	public function test_fires_when_notification_is_disabled() {
		$nx_id = $this->insert_notification( true );

		$result = PostType::get_instance()->update_status(
			array(
				'nx_id'   => $nx_id,
				'source'  => 'gdpr_notification',
				'enabled' => false,
			)
		);

		$this->assertNotFalse( $result );
		$this->assertSame( array( array( $nx_id, false, 'gdpr_notification' ) ), $this->calls );
	}

	public function test_fires_when_notification_is_enabled() {
		add_filter( 'nx_can_enable', '__return_true' );
		$nx_id = $this->insert_notification( false );

		$result = PostType::get_instance()->update_status(
			array(
				'nx_id'   => $nx_id,
				'source'  => 'gdpr_notification',
				'enabled' => true,
			)
		);

		$this->assertNotFalse( $result );
		$this->assertSame( array( array( $nx_id, true, 'gdpr_notification' ) ), $this->calls );
	}

	public function test_does_not_fire_when_status_is_unchanged() {
		$nx_id = $this->insert_notification( true );

		PostType::get_instance()->update_status(
			array(
				'nx_id'   => $nx_id,
				'source'  => 'gdpr_notification',
				'enabled' => true,
			)
		);

		$this->assertSame( array(), $this->calls );
	}

	public function test_does_not_fire_when_enabling_is_refused() {
		add_filter( 'nx_can_enable', '__return_false' );
		$nx_id = $this->insert_notification( false );

		PostType::get_instance()->update_status(
			array(
				'nx_id'   => $nx_id,
				'source'  => 'gdpr_notification',
				'enabled' => true,
			)
		);

		$this->assertSame( array(), $this->calls );
	}

	/**
	 * The admin list toggle posts `update_status` through save_post().
	 */
	public function test_fires_through_save_post_toggle_path() {
		$nx_id = $this->insert_notification( true );

		PostType::get_instance()->save_post(
			array(
				'update_status' => true,
				'nx_id'         => $nx_id,
				'source'        => 'gdpr_notification',
				'enabled'       => false,
			)
		);

		$this->assertCount( 1, $this->calls );
		$this->assertSame( $nx_id, $this->calls[0][0] );
		$this->assertFalse( $this->calls[0][1] );
	}

	/**
	 * Disabling may arrive without a source; the hook still fires with ''.
	 */
	public function test_source_defaults_to_empty_string() {
		$nx_id = $this->insert_notification( true );

		PostType::get_instance()->update_status(
			array(
				'nx_id'   => $nx_id,
				'enabled' => false,
			)
		);

		$this->assertSame( array( array( $nx_id, false, '' ) ), $this->calls );
	}
}
