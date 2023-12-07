<?php
/**
 * WooCommerce Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\WooCommerce;

use NotificationX\Admin\Entries;
use NotificationX\Core\Helper;
use NotificationX\Core\PostType;
use NotificationX\Core\Rules;
use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\GlobalFields;

/**
 * WooCommerce Extension Class
 * @method static WooCommerce get_instance($args = null)
 */
class WooCommerceSales extends WooCommerce {
    /**
     * Instance of WooInline
     *
     * @var WooCommerceSales
     */
    protected static $instance = null;
    public $priority        = 5;
    public $id              = 'woocommerce_sales';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/woocommerce.png';
    public $doc_link        = 'https://notificationx.com/docs/woocommerce-sales-notifications/';
    public $types           = 'woocommerce_sales';
    public $module          = 'modules_woocommerce_sales';
    public $module_priority = 3;
    public $class           = '\WooCommerce';
    public $default_theme   = 'woocommerce_sales_theme-one';
    public $wpml_included   = [
                                'sales_count',
                              ];

    /**
     * Get the instance of called class.
     *
     * @return WooCommerce
    */
    public static function get_instance($args = null){
        if ( is_null( static::$instance ) || ! static::$instance instanceof self ) {
            $class = __CLASS__;
            if(strpos($class, "NotificationX\\") === 0){
                $pro_class = str_replace("NotificationX\\", "NotificationXPro\\", $class);
                if(class_exists($pro_class)){
                    $class = $pro_class;
                }
            }

            if(!empty($args)){
                static::$instance = new $class($args);
            }
            else{
                static::$instance = new $class;
            }
        }
        return static::$instance;
    }
    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('WooCommerce Sales', 'notificationx');
        $this->module_title = __('WooCommerce Sales', 'notificationx');
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
            'theme-four' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/nx-conv-theme-four.png',
                'image_shape' => 'circle',
                'template'  => $common_fields,
            ),
            'theme-five' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/nx-conv-theme-five.png',
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
                ],
                'template'  => $common_fields,
            ),
            'conv-theme-eleven' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/nx-conv-theme-5.png',
                'image_shape' => 'rounded',
                'defaults'     => [
                    'link_button'   => true,
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
                    'woocommerce_sales_conv-theme-six',
                    'woocommerce_sales_conv-theme-seven',
                    'woocommerce_sales_conv-theme-eight',
                    'woocommerce_sales_conv-theme-nine',
                ]
            ],
        ];
        parent::__construct();
    }

}
