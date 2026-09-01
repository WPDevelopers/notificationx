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

        // Cart Peek's message reads "N shoppers have this in their cart" — it has
        // no purchase verb. The second text slot reuses the shared content field
        // whose field-level default ("recently purchased") re-fills the emptied
        // value in the builder, which then renders on the front end and looks
        // wrong. Keep that slot blank by default (only when it is still the
        // inherited sales verb; a verb the merchant typed themselves is kept),
        // enforced on both save and the builder preview.
        add_filter( "nx_save_post_{$this->default_source}", [ $this, 'blank_default_verb_post' ], 20, 1 );
        add_filter( "nx_preview_settings_{$this->default_source}", [ $this, 'blank_default_verb_settings' ], 20, 1 );
        // On read: an existing notification saved without the field (Cart Peek
        // does not expose it) has no second_param at all, so the front end falls
        // back to the remapped Sales design's "just purchased" default. Pin it to
        // empty whenever a Cart Peek record is read so the front end renders no
        // verb.
        add_filter( "nx_get_post_{$this->default_source}", [ $this, 'blank_default_verb_settings' ], 20, 1 );
        // At front-end render (after the theme is remapped to the Sales design).
        add_filter( 'nx_filtered_post', [ $this, 'blank_default_verb_filtered_post' ], 20, 1 );

        // Hide the free-text "verb" (second_param) field from the Content tab for
        // Cart Peek ONLY. Cart Peek's message is count / product / time — it has no
        // verb slot in its template, so anything typed there never renders and only
        // confuses. Every other notification type still shows the field.
        add_filter( 'nx_notification_template', [ $this, 'hide_verb_field' ], 20, 1 );

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
            "denyButtonText"    => __("<a href='https://notificationx.com/docs/how-to-create-a-cart-peek-notification/' target='_blank'>More Info</a>", "notificationx"),
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            // phpcs:disable PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- False positive for this context: remote documentation/pricing links in admin help text, not offloaded plugin assets.
            "html"              => __('
                <span>Show the live count of shoppers who currently have a product in their cart to spark urgency and boost conversions.</span>
            ', 'notificationx')
            // phpcs:enable PluginCheck.CodeAnalysis.Offloading.OffloadedContent
        ];
    }

    /**
     * The inherited sales verbs that do not belong on a Cart Peek message.
     * Anything else in the slot is treated as merchant-authored and kept.
     *
     * @param string $value
     * @return bool
     */
    protected function is_inherited_verb( $value ) {
        return in_array(
            trim( (string) $value ),
            [ '', __( 'recently purchased', 'notificationx' ), __( 'just purchased', 'notificationx' ) ],
            true
        );
    }

    /**
     * True when the first row still holds an inherited Sales default rather than
     * Cart Peek's own headline. Every Cart Peek theme uses `tag_cart_peek_count`
     * for row 1 ("N shoppers have this in their cart") — the builder offers no
     * other option — so a `tag_name` (or empty) value can only be a default that
     * leaked in from the Sales schema and must be normalised back to the count.
     *
     * @param string $value
     * @return bool
     */
    protected function is_inherited_first_param( $value ) {
        return in_array( trim( (string) $value ), [ '', 'tag_name' ], true );
    }

    /**
     * Blank the "verb" slot when it still holds the inherited Sales default
     * (or is unset). Covers BOTH the flat `second_param` and the nested
     * `notification-template.second_param` — the front end composes the message
     * from the nested copy, so both must be cleared. Merchant-authored text is
     * kept.
     *
     * @param array $arr
     * @return array
     */
    protected function blank_verb( $arr ) {
        if ( ! is_array( $arr ) ) {
            return $arr;
        }
        if ( ! isset( $arr['second_param'] ) || $this->is_inherited_verb( $arr['second_param'] ) ) {
            $arr['second_param'] = '';
        }
        if ( isset( $arr['notification-template'] ) && is_array( $arr['notification-template'] )
            && ( ! isset( $arr['notification-template']['second_param'] ) || $this->is_inherited_verb( $arr['notification-template']['second_param'] ) ) ) {
            $arr['notification-template']['second_param'] = '';
        }
        // Force row 1 back to the live-count headline when it still carries a
        // leaked Sales default (`tag_name`/empty). The default Cart Peek theme
        // (conv-theme-fourteen) can otherwise save `first_param => tag_name`,
        // which renders "Someone <product>" instead of the shopper count. Covers
        // both the flat and the nested (front-end-composed) copies.
        if ( ! isset( $arr['first_param'] ) || $this->is_inherited_first_param( $arr['first_param'] ) ) {
            $arr['first_param'] = 'tag_cart_peek_count';
        }
        if ( isset( $arr['notification-template'] ) && is_array( $arr['notification-template'] )
            && ( ! isset( $arr['notification-template']['first_param'] ) || $this->is_inherited_first_param( $arr['notification-template']['first_param'] ) ) ) {
            $arr['notification-template']['first_param'] = 'tag_cart_peek_count';
        }
        return $arr;
    }

    /**
     * On save. Wired to nx_save_post_{source}; the params live in $post['data'].
     *
     * @param array $post
     * @return array
     */
    public function blank_default_verb_post( $post ) {
        if ( isset( $post['data'] ) && is_array( $post['data'] ) ) {
            $post['data'] = $this->blank_verb( $post['data'] );
        }
        return $post;
    }

    /**
     * Builder preview + every read of a Cart Peek record
     * (nx_preview_settings_{source} / nx_get_post_{source}).
     *
     * @param array $settings
     * @return array
     */
    public function blank_default_verb_settings( $settings ) {
        return $this->blank_verb( $settings );
    }

    /**
     * Front-end render filter (nx_filtered_post fires for every source, so gate
     * on this one). This is the path the live popup reads.
     *
     * @param array $post
     * @return array
     */
    public function blank_default_verb_filtered_post( $post ) {
        if ( isset( $post['source'] ) && $this->default_source === $post['source'] ) {
            $post = $this->blank_verb( $post );
        }
        return $post;
    }

    /**
     * Hooked to nx_notification_template. Hides the shared free-text `second_param`
     * ("verb") field for Cart Peek only, via a source rule the builder evaluates
     * client-side — every other source keeps the field. Cart Peek's template does
     * not render second_param, so leaving it editable only invited confusion.
     *
     * @param array $fields notification-template group sub-fields.
     * @return array
     */
    public function hide_verb_field( $fields ) {
        if ( isset( $fields['second_param'] ) ) {
            // Show only when the source is NOT Cart Peek (i.e. hide for Cart Peek).
            $fields['second_param']['rules'] = Rules::is( 'source', $this->default_source, true );
        }
        return $fields;
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
