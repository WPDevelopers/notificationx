<?php
/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Types;

use NotificationX\Core\Rules;
use NotificationX\Types\Traits\Conversions;
use NotificationX\Types\Traits\Reviews;
use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;
use NotificationX\NotificationX;

/**
 * Extension Abstract for all Extension.
 * @method static WooCommerce get_instance($args = null)
 */
class WooCommerceSales extends Types {
    /**
     * Instance of Admin
     *
     * @var Admin
     */
	use GetInstance;
    use Reviews;
    use Conversions;

    // colored_themes
    public $priority = 5;
    public $themes = [];
    public $res_themes = [];
    public $module = [
        'modules_woocommerce',
        'modules_woocommerce_sales_reviews',
        'modules_woocommerce_sales_inline',
    ];
    
    public $map_dependency = [];

    public $default_source    = 'woocommerce_sales';
    // public $default_theme = 'woocommerce_theme-one';
    public $link_type = 'product_page';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
        $this->id = 'woocommerce_sales';
    }

    public function init() {
        parent::init();
        $this->title = __('WooCommerce', 'notificationx');

        $is_pro = ! NotificationX::is_pro();
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
        add_filter("nx_filtered_entry_{$this->id}", array($this, 'conversion_data'), 11, 2);
    }
    
    /**
     * Hooked to nx_before_metabox_load action.
     *
     * @return void
     */
    public function init_fields() {
        parent::init_fields();
        add_filter('nx_notification_template', [$this, 'review_templates'], 7);
        add_filter('nx_content_trim_length_dependency', [$this, 'content_trim_length_dependency']);
    }

}
