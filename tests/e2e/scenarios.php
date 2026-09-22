<?php
/**
 * Preview scenarios for tests/e2e/compare-frontend-bundles.js.
 *
 * Run through WP-CLI: `wp eval-file tests/e2e/scenarios.php`. Prints JSON:
 * { "<scenario>": { "themes": "...", "b64": "<base64 settings>" } }.
 *
 * Read-only: builds notification settings from NotificationX field defaults
 * and the `nx_get_post` filters. Nothing is saved; the E2E runner POSTs these
 * to NotificationX preview mode (`nx-preview`), which renders from the POST.
 *
 * @package Notificationx
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$nx_e2e_template = array(
	'first_param'         => 'tag_name',
	'custom_first_param'  => 'Someone',
	'second_param'        => 'just purchased',
	'third_param'         => 'tag_product_title',
	'custom_third_param'  => 'Anonymous Product',
	'fourth_param'        => 'tag_time',
	'custom_fourth_param' => 'Some time ago',
);

$nx_e2e_bar = array(
	'press_content' => '<p>Big Summer Sale: 50% off everything</p>',
	'button_text'   => 'Shop now',
	'button_url'    => home_url( '/shop' ),
	'link_button'   => true,
	'position'      => 'top',
);

$nx_e2e_gdpr = array(
	'source' => 'gdpr_notification',
	'type'   => 'gdpr',
);

$nx_e2e_scenarios = array(
	'conversions-theme-one'   => array( 'source' => 'woocommerce', 'type' => 'conversions', 'themes' => 'theme-one', 'notification-template' => $nx_e2e_template ),
	// theme-seven draws its icon with the FontAwesome font.
	'conversions-theme-seven' => array( 'source' => 'woocommerce', 'type' => 'conversions', 'themes' => 'theme-seven', 'notification-template' => $nx_e2e_template ),
	'gdpr-default-delay'      => $nx_e2e_gdpr + array( 'themes' => 'gdpr_theme-light-one' ),
	'gdpr-delay-zero'         => $nx_e2e_gdpr + array( 'themes' => 'gdpr_theme-light-one', 'cookie_visibility_delay_before' => '0' ),
	// The runner clicks a button in these three (see ACTIONS in the runner).
	'gdpr-modal'              => $nx_e2e_gdpr + array( 'themes' => 'gdpr_theme-light-one' ),
	// light-two has no close button, so "Reject All" is shown.
	'gdpr-accept'             => $nx_e2e_gdpr + array( 'themes' => 'gdpr_theme-light-two' ),
	'gdpr-reject'             => $nx_e2e_gdpr + array( 'themes' => 'gdpr_theme-light-two', 'gdpr_cookie_removal' => true ),
	'pressbar-theme-one'      => array( 'source' => 'press_bar', 'type' => 'notification_bar', 'themes' => 'press_bar_theme-one' ) + $nx_e2e_bar,
	// theme-four is in `themes_has_bg` (background image path in Pressbar.tsx).
	'pressbar-theme-four'     => array( 'source' => 'press_bar', 'type' => 'notification_bar', 'themes' => 'press_bar_theme-four' ) + $nx_e2e_bar,
	'popup'                   => array( 'source' => 'popup_notification', 'type' => 'popup', 'themes' => 'popup_notification_theme-one' ),
);

$nx_e2e_out = array();
foreach ( $nx_e2e_scenarios as $nx_e2e_name => $nx_e2e_settings ) {
	$nx_e2e_post = \NotificationX\NotificationX::get_instance()->normalize_post(
		$nx_e2e_settings + array(
			'title'   => "E2E $nx_e2e_name",
			'enabled' => true,
		)
	);
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- NotificationX hook.
	$nx_e2e_post = apply_filters( "nx_get_post_{$nx_e2e_post['source']}", $nx_e2e_post );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- NotificationX hook.
	$nx_e2e_post = apply_filters( 'nx_get_post', $nx_e2e_post );

	$nx_e2e_out[ $nx_e2e_name ] = array(
		'themes' => isset( $nx_e2e_post['themes'] ) ? $nx_e2e_post['themes'] : null,
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- preview mode expects base64 JSON.
		'b64'    => base64_encode( wp_json_encode( $nx_e2e_post ) ),
	);
}

echo wp_json_encode( $nx_e2e_out );
