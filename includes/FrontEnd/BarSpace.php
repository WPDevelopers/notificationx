<?php

namespace NotificationX\FrontEnd;

use NotificationX\Core\PostType;
use NotificationX\GetInstance;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Reserves room for a top notification bar before it mounts.
 *
 * The bar is fetched over REST and rendered after page load, then pushes the
 * page down with body padding — a layout shift on every page view. Its height
 * is only known in the browser, so the frontend reports the rendered height per
 * viewport width (`report_height`), and later page loads print it as body
 * padding in <head> (`print_reserve`), so the page starts at its final position.
 *
 * @method static BarSpace get_instance($args = null)
 */
class BarSpace {
    use GetInstance;

    const OPTION     = 'notificationx_bar_heights';
    const MAX_HEIGHT = 400;
    const THROTTLE   = 10 * MINUTE_IN_SECONDS;

    /**
     * Viewport buckets as [ key => max width (exclusive) ]. Text wrapping
     * changes the bar's height with width, so phones are split in two and
     * wide desktops get their own bucket. `device` maps to the bar's
     * mobile/tablet/desktop visibility (see getDeviceType in useNotificationX).
     */
    const BUCKETS = [
        'xs' => [ 'max' => 480, 'device' => 'mobile' ],
        'sm' => [ 'max' => 768, 'device' => 'mobile' ],
        'md' => [ 'max' => 1024, 'device' => 'tablet' ],
        'lg' => [ 'max' => 1440, 'device' => 'desktop' ],
        'xl' => [ 'max' => 0, 'device' => 'desktop' ],
    ];

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route(
            'notificationx/v1',
            '/bar-height',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'report_height' ],
                // Public, like analytics: reported by visitors' browsers.
                'permission_callback' => '__return_true',
                'args'                => [
                    'nx_id'  => [ 'required' => true, 'type' => 'integer', 'minimum' => 1 ],
                    'width'  => [ 'required' => true, 'type' => 'integer', 'minimum' => 200, 'maximum' => 10000 ],
                    'height' => [ 'required' => true, 'type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_HEIGHT ],
                ],
            ]
        );
    }

    /**
     * Whether a bar pushes the page down from the top as soon as it loads —
     * the only case where reserving space removes a shift instead of adding one.
     *
     * @param array $settings Notification settings.
     * @return bool
     */
    public static function reserves_space( $settings ) {
        if ( empty( $settings['enabled'] ) || ( $settings['source'] ?? '' ) !== 'press_bar' ) {
            return false;
        }
        // `bottom_left` is rendered as a top bar (FrontEnd::get_notifications_data).
        if ( ! in_array( $settings['position'] ?? 'top', [ 'top', 'bottom_left' ], true ) ) {
            return false;
        }
        if ( ! empty( $settings['pressbar_body'] ) || ( $settings['appear_condition'] ?? '' ) === 'on_scroll' ) {
            return false;
        }
        return empty( $settings['initial_delay'] ) || (float) $settings['initial_delay'] <= 0;
    }

    /**
     * @param int $width Viewport width.
     * @return string Bucket key.
     */
    public static function bucket( $width ) {
        foreach ( self::BUCKETS as $key => $bucket ) {
            if ( ! $bucket['max'] || $width < $bucket['max'] ) {
                return $key;
            }
        }
        return 'xl';
    }

    public function report_height( WP_REST_Request $request ) {
        $nx_id  = absint( $request['nx_id'] );
        $bucket = self::bucket( (int) $request['width'] );
        $height = (int) $request['height'];

        $posts    = PostType::get_instance()->get_posts_by_ids( [ $nx_id ], 'press_bar' );
        $settings = $posts ? reset( $posts ) : null;
        if ( ! $settings || ! self::reserves_space( $settings ) ) {
            return new WP_REST_Response( [ 'saved' => false ], 200 );
        }

        $throttle_key = "nx_bar_height_{$nx_id}_{$bucket}";
        if ( get_transient( $throttle_key ) ) {
            return new WP_REST_Response( [ 'saved' => false ], 200 );
        }

        $version = (string) ( $settings['updated_at'] ?? '' );
        $heights = get_option( self::OPTION, [] );
        $entry   = isset( $heights[ $nx_id ] ) && is_array( $heights[ $nx_id ] ) && ( $heights[ $nx_id ]['v'] ?? '' ) === $version
            ? $heights[ $nx_id ]
            : [ 'v' => $version, 'h' => [] ];

        if ( isset( $entry['h'][ $bucket ] ) && abs( $entry['h'][ $bucket ] - $height ) < 2 ) {
            return new WP_REST_Response( [ 'saved' => false ], 200 );
        }

        $entry['h'][ $bucket ] = $height;
        $heights[ $nx_id ]     = $entry;
        update_option( self::OPTION, $heights, false );
        set_transient( $throttle_key, 1, self::THROTTLE );

        return new WP_REST_Response( [ 'saved' => true ], 200 );
    }

    /**
     * Print the reservation for the page's first eligible top bar.
     *
     * Plain CSS with media queries, so it applies before first paint even when
     * an optimizer delays inline scripts. The frontend adds
     * `nx-bar-reserve-off` to <html> once the bar has set its own padding, or
     * when no bar is shown (closed, hidden on this device, schedule).
     *
     * @param int[] $bar_ids Press bar IDs active on this page.
     */
    public function print_reserve( $bar_ids ) {
        if ( empty( $bar_ids ) ) {
            return;
        }
        $heights = get_option( self::OPTION, [] );
        if ( empty( $heights ) || ! is_array( $heights ) ) {
            return;
        }

        foreach ( PostType::get_instance()->get_posts_by_ids( $bar_ids, 'press_bar' ) as $settings ) {
            $nx_id = absint( $settings['nx_id'] );
            $entry = $heights[ $nx_id ] ?? null;
            if ( ! $entry || ( $entry['v'] ?? '' ) !== (string) ( $settings['updated_at'] ?? '' ) || empty( $entry['h'] ) || ! self::reserves_space( $settings ) ) {
                continue;
            }

            $rules = [];
            $min   = 0;
            foreach ( self::BUCKETS as $key => $bucket ) {
                $height = isset( $entry['h'][ $key ] ) ? min( self::MAX_HEIGHT, absint( $entry['h'][ $key ] ) ) : 0;
                // Respect per-device visibility (`hide_on_*` set means "show").
                $visible = ! empty( $settings[ 'mobile' === $bucket['device'] ? 'hide_on_mobile' : ( 'tablet' === $bucket['device'] ? 'hide_on_tab' : 'hide_on_desktop' ) ] );
                if ( $height && $visible ) {
                    $query = [];
                    if ( $min ) {
                        $query[] = "(min-width:{$min}px)";
                    }
                    if ( $bucket['max'] ) {
                        $query[] = '(max-width:' . ( $bucket['max'] - 0.02 ) . 'px)';
                    }
                    $rule    = "html:not(.nx-bar-reserve-off) body{padding-top:{$height}px}";
                    $rules[] = $query ? '@media ' . implode( ' and ', $query ) . "{{$rule}}" : $rule;
                }
                $min = $bucket['max'];
            }

            if ( $rules ) {
                printf(
                    "<style id=\"nx-bar-reserve\" data-nx-id=\"%d\" data-heights=\"%s\">%s</style>\n",
                    esc_attr( $nx_id ),
                    esc_attr( wp_json_encode( $entry['h'] ) ),
                    implode( '', $rules ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from integers above.
                );
            }
            // Only one top bar sets the body padding.
            return;
        }
    }
}
