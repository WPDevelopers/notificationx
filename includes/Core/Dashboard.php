<?php

namespace NotificationX\Core;

use NotificationX\Admin\Admin;
use NotificationX\GetInstance;

/**
 * @method static Dashboard get_instance($args = null)
 */
class Dashboard {

    /**
     * Instance of QuickBuild
     *
     * @var QuickBuild
     */
    use GetInstance;

    /**
     * Initially Invoked when initialized.
     *
     * @hook init
    */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'menu' ], 30 );
    }

    /**
     * This method is responsible for Admin Menu of
     * NotificationX
     *
     * @return void
     */
    public function menu() {
        add_submenu_page( 'nx-admin', __( 'Dashboard', 'notificationx' ), __( 'Dashboard', 'notificationx' ), 'read_notificationx', 'nx-dashboard', [ Admin::get_instance(), 'views' ], 0 );
    }

}
