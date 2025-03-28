<?php

/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Types;

use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;
use NotificationX\Modules;
use NotificationX\NotificationX;

/**
 * Extension Abstract for all Extension.
 * @method static OfferAnnouncement get_instance($args = null)
 */
class OfferAnnouncement extends Types {
    /**
     * Instance of OfferAnnouncement
     *
     * @var OfferAnnouncement
     */
    use GetInstance;
    public $priority       = 36;
    public $module         = ['modules_announcements'];
    public $id             = 'offer_announcement';
    public $default_source = 'announcements';
    public $default_theme  = 'announcements_theme-one';
    public $is_pro         = true;
    // public $link_type      = 'comment_url';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        
        // add_filter('nx_link_types', [$this, 'link_types']);
    }

    public function init()
    {
        parent::init();
        $this->title = __('Discount Alert', 'notificationx');
        $this->popup = [
            "denyButtonText" => __("<a href='https://notificationx.com/docs/configure-discount-alert/' target='_blank'>More Info</a>", "notificationx"),
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            "html"=> __('
                <span>Discount Alert by NotificationX will allow you to display offers/discounts of your products/services on your website interactively & easily.</span>
                <video id="pro_alert_video_popup" type="text/html" allowfullscreen width="450" height="235" autoplay loop muted>
                    <source src="https://notificationx.com/wp-content/uploads/2024/01/NX-Discount-Alert-1.mp4" type="video/mp4">
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
        // add_filter('nx_content_trim_length_dependency', [$this, 'content_trim_length_dependency']);

    }

    /**
     * Adds option to Link Type field in Content tab.
     *
     * @param array $options
     * @return array
     */
    public function link_types($options){
        $_options = GlobalFields::get_instance()->normalize_fields([
            'comment_url'      => __('Comment URL', 'notificationx'),
        ], 'type', $this->id);

        return array_merge($options, $_options);
    }


    /**
     * This method is an implementable method for All Extension coming forward.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function content_trim_length_dependency($dependency) {
        $dependency[] = 'comments_theme-six-free';
        $dependency[] = 'comments_theme-seven-free';
        $dependency[] = 'comments_theme-eight-free';
        return $dependency;
    }
}
