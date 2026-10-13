<?php
/**
 * Regression tests for the unauthenticated stored XSS via `popup-submit`
 * (Wordfence: "NotificationX <= 3.3.3 - Unauthenticated Stored Cross-Site
 * Scripting via 'name' Parameter via popup-submit REST Route").
 *
 * `POST /notificationx/v1/popup-submit` is public by design (popup and exit
 * intent visitors submit it), but the handler accepted any notification ID and
 * stored the entry under that notification's own source. An attacker could
 * therefore inject a row into a WordPress.org Reviews (`wp_reviews`) campaign.
 * WPOrgReview decoded the entity-encoded `name` into `plugin_name`, and the
 * inline shortcode / dynamic block substituted it unescaped into the page.
 *
 * The fix:
 *   - PopupNotification::handle_popup_submission() refuses notifications that
 *     are disabled or whose source is not in Rest\Popup::form_sources().
 *   - WPOrgReview strips tags after decoding `name`.
 *   - (Pro) Core\ShortcodeInline escapes every substituted value.
 *
 * @package Notificationx
 */

use NotificationX\Core\PostType;
use NotificationX\Core\REST;
use NotificationX\Core\Rest\Popup;
use NotificationX\Extensions\WordPress\WPOrgReview;

class Test_Security_Popup_Submit extends WP_UnitTestCase {

	const PAYLOAD = '&lt;img src=x onerror=alert(1)&gt;';

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

		// The route is public; every request here is anonymous.
		wp_set_current_user( 0 );
	}

	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tearDown();
	}

	protected function create_notification( $type, $source, $theme, $enabled = true, $extra = array() ) {
		$saved = PostType::get_instance()->save_post(
			array_merge(
				array(
					'type'    => $type,
					'source'  => $source,
					'themes'  => $theme,
					'enabled' => $enabled,
					'title'   => "popup submit test {$source}",
				),
				$extra
			)
		);
		$this->assertNotEmpty( $saved['nx_id'] );
		return (int) $saved['nx_id'];
	}

	/**
	 * A wp_reviews campaign. Saving one through save_post() fetches
	 * wordpress.org, so store a press_bar row and retag it as Reviews.
	 */
	protected function create_reviews_notification() {
		global $wpdb;
		$nx_id = $this->create_notification( 'notification_bar', 'press_bar', 'press_bar_theme-one' );
		$wpdb->update(
			$wpdb->prefix . 'nx_posts',
			array(
				'type'   => 'reviews',
				'source' => 'wp_reviews',
				'theme'  => 'wp_reviews_total-rated',
			),
			array( 'nx_id' => $nx_id )
		);
		$this->assertSame( 'wp_reviews', PostType::get_instance()->get_post( $nx_id )['source'] );
		return $nx_id;
	}

	protected function submit( $nx_id, $params = array() ) {
		$request = new WP_REST_Request( 'POST', '/notificationx/v1/popup-submit' );
		$params  = array_merge(
			array(
				'nx_id' => $nx_id,
				'name'  => self::PAYLOAD,
			),
			$params
		);
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return $this->server->dispatch( $request );
	}

	protected function entries_for( $nx_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT source, entry_key FROM {$wpdb->prefix}nx_entries WHERE nx_id = %d", $nx_id ),
			ARRAY_A
		);
	}

	/* ---------------------------------------------------------------------
	 * Submission gate
	 * ------------------------------------------------------------------ */

	public function test_route_stays_public() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/notificationx/v1/popup-submit', $routes );
	}

	/**
	 * The reported chain: a Reviews campaign must not accept form entries.
	 */
	public function test_rejects_wp_reviews_notification() {
		$nx_id = $this->create_reviews_notification();

		$response = $this->submit( $nx_id );

		$this->assertSame( 404, $response->get_status() );
		$this->assertFalse( $response->get_data()['success'] );
		$this->assertEmpty( $this->entries_for( $nx_id ), 'Nothing may be stored for a non-form source.' );
	}

	/**
	 * Any other non-form source is refused too, not only wp_reviews.
	 */
	public function test_rejects_other_non_form_source() {
		$nx_id = $this->create_notification( 'notification_bar', 'press_bar', 'press_bar_theme-one' );

		$response = $this->submit( $nx_id );

		$this->assertSame( 404, $response->get_status() );
		$this->assertEmpty( $this->entries_for( $nx_id ) );
	}

	public function test_rejects_disabled_popup() {
		$nx_id = $this->create_notification( 'popup', 'popup_notification', 'popup_notification_theme-four', false );

		$response = $this->submit( $nx_id );

		$this->assertSame( 404, $response->get_status() );
		$this->assertEmpty( $this->entries_for( $nx_id ) );
	}

	public function test_rejects_unknown_notification() {
		$response = $this->submit( 999999 );

		$this->assertSame( 404, $response->get_status() );
	}

	/**
	 * No regression: real popup submissions still save under their own source.
	 */
	public function test_accepts_enabled_popup() {
		$nx_id = $this->create_notification( 'popup', 'popup_notification', 'popup_notification_theme-four' );

		$response = $this->submit(
			$nx_id,
			array(
				'name'    => 'Jane Doe',
				'email'   => 'jane@example.org',
				'message' => 'Hello',
			)
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
		$this->assertSame(
			array(
				array(
					'source'    => 'popup_notification',
					'entry_key' => 'popup_notification_' . $nx_id,
				),
			),
			$this->entries_for( $nx_id )
		);
	}

	/**
	 * No regression: exit intent shares the same endpoint and keeps its source.
	 */
	public function test_accepts_enabled_exit_intent() {
		$nx_id = $this->create_notification( 'exit_intent', 'exit_intent_custom', 'exit_intent_custom_theme-one' );

		$response = $this->submit( $nx_id, array( 'email' => 'jane@example.org' ) );

		$this->assertSame( 200, $response->get_status() );
		$entries = $this->entries_for( $nx_id );
		$this->assertCount( 1, $entries );
		$this->assertSame( 'exit_intent_custom', $entries[0]['source'] );
	}

	/**
	 * A submission without `title` used to raise "Undefined array key".
	 */
	public function test_missing_title_falls_back_to_default() {
		global $wpdb;
		$nx_id = $this->create_notification( 'popup', 'popup_notification', 'popup_notification_theme-four' );

		$this->submit( $nx_id, array( 'name' => 'Jane' ) );

		$data = maybe_unserialize(
			$wpdb->get_var( $wpdb->prepare( "SELECT data FROM {$wpdb->prefix}nx_entries WHERE nx_id = %d", $nx_id ) )
		);
		$this->assertSame( 'Popup Submission', $data['title'] );
	}

	public function test_form_sources_list() {
		$this->assertSame( array( 'popup_notification', 'exit_intent_custom' ), Popup::form_sources() );
	}

	/* ---------------------------------------------------------------------
	 * WPOrgReview decode
	 * ------------------------------------------------------------------ */

	public function test_conversion_data_strips_decoded_markup() {
		$data = WPOrgReview::get_instance()->conversion_data(
			array( 'name' => self::PAYLOAD ),
			array( 'wp_reviews_product_type' => 'plugin' )
		);

		$this->assertStringNotContainsString( '<', $data['name'] );
		$this->assertStringNotContainsString( '<', $data['plugin_name'] );
	}

	/**
	 * Real wordpress.org names arrive entity-encoded; they must still decode.
	 */
	public function test_conversion_data_keeps_entity_decoding() {
		$data = WPOrgReview::get_instance()->conversion_data(
			array( 'name' => 'Foo &amp; Bar&#039;s' ),
			array( 'wp_reviews_product_type' => 'plugin' )
		);

		$this->assertSame( "Foo & Bar's", $data['name'] );
		$this->assertSame( "Foo & Bar's", $data['plugin_name'] );
	}

	public function test_total_rated_theme_strips_decoded_markup() {
		$entries = WPOrgReview::get_instance()->total_rated_theme(
			array(
				array(
					'source' => 'wp_reviews',
					'nx_id'  => 7,
					'name'   => self::PAYLOAD,
				),
			),
			array( 7 ),
			array(
				7 => array(
					'theme'                   => 'reviews_total-rated',
					'wp_reviews_product_type' => 'plugin',
				),
			)
		);

		$entry = reset( $entries );
		$this->assertStringNotContainsString( '<', $entry['name'] );
		$this->assertStringNotContainsString( '<', $entry['plugin_name'] );
	}
}
