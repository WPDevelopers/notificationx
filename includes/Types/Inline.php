<?php

/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Types;

use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;

/**
 * Extension Abstract for all Extension.
 * @method static Inline get_instance($args = null)
 */
class Inline extends Types {
    /**
     * Instance of Admin
     *
     * @var Admin
     */
    use GetInstance;
    public $priority       = 50;
    public $is_pro         = true;
    public $module         = ['modules_woocommerce'];
    public $id             = 'inline';
    public $default_source = 'woo_inline';
    public $link_type      = '-1';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        // nx_comment_colored_themes
        parent::__construct();

    }

    public function init()
    {
        parent::init();
        $this->title = __('Growth Alert 🚀', 'notificationx');
        $this->dashboard_title = __('Growth Alert', 'notificationx');
        $this->popup = [
            "denyButtonText" => __("<a href='https://notificationx.com/growth-alert/' target='_blank'>More Info</a>", "notificationx"),
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            "html"=> __('
                <span>Highlight your sales, low stock updates with inline growth alert to boost sales</span>
                <video id="pro_alert_video_popup" type="text/html" allowfullscreen width="450" height="235" autoplay loop muted>
                    <source src="https://notificationx.com/wp-content/uploads/2024/01/Introducing-Growth-Alert-Instant-Sales-Booster-With-NotificationX.mp4" type="video/mp4">
                </video>
            ', 'notificationx')
        ];
    }

    /**
     * Hooked to nx_before_metabox_load action.
     *
     * @return void
     */
    public function init_fields() {
        parent::init_fields();
        add_filter( 'nx_show_on_exclude', array( $this, 'show_on_exclude' ), 10, 4 );

    }

    /**
     * Making sure inline notice don't show as normal notice
     * if pro is disabled.
     *
     * @param  bool $exclude
     * @param  array $settings
     * @return bool
     */
    public function show_on_exclude( $exclude, $settings ) {
        if ( ! empty( $settings['inline_location'] ) && $this->id === $settings['type'] ) {
            return true;
        }
        return $exclude;
    }
}
