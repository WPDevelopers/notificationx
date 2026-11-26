<?php
/**
 * Exit Intent "Build With Gutenberg": the block design post type, the starting
 * designs and the import / remove routes.
 *
 * @package Notificationx
 */

use NotificationX\Core\REST;
use NotificationX\Extensions\ExitIntent\GutenbergDesign;

class Test_Exit_Intent_Gutenberg_Design extends WP_UnitTestCase {

	/** @var WP_REST_Server */
	protected $server;

	protected $admin_id;

	public function setUp(): void {
		parent::setUp();
		REST::get_instance();
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );

		$this->admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$admin          = new WP_User( $this->admin_id );
		$admin->add_cap( 'edit_notificationx' );
	}

	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	private function dispatch( $route, $params ) {
		$request = new WP_REST_Request( 'POST', $route );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return $this->server->dispatch( $request );
	}

	private function design_count() {
		return count( get_posts( array( 'post_type' => GutenbergDesign::POST_TYPE, 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) ) );
	}

	/* --- post type --------------------------------------------------------- */

	public function test_post_type_is_registered_for_the_block_editor() {
		$this->assertTrue( post_type_exists( 'nx_exit_intent_eb' ) );
		$type = get_post_type_object( 'nx_exit_intent_eb' );
		$this->assertTrue( $type->show_in_rest );
		$this->assertSame( 'nx-exit-intent-designs', $type->rest_base );
		$this->assertFalse( $type->public );
		$this->assertTrue( $type->publicly_queryable, 'The runtime fetches a design\'s assets from its permalink.' );
		$this->assertFalse( $type->show_in_menu );
		$this->assertTrue( $type->exclude_from_search );
		$this->assertTrue( post_type_supports( 'nx_exit_intent_eb', 'editor' ) );
		$this->assertTrue( use_block_editor_for_post_type( 'nx_exit_intent_eb' ) );
	}

	public function test_rest_base_does_not_collide_with_the_bar_designs() {
		$this->assertNotSame( get_post_type_object( 'nx_bar_eb' )->rest_base, get_post_type_object( 'nx_exit_intent_eb' )->rest_base );
	}

	public function test_bar_and_elementor_post_types_are_unchanged() {
		$bar = get_post_type_object( 'nx_bar_eb' );
		$this->assertSame( 'NotificationX', $bar->rest_base );
		$this->assertTrue( $bar->show_in_rest );
		$exit = get_post_type_object( 'nx_exit_intent' );
		$this->assertFalse( (bool) $exit->show_in_rest, 'The Elementor post type stays out of the block editor.' );
		$this->assertTrue( post_type_supports( 'nx_exit_intent', 'elementor' ) );
	}

	public function test_design_permalink_is_not_indexed() {
		$id = self::factory()->post->create( array( 'post_type' => 'nx_exit_intent_eb', 'post_status' => 'publish' ) );
		$this->go_to( get_permalink( $id ) );
		$robots = GutenbergDesign::get_instance()->no_index( array() );
		$this->assertTrue( $robots['noindex'] );
		$this->go_to( home_url( '/' ) );
		$this->assertSame( array(), GutenbergDesign::get_instance()->no_index( array() ) );
	}

	public function test_templately_names_the_design_type() {
		$this->assertSame( 'NX Exit Intent', apply_filters( 'templately_cloud_push_post_type', 'nx_exit_intent_eb' ) );
		$this->assertSame( 'page', apply_filters( 'templately_cloud_push_post_type', 'page' ) );
	}

	/* --- starting designs -------------------------------------------------- */

	public function seed_provider() {
		return array(
			'theme-one'   => array( 'theme-one' ),
			'theme-two'   => array( 'theme-two' ),
			'theme-three' => array( 'theme-three' ),
			'theme-four'  => array( 'theme-four' ),
		);
	}

	/**
	 * @dataProvider seed_provider
	 */
	public function test_seed_uses_only_core_and_notificationx_blocks( $key ) {
		$seed = GutenbergDesign::read_seed( $key );
		$this->assertIsArray( $seed );
		$names = array();
		$walk  = function ( $blocks ) use ( &$walk, &$names ) {
			foreach ( $blocks as $block ) {
				if ( $block['blockName'] ) {
					$names[] = $block['blockName'];
				}
				$walk( $block['innerBlocks'] );
			}
		};
		$walk( parse_blocks( $seed['content'] ) );
		$this->assertNotEmpty( $names );
		$this->assertSame( 'core/group', $names[0], 'The outer block is a Group (its content width sets the popup width).' );
		foreach ( $names as $name ) {
			$this->assertMatchesRegularExpression( '#^(core|notificationx)/#', $name );
		}
	}

	/**
	 * @dataProvider seed_provider
	 */
	public function test_seed_loads_nothing_remote( $key ) {
		$seed = GutenbergDesign::read_seed( $key );
		$this->assertStringNotContainsString( '{{NX_IMG}}', $seed['content'] );
		preg_match_all( '#(?:src|href)="([^"]+)"#', $seed['content'], $m );
		foreach ( $m[1] as $url ) {
			$this->assertTrue( '#' === $url[0] || 0 === strpos( $url, NOTIFICATIONX_PUBLIC_URL ), "Remote URL in {$key}: {$url}" );
		}
	}

	public function test_image_token_becomes_the_bundled_image() {
		$seed = GutenbergDesign::read_seed( 'theme-three' );
		$this->assertStringContainsString( NOTIFICATIONX_PUBLIC_URL . 'image/exit-intent/exit-intend-theme-six.jpg', $seed['content'] );
		$this->assertFileExists( NOTIFICATIONX_PATH . 'assets/public/image/exit-intent/exit-intend-theme-six.jpg' );
	}

	/**
	 * @dataProvider seed_provider
	 */
	public function test_each_seed_has_a_preview_image( $key ) {
		$this->assertFileExists( NOTIFICATIONX_PATH . "assets/admin/images/extensions/themes/exit-intent-gutenberg/{$key}.png" );
	}

	public function unknown_theme_provider() {
		return array(
			'traversal to the bar seeds' => array( '../../PressBar/jsons-gb/theme-one' ),
			'traversal up'               => array( '../../../../../../wp-config' ),
			'absolute path'              => array( '/etc/passwd' ),
			'empty'                      => array( '' ),
			'unknown key'                => array( 'theme-five' ),
			'array'                      => array( array( 'theme-one' ) ),
			'null byte'                  => array( "theme-one\0.php" ),
		);
	}

	/**
	 * @dataProvider unknown_theme_provider
	 */
	public function test_read_seed_refuses_anything_but_the_seed_keys( $theme_id ) {
		$this->assertWPError( GutenbergDesign::read_seed( $theme_id ) );
	}

	/* --- import route ------------------------------------------------------ */

	public function test_import_creates_a_published_design_from_the_seed() {
		wp_set_current_user( $this->admin_id );
		$response = $this->dispatch( '/notificationx/v1/exit-intent/gutenberg/import', array( 'theme_id' => 'theme-two' ) );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$context = $data['data']['context'];
		$this->assertNull( $context['themes'] );
		$post = get_post( $context['gutenberg_id'] );
		$this->assertSame( 'nx_exit_intent_eb', $post->post_type );
		$this->assertSame( 'publish', $post->post_status );
		$this->assertStringContainsString( 'Flash sale with countdown', $post->post_title );
		$seed = GutenbergDesign::read_seed( 'theme-two' );
		$this->assertSame( wp_list_pluck( parse_blocks( $seed['content'] ), 'blockName' ), wp_list_pluck( parse_blocks( $post->post_content ), 'blockName' ) );
		$this->assertStringContainsString( 'wp:notificationx/countdown', $post->post_content );
		// Stored byte for byte: the countdown's style JSON keeps its escapes (\u003e),
		// so the CSS it renders is "… > div", not "… u003e div".
		$this->assertSame( $seed['content'], $post->post_content );
		$this->assertStringContainsString( '.nx-countdown-item > div', do_blocks( $post->post_content ) );
		$this->assertStringContainsString( 'post.php?post=' . $post->ID . '&action=edit', $context['gutenberg_edit_link'] );
	}

	/**
	 * @dataProvider unknown_theme_provider
	 */
	public function test_import_refuses_unknown_designs_and_creates_nothing( $theme_id ) {
		wp_set_current_user( $this->admin_id );
		$before   = $this->design_count();
		$response = $this->dispatch( '/notificationx/v1/exit-intent/gutenberg/import', array( 'theme_id' => $theme_id ) );
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'nx_exit_intent_unknown_design', $response->get_data()['code'] );
		$this->assertSame( $before, $this->design_count() );
	}

	public function test_import_needs_edit_notificationx() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$response = $this->dispatch( '/notificationx/v1/exit-intent/gutenberg/import', array( 'theme_id' => 'theme-one' ) );
		$this->assertContains( $response->get_status(), array( 401, 403 ) );
		$this->assertSame( 0, $this->design_count() );
	}

	/* --- remove route ------------------------------------------------------ */

	public function test_remove_deletes_the_design() {
		wp_set_current_user( $this->admin_id );
		$id       = self::factory()->post->create( array( 'post_type' => 'nx_exit_intent_eb' ) );
		$response = $this->dispatch( '/notificationx/v1/exit-intent/gutenberg/remove', array( 'gutenberg_id' => $id ) );
		$this->assertTrue( $response->get_data()['success'] );
		$this->assertNull( get_post( $id ) );
	}

	public function other_type_provider() {
		return array(
			'page'           => array( 'page' ),
			'post'           => array( 'post' ),
			'wp_block'       => array( 'wp_block' ),
			'bar design'     => array( 'nx_bar_eb' ),
			'elementor exit' => array( 'nx_exit_intent' ),
		);
	}

	/**
	 * @dataProvider other_type_provider
	 */
	public function test_remove_never_deletes_other_types( $type ) {
		wp_set_current_user( $this->admin_id );
		$id       = self::factory()->post->create( array( 'post_type' => $type ) );
		$response = $this->dispatch( '/notificationx/v1/exit-intent/gutenberg/remove', array( 'gutenberg_id' => $id ) );
		$this->assertFalse( $response->get_data()['success'] );
		$this->assertNotNull( get_post( $id ) );
	}

	public function test_remove_needs_edit_notificationx() {
		$id = self::factory()->post->create( array( 'post_type' => 'nx_exit_intent_eb' ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$response = $this->dispatch( '/notificationx/v1/exit-intent/gutenberg/remove', array( 'gutenberg_id' => $id ) );
		$this->assertContains( $response->get_status(), array( 401, 403 ) );
		$this->assertNotNull( get_post( $id ) );
	}
}
