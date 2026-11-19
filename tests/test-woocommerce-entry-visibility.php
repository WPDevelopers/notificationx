<?php
/**
 * WooCommerce entries of a product that is gone or not published are hidden,
 * and each product is loaded once per request however many entries it has.
 *
 * WooCommerce is not loaded in this suite, so wc_get_product() is a stand-in
 * that reads the product state from this class and counts its calls.
 *
 * @package NotificationX
 */

use NotificationX\Extensions\ExtensionFactory;
use NotificationX\Extensions\WooCommerce\WooCommerce;

if ( ! function_exists( 'wc_get_product' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Stand-in for the WooCommerce function.
	function wc_get_product( $product_id ) {
		return Test_WooCommerce_Entry_Visibility::load_product( $product_id );
	}
	define( 'NX_TEST_WC_GET_PRODUCT_STUB', true );
}

class Test_WooCommerce_Entry_Visibility extends WP_UnitTestCase {

	/** @var array<int, string> Product ID => status. A missing ID is a deleted product. */
	public static $products = array();

	/** @var int[] Product IDs passed to wc_get_product(), in order. */
	public static $loaded = array();

	/** @var WooCommerce */
	private $woo;

	public static function load_product( $product_id ) {
		self::$loaded[] = $product_id;
		if ( ! is_scalar( $product_id ) || ! isset( self::$products[ $product_id ] ) ) {
			return false;
		}
		$status = self::$products[ $product_id ];
		return new class( $status ) {
			private $status;
			public function __construct( $status ) {
				$this->status = $status;
			}
			public function get_status() {
				return $this->status;
			}
		};
	}

	public function set_up() {
		parent::set_up();
		if ( ! defined( 'NX_TEST_WC_GET_PRODUCT_STUB' ) ) {
			$this->markTestSkipped( 'WooCommerce is loaded; this test needs its stand-in for wc_get_product().' );
		}
		$this->woo      = ExtensionFactory::get_instance()->get( 'woocommerce' );
		self::$products = array(
			10 => 'publish',
			11 => 'draft',
			12 => 'trash',
			13 => 'private',
		);
		self::$loaded   = array();
		WooCommerce::forget_product_visibility();
	}

	public function tear_down() {
		WooCommerce::forget_product_visibility();
		parent::tear_down();
	}

	private function hidden( $entry ) {
		return $this->woo->entry_display( false, $entry, array() );
	}

	public function test_published_product_is_shown() {
		$this->assertFalse( $this->hidden( array( 'product_id' => 10 ) ) );
	}

	/**
	 * @dataProvider hidden_entries
	 */
	public function test_entry_is_hidden( $entry ) {
		$this->assertTrue( $this->hidden( $entry ) );
	}

	public function hidden_entries() {
		return array(
			'draft product'     => array( array( 'product_id' => 11 ) ),
			'trashed product'   => array( array( 'product_id' => 12 ) ),
			'private product'   => array( array( 'product_id' => 13 ) ),
			'deleted product'   => array( array( 'product_id' => 99 ) ),
			'no product id'     => array( array( 'title' => 'no id' ) ),
			'empty product id'  => array( array( 'product_id' => '' ) ),
			'zero product id'   => array( array( 'product_id' => 0 ) ),
			'array product id'  => array( array( 'product_id' => array( 10 ) ) ),
		);
	}

	public function test_a_product_is_loaded_once_for_many_entries() {
		for ( $i = 0; $i < 50; $i++ ) {
			$this->assertFalse( $this->hidden( array( 'product_id' => 10 ) ) );
			$this->assertTrue( $this->hidden( array( 'product_id' => 11 ) ) );
			$this->assertTrue( $this->hidden( array( 'product_id' => 99 ) ) );
		}

		$this->assertSame( array( 10, 11, 99 ), self::$loaded );
	}

	public function test_string_and_integer_ids_share_one_check() {
		$this->assertFalse( $this->hidden( array( 'product_id' => 10 ) ) );
		$this->assertFalse( $this->hidden( array( 'product_id' => '10' ) ) );

		$this->assertCount( 1, self::$loaded );
	}

	public function test_entries_without_a_product_load_nothing() {
		$this->hidden( array( 'title' => 'no id' ) );
		$this->hidden( array( 'product_id' => 0 ) );
		$this->hidden( array( 'product_id' => array( 10 ) ) );

		$this->assertSame( array(), self::$loaded );
	}

	public function test_every_woocommerce_source_shares_the_check() {
		$sales = ExtensionFactory::get_instance()->get( 'woocommerce_sales' );
		if ( ! $sales instanceof WooCommerce ) {
			$this->markTestSkipped( 'woocommerce_sales is not registered in this build.' );
		}

		$this->assertFalse( $this->hidden( array( 'product_id' => 10 ) ) );
		$this->assertFalse( $sales->entry_display( false, array( 'product_id' => 10 ), array() ) );

		$this->assertCount( 1, self::$loaded );
	}

	public function test_forgetting_checks_the_product_again() {
		$this->assertFalse( $this->hidden( array( 'product_id' => 10 ) ) );

		self::$products[10] = 'draft';
		$this->assertFalse( $this->hidden( array( 'product_id' => 10 ) ), 'Within a request the first answer is kept.' );

		WooCommerce::forget_product_visibility();
		$this->assertTrue( $this->hidden( array( 'product_id' => 10 ) ) );
	}

	/** init() hooks the reset to post status changes and deletions when WooCommerce is active. */
	public function test_post_status_change_forgets_the_checks() {
		add_action( 'transition_post_status', array( WooCommerce::class, 'forget_product_visibility' ), 10, 0 );
		$post_id = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		$this->assertFalse( $this->hidden( array( 'product_id' => 10 ) ) );
		self::$products[10] = 'draft';

		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			)
		);
		remove_action( 'transition_post_status', array( WooCommerce::class, 'forget_product_visibility' ), 10 );

		$this->assertTrue( $this->hidden( array( 'product_id' => 10 ) ) );
	}
}
