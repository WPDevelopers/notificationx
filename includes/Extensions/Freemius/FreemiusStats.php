<?php
/**
 * Freemius Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Freemius;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * FreemiusStats Extension
 * @method static FreemiusStats get_instance($args = null)
 */
class FreemiusStats extends Extension {
    /**
     * Instance of Freemius
     *
     * @var Freemius
     */
    use GetInstance;
    use Freemius;

    public $priority = 10;
    public $id       = 'freemius_stats';
    public $types    = 'download_stats';
    public $img      = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/freemius.png';
    public $doc_link = 'https://notificationx.com/docs/freemius-sales-notification/';
    public $module   = 'modules_freemius';
    public $is_pro   = true;
    public $module_priority = 12;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('Freemius', 'notificationx');
        $this->module_title = __('Freemius', 'notificationx');
        $this->popup = [
            "showCloseButton" => false,
            "denyButtonText" => '',
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            "html"=> __('
                <span>Amazing platform to display download statistics to urge visitors to trust your items.</span>
                <iframe id="email_subscription_video" type="text/html" allowfullscreen width="450" height="235"
                src="https://www.youtube.com/embed/0uANsOSFmtw">
                </iframe>
            ', 'notificationx')
        ];
        parent::__construct();
    }

}
