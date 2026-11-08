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
     * Free's own list is empty until each theme family passes its screenshots.
     * Add-ons add their own theme keys (as saved in `themes`) through the
     * `nx_card_size_themes` filter.
     *
     * @return string[]
     */
    public static function supported_themes() {
        $themes = [];

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
