<?php
/**
 * Exit Intent "Build With Gutenberg" on the front end: the `notice` payload,
 * what may be rendered, the render pipeline, the popup layout, the block-asset
 * pass and the WP Rocket safelist.
 *
 * @package Notificationx
 */

use NotificationX\Extensions\ExitIntent\GutenbergDesign;

class Test_Exit_Intent_Gutenberg_Frontend extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		// The style queue is global and survives between tests; keep the registrations.
		wp_styles()->queue = array();
	}

	public function tear_down() {
		wp_styles()->queue = array();
		remove_shortcode( 'nxqa_sc' );
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	protected function design( $key = 'theme-one', $args = array() ) {
		$seed = GutenbergDesign::read_seed( $key );
		return self::factory()->post->create(
			array_merge(
				array(
					'post_type'    => 'nx_exit_intent_eb',
					'post_status'  => 'publish',
					'post_content' => $seed['content'],
				),
				$args
			)
		);
	}

	protected function settings( $gutenberg_id, $extra = array() ) {
		return array_merge(
			array(
				'nx_id'                => 36,
				'source'               => 'exit_intent_custom',
				'themes'               => null,
				'gutenberg_id'         => $gutenberg_id,
				'gutenberg_edit_link'  => 'http://example.org/wp-admin/post.php?post=' . $gutenberg_id . '&action=edit',
				'gutenberg_exit_theme' => 'theme-one',
				'is_gb_confirmed'      => true,
				'is_gutenberg'         => true,
			),
			$extra
		);
	}

	/* --- payload ----------------------------------------------------------- */

	public function test_a_published_design_goes_into_the_payload() {
		$id       = $this->design( 'theme-one' );
		$settings = apply_filters( 'nx_filtered_post', $this->settings( $id ), array() );
		$this->assertSame( 'gutenberg', $settings['mode'] );
		$this->assertStringContainsString( 'Wait! Here', $settings['gutenberg_html'] );
		$this->assertStringContainsString( 'STAY10', $settings['gutenberg_html'] );
		$this->assertStringContainsString( 'wp-block-group', $settings['gutenberg_html'] );
		$this->assertStringNotContainsString( '<!-- wp:', $settings['gutenberg_html'] );
		$this->assertSame( array( 'width' => '520px', 'radius' => '16px' ), $settings['gutenberg_layout'] );
		$this->assertSame( get_permalink( $id ), $settings['gutenberg_url'] );
	}

	public function test_builder_only_keys_never_reach_visitors() {
		$id       = $this->design();
		$settings = apply_filters( 'nx_filtered_post', $this->settings( $id ), array() );
		foreach ( GutenbergDesign::BUILDER_ONLY_KEYS as $key ) {
			$this->assertArrayNotHasKey( $key, $settings, $key );
		}
		$this->assertSame( $id, $settings['gutenberg_id'] );
	}

	public function test_other_sources_are_untouched() {
		$id      = $this->design();
		$bar     = array( 'source' => 'press_bar', 'gutenberg_id' => $id, 'gutenberg_edit_link' => 'x', 'is_gutenberg' => true );
		$result  = GutenbergDesign::get_instance()->inject_gutenberg_html( $bar, array() );
		$this->assertSame( $bar, $result );
	}

	public function test_an_elementor_design_wins() {
		$id     = $this->design();
		$result = GutenbergDesign::get_instance()->inject_gutenberg_html( $this->settings( $id, array( 'mode' => 'elementor', 'elementor_html' => '<p>e</p>' ) ), array() );
		$this->assertSame( 'elementor', $result['mode'] );
		$this->assertArrayNotHasKey( 'gutenberg_html', $result );
	}

	public function test_no_design_keeps_the_built_in_popup() {
		$settings = apply_filters( 'nx_filtered_post', $this->settings( false ), array() );
		$this->assertSame( 'built_in', $settings['mode'] );
		$this->assertArrayNotHasKey( 'gutenberg_html', $settings );
	}

	public function unrenderable_provider() {
		return array(
			'draft'           => array( array( 'post_status' => 'draft' ) ),
			'private'         => array( array( 'post_status' => 'private' ) ),
			'password'        => array( array( 'post_password' => 'secret' ) ),
			'trashed'         => array( array( 'post_status' => 'trash' ) ),
			'a page'          => array( array( 'post_type' => 'page' ) ),
			'a post'          => array( array( 'post_type' => 'post' ) ),
			'a bar design'    => array( array( 'post_type' => 'nx_bar_eb' ) ),
			'a reusable block'=> array( array( 'post_type' => 'wp_block' ) ),
			'empty content'   => array( array( 'post_content' => '' ) ),
		);
	}

	/**
	 * @dataProvider unrenderable_provider
	 */
	public function test_visitors_never_get_anything_but_a_published_design( $args ) {
		wp_set_current_user( 0 );
		$id       = $this->design( 'theme-one', $args );
		$settings = apply_filters( 'nx_filtered_post', $this->settings( $id ), array() );
		$this->assertSame( 'built_in', $settings['mode'] );
		$this->assertArrayNotHasKey( 'gutenberg_html', $settings );
	}

	public function test_a_missing_design_falls_back() {
		$settings = apply_filters( 'nx_filtered_post', $this->settings( 999999 ), array() );
		$this->assertSame( 'built_in', $settings['mode'] );
	}

	public function test_an_editor_sees_their_draft_design() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$id       = $this->design( 'theme-one', array( 'post_status' => 'draft' ) );
		$settings = apply_filters( 'nx_filtered_post', $this->settings( $id ), array() );
		$this->assertSame( 'gutenberg', $settings['mode'] );
	}

	public function test_the_builder_preview_shows_the_block_design() {
		$id       = $this->design( 'theme-one' );
		$settings = apply_filters( 'nx_preview_settings_exit_intent_custom', $this->settings( $id ) );
		$this->assertSame( 'gutenberg', $settings['mode'] );
		$this->assertStringContainsString( 'STAY10', $settings['gutenberg_html'] );
		$this->assertSame( get_permalink( $id ), $settings['gutenberg_url'] );
	}

	public function test_the_builder_preview_without_a_design_is_unchanged() {
		$settings = array( 'source' => 'exit_intent_custom', 'themes' => 'exit_intent_custom_theme-one' );
		$this->assertSame( $settings, apply_filters( 'nx_preview_settings_exit_intent_custom', $settings ) );
	}

	/* --- render pipeline --------------------------------------------------- */

	public function test_render_runs_shortcodes_and_embeds_but_not_the_content_filter() {
		add_shortcode( 'nxqa_sc', function () {
			return '<span class="nxqa-shortcode">ok</span>';
		} );
		wp_embed_register_handler( 'nxqa', '#https://nxqa\.example/video/(\d+)#', function ( $m ) {
			return '<iframe class="nxqa-embed" data-id="' . esc_attr( $m[1] ) . '"></iframe>';
		} );
		add_filter( 'the_content', function ( $c ) {
			return $c . '<div class="nxqa-share-buttons"></div>';
		} );
		$content = "<!-- wp:paragraph -->\n<p>[nxqa_sc]</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:embed {\"url\":\"https://nxqa.example/video/7\"} -->\n<figure class=\"wp-block-embed\"><div class=\"wp-block-embed__wrapper\">\nhttps://nxqa.example/video/7\n</div></figure>\n<!-- /wp:embed -->";
		$html    = GutenbergDesign::render( $content );
		$this->assertStringContainsString( 'nxqa-shortcode', $html );
		$this->assertStringContainsString( 'nxqa-embed', $html );
		$this->assertStringNotContainsString( 'nxqa-share-buttons', $html );
		wp_embed_unregister_handler( 'nxqa' );
	}

	public function test_render_runs_the_countdown_block() {
		$seed = GutenbergDesign::read_seed( 'theme-two' );
		$html = GutenbergDesign::render( $seed['content'] );
		$this->assertStringContainsString( 'nx-countdown-wrapper', $html );
		$this->assertStringContainsString( 'data-countdown-type="evergreen"', $html );
		$this->assertStringContainsString( 'background-color: #312e81', $html );
	}

	/* --- layout ------------------------------------------------------------ */

	public function test_layout_reads_the_outer_group() {
		foreach ( array( 'theme-one' => '520px', 'theme-two' => '560px', 'theme-three' => '760px', 'theme-four' => '560px' ) as $key => $width ) {
			$seed = GutenbergDesign::read_seed( $key );
			$this->assertSame( array( 'width' => $width, 'radius' => '16px' ), GutenbergDesign::layout( $seed['content'] ), $key );
		}
	}

	public function test_layout_ignores_odd_values_and_falls_back() {
		$this->assertSame( array( 'width' => '600px', 'radius' => '0' ), GutenbergDesign::layout( '<p>No blocks</p>' ) );
		$odd = '<!-- wp:group {"style":{"border":{"radius":{"topLeft":"8px"}}},"layout":{"type":"constrained","contentSize":"calc(100% - 1px);background:url(x)"}} --><div class="wp-block-group"></div><!-- /wp:group -->';
		$this->assertSame( array( 'width' => '600px', 'radius' => '0' ), GutenbergDesign::layout( $odd ) );
		$rem = '<!-- wp:group {"style":{"border":{"radius":"1.5rem"}},"layout":{"type":"constrained","contentSize":"40rem"}} --><div class="wp-block-group"></div><!-- /wp:group -->';
		$this->assertSame( array( 'width' => '40rem', 'radius' => '1.5rem' ), GutenbergDesign::layout( $rem ) );
	}

	/* --- block assets on the page ----------------------------------------- */

	public function test_asset_pass_enqueues_block_assets_prints_nothing_and_marks_the_body() {
		$id = $this->design( 'theme-two' );
		wp_dequeue_style( 'notificationx-countdown-style' );
		$hook = function () use ( $id ) {
			GutenbergDesign::get_instance()->enqueue_assets( $id );
		};
		add_action( 'wp_enqueue_scripts', $hook );
		ob_start();
		do_action( 'wp_enqueue_scripts' );
		$printed = ob_get_clean();
		remove_action( 'wp_enqueue_scripts', $hook );
		$this->assertSame( '', $printed );
		$this->assertTrue( wp_style_is( 'notificationx-countdown-style', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'notificationx-countdown-frontend', 'enqueued' ) );
		$this->assertContains( 'nx-block-assets-' . $id, GutenbergDesign::get_instance()->body_class( array() ) );
	}

	public function test_asset_pass_runs_only_during_wp_enqueue_scripts() {
		$id = $this->design( 'theme-one' );
		GutenbergDesign::get_instance()->enqueue_assets( $id );
		$this->assertNotContains( 'nx-block-assets-' . $id, GutenbergDesign::get_instance()->body_class( array() ) );
	}

	public function test_asset_pass_skips_designs_visitors_may_not_see() {
		$id   = $this->design( 'theme-one', array( 'post_status' => 'draft' ) );
		$hook = function () use ( $id ) {
			GutenbergDesign::get_instance()->enqueue_assets( $id );
		};
		add_action( 'wp_enqueue_scripts', $hook );
		do_action( 'wp_enqueue_scripts' );
		remove_action( 'wp_enqueue_scripts', $hook );
		$this->assertNotContains( 'nx-block-assets-' . $id, GutenbergDesign::get_instance()->body_class( array() ) );
	}

	public function test_essential_blocks_design_waits_for_its_stylesheets() {
		$id   = $this->design( 'theme-one', array( 'post_content' => '<!-- wp:essential-blocks/row --><div class="eb-row"></div><!-- /wp:essential-blocks/row -->' ) );
		$hook = function () use ( $id ) {
			GutenbergDesign::get_instance()->enqueue_assets( $id );
		};
		add_action( 'wp_enqueue_scripts', $hook );
		do_action( 'wp_enqueue_scripts' );
		remove_action( 'wp_enqueue_scripts', $hook );
		$this->assertNotContains( 'nx-block-assets-' . $id, GutenbergDesign::get_instance()->body_class( array() ), 'The runtime must fetch the missing EB stylesheets.' );
	}

	protected function run_enqueue_with( $id, $dequeue_library ) {
		$hook = function () use ( $id ) {
			GutenbergDesign::get_instance()->enqueue_assets( $id );
		};
		// What Storefy showed: no core block CSS left on the page (Elementor's
		// "Optimized Gutenberg Loading" drops the library on Elementor pages).
		$drop = function () {
			foreach ( wp_styles()->queue as $handle ) {
				if ( 'wp-block-library' === $handle || 0 === strpos( $handle, 'wp-block-' ) ) {
					wp_dequeue_style( $handle );
				}
			}
		};
		add_action( 'wp_enqueue_scripts', $hook );
		if ( $dequeue_library ) {
			add_action( 'wp_enqueue_scripts', $drop, 999 );
		} else {
			add_action( 'wp_enqueue_scripts', function () {
				wp_enqueue_style( 'wp-block-library' );
			}, 999 );
		}
		do_action( 'wp_enqueue_scripts' );
	}

	public function test_core_block_styles_return_when_another_plugin_drops_the_block_library() {
		$id = $this->design( 'theme-one' );
		$this->run_enqueue_with( $id, true );
		$this->assertFalse( wp_style_is( 'wp-block-library', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'nx-exit-intent-block-common', 'enqueued' ), 'core\'s shared block CSS (has-text-align-center…)' );
		foreach ( array( 'group', 'heading', 'paragraph', 'buttons', 'button' ) as $slug ) {
			$this->assertTrue( wp_style_is( 'nx-exit-intent-block-' . $slug, 'enqueued' ), $slug );
		}
		$this->assertStringEndsWith( 'wp-includes/blocks/button/style.min.css', wp_styles()->registered['nx-exit-intent-block-button']->src );
		$this->assertSame( 'replace', wp_styles()->registered['nx-exit-intent-block-button']->extra['rtl'] );
		$this->assertFalse( wp_style_is( 'nx-exit-intent-block-countdown', 'registered' ), 'Only core blocks.' );
	}

	public function test_nothing_extra_when_the_block_library_is_on_the_page() {
		$id = $this->design( 'theme-one' );
		$this->run_enqueue_with( $id, false );
		$this->assertTrue( wp_style_is( 'wp-block-library', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'nx-exit-intent-block-heading', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'nx-exit-intent-block-common', 'enqueued' ) );
	}

	/* --- WP Rocket --------------------------------------------------------- */

	public function test_rocket_safelist_keeps_the_active_designs_block_styles() {
		$id = $this->design( 'theme-two' );
		\NotificationX\Core\PostType::get_instance()->save_post(
			array( 'type' => 'exit_intent', 'source' => 'exit_intent_custom', 'themes' => null, 'enabled' => true, 'title' => 'rucss', 'gutenberg_id' => $id )
		);
		$list = apply_filters( 'rocket_rucss_safelist', array( 'existing.css' ) );
		$this->assertContains( 'existing.css', $list );
		$this->assertContains( includes_url( 'blocks/heading/style.min.css' ), $list );
		$this->assertContains( includes_url( 'blocks/button/style.min.css' ), $list );
		$this->assertContains( NOTIFICATIONX_PUBLIC_URL . 'css/nx-countdown.css', $list );
		$this->assertContains( '.nx-exit-intent-gutenberg', $list );
	}

	public function test_rocket_safelist_ignores_disabled_campaigns() {
		$id = $this->design( 'theme-two' );
		\NotificationX\Core\PostType::get_instance()->save_post(
			array( 'type' => 'exit_intent', 'source' => 'exit_intent_custom', 'themes' => null, 'enabled' => false, 'title' => 'rucss off', 'gutenberg_id' => $id )
		);
		$this->assertSame( array( 'existing.css' ), GutenbergDesign::get_instance()->rocket_rucss_safelist( array( 'existing.css' ) ) );
	}
}
