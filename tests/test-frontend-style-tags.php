<?php
/**
 * Tests for the non-blocking stylesheet tags (FrontEnd::non_blocking_style_tag())
 * and the Gutenberg bar asset pass (FrontEnd::enqueue_gutenberg_bar_assets()).
 *
 * @package Notificationx
 */

use NotificationX\FrontEnd\FrontEnd;

class Test_FrontEnd_Style_Tags extends WP_UnitTestCase {

	protected function tag( $handle ) {
		return "<link rel='stylesheet' id='{$handle}-css' href='https://example.org/{$handle}.css?ver=1' media='all' />\n";
	}

	public function setUp(): void {
		parent::setUp();
		if ( ! wp_style_is( 'notificationx-public', 'registered' ) ) {
			wp_register_style( 'notificationx-public', home_url( '/wp-content/plugins/notificationx/assets/public/css/frontend.css' ), array(), '1.0' );
		}
	}

	public function test_deferred_sheets_keep_the_media_swap_and_are_marked_for_the_runtime() {
		foreach ( array( 'notificationx-public', 'notificationx-gdpr-modal', 'notificationx-open-sans', 'notificationx-fontawesome-4' ) as $handle ) {
			$out = FrontEnd::get_instance()->non_blocking_style_tag( $this->tag( $handle ), $handle );
			// Same markup as before plus the marker, so an older cached runtime still works.
			$this->assertStringContainsString( "rel='stylesheet'", $out );
			$this->assertStringContainsString( "media='print' onload=\"this.media='all'\" data-nx-style='print'", $out );
			$this->assertStringContainsString( '<noscript>' . trim( $this->tag( $handle ) ) . '</noscript>', $out );
		}
	}

	public function test_other_handles_and_processed_tags_are_left_alone() {
		$tag = $this->tag( 'some-theme' );
		$this->assertSame( $tag, FrontEnd::get_instance()->non_blocking_style_tag( $tag, 'some-theme' ) );
		$once = FrontEnd::get_instance()->non_blocking_style_tag( $this->tag( 'notificationx-public' ), 'notificationx-public' );
		$this->assertSame( $once, FrontEnd::get_instance()->non_blocking_style_tag( $once, 'notificationx-public' ) );
	}

	public function test_localized_styles_list_the_enqueued_sheets() {
		wp_enqueue_style( 'notificationx-public' );
		$data = FrontEnd::get_instance()->get_localize_data( array() );
		wp_dequeue_style( 'notificationx-public' );
		$this->assertArrayHasKey( 'styles', $data );
		$this->assertStringContainsString( 'public/css/frontend.css?ver=', $data['styles']['notificationx-public'] );
		// The GDPR modal sheet is not on this page.
		$this->assertArrayNotHasKey( 'notificationx-gdpr-modal', $data['styles'] );
	}

	public function test_localized_styles_follow_style_loader_src() {
		wp_enqueue_style( 'notificationx-public' );
		$cdn = function ( $src ) {
			return str_replace( home_url(), 'https://cdn.example.org', $src );
		};
		add_filter( 'style_loader_src', $cdn );
		$data = FrontEnd::get_instance()->get_localize_data( array() );
		remove_filter( 'style_loader_src', $cdn );
		wp_dequeue_style( 'notificationx-public' );
		$this->assertStringStartsWith( 'https://cdn.example.org', $data['styles']['notificationx-public'] );
	}

	public function test_no_localized_styles_for_cross_domain_embeds_or_without_sheets() {
		wp_enqueue_style( 'notificationx-public' );
		$cross = FrontEnd::get_instance()->get_localize_data( array( 'cross' => true ) );
		wp_dequeue_style( 'notificationx-public' );
		$this->assertArrayNotHasKey( 'styles', $cross );
		$this->assertArrayNotHasKey( 'styles', FrontEnd::get_instance()->get_localize_data( array() ) );
	}

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
