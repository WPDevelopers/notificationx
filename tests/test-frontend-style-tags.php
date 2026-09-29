<?php
/**
 * Tests for the Gutenberg bar asset pass (FrontEnd::enqueue_gutenberg_bar_assets()).
 *
 * @package Notificationx
 */

use NotificationX\FrontEnd\FrontEnd;

class Test_FrontEnd_Style_Tags extends WP_UnitTestCase {

	public function test_gutenberg_bar_asset_pass_prints_nothing() {
		register_block_type(
			'nxtest/echoes',
			array(
				'render_callback' => function () {
					echo '<div class="nxtest-leak">leak</div>';
					return '';
				},
			)
		);
		$id = self::factory()->post->create(
			array(
				'post_type'    => 'nx_bar_eb',
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:nxtest/echoes /-->',
			)
		);
		$method = new ReflectionMethod( FrontEnd::class, 'enqueue_gutenberg_bar_assets' );
		$method->setAccessible( true );
		// The pass only runs during wp_enqueue_scripts.
		global $wp_current_filter;
		$wp_current_filter[] = 'wp_enqueue_scripts';
		ob_start();
		$method->invoke( FrontEnd::get_instance(), $id );
		$printed = ob_get_clean();
		array_pop( $wp_current_filter );
		unregister_block_type( 'nxtest/echoes' );
		$this->assertSame( '', $printed );
	}
}
