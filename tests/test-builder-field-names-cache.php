<?php
/**
 * The field list that normalize_post() needs on every notification read is
 * cached apart from the large builder fields and loaded once per request, so a
 * store that cannot keep the large value does not rebuild it per notification.
 *
 * @package NotificationX
 */

use NotificationX\NotificationX;

class Test_Builder_Field_Names_Cache extends WP_UnitTestCase {

	/** @var NotificationX */
	private $nx;

	/** @var int Times the builder fields were built. */
	private $builds = 0;

	/** @var int Times the large builder fields value was read. */
	private $large_reads = 0;

	public function set_up() {
		parent::set_up();
		$this->nx = NotificationX::get_instance();
		delete_transient( 'nx_builder_fields' );
		delete_transient( 'nx_builder_fields_pro' );
		$this->builds      = 0;
		$this->large_reads = 0;
		// GlobalFields::tabs() applies this filter once per build.
		add_filter( 'nx_is_pro_sources', array( $this, 'count_build' ) );
		add_filter( 'transient_nx_builder_fields', array( $this, 'count_large_read' ) );
	}

	public function tear_down() {
		remove_filter( 'nx_is_pro_sources', array( $this, 'count_build' ) );
		remove_filter( 'transient_nx_builder_fields', array( $this, 'count_large_read' ) );
		remove_filter( 'transient_nx_builder_fields', '__return_false', 99 );
		remove_filter( 'transient_' . NotificationX::FIELD_NAMES_TRANSIENT, '__return_false', 99 );
		delete_transient( 'nx_builder_fields' );
		delete_transient( 'nx_builder_fields_pro' );
		parent::tear_down();
	}

	public function count_build( $value ) {
		$this->builds++;
		return $value;
	}

	public function count_large_read( $value ) {
		$this->large_reads++;
		return $value;
	}

	/** A new request has no field list in memory. */
	private function new_request() {
		$memo = new ReflectionProperty( NotificationX::class, 'field_names' );
		$memo->setAccessible( true );
		$memo->setValue( $this->nx, array() );
		$this->builds      = 0;
		$this->large_reads = 0;
	}

	public function test_field_list_matches_the_builder_fields() {
		$tabs     = $this->nx->get_tab();
		$expected = $this->nx->_get_field_names( $tabs['tabs'] );

		$this->assertNotEmpty( $expected );
		$this->assertSame( $expected, $this->nx->get_field_names() );
		$this->assertSame( $expected, get_transient( NotificationX::FIELD_NAMES_TRANSIENT ) );
	}

	public function test_a_request_does_not_read_the_large_value() {
		$expected = $this->nx->get_field_names();
		$this->new_request();

		for ( $i = 0; $i < 5; $i++ ) {
			$this->nx->normalize_post( array( 'source' => 'press_bar' ) );
		}

		$this->assertSame( $expected, $this->nx->get_field_names() );
		$this->assertSame( 0, $this->large_reads, 'The large builder fields value was read on a warm request.' );
		$this->assertSame( 0, $this->builds );
	}

	public function test_large_value_not_kept_costs_no_rebuild() {
		$expected = $this->nx->get_field_names();
		// As with an object cache that rejects values over its item size limit.
		add_filter( 'transient_nx_builder_fields', '__return_false', 99 );
		$this->new_request();

		for ( $i = 0; $i < 5; $i++ ) {
			$this->nx->normalize_post( array( 'source' => 'press_bar' ) );
		}

		$this->assertSame( 0, $this->builds );
		$this->assertSame( $expected, $this->nx->get_field_names() );
	}

	public function test_nothing_kept_rebuilds_once_per_request() {
		$expected = $this->nx->get_field_names();
		add_filter( 'transient_nx_builder_fields', '__return_false', 99 );
		add_filter( 'transient_' . NotificationX::FIELD_NAMES_TRANSIENT, '__return_false', 99 );
		$this->new_request();

		for ( $i = 0; $i < 5; $i++ ) {
			$this->nx->normalize_post( array( 'source' => 'press_bar' ) );
		}

		$this->assertSame( 1, $this->builds, 'The builder fields were rebuilt for every notification.' );
		// Keys only: some defaults hold the build time, so a rebuild changes them.
		$this->assertSame( array_keys( $expected ), array_keys( $this->nx->get_field_names() ) );
	}

	public function test_clearing_the_builder_fields_clears_the_field_list() {
		$this->nx->get_field_names();
		$this->assertIsArray( get_transient( NotificationX::FIELD_NAMES_TRANSIENT ) );

		delete_transient( 'nx_builder_fields' );

		$this->assertFalse( get_transient( NotificationX::FIELD_NAMES_TRANSIENT ) );
		$this->builds = 0;
		$this->assertNotEmpty( $this->nx->get_field_names() );
		$this->assertSame( 1, $this->builds, 'The field list was served from memory after the cache was cleared.' );
	}

	public function test_replacing_the_builder_fields_replaces_the_field_list() {
		$this->nx->get_field_names();

		set_transient(
			'nx_builder_fields',
			array(
				'tabs' => array(
					array(
						'type' => 'text',
						'name' => 'only_field',
					),
				),
			),
			DAY_IN_SECONDS
		);

		$this->assertSame( array( 'only_field' ), array_keys( $this->nx->get_field_names() ) );
	}

	public function test_changed_environment_rebuilds_the_field_list() {
		$this->nx->get_field_names();
		// What the key held before the Pro version, Elementor or Essential Blocks changed.
		set_transient( 'nx_builder_fields_pro', 'other', DAY_IN_SECONDS );
		$this->builds = 0;

		$this->assertNotEmpty( $this->nx->get_field_names() );
		$this->assertSame( 1, $this->builds );
	}

	public function test_fields_cached_by_an_older_version_are_reused() {
		$tabs = $this->nx->get_tab();
		// An update from a version that cached only the builder fields.
		delete_transient( NotificationX::FIELD_NAMES_TRANSIENT );
		$this->new_request();

		$this->assertSame( $this->nx->_get_field_names( $tabs['tabs'] ), $this->nx->get_field_names() );
		$this->assertSame( 0, $this->builds );
		$this->assertLessThanOrEqual(
			(int) get_option( '_transient_timeout_nx_builder_fields' ),
			(int) get_option( '_transient_timeout_' . NotificationX::FIELD_NAMES_TRANSIENT ),
			'The field list outlives the builder fields it was derived from.'
		);
	}

	public function test_saving_settings_clears_the_field_list() {
		$this->nx->get_field_names();
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		if ( ! current_user_can( 'edit_notificationx_settings' ) ) {
			get_role( 'administrator' )->add_cap( 'edit_notificationx_settings' );
			wp_set_current_user( 0 );
			wp_set_current_user( $admin );
		}

		\NotificationX\Admin\Settings::get_instance()->save_settings( array() );

		$this->assertFalse( get_transient( NotificationX::FIELD_NAMES_TRANSIENT ) );
	}

	/** Each site of a network has its own modules, so its own field list. */
	public function test_field_list_in_memory_is_per_site() {
		$expected = $this->nx->get_field_names();
		$memo     = new ReflectionProperty( NotificationX::class, 'field_names' );
		$memo->setAccessible( true );
		$memo->setValue( $this->nx, array( get_current_blog_id() + 1 => array( 'other_site_field' => array() ) ) );

		$this->assertSame( $expected, $this->nx->get_field_names() );
	}

	/** @group ms-required */
	public function test_switching_site_does_not_reuse_the_field_list() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Needs a network.' );
		}
		$expected = $this->nx->get_field_names();
		$other    = self::factory()->blog->create();

		switch_to_blog( $other );
		// Record this site's state first, or the next read clears the value below.
		$this->nx->get_tab();
		set_transient(
			'nx_builder_fields',
			array(
				'tabs' => array(
					array(
						'type' => 'text',
						'name' => 'other_site_field',
					),
				),
			),
			DAY_IN_SECONDS
		);
		$on_other = array_keys( $this->nx->get_field_names() );
		restore_current_blog();

		$this->assertSame( array( 'other_site_field' ), $on_other );
		$this->assertSame( $expected, $this->nx->get_field_names() );
	}

	public function test_explicit_fields_are_not_cached() {
		$names = $this->nx->get_field_names(
			array(
				'tabs' => array(
					array(
						'type' => 'text',
						'name' => 'settings_field',
					),
				),
			)
		);

		$this->assertSame( array( 'settings_field' ), array_keys( $names ) );
		$this->assertArrayNotHasKey( 'settings_field', $this->nx->get_field_names() );
	}
}
