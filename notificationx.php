<?php
/**
 * @link              https://wpdeveloper.net
 * @since             1.0.0
 * @package           NotificationX
 *
 * @wordpress-plugin
 * Plugin Name:       NotificationX
 * Plugin URI:        https://notificationx.com
 * Description:       Social Proof & Recent Sales Popup, Comment Notification, Subscription Notification, Notification Bar and many more.
 * Version:           1.9.1
 * Author:            WPDeveloper
 * Author URI:        https://wpdeveloper.net
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       notificationx
 * Domain Path:       /languages
 */
/**
 * If this file is called directly, abort.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * Defines CONSTANTS for Whole plugins.
 */
define( 'NOTIFICATIONX_VERSION', '1.9.1' );
define( 'NOTIFICATIONX_PLUGIN_URL', 'https://notificationx.com' );
define( 'NOTIFICATIONX_URL', plugins_url( '/', __FILE__ ) );
define( 'NOTIFICATIONX_ADMIN_URL', NOTIFICATIONX_URL . 'admin/' );
define( 'NOTIFICATIONX_PUBLIC_URL', NOTIFICATIONX_URL . 'public/' );
define( 'NOTIFICATIONX_FILE', __FILE__ );
define( 'NOTIFICATIONX_BASENAME', plugin_basename( __FILE__ ) );
define( 'NOTIFICATIONX_ROOT_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'NOTIFICATIONX_ADMIN_DIR_PATH', NOTIFICATIONX_ROOT_DIR_PATH . 'admin/' );
define( 'NOTIFICATIONX_PUBLIC_PATH', NOTIFICATIONX_ROOT_DIR_PATH . 'public/' );
define( 'NOTIFICATIONX_EXT_DIR_PATH', NOTIFICATIONX_ROOT_DIR_PATH . 'extensions/' );
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-nx-activator.php
 */
function activate_notificationx() {
	require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-nx-activator.php';
	NotificationX_Activator::activate();
}
/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-nx-deactivator.php
 */
function deactivate_notificationx() {
	require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-nx-deactivator.php';
	NotificationX_Deactivator::deactivate();
}
register_activation_hook( __FILE__, 'activate_notificationx' );
register_deactivation_hook( __FILE__, 'deactivate_notificationx' );
/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-nx.php';
/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_NotificationX() {
	$plugin = new NotificationX();
	$plugin->run();
}
run_NotificationX();