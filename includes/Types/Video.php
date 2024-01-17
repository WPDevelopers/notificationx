<?php

/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Types;

use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;
use NotificationX\Modules;

/**
 * Extension Abstract for all Extension.
 * @method static Video get_instance($args = null)
 */
class Video extends Types {
    /**
     * Instance of Video
     *
     * @var Video
     */
    use GetInstance;

    public $priority = 60;
    public $themes = [];
    public $module = [
        'modules_google_youtube',
    ];
    public $default_source = 'youtube';
    public $is_pro = true;
    // public $default_theme  = '';
    // public $link_type      = '';


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        $this->id    = 'video';
        $this->title = __('Video', 'notificationx');
        $this->popup = [
            "denyButtonText" => __("<a href='https://notificationx.com/docs/youtube-video-activities-popups/' target='_blank'>More Info</a>", "notificationx"),
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            "html"=> __('
                <span>NotificationX will help you increasing engagement of your YouTube channel and gaining more credibility.</span>
                <video id="pro_alert_video_popup" type="text/html" allowfullscreen width="450" height="235" autoplay loop muted>
                    <source src="https://notificationx.com/wp-content/uploads/2024/01/How-To-Show-YouTube-Activities-Popup-With-NotificationX.mp4" type="video/mp4">
                </video>
            ', 'notificationx')
        ];
        parent::__construct();
    }

}
