<?php
/**
 * Exit Intent "Build With Gutenberg" on tablets: a column keeps its own width
 * from 768 to 781px, where core stacks Columns (GutenbergDesign::column_styles()).
 * The phone layout and the CSS rules are covered by
 * tests/js/exit-intent-gutenberg-responsive-css.test.js.
 *
 * @package Notificationx
 */

use NotificationX\Extensions\ExitIntent\GutenbergDesign;

class Test_Exit_Intent_Gutenberg_Responsive extends WP_UnitTestCase {

	public function test_design_three_columns_keep_their_widths_on_tablets() {
		$seed = GutenbergDesign::read_seed( 'theme-three' );
		$css  = GutenbergDesign::column_styles( GutenbergDesign::render( $seed['content'] ) );
		$this->assertStringStartsWith( '<style class="nx-exit-intent-gutenberg-columns">@media (min-width:768px) and (max-width:781px){', $css );
		$this->assertStringContainsString( '.nx-exit-intent-gutenberg-body .wp-block-columns:not(.is-not-stacked-on-mobile)>.wp-block-column[style*="flex-basis:42%"]{flex-basis:42%!important}', $css );
		$this->assertStringContainsString( '[style*="flex-basis:58%"]{flex-basis:58%!important}', $css );
	}

	public function test_designs_without_column_widths_get_nothing() {
		foreach ( array( 'theme-one', 'theme-two', 'theme-four' ) as $key ) {
			$seed = GutenbergDesign::read_seed( $key );
			$this->assertSame( '', GutenbergDesign::column_styles( GutenbergDesign::render( $seed['content'] ) ), $key );
		}
	}

	public function test_only_plain_lengths_are_written() {
		$html = '<div class="wp-block-columns"><div class="wp-block-column" style="flex-basis:33.33%"></div><div class="wp-block-column" style="flex-basis:240px"></div><div class="wp-block-column" style="flex-basis:calc(1px);x:</style>"></div></div>';
		$css  = GutenbergDesign::column_styles( $html );
		$this->assertStringContainsString( 'flex-basis:33.33%!important', $css );
		$this->assertStringContainsString( 'flex-basis:240px!important', $css );
		$this->assertStringNotContainsString( 'calc', $css );
		$this->assertSame( 1, substr_count( $css, '</style>' ) );
	}

	public function test_the_payload_carries_the_column_rules() {
		$seed = GutenbergDesign::read_seed( 'theme-three' );
		$id   = self::factory()->post->create( array( 'post_type' => GutenbergDesign::POST_TYPE, 'post_status' => 'publish', 'post_content' => wp_slash( $seed['content'] ) ) );
		$out  = apply_filters( 'nx_filtered_post', array( 'source' => 'exit_intent_custom', 'gutenberg_id' => $id, 'nx_id' => 1 ), array() );
		$this->assertStringContainsString( 'nx-exit-intent-gutenberg-columns', $out['gutenberg_html'] );
	}
}
