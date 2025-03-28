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
 * @method static CustomNotification get_instance($args = null)
 */
class CustomNotification extends Types {
    /**
     * Instance of Admin
     *
     * @var Admin
     */
	use GetInstance;
    public $priority = 55;
    public $is_pro = true;
    public $themes = 'all';
    public $module = ['modules_custom_notification'];
    public $default_source    = 'custom_notification';
    // @todo default theme for custom
    // public $default_theme = 'conversions_theme-one';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
        $this->id = 'custom';
    }

    /**
     * Runs when modules is enabled.
     *
     * @return void
     */
    public function init(){
        parent::init();
        $this->title = __('Custom Notification', 'notificationx');
    }



}
