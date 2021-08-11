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
        <ul class="reviews nx-template-keys">
            <li><span>Field Name:</span> <strong>Field Key</strong></li>
            <li><span>Username:</span> <strong>username</strong></li>
            <li><span>Email:</span> <strong>email</strong></li>
            <li><span>Rated:</span> <strong>rated</strong></li>
            <li><span>Plugin Name:</span> <strong>plugin_name</strong></li>
            <li><span>Plugin Review:</span> <strong>plugin_review</strong></li>
            <li><span>Review Title:</span> <strong>title</strong></li>
            <li><span>Anonymous Title:</span> <strong>anonymous_title</strong></li>
            <li><span>Rating:</span> <strong>rating</strong></li>
            <li><span>Definite Time:</span> <strong>timestamp</strong></li>
            <li><span>Some time ago:</span> <strong>sometime</strong></li>
        </ul>';
    }
}
