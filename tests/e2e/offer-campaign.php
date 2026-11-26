<?php
/**
 * Site state for tests/e2e/offer-campaign.js.
 *
 * The offer notice and the offer pointer are hidden by state that has nothing to do with the
 * code under test: a dismissal by this user, the notice library's cooldown after any dismissal,
 * another plugin's claim on the shared pointer slot. Run through WP-CLI as the test user:
 *
 *   wp eval-file tests/e2e/offer-campaign.php prepare --user=admin
 *     Prints JSON: the state it found ("snapshot"), whether the campaign can show ("pro",
 *     "in_window") and the pointer's ids. Then removes the blockers.
 *   wp eval-file tests/e2e/offer-campaign.php restore <base64 snapshot> --user=admin
 *     Puts back exactly what `prepare` found.
 *   wp eval-file tests/e2e/offer-campaign.php state --user=admin
 *     Prints JSON: this user's pointer dismissal and the shared slot, as they are now.
 *   wp eval-file tests/e2e/offer-campaign.php scheme <name> --user=admin
 *     Sets this user's admin colour scheme.
 *
 * Local sites only.
 *
 * @package Notificationx
 */

use NotificationX\Admin\OfferPointer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WP-CLI eval-file script.

$nx_e2e_user = get_current_user_id();
if ( ! $nx_e2e_user || ! current_user_can( OfferPointer::CAPABILITY ) ) {
	WP_CLI::error( 'Run as an administrator, e.g. --user=admin.' );
}

$nx_e2e_action      = isset( $args[0] ) ? $args[0] : 'state';
$nx_e2e_cooldown    = 'wpnotice_priority_time_expired';
$nx_e2e_notice_meta = 'notificationx_nx_bfcm_2026_notice_dismissed';
$nx_e2e_user_metas  = array( $nx_e2e_notice_meta, OfferPointer::meta_key(), 'admin_color' );

if ( 'scheme' === $nx_e2e_action ) {
	update_user_meta( $nx_e2e_user, 'admin_color', isset( $args[1] ) ? sanitize_key( $args[1] ) : 'fresh' );
	return;
}

if ( 'state' === $nx_e2e_action ) {
	echo wp_json_encode(
		array(
			'dismissed' => OfferPointer::is_dismissed( $nx_e2e_user ),
			'slot'      => get_option( OfferPointer::PRIORITY_OPTION ),
		)
	);
	return;
}

if ( 'restore' === $nx_e2e_action ) {
	$nx_e2e_snapshot = isset( $args[1] ) ? json_decode( (string) base64_decode( $args[1] ), true ) : null; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- shell-safe argument.
	if ( ! is_array( $nx_e2e_snapshot ) ) {
		WP_CLI::error( 'Missing or unreadable snapshot.' );
	}

	foreach ( $nx_e2e_user_metas as $nx_e2e_key ) {
		if ( array_key_exists( $nx_e2e_key, $nx_e2e_snapshot['meta'] ) ) {
			update_user_meta( $nx_e2e_user, $nx_e2e_key, $nx_e2e_snapshot['meta'][ $nx_e2e_key ] );
		} else {
			delete_user_meta( $nx_e2e_user, $nx_e2e_key );
		}
	}

	if ( $nx_e2e_snapshot['slot_exists'] ) {
		update_option( OfferPointer::PRIORITY_OPTION, $nx_e2e_snapshot['slot'] );
	} else {
		delete_option( OfferPointer::PRIORITY_OPTION );
	}

	if ( $nx_e2e_snapshot['cooldown'] ) {
		set_transient( $nx_e2e_cooldown, $nx_e2e_snapshot['cooldown'], max( 1, (int) $nx_e2e_snapshot['cooldown_until'] - time() ) );
	} else {
		delete_transient( $nx_e2e_cooldown );
	}
	return;
}

if ( 'prepare' !== $nx_e2e_action ) {
	WP_CLI::error( 'Unknown action: ' . $nx_e2e_action );
}

$nx_e2e_meta = array();
foreach ( $nx_e2e_user_metas as $nx_e2e_key ) {
	if ( metadata_exists( 'user', $nx_e2e_user, $nx_e2e_key ) ) {
		$nx_e2e_meta[ $nx_e2e_key ] = get_user_meta( $nx_e2e_user, $nx_e2e_key, true );
	}
}

$nx_e2e_slot     = get_option( OfferPointer::PRIORITY_OPTION, '__missing__' );
$nx_e2e_snapshot = array(
	'meta'           => $nx_e2e_meta,
	'slot_exists'    => '__missing__' !== $nx_e2e_slot,
	'slot'           => '__missing__' === $nx_e2e_slot ? null : $nx_e2e_slot,
	'cooldown'       => get_transient( $nx_e2e_cooldown ),
	'cooldown_until' => (int) get_option( '_transient_timeout_' . $nx_e2e_cooldown ),
);

delete_user_meta( $nx_e2e_user, $nx_e2e_notice_meta );
delete_user_meta( $nx_e2e_user, OfferPointer::meta_key() );
update_user_meta( $nx_e2e_user, 'admin_color', 'fresh' );
delete_option( OfferPointer::PRIORITY_OPTION );
delete_transient( $nx_e2e_cooldown );

echo wp_json_encode(
	array(
		'snapshot'  => base64_encode( wp_json_encode( $nx_e2e_snapshot ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- shell-safe argument.
		'pro'       => OfferPointer::is_pro_active(),
		'in_window' => OfferPointer::in_window( time() ),
		'priority'  => OfferPointer::PRIORITY,
		'action'    => OfferPointer::AJAX_ACTION,
		'url'       => OfferPointer::URL,
	)
);
