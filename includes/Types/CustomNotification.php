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
        $this->id = 'custom';
        $this->title = __('Custom Notification', 'notificationx');
        $this->popup = [
            "denyButtonText" => __("<a href='https://notificationx.com/docs/custom-notification/' target='_blank'>More Info</a>", "notificationx"),
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            "html"=> __('
                <span style="text-align:center;">Display custom conversion notifications as pop up.</span>
                <iframe id="email_subscription_video" type="text/html" allowfullscreen width="450" height="235"
                src="https://www.youtube.com/embed/OuTmDZ0_TEw">
                </iframe>
            ', 'notificationx')
        ];
        parent::__construct();
    }

    /**
     * Runs when modules is enabled.
     *
     * @return void
     */
    public function init(){
        parent::init();

    }



}
