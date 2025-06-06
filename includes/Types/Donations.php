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
 * @method static Donations get_instance($args = null)
 */
class Donations extends Types {
    /**
     * Instance of Admin
     *
     * @var Admin
     */
    use GetInstance;

    // donation_themes
    public $priority = 40;
    public $themes = [];
    public $module = ['modules_give'];
    public $default_source    = 'give';
    public $default_theme = 'donation_theme-one';
    public $link_type = 'donation_page';


    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
        $this->id     = 'donation';
    }

    public function init() {
        parent::init();
        $this->title  = __('Donations', 'notificationx');
        $common_fields = array(
            'first_param' => 'tag_name',
            'custom_first_param'  => __('Someone', 'notificationx'),
            'second_param' => __('recently donated for', 'notificationx'),
            'third_param' => 'tag_none',
            'custom_third_param' => __('100', 'notificationx'),
            'fourth_param' => 'tag_title',
            'custom_fourth_param' => __('Anonymous Title', 'notificationx'),
            'fifth_param' => 'tag_time',
            'custom_fifth_param' => __('Some time ago', 'notificationx'),
        );
        $this->themes = [
            'theme-one'   => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/donation/donation-theme-1.jpg',
                'image_shape' => 'circle',
                'template'  => $common_fields,
            ],
            'theme-two'   => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/donation/donation-theme-2.jpg',
                'image_shape' => 'circle',
                'template'  => $common_fields,
            ],
            'theme-three' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/donation/donation-theme-3.jpg',
                'image_shape' => 'square',
                'template'  => $common_fields,
            ],
            'theme-four'  => [
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/donation/donation-theme-4.png',
                'image_shape' => 'circle',
                'template'  => $common_fields,
            ],
            'theme-five' => [
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/donation/donation-theme-5.png',
                'image_shape' => 'circle',
                'template'  => $common_fields,
            ],
            'conv-theme-six' => [
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/donation/donation-theme-6.jpg',
                'image_shape' => 'circle',
            ],
            'maps_theme' => [
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/donation/maps-theme.png',
                'image_shape' => 'square',
                'show_notification_image' => 'maps_image',
            ],
            'conv-theme-seven' => [
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/donation/donation-theme-7.png',
                'image_shape' => 'rounded',
            ],
            'conv-theme-eight' => [
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/donation/donation-theme-8.png',
                'image_shape' => 'circle',
            ],
            'conv-theme-nine' => [
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/donation/donation-theme-9.png',
                'image_shape' => 'square',
            ],
        ];
        $this->res_themes = [
            'res-theme-one'   => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_donation/donation-res-theme-1.png',
                '_template' => 'donation_template_new',
                'is_pro'    => true,
            ],
            'res-theme-two'   => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_donation/donation-res-theme-2.png',
                '_template' => 'donation_template_new',
                'is_pro'    => true,
            ],
            'res-theme-three' => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_donation/donation-res-theme-3.png',
                '_template' => 'donation_template_new',
                'is_pro'    => true,
            ],
            'res-theme-four'  => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_donation/donation-res-theme-4.png',
                '_template' => 'donation_template_new',
                'is_pro'    => true,
            ],
            'res-theme-five' => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_donation/donation-res-theme-5.png',
                '_template' => 'donation_template_new',
                'is_pro'    => true,
            ],
            'res-theme-six' => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_donation/donation-res-theme-6.png',
                '_template' => 'donation_template_new',
                'is_pro'    => true,
            ],
            'res-theme-seven' => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_donation/donation-res-theme-7.png',
                '_template' => 'maps_template_new',
                'is_pro'    => true,
            ],
            'res-theme-eight' => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_donation/donation-res-theme-8.png',
                '_template' => 'donation_template_sales_count',
                'is_pro'    => true,
            ],
            'res-theme-nine' => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_donation/donation-res-theme-9.png',
                '_template' => 'donation_template_sales_count',
                'is_pro'    => true,
            ],
            'res-theme-ten' => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_donation/donation-res-theme-10.png',
                '_template' => 'donation_template_sales_count',
                'is_pro'    => true,
            ],
        ];
        $this->templates = [
            'donation_template_new' => [
                'first_param' => GlobalFields::get_instance()->common_name_fields(),
                'third_param' => [ // amount_param
                    'tag_amount' => __('Donation Amount', 'notificationx'),
                    'tag_none'   => __('None', 'notificationx'),
                ],
                'fourth_param' => [ // third_param
                    'tag_title'           => __('Donation For Title', 'notificationx'),
                    // 'tag_anonymous_title' => __('Anonymous Title', 'notificationx'),
                ],
                'fifth_param' => [ // fourth_param
                    'tag_time' => __('Definite Time', 'notificationx'),
                    // @todo add more options in pro version https://github.com/WPDevelopers/notificationx-pro/blob/440595540e9d3e26e9e2b674921dede7174b22eb/features/class-nxpro-sales-features.php#L290
                ],
                '_themes' => [
                    'donation_theme-one',
                    'donation_theme-two',
                    'donation_theme-three',
                    'donation_theme-four',
                    'donation_theme-five',
                    'donation_maps_theme',
                ],
            ],
            'donation_template_sales_count' => [
                'first_param' => GlobalFields::get_instance()->common_name_fields(),
                'third_param' => [ // third_param
                    'tag_title'           => __('Donation For Title', 'notificationx'),
                    // 'tag_anonymous_title' => __('Anonymous Title', 'notificationx'),
                ],
                'fourth_param' => [ // fourth_param
                    // @todo add more options in pro version https://github.com/WPDevelopers/notificationx-pro/blob/440595540e9d3e26e9e2b674921dede7174b22eb/features/class-nxpro-sales-features.php#L290
                ],
                '_themes' => [
                    'donation_conv-theme-six',
                    'donation_conv-theme-seven',
                    'donation_conv-theme-eight',
                    'donation_conv-theme-nine',
                ],
            ],
        ];
    }

    /**
     * Hooked to nx_before_metabox_load action.
     *
     * @return void
     */
    public function init_fields() {
        parent::init_fields();
        add_filter('nx_link_types', [$this, 'link_types']);
    }

    /**
     * Adds option to Link Type field in Content tab.
     *
     * @param array $options
     * @return array
     */
    public function link_types($options) {
        $_options = GlobalFields::get_instance()->normalize_fields([
            'donation_page' => __('Donation Form Page', 'notificationx'),
        ], 'type', $this->id);

        return array_merge($options, $_options);
    }

    public function preview_entry($entry, $settings){
        $entry = array_merge($entry, [
            "title"             => "Fundraising Camp for Health",

        ]);
        return $entry;
    }

}
