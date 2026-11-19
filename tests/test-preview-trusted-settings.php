<?php
/**
 * `nx_preview_trusted_settings`: settings an add-on supplies from the server
 * for a preview (for example a stored share-link snapshot).
 *
 * The filter is consulted only when there is no authorised preview POST, and
 * it never makes the POST readable. The gate itself is covered, unchanged, by
 * test-security-preview-gate.php.
 *
 * @package Notificationx
 */

use NotificationX\FrontEnd\Preview;

class Test_Preview_Trusted_Settings extends WP_UnitTestCase {

	/**
	 * Calls of the probe shortcode.
	 *
	 * @var int
	 */
	protected $probe_calls = 0;

	/**
	 * Calls of the trusted-settings filter.
	 *
	 * @var int
	 */
	protected $filter_calls = 0;

	/**
	 * What the trusted-settings filter returns.
	 *
	 * @var mixed
	 */
	protected $trusted = array();

	public function setUp(): void {
		parent::setUp();
		$this->probe_calls  = 0;
		$this->filter_calls = 0;
		$this->trusted      = array();
		add_shortcode(
			'nx_preview_probe',
			function () {
				$this->probe_calls++;
				return 'NX_PROBE_RAN';
			}
		);
		add_filter( 'nx_preview_trusted_settings', array( $this, 'supply' ) );
		wp_set_current_user( 0 );
	}

	public function tearDown(): void {
		remove_shortcode( 'nx_preview_probe' );
		remove_filter( 'nx_preview_trusted_settings', array( $this, 'supply' ) );
		remove_filter( 'nx_is_preview', '__return_true' );
		$_POST = array();
		parent::tearDown();
	}

	public function supply( $settings ) {
		$this->filter_calls++;
		return $this->trusted;
	}

	protected function card( $extra = array() ) {
		return array_merge(
			array(
				'source'                  => 'woocommerce',
				'type'                    => 'conversions',
				'themes'                  => 'conv-theme-one',
				'global_queue'            => false,
				'show_notification_image' => 'featured_image',
				'show_default_image'      => false,
				'notification-template'   => array( 'first_param' => 'tag_name' ),
			),
			$extra
		);
	}

	protected function bar_payload() {
		return base64_encode(
			wp_json_encode(
				array(
					'source'        => 'press_bar',
					'type'          => 'notification_bar',
					'themes'        => 'press_bar_theme-one',
					'global_queue'  => false,
					'press_content' => 'Sale [nx_preview_probe]',
				)
			)
		);
	}

	public function test_no_filter_result_means_no_settings() {
		$this->assertSame( array(), Preview::get_instance()->get_settings() );
		$this->assertSame( 1, $this->filter_calls );
	}

	public function test_trusted_settings_are_returned_without_a_post() {
		$this->trusted = $this->card();
		$this->assertSame( 'woocommerce', Preview::get_instance()->get_settings()['source'] );
	}

	public function test_an_unauthorised_post_is_never_read_with_the_filter_set() {
		$this->trusted = $this->card();
		// Anonymous, a forged nonce: the POST must stay unread.
		$_POST = array(
			'nx-preview'       => $this->bar_payload(),
			'nx-preview-nonce' => 'forged',
		);

		$settings = Preview::get_instance()->get_settings();
		$this->assertSame( 'woocommerce', $settings['source'] );
		$this->assertArrayNotHasKey( 'press_content', $settings );

		add_filter( 'nx_is_preview', '__return_true' );
		$result = Preview::get_instance()->enqueue_scripts( false );
		$this->assertSame( array(), $result['pressbar'] );
		$this->assertSame( 0, $this->probe_calls );
	}

	public function test_an_authorised_post_wins_and_the_filter_is_not_asked() {
		$this->trusted = $this->card();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$_POST = array(
			'nx-preview'       => $this->bar_payload(),
			'nx-preview-nonce' => wp_create_nonce( Preview::NONCE_ACTION ),
		);

		$this->assertSame( 'press_bar', Preview::get_instance()->get_settings()['source'] );
		$this->assertSame( 0, $this->filter_calls );
	}

	public function test_trusted_settings_alone_do_not_switch_preview_on() {
		$this->trusted = $this->card();
		$this->assertFalse( Preview::get_instance()->is_preview() );
		$this->assertSame( 'kept', Preview::get_instance()->enqueue_scripts( 'kept' ) );
	}

	public function test_forced_preview_without_trusted_settings_renders_nothing() {
		add_filter( 'nx_is_preview', '__return_true' );
		$this->assertEmpty( Preview::get_instance()->enqueue_scripts( false ) );
	}

	public function test_forced_preview_with_trusted_settings_renders_one_sample_notification() {
		$this->trusted = $this->card( array( 'id' => 77 ) );
		add_filter( 'nx_is_preview', '__return_true' );

		$result = Preview::get_instance()->enqueue_scripts( false );
		$this->assertTrue( $result['nxPreview'] );
		$this->assertSame( 1, $result['total'] );
		$this->assertSame( array( 'woocommerce' ), array_keys( $result['active'] ) );
		$this->assertCount( 1, $result['active']['woocommerce']['entries'] );

		$post = $result['active']['woocommerce']['post'];
		$this->assertTrue( $post['is_preview'] );
		// A random id, never the saved notification's.
		$this->assertNotSame( 77, $post['nx_id'] );
		// Sample data, as in the builder preview.
		$this->assertSame( 'John', $result['active']['woocommerce']['entries'][0]['first_name'] );
	}

	public function test_trusted_settings_are_cleaned_like_a_post() {
		$this->trusted = $this->card(
			array(
				'custom_contents' => array( array( 'title' => '<script>alert(1)</script>Bread' ) ),
				'nested'          => array( 'a' => array( 'b' => '<b>bold</b>' ) ),
			)
		);
		$settings = Preview::get_instance()->get_settings();
		$this->assertSame( 'Bread', $settings['custom_contents'][0]['title'] );
		$this->assertSame( 'bold', $settings['nested']['a']['b'] );
	}

	public function test_a_filter_returning_garbage_gives_no_settings() {
		foreach ( array( 'press_bar', 42, null, false, new stdClass() ) as $garbage ) {
			$this->trusted = $garbage;
			$this->assertSame( array(), Preview::get_instance()->get_settings() );
		}
	}
}
