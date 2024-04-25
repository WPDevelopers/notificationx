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
        } elseif(!$nx_free_version && $_is_table_created ){
            $this->database->update_option( 'nx_free_version', NOTIFICATIONX_VERSION, 'no' );
        }
        if ( version_compare($nx_free_version, '2.8.0', '<=') ) {
            $this->migrate_for_donation();
        }
        if ($nx_free_version !== NOTIFICATIONX_VERSION) {
            $this->database->update_option( 'nx_free_version', NOTIFICATIONX_VERSION, 'no' );
            $this->clear_transient();
        }
    }

    public function clear_transient(){
        delete_transient('nx_builder_fields');
    }

    public function migrate_for_donation() {
        $posts = Database::get_instance()->get_col( Database::$table_posts, 'data', ['type'  => 'donation'] );

        if ( ! empty( $posts ) ) {
            foreach( $posts as $post ) {
                if ( isset( $post['notification-template']['first_param'] ) && $post['notification-template']['first_param'] === 'tag_sales_count' ) {
                    $post['notification-template']['first_param'] = 'tag_donation_count';
                    Database::get_instance()->update_post( Database::$table_posts, ['data' => $post], $post['nx_id'] );
                }
            }
        }
    }
}
