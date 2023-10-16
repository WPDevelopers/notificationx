<?php
/**
 * Announcements Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\OfferAnnouncement;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\ExtensionFactory;
use NotificationX\Types\Conversions;

/**
 * Announcements Extension
 * @method static Announcements get_instance($args = null)
 */
class Announcements extends Extension {
    /**
     * Instance of Announcements
     *
     * @var Announcements
     */
    use GetInstance;

    public $priority        = 10;
    public $id              = 'announcements';
    // public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/custom.png';
    // public $doc_link        = 'https://notificationx.com/docs/custom-notification';
    public $types           = 'offer_announcement';
    public $module          = 'modules_announcements';
    public $module_priority = 18;
    public $is_pro          = true;
    public $link_type       = '-1';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('Announcements', 'notificationx');
        $this->module_title = __('Announcements', 'notificationx');
        parent::__construct();
    }

    /**
     * Get data for CustomNotification Extension.
     *
     * @param array $args Settings arguments.
     * @return array
     */
    public function get_data( $args = array() ){
        return 'Hello From Custom Notification';
    }

    public function supported_themes() {
        $custom                = [];
        $custom['sales_count'] = $this->get_themes_for_type('conversions_count');
        $custom['conversions'] = $this->get_themes_for_type('conversions');
        $custom['maps_theme']  = $this->get_themes_for_type('maps_theme');
        $custom['comments']    = $this->get_themes_for_type('comments');
        $custom['reviews']     = $this->get_themes_for_type('reviews');
        $custom['stats']       = $this->get_themes_for_type('download_stats');
        $custom['subs']        = $this->get_themes_for_type('email_subscription');
        return $custom;
    }

    public function get_themes_for_type($type) {
        $conversions_count = Conversions::get_instance()->conversions_count;
        $maps_theme = array('conversions_maps_theme', 'conversions_conv-theme-six', 'comments_maps_theme', 'email_subscription_maps_theme');
        if ($type == 'conversions_count') return $conversions_count;
        if ($type == 'maps_theme') {
            return $maps_theme;
        }

        $themes = ExtensionFactory::get_instance()->get_themes_for_type($type);

        if ($type == 'conversions') {
            $themes = array_values(array_diff($themes, $conversions_count));
        }
        $themes = array_values(array_diff($themes, $maps_theme));

        return $themes;
    }

    public function doc(){
        return sprintf(__('<p>You can make custom notification for its all types of campaign. For further assistance, check out our step by step <a target="_blank" href="%1$s">documentation</a>.</p>
		<p>🎦 Watch <a target="_blank" href="%2$s">video tutorial</a> to learn quickly</p>
		<p><strong>Recommended Blog:</strong></p>
		<p>🔥 How to <a target="_blank" href="%3$s">Display Custom Notification Alerts</a> On Your Website Using NotificationX</p>', 'notificationx'),
        'https://notificationx.com/docs/custom-notification/',
        'https://www.youtube.com/watch?v=OuTmDZ0_TEw',
        'https://wpdeveloper.com/custom-notificationx-alert-fomo/'
        );
    }
}
