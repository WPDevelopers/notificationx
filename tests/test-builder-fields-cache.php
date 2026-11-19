<?php
/**
 * The builder's cached field list is rebuilt when the plugins it was built
 * from change, so field defaults such as is_elementor are never stale.
 *
 * @package NotificationX
 */

use NotificationX\NotificationX;

class Test_Builder_Fields_Cache extends WP_UnitTestCase {

	private $stale = array( 'stale' => true );

	public function tear_down() {
		delete_transient( 'nx_builder_fields' );
		delete_transient( 'nx_builder_fields_pro' );
		parent::tear_down();
	}

	/** Prime the cache with a marker, then let get_tab() record the current state. */
	private function prime() {
		delete_transient( 'nx_builder_fields_pro' );
		NotificationX::get_instance()->get_tab();
		set_transient( 'nx_builder_fields', $this->stale, DAY_IN_SECONDS );
	}

	public function test_unchanged_state_keeps_the_cache() {
		$this->prime();
		$this->assertSame( $this->stale, NotificationX::get_instance()->get_tab() );
	}

	public function test_value_from_before_this_change_rebuilds_once() {
		$this->prime();
		// Before 3.3.8 the key held only the Pro version (or 'none').
		set_transient( 'nx_builder_fields_pro', 'none', DAY_IN_SECONDS );
		$tabs = NotificationX::get_instance()->get_tab();
		$this->assertNotSame( $this->stale, $tabs );
		$this->assertArrayHasKey( 'tabs', $tabs );
	}

	public function test_essential_blocks_switched_on_rebuilds() {
		$this->prime();
		$active = get_option( 'active_plugins', array() );
		update_option( 'active_plugins', array_merge( (array) $active, array( 'essential-blocks/essential-blocks.php' ) ) );
		try {
			$this->assertNotSame( $this->stale, NotificationX::get_instance()->get_tab() );
		} finally {
			update_option( 'active_plugins', $active );
		}
	}
}
