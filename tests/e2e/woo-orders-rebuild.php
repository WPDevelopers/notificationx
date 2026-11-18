<?php
/**
 * Test data for tests/e2e/woo-orders-rebuild.js.
 *
 * Run through WP-CLI as a user who can edit notifications:
 *
 *   wp eval-file tests/e2e/woo-orders-rebuild.php setup <ages> --user=admin
 *     <ages> is a comma-separated list of order ages in minutes. Creates one
 *     published product and one Processing order per age, newest first.
 *     Prints JSON: { "product", "orders": [ { "id", "age" } ], "foreign" },
 *     where foreign is the number of other Processing / Completed orders from
 *     the last 31 days (the runner needs 0, because they would use up the limit).
 *   wp eval-file tests/e2e/woo-orders-rebuild.php entries <nx_id> --user=admin
 *     Prints JSON: the order ids of the notification's stored entries.
 *   wp eval-file tests/e2e/woo-orders-rebuild.php teardown <product> <orders> <notifications> --user=admin
 *     Each a comma-separated id list (or "-"). Deletes the notifications and
 *     their entries, the orders, any entry another notification stored for
 *     those orders, and the product.
 *
 * No email is sent: wp_mail() is short-circuited in this process.
 *
 * @package Notificationx
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WP-CLI eval-file script.

if ( ! get_current_user_id() || ! current_user_can( 'edit_notificationx' ) ) {
	WP_CLI::error( 'Run as a user who can edit notifications, e.g. --user=admin.' );
}
if ( ! function_exists( 'wc_get_orders' ) ) {
	WP_CLI::error( 'WooCommerce is not active.' );
}

add_filter( 'pre_wp_mail', '__return_false' );

$nx_e2e_action = isset( $args[0] ) ? $args[0] : '';
$nx_e2e_ids    = static function ( $list ) {
	return array_values( array_filter( array_map( 'absint', explode( ',', (string) $list ) ) ) );
};

if ( 'setup' === $nx_e2e_action ) {
	$nx_e2e_foreign = wc_get_orders(
		array(
			'status'       => array( 'wc-completed', 'wc-processing' ),
			'date_created' => '>' . ( time() - 31 * DAY_IN_SECONDS ),
			'limit'        => -1,
			'return'       => 'ids',
		)
	);

	$nx_e2e_product = new WC_Product_Simple();
	$nx_e2e_product->set_name( 'NX E2E rebuild product' );
	$nx_e2e_product->set_regular_price( '10' );
	$nx_e2e_product->set_status( 'publish' );
	$nx_e2e_product->save();

	$nx_e2e_now    = time();
	$nx_e2e_orders = array();
	foreach ( explode( ',', isset( $args[1] ) ? $args[1] : '' ) as $nx_e2e_age ) {
		$nx_e2e_age   = (int) $nx_e2e_age;
		$nx_e2e_order = wc_create_order( array( 'status' => 'processing' ) );
		$nx_e2e_order->add_product( $nx_e2e_product, 1 );
		$nx_e2e_order->set_billing_first_name( 'E2E' );
		$nx_e2e_order->set_billing_last_name( 'Buyer ' . $nx_e2e_age );
		$nx_e2e_order->set_date_created( $nx_e2e_now - $nx_e2e_age * MINUTE_IN_SECONDS );
		$nx_e2e_order->save();
		$nx_e2e_orders[] = array(
			'id'  => $nx_e2e_order->get_id(),
			'age' => $nx_e2e_age,
		);
	}

	echo wp_json_encode(
		array(
			'product' => $nx_e2e_product->get_id(),
			'orders'  => $nx_e2e_orders,
			'foreign' => count( $nx_e2e_foreign ),
		)
	);
	return;
}

if ( 'entries' === $nx_e2e_action ) {
	$nx_e2e_rows = \NotificationX\Admin\Entries::get_instance()->get_entries(
		array( 'nx_id' => isset( $args[1] ) ? absint( $args[1] ) : 0 ),
		'entry_key'
	);
	echo wp_json_encode(
		array_map(
			static function ( $row ) {
				return (int) strtok( (string) $row['entry_key'], '-' );
			},
			is_array( $nx_e2e_rows ) ? $nx_e2e_rows : array()
		)
	);
	return;
}

if ( 'teardown' === $nx_e2e_action ) {
	foreach ( $nx_e2e_ids( isset( $args[3] ) ? $args[3] : '' ) as $nx_e2e_id ) {
		\NotificationX\Core\PostType::get_instance()->delete_post( $nx_e2e_id );
	}
	global $wpdb;
	foreach ( $nx_e2e_ids( isset( $args[2] ) ? $args[2] : '' ) as $nx_e2e_id ) {
		$nx_e2e_order = wc_get_order( $nx_e2e_id );
		if ( $nx_e2e_order ) {
			$nx_e2e_order->delete( true );
		}
		// An enabled WooCommerce notification on the site stores new orders too.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}nx_entries WHERE entry_key LIKE %s", $wpdb->esc_like( $nx_e2e_id . '-' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
	foreach ( $nx_e2e_ids( isset( $args[1] ) ? $args[1] : '' ) as $nx_e2e_id ) {
		$nx_e2e_product = wc_get_product( $nx_e2e_id );
		if ( $nx_e2e_product ) {
			$nx_e2e_product->delete( true );
		}
	}
	return;
}

WP_CLI::error( 'Unknown action. Use setup, entries or teardown.' );
