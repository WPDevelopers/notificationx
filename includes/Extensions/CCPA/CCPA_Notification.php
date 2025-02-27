<?php
/**
 * CCPA_Notification Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\CCPA;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * CCPA_Notification Extension
 * @method static CCPA_Notification get_instance($args = null)
 */
class CCPA_Notification extends Extension {
    /**
     * Instance of CCPA_Notification
     *
     * @var CCPA_Notification
     */
    use GetInstance;

    public $priority        = 20;
    public $id              = 'ccpa_notification';
    public $doc_link        = 'https://notificationx.com/docs/google-reviews-with-notificationx/';
    public $types           = 'gdpr';
    // public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/vimeo.png';
    public $show_on_module  = false;
    public $show_on_type     = false;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
    }

    public function init_extension()
    {
        $this->title = __('CCPA', 'notificationx');
    }
}
