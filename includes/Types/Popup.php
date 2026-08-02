<?php

/**
 * Popup Notification Type
 *
 * @package NotificationX\Types
 */

namespace NotificationX\Types;

use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;

/**
 * Popup notification type for displaying modal-style notifications
 * @method static Popup get_instance($args = null)
 */
class Popup extends Types {
    /**
     * Instance of Popup
     *
     * @var Popup
     */
    use GetInstance;

    public $priority = 16; // After NotificationBar (15) but before Reviews (20)
    public $themes = [];
    public $module = [];
    public $default_source = 'popup_notification';
    public $id = 'popup';
    // Theme keys are prefixed with the source id ('popup_notification_'), so the
    // default theme must be the fully-prefixed key. 'popup_theme-one' matched no
    // registered theme, leaving the initial theme invalid and breaking default
    // application for rules-gated fields (subtitle / coupon code).
    public $default_theme = 'popup_notification_theme-one';
    public $link_type = 'popup_url';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Runs when modules is enabled.
     *
     * @return void
     */
    public function init() {
        parent::init();
        $this->title = __('Announcement', 'notificationx');
        $this->dashboard_title = __('Announcement', 'notificationx');
    }

    /**
     * Initialize popup-specific fields
     *
     * @return void
     */
    public function init_fields() {
        parent::init_fields();
        // Additional field initialization can be added here
    }
}
