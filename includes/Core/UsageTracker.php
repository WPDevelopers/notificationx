<?php
/**
 * Usage Tracker Class File.
 *
 * Collects anonymous NotificationX usage statistics — which notification
 * types and which data sources customers are using — and merges them into the
 * WP Insights tracking payload that is sent (once per day, after user opt-in)
 * to send.wpinsight.com via {@see \NotificationX\Admin\PluginInsights}.
 *
 * The data is attached to the payload's `optional_data` key. The wpinsights
 * app renders `optional_data` as a flat `key => value` table (nested arrays
 * show up as "[object Object]"), so every value here is a scalar count and the
 * type/source grouping is expressed through key prefixes:
 *
 *   created_notifications              => total notifications
 *   notificationx-total-active         => total enabled notifications
 *   notificationx-total-inactive       => total disabled notifications
 *   notificationx-type-<slug>          => count of notifications of that type
 *   notificationx-source-<slug>        => count of notifications using that source
 *   notificationx-active-type-<slug>   => count of *enabled* notifications of that type
 *   notificationx-active-source-<slug> => count of *enabled* notifications using that source
 *
 * No additional cron is required: PluginInsights already schedules a daily
 * `put_do_weekly_action` event whose payload we enrich here through the
 * `nx_plugin_usage_tracker_data` filter.
 *
 * @package NotificationX\Core
 * @since 3.3.0
 */

namespace NotificationX\Core;

use NotificationX\GetInstance;

/**
 * UsageTracker Class.
 *
 * @method static UsageTracker get_instance($args = null)
 */
class UsageTracker {

    use GetInstance;

    /**
     * Prefix applied to every key inside optional_data so NotificationX rows
     * are easy to identify in the wpinsights app.
     */
    const KEY_PREFIX = 'notificationx-';

    /**
     * Bootstrap the tracker by hooking into the insights payload.
     */
    public function __construct() {
        add_filter( 'nx_plugin_usage_tracker_data', [ $this, 'add_usage_data' ], 10, 1 );
    }

    /**
     * Merge NotificationX usage counts into the insights payload's
     * `optional_data` (the mapping WP Insights renders in the app).
     *
     * @param array $body The tracking data collected by PluginInsights.
     * @return array
     */
    public function add_usage_data( $body ) {
        $optional = ( isset( $body['optional_data'] ) && is_array( $body['optional_data'] ) )
            ? $body['optional_data']
            : [];

        $body['optional_data'] = array_merge( $optional, $this->get_optional_data() );

        return $body;
    }

    /**
     * Build the grouped `optional_data`: totals plus per-type and per-source
     * counts, split into "created" (all) and "active" (enabled-only) groups.
     *
     * @return array
     */
    public function get_optional_data() {
        $db = Database::get_instance();

        // Grouped counts, e.g. [ 'woocommerce_sales' => 5, ... ].
        $by_type        = $this->normalize_counts( $db->get_source_count( Database::$table_posts, 'type' ) );
        $by_source      = $this->normalize_counts( $db->get_source_count( Database::$table_posts, 'source' ) );
        $by_type_active = $this->normalize_counts( $db->get_source_count( Database::$table_posts, 'type', [ 'enabled' => true ] ) );
        $by_src_active  = $this->normalize_counts( $db->get_source_count( Database::$table_posts, 'source', [ 'enabled' => true ] ) );

        $total        = array_sum( $by_type );
        $total_active = array_sum( $by_type_active );

        // The wpinsights app renders optional_data as a flat key => value
        // table; nested arrays display as "[object Object]". So everything is
        // flattened to top-level scalar keys, with descriptive prefixes that
        // keep the type/source grouping readable by name.
        $data = [
            'created_notifications'             => $total,
            self::KEY_PREFIX . 'total-active'   => $total_active,
            self::KEY_PREFIX . 'total-inactive' => max( 0, $total - $total_active ),
        ];

        $data = array_merge(
            $data,
            $this->prefix_keys( self::KEY_PREFIX . 'type-', $by_type ),
            $this->prefix_keys( self::KEY_PREFIX . 'source-', $by_source ),
            $this->prefix_keys( self::KEY_PREFIX . 'active-type-', $by_type_active ),
            $this->prefix_keys( self::KEY_PREFIX . 'active-source-', $by_src_active )
        );

        /**
         * Filter the final NotificationX optional_data before it is attached
         * to the tracking payload.
         *
         * @since 3.3.0
         * @param array<string,int> $data Flat usage count map.
         */
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
        return apply_filters( 'nx_usage_tracker_data', $data );
    }

    /**
     * Re-key a grouped-count set with a prefixed slug key.
     *
     * @param string $prefix Key prefix, e.g. 'notificationx-type-'.
     * @param array  $counts Counts keyed by slug.
     * @return array<string,int>
     */
    protected function prefix_keys( $prefix, $counts ) {
        $result = [];
        foreach ( $counts as $slug => $count ) {
            $result[ $prefix . $slug ] = $count;
        }
        return $result;
    }

    /**
     * Normalize a grouped-count result: drop empty keys, cast counts to int
     * and sort descending so the most-used entry comes first.
     *
     * @param array $counts Raw counts keyed by slug.
     * @return array<string,int>
     */
    protected function normalize_counts( $counts ) {
        $result = [];
        if ( ! is_array( $counts ) ) {
            return $result;
        }
        foreach ( $counts as $key => $value ) {
            if ( '' === $key || null === $key ) {
                continue;
            }
            $result[ $key ] = (int) $value;
        }
        arsort( $result );
        return $result;
    }
}
