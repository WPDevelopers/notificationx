<?php

/**
 * Fired during plugin activation
 *
 * @link       https://wpdeveloper.net
 * @since      1.0.0
 *
 * @package    FomoPress
 * @subpackage FomoPress/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    FomoPress
 * @subpackage FomoPress/includes
 * @author     WPDeveloper <support@wpdeveloper.net>
 */
class FomoPress_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		
		/**
		 * Reqrite the rules on activation.
		 */
		flush_rewrite_rules();
	}

}
