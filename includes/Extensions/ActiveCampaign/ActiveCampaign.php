<?php
/**
 * ActiveCampaign Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\ActiveCampaign;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * ActiveCampaign Extension
 * @method static ActiveCampaign get_instance($args = null)
 */
class ActiveCampaign extends Extension {
    /**
     * Instance of ActiveCampaign
     *
     * @var ActiveCampaign
     */
    use GetInstance;

    public $priority        = 15;
    public $id              = 'ActiveCampaign';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/ActiveCampaign.png';
    public $doc_link        = 'https://notificationx.com/docs/ActiveCampaign-email-subscription-alert/';
    public $types           = 'email_subscription';
    public $module          = 'modules_activecampaign';
    public $module_priority = 20;
    public $is_pro          = true;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('ActiveCampaign', 'notificationx');
        $this->module_title = __('ActiveCampaign', 'notificationx');
        parent::__construct();
    }

    /**
     * Get data for ActiveCampaign Extension.
     *
     * @param array $args Settings arguments.
     * @return array
     */
    public function get_data( $args = array() ){
        return 'Hello From ActiveCampaign';
    }

    public function doc(){
        return sprintf(__('<p>Make sure that you have <a target="_blank" href="%1$s">signed in & retrieved API URL & API key from ActiveCampaign account</a> to use its campaign & email subscriptions data. For further assistance, check out our step by step <a target="_blank" href="%2$s">documentation</a>.</p>
		<p>👉 NotificationX <a target="_blank" href="%3$s">Integration with ActiveCampaign</a></p>
		<p><strong>Recommended Blogs:</strong></p>
		<p>🔥 Boosting Engagement with <a target="_blank" href="%4$s">ActiveCampaign Email Subscription Alerts</a> via NotificationX</p>', 'notificationx'),
        'https://help.activecampaign.com/hc/en-us/articles/207317590-Getting-started-with-the-API#getting-started-with-the-api-0-0',
        'https://notificationx.com/docs/activecampaign-email-subscription-alert/',
        'https://notificationx.com/integrations/activecampaign/',
        'https://notificationx.com/blog/activecampaign-email-subscription-alerts/'
        );
    }
}
