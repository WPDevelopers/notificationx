<?php
/**
 * Lists the themes of every source the overhang visual tests render, as JSON (read-only).
 *
 * Usage: php tests/visual/overhang/themes.php /path/to/wp-load.php > "$NX_VISUAL_OUT/themes.json"
 * (add `-d mysqli.default_socket=...` when the site's database is on a socket).
 */

if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) || ! is_file( $argv[1] ) ) {
	fwrite( STDERR, "Usage: php themes.php /path/to/wp-load.php\n" );
	exit( 1 );
}

define( 'DISABLE_WP_CRON', true );
define( 'WP_USE_THEMES', false );
$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require $argv[1];

// One source per type; the reviews sources with their own themes are added too.
$sources = [ 'cf7', 'mailchimp', 'custom_notification', 'woocommerce', 'announcements', 'wp_reviews', 'google_reviews',
	'woo_reviews', 'wp_stats', 'give', 'google', 'tutor', 'woocommerce_sales', 'woocommerce_cart_peek', 'wp_comments', 'youtube' ];
$out     = [];
$factory = \NotificationX\Extensions\ExtensionFactory::get_instance();
foreach ( $sources as $source ) {
	$ext = $factory->get( $source );
	if ( ! $ext ) {
		continue;
	}
	$themes = [];
	foreach ( (array) $ext->get_themes() as $key => $theme ) {
		// What the builder applies when the theme is picked.
		$themes[ $key ] = [
			'template'                => $theme['template'] ?? null,
			'defaults'                => $theme['defaults'] ?? null,
			'image_shape'             => $theme['image_shape'] ?? null,
			'show_notification_image' => $theme['show_notification_image'] ?? null,
		];
	}
	$out[ $source ] = [
		'type'   => $ext->types,
		'themes' => $themes,
		'res'    => array_keys( (array) $ext->get_res_themes() ),
	];
}
echo wp_json_encode( $out, JSON_PRETTY_PRINT );
