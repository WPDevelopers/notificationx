<?php
/**
 * The builder Preview's page picker route (`GET notificationx/v1/preview/pages`).
 *
 * The route only lists pages; the preview itself stays the builder's
 * nonce-checked POST (see test-security-preview-gate.php).
 *
 * @package Notificationx
 */

use NotificationX\Core\REST;

class Test_Preview_Pages extends WP_UnitTestCase {

	/**
	 * @var WP_REST_Server
	 */
	protected $server;

	public function setUp(): void {
		parent::setUp();
		REST::get_instance();
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );
		wp_set_current_user( 0 );
	}

	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;
		update_option( 'show_on_front', 'posts' );
		delete_option( 'page_on_front' );
		parent::tearDown();
	}

	protected function get( $params = array() ) {
		$request = new WP_REST_Request( 'GET', '/notificationx/v1/preview/pages' );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return $this->server->dispatch( $request );
	}

	protected function admin() {
		$id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $id );
		return $id;
	}

	protected function ids( $response ) {
		return wp_list_pluck( $response->get_data()['items'], 'id' );
	}

	protected function item( $response, $id ) {
		foreach ( $response->get_data()['items'] as $item ) {
			if ( $id === $item['id'] ) {
				return $item;
			}
		}
		return null;
	}

	public function test_route_is_registered() {
		$this->assertArrayHasKey( '/notificationx/v1/preview/pages', $this->server->get_routes() );
	}

	public function test_anonymous_is_refused() {
		$this->assertSame( 401, $this->get()->get_status() );
	}

	public function test_subscriber_is_refused() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertSame( 403, $this->get()->get_status() );
	}

	public function test_editor_without_the_notificationx_capability_is_refused() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertFalse( current_user_can( 'edit_notificationx' ) );
		$this->assertSame( 403, $this->get()->get_status() );
	}

	public function test_lists_published_pages_posts_with_their_links() {
		$this->admin();
		$page = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'About &amp; us' ) );
		$post = self::factory()->post->create( array( 'post_title' => 'Hello there' ) );

		$response = $this->get();
		$this->assertSame( 200, $response->get_status() );

		$item = $this->item( $response, $page );
		$this->assertSame( 'About & us', $item['title'] );
		$this->assertSame( 'page', $item['type'] );
		$this->assertSame( 'Pages', $item['type_label'] );
		$this->assertSame( 'publish', $item['status'] );
		$this->assertTrue( $item['public'] );
		$this->assertFalse( $item['password'] );
		$this->assertSame( get_permalink( $page ), $item['url'] );
		$this->assertContains( $post, $this->ids( $response ) );
	}

	public function test_attachments_are_not_offered() {
		$this->admin();
		$attachment = self::factory()->attachment->create( array( 'post_title' => 'A photo', 'post_status' => 'inherit' ) );
		$this->assertNotContains( $attachment, $this->ids( $this->get() ) );
	}

	public function test_search_matches_titles() {
		$this->admin();
		$match = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'Honey oat bread' ) );
		$other = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'Contact' ) );

		$ids = $this->ids( $this->get( array( 'search' => 'honey' ) ) );
		$this->assertContains( $match, $ids );
		$this->assertNotContains( $other, $ids );
	}

	public function test_drafts_point_at_their_preview_link_and_are_not_public() {
		$this->admin();
		$draft = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'Coming soon' ) );

		$item = $this->item( $this->get(), $draft );
		$this->assertSame( 'draft', $item['status'] );
		$this->assertFalse( $item['public'] );
		$this->assertSame( get_preview_post_link( $draft ), $item['url'] );
		$this->assertStringContainsString( 'preview=true', $item['url'] );
	}

	public function test_private_and_password_pages_are_marked_not_public() {
		$this->admin();
		$private  = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'private' ) );
		$password = self::factory()->post->create( array( 'post_type' => 'page', 'post_password' => 'secret' ) );

		$response = $this->get();
		$this->assertSame( 'private', $this->item( $response, $private )['status'] );
		$this->assertFalse( $this->item( $response, $private )['public'] );
		$this->assertTrue( $this->item( $response, $password )['password'] );
		$this->assertFalse( $this->item( $response, $password )['public'] );
		$this->assertArrayNotHasKey( 'post_password', $this->item( $response, $password ) );
	}

	public function test_trashed_posts_are_never_listed() {
		$this->admin();
		$trashed = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'trash' ) );
		$this->assertNotContains( $trashed, $this->ids( $this->get() ) );
	}

	public function test_other_authors_drafts_are_hidden_from_a_user_who_cannot_read_them() {
		$owner  = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$theirs = self::factory()->post->create( array( 'post_status' => 'draft', 'post_author' => $owner, 'post_title' => 'Their draft' ) );
		$author = self::factory()->user->create( array( 'role' => 'author' ) );
		$mine   = self::factory()->post->create( array( 'post_status' => 'draft', 'post_author' => $author, 'post_title' => 'My draft' ) );
		$secret = self::factory()->post->create( array( 'post_status' => 'private', 'post_author' => $owner, 'post_title' => 'Their private post' ) );

		$user = new WP_User( $author );
		$user->add_cap( 'edit_notificationx' );
		wp_set_current_user( $author );
		$this->assertTrue( current_user_can( 'edit_notificationx' ) );

		$ids = $this->ids( $this->get() );
		$this->assertContains( $mine, $ids );
		$this->assertNotContains( $theirs, $ids );
		$this->assertNotContains( $secret, $ids );
	}

	public function test_front_page_comes_first_and_names_the_home_url() {
		$this->admin();
		$front = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'Bakery home' ) );
		self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'Newer page' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front );

		$data = $this->get( array( 'url' => trailingslashit( home_url() ) ) )->get_data();
		$this->assertSame( $front, $data['items'][0]['id'] );
		$this->assertTrue( $data['items'][0]['front'] );
		$this->assertSame( array( 'id' => $front, 'title' => 'Bakery home' ), $data['current'] );
		$this->assertSame( 1, count( array_keys( $this->ids( $this->get() ), $front, true ) ) );
	}

	public function test_home_url_with_latest_posts_is_named_home() {
		$this->admin();
		$data = $this->get( array( 'url' => home_url( '/' ) ) )->get_data();
		$this->assertSame( array( 'id' => 0, 'title' => 'Home' ), $data['current'] );
	}

	public function test_a_page_url_is_named_and_unknown_urls_are_not() {
		$this->admin();
		$page = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'Shop' ) );

		$this->assertSame( array( 'id' => $page, 'title' => 'Shop' ), $this->get( array( 'url' => get_permalink( $page ) ) )->get_data()['current'] );
		$this->assertNull( $this->get( array( 'url' => home_url( '/no-such-page/' ) ) )->get_data()['current'] );
		$this->assertNull( $this->get()->get_data()['current'] );
	}

	public function test_url_of_a_post_the_user_cannot_read_is_not_named() {
		$owner = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$draft = self::factory()->post->create( array( 'post_status' => 'private', 'post_author' => $owner, 'post_title' => 'Hidden' ) );
		$author = self::factory()->user->create( array( 'role' => 'author' ) );
		( new WP_User( $author ) )->add_cap( 'edit_notificationx' );
		wp_set_current_user( $author );

		$this->assertNull( $this->get( array( 'url' => get_permalink( $draft ) ) )->get_data()['current'] );
	}

	public function test_post_types_can_be_filtered() {
		$this->admin();
		$post = self::factory()->post->create();
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$only_pages = function () {
			return array( 'page', 'no_such_type' );
		};
		add_filter( 'nx_preview_page_post_types', $only_pages );
		$ids = $this->ids( $this->get() );
		remove_filter( 'nx_preview_page_post_types', $only_pages );

		$this->assertContains( $page, $ids );
		$this->assertNotContains( $post, $ids );
	}
}
