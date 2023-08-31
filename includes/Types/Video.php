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
        parent::__construct();
    }

}
