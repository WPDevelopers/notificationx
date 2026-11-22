<?php
/**
 * Build With AI upsell tab for Announcement, Sales, Reviews and WooCommerce
 * (Extensions/GlobalFields.php), shown without NotificationX Pro.
 *
 * @package Notificationx
 */

use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;

class Test_Build_With_Ai_Upsell extends WP_UnitTestCase {

	private function design_fields() {
		return [
			'themes' => [ 'fields' => [ 'themes_section' => [ 'fields' => [ 'themes_tab' => [ 'fields' => [
				[ 'name' => 'for_desktop' ],
				[ 'name' => 'for_mobile' ],
			] ] ] ] ] ],
		];
	}

	private function our_tab( $fields ) {
		foreach ( $fields['themes']['fields']['themes_section']['fields']['themes_tab']['fields'] as $tab ) {
			if ( GlobalFields::AI_TYPES_TAB === ( $tab['name'] ?? '' ) ) {
				return $tab;
			}
		}
		return null;
	}

	public function test_the_tab_is_added_for_the_ai_types_without_pro() {
		if ( \NotificationX\NotificationX::is_pro() ) {
			$this->markTestSkipped( 'NotificationX Pro is loaded; Pro adds its own tab.' );
		}
		$tab = $this->our_tab( GlobalFields::get_instance()->build_with_ai_upsell_tab( $this->design_fields() ) );
		$this->assertNotNull( $tab );
		$this->assertSame( 'nx_type_build_with_ai', $tab['name'] );
		$this->assertEquals( Rules::includes( 'type', [ 'popup', 'conversions', 'reviews', 'woocommerce_sales' ] ), $tab['rules'] );
		$section = $tab['fields']['nx_type_build_with_ai'];
		$this->assertSame( 'exit_intent_ai_tab', $section['classes'], 'Gets the Exit Intent upsell card styles.' );
		$this->assertSame( 'nxbar-build_with_ai', $section['fields']['nx_type_build_with_ai_fields']['type'], 'The field that shows the Pro notice without Pro.' );
	}

	public function test_the_tab_is_registered_after_exit_intent_tabs() {
		$this->assertSame( 101, has_filter( 'nx_design_tab_fields', [ GlobalFields::get_instance(), 'build_with_ai_upsell_tab' ] ) );
	}

	public function test_fields_without_a_themes_tab_are_left_alone() {
		$fields = [ 'themes' => [ 'fields' => [] ] ];
		$this->assertSame( $fields, GlobalFields::get_instance()->build_with_ai_upsell_tab( $fields ) );
	}

	public function test_the_quick_builder_lists_the_tab_without_pro() {
		if ( \NotificationX\NotificationX::is_pro() ) {
			$this->markTestSkipped( 'NotificationX Pro is loaded.' );
		}
		$config = GlobalFields::get_instance()->build_with_ai_upsell_quick_builder( [ 'show' => [ 'themes' ] ] );
		$this->assertContains( 'nx_type_build_with_ai', $config['show'] );
		$this->assertContains( 'nx_type_build_with_ai_fields', $config['show'] );
	}
}
