<?php
/**
 * ConvertKit Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\ConvertKit;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * ConvertKit Extension
 */
class ConvertKit extends Extension {
    /**
     * Instance of ConvertKit
     *
     * @var ConvertKit
     */
    use GetInstance;

    public $priority        = 10;
    public $id              = 'convertkit';
    public $img             = '';
    public $doc_link        = 'https://notificationx.com/docs/contact-form-submission-alert/';
    public $types           = 'email_subscription';
    public $module          = 'modules_convertkit';
    public $module_priority = 15;
    public $is_pro          = true;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('ConvertKit', 'notificationx');
        $this->module_title = __('ConvertKit', 'notificationx');
        parent::__construct();
    }

    /**
     * Get data for ConvertKit Extension.
     *
     * @param array $args Settings arguments.
     * @return array
     */
    public function get_data( $args = array() ){
        return 'Hello From ConvertKit';
    }

    public function doc(){
        return '<p>Make sure that you have <a target="_blank" href="https://app.convertkit.com/users/login">signed in & retrieved your API key from ConvertKit account</a> to use its campaign & email subscriptions data. For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/convertkit-alert/">documentation</a>.</p>
		<p>🎦 <a target="_blank" href="https://youtu.be/lk_KMSBkEbY">Watch video tutorial</a> to learn quickly</p>
		<p>👉 NotificationX <a target="_blank" href="https://notificationx.com/integrations/convertkit/">Integration with ConvertKit</a></p>
		<p><strong>Recommended Blog:</strong></p>
		<p>🔥 Connect <a target="_blank" href="https://wpdeveloper.net/convertkit-social-proof/">NotificationX With ConvertKit</a>: Grow Your Audience By Leveraging Social Proof</p>';
    }
}
