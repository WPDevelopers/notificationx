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
 * @method static Social get_instance($args = null)
 */
class Social extends Types {
    /**
     * Instance of Social
     *
     * @var Social
     */
    use GetInstance;

    public $priority = 10;
    public $themes = [];
    public $module = [
        'modules_google_youtube',
    ];
    public $default_source = 'youtube';
    // public $default_theme  = '';
    // public $link_type      = '';


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        $this->id    = 'social';
        $this->title = __('Social', 'notificationx');
        parent::__construct();
    }

}
