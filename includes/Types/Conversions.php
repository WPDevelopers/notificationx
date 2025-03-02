<?php
/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Types;

use NotificationX\Core\Rules;
use NotificationX\Types\Traits\Conversions as TraitsConversions;
use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;
use NotificationX\Modules;
use NotificationX\NotificationX;

/**
 * Extension Abstract for all Extension.
 * @method static Conversions get_instance($args = null)
 */
class Conversions extends Types {
    /**
     * Instance of Admin
     *
     * @var Admin
     */
	use GetInstance;
    use TraitsConversions;

    // colored_themes
    public $priority = 5;
    public $themes = [];
    public $module = [
        'modules_edd',
        'modules_custom_notification',
        'modules_zapier',
        'modules_freemius',
        'modules_envato',
    ];

    public $conversions_count = array('conversions_conv-theme-seven', 'conversions_conv-theme-eight', 'conversions_conv-theme-nine','woocommerce_sales_conv-theme-seven', 'woocommerce_sales_conv-theme-eight', 'woocommerce_sales_conv-theme-nine');
    public $map_dependency = [];


    public $default_source    = 'woocommerce';
    public $default_theme     = 'conversions_theme-one';
    public $default_res_theme = 'conversions_res-theme-one';
    public $link_type         = 'product_page';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
        $this->id = 'conversions';
    }

    public function init()
    {
        parent::init();
        $this->title = __('Sales Notification', 'notificationx');
        // nx_colored_themes
        $common_fields = [
            'first_param'         => 'tag_name',
            'custom_first_param'  => __('Someone' , 'notificationx'),
            'second_param'        => __('just purchased', 'notificationx'),
            'third_param'         => 'tag_product_title',
            'custom_third_param'  => __('Anonymous Product', 'notificationx'),
            'fourth_param'        => 'tag_time',
            'custom_fourth_param' => __( 'Some time ago', 'notificationx' ),
        ];
        $this->themes = [
            'theme-one'   => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/nx-conv-theme-2.jpg',
                'image_shape' => 'square',
                'template'  => $common_fields,
            ],
            'theme-two'   => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/nx-conv-theme-1.jpg',
                'image_shape' => 'square',
                'template'  => $common_fields,
            ],
            'theme-three' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/nx-conv-theme-3.jpg',
                'image_shape' => 'square',
                'template'  => $common_fields,
            ],
            'theme-five' => array(
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/nx-conv-theme-five.png',
                'image_shape' => 'circle',
                'template'  => $common_fields,
            ),
            'theme-four' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/nx-conv-theme-four.png',
                'image_shape' => 'circle',
                'template'  => $common_fields,
            ),
            // @todo pro map theme
            'conv-theme-six' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/nx-conv-theme-6.jpg',
                'image_shape' => 'circle',
            ),
            // @todo pro map theme
            'maps_theme' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/maps-theme.png',
                'image_shape' => 'square',
                'show_notification_image' => 'maps_image',
            ),
            'conv-theme-ten' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/nx-conv-theme-4.png',
                'image_shape' => 'rounded',
                'defaults'     => [
                    'link_button'   => true,
                    'link_button_text'  => __( 'Buy Now','notificationx' ),
                ],
                'template'  => $common_fields,
            ),
            'conv-theme-eleven' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/nx-conv-theme-5.png',
                'image_shape' => 'rounded',
                'defaults'     => [
                    'link_button'   => true,
                    'link_button_text'  => __( 'Buy Now','notificationx' ),
                ],
                'template'  => $common_fields,
            ),
            'conv-theme-seven' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/nx-conv-theme-7.png',
                'image_shape' => 'rounded',
            ),
            'conv-theme-eight' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/nx-conv-theme-8.png',
                'image_shape' => 'circle',

            ),
            'conv-theme-nine' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/nx-conv-theme-9.png',
                'image_shape' => 'rounded',
            ),
        ];
        $this->res_themes = [
            'res-theme-one'   => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_conv/nx-conv-res-theme-1.png',
                '_template' => 'woo_template_new',
                'is_pro'    => true,
            ],
            'res-theme-two'   => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_conv/nx-conv-res-theme-2.png',
                '_template' => 'woo_template_new',
                'is_pro'    => true,
            ],
            'res-theme-three'   => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_conv/nx-conv-res-theme-3.png',
                '_template' => 'woo_template_new',
                'is_pro'    => true,
            ],
            'res-theme-four'   => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_conv/nx-conv-res-theme-4.png',
                '_template' => 'woo_template_new',
                'is_pro'    => true,
            ],
            'res-theme-five'   => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_conv/nx-conv-res-theme-5.png',
                '_template' => 'maps_template_new',
                'is_pro'    => true,
            ],
            'res-theme-six'   => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_conv/nx-conv-res-theme-6.png',
                '_template' => 'maps_template_new',
                'is_pro'    => true,
            ],
            'res-theme-seven'   => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_conv/nx-conv-res-theme-7.png',
                '_template' => 'woo_template_new',
                'is_pro'    => true,
            ],
            'res-theme-eight'   => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_conv/nx-conv-res-theme-8.png',
                '_template' => 'woo_template_new',
                'is_pro'    => true,
            ],
            'res-theme-nine'   => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_conv/nx-conv-res-theme-9.png',
                '_template' => 'woo_template_sales_count',
                'is_pro'    => true,
            ],
            'res-theme-ten'   => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_conv/nx-conv-res-theme-10.png',
                '_template' => 'woo_template_sales_count',
                'is_pro'    => true,
            ],
            'res-theme-eleven'   => [
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_conv/nx-conv-res-theme-11.png',
                '_template' => 'woo_template_sales_count',
                'is_pro'    => true,
            ],
        ];
        $this->templates = [
            'woo_template_new' => [
                'first_param' => GlobalFields::get_instance()->common_name_fields(),
                'third_param' => [
                    'tag_product_title' => __('Product Title', 'notificationx'),
                ],
                'fourth_param' => [
                    'tag_time' => __('Definite Time', 'notificationx'),
                ],
                '_themes' => [
                    'conversions_theme-one',
                    'conversions_theme-two',
                    'conversions_theme-three',
                    'conversions_theme-four',
                    'conversions_theme-five',
                    'conversions_conv-theme-ten',
                    'conversions_conv-theme-eleven',
                    'woocommerce_sales_theme-one',
                    'woocommerce_sales_theme-two',
                    'woocommerce_sales_theme-three',
                    'woocommerce_sales_theme-four',
                    'woocommerce_sales_theme-five',
                    'woocommerce_sales_conv-theme-ten',
                    'woocommerce_sales_conv-theme-eleven',
                ]
            ],
            'woo_template_sales_count' => [
                'first_param' => GlobalFields::get_instance()->common_name_fields(),
                'third_param' => [
                    'tag_product_title' => __('Product Title', 'notificationx'),
                ],
                'fourth_param' => [
                    // 'tag_time' => __('Definite Time', 'notificationx'),
                ],
                '_themes' => [
                    'conversions_conv-theme-six',
                    'conversions_conv-theme-seven',
                    'conversions_conv-theme-eight',
                    'conversions_conv-theme-nine',
                    'woocommerce_sales_conv-theme-six',
                    'woocommerce_sales_conv-theme-seven',
                    'woocommerce_sales_conv-theme-eight',
                    'woocommerce_sales_conv-theme-nine',
                ]
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
    } 
    
}
