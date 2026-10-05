<?php
/**
 * Event tracking for the Analytics Audience reports.
 *
 * The frontend runtime reports what visitors actually do with a notification:
 * it was seen (at least half of it on screen for a second), clicked, closed,
 * or its form was submitted. Events land in `nx_events` and are rolled up
 * hourly into `nx_stats_daily`, one row per day, notification and dimension
 * value (device, country, page, source, channel). Reports read only the
 * rollup; raw events are deleted after RETENTION_DAYS.
 *
 * Privacy: no IP address, cookie or user ID is stored. Visitors are counted
 * with a hash of IP + user agent salted with a key that changes every day,
 * so the same person can't be followed from one day to the next. Countries
 * come from CDN/host headers or an already cached lookup, never from a new
 * request to a geo service.
 *
 * The legacy `nx_stats` counters are left untouched.
 *
 * @package NotificationX
 */

namespace NotificationX\Core;

use NotificationX\Admin\Settings;
use NotificationX\GetInstance;

/**
 * @method static Tracker get_instance($args = null)
 */
class Tracker {
    use GetInstance;

    const CRON_HOOK = 'nx_analytics_rollup';

    /**
     * Event names the frontend sends, and how they are stored.
     */
    const EVENTS = [
        'view'   => 1,
        'click'  => 2,
        'close'  => 3,
        'submit' => 4,
        'hover'  => 5,
    ];

    /**
     * Dimensions kept in the daily rollup. 'all' holds the per-notification total.
     */
    const DIMENSIONS = [ 'all', 'device', 'country', 'page', 'source', 'channel' ];

    /**
     * Most events one request may carry.
     */
    const MAX_BATCH = 20;

    /**
     * Most events one visitor may send per minute.
     */
    const RATE_LIMIT = 120;

    /**
     * Days raw events are kept; they only feed the rollup.
     */
    const RETENTION_DAYS = 30;

    /**
     * Days of daily data Free keeps. Free shows 7 or 30 days, but keeps 90
     * so the history is already there after an upgrade to Pro.
     */
    const FREE_RETENTION_DAYS = 90;

    /**
     * Retention choices (days; 0 = forever) for the analytics_retention setting.
     */
    const RETENTION_CHOICES = [ 90, 180, 365, 730, 0 ];

    /**
     * Referrer hosts of AI assistants, and the name shown for each.
     */
    const AI_HOSTS = [
        'chatgpt.com'           => 'ChatGPT',
        'chat.openai.com'       => 'ChatGPT',
        'openai.com'            => 'ChatGPT',
        'perplexity.ai'         => 'Perplexity',
        'gemini.google.com'     => 'Gemini',
        'bard.google.com'       => 'Gemini',
        'copilot.microsoft.com' => 'Copilot',
        'claude.ai'             => 'Claude',
        'chat.deepseek.com'     => 'DeepSeek',
        'deepseek.com'          => 'DeepSeek',
        'grok.com'              => 'Grok',
        'meta.ai'               => 'Meta AI',
        'you.com'               => 'You.com',
        'phind.com'             => 'Phind',
        'poe.com'               => 'Poe',
        'chat.mistral.ai'       => 'Mistral',
    ];

    const SEARCH_HOSTS = [ 'google.', 'bing.com', 'duckduckgo.com', 'search.yahoo.', 'yahoo.com', 'yandex.', 'baidu.com', 'ecosia.org', 'search.brave.com', 'naver.com', 'startpage.com' ];

    const SOCIAL_HOSTS = [ 'facebook.com', 'fb.com', 'instagram.com', 't.co', 'twitter.com', 'x.com', 'linkedin.com', 'lnkd.in', 'reddit.com', 'pinterest.', 'youtube.com', 'tiktok.com', 'threads.net', 'bsky.app', 'quora.com', 'whatsapp.com', 'telegram.org', 't.me' ];

    const EMAIL_HOSTS = [ 'mail.google.com', 'outlook.live.com', 'outlook.office.com', 'mail.yahoo.com' ];

    public function __construct() {
        add_action( 'init', [ $this, 'schedule' ], 20 );
        add_action( self::CRON_HOOK, [ $this, 'run_cron' ] );
        // A deleted notification takes its tracked data with it.
        add_action( 'nx_delete_post', [ $this, 'delete_data' ] );
    }

    /**
     * Days of daily data to keep; 0 keeps everything. Free: 90 days. Pro:
     * the analytics_retention setting, keeping everything by default.
     */
    public function retention_days() {
        if ( ! \NotificationX\NotificationX::get_instance()->is_pro() ) {
            $days = self::FREE_RETENTION_DAYS;
        } else {
            $days = (int) Settings::get_instance()->get( 'settings.analytics_retention', 0 );
            $days = in_array( $days, self::RETENTION_CHOICES, true ) ? $days : 0;
        }
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        return max( 0, (int) apply_filters( 'nx_analytics_retention_days', $days ) );
    }

    /**
     * Delete tracked data (raw events and daily rows) of one notification,
     * or of every notification when $nx_id is 0.
     */
    public function delete_data( $nx_id = 0 ) {
        global $wpdb;
        $nx_id = absint( $nx_id );
        foreach ( [ Database::$table_events, Database::$table_stats_daily ] as $table ) {
            if ( $nx_id ) {
                $wpdb->delete( $table, [ 'nx_id' => $nx_id ], [ '%d' ] ); // phpcs:ignore WordPress.DB
            } else {
                $wpdb->query( "DELETE FROM {$table}" ); // phpcs:ignore WordPress.DB
            }
        }
    }

    public function enabled() {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        return (bool) apply_filters( 'nx_analytics_tracking_enabled', (bool) Settings::get_instance()->get( 'settings.enable_analytics', true ) );
    }

    public function schedule() {
        if ( $this->enabled() && ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'hourly', self::CRON_HOOK );
        }
    }

    /**
     * Site token the runtime sends with every batch. It is public (it is in
     * the page), so it only keeps out requests that never loaded a page of
     * this site; the rate limit and notification check do the real work.
     * It does not expire, so cached pages keep working.
     */
    public function token() {
        return substr( hash_hmac( 'sha256', 'nx-track|' . home_url(), wp_salt( 'nonce' ) ), 0, 20 );
    }

    /**
     * Data the frontend runtime needs, added to the localized config.
     */
    public function client_config() {
        if ( ! $this->enabled() ) {
            return null;
        }
        return [
            'url'   => rest_url( 'notificationx/v1/track' ),
            'token' => $this->token(),
            // Also send nx_view / nx_click / nx_close to Google Analytics (gtag or GTM dataLayer).
            'ga'    => (bool) Settings::get_instance()->get( 'settings.analytics_ga4_events', false ),
        ];
    }

    /**
     * Store a batch sent by the frontend runtime.
     *
     * @param array $payload { t: token, p: page URL, r: referrer, w: viewport width, ev: [{ n: nx_id, e: event }] }
     * @return int|\WP_Error Number of events stored.
     */
    public function ingest( $payload ) {
        global $wpdb;
        if ( ! is_array( $payload ) || empty( $payload['t'] ) || ! is_string( $payload['t'] ) || ! hash_equals( $this->token(), $payload['t'] ) ) {
            return new \WP_Error( 'nx_track_token', 'Invalid token.', [ 'status' => 403 ] );
        }
        if ( ! Analytics::get_instance()->should_count() ) {
            return 0;
        }

        $events = [];
        foreach ( array_slice( isset( $payload['ev'] ) && is_array( $payload['ev'] ) ? $payload['ev'] : [], 0, self::MAX_BATCH ) as $ev ) {
            if ( ! is_array( $ev ) || empty( $ev['n'] ) || empty( $ev['e'] ) || ! is_scalar( $ev['e'] ) || ! isset( self::EVENTS[ $ev['e'] ] ) ) {
                continue;
            }
            $events[] = [ absint( $ev['n'] ), self::EVENTS[ $ev['e'] ] ];
        }
        if ( ! $events ) {
            return 0;
        }

        // Only notifications that exist and are switched on.
        $ids     = array_values( array_unique( array_column( $events, 0 ) ) );
        $in      = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        $enabled = array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( 'SELECT nx_id FROM ' . Database::$table_posts . " WHERE enabled = 1 AND nx_id IN ($in)", $ids ) ) ); // phpcs:ignore WordPress.DB
        $events  = array_values( array_filter( $events, function ( $ev ) use ( $enabled ) {
            return in_array( $ev[0], $enabled, true );
        } ) );
        if ( ! $events ) {
            return 0;
        }

        $visitor = $this->visitor_hash();
        $now     = gmdate( 'Y-m-d H:i:s' );
        $recent  = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . Database::$table_events . ' WHERE visitor = %s AND created_at > %s', $visitor, gmdate( 'Y-m-d H:i:s', time() - MINUTE_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB
        if ( $recent + count( $events ) > self::RATE_LIMIT ) {
            return new \WP_Error( 'nx_track_rate', 'Too many events.', [ 'status' => 429 ] );
        }

        $page = $this->page_path( isset( $payload['p'] ) ? $payload['p'] : '' );
        list( $source, $channel ) = $this->classify(
            isset( $payload['r'] ) ? $payload['r'] : '',
            isset( $payload['p'] ) ? $payload['p'] : ''
        );
        $device  = $this->device( isset( $payload['w'] ) ? (int) $payload['w'] : 0 );
        $country = $this->country();

        $rows = [];
        $args = [];
        foreach ( $events as $ev ) {
            $rows[] = '(%d,%d,%s,%s,%s,%s,%s,%s,%s)';
            array_push( $args, $ev[0], $ev[1], $visitor, $device, $country, $page, $source, $channel, $now );
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
        $wpdb->query( $wpdb->prepare( 'INSERT INTO ' . Database::$table_events . ' (nx_id,event,visitor,device,country,page,source,channel,created_at) VALUES ' . implode( ',', $rows ), $args ) );
        return count( $events );
    }

    /**
     * Daily-salted visitor hash: counts unique visitors per day without
     * being able to recognise them on any other day.
     */
    public function visitor_hash() {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) )[0] );
        }
        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        return substr( hash_hmac( 'sha256', $ip . '|' . $ua, $this->daily_salt() ), 0, 16 );
    }

    protected function daily_salt() {
        $today = gmdate( 'Y-m-d' );
        $salt  = get_option( 'nx_analytics_salt' );
        if ( ! is_array( $salt ) || empty( $salt['day'] ) || $salt['day'] !== $today || empty( $salt['key'] ) ) {
            $salt = [
                'day' => $today,
                'key' => wp_generate_password( 32, true, true ),
            ];
            update_option( 'nx_analytics_salt', $salt, false );
        }
        return $salt['key'];
    }

    /**
     * Path of the page the event happened on, without query string or fragment.
     */
    public function page_path( $url ) {
        $path = wp_parse_url( (string) $url, PHP_URL_PATH );
        $path = is_string( $path ) && '' !== $path ? $path : '/';
        $path = '/' . ltrim( sanitize_text_field( rawurldecode( $path ) ), '/' );
        return function_exists( 'mb_substr' ) ? mb_substr( $path, 0, 191 ) : substr( $path, 0, 191 );
    }

    /**
     * Where the visitor came from: the referrer host and its channel
     * (direct, internal, search, social, email, ai, referral). A utm_source
     * on the landing page wins over the referrer, because AI assistants
     * often strip the referrer but tag their links (utm_source=chatgpt.com).
     *
     * @return array{0:string,1:string} [ source, channel ]
     */
    public function classify( $referrer, $page_url = '' ) {
        $utm_source = '';
        $utm_medium = '';
        $query      = wp_parse_url( (string) $page_url, PHP_URL_QUERY );
        if ( $query ) {
            parse_str( $query, $q );
            $utm_source = isset( $q['utm_source'] ) && is_string( $q['utm_source'] ) ? strtolower( sanitize_text_field( $q['utm_source'] ) ) : '';
            $utm_medium = isset( $q['utm_medium'] ) && is_string( $q['utm_medium'] ) ? strtolower( sanitize_text_field( $q['utm_medium'] ) ) : '';
        }

        $host = '';
        if ( $utm_source ) {
            $host = preg_replace( '/^www\./', '', $utm_source );
        } elseif ( $referrer ) {
            $ref_host = wp_parse_url( (string) $referrer, PHP_URL_HOST );
            $host     = is_string( $ref_host ) ? preg_replace( '/^www\./', '', strtolower( $ref_host ) ) : '';
            $own_host = preg_replace( '/^www\./', '', strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) );
            if ( $host && $host === $own_host ) {
                return [ $host, 'internal' ];
            }
        }
        $host = substr( (string) $host, 0, 100 );

        if ( '' === $host ) {
            $channel = 'direct';
        } elseif ( $this->ai_name( $host ) ) {
            $channel = 'ai';
        } elseif ( 'email' === $utm_medium || 'newsletter' === $utm_medium || $this->host_matches( $host, self::EMAIL_HOSTS ) ) {
            $channel = 'email';
        } elseif ( $this->host_matches( $host, self::SEARCH_HOSTS ) ) {
            $channel = 'search';
        } elseif ( $this->host_matches( $host, self::SOCIAL_HOSTS ) ) {
            $channel = 'social';
        } else {
            $channel = 'referral';
        }
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        $channel = (string) apply_filters( 'nx_analytics_channel', $channel, $host, $referrer );
        return [ $host, substr( $channel, 0, 12 ) ];
    }

    /**
     * The assistant's display name for an AI referrer host, or ''.
     */
    public function ai_name( $host ) {
        $host = preg_replace( '/^www\./', '', strtolower( (string) $host ) );
        foreach ( self::AI_HOSTS as $ai_host => $name ) {
            if ( $host === $ai_host || substr( $host, -strlen( '.' . $ai_host ) ) === '.' . $ai_host ) {
                return $name;
            }
        }
        return '';
    }

    protected function host_matches( $host, $patterns ) {
        foreach ( $patterns as $pattern ) {
            if ( '.' === substr( $pattern, -1 ) ) {
                // 'google.' matches google.com, google.co.uk, news.google.de...
                if ( 0 === strpos( $host, $pattern ) || false !== strpos( $host, '.' . $pattern ) ) {
                    return true;
                }
            } elseif ( $host === $pattern || substr( $host, -strlen( '.' . $pattern ) ) === '.' . $pattern ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Device class from the viewport width the runtime reports, falling
     * back to the user agent.
     */
    public function device( $width ) {
        if ( $width > 0 ) {
            return $width < 768 ? 'mobile' : ( $width < 1024 ? 'tablet' : 'desktop' );
        }
        return wp_is_mobile() ? 'mobile' : 'desktop';
    }

    /**
     * Two-letter country code, or '' when unknown. Uses CDN/host headers or
     * the result Targeting already cached for this IP; never a new lookup.
     */
    public function country() {
        $code = '';
        foreach ( [ 'HTTP_CF_IPCOUNTRY', 'HTTP_CLOUDFRONT_VIEWER_COUNTRY', 'HTTP_X_COUNTRY_CODE', 'GEOIP_COUNTRY_CODE', 'HTTP_X_VERCEL_IP_COUNTRY' ] as $h ) {
            if ( ! empty( $_SERVER[ $h ] ) ) {
                $code = strtoupper( sanitize_text_field( wp_unslash( $_SERVER[ $h ] ) ) );
                break;
            }
        }
        if ( '' === $code && ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $cached = get_transient( 'nx_geo_' . md5( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) ) );
            $code   = is_string( $cached ) ? strtoupper( $cached ) : '';
        }
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        $code = strtoupper( (string) apply_filters( 'nx_analytics_country', $code ) );
        return preg_match( '/^[A-Z]{2}$/', $code ) && ! in_array( $code, [ 'XX', 'T1' ], true ) ? $code : '';
    }

    /**
     * Rebuild the daily rollup for every day from $from to $to (Y-m-d, UTC)
     * from the raw events. Safe to run again: each day is replaced, not added to.
     */
    public function rollup( $from, $to ) {
        global $wpdb;
        $start = $from . ' 00:00:00';
        $end   = gmdate( 'Y-m-d', strtotime( $to . ' +1 day' ) ) . ' 00:00:00';
        $daily = Database::$table_stats_daily;
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$daily} WHERE day BETWEEN %s AND %s", $from, $to ) ); // phpcs:ignore WordPress.DB
        foreach ( self::DIMENSIONS as $dim ) {
            $val = 'all' === $dim ? "''" : $dim;
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- dimension names are fixed strings.
            $wpdb->query( $wpdb->prepare(
                "INSERT INTO {$daily} (day,nx_id,dim,val,views,clicks,closes,submits,hovers,visitors)
                SELECT DATE(created_at), nx_id, %s, {$val}, SUM(event = 1), SUM(event = 2), SUM(event = 3), SUM(event = 4), SUM(event = 5), COUNT(DISTINCT visitor)
                FROM " . Database::$table_events . " WHERE created_at >= %s AND created_at < %s
                GROUP BY DATE(created_at), nx_id, {$val}",
                $dim,
                $start,
                $end
            ) );
        }
        // Site-wide unique visitors per day (nx_id 0): a visitor who saw two
        // notifications counts once here, unlike in the per-notification rows.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
        $wpdb->query( $wpdb->prepare(
            "INSERT INTO {$daily} (day,nx_id,dim,val,views,clicks,closes,submits,hovers,visitors)
            SELECT DATE(created_at), 0, 'site', '', SUM(event = 1), SUM(event = 2), SUM(event = 3), SUM(event = 4), SUM(event = 5), COUNT(DISTINCT visitor)
            FROM " . Database::$table_events . ' WHERE created_at >= %s AND created_at < %s
            GROUP BY DATE(created_at)',
            $start,
            $end
        ) );
        update_option( 'nx_analytics_rolled_at', time(), false );
    }

    /**
     * Roll up today (and yesterday, which may have late events) when the
     * last run is older than $max_age seconds. Reports call this so they are
     * never more than a few minutes behind, even if WP-Cron is slow.
     */
    public function maybe_rollup( $max_age = 600 ) {
        if ( time() - (int) get_option( 'nx_analytics_rolled_at', 0 ) >= $max_age ) {
            $this->rollup( gmdate( 'Y-m-d', strtotime( '-1 day' ) ), gmdate( 'Y-m-d' ) );
        }
    }

    public function run_cron() {
        $this->rollup( gmdate( 'Y-m-d', strtotime( '-1 day' ) ), gmdate( 'Y-m-d' ) );
        $this->purge();
    }

    /**
     * Delete raw events older than RETENTION_DAYS (their days were rolled up
     * long before) and daily rows older than retention_days().
     */
    public function purge() {
        global $wpdb;
        $keep = $this->retention_days();
        $raw  = $keep ? min( self::RETENTION_DAYS, $keep ) : self::RETENTION_DAYS;
        $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . Database::$table_events . ' WHERE created_at < %s LIMIT 5000', gmdate( 'Y-m-d 00:00:00', strtotime( '-' . $raw . ' days' ) ) ) ); // phpcs:ignore WordPress.DB
        if ( $keep ) {
            $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . Database::$table_stats_daily . ' WHERE day < %s', gmdate( 'Y-m-d', strtotime( '-' . $keep . ' days' ) ) ) ); // phpcs:ignore WordPress.DB
        }
    }

    /**
     * First day with tracked data, or null.
     */
    public function tracking_since() {
        global $wpdb;
        $day = $wpdb->get_var( 'SELECT MIN(day) FROM ' . Database::$table_stats_daily ); // phpcs:ignore WordPress.DB
        return $day ? $day : null;
    }

    public static function unschedule() {
        wp_clear_scheduled_hook( self::CRON_HOOK );
    }
}
