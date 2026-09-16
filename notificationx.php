<?php
/**
 * Plugin Name:       NotificationX
 * Plugin URI:        https://notificationx.com
 * Description:       Social Proof & Recent Sales Popup, Comment Notification, Subscription Notification, Notification Bar and many more.
 * Version:           3.3.1
 * Author:            WPDeveloper
 * Author URI:        https://wpdeveloper.com
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       notificationx
 * Domain Path:       /languages
 *
 * @package           NotificationX
 * @link              https://wpdeveloper.com
 * @since             1.0.0
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
define( 'NOTIFICATIONX_FILE', __FILE__ );
define( 'NOTIFICATIONX_VERSION', '3.3.1' );
/**
 * Lowest NotificationX Pro that ships its own copy of the features this plugin
 * used to serve (Cart Peek, Inline, the Flashing Tab runtime). Below this, Pro
 * still loads but those features degrade, so the admin is warned — see
 * notificationx_free_compatibility_notice().
 */
define( 'NOTIFICATIONX_REQUIRED_PRO_VERSION', '3.3.0' );
define( 'NOTIFICATIONX_URL', plugins_url( '/', __FILE__ ) );
define( 'NOTIFICATIONX_PATH', plugin_dir_path( __FILE__ ) );
define( 'NOTIFICATIONX_BASENAME', plugin_basename( __FILE__ ) );

define( 'NOTIFICATIONX_ASSETS', NOTIFICATIONX_URL . 'assets/' );
define( 'NOTIFICATIONX_ASSETS_PATH', NOTIFICATIONX_PATH . 'assets/' );
define( 'NOTIFICATIONX_DEV_ASSETS', NOTIFICATIONX_URL . 'nxbuild/' );
define( 'NOTIFICATIONX_DEV_ASSETS_PATH', NOTIFICATIONX_PATH . 'nxbuild/' );
define( 'NOTIFICATIONX_INCLUDES', NOTIFICATIONX_PATH . 'includes/' );


define( 'NOTIFICATIONX_PLUGIN_URL', 'https://notificationx.com' );
define( 'NOTIFICATIONX_ADMIN_URL', NOTIFICATIONX_ASSETS . 'admin/' );
define( 'NOTIFICATIONX_PUBLIC_URL', NOTIFICATIONX_ASSETS . 'public/' );
define( 'NOTIFICATIONX_COMMON_URL', NOTIFICATIONX_ASSETS . 'common/' );

/**
 * The Core Engine of the Plugin
 */
if ( ! class_exists( '\NotificationX\NotificationX' ) ) {
    require_once NOTIFICATIONX_PATH . 'vendor/autoload.php';
    if ( notificationx_is_plugin_active( 'notificationx-pro/notificationx-pro.php' ) ) {
        add_action( 'admin_notices', 'notificationx_free_compatibility_notice' );
        if ( file_exists( dirname( NOTIFICATIONX_PATH ) . '/notificationx-pro/notificationx-pro.php' ) ) {
            require_once dirname( NOTIFICATIONX_PATH ) . '/notificationx-pro/notificationx-pro.php';
        } else {
            add_action('plugins_loaded', function() {
                remove_action( 'admin_notices', 'notificationx_install_core_notice' );
                \NotificationX\Core\Helper::remove_old_notice();
            });
        }
    }

    function notificationx_activate() {
        \NotificationX\NotificationX::get_instance()->activator();
    }
    /**
     * Plugin Activator
     */
    register_activation_hook( NOTIFICATIONX_FILE, 'notificationx_activate' );
    \NotificationX\NotificationX::get_instance();
}

function notificationx_free_compatibility_notice() {
    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $plugins = get_plugins();
    if ( isset( $plugins['notificationx-pro/notificationx-pro.php']['Version'] ) && version_compare( $plugins['notificationx-pro/notificationx-pro.php']['Version'], NOTIFICATIONX_REQUIRED_PRO_VERSION, '>=' ) ) {
        return;
    }
    /**
     * Not dismissible on purpose. From this version on, Pro features that used to
     * be served by this plugin ship with NotificationX Pro instead, so an outdated
     * Pro silently loses those features rather than failing loudly. The notice has
     * to stay visible until the update is done.
     */
    ?>
        <div class="notice notice-error">
            <p>
            <?php
            echo wp_kses_post(
                sprintf(
                    /* translators: 1: required NotificationX Pro version, 2: URL of the wp-admin plugins page */
                    __( "<strong>Action required: </strong> Your NotificationX Pro is older than %1\$s. Everything still works today, but Pro features such as Cart Peek, Inline notifications and Flashing Tab will stop working after the next NotificationX update. Update NotificationX Pro now from <a href='%2\$s'><strong>wp-admin &rarr; Plugins</strong></a>.", 'notificationx' ),
                    esc_html( NOTIFICATIONX_REQUIRED_PRO_VERSION ),
                    esc_url( admin_url( 'plugins.php' ) )
                )
            ); ?></p>
        </div>
    <?php
}


function notificationx_is_plugin_active( $plugin ) {
    return in_array( $plugin, (array) get_option( 'active_plugins', array() ), true ) || notificationx_is_plugin_active_for_network( $plugin );
}

function notificationx_is_plugin_active_for_network( $plugin ) {
    if ( ! is_multisite() ) {
        return false;
    }

    $plugins = get_site_option( 'active_sitewide_plugins' );
    if ( isset( $plugins[ $plugin ] ) ) {
        return true;
    }

    return false;
}

//declare compliance with WP Consent API
add_filter( "wp_consent_api_registered_".NOTIFICATIONX_BASENAME, '__return_true' );