<?php
/**
 * Login session for tests/e2e/compare-frontend-bundles.js.
 *
 * Preview mode only renders a POSTed `nx-preview` payload for a user who can
 * edit notifications and sends a valid `nx-preview-nonce`. Run through WP-CLI
 * as such a user:
 *
 *   wp eval-file tests/e2e/auth.php create --user=admin
 *     Prints JSON: { "name", "value", "admin", "token", "nonce", "expires" },
 *     where name/value is the logged_in cookie, admin is the wp-admin auth
 *     cookie, and nonce is valid for that session.
 *   wp eval-file tests/e2e/auth.php destroy <token> --user=admin
 *     Ends the session again.
 *
 * Writes one session token to the user's meta (`session_tokens`); the runner
 * destroys it when it finishes, and it expires after an hour regardless.
 *
 * @package Notificationx
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WP-CLI eval-file script.

$nx_e2e_user = get_current_user_id();
if ( ! $nx_e2e_user || ! current_user_can( 'edit_notificationx' ) ) {
	WP_CLI::error( 'Run as a user who can edit notifications, e.g. --user=admin.' );
}

$nx_e2e_action = isset( $args[0] ) ? $args[0] : 'create';
$nx_e2e_tokens = WP_Session_Tokens::get_instance( $nx_e2e_user );

if ( 'destroy' === $nx_e2e_action ) {
	if ( empty( $args[1] ) ) {
		WP_CLI::error( 'Missing session token.' );
	}
	$nx_e2e_tokens->destroy( $args[1] );
	return;
}

$nx_e2e_expires = time() + HOUR_IN_SECONDS;
$nx_e2e_token   = $nx_e2e_tokens->create( $nx_e2e_expires );
$nx_e2e_cookie  = wp_generate_auth_cookie( $nx_e2e_user, $nx_e2e_expires, 'logged_in', $nx_e2e_token );
$nx_e2e_secure  = 'https' === wp_parse_url( admin_url(), PHP_URL_SCHEME );

// wp_create_nonce() ties the nonce to the session token read from this cookie.
$_COOKIE[ LOGGED_IN_COOKIE ] = $nx_e2e_cookie;

echo wp_json_encode(
	array(
		'name'    => LOGGED_IN_COOKIE,
		'value'   => $nx_e2e_cookie,
		// wp-admin cookie, for runners that open the builder (builder-preview.js).
		'admin'   => array(
			'name'  => $nx_e2e_secure ? SECURE_AUTH_COOKIE : AUTH_COOKIE,
			'value' => wp_generate_auth_cookie( $nx_e2e_user, $nx_e2e_expires, $nx_e2e_secure ? 'secure_auth' : 'auth', $nx_e2e_token ),
		),
		'token'   => $nx_e2e_token,
		'nonce'   => wp_create_nonce( \NotificationX\FrontEnd\Preview::NONCE_ACTION ),
		'expires' => $nx_e2e_expires,
	)
);
