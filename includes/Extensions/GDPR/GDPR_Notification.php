<?php
/**
 * Wistia Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\GDPR;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * GDPR Extension
 * @method static GDPR get_instance($args = null)
 */
class GDPR_Notification extends Extension {
    /**
     * Instance of GDPR
     *
     * @var GDPR
     */
    use GetInstance;

    public $priority        = 15;
    public $id              = 'gdpr_notification';
    public $doc_link        = 'https://notificationx.com/docs/google-reviews-with-notificationx/';
    public $types           = 'gdpr';
    public $module          = 'modules_gdpr';
    // public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/GDPR.png';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('GDPR Notification', 'notificationx');
        parent::__construct();
    }

}
