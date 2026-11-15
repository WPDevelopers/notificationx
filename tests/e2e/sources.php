<?php
/**
 * Registered notification sources for tests/e2e/release-smoke.js.
 *
 * Run through WP-CLI: `wp eval-file tests/e2e/sources.php`. Prints JSON:
 * [ { "source": "...", "type": "...", "themes": "..." } ], one entry per
 * extension active on the site, free and Pro.
 *
 * Read-only: the runner creates and deletes the notifications itself.
 *
 * @package Notificationx
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WP-CLI eval-file script.

$nx_e2e_sources = array();
foreach ( \NotificationX\Extensions\ExtensionFactory::get_instance()->get_all() as $nx_e2e_extension ) {
	$nx_e2e_type   = is_array( $nx_e2e_extension->types ) ? reset( $nx_e2e_extension->types ) : $nx_e2e_extension->types;
	$nx_e2e_themes = $nx_e2e_extension->get_themes_name();
	if ( ! $nx_e2e_extension->id || ! $nx_e2e_type ) {
		continue;
	}
	$nx_e2e_sources[] = array(
		'source' => $nx_e2e_extension->id,
		'type'   => $nx_e2e_type,
		'themes' => $nx_e2e_themes ? reset( $nx_e2e_themes ) : '',
	);
}

echo wp_json_encode( $nx_e2e_sources );
