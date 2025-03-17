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
 * @method static ZapierReviews get_instance($args = null)
 */
class ZapierReviews extends Extension {
    /**
     * Instance of Zapier
     *
     * @var Zapier
     */
    use GetInstance;
    use Zapier;

    public $priority = 25;
    public $id       = 'zapier_reviews';
    public $img      = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/zapier.png';
    public $doc_link = 'https://notificationx.com/docs/zapier-notification-alert/';
    public $types    = 'reviews';
    public $module   = 'modules_zapier';
    public $is_pro   = true;
    public $module_priority = 16;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
    }

    public function init_extension()
    {
        $this->title = __('Zapier', 'notificationx');
        $this->module_title = __('Zapier', 'notificationx');
        $this->popup = [
            "denyButtonText" => __("<a href='https://notificationx.com/docs/zapier-notification-alert/' target='_blank'>More Info</a>", "notificationx"),
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            "html"=> __('
                <span>Display review alerts from popular social media networks & encourage visitors to place trust in your business.</span>
            ', 'notificationx')
        ];
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
