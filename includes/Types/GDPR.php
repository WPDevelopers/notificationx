<?php

/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Types;

use NotificationX\GetInstance;

/**
 * Extension Abstract for all Extension.
 * @method static GDPR get_instance($args = null)
 */
class GDPR extends Types {
    /**
     * Instance of GDPR
     *
     * @var GDPR
     */
    use GetInstance;

    public $priority = 10;
    public $themes = [];
    public $module = [
        'modules_gdpr',
    ];
    public $default_source = 'gdpr_notification';


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        $this->id    = 'gdpr';
        $this->title = __('GDPR Notification', 'notificationx');
        parent::__construct();
    }

}
