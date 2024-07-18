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

/**
 * Extension Abstract for all Extension.
 * @method static EmailSubscription get_instance($args = null)
 */
class EmailSubscription extends Types {
    /**
     * Instance of Admin
     *
     * @var Admin
     */
	use GetInstance;

    public $priority = 65;
    public $is_pro = true;
    public $module = [
        'modules_mailchimp',
        'modules_convertkit',
        'modules_mailchimp',
        'modules_zapier',
    ];
    public $default_source    = 'mailchimp';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->id = 'email_subscription';
        $this->title = __('Email Subscription', 'notificationx');
        $this->popup = [
            "denyButtonText" => __("<a href='https://notificationx.com/docs/mailchimp-email-subscription-alert/' target='_blank'>More Info</a>", "notificationx"),
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            "html"=> __('
                <span>Show popups to display which users subscribed to your Newsletter.</span>
                <video id="pro_alert_video_popup" type="text/html" allowfullscreen width="450" height="235" autoplay loop muted>
                    <source src="https://notificationx.com/wp-content/uploads/2024/01/How-to-Display-Email-Subscription-Alerts-using-NotificationX.mp4" type="video/mp4">
                </video>
            ', 'notificationx')
        ];
        parent::__construct();

        $common_fields = [
            'first_param'         => 'tag_first_name',
            'custom_first_param'  => __('Someone' , 'notificationx'),
            'second_param'        => __('just subscribed to', 'notificationx'),
            'third_param'         => 'tag_title',
            'custom_third_param'  => __('Anonymous Title', 'notificationx'),
            'fourth_param'        => 'tag_time',
            'custom_fourth_param' => __( 'Some time ago', 'notificationx' ),
        ];

        $this->themes = [
            'theme-one'   => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/subscriptions/mailchimp-theme-1.jpg',
                'image_shape' => 'rounded',
                'template' => $common_fields,
            ],
            'theme-two'   => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/subscriptions/mailchimp-theme-2.png',
                'template' => $common_fields,
                'image_shape' => 'circle',
            ],
            'theme-three' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/subscriptions/mailchimp-theme-three.jpg',
                'image_shape' => 'square',
                'template' => $common_fields,
            ],
            'maps_theme'  => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/subscriptions/maps-theme-subscribed.png',
                'image_shape' => 'square',
                'show_notification_image' => 'maps_image',
            ],
        ];

        $this->templates = [
            'mailchimp_template_new' => [
                'first_param' => GlobalFields::get_instance()->common_name_fields(),
                'third_param' => [
                    'tag_title'           => __('List Title', 'notificationx'),
                    // 'tag_anonymous_title' => __('Anonymous Title' , 'notificationx'),
                ],
                'fourth_param' => [
                    'tag_time' => __('Definite Time', 'notificationx'),
                    'tag_sometime' => __('Some time ago', 'notificationx'),
                ],
                '_themes' => [
                    "{$this->id}_theme-one",
                    "{$this->id}_theme-two",
                    "{$this->id}_theme-three",
                    "{$this->id}_maps_theme",
                ],
            ],
        ];
    }

    /**
     * Runs when modules is enabled.
     *
     * @return void
     */
    public function init(){
        parent::init();

    }

    public function preview_entry($entry, $settings){
        $entry = array_merge($entry, [
            "title"             => "NotificationX Pro",
        ]);

        if('email_subscription_maps_theme' !== $settings['theme']){
            $entry['image_data'] = array(
                'url'     => NOTIFICATIONX_PUBLIC_URL . 'image/icons/pink-face-looped.gif',
                'alt'     => '',
                'classes' => 'greview_icon',
            );
        }
        return $entry;
    }

}
