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
 * FreemiusReviews Extension
 * @method static FreemiusReviews get_instance($args = null)
 */
class FreemiusReviews extends Extension {
    /**
     * Instance of Freemius
     *
     * @var Freemius
     */
    use GetInstance;
    use Freemius;

    public $priority = 20;
    public $id       = 'freemius_reviews';
    public $types    = 'reviews';
    public $img      = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/freemius.png';
    public $doc_link = 'https://notificationx.com/docs/freemius-sales-notification/';
    public $module   = 'modules_freemius';
    public $is_pro   = true;
    public $module_priority = 12;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
    }

    public function init_extension()
    {
        $this->title = __('Freemius', 'notificationx');
        $this->module_title = __('Freemius', 'notificationx');
        $this->popup = [
            "denyButtonText" => __("<a href='https://notificationx.com/docs/freemius-review-notificationx/' target='_blank'>More Info</a>", "notificationx"),
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            "html"=> __('
                <span>Widely used medium to show review teasers to persuade visitors to trust your offerings.</span>
            ', 'notificationx')
        ];
    }

}
