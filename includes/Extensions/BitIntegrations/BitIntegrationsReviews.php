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
 * @method static BitIntegrationReviews get_instance($args = null)
 */
class BitIntegrtionsReviews extends Extension {
    /**
     * Instance of BitIntegrtions
     *
     * @var BitIntegrtions
     */
    use GetInstance;
    use BitIntegrations;

    public $priority        = 25;
    public $id              = 'bitintegrations_reviews';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/bit-integrations.png';
    public $doc_link        = 'https://notificationx.com/docs/bitintegration-notification-alert/';
    public $types           = 'reviews';
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
                <span>Display review alerts from popular social media networks & encourage visitors to place trust in your business.</span>
            ', 'notificationx')
        ];
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

    /**
     * Get data for BitIntegrations Extension.
     *
     * @param array $args Settings arguments.
     * @return array
     */
    public function get_data( $args = array() ){
        return 'Hello From BitIntegrations';
    }

    public function doc(){
        return '
        <ul class="reviews nx-template-keys">
            <li><span>' . __('Field Name:', 'notificationx') . '</span> <strong>' . __('Field Key', 'notificationx') . '</strong></li>
            <li><span>' . __('Username:', 'notificationx') . '</span> <strong>username</strong></li>
            <li><span>' . __('Email:', 'notificationx') . '</span> <strong>email</strong></li>
            <li><span>' . __('Rated:', 'notificationx') . '</span> <strong>rated</strong></li>
            <li><span>' . __('Plugin Name:', 'notificationx') . '</span> <strong>plugin_name</strong></li>
            <li><span>' . __('Plugin Review:', 'notificationx') . '</span> <strong>plugin_review</strong></li>
            <li><span>' . __('Review Title:', 'notificationx') . '</span> <strong>title</strong></li>
            <li><span>' . __('Anonymous Title:', 'notificationx') . '</span> <strong>anonymous_title</strong></li>
            <li><span>' . __('Rating:', 'notificationx') . '</span> <strong>rating</strong></li>
            <li><span>' . __('Definite Time:', 'notificationx') . '</span> <strong>timestamp</strong></li>
            <li><span>' . __('Some time ago:', 'notificationx') . '</span> <strong>sometime</strong></li>
        </ul>';
    }
}
