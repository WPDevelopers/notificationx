<?php
/**
 * WooCommerce Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\WooCommerce;

use NotificationX\Admin\Settings;
use NotificationX\Core\Modules;
use NotificationX\Core\PostType;
use NotificationX\Core\Rules;
use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\GlobalFields;

/**
 * WooCommerce Extension Class
 */
class WooInline extends WooCommerce {
    /**
     * Instance of WooInline
     *
     * @var WooInline
     */
    protected static $instance = null;
    public $priority        = 5;
    public $id              = 'woo_inline';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/woocommerce.png';
    public $doc_link        = 'https://notificationx.com/docs/woocommerce-sales-notifications/';
    public $types           = 'inline';
    public $module          = 'modules_woocommerce';
    public $module_priority = 3;
    public $class           = '\WooCommerce';
    public $is_pro          = true;

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
        add_filter( 'nx_show_on_exclude', array( $this, 'show_on_exclude' ), 10, 4 );
    }

    public function init_extension()
    {
        
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
        // Google Analytics live-viewer design. Only offered when the Google
        // Analytics module is switched on — the number comes from a connected
        // GA4 property, so without it the design could never render anything.
        if ( Modules::get_instance()->is_enabled( 'modules_google_analytics' ) ) {
            $this->themes['live-viewers'] = array(
                'is_pro'          => true,
                'source'          => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/ga-live-viewers.jpg',
                'image_shape'     => 'rounded',
                'inline_location' => [ 'woocommerce_before_add_to_cart_form' ],
                'template'        => [
                    'first_param'         => 'tag_live_viewers',
                    'second_param'        => __( 'people are viewing', 'notificationx' ),
                    'third_param'         => 'tag_product_title',
                    'custom_third_param'  => ' ',
                    // A real tag rather than `tag_custom`: `custom_fourth_param`
                    // is one text field shared by every design, so seeding it is
                    // not reliable — switching in from the sales-count design can
                    // leave its "in last {{day:7}}" text behind. The tag renders
                    // from `fallback_data()` and cannot go stale; merchants who
                    // want their own wording pick "Custom" in the select, which
                    // seeds from `custom_fourth_param` below.
                    'fourth_param'        => 'tag_right_now',
                    'custom_fourth_param' => __( 'right now', 'notificationx' ),
                ],
            );
        }

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

        if ( Modules::get_instance()->is_enabled( 'modules_google_analytics' ) ) {
            $this->templates['woo_live_viewers_template'] = [
                'first_param' => [
                    'tag_live_viewers' => __( 'Live Product Viewers', 'notificationx' ),
                ],
                'third_param' => [
                    'tag_product_title' => __( 'Product Title', 'notificationx' ),
                ],
                'fourth_param' => [
                    'tag_right_now' => __( 'right now', 'notificationx' ),
                ],
                '_themes'     => [
                    "{$this->id}_live-viewers",
                ],
            ];
        }
    }

    /**
     * The live-viewer design needs a connected GA4 property on top of
     * WooCommerce, so it gets its own message keyed separately from the
     * parent's "install WooCommerce" one and scoped to that theme.
     *
     * @param array $messages
     * @return array
     */
    public function source_error_message( $messages ) {
        $messages = parent::source_error_message( $messages );

        $url         = admin_url( 'admin.php?page=nx-settings&tab=tab-api-integrations#google_analytics_settings_section' );
        $pa_settings = Settings::get_instance()->get( 'settings.nx_pa_settings' );
        $profile     = Settings::get_instance()->get( 'settings.ga_profile' );
        $message     = '';

        if ( empty( $pa_settings ) ) {
            /* translators: %1$s: leading sentence, %2$s: settings URL, %3$s: link text, %4$s: trailing word. */
            $message = sprintf( '%1$s <a href="%2$s" target="_blank">%3$s</a> %4$s.',
                __( 'This design needs live traffic data. Connect your', 'notificationx' ),
                $url,
                __( 'Google Analytics Account', 'notificationx' ),
                __( 'first', 'notificationx' )
            );
        } elseif ( empty( $profile ) || strpos( $profile, 'properties/' ) !== 0 ) {
            // Realtime per-page data only exists on GA4 properties; legacy
            // Universal Analytics views cannot serve this design.
            /* translators: %1$s: leading sentence, %2$s: settings URL, %3$s: link text. */
            $message = sprintf( '%1$s <a href="%2$s" target="_blank">%3$s</a>.',
                __( 'This design needs a Google Analytics 4 property. Select one in', 'notificationx' ),
                $url,
                __( 'API Integrations', 'notificationx' )
            );
        }

        if ( $message ) {
            $messages[ "{$this->id}_ga" ] = [
                'message' => $message,
                'html'    => true,
                'type'    => 'error',
                'rules'   => Rules::includes( 'themes', [ "{$this->id}_live-viewers" ] ),
            ];
        }

        return $messages;
    }

    /**
     * @todo Something
     *
     * @param [type] $exclude
     * @param [type] $settings
     * @return void
     */
    public function show_on_exclude( $exclude, $settings ) {
        if ( $settings['source'] === $this->id ) {
            $woo_location = $settings['inline_location'];
            $hooks        = ['woocommerce_before_add_to_cart_form', 'woocommerce_after_shop_loop_item_title', 'woocommerce_after_shop_loop_item', 'woocommerce_after_cart_item_name'];
            $diff         = array_diff( $hooks, $woo_location );
            if ( count( $diff ) <= count( $hooks ) ) {
                return true;
            }
        }
        return $exclude;
    }

    public function content_fields($fields){
        return $fields;
    }

}
