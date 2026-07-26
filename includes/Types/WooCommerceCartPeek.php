<?php

/**
 * Cart Peek Type
 *
 * @package NotificationX\Types
 */

namespace NotificationX\Types;

use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;

/**
 * Cart Peek Type — Conversions-family feature card that surfaces the live
 * count of shoppers who currently have a product in cart. Pro-only, WooCommerce
 * module. Sits next to Growth Alert 🚀 (Inline) in the Notification Type grid.
 *
 * Design note: Cart Peek reuses the existing WooCommerce "Sales" conversion
 * theme styling so it ships with polished designs and zero frontend rebuild.
 * The theme keys declared here (`woocommerce_cart_peek_*`) are remapped to the
 * matching `woocommerce_sales_*` classes at render time by the Pro extension
 * (see WooCommerceCartPeek::remap_theme()), so the compiled Sales CSS/React
 * renders them. Theme grid previews reuse the Sales conversion preview images.
 *
 * @method static WooCommerceCartPeek get_instance($args = null)
 */
class WooCommerceCartPeek extends Types {
    /**
     * Instance of Admin
     *
     * @var Admin
     */
    use GetInstance;
    public $priority       = 51;
    public $is_pro         = true;
    public $themes         = [];
    public $res_themes     = [];
    public $module         = ['modules_woocommerce'];
    public $id             = 'woocommerce_cart_peek';
    public $default_source = 'woocommerce_cart_peek';
    public $default_theme  = 'woocommerce_cart_peek_conv-theme-fourteen';
    public $link_type      = 'product_page';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
    }

    public function init() {
        parent::init();
        $this->title           = __('Cart Peek 🛒', 'notificationx');
        $this->dashboard_title = __('Cart Peek', 'notificationx');

        // Default param selection for each theme's text rows.
        //   row 1 (first_param)  = tag_cart_peek_count -> "N shoppers have this in their cart"
        //   row 2 (third_param)  = tag_product_title   -> the product name
        //   row 3 (fourth_param) = tag_time
        $common_fields = [
            'first_param'         => 'tag_cart_peek_count',
            'custom_first_param'  => __('A few shoppers have this in their cart', 'notificationx'),
            'second_param'        => '',
            'third_param'         => 'tag_product_title',
            'custom_third_param'  => __('this product', 'notificationx'),
            'fourth_param'        => 'tag_time',
            'custom_fourth_param' => __('right now', 'notificationx'),
        ];

        // Preview-image base URL + an mtime cache-buster, so regenerated preview
        // artwork is picked up without a hard refresh (and never served stale
        // from the browser cache at the same filename).
        $cp  = NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/cart-peek/';
        $cpv = '?ver=' . ( @filemtime( NOTIFICATIONX_ASSETS_PATH . 'admin/images/extensions/themes/cart-peek/cp-14.png' ) ?: NOTIFICATIONX_VERSION );

        // Desktop themes — reuse the Sales conversion + sales-count card designs.
        // The count-card themes (fourteen/sixteen) read best for a "N people have
        // this in their cart" message, so they lead the grid.
        $this->themes = [
            'conv-theme-fourteen' => [
                'source'      => $cp . 'cp-14.png' . $cpv,
                'image_shape' => 'rounded',
                'template'    => $common_fields,
                'defaults'    => [
                    'link_button'      => true,
                    'link_button_text' => __('View product', 'notificationx'),
                ],
            ],
            'conv-theme-sixteen' => [
                'source'      => $cp . 'cp-16.png' . $cpv,
                'image_shape' => 'rounded',
                'template'    => $common_fields,
                'defaults'    => [
                    'link_button'      => true,
                    'link_button_text' => __('View product', 'notificationx'),
                ],
            ],
            'theme-one' => [
                'source'      => $cp . 'cp-2.png' . $cpv,
                'image_shape' => 'square',
                'template'    => $common_fields,
            ],
            'theme-two' => [
                'source'      => $cp . 'cp-1.png' . $cpv,
                'image_shape' => 'square',
                'template'    => $common_fields,
            ],
            'theme-three' => [
                'source'      => $cp . 'cp-3.png' . $cpv,
                'image_shape' => 'square',
                'template'    => $common_fields,
            ],
            'conv-theme-twelve' => [
                'source'      => $cp . 'cp-12.png' . $cpv,
                'image_shape' => 'circle',
                'template'    => $common_fields,
                'defaults'    => [
                    'link_button'      => true,
                    'link_button_text' => __('View product', 'notificationx'),
                ],
            ],
        ];

        // Mobile themes.
        $this->res_themes = [
            'res-theme-nine' => [
                'source'    => $cp . 'cp-res-9.png' . $cpv,
                '_template' => 'cart_peek_count_template',
            ],
            'res-theme-one' => [
                'source'    => $cp . 'cp-res-1.png' . $cpv,
                '_template' => 'cart_peek_template',
            ],
            'res-theme-two' => [
                'source'    => $cp . 'cp-res-2.png' . $cpv,
                '_template' => 'cart_peek_template',
            ],
        ];

        // Builder param dropdowns per template. `_themes` lists the full theme
        // keys (type-id prefixed) that use each template.
        $this->templates = [
            'cart_peek_count_template' => [
                'first_param'  => ['tag_cart_peek_count' => __('Cart Count', 'notificationx')],
                'third_param'  => ['tag_product_title'   => __('Product Title', 'notificationx')],
                'fourth_param' => ['tag_time'            => __('Definite Time', 'notificationx')],
                '_themes'      => [
                    'woocommerce_cart_peek_conv-theme-fourteen',
                    'woocommerce_cart_peek_conv-theme-sixteen',
                ],
            ],
            'cart_peek_template' => [
                'first_param'  => ['tag_cart_peek_count' => __('Cart Count', 'notificationx')],
                'third_param'  => ['tag_product_title'   => __('Product Title', 'notificationx')],
                'fourth_param' => ['tag_time'            => __('Definite Time', 'notificationx')],
                '_themes'      => [
                    'woocommerce_cart_peek_theme-one',
                    'woocommerce_cart_peek_theme-two',
                    'woocommerce_cart_peek_theme-three',
                    'woocommerce_cart_peek_conv-theme-twelve',
                    'woocommerce_cart_peek_res-theme-two',
                ],
            ],
        ];

        $this->popup = [
            "denyButtonText"    => __("<a href='https://notificationx.com/docs/cart-peek/' target='_blank'>More Info</a>", "notificationx"),
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            // phpcs:disable PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- False positive for this context: remote documentation/pricing links in admin help text, not offloaded plugin assets.
            "html"              => __('
                <span>Show the live count of shoppers who currently have a product in their cart to spark urgency and boost conversions.</span>
            ', 'notificationx')
            // phpcs:enable PluginCheck.CodeAnalysis.Offloading.OffloadedContent
        ];
    }

    /**
     * Hooked to nx_before_metabox_load. Adds the Cart Peek-only "Cart
     * Confirmation Popup" controls to the Customize tab.
     *
     * @return void
     */
    public function init_fields() {
        parent::init_fields();
        add_filter( 'nx_customize_fields', array( $this, 'cart_confirm_fields' ), 20 );
    }

    /**
     * Builder fields for the shopper-facing cart confirmation popup — a small
     * "Continue shopping / Go to checkout" prompt shown to the shopper right
     * after they add a product to the cart (behaviour lives in the Pro
     * extension). Scoped to the Cart Peek type only.
     *
     * @param array $fields
     * @return array
     */
    public function cart_confirm_fields( $fields ) {
        $fields[] = [
            'label'    => __( 'Cart Confirmation Popup', 'notificationx' ),
            'name'     => 'cart_confirm_section',
            'type'     => 'section',
            'priority' => 12,
            'rules'    => Rules::is( 'type', 'woocommerce_cart_peek' ),
            'fields'   => [
                [
                    'label'       => __( 'Cart Confirmation Popup', 'notificationx' ),
                    'name'        => 'cart_confirm_enable',
                    'type'        => 'toggle',
                    'default'     => false,
                    'is_pro'      => true,
                    'description' => __( 'Show the shopper a "Continue shopping / Go to checkout" popup right after they add a product to the cart.', 'notificationx' ),
                ],
                [
                    'label'   => __( 'Popup Heading', 'notificationx' ),
                    'name'    => 'cart_confirm_heading',
                    'type'    => 'text',
                    'default' => __( 'Leaving so soon?', 'notificationx' ),
                    'rules'   => Rules::is( 'cart_confirm_enable', true ),
                ],
                [
                    'label'   => __( 'Continue Shopping Button', 'notificationx' ),
                    'name'    => 'cart_confirm_continue_text',
                    'type'    => 'text',
                    'default' => __( 'Continue shopping', 'notificationx' ),
                    'rules'   => Rules::is( 'cart_confirm_enable', true ),
                ],
                [
                    'label'   => __( 'Checkout Button', 'notificationx' ),
                    'name'    => 'cart_confirm_checkout_text',
                    'type'    => 'text',
                    'default' => __( 'Go to checkout', 'notificationx' ),
                    'rules'   => Rules::is( 'cart_confirm_enable', true ),
                ],
                [
                    'label'       => __( 'Multiple Products Display', 'notificationx' ),
                    'name'        => 'cart_confirm_multi_display',
                    'type'        => 'select',
                    'default'     => 'count',
                    'description' => __( 'How to show the message when the cart has more than one product.', 'notificationx' ),
                    'options'     => GlobalFields::get_instance()->normalize_fields( [
                        'count' => __( 'Show count — e.g. "Product A and 2 more products"', 'notificationx' ),
                        'list'  => __( 'List product names — e.g. "Product A, Product B, Product C"', 'notificationx' ),
                        'total' => __( 'Show total items — e.g. "3 items"', 'notificationx' ),
                    ] ),
                    'rules'       => Rules::is( 'cart_confirm_enable', true ),
                ],
            ],
        ];
        return $fields;
    }

    /**
     * Bound to `nx_can_entry_{$id}` by the WooCommerce base extension (which
     * assumes the type exposes this, like Sales/Conversions do). Cart Peek does
     * not offer product-exclude controls, so every entry passes through.
     *
     * @param bool  $result
     * @param array $entry
     * @param array $settings
     * @return bool
     */
    public function nx_can_entry( $result, $entry, $settings ) {
        return $result;
    }

    /**
     * Bound to `nx_filtered_data_{$id}` by the WooCommerce Pro base extension.
     * No product-exclude filtering for Cart Peek — return data untouched.
     *
     * @param array $data
     * @param array $settings
     * @return array
     */
    public function show_exclude_product( $data, $settings ) {
        return $data;
    }
}
