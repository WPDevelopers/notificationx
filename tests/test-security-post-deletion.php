<?php
/**
 * Regression tests for NX-01 -- arbitrary post deletion via unvalidated post ID.
 *
 * Three REST routes accepted a post ID straight from the request body and passed
 * it to `wp_delete_post( $id, true )` with no check that the target belonged to
 * NotificationX:
 *
 *   /elementor/remove              -> PressBar::delete_elementor_post()
 *   /gutenberg/remove              -> PressBar::gutenberg_remove()
 *   /exit-intent/elementor/remove  -> ExitIntentNotification::delete_elementor_post()
 *
 * All three sit behind `edit_notificationx`, so any role delegated only
 * "Who Can Create Notification?" could permanently destroy any post on the site.
 * `$force_delete = true` means the deletion bypassed the trash entirely.
 *
 * The fix adds an object-level boundary in Helper::delete_owned_post(): only a
 * post whose type NotificationX itself creates may be deleted.
 *
 * @package Notificationx
 */

use NotificationX\Core\REST;
use NotificationX\Core\Helper;

class Test_Security_Post_Deletion extends WP_UnitTestCase {

	/**
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * A user holding only `edit_notificationx` -- the delegated notification
	 * creator this boundary exists to constrain.
	 */
	protected $creator_id;

	public function setUp(): void {
		parent::setUp();

		REST::get_instance();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );

		$this->creator_id = self::factory()->user->create( array( 'role' => 'contributor' ) );
		$creator          = new WP_User( $this->creator_id );
		$creator->add_cap( 'edit_notificationx' );
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

	/* ---------------------------------------------------------------------
	 * Helper-level boundary
	 * ------------------------------------------------------------------ */

	/**
	 * The core of the fix: a foreign post type is never deleted.
	 */
	public function test_helper_refuses_foreign_post_type() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$result = Helper::delete_owned_post( $page_id, 'nx_bar' );

		$this->assertFalse( $result, 'Helper reported a deletion for a foreign post type.' );
		$this->assertNotNull( get_post( $page_id ), 'A page was deleted through an NX-owned-post helper.' );
	}

	/**
	 * The legitimate case must keep working -- a genuine NX design is removable.
	 */
	public function test_helper_deletes_owned_post_type() {
		$bar_id = self::factory()->post->create( array( 'post_type' => 'nx_bar' ) );

		$result = Helper::delete_owned_post( $bar_id, 'nx_bar' );

		$this->assertTrue( $result, 'A genuine nx_bar design could not be deleted.' );
		$this->assertNull( get_post( $bar_id ), 'nx_bar design still present after deletion.' );
	}

	public function test_helper_handles_missing_and_invalid_ids() {
		$this->assertFalse( Helper::delete_owned_post( 0, 'nx_bar' ) );
		$this->assertFalse( Helper::delete_owned_post( '', 'nx_bar' ) );
		$this->assertFalse( Helper::delete_owned_post( 999999, 'nx_bar' ) );
		$this->assertFalse( Helper::delete_owned_post( 'not-an-id', 'nx_bar' ) );
	}

	/* ---------------------------------------------------------------------
	 * Route-level: the actual attack, end to end
	 * ------------------------------------------------------------------ */

	/**
	 * @dataProvider foreign_content_provider
	 */
	public function test_creator_cannot_delete_foreign_content_via_routes( $route, $param, $post_type ) {
		wp_set_current_user( $this->creator_id );

		$victim_id = self::factory()->post->create(
			array(
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'post_title'  => 'Victim content',
			)
		);

		$this->dispatch( $route, array( $param => $victim_id ) );

		$this->assertNotNull(
			get_post( $victim_id ),
			sprintf( 'A %s was permanently deleted through %s by an edit_notificationx user.', $post_type, $route )
		);
	}

	public function foreign_content_provider() {
		return array(
			'elementor/remove vs page'             => array( '/notificationx/v1/elementor/remove', 'elementor_id', 'page' ),
			'elementor/remove vs post'             => array( '/notificationx/v1/elementor/remove', 'elementor_id', 'post' ),
			'gutenberg/remove vs page'             => array( '/notificationx/v1/gutenberg/remove', 'gutenberg_id', 'page' ),
			'gutenberg/remove vs post'             => array( '/notificationx/v1/gutenberg/remove', 'gutenberg_id', 'post' ),
			'exit-intent/elementor/remove vs page' => array( '/notificationx/v1/exit-intent/elementor/remove', 'elementor_id', 'page' ),
			'exit-intent/elementor/remove vs post' => array( '/notificationx/v1/exit-intent/elementor/remove', 'elementor_id', 'post' ),
		);
	}

	/**
	 * A reusable block belongs to core, not to NotificationX -- the Gutenberg
	 * route creates `nx_bar_eb`, so `wp_block` must stay out of reach.
	 */
	public function test_creator_cannot_delete_core_reusable_block() {
		wp_set_current_user( $this->creator_id );

		$block_id = self::factory()->post->create( array( 'post_type' => 'wp_block' ) );

		$this->dispatch( '/notificationx/v1/gutenberg/remove', array( 'gutenberg_id' => $block_id ) );

		$this->assertNotNull( get_post( $block_id ), 'A core reusable block was deleted through /gutenberg/remove.' );
	}

	/**
	 * Deleting a genuine NX design through the route must still succeed, or the
	 * fix has broken the feature it was protecting.
	 */
	public function test_creator_can_still_delete_own_design_types() {
		wp_set_current_user( $this->creator_id );

		$bar_id = self::factory()->post->create( array( 'post_type' => 'nx_bar' ) );
		$this->dispatch( '/notificationx/v1/elementor/remove', array( 'elementor_id' => $bar_id ) );
		$this->assertNull( get_post( $bar_id ), 'A genuine nx_bar design could not be removed via the route.' );

		$eb_id = self::factory()->post->create( array( 'post_type' => 'nx_bar_eb' ) );
		$this->dispatch( '/notificationx/v1/gutenberg/remove', array( 'gutenberg_id' => $eb_id ) );
		$this->assertNull( get_post( $eb_id ), 'A genuine nx_bar_eb design could not be removed via the route.' );

		$exit_id = self::factory()->post->create( array( 'post_type' => 'nx_exit_intent' ) );
		$this->dispatch( '/notificationx/v1/exit-intent/elementor/remove', array( 'elementor_id' => $exit_id ) );
		$this->assertNull( get_post( $exit_id ), 'A genuine nx_exit_intent design could not be removed via the route.' );
	}

	/**
	 * Bulk sweep: the original bug allowed walking the ID space. Nothing outside
	 * NotificationX's own types may be reachable.
	 */
	public function test_id_sweep_leaves_foreign_content_intact() {
		wp_set_current_user( $this->creator_id );

		$victims = array(
			self::factory()->post->create( array( 'post_type' => 'page' ) ),
			self::factory()->post->create( array( 'post_type' => 'post' ) ),
			self::factory()->post->create( array( 'post_type' => 'wp_block' ) ),
			self::factory()->post->create( array( 'post_type' => 'attachment' ) ),
		);

		foreach ( $victims as $victim_id ) {
			$this->dispatch( '/notificationx/v1/elementor/remove', array( 'elementor_id' => $victim_id ) );
			$this->dispatch( '/notificationx/v1/gutenberg/remove', array( 'gutenberg_id' => $victim_id ) );
			$this->dispatch( '/notificationx/v1/exit-intent/elementor/remove', array( 'elementor_id' => $victim_id ) );
		}

		foreach ( $victims as $victim_id ) {
			$this->assertNotNull( get_post( $victim_id ), "Post {$victim_id} was destroyed by an ID sweep." );
		}
	}
}
