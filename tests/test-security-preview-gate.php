<?php
/**
 * Regression tests for the unauthenticated builder preview path (Patchstack).
 *
 * Preview mode renders notification settings sent in `$_POST['nx-preview']`
 * on any front-end page. Before the fix, any non-empty `nx-preview` switched
 * it on: no nonce, no capability. An anonymous visitor could
 *   - run any registered shortcode through the bar's `press_content`
 *     (PressBar::print_bar_notice() -> do_shortcode()),
 *   - render draft, private or password-protected posts through `gutenberg_id`
 *     (do_blocks() on any post ID),
 *   - hide the admin bar and silence Query Monitor for the request.
 *
 * The fix:
 *   - Preview::is_authorized_preview_request() requires the `nx-preview` nonce
 *     and the `edit_notificationx` capability. is_preview() and get_settings()
 *     both use it, so a filter that forces preview mode on still never makes
 *     the request payload readable.
 *   - The NotificationX Inline block turns preview mode on for its own
 *     shortcode only, instead of for the rest of the request.
 *   - PressBar::can_render_block_bar() renders only bar posts (`nx_bar_eb`,
 *     legacy `wp_block`) that are published without a password, unless the
 *     user can edit them. The Elementor branch accepts only `nx_bar` posts.
 *   - The builder gets the nonce per user as `notificationxTabs.preview_nonce`.
 *
 * @package Notificationx
 */

use NotificationX\Blocks\Blocks;
use NotificationX\Core\PostType;
use NotificationX\Extensions\PressBar\PressBar;
use NotificationX\FrontEnd\Preview;

class Test_Security_Preview_Gate extends WP_UnitTestCase {

	/**
	 * Calls of the probe shortcode.
	 *
	 * @var int
	 */
	protected $probe_calls = 0;

	public function setUp(): void {
		parent::setUp();

		$this->probe_calls = 0;
		add_shortcode(
			'nx_preview_probe',
			function () {
				$this->probe_calls++;
				return 'NX_PROBE_RAN';
			}
		);

		if ( ! post_type_exists( 'nx_bar_eb' ) ) {
			PressBar::register_post_type();
		}

		wp_set_current_user( 0 );
	}

	public function tearDown(): void {
		remove_shortcode( 'nx_preview_probe' );
		$_POST = array();
		parent::tearDown();
	}

	protected function admin() {
		return self::factory()->user->create( array( 'role' => 'administrator' ) );
	}

	protected function bar_payload( $extra = array() ) {
		return base64_encode(
			wp_json_encode(
				array_merge(
					array(
						'source'        => 'press_bar',
						'type'          => 'notification_bar',
						'themes'        => 'press_bar_theme-one',
						'global_queue'  => false,
						'press_content' => 'Sale [nx_preview_probe]',
					),
					$extra
				)
			)
		);
	}

	/**
	 * Sets up a preview POST. A null nonce sends no nonce field.
	 */
	protected function post_preview( $payload, $nonce = null ) {
		$_POST = array( 'nx-preview' => $payload );
		if ( null !== $nonce ) {
			$_POST[ Preview::NONCE_FIELD ] = $nonce;
		}
	}

	/**
	 * Runs the front-end preview hook the way FrontEnd::enqueue_scripts() does.
	 */
	protected function run_preview() {
		return Preview::get_instance()->enqueue_scripts( false );
	}

	public function test_anonymous_payload_without_nonce_is_not_a_preview() {
		$this->post_preview( $this->bar_payload() );

		$this->assertFalse( Preview::get_instance()->is_preview() );
		$this->assertSame( array(), Preview::get_instance()->get_settings() );
		$this->assertFalse( $this->run_preview() );
		$this->assertSame( 0, $this->probe_calls, 'press_content shortcode ran for an anonymous request.' );
	}

	public function test_anonymous_payload_with_forged_nonce_is_rejected() {
		$this->post_preview( $this->bar_payload(), 'abcdef1234' );

		$this->assertFalse( Preview::get_instance()->is_preview() );
		$this->assertFalse( $this->run_preview() );
		$this->assertSame( 0, $this->probe_calls );
	}

	public function test_anonymous_nonce_is_rejected() {
		// A logged-out visitor can get a nonce for user 0 from any page that prints one.
		$this->post_preview( $this->bar_payload(), wp_create_nonce( Preview::NONCE_ACTION ) );

		$this->assertFalse( Preview::get_instance()->is_preview() );
		$this->assertSame( 0, $this->probe_calls );
	}

	public function test_subscriber_with_valid_nonce_is_rejected() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->post_preview( $this->bar_payload(), wp_create_nonce( Preview::NONCE_ACTION ) );

		$this->assertFalse( Preview::get_instance()->is_preview() );
		$this->assertFalse( $this->run_preview() );
		$this->assertSame( 0, $this->probe_calls );
	}

	public function test_admin_without_nonce_is_rejected() {
		wp_set_current_user( $this->admin() );
		$this->post_preview( $this->bar_payload() );

		$this->assertFalse( Preview::get_instance()->is_preview() );
		$this->assertSame( array(), Preview::get_instance()->get_settings() );
	}

	public function test_admin_with_nonce_for_another_action_is_rejected() {
		wp_set_current_user( $this->admin() );
		$this->post_preview( $this->bar_payload(), wp_create_nonce( 'wp_rest' ) );

		$this->assertFalse( Preview::get_instance()->is_preview() );
	}

	public function test_admin_with_valid_nonce_gets_the_preview() {
		wp_set_current_user( $this->admin() );
		$this->post_preview( $this->bar_payload(), wp_create_nonce( Preview::NONCE_ACTION ) );

		$this->assertTrue( Preview::get_instance()->is_preview() );
		$this->assertSame( 'press_bar', Preview::get_instance()->get_settings()['source'] );

		$result = $this->run_preview();
		$this->assertTrue( $result['nxPreview'] );
		$this->assertStringContainsString( 'NX_PROBE_RAN', $result['pressbar']['press_bar']['content'] );
	}

	public function test_forced_preview_filter_does_not_expose_the_payload() {
		// What the Inline block in a block template did for the whole request.
		add_filter( 'nx_is_preview', '__return_true' );
		$this->post_preview( $this->bar_payload() );

		$this->assertTrue( Preview::get_instance()->is_preview() );
		$this->assertSame( array(), Preview::get_instance()->get_settings() );
		$this->assertEmpty( $this->run_preview() );
		$this->assertSame( 0, $this->probe_calls );

		remove_filter( 'nx_is_preview', '__return_true' );
	}

	public function test_anonymous_request_keeps_admin_bar_setup() {
		global $show_admin_bar;
		$show_admin_bar = true;
		$this->post_preview( $this->bar_payload() );

		Preview::get_instance()->setup_preview_request();

		$this->assertTrue( $show_admin_bar );
	}

	public function test_authorized_request_hides_admin_bar() {
		global $show_admin_bar;
		$show_admin_bar = true;
		wp_set_current_user( $this->admin() );
		$this->post_preview( $this->bar_payload(), wp_create_nonce( Preview::NONCE_ACTION ) );

		Preview::get_instance()->setup_preview_request();

		$this->assertFalse( $show_admin_bar );
	}

	public function test_preview_setup_runs_on_plugins_loaded() {
		$this->assertSame( 0, has_action( 'plugins_loaded', array( Preview::get_instance(), 'setup_preview_request' ) ) );
	}

	public function test_template_inline_block_does_not_leave_preview_on() {
		Blocks::get_instance()->gutenberg_examples_dynamic_render_callback(
			array(
				'post_type' => 'wp_template',
				'blockId'   => 'nx-test-block',
				'nx_id'     => '',
			),
			''
		);

		$this->assertFalse( Preview::get_instance()->is_preview() );
	}

	public function test_anonymous_cannot_render_unpublished_block_bars() {
		$cases = array(
			'draft'    => array( 'post_status' => 'draft' ),
			'private'  => array( 'post_status' => 'private' ),
			'pending'  => array( 'post_status' => 'pending' ),
			'password' => array(
				'post_status'   => 'publish',
				'post_password' => 'secret',
			),
		);
		foreach ( $cases as $label => $args ) {
			$id = self::factory()->post->create(
				array_merge(
					array(
						'post_type'    => 'nx_bar_eb',
						'post_content' => "SECRET_{$label}",
					),
					$args
				)
			);
			$html = PressBar::get_instance()->print_bar_notice( array( 'gutenberg_id' => $id ) );
			$this->assertStringNotContainsString( "SECRET_{$label}", $html, "{$label} bar post was rendered for an anonymous visitor." );
		}
	}

	public function test_anonymous_cannot_render_other_post_types() {
		$cases = array(
			'post'     => array( 'post_status' => 'private' ),
			'page'     => array( 'post_status' => 'draft' ),
			'public'   => array( 'post_status' => 'publish' ),
		);
		foreach ( $cases as $label => $args ) {
			$id = self::factory()->post->create(
				array_merge(
					array(
						'post_type'    => 'public' === $label ? 'post' : $label,
						'post_content' => "SECRET_{$label}",
					),
					$args
				)
			);
			$html = PressBar::get_instance()->print_bar_notice( array( 'gutenberg_id' => $id ) );
			$this->assertSame( '', $html, "{$label} post was rendered as a bar." );
		}
	}

	public function test_missing_block_bar_post_renders_nothing() {
		$this->assertSame( '', PressBar::get_instance()->print_bar_notice( array( 'gutenberg_id' => 999999 ) ) );
	}

	public function test_published_block_bars_still_render() {
		foreach ( array( 'nx_bar_eb', 'wp_block' ) as $type ) {
			$id   = self::factory()->post->create(
				array(
					'post_type'    => $type,
					'post_status'  => 'publish',
					'post_content' => "<!-- wp:paragraph --><p>BAR_{$type}</p><!-- /wp:paragraph -->",
				)
			);
			$html = PressBar::get_instance()->print_bar_notice( array( 'gutenberg_id' => $id ) );
			$this->assertStringContainsString( "BAR_{$type}", $html );
		}
	}

	public function test_editor_can_render_own_draft_block_bar() {
		wp_set_current_user( $this->admin() );
		$id = self::factory()->post->create(
			array(
				'post_type'    => 'nx_bar_eb',
				'post_status'  => 'draft',
				'post_content' => 'DRAFT_BAR',
			)
		);

		$this->assertStringContainsString( 'DRAFT_BAR', PressBar::get_instance()->print_bar_notice( array( 'gutenberg_id' => $id ) ) );
	}

	public function test_builder_gets_a_valid_nonce_per_user() {
		wp_set_current_user( $this->admin() );
		$nonce = PostType::get_instance()->get_localize_scripts()['preview_nonce'];
		$this->assertNotEmpty( $nonce );
		$this->assertNotFalse( wp_verify_nonce( $nonce, Preview::NONCE_ACTION ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertSame( '', PostType::get_instance()->get_localize_scripts()['preview_nonce'] );
	}

	public function test_nonce_is_not_in_the_shared_builder_config() {
		wp_set_current_user( $this->admin() );
		$tabs = Preview::get_instance()->content_heading( array() );
		$this->assertArrayNotHasKey( 'nonce', $tabs['config']['content_heading']['preview'] );
		$this->assertStringNotContainsString( wp_create_nonce( Preview::NONCE_ACTION ), wp_json_encode( $tabs ) );
	}
}
