<?php

/**
 * @link              https://wpdeveloper.net
 * @since             1.0.0
 * @package           Fomopress
 *
 * @wordpress-plugin
 * Plugin Name:       FomoPress
 * Plugin URI:        https://wpdeveloper.net/fomopress
 * Description:       FOMO notification for WordPress.
 * Version:           1.0.0
 * Author:            WPDeveloper
 * Author URI:        https://wpdeveloper.net
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       fomopress
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'FOMOPRESS_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-fomopress-activator.php
 */
function activate_fomopress() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-fomopress-activator.php';
	Fomopress_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-fomopress-deactivator.php
 */
function deactivate_fomopress() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-fomopress-deactivator.php';
	Fomopress_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_fomopress' );
register_deactivation_hook( __FILE__, 'deactivate_fomopress' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-fomopress.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_fomopress() {

	$plugin = new Fomopress();
	$plugin->run();

}
run_fomopress();
