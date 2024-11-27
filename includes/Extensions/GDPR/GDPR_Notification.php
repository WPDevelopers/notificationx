<?php
/**
 * Wistia Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\GDPR;

use NotificationX\GetInstance;
use NotificationX\Core\Rules;
use NotificationX\Extensions\Extension;

/**
 * GDPR Extension
 * @method static GDPR get_instance($args = null)
 */
class GDPR_Notification extends Extension {
    /**
     * Instance of GDPR
     *
     * @var GDPR
     */
    use GetInstance;

    public $priority        = 15;
    public $id              = 'gdpr_notification';
    public $doc_link        = 'https://notificationx.com/docs/google-reviews-with-notificationx/';
    public $types           = 'gdpr';
    public $module          = 'modules_gdpr';
    // public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/GDPR.png';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('GDPR Notification', 'notificationx');
        parent::__construct();
        add_filter('nx_content_fields', array($this, 'content_fields'), 999);
        add_filter('nx_customize_fields', array($this, 'customize_fields'), 999);
    }

    public function customize_fields( $fields ) {
        if (isset($fields['appearance'])) {
			$fields['appearance'] = Rules::is('source', $this->id, true, $fields['appearance']);
		}
        if (isset($fields['queue_management'])) {
			$fields['queue_management'] = Rules::is('source', $this->id, true, $fields['queue_management']);
		}
        if (isset($fields['queue_management'])) {
			$fields['queue_management'] = Rules::is('source', $this->id, true, $fields['queue_management']);
		}
        if (isset($fields['timing'])) {
			$fields['timing'] = Rules::is('source', $this->id, true, $fields['timing']);
		}
        if (isset($fields['behaviour'])) {
			$fields['behaviour'] = Rules::is('source', $this->id, true, $fields['behaviour']);
		}
        if (isset($fields['sound_section'])) {
			$fields['sound_section'] = Rules::is('source', $this->id, true, $fields['sound_section']);
		}
        return $fields;
    }

    public function content_fields( $fields ) {
        if (isset($fields['utm_options'])) {
			$fields['utm_options'] = Rules::is('source', $this->id, true, $fields['utm_options']);
		}
        if (isset($fields['content'])) {
			$fields['content'] = Rules::is('source', $this->id, true, $fields['content']);
		}
        return $fields;
    }

}
