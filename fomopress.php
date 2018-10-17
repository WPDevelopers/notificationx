<?php

/**
 * @link              https://wpdeveloper.net
 * @since             1.0.0
 * @package           FomoPress
 *
 * @wordpress-plugin
 * Plugin Name:       FomoPress
 * Plugin URI:        https://wpdeveloper.net/FomoPress
 * Description:       FOMO notification for WordPress.
 * Version:           1.0.0
 * Author:            WPDeveloper
 * Author URI:        https://wpdeveloper.net
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       FomoPress
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'FOMOPRESS_VERSION', '1.0.0' );

define( 'FOMOPRESS_URL', plugins_url( '/', __FILE__ ) );
define( 'FOMOPRESS_ADMIN_URL', FOMOPRESS_URL . 'admin/' );
define( 'FOMOPRESS_PUBLIC_URL', FOMOPRESS_URL . 'public/' );

define( 'FOMOPRESS_ROOT_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'FOMOPRESS_ADMIN_DIR_PATH', FOMOPRESS_ROOT_DIR_PATH . 'admin/' );
define( 'FOMOPRESS_PUBLIC_PATH', FOMOPRESS_ROOT_DIR_PATH . 'public/' );
define( 'FOMOPRESS_EXT_DIR_PATH', FOMOPRESS_ROOT_DIR_PATH . 'extensions/' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-fomopress-activator.php
 */
function activate_FomoPress() {
	require_once FOMOPRESS_ROOT_DIR_PATH . 'includes/class-fomopress-activator.php';
	FomoPress_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-fomopress-deactivator.php
 */
function deactivate_FomoPress() {
	require_once FOMOPRESS_ROOT_DIR_PATH . 'includes/class-fomopress-deactivator.php';
	FomoPress_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_FomoPress' );
register_deactivation_hook( __FILE__, 'deactivate_FomoPress' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once FOMOPRESS_ROOT_DIR_PATH . 'includes/class-fomopress.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */

$main = array();
$main_reg = array();

function run_fomopress() {
	$plugin = new FomoPress();
	$plugin->run();
}
run_fomopress();