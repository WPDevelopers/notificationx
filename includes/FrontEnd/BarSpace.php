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
 * A report can be wrong (measured before the bar's block styles or fonts
 * arrived, zoomed text, a spoofed request), so each width bucket keeps the last
 * few samples and reserves their median once there are enough of them.
 *
 * @method static BarSpace get_instance($args = null)
 */
class BarSpace {
    use GetInstance;

    // v2: per-bucket samples + median (v1 stored the last report only).
    const OPTION      = 'notificationx_bar_heights_v2';
    const MAX_HEIGHT  = 400;
    // One report per visitor IP, bar and width bucket in this window.
    const THROTTLE    = MINUTE_IN_SECONDS;
    const SAMPLES     = 5;
    const MIN_SAMPLES = 3;

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

    // A report more than this many times the current reservation (plus a small
    // allowance) is ignored: it can only open a gap that is not there.
    const OUTLIER_RATIO = 2;
    const OUTLIER_SLACK = 16;

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
        // Any save can change the bar's height (text, font size, layout), and a
        // builder save resubmits the loaded `updated_at`, so the version check
        // alone does not notice an edit.
        add_action( 'nx_saved_post', [ $this, 'forget_heights' ], 10, 3 );
    }

    /**
     * Drop the stored heights of a notification that was just saved, so its old
     * height is not reserved while visitors report the new one.
     *
     * @param array $post  Saved post row.
     * @param array $data  Submitted data.
     * @param int   $nx_id Notification ID.
     */
    public function forget_heights( $post, $data, $nx_id ) {
        $nx_id   = absint( $nx_id );
        $heights = get_option( self::OPTION, [] );
        if ( $nx_id && is_array( $heights ) && isset( $heights[ $nx_id ] ) ) {
            unset( $heights[ $nx_id ] );
            update_option( self::OPTION, $heights, true );
        }
    }

    /**
     * The address a height report came from, used to throttle it and to count
     * each visitor once.
     *
     * `REMOTE_ADDR` is the only value a client cannot set. When it is a private
     * or loopback address the request reached PHP through a reverse proxy on the
     * site's own network, and every visitor would share it — the median would
     * never get enough samples. In that case the address the proxy appended to
     * `X-Forwarded-For` (its last entry), or `X-Real-IP`, identifies the
     * visitor. A public `REMOTE_ADDR` is used as is: forwarded headers from the
     * open internet are not trusted.
     *
     * @return string
     */
    public static function client_ip() {
        // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- isset-checked, validated with filter_var below.
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        if ( $ip && ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
            $forwarded = '';
            if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
                $hops      = array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) );
                $forwarded = (string) end( $hops );
            } elseif ( ! empty( $_SERVER['HTTP_X_REAL_IP'] ) ) {
                $forwarded = trim( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) ) );
            }
            if ( filter_var( $forwarded, FILTER_VALIDATE_IP ) ) {
                $ip = $forwarded;
            }
        }
        // phpcs:enable

        /**
         * Filters the visitor address used to throttle and count bar-height reports.
         *
         * Sites behind a CDN whose edge addresses are public (so the default above
         * keeps them) can return the visitor's address from the CDN's header here.
         *
         * @since 3.3.3
         *
         * @param string $ip Detected address.
         */
        return (string) apply_filters( 'nx_bar_height_client_ip', $ip ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- nx_ is this plugin's hook prefix.
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

    /**
     * Transient name that throttles one visitor's reports for one bar and width
     * bucket. Salted, so the stored name does not reveal the address.
     *
     * @param string $ip     Visitor address.
     * @param int    $nx_id  Notification ID.
     * @param string $bucket Width bucket.
     * @return string
     */
    public static function throttle_key( $ip, $nx_id, $bucket ) {
        return 'nx_bar_height_' . md5( wp_hash( "{$ip}|{$nx_id}|{$bucket}" ) );
    }

    /**
     * Lower median, so an even window leans towards the smaller height.
     *
     * @param int[] $values
     * @return int
     */
    public static function median( array $values ) {
        sort( $values );
        return (int) $values[ ( count( $values ) - 1 ) >> 1 ];
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

        // The endpoint is public, so a single client must not be able to set the
        // gap every visitor sees. Throttle per IP (not per bar, which would also
        // block real browsers from correcting a bad value); the median below
        // takes at most one sample per IP.
        $ip           = self::client_ip();
        $throttle_key = self::throttle_key( $ip, $nx_id, $bucket );
        if ( get_transient( $throttle_key ) ) {
            return new WP_REST_Response( [ 'saved' => false ], 200 );
        }
        set_transient( $throttle_key, 1, self::THROTTLE );

        $version = (string) ( $settings['updated_at'] ?? '' );
        $heights = get_option( self::OPTION, [] );
        if ( ! is_array( $heights ) ) {
            $heights = [];
        }
        $entry = isset( $heights[ $nx_id ] ) && is_array( $heights[ $nx_id ] ) && ( $heights[ $nx_id ]['v'] ?? '' ) === $version
            ? $heights[ $nx_id ]
            : [ 'v' => $version, 's' => [], 'h' => [] ];

        $samples = isset( $entry['s'][ $bucket ] ) && is_array( $entry['s'][ $bucket ] ) ? array_values( $entry['s'][ $bucket ] ) : [];
        $sources = isset( $entry['i'][ $bucket ] ) && is_array( $entry['i'][ $bucket ] ) ? array_values( $entry['i'][ $bucket ] ) : [];
        if ( count( $sources ) !== count( $samples ) ) {
            $sources = array_fill( 0, count( $samples ), '' );
        }
        // Settled: a full window already agrees with this report.
        if ( count( $samples ) >= self::SAMPLES && isset( $entry['h'][ $bucket ] ) && abs( $entry['h'][ $bucket ] - $height ) < 2 ) {
            return new WP_REST_Response( [ 'saved' => false ], 200 );
        }
        // Once a height is reserved, a report far above it is not the bar (an
        // edit clears the window, see forget_heights()) — it would only open a
        // gap above the page, so it never gets a vote.
        if ( isset( $entry['h'][ $bucket ] ) && $height > $entry['h'][ $bucket ] * self::OUTLIER_RATIO + self::OUTLIER_SLACK ) {
            return new WP_REST_Response( [ 'saved' => false ], 200 );
        }

        // One sample per source IP in the window, so a single client cannot
        // supply the majority of samples and pick the median. Stored as a salted
        // hash, never the address.
        $source = substr( wp_hash( $ip ), 0, 8 );
        $index  = array_search( $source, $sources, true );
        if ( false !== $index ) {
            array_splice( $samples, $index, 1 );
            array_splice( $sources, $index, 1 );
        }
        $samples[] = $height;
        $sources[] = $source;
        $samples   = array_slice( $samples, -self::SAMPLES );
        $sources   = array_slice( $sources, -self::SAMPLES );

        $entry['s'][ $bucket ] = $samples;
        $entry['i'][ $bucket ] = $sources;
        $saved                 = false;
        if ( count( $samples ) >= self::MIN_SAMPLES ) {
            $entry['h'][ $bucket ] = self::median( $samples );
            $saved                 = true;
        }
        $heights[ $nx_id ] = $entry;
        // Autoloaded: print_reserve() reads it on every front-end page with a bar.
        update_option( self::OPTION, $heights, true );
        // v1 storage (single value per bucket) is no longer read.
        delete_option( 'notificationx_bar_heights' );

        return new WP_REST_Response( [ 'saved' => $saved ], 200 );
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
                    // Only while scripts run: without JavaScript the bar never
                    // mounts and nothing would release the space. Browsers that
                    // do not know `scripting` skip the rule (no reservation).
                    $query = [ '(scripting:enabled)' ];
                    if ( $min ) {
                        $query[] = "(min-width:{$min}px)";
                    }
                    if ( $bucket['max'] ) {
                        $query[] = '(max-width:' . ( $bucket['max'] - 0.02 ) . 'px)';
                    }
                    $rule    = "html:not(.nx-bar-reserve-off) body{padding-top:{$height}px}";
                    $rules[] = '@media ' . implode( ' and ', $query ) . "{{$rule}}";
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
                // A page cache serves this reservation to visitors who closed the
                // bar too; for them the bar never mounts and the page would jump
                // up once the frontend releases it. Check the close cookie before
                // first paint. Tagged so optimizers do not delay it.
                $cookie = 'notificationx_' . $nx_id . ( ! empty( $settings['countdown_rand'] ) ? '-' . $settings['countdown_rand'] : '' );
                printf(
                    "<script data-no-optimize=\"1\" data-cfasync=\"false\" data-no-defer=\"1\" nowprocket>(function(n){try{if(document.cookie.split('; ').some(function(c){var i=c.indexOf('=');return c.slice(0,i)===n&&c.slice(i+1)!==''&&c.slice(i+1)!=='false';}))document.documentElement.classList.add('nx-bar-reserve-off');}catch(e){}})(%s);</script>\n",
                    wp_json_encode( $cookie ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded string literal.
                );
            }
            // Only one top bar sets the body padding.
            return;
        }
    }
}
