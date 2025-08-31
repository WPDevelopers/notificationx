<?php

/**
 * Popup Notification
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Popup;

use NotificationX\GetInstance;
use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;
use NotificationX\Extensions\Extension;

/**
 * Popup Extension
 * @method static Popup get_instance($args = null)
 */
class PopupNotification extends Extension {
    /**
     * Instance of Popup
     *
     * @var Popup
     */
    use GetInstance;

    public $priority        = 15;
    public $id              = 'popup_notification';
    public $doc_link        = 'https://notificationx.com/docs/google-reviews-with-notificationx/';
    public $types           = 'popup';
    public $module          = 'modules_popup';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
    }

    public function init_extension()
    {
        $this->title = __('Popup', 'notificationx');
        $this->module_title = __('Popup', 'notificationx');
    }
    

}
