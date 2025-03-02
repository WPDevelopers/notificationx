<?php

/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Types;

use NotificationX\GetInstance;
use NotificationX\Modules;

/**
 * Extension Abstract for all Extension.
 * @method static NotificationBar get_instance($args = null)
 */
class NotificationBar extends Types {
    /**
     * Instance of Admin
     *
     * @var Admin
     */
    use GetInstance;

    public $priority       = 15;
    public $themes         = [];
    public $module         = [];
    public $default_source = 'press_bar';
    public $link_type      = '-1';


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        $this->id = 'notification_bar';
    }

    /**
     * Runs when modules is enabled.
     *
     * @return void
     */
    public function init() {
        parent::init();
        $this->title = __('Notification Bar', 'notificationx');
    }


}
