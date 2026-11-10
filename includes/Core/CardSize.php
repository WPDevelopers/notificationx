<?php
/**
 * Card size presets: Compact, Default and Large.
 *
 * @package NotificationX\Core
 */

namespace NotificationX\Core;

use NotificationX\GetInstance;

/**
 * The `card_size` notification setting and its server-side guard.
 *
 * A notification can show its card at Compact (0.8) or Large (1.2) instead of
 * Default. The setting is stored with the other settings; notifications saved
 * before it existed have no key, which means Default. Nothing is migrated.
 *
 * Only themes in the supported list offer the setting. normalize() removes the
 * value from everything the frontend receives unless it is `compact` or
 * `large` and the theme is supported, so Default output never changes, and
 * removing a theme from the list returns its cards to Default. Verified Cards
 * themes (keys containing `_vc-`) are never supported.
 *
 * @method static CardSize get_instance($args = null)
 * @since x.x.x
 */
class CardSize {
    /**
     * Instance of CardSize
     *
     * @var CardSize
     */
    use GetInstance;

    /**
     * The setting's name in the notification data.
     */
    const FIELD = 'card_size';

    /**
     * The value for a card at its normal size.
     */
    const DEFAULT_VALUE = 'default';

    /**
     * The scale of each non-default preset (the frontend's `--nx-scale`).
     */
    const SCALES = [
        'compact' => 0.8,
        'large'   => 1.2,
    ];

    /**
     * Free's own themes that offer the setting, family by family, each added
     * once its cards pass the Compact and Large checks.
     *
     * Sales: the free themes of the Sales Notification (`conversions`) and
     * WooCommerce (`woocommerce_sales`) types. Comments: the free themes of
     * the Comments type. Reviews: the six layouts of each free review source
     * (WordPress.org `reviews_`, WooCommerce `woo_reviews_`, ReviewX `reviewx_`,
     * the WooCommerce type's Reviews `woocommerce_sales_reviews_`). The
     * Pro-locked ones (and Google Reviews) are left to add-ons
     * (`nx_card_size_themes`).
     */
    const FREE_THEMES = [
        'conversions_theme-one',
        'conversions_theme-two',
        'conversions_theme-three',
        'conversions_theme-five',
        'conversions_conv-theme-twelve',
        'conversions_conv-theme-thirteen',
        'conversions_conv-theme-fifteen',
        'woocommerce_sales_theme-one',
        'woocommerce_sales_theme-two',
        'woocommerce_sales_theme-three',
        'woocommerce_sales_theme-five',
        'woocommerce_sales_conv-theme-twelve',
        'woocommerce_sales_conv-theme-thirteen',
        'woocommerce_sales_conv-theme-fifteen',
        'comments_theme-one',
        'comments_theme-two',
        'comments_theme-three',
        'comments_theme-six-free',
        'comments_theme-seven-free',
        'comments_theme-eight-free',
        'reviews_total-rated',
        'reviews_reviewed',
        'reviews_review_saying',
        'reviews_review-comment',
        'reviews_review-comment-2',
        'reviews_review-comment-3',
        'woo_reviews_total-rated',
        'woo_reviews_reviewed',
        'woo_reviews_review_saying',
        'woo_reviews_review-comment',
        'woo_reviews_review-comment-2',
        'woo_reviews_review-comment-3',
        'reviewx_total-rated',
        'reviewx_reviewed',
        'reviewx_review_saying',
        'reviewx_review-comment',
        'reviewx_review-comment-2',
        'reviewx_review-comment-3',
        'woocommerce_sales_reviews_total-rated',
        'woocommerce_sales_reviews_reviewed',
        'woocommerce_sales_reviews_review_saying',
        'woocommerce_sales_reviews_review-comment',
        'woocommerce_sales_reviews_review-comment-2',
        'woocommerce_sales_reviews_review-comment-3',
    ];

    /**
     * Registers the guard.
     */
    public function __construct() {
        // Before FrontEnd::filtered_post() (9999), on every settings array the frontend receives.
        add_filter( 'nx_filtered_post', [ $this, 'filtered_post' ], 10, 2 );
    }

    /**
     * The setting's values, in builder order.
     *
     * @return string[]
     */
    public static function values() {
        return [ 'compact', self::DEFAULT_VALUE, 'large' ];
    }

    /**
     * The theme keys that offer the setting.
     *
     * Free's own list (FREE_THEMES) grows family by family as each passes its
     * screenshots. Add-ons add their own theme keys (as saved in `themes`)
     * through the `nx_card_size_themes` filter.
     *
     * @return string[]
     */
    public static function supported_themes() {
        $themes = self::FREE_THEMES;

        /**
         * Filters the theme keys that offer the card size setting.
         *
         * Add a theme only after its cards look right at Compact and Large.
         * Verified Cards keys (containing `_vc-`) are ignored.
         *
         * @since x.x.x
         *
         * @param string[] $themes Theme keys as saved in `themes`, e.g. 'conversions_theme-one'.
         */
        $filtered = apply_filters( 'nx_card_size_themes', $themes );
        if ( ! is_array( $filtered ) ) {
            return $themes;
        }

        $supported = [];
        foreach ( $filtered as $theme ) {
            if ( is_string( $theme ) && '' !== $theme && false === strpos( $theme, '_vc-' ) && ! in_array( $theme, $supported, true ) ) {
                $supported[] = $theme;
            }
        }
        return $supported;
    }

    /**
     * Whether a theme key offers the setting.
     *
     * @param mixed $theme The saved `themes` value.
     * @return bool
     */
    public static function is_supported( $theme ) {
        return is_string( $theme ) && in_array( $theme, self::supported_themes(), true );
    }

    /**
     * Keeps `card_size` only when it is `compact` or `large` for a supported
     * theme; otherwise removes it, so the frontend renders Default.
     *
     * @param mixed $settings A notification's settings.
     * @return mixed The settings, without `card_size` unless it applies.
     */
    public static function normalize( $settings ) {
        if ( ! is_array( $settings ) || ! array_key_exists( self::FIELD, $settings ) ) {
            return $settings;
        }

        $value = $settings[ self::FIELD ];
        $theme = isset( $settings['themes'] ) ? $settings['themes'] : null;
        if ( is_string( $value ) && isset( self::SCALES[ $value ] ) && self::is_supported( $theme ) ) {
            return $settings;
        }

        unset( $settings[ self::FIELD ] );
        return $settings;
    }

    /**
     * `nx_filtered_post`: the guard for the frontend, cross-domain notices and shortcodes.
     *
     * @param mixed $post   The notification's settings.
     * @param mixed $params The request parameters.
     * @return mixed
     */
    public function filtered_post( $post, $params = null ) {
        return self::normalize( $post );
    }
}
