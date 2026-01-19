<?php
/**
 * BitIntegrations Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\BitIntegrations;

use NotificationX\Core\Rules;
use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * BitIntegrations Extension
 * @method static BitIntegrationsConversions get_instance($args = null)
 */
class BitIntegrationsConversions extends Extension {
    /**
     * Instance of BitIntegrations
     *
     * @var BitIntegrations
     */
    use GetInstance;

    public $priority        = 20;
    public $id              = 'bitintegrations_conversions';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/bit-integrations.png';
    public $doc_link        = 'https://notificationx.com/docs/bitintegrations-notification-alert/';
    public $types           = 'conversions';
    public $module          = 'modules_bitintegrations';
    public $module_priority = 16;
    public $class           = 'BitCode\FI\Plugin';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
    }

    public function init_extension()
    {
        $this->title = __('Bit Integrations', 'notificationx');
        $this->module_title = __('Bit Integrations', 'notificationx');
        $this->popup = [
            "denyButtonText" => __("<a href='https://notificationx.com/docs/bitintegrations-notification-alert/' target='_blank'>More Info</a>", "notificationx"),
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            "html"=> __('
                <span>A well-known web-based tool to connect with any of your web-based applications & boost productivity.</span>
                <iframe id="email_subscription_video" type="text/html" allowfullscreen width="450" height="235"
                src="https://www.youtube.com/embed/KjdLv5YMByQ">
                </iframe>
            ', 'notificationx')
        ];
    }

    /**
     * Get data for BitIntegrations Extension.
     *
     * @param array $args Settings arguments.
     * @return array
     */
    public function get_data( $args = array() ){
        return 'Hello From Bit Integrations';
    }

    /**
     * Error message if BitIntegrations is disabled.
     *
     * @param array $messages
     * @return array
     */
   public function source_error_message($messages) {
        if (!$this->class_exists()) {
            $url = admin_url('plugin-install.php?s=bit+integrations&tab=search&type=term');
            $messages[$this->id] = [
                'message' => sprintf(
                    '%s <a href="%s" target="_blank">%s</a> %s',
                    __('You have to install', 'notificationx'),
                    $url,
                    __('Bit Integrations', 'notificationx'),
                    __('plugin first.', 'notificationx')
                ),
                'html' => true,
                'type' => 'error',
                'rules' => Rules::is('source', $this->id),
            ];
        }
        return $messages;
    }

    public function doc(){
        return '
        <ul class="conversions nx-template-keys">
            <li><span>' . __('Field Name:', 'notificationx') . '</span> <strong>' . __('Field Key', 'notificationx') . '</strong></li>
            <li><span>' . __('Full Name:', 'notificationx') . '</span> <strong>name</strong></li>
            <li><span>' . __('First Name:', 'notificationx') . '</span> <strong>first_name</strong></li>
            <li><span>' . __('Last Name:', 'notificationx') . '</span> <strong>last_name</strong></li>
            <li><span>' . __('Sales Count:', 'notificationx') . '</span> <strong>sales_count</strong></li>
            <li><span>' . __('Customer Email:', 'notificationx') . '</span> <strong>email</strong></li>
            <li><span>' . __('Title, Product Title:', 'notificationx') . '</span> <strong>title</strong></li>
            <li><span>' . __('Anonymous Title, Product:', 'notificationx') . '</span> <strong>anonymous_title</strong></li>
            <li><span>' . __('Definite Time:', 'notificationx') . '</span> <strong>timestamp</strong></li>
            <li><span>' . __('Sometime:', 'notificationx') . '</span> <strong>sometime</strong></li>
            <li><span>' . __('In last 1 day:', 'notificationx') . '</span> <strong>1day</strong></li>
            <li><span>' . __('In last 7 days:', 'notificationx') . '</span> <strong>7days</strong></li>
            <li><span>' . __('In last 30 days:', 'notificationx') . '</span> <strong>30days</strong></li>
            <li><span>' . __('City:', 'notificationx') . '</span> <strong>city</strong></li>
            <li><span>' . __('Country:', 'notificationx') . '</span> <strong>country</strong></li>
            <li><span>' . __('City,Country:', 'notificationx') . '</span> <strong>city_country</strong></li>
        </ul>';
    }
}
