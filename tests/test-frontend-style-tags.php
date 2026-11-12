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

	/**
	 * Runs the Gutenberg bar asset pass for a bar post and returns the body classes.
	 */
	protected function bar_body_classes( $id ) {
		$frontend = FrontEnd::get_instance();
		$assets   = new ReflectionProperty( FrontEnd::class, 'gutenberg_bar_assets' );
		$assets->setAccessible( true );
		$assets->setValue( $frontend, array() );
		$method = new ReflectionMethod( FrontEnd::class, 'enqueue_gutenberg_bar_assets' );
		$method->setAccessible( true );
		global $wp_current_filter;
		$wp_current_filter[] = 'wp_enqueue_scripts';
		$method->invoke( $frontend, $id );
		array_pop( $wp_current_filter );
		$classes = $frontend->nx_add_body_class( array() );
		$assets->setValue( $frontend, array() );
		return $classes;
	}

	protected function bar( $content ) {
		return self::factory()->post->create(
			array(
				'post_type'    => 'nx_bar_eb',
				'post_status'  => 'publish',
				'post_content' => $content,
			)
		);
	}

	public function test_core_block_bar_is_marked() {
		$id = $this->bar( '<!-- wp:paragraph --><p>Sale</p><!-- /wp:paragraph -->' );
		$this->assertContains( 'nx-bar-assets-' . $id, $this->bar_body_classes( $id ) );
	}

	public function test_essential_blocks_bar_waits_for_its_stylesheets() {
		if ( ! defined( 'ESSENTIAL_BLOCKS_FILE' ) ) {
			define( 'ESSENTIAL_BLOCKS_FILE', __FILE__ );
		}
		$id = $this->bar( '<!-- wp:essential-blocks/row {"blockId":"eb-row-test"} --><div class="eb-row-test"></div><!-- /wp:essential-blocks/row -->' );

		// No stylesheets yet: unmarked, so the runtime fetches the bar's page.
		$this->assertNotContains( 'nx-bar-assets-' . $id, $this->bar_body_classes( $id ) );

		// Only one of the two files: still unmarked.
		$dir = trailingslashit( wp_upload_dir()['basedir'] ) . 'eb-style/';
		wp_mkdir_p( $dir . 'frontend' );
		$files = array( $dir . "eb-style-{$id}.min.css", $dir . "frontend/frontend-{$id}.min.css" );
		file_put_contents( $files[0], '.eb-row-test{display:flex}' );
		$this->assertNotContains( 'nx-bar-assets-' . $id, $this->bar_body_classes( $id ) );

		// Once Essential Blocks has written both, the bar takes the fast path.
		file_put_contents( $files[1], '.eb-row-test{display:flex}' );
		$classes = $this->bar_body_classes( $id );
		foreach ( $files as $file ) {
			unlink( $file );
		}
		$this->assertContains( 'nx-bar-assets-' . $id, $classes );
	}
}
