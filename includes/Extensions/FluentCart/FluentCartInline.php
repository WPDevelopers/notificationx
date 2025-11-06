<?php
/**
 * FluentCart Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\FluentCart;

use NotificationX\Core\PostType;
use NotificationX\Core\Rules;
use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\FluentCart\FluentCart;
use NotificationX\Extensions\GlobalFields;

/**
 * FluentCart Extension Class
 */
class FluentCartInline extends FluentCart {
    /**
     * Instance of FluentCartInline
     *
     * @var FluentCartInline
     */
    protected static $instance = null;
    public $priority        = 6;
    public $id              = 'fluentcart_inline';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/fluentcart.png';
    public $doc_link        = 'https://notificationx.com/docs/fluentcart-sales-notifications/';
    public $types           = 'inline';
    public $module          = 'modules_fluentcart';
    public $module_priority = 3;
    public $is_pro          = true;

     /**
     * Get the instance of called class.
     *
     * @return FluentCartInline
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
        parent::__construct();
    }

    public function init_extension()
    {
        $this->title = __('FluentCart', 'notificationx');
        $this->module_title = __('FluentCart', 'notificationx');
        $this->themes = [
            'conv-theme-seven' => array(
                'is_pro'      => true,
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/woo-inline.jpg',
                'image_shape' => 'rounded',
                'inline_location' => [ 'fluentcart_single' ],
                'template'    => [
                    'first_param'         => 'tag_sales_count',
                    'custom_first_param'  => __( '99', 'notificationx' ),
                    'second_param'        => __( 'people purchased', 'notificationx' ),
                    'third_param'         => 'tag_product_title',
                    'custom_third_param'  => ' ',
                    'fourth_param'        => 'tag_7days',
                    'custom_fourth_param' => __( 'in last {{day:7}}', 'notificationx' ),
                ],
            ),
            'stock-theme-one' => array(
                'is_pro'      => true,
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/woo-inline-2.jpg',
                'image_shape' => 'rounded',
                'inline_location' => [ 'fluentcart_single' ],
                'template'    => [
                    // 'first_param'         => 'tag_sales_count',
                    // 'custom_first_param'  => __( 'Someone', 'notificationx' ),
                    'second_param'        => __( 'Only', 'notificationx' ),
                    'third_param'         => 'tag_stock_count',
                    'custom_third_param'  => 10,
                    'fourth_param'        => 'tag_left_in_stock',
                    'custom_fourth_param' => __( 'left in stock', 'notificationx' ),
                    'fifth_param'         => 'tag_order_soon',
                    'custom_fifth_param'  => __( '- order soon.', 'notificationx' ),
                ],
            ),
            'stock-theme-two' => array(
                'is_pro'      => true,
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/woo-inline-3.jpg',
                'image_shape' => 'rounded',
                'inline_location' => [ 'fluentcart_after_cart_item_name' ],
                'template'    => [
                    // 'first_param'         => 'tag_sales_count',
                    // 'custom_first_param'  => __( 'Someone', 'notificationx' ),
                    'second_param'        => __( 'In high demand - only', 'notificationx' ),
                    'third_param'         => 'tag_stock_count',
                    'custom_third_param'  => 10,
                    'fourth_param'        => 'tag_left',
                    'custom_fourth_param' => __( 'left', 'notificationx' ),
                    'fifth_param'         => 'tag_on_our_site',
                    'custom_fifth_param'  => __( 'on our site!', 'notificationx' ),
                ],
            ),
        ];
        $this->templates = [
            'woo_template_sales_count' => [
                'first_param'  => [
                    'tag_sales_count' => __( 'Sales Count', 'notificationx' ),
                ],
                'third_param'  => [
                    'tag_product_title' => __( 'Product Title', 'notificationx' ),
                ],
                'fourth_param' => [
                    'tag_1day'   => __( 'In last 1 day', 'notificationx' ),
                    'tag_7days'  => __( 'In last 7 days', 'notificationx' ),
                    'tag_30days' => __( 'In last 30 days', 'notificationx' ),
                ],
                '_themes'      => [
                    "{$this->id}_conv-theme-seven",
                ],
            ],
            'inline_stock_template'    => [
                'third_param'  => [
                    'tag_stock_count' => __( 'Stock Count', 'notificationx' ),
                ],
                'fourth_param' => [
                    'tag_left_in_stock' => __( 'left in stock', 'notificationx' ),
                    'tag_left' => __( 'left', 'notificationx' ),
                ],
                'fifth_param' => [
                    'tag_order_soon' => __( 'order soon.', 'notificationx' ),
                    'tag_on_our_site' => __( 'on our site!', 'notificationx' ),
                ],
                '_themes'      => [
                    'fluentcart_inline_conv-theme-seven',
                ],
            ],
        ];
    }

     public function doc(){
        /* translators: 
            %1$s: URL to the FluentCart WordPress plugin page, 
            %2$s: URL to the step-by-step documentation for FluentCart integration with NotificationX, 
            %3$s: URL to the NotificationX integration guide for FluentCart 
        */
        return sprintf(__('<p>Make sure that you have the <a target="_blank" href="%1$s">FluentCart WordPress plugin installed & configured</a> to use its campaign and selling data. For detailed guidelines, check out the step-by-step <a target="_blank" href="%2$s">documentation</a>.</p>
        <a target="_blank" href="%3$s">👉 NotificationX Integration with FluentCart</a>', 'notificationx'),
        'https://wordpress.org/plugins/fluent-cart/',
        'https://notificationx.com/docs/fluentcart-growth-alert/',
        'https://notificationx.com/docs/fluentcart-growth-alert/'
        );
    }

}
