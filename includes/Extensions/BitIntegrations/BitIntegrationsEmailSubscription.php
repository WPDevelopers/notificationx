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
 * @method static BitIntegrationsEmailSubscription get_instance($args = null)
 */
class BitIntegrationsEmailSubscription extends Extension {
    /**
     * Instance of BitIntegrations
     *
     * @var BitIntegrations
     */
    use GetInstance;
    use BitIntegrations;

    public $priority        = 15;
    public $id              = 'bitintegration_email_subscription';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/bit-integrations.png';
    public $doc_link        = 'https://notificationx.com/docs/bitintegration-notification-alert/';
    public $types           = 'email_subscription';
    public $module          = 'modules_bitintegration';
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
        $this->title = __('BitIntegrations', 'notificationx');
        $this->module_title = __('BitIntegrations', 'notificationx');
    }

    /**
     * Get data for BitIntegrations Extension.
     *
     * @param array $args Settings arguments.
     * @return array
     */
    public function get_data( $args = array() ){
        return 'Hello From BitIntegrations';
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
        <ul class="email_subscription nx-template-keys">
            <li><span>' . __('Field Name:', 'notificationx') . '</span> <strong>' . __('Field Key', 'notificationx') . '</strong></li>
            <li><span>' . __('Full Name:', 'notificationx') . '</span> <strong>name</strong></li>
            <li><span>' . __('First Name:', 'notificationx') . '</span> <strong>first_name</strong></li>
            <li><span>' . __('Last Name:', 'notificationx') . '</span> <strong>last_name</strong></li>
            <li><span>' . __('Email:', 'notificationx') . '</span> <strong>email</strong></li>
            <li><span>' . __('Title, Product Title:', 'notificationx') . '</span> <strong>title</strong></li>
            <li><span>' . __('Anonymous Title:', 'notificationx') . '</span> <strong>anonymous_title</strong></li>
            <li><span>' . __('Definite Time:', 'notificationx') . '</span> <strong>timestamp</strong></li>
            <li><span>' . __('Some time ago:', 'notificationx') . '</span> <strong>sometime</strong></li>
            <li><span>' . __('City:', 'notificationx') . '</span> <strong>city</strong></li>
            <li><span>' . __('Country:', 'notificationx') . '</span> <strong>country</strong></li>
            <li><span>' . __('City,Country:', 'notificationx') . '</span> <strong>city_country</strong></li>
        </ul>';
    }
}
