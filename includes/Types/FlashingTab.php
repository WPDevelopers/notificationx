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
        $this->title = __('Flashing Tab', 'notificationx');
        $this->dashboard_title = __('Flashing Tab', 'notificationx');
        $this->popup = [
            "denyButtonText" => __("<a href='https://notificationx.com/growth-alert/' target='_blank'>More Info</a>", "notificationx"),
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            "html"=> __('
                <span>Highlight your sales, low stock updates with inline growth alert to boost sales</span>
                <iframe id="email_subscription_video" type="text/html" allowfullscreen width="450" height="235"
                src="https://www.youtube.com/embed/vXMtBPvizDw">
                </iframe>
            ', 'notificationx')
        ];

        // nx_comment_colored_themes
        parent::__construct();

    }

}
