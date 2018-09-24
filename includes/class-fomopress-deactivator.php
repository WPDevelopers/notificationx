<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://wpdeveloper.net
 * @since      1.0.0
 *
 * @package    FomoPress
 * @subpackage FomoPress/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    FomoPress
 * @subpackage FomoPress/includes
 * @author     WPDeveloper <support@wpdeveloper.net>
 */
class FomoPress_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

		/**
		 * Reqrite the rules on deactivation.
		 */
		flush_rewrite_rules();
	}

}
