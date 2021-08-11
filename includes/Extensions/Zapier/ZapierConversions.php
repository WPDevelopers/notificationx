<?php
/**
 * Zapier Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Zapier;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * Zapier Extension
 */
class ZapierConversions extends Extension {
    /**
     * Instance of Zapier
     *
     * @var Zapier
     */
    use GetInstance;
    use Zapier;

    public $priority = 20;
    public $id       = 'zapier_conversions';
    public $img      = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/zapier.png';
    public $doc_link = 'https://notificationx.com/docs/zapier-notification-alert/';
    public $types    = 'conversions';
    public $module   = 'modules_zapier';
    public $is_pro   = true;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('Zapier', 'notificationx');
        $this->module_title = __('Zapier', 'notificationx');
        parent::__construct();
    }

    /**
     * Get data for Zapier Extension.
     *
     * @param array $args Settings arguments.
     * @return array
     */
    public function get_data( $args = array() ){
        return 'Hello From Zapier';
    }

    public function _doc(){
        return '
        <ul class="conversions nx-template-keys">
            <li><span>Field Name:</span> <strong>Field Key</strong></li>
            <li><span>Full Name:</span> <strong>name</strong></li>
            <li><span>First Name:</span> <strong>first_name</strong></li>
            <li><span>Last Name:</span> <strong>last_name</strong></li>
            <li><span>Sales Count:</span> <strong>sales_count</strong></li>
            <li><span>Customer Email:</span> <strong>email</strong></li>
            <li><span>Title, Product Title:</span> <strong>title</strong></li>
            <li><span>Anonymous Title, Product:</span> <strong>anonymous_title</strong></li>
            <li><span>Definite Time:</span> <strong>timestamp</strong></li>
            <li><span>Sometime:</span> <strong>sometime</strong></li>
            <li><span>In last 1 day:</span> <strong>1day</strong></li>
            <li><span>In last 7 days:</span> <strong>7days</strong></li>
            <li><span>In last 30 days:</span> <strong>30days</strong></li>
            <li><span>City:</span> <strong>city</strong></li>
            <li><span>Country:</span> <strong>country</strong></li>
            <li><span>City,Country:</span> <strong>city_country</strong></li>
        </ul>';
    }
}
