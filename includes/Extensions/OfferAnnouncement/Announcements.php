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
use NotificationX\Extensions\GlobalFields;
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
    public $link_type       = 'announcements_link';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('Discount Announcement', 'notificationx');
        $this->module_title = __('Discount Announcement', 'notificationx');
        parent::__construct();


        $this->themes = [
            'theme-1'   => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/announcements/theme-1.png',
                'image_shape' => 'rounded',
                'template' => [
                    'first_param'         => 'tag_offer_title',
                    'custom_first_param'  => __('Flash Sale: Limited Time Offer!' , 'notificationx'),
                    'third_param'         => 'tag_offer_description',
                    'custom_third_param'  => __('Enjoy flat 50% Off on NotificationX PRO Valid till this week', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __( 'Some time ago', 'notificationx' ),
                    // 'fifth_param'         => 'tag_offer_discount',
                    // 'custom_fifth_param'  => __( 'Some time ago', 'notificationx' ),
                ],
                'defaults' => [
                    // 'announcement_link_button'      => false,
                    'link'                          => '#',
                    'offer_title'                   => __( 'Flash Sale: Limited Time Offer!', 'notificationx' ),
                    'offer_description'             => __( 'Enjoy flat 50% Off on NotificationX PRO Valid till this week', 'notificationx' ),
                    'announcement_link_button_text' => __( 'Grab Now', 'notificationx' ),
                ],
            ],
            'theme-2'   => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/announcements/theme-2.png',
                'template' => [
                    'first_param'         => 'tag_offer_title',
                    'custom_first_param'  => __('Flash Sale: Limited Time Offer!' , 'notificationx'),
                    'third_param'         => 'tag_offer_description',
                    'custom_third_param'  => __('Enjoy flat 50% Off on NotificationX PRO Valid till this week', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __( 'Some time ago', 'notificationx' ),
                    // 'fifth_param'         => 'tag_offer_discount',
                    // 'custom_fifth_param'  => __( '25% OFF', 'notificationx' ),
                ],
                'defaults' => [
                    // 'announcement_link_button'      => false,
                    'link'                          => '#',
                    'offer_title'                   => __( 'Flash Sale: Limited Time Offer!', 'notificationx' ),
                    'offer_description'             => __( 'Enjoy flat 50% Off on NotificationX PRO Valid till this week', 'notificationx' ),
                    'announcement_link_button_text' => __( 'Grab Now', 'notificationx' ),
                ],
                'image_shape' => 'circle',
            ],
            'theme-12'   => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/announcements/theme-12.png',
                'template' => [
                    'first_param'         => 'tag_offer_title',
                    'custom_first_param'  => __('Flash Sale: Limited Time Offer!' , 'notificationx'),
                    'third_param'         => 'tag_offer_description',
                    'custom_third_param'  => __('Enjoy flat 50% Off on NotificationX PRO Valid till this week', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __( 'Some time ago', 'notificationx' ),
                    // 'fifth_param'         => 'tag_offer_discount',
                    // 'custom_fifth_param'  => __( '25% OFF', 'notificationx' ),
                ],
                'defaults' => [
                    // 'announcement_link_button'      => true,
                    'announcement_link_button_text' => __( 'Buy Now', 'notificationx' ),
                    'link'                          => '#',
                    'offer_title'                   => __( 'Flash Sale: Limited Time Offer!', 'notificationx' ),
                    'link_button'                   => true,
                ],
                'image_shape' => 'rounded',
            ],
            // 'theme-13'   => [
            //     'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/announcements/theme-13.png',
            //     'template' => [
            //         'first_param'         => 'tag_offer_title',
            //         'custom_first_param'  => __('How Does It Works' , 'notificationx'),
            //     ],
            //     'defaults' => [
            //         // 'announcement_link_button'      => false,
            //         'announcement_link_button_text' => __( 'Watch Now', 'notificationx' ),
            //         'offer_title'                   => __( 'How Does It Works', 'notificationx' ),
            //         'link'                          => '#',
            //     ],
            // ],
            'theme-14'   => [
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/announcements/theme-14.png',
                'image_shape' => 'circle',
                'template'    => [
                    'first_param'         => 'tag_offer_title',
                    'custom_first_param'  => __('Hi There!' , 'notificationx'),
                    'third_param'         => 'tag_offer_description',
                    'custom_third_param'  => __('Enjoy flat 50% Off on NotificationX PRO Valid till this week', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __( 'Some time ago', 'notificationx' ),
                      // 'fifth_param'         => 'tag_offer_discount',
                      // 'custom_fifth_param'  => __( 'Some time ago', 'notificationx' ),
                ],
                'defaults' => [
                      // 'announcement_link_button'      => false,
                    'announcement_link_button_text' => __( 'Get It Now', 'notificationx' ),
                    'offer_title'                   => __( 'Hi There!', 'notificationx' ),
                    'link'                          => '#',
                ],
            ],
            'theme-15'   => [
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/announcements/theme-15.png',
                'template'    => [
                    'first_param'         => 'tag_offer_title',
                    'custom_first_param'  => __('Hi There!' , 'notificationx'),
                    'third_param'         => 'tag_offer_description',
                    'custom_third_param'  => __('Enjoy flat 50% Off on NotificationX PRO', 'notificationx'),
                ],
                'defaults' => [
                    // 'announcement_link_button'      => false,
                    'announcement_link_button_text' => __( 'Book Now', 'notificationx' ),
                    'offer_title'                   => __( 'Hi There!', 'notificationx' ),
                    'offer_description'             => __( 'Enjoy flat 50% Off on NotificationX PRO', 'notificationx' ),
                    'link'                          => '#',
                ],
            ],
        ];

        $this->templates = [
            'announcements_template_new' => [
                'first_param' => [
                    'tag_offer_title' => __('Offer Title', 'notificationx'),
                ],
                'third_param' => [
                    'tag_offer_description' => __('Offer Description', 'notificationx'),
                    // 'tag_anonymous_title' => __('Anonymous Title' , 'notificationx'),
                ],
                'fourth_param' => [
                    'tag_time'     => __('Definite Time', 'notificationx'),
                    'tag_sometime' => __('Some time ago', 'notificationx'),
                ],
                'fifth_param' => [
                    'tag_offer_discount' => __('Discount', 'notificationx'),
                    'tag_offer_image'    => __('Image', 'notificationx'),
                ],
                '_themes' => [
                    "{$this->id}_theme-1",
                    "{$this->id}_theme-2",
                    "{$this->id}_theme-12",
                    "{$this->id}_theme-14",
                    "{$this->id}_theme-15",
                ],
            ],
        ];
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

    public function doc(){
        return sprintf(__('<p>You can showcase the discount alert popup on your WordPress website to make visitors take purchasing action immediately. For further assistance, check out our step-by-step <a target="_blank" href="%1$s">documentation</a>.</p>
		<p><strong>Recommended Blog:</strong></p>
		<p>🔥Introducing Discount Alert By NotificationX <a target="_blank" href="%2$s">Guide To Notify Customers About On-Sale Products</a> </p>', 'notificationx'),
        'https://notificationx.com/docs/configure-discount-alert/',
        'https://notificationx.com/discount-alerts/'
        );
    }
}
