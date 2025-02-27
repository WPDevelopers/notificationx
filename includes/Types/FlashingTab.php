<?php

/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Types;

use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;

/**
 * Extension Abstract for all Extension.
 * @method static FlashingTab get_instance($args = null)
 */
class FlashingTab extends Types {
    /**
     * Instance of FlashingTab
     *
     * @var FlashingTab
     */
    use GetInstance;
    public $priority       = 45;
    public $is_pro         = true;
    public $module         = ['modules_flashing'];
    public $id             = 'flashing_tab';
    public $default_source = 'flashing_tab';
    public $link_type      = '-1';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        // nx_comment_colored_themes
        parent::__construct();
    }

    public function init()
    {
        parent::init();
        $this->title = __('Flashing Tab', 'notificationx');
        $this->dashboard_title = __('Flashing Tab', 'notificationx');
        $this->popup = [
            "denyButtonText" => __("<a href='https://notificationx.com/flashing-tab/' target='_blank'>More Info</a>", "notificationx"),
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            "html"=> __('
                <span>Revive lost visitors and convert them into customers with captivating Flashing Tab alerts.</span>
                <video id="pro_alert_video_popup" type="text/html" allowfullscreen width="450" height="235" autoplay loop muted>
                    <source src="https://notificationx.com/wp-content/uploads/2024/01/How-To-Configure-Flashing-Tab-Alert-With-NotificationX.mp4" type="video/mp4">
                </video>
            ', 'notificationx')
        ];
    }

}
