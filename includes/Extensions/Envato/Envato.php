<?php
/**
 * Envato Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Envato;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * Envato Extension
 */
class Envato extends Extension {
    /**
     * Instance of Envato
     *
     * @var Envato
     */
    use GetInstance;

    public $priority        = 25;
    public $id              = 'envato';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/envato.png';
    public $doc_link        = 'https://notificationx.com/docs/envato-sales-notification';
    public $types           = 'conversions';
    public $module          = 'modules_envato';
    public $module_priority = 17;
    public $is_pro          = true;
    public $version         = '1.2.0';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('Envato', 'notificationx');
        $this->module_title = __('Envato', 'notificationx');
        parent::__construct();
    }

    /**
     * Get data for Envato Extension.
     *
     * @param array $args Settings arguments.
     * @return array
     */
    public function get_data( $args = array() ){
        return 'Hello From Custom Notification';
    }

    public function doc(){
        return '<p>Make sure that you have <a target="_blank" href="https://account.envato.com/sign_in?to=envato-api">created & signed in to Envato account</a> to use its campaign & product sales data.  For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/envato-sales-notification/">documentation</a>.</p>
		<p>🎦 <a target="_blank" href="https://youtu.be/-df_6KHgr7I">Watch video tutorial</a> to learn quickly</p>
		<p>👉 NotificationX <a target="_blank" href="https://notificationx.com/integrations/envato/">Integration with Envato</a></p>';
    }
}
