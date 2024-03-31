<?php
/**
 * Drip Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Drip;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * Drip Extension
 * @method static Drip get_instance($args = null)
 */
class Drip extends Extension {
    /**
     * Instance of Drip
     *
     * @var Drip
     */
    use GetInstance;

    public $priority        = 16;
    public $id              = 'Drip';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/Drip.png';
    public $doc_link        = 'https://notificationx.com/docs/drip-email-subscription-alert/';
    public $types           = 'email_subscription';
    public $module          = 'modules_drip';
    public $module_priority = 21;
    public $is_pro          = true;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('Drip', 'notificationx');
        $this->module_title = __('Drip', 'notificationx');
        parent::__construct();
    }

    /**
     * Get data for Drip Extension.
     *
     * @param array $args Settings arguments.
     * @return array
     */
    public function get_data( $args = array() ){
        return 'Hello From Drip';
    }

    public function doc(){
		return sprintf(__(
			'<p>Make sure that you have <a target="_blank" href="%1$s">signed in & retrieved API URL & API key from Drip account</a> to use its campaign & email subscriptions data. For further assistance, check out our step by step <a target="_blank" href="%2$s">documentation</a>.</p>
			<p>👉 NotificationX <a target="_blank" href="%3$s">Integration with Drip</a></p>
			<p><strong>Recommended Blogs:</strong></p>
			<p>🔥 Boosting Engagement with <a target="_blank" href="%4$s">Drip Email Subscription Alerts</a> via NotificationX</p>', 'notificationx'),
			'https://help.drip.com/hc/en-us/articles/207317590-Getting-started-with-the-API#getting-started-with-the-api-0-0',
			'https://notificationx.com/docs/drip-email-subscription-alert/',
			'https://notificationx.com/integrations/drip/',
			'https://notificationx.com/blog/drip-email-subscription-alerts/'
        );
    }
}
