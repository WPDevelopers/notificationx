<?php
/**
 * WordPress Abilities API bridge tests.
 *
 * The Abilities API rejects any ability whose category is not registered, so
 * NotificationX has to register the `notificationx` category on
 * `wp_abilities_api_categories_init` before core fires
 * `wp_abilities_api_init`. These tests cover that contract: the category
 * exists, every ability mirrors into core under it, and the bridge stays
 * silent (no `_doing_it_wrong()`) when the category is missing.
 *
 * Skipped wholesale on WordPress versions that ship no Abilities API.
 *
 * @package Notificationx
 */

use NotificationX\Abilities\Registrar;

/**
 * @group abilities
 */
class Test_Abilities_Category extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();

		if ( ! function_exists( 'wp_register_ability' ) || ! function_exists( 'wp_register_ability_category' ) ) {
			$this->markTestSkipped( 'The WordPress Abilities API is not available on this WordPress version.' );
		}
	}

	/**
	 * The plugin must register its own category; without it core refuses
	 * every ability with "Ability category "notificationx" is not registered".
	 */
	public function test_ability_category_is_registered() {
		$this->assertTrue(
			wp_has_ability_category( Registrar::CATEGORY ),
			'The "' . Registrar::CATEGORY . '" ability category was not registered.'
		);

		$category = wp_get_ability_category( Registrar::CATEGORY );

		$this->assertNotNull( $category );
		$this->assertNotEmpty( $category->get_label() );
		$this->assertNotEmpty( $category->get_description() );
	}

	/**
	 * Every ability the plugin registry holds must land in core's registry
	 * under our category. A failure here is the symptom users see: the
	 * abilities silently disappear from wp_get_abilities()/the REST index.
	 */
	public function test_every_ability_is_mirrored_into_core() {
		$abilities = Registrar::get_instance()->get_all();

		$this->assertNotEmpty( $abilities, 'The NotificationX ability registry is empty.' );

		foreach ( $abilities as $id => $ability ) {
			$this->assertTrue(
				wp_has_ability( $id ),
				"Ability '{$id}' was not registered with the WordPress Abilities API."
			);

			$this->assertSame(
				Registrar::CATEGORY,
				wp_get_ability( $id )->get_category(),
				"Ability '{$id}' was registered under an unexpected category."
			);
		}
	}

	/**
	 * A representative id, pinned so a rename cannot quietly break the
	 * category wiring this test file exists for.
	 */
	public function test_list_entries_ability_is_registered() {
		$this->assertTrue( wp_has_ability( 'notificationx/list-entries' ) );
	}

	/**
	 * Re-running the bridge is a no-op: already-registered abilities are
	 * skipped, so no duplicate-registration _doing_it_wrong() is emitted.
	 * WP_UnitTestCase fails the test on any unexpected _doing_it_wrong().
	 */
	public function test_re_running_the_bridge_is_silent() {
		Registrar::get_instance()->register_with_wp_abilities();

		$this->assertTrue( wp_has_ability( 'notificationx/list-entries' ) );
	}

	/**
	 * If the module ever boots after `wp_abilities_api_categories_init` has
	 * fired, the category cannot be registered any more. The bridge must then
	 * bail instead of emitting one _doing_it_wrong() notice per ability.
	 */
	public function test_bridge_bails_when_category_is_missing() {
		$id       = 'notificationx/list-entries';
		$category = wp_get_ability_category( Registrar::CATEGORY );

		wp_unregister_ability( $id );
		wp_unregister_ability_category( Registrar::CATEGORY );

		try {
			Registrar::get_instance()->register_with_wp_abilities();

			$this->assertFalse(
				wp_has_ability( $id ),
				'The bridge registered an ability while its category was missing.'
			);
		} finally {
			// Restore global state for the rest of the suite. The public
			// wp_register_ability_category() only works during
			// `wp_abilities_api_categories_init`, which has long fired.
			WP_Ability_Categories_Registry::get_instance()->register(
				Registrar::CATEGORY,
				array(
					'label'       => $category->get_label(),
					'description' => $category->get_description(),
				)
			);
			// wp_register_ability() refuses (with _doing_it_wrong) unless
			// `wp_abilities_api_init` is the running action, so re-register
			// inside that action context.
			$GLOBALS['wp_current_filter'][] = 'wp_abilities_api_init';
			try {
				Registrar::get_instance()->register_with_wp_abilities();
			} finally {
				array_pop( $GLOBALS['wp_current_filter'] );
			}
		}

		$this->assertTrue( wp_has_ability( $id ) );
	}
}
