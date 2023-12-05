<?php

/**
 * Extension Factory
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Core;

use NotificationX\GetInstance;
use NotificationX\NotificationX;

/**
 * @method static Upgrader get_instance($args = null)
 */
class Upgrader {
    /**
     * Instance of Upgrader
     *
     * @var Upgrader
     */
    use GetInstance;

    protected $database;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        $this->database  = Database::get_instance();
        $nx_free_version = $this->database->get_option('nx_free_version');
        $nx_db_version   = $this->database->get_option('nx_db_version');

        $_is_table_created = false;
        if ($nx_db_version === false || $nx_db_version != Database::$version) {
            try {
                Database::get_instance()->Create_DB();
                $this->database->update_option('nx_db_version', Database::$version, 'no');
                $_is_table_created = true;
            } catch (\Exception $th) {
                error_log('NX: Database Creation Failed');
            }
        }

        if((!$nx_free_version || version_compare($nx_free_version, '2.0.0', '<')) && $_is_table_created ){
            try {
                Migration::get_instance();
                $this->database->update_option('notificationx_2x_upgraded', true, 'no');
            } catch (\Exception $th) {
                error_log('NX: Migration Failed');
            }
            $this->database->update_option( 'nx_free_version', NOTIFICATIONX_VERSION, 'no' );
        } else if( $nx_free_version && version_compare($nx_free_version, '2.7.9', '<=') ){
            $this->woocommerce_type_migration();
        } else if(!$nx_free_version && $_is_table_created ){
            $this->database->update_option( 'nx_free_version', NOTIFICATIONX_VERSION, 'no' );
        }
        if ($nx_free_version !== NOTIFICATIONX_VERSION) {
            $this->clear_transient();
        }
    }

    public function clear_transient(){
        delete_transient('nx_builder_fields');
    }

    /**
     * This function handles the migration of WooCommerce type notifications.
     * It fetches all the 'conversions' type notifications from the database that have 'woocommerce' as their source.
     * Then, it updates each notification's type to 'woocommerce' and modifies its theme accordingly.
     * Finally, it updates the notification in the database with the new type, theme, and data.
     */
    public function woocommerce_type_migration(){
        // Fetch 'conversions' type notifications with 'woocommerce' as their source from the database.
        // We're not using PostType::get_posts() because it will apply filters.
        $notifications = Database::get_instance()->get_posts( Database::$table_posts, 'nx_id, theme, data', [
            'type'   => 'conversions',
            'source' => 'woocommerce',
        ] );

        // If there are any notifications,
        if(!empty($notifications)){
            // Loop through each notification
            foreach ($notifications as $notification) {
                // Replace 'conversions_' in the theme with 'woocommerce_'
                $theme = str_replace('conversions_', 'woocommerce_', $notification['theme']);

                // Update the notification's type to 'woocommerce' and its theme
                $notification['data']['type']   = 'woocommerce';
                $notification['data']['themes'] = $theme;

                // Update the notification in the database
                PostType::get_instance()->update_post([
                    'type'  => 'woocommerce',
                    'theme' => $theme,
                    'data'  => $notification['data'],
                ], [
                    'nx_id'  => $notification['nx_id'],
                ]);
            }
        }
    }
}
