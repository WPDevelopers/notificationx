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

    public $priority = 10;
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
                <iframe id="email_subscription_video" type="text/html" allowfullscreen width="450" height="235"
                src="https://www.youtube.com/embed/gJLQTpumZS4?si=ErcLlvH7V5rnbCaA">
                </iframe>
            ', 'notificationx')
        ];
        parent::__construct();
    }

}
