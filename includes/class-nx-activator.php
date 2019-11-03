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
		// _nx_meta_activation_notice
		if( current_user_can( 'delete_users' ) ) {
			set_transient( '_nx_meta_activation_notice', true, 30 );
		}

		if( ! get_option( 'nx_free_version', false ) ) {
			$saved_settings = NotificationX_DB::get_settings();
			$settings_args = NotificationX_Settings::settings_args();
			$modules = NotificationX_Settings::get_modules( $settings_args['general']['sections']['modules_sections']['fields'] );
			$default_modules = $modules[0];
			$active_modules = array_fill_keys( array_keys( $default_modules ), true );
			$saved_settings['nx_modules'] = $active_modules;
			NotificationX_DB::update_settings( $saved_settings );
		}
		/**
		 * Reqrite the rules on activation.
		 */
		flush_rewrite_rules();
	}

}
