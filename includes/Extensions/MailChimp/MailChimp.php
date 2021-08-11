<?php
/**
 * MailChimp Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\MailChimp;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * MailChimp Extension
 */
class MailChimp extends Extension {
    /**
     * Instance of MailChimp
     *
     * @var MailChimp
     */
    use GetInstance;

    public $priority        = 5;
    public $id              = 'mailchimp';
    public $img             = '';
    public $doc_link        = 'https://notificationx.com/docs/mailchimp-email-subscription-alert/';
    public $types           = 'email_subscription';
    public $module          = 'modules_mailchimp';
    public $module_priority = 14;
    public $is_pro          = true;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('MailChimp', 'notificationx');
        $this->module_title = __('MailChimp', 'notificationx');
        parent::__construct();
    }

    /**
     * Get data for MailChimp Extension.
     *
     * @param array $args Settings arguments.
     * @return array
     */
    public function get_data( $args = array() ){
        return 'Hello From MailChimp';
    }

    public function doc(){
        return '<p>Make sure that you have <a target="_blank" href="https://mailchimp.com/help/about-api-keys/">signed in & retrieved API key from MailChimp account</a> to use its campaign & email subscriptions data. For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/mailchimp-email-subscription-alert/">documentation</a>.</p>
		<p>🎦 <a target="_blank" href="https://youtu.be/WvX8feM5DBw">Watch video tutorial</a> to learn quickly</p>
		<p>👉 NotificationX <a target="_blank" href="https://notificationx.com/integrations/mailchimp/">Integration with MailChimp</a></p>
		<p><strong>Recommended Blogs:</strong></p>
		<p>🔥 How To Improve Your <a target="_blank" href="https://wpdeveloper.net/email-marketing-social-proof/">Email Marketing Strategy</a> With Social Proof</p>
		<p>🚀 Hacks To Grow Your <a target="_blank" href="https://wpdeveloper.net/email-subscription-list-wordpress/">Email Subscription List</a> On WordPress Website</p>';
    }
}
