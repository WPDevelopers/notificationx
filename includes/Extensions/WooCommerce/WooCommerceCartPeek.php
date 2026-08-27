<?php
/**
 * WooCommerce Cart Peek Extension (Free stub).
 *
 * The Free plugin registers the Cart Peek source so the Add New modal can
 * render it as a locked / Pro-badged card. All behaviour lives in the Pro
 * override at `NotificationXPro\Extensions\WooCommerce\WooCommerceCartPeek`;
 * see WooCommerceSales.php for the same namespace-swap pattern.
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\WooCommerce;

/**
 * WooCommerce Cart Peek Extension Class
 * @method static WooCommerceCartPeek get_instance($args = null)
 */
class WooCommerceCartPeek extends WooCommerce {
    /**
     * Instance of WooCommerceCartPeek
     *
     * @var WooCommerceCartPeek
     */
    protected static $instance = null;
    public $priority        = 6;
    public $id              = 'woocommerce_cart_peek';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/woocommerce.png';
    public $doc_link        = 'https://notificationx.com/docs/how-to-create-a-cart-peek-notification/';
    public $types           = 'woocommerce_cart_peek';
    public $module          = 'modules_woocommerce';
    public $module_priority = 3;
    public $class           = '\WooCommerce';
    public $default_theme   = 'woocommerce_cart_peek_conv-theme-fourteen';
    public $is_pro          = true;

    /**
     * Get the instance of called class.
     *
     * @return WooCommerceCartPeek
     */
    public static function get_instance($args = null) {
        if ( is_null( static::$instance ) || ! static::$instance instanceof self ) {
            $class = __CLASS__;
            if ( strpos( $class, 'NotificationX\\' ) === 0 ) {
                $pro_class = str_replace( 'NotificationX\\', 'NotificationXPro\\', $class );
                if ( class_exists( $pro_class ) ) {
                    $class = $pro_class;
                }
            }

            if ( ! empty( $args ) ) {
                static::$instance = new $class( $args );
            } else {
                static::$instance = new $class;
            }
        }
        return static::$instance;
    }

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
    }

    public function init_extension() {
        // The source is the WooCommerce store (shown with the WooCommerce icon in
        // the Source tab); the "Cart Peek" identity lives on the notification type.
        $this->title        = __( 'WooCommerce', 'notificationx' );
        $this->module_title = __( 'Cart Peek', 'notificationx' );
    }

    public function doc() {
        /* translators: %1$s: WooCommerce install link, %2$s: Cart Peek documentation link */
        return sprintf(
            __( '<p>Show shoppers how many others have this product in their cart right now — a live social-proof signal that fires at the highest-value hesitation moment on the PDP.</p>
            <p>Requires an active NotificationX Pro licence and <a target="_blank" href="%1$s">WooCommerce installed &amp; activated</a>.</p>
            <p>📚 <a target="_blank" href="%2$s">Documentation</a></p>', 'notificationx' ),
            'https://wordpress.org/plugins/woocommerce/',
            'https://notificationx.com/docs/how-to-create-a-cart-peek-notification/'
        );
    }
}
