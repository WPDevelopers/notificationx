<?php
  /**
 * WooCommerce Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\WooCommerce;

  /**
 * WooCommerce Extension Class
 * @method static WooCommerce get_instance($args = null)
 */
class WooCommerceSalesInline extends WooInline {

    /**
     * Instance of WooInline
     *
     * @var WooInline
    */
    protected static $instance = null;
    public    $priority        = 15;
    public    $id              = 'woocommerce_sales_inline';
    public    $img             = '';
    public    $doc_link        = 'https://notificationx.com/docs/woocommerce-growth-alerts/';
    public    $types           = 'woocommerce_sales';
    public    $module          = 'modules_woocommerce';
    public    $module_priority = 3;
    public    $class           = '\WooCommerce';
    public    $is_pro          = true;

    /**
     * Get the instance of called class.
     *
     * @return WooInline
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
        $this->title        = __('Growth Alert', 'notificationx');
        $this->module_title = __('Growth Alert', 'notificationx');
        $this->themes = [
            'conv-theme-seven' => array(
                'is_pro'      => true,
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/woo-inline.jpg',
                'image_shape' => 'rounded',
                'inline_location' => [ 'woocommerce_before_add_to_cart_form' ],
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
                'inline_location' => [ 'woocommerce_before_add_to_cart_form' ],
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
                'inline_location' => [ 'woocommerce_after_cart_item_name' ],
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
                    "{$this->id}_stock-theme-one",
                    "{$this->id}_stock-theme-two",
                ],
            ],
        ];
        $this->popup = [
            "denyButtonText" => __("<a href='https://notificationx.com/growth-alert/' target='_blank'>More Info</a>", "notificationx"),
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            "html"=> __('
                <span>Highlight your sales, low stock updates with inline growth alert to boost sales</span>
                <iframe id="email_subscription_video" type="text/html" allowfullscreen width="450" height="235"
                src="https://www.youtube.com/embed/vXMtBPvizDw">
                </iframe>
            ', 'notificationx')
        ];
    }

    public function doc(){
        return sprintf(__('<p>Make sure that you have <a target="_blank" href="%1$s">WooCommerce installed & activated</a> to use this campaign. For further assistance, check out our step by step <a target="_blank" href="%2$s">documentation</a>.</p>
		<p>🎦 <a href="%3$s" target="_blank">Watch video tutorial</a> to learn quickly</p>
		<p>⭐ NotificationX Integration with WooCommerce</p>
		<p><strong>Recommended Blog:</strong></p>
		<p>🔥 Why NotificationX is The <a target="_blank" href="%4$s">Best FOMO and Social Proof Plugin</a> for WooCommerce?</p>
		<p>🚀 How to <a target="_blank" href="%5$s">boost WooCommerce Sales</a> Using NotificationX</p>', 'notificationx'),
        'https://wordpress.org/plugins/woocommerce/',
        'https://notificationx.com/docs/woocommerce-growth-alerts/',
        'https://www.youtube.com/watch?v=dVthd36hJ-E&t=1s',
        'https://notificationx.com/integrations/woocommerce/',
        'https://notificationx.com/blog/best-fomo-and-social-proof-plugin-for-woocommerce/'
        );
    }

}
