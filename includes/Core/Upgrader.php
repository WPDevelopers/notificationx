<?php

/**
 * Extension Factory
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Core;

use NotificationX\GetInstance;

/**
 * ExtensionFactory Class
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
        if( isset( $_GET['nx_db_clear'] ) ) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $table_posts   = Database::$table_posts;
            $table_entries = Database::$table_entries;
            $table_stats   = Database::$table_stats;
            $dbDelta = dbDelta( "DROP TABLE $table_posts; DROP TABLE $table_entries; DROP TABLE $table_stats" );

            $this->database->update_option( 'nx_free_version', false, 'no' );
            $this->database->update_option( 'nx_db_version', false, 'no' );
        }

        $nx_free_version = $this->database->get_option('nx_free_version');
        $nx_db_version   = $this->database->get_option('nx_db_version');

        $_is_table_created = false;
        if ($nx_db_version === false) {
            try {
                Database::get_instance()->Create_DB();
                $this->database->update_option('nx_db_version', NOTIFICATIONX_VERSION, 'no');
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
        } elseif(!$nx_free_version && $_is_table_created ){
            $this->database->update_option( 'nx_free_version', NOTIFICATIONX_VERSION, 'no' );
        }
    }

}
