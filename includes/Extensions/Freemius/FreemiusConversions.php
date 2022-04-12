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
 * Freemius Extension
 */
class FreemiusConversions extends Extension {
    /**
     * Instance of Freemius
     *
     * @var Freemius
     */
    use GetInstance;
    use Freemius;

    public $priority = 15;
    public $id       = 'freemius_conversions';
    public $types    = 'conversions';
    public $img      = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/freemius.png';
    public $doc_link = 'https://notificationx.com/docs/freemius-sales-notification/';
    public $module   = 'modules_freemius';
    public $is_pro   = true;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('Freemius', 'notificationx');
        $this->module_title = __('Freemius', 'notificationx');
        $this->popup = [
            "denyButtonText" => __("<a href='https://notificationx.com/docs/freemius-sales-notification/' target='_blank'>More Info</a>", "notificationx"),
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            "html"=> __('
                <span>Fantastic platform for WordPress users to sell their items all around the world.</span>
                <iframe id="email_subscription_video" type="text/html" allowfullscreen width="450" height="235"
                src="https://www.youtube.com/embed/0uANsOSFmtw">
                </iframe>
            ', 'notificationx')
        ];
        parent::__construct();
    }

}
