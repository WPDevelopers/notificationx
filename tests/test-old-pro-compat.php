<?php
/**
 * Free 3.3.4 without NotificationX Pro, or with Pro older than 3.2.4.
 *
 * Free 3.3.4 removed the Growth Alert, Cart Peek and Discount Alert rendering
 * and the `[notificationx_inline]` shortcode; Pro 3.2.4+ ships them. These
 * tests cover what free does when that Pro is missing:
 *
 * - The builder never rewrites a notification whose saved type is not
 *   registered (PostType::check_type_change(), Rest\Posts).
 * - The frontend skips the Pro-rendered types
 *   (NotificationX::is_type_unrenderable()).
 * - `[notificationx_inline]` prints nothing instead of its raw text.
 * - Pro 3.2.2's `notificationx-public-flashing-tab` script, which points at
 *   free's deleted copy, is dequeued.
 *
 * The test suite loads free without Pro, so the "outdated Pro" branch of
 * is_type_unrenderable() is covered by Admin::pro_needs_update() tests and by
 * the Pro suite (current Pro: nothing skipped).
 *
 * See docs/qa-guide/free-3.3.4-pro-3.2.4-code-separation.md (T16-T18).
 *
 * @package Notificationx
 */

use NotificationX\Admin\Settings;
use NotificationX\Core\Database;
use NotificationX\Core\PostType;
use NotificationX\Core\REST;
use NotificationX\Extensions\GlobalFields;
use NotificationX\FrontEnd\FrontEnd;
use NotificationX\NotificationX;
use NotificationX\Types\TypeFactory;

class Test_Old_Pro_Compat extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		REST::get_instance();
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );
	}

	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;
		wp_dequeue_script( 'notificationx-public-flashing-tab' );
		wp_deregister_script( 'notificationx-public-flashing-tab' );
		parent::tearDown();
	}

	/**
	 * Store a notification row directly, the way an older version or Pro left
	 * it, without going through the builder.
	 */
	protected function insert_row( $type, $source, $theme ) {
		$saved = PostType::get_instance()->save_post(
			[
				'type'    => $type,
				'source'  => $source,
				'themes'  => $theme,
				'enabled' => true,
				'title'   => "old pro compat {$type}",
			]
		);
		$this->assertNotEmpty( $saved['nx_id'] );
		return (int) $saved['nx_id'];
	}

	protected function saved_row( $nx_id ) {
		return Database::get_instance()->get_post( Database::$table_posts, $nx_id, 'type, source, theme' );
	}

	protected function update_request( $nx_id, $body ) {
		$request = new WP_REST_Request( 'POST', "/notificationx/v1/nx/{$nx_id}" );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );
		return rest_do_request( $request );
	}

	/**
	 * A type that is registered in this test run (it depends on the modules).
	 */
	protected function registered_type() {
		$types = array_keys( TypeFactory::get_instance()->get_all() );
		$this->assertNotEmpty( $types );
		return $types[0];
	}

	// --- Builder: unregistered saved type ------------------------------------

	public function test_cart_peek_type_is_not_registered_without_pro() {
		$this->assertFalse( PostType::get_instance()->is_type_registered( 'woocommerce_cart_peek' ) );
		$this->assertFalse( PostType::get_instance()->is_type_registered( '' ) );
		$this->assertFalse( PostType::get_instance()->is_type_registered( null ) );
		$this->assertTrue( PostType::get_instance()->is_type_registered( $this->registered_type() ) );
	}

	/**
	 * Without Pro, Cart Peek stays in the type picker (Add New and Quick
	 * Builder) as a locked Pro type with its upsell popup, as in free 3.3.3.
	 */
	public function test_picker_lists_cart_peek_locked_without_pro() {
		$options = wp_list_pluck( GlobalFields::get_instance()->get_type_options(), null, 'value' );
		$this->assertArrayHasKey( 'woocommerce_cart_peek', $options );
		$cart_peek = $options['woocommerce_cart_peek'];
		$this->assertTrue( $cart_peek['is_pro'] );
		$this->assertSame( 51, $cart_peek['priority'] );
		$this->assertStringContainsString( 'Upgrade to PRO', $cart_peek['popup']['confirmButtonText'] );
		$this->assertArrayNotHasKey( 'forced', $cart_peek['popup'] );
		$this->assertSame( 1, count( array_keys( wp_list_pluck( $options, 'value' ), 'woocommerce_cart_peek', true ) ) );
	}

	public function test_picker_hides_cart_peek_with_the_woocommerce_module_off() {
		$modules = (array) Settings::get_instance()->get( 'settings.modules' );
		Settings::get_instance()->set( 'settings.modules', array_merge( $modules, [ 'modules_woocommerce' => false ] ) );
		$values = wp_list_pluck( GlobalFields::get_instance()->get_type_options(), 'value' );
		Settings::get_instance()->set( 'settings.modules', $modules );
		$this->assertNotContains( 'woocommerce_cart_peek', $values );
	}

	public function test_list_labels_an_unregistered_cart_peek_notification() {
		$nx_id = $this->insert_row( 'woocommerce_cart_peek', 'woocommerce_cart_peek', 'woocommerce_cart_peek_conv-theme-fourteen' );
		$posts = wp_list_pluck( PostType::get_instance()->get_posts( [ 'nx_id' => $nx_id ] ), null, 'nx_id' );
		$this->assertSame( 'Cart Peek', $posts[ $nx_id ]['type_label'] );
		$this->assertSame( '', $posts[ $nx_id ]['source_label'] );
	}

	/**
	 * The tester's case: Cart Peek opened without Pro shows as WooCommerce
	 * Sales, and Update used to save that.
	 */
	public function test_update_cannot_change_an_unregistered_type() {
		$nx_id = $this->insert_row( 'woocommerce_cart_peek', 'woocommerce_cart_peek', 'woocommerce_cart_peek_conv-theme-fourteen' );

		$response = $this->update_request(
			$nx_id,
			[
				'nx_id'  => $nx_id,
				'type'   => 'woocommerce_sales',
				'source' => 'woocommerce_sales',
				'themes' => 'woocommerce_sales_theme-one',
			]
		);

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'nx_type_unavailable', $response->get_data()['code'] );
		$this->assertStringContainsString( '3.2.4', $response->get_data()['message'] );
		$this->assertSame(
			[
				'type'   => 'woocommerce_cart_peek',
				'source' => 'woocommerce_cart_peek',
				'theme'  => 'woocommerce_cart_peek_conv-theme-fourteen',
			],
			$this->saved_row( $nx_id )
		);
	}

	public function test_update_keeping_an_unregistered_type_is_allowed() {
		$nx_id = $this->insert_row( 'woocommerce_cart_peek', 'woocommerce_cart_peek', 'woocommerce_cart_peek_conv-theme-fourteen' );

		$response = $this->update_request(
			$nx_id,
			[
				'nx_id'  => $nx_id,
				'type'   => 'woocommerce_cart_peek',
				'source' => 'woocommerce_cart_peek',
				'themes' => 'woocommerce_cart_peek_conv-theme-fourteen',
				'title'  => 'renamed',
			]
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'woocommerce_cart_peek', $this->saved_row( $nx_id )['type'] );
	}

	public function test_update_can_change_a_registered_type() {
		$from  = $this->registered_type();
		$nx_id = $this->insert_row( $from, 'custom_notification', 'theme-one' );

		$check = PostType::get_instance()->check_type_change(
			[
				'nx_id' => $nx_id,
				'type'  => 'some_other_type',
			]
		);

		$this->assertTrue( $check );
	}

	/**
	 * The status toggle sends only source/enabled; it must never be blocked.
	 */
	public function test_status_toggle_is_not_blocked() {
		$nx_id = $this->insert_row( 'woocommerce_cart_peek', 'woocommerce_cart_peek', 'woocommerce_cart_peek_conv-theme-fourteen' );

		$this->assertTrue(
			PostType::get_instance()->check_type_change(
				[
					'nx_id'         => $nx_id,
					'type'          => 'woocommerce_sales',
					'update_status' => true,
				]
			)
		);
		$this->assertTrue( PostType::get_instance()->check_type_change( [ 'type' => 'woocommerce_sales' ] ) );
		$this->assertTrue( PostType::get_instance()->check_type_change( [ 'nx_id' => $nx_id ] ) );
	}

	public function test_get_item_flags_an_unregistered_type() {
		$unavailable = $this->insert_row( 'woocommerce_cart_peek', 'woocommerce_cart_peek', 'woocommerce_cart_peek_conv-theme-fourteen' );
		$available   = $this->insert_row( $this->registered_type(), 'custom_notification', 'theme-one' );

		$response = rest_do_request( new WP_REST_Request( 'GET', "/notificationx/v1/nx/{$unavailable}" ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['type_unavailable'] );

		$response = rest_do_request( new WP_REST_Request( 'GET', "/notificationx/v1/nx/{$available}" ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $response->get_data()['type_unavailable'] );
	}

	/**
	 * The builder sends back every value it loaded; the flag is never stored.
	 */
	public function test_type_unavailable_flag_is_not_stored() {
		$nx_id = $this->insert_row( 'woocommerce_cart_peek', 'woocommerce_cart_peek', 'woocommerce_cart_peek_conv-theme-fourteen' );

		PostType::get_instance()->save_post(
			[
				'nx_id'            => $nx_id,
				'type'             => 'woocommerce_cart_peek',
				'source'           => 'woocommerce_cart_peek',
				'themes'           => 'woocommerce_cart_peek_conv-theme-fourteen',
				'enabled'          => true,
				'type_unavailable' => true,
			]
		);

		$row = Database::get_instance()->get_post( Database::$table_posts, $nx_id, 'data' );
		$this->assertArrayNotHasKey( 'type_unavailable', $row['data'] );
	}

	// --- Frontend: Pro-rendered types ------------------------------------------

	public function test_pro_rendered_types_are_unrenderable_without_pro() {
		$this->assertFalse( NotificationX::is_pro() );
		foreach ( [ 'inline', 'woocommerce_cart_peek', 'offer_announcement' ] as $type ) {
			$this->assertTrue( NotificationX::is_type_unrenderable( $type ), $type );
		}
		foreach ( [ 'conversions', 'flashing_tab', 'notification_bar', 'gdpr', '', null ] as $type ) {
			$this->assertFalse( NotificationX::is_type_unrenderable( $type ), (string) $type );
		}
	}

	/**
	 * Growth Alert used to show as a floating popup with Pro 3.2.3, Discount
	 * Alert logged "Please select a theme": both are skipped now.
	 */
	public function test_frontend_skips_pro_rendered_types() {
		$inline   = $this->insert_row( 'inline', 'woo_inline', 'woo_inline_conv-theme-seven' );
		$discount = $this->insert_row( 'offer_announcement', 'announcements', 'announcements_theme-1' );
		$gdpr     = $this->insert_row( 'gdpr', 'gdpr_notification', 'gdpr_theme-light-one' );

		$result = FrontEnd::get_instance()->get_notifications( [ $inline, $discount, $gdpr ] );

		$this->assertArrayNotHasKey( $inline, $result );
		$this->assertArrayNotHasKey( $discount, $result );
		$this->assertArrayHasKey( $gdpr, $result );

		$ids = FrontEnd::get_instance()->get_notifications_ids();
		$all = array_merge( ...array_values( array_filter( $ids, 'is_array' ) ) );
		$this->assertNotContains( $inline, $all );
		$this->assertNotContains( $discount, $all );
	}

	// --- [notificationx_inline] ------------------------------------------------

	public function test_inline_shortcode_prints_nothing_without_pro() {
		$this->assertTrue( shortcode_exists( 'notificationx_inline' ) );
		$this->assertSame( '', do_shortcode( '[notificationx_inline id="5"]' ) );
		$this->assertSame( 'a  b', do_shortcode( 'a [notificationx_inline id="5" product_id="9"] b' ) );
	}

	/**
	 * Pro registers the real shortcode first; the fallback must not replace it.
	 */
	public function test_inline_shortcode_fallback_keeps_an_existing_handler() {
		global $shortcode_tags;
		$before  = $shortcode_tags['notificationx_inline'];
		$handler = function () {
			return 'pro';
		};
		add_shortcode( 'notificationx_inline', $handler );

		FrontEnd::get_instance()->register_inline_shortcode_fallback();

		$this->assertSame( 'pro', do_shortcode( '[notificationx_inline id="5"]' ) );
		$shortcode_tags['notificationx_inline'] = $before;
	}

	// --- Pro 3.2.2 Flashing Tab script -----------------------------------------

	public function test_old_pro_flashing_tab_script_from_free_is_dequeued() {
		wp_enqueue_script( 'notificationx-public-flashing-tab', NOTIFICATIONX_ASSETS . 'public/js/flashing-tab.js', [], '3.2.2', true );

		FrontEnd::get_instance()->dequeue_removed_scripts();

		$this->assertFalse( wp_script_is( 'notificationx-public-flashing-tab', 'enqueued' ) );
	}

	/**
	 * Pro 3.2.3+ uses the same handle with its own file.
	 */
	public function test_pro_flashing_tab_script_is_kept() {
		wp_enqueue_script( 'notificationx-public-flashing-tab', 'https://example.com/wp-content/plugins/notificationx-pro/assets/public/js/flashing-tab.js', [], '3.2.4', true );

		FrontEnd::get_instance()->dequeue_removed_scripts();

		$this->assertTrue( wp_script_is( 'notificationx-public-flashing-tab', 'enqueued' ) );
	}

	public function test_dequeue_is_hooked_after_add_ons_enqueue() {
		$this->assertSame( 100, has_action( 'wp_enqueue_scripts', [ FrontEnd::get_instance(), 'dequeue_removed_scripts' ] ) );
	}
}
