<?php
/**
 * Tests for the order query that rebuilds a WooCommerce sales notification.
 *
 * Saving a notification or clicking Regenerate deletes its entries and builds
 * them again from wc_get_orders(). The start of the "Display From The Last"
 * window went through gmdate( 'Y-m-d h:i' ) (issue #218). `h` is the 12-hour
 * hour with no am/pm, so a start between 00:00 and 00:59 UTC moved 12 hours
 * later (orders from the first 12 hours of the window were dropped) and a
 * start between 13:00 and 23:59 UTC moved 12 hours earlier.
 *
 * "Display The Last" and the order statuses were correct and must stay as
 * they are: wc_get_orders() maps `numberposts` to `limit`.
 *
 * WooCommerce is not loaded in this suite, so these tests cover the query
 * arguments. tests/e2e/woo-orders-rebuild.js covers the result with real
 * orders. See docs/qa-guide/woo-sales-rebuild-window.md.
 *
 * @package Notificationx
 */

use NotificationX\Extensions\WooCommerce\WooCommerce;
use NotificationX\Extensions\WooCommerce\WooCommerceSales;

class Test_Woo_Orders_Query extends WP_UnitTestCase {

	/**
	 * Seconds the clock may move between the test's time() and the query's.
	 */
	const CLOCK_SLACK = 5;

	protected function query_args( $data ) {
		return WooCommerce::get_instance()->get_orders_query_args( $data );
	}

	/**
	 * The window start in the query, as a timestamp.
	 */
	protected function window_start( $data ) {
		$args = $this->query_args( $data );
		$this->assertMatchesRegularExpression( '/^>\d+$/', $args['date_created'], 'date_created must be ">" followed by a UTC timestamp.' );
		return (int) substr( $args['date_created'], 1 );
	}

	public function window_provider() {
		return array(
			'30 days (default)'    => array( array( 'display_from' => 30 ), 30 * DAY_IN_SECONDS ),
			'2 days'               => array( array( 'display_from' => 2 ), 2 * DAY_IN_SECONDS ),
			'1 day'                => array( array( 'display_from' => 1 ), DAY_IN_SECONDS ),
			'days, hours, minutes' => array(
				array(
					'display_from'        => 1,
					'display_from_hour'   => 3,
					'display_from_minute' => 15,
				),
				DAY_IN_SECONDS + 3 * HOUR_IN_SECONDS + 15 * MINUTE_IN_SECONDS,
			),
			'hours only'           => array(
				array(
					'display_from'      => 0,
					'display_from_hour' => 6,
				),
				6 * HOUR_IN_SECONDS,
			),
			'minutes only'         => array(
				array(
					'display_from'        => 0,
					'display_from_hour'   => 0,
					'display_from_minute' => 45,
				),
				45 * MINUTE_IN_SECONDS,
			),
			'string values'        => array(
				array(
					'display_from'        => '7',
					'display_from_hour'   => '0',
					'display_from_minute' => '0',
				),
				7 * DAY_IN_SECONDS,
			),
		);
	}

	/**
	 * The window starts exactly "Display From The Last" before now, at any hour
	 * of the day. The old code was 12 hours off for half of the day and always
	 * dropped the seconds.
	 *
	 * @dataProvider window_provider
	 */
	public function test_window_starts_exactly_display_from_ago( $data, $seconds ) {
		$before = time();
		$from   = $this->window_start( $data );
		$after  = time();

		$this->assertGreaterThanOrEqual( $before - $seconds, $from );
		$this->assertLessThanOrEqual( $after - $seconds + self::CLOCK_SLACK, $from );
	}

	/**
	 * With no window set, the start is "now", as before the fix: no order is
	 * newer than that, so nothing is read.
	 */
	public function test_empty_window_starts_now() {
		$before = time();
		$from   = $this->window_start(
			array(
				'display_from'        => 0,
				'display_from_hour'   => 0,
				'display_from_minute' => 0,
			)
		);
		$this->assertGreaterThanOrEqual( $before, $from );
		$this->assertLessThanOrEqual( time() + self::CLOCK_SLACK, $from );
	}

	public function limit_provider() {
		return array(
			'default 20'       => array( array( 'display_last' => 20 ), 20 ),
			'above 10'         => array( array( 'display_last' => 50 ), 50 ),
			'below 10'         => array( array( 'display_last' => 3 ), 3 ),
			'numeric string'   => array( array( 'display_last' => '25' ), 25 ),
			'missing: 10'      => array( array(), 10 ),
		);
	}

	/**
	 * "Display The Last" is unchanged: it reaches the query as `numberposts`,
	 * which wc_get_orders() maps to `limit`.
	 *
	 * @dataProvider limit_provider
	 */
	public function test_display_last_is_unchanged( $data, $expected ) {
		$args = $this->query_args( $data + array( 'display_from' => 30 ) );

		$this->assertSame( $expected, $args['numberposts'] );
	}

	/**
	 * The query has no other argument than before the fix, so wc_get_orders()
	 * keeps its defaults (newest first, orders only).
	 */
	public function test_query_has_only_the_three_known_arguments() {
		$args = $this->query_args( array( 'display_from' => 30 ) );

		$this->assertSame( array( 'status', 'date_created', 'numberposts' ), array_keys( $args ) );
	}

	/**
	 * Order status is unchanged: Processing and Completed by default, the
	 * selected statuses otherwise.
	 */
	public function test_order_status_is_unchanged() {
		$default = $this->query_args( array( 'display_from' => 30 ) );
		$this->assertSame( array( 'wc-completed', 'wc-processing' ), $default['status'] );

		$empty = $this->query_args(
			array(
				'display_from' => 30,
				'order_status' => array(),
			)
		);
		$this->assertSame( array( 'wc-completed', 'wc-processing' ), $empty['status'] );

		$custom = $this->query_args(
			array(
				'display_from' => 30,
				'order_status' => array( 'wc-on-hold' ),
			)
		);
		$this->assertSame( array( 'wc-on-hold' ), $custom['status'] );
	}

	/**
	 * The WooCommerce Sales type uses a subclass of the same extension, so it
	 * gets the same query.
	 */
	public function test_woocommerce_sales_source_uses_the_same_query() {
		$data = array(
			'display_from' => 2,
			'display_last' => 40,
		);
		$args = WooCommerceSales::get_instance()->get_orders_query_args( $data );

		$this->assertSame( 40, $args['numberposts'] );
		$this->assertMatchesRegularExpression( '/^>\d+$/', $args['date_created'] );
	}

	/**
	 * Without WooCommerce there is nothing to read, and saving must not fail.
	 */
	public function test_get_orders_returns_null_without_woocommerce() {
		if ( function_exists( 'wc_get_orders' ) ) {
			$this->markTestSkipped( 'WooCommerce is loaded.' );
		}
		$this->assertNull( WooCommerce::get_instance()->get_orders( array( 'display_from' => 30 ) ) );
		$this->assertNull( WooCommerce::get_instance()->get_orders( array() ) );
	}

	/**
	 * A 12-hour `h` or `g` with no am/pm marker cannot be turned back into the
	 * same time. No extension may build a query date that way.
	 */
	public function test_no_extension_formats_a_date_with_a_12_hour_clock() {
		$root     = dirname( __DIR__ ) . '/includes/Extensions';
		$files    = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
		$offences = array();

		foreach ( $files as $file ) {
			if ( 'php' !== $file->getExtension() ) {
				continue;
			}
			foreach ( file( $file->getPathname() ) as $number => $line ) {
				if ( ! preg_match_all( '/\b(?:gm)?date\(\s*([\'"])((?:(?!\1).)*)\1/', $line, $matches ) ) {
					continue;
				}
				foreach ( $matches[2] as $format ) {
					$plain = preg_replace( '/\\\\./', '', $format );
					if ( preg_match( '/[hg]/', $plain ) && ! preg_match( '/[aA]/', $plain ) ) {
						$offences[] = str_replace( dirname( __DIR__ ) . '/', '', $file->getPathname() ) . ':' . ( $number + 1 ) . " ('$format')";
					}
				}
			}
		}

		$this->assertSame( array(), $offences, 'Use H (24-hour), or pass the timestamp itself.' );
	}
}
