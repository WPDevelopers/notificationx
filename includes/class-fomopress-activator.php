<?php

/**
 * Fired during plugin activation
 *
 * @link       https://wpdeveloper.net
 * @since      1.0.0
 *
 * @package    NotificationX
 * @subpackage NotificationX/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    NotificationX
 * @subpackage NotificationX/includes
 * @author     WPDeveloper <support@wpdeveloper.net>
 */
class NotificationX_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// _fomopress_activation_notice
		if( current_user_can( 'delete_users' ) ) {
			set_transient( '_fomopress_activation_notice', true, 30 );
		}
		/**
		 * Reqrite the rules on activation.
		 */
		flush_rewrite_rules();
	}

}
