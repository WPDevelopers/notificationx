<?php
/**
 * Read endpoints for the Analytics dashboard.
 *
 * Every report resolves one date window (see resolve_window()) and reads the
 * daily `nx_stats` rows inside it, so KPI cards, charts, tables and exports
 * always describe the same range. Counts are stored as varchar in nx_stats,
 * so every sum casts to an integer.
 *
 * @package NotificationX
 */

namespace NotificationX\Core\Rest;

use NotificationX\Admin\Settings;
use NotificationX\Core\Database;
use NotificationX\Core\Helper;
use NotificationX\Core\Tracker;
use NotificationX\GetInstance;
use NotificationX\Types\TypeFactory;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * @method static AnalyticsReports get_instance($args = null)
 */
class AnalyticsReports {
    use GetInstance;

    /**
     * REST namespace.
     *
     * @var string
     */
    public $namespace = 'notificationx/v1';

    /**
     * Presets the Free dashboard offers; anything else needs Pro.
     *
     * @var string[]
     */
    const FREE_RANGES = [ '7', '30' ];

    /**
     * Longest custom window, in days.
     */
    const MAX_DAYS = 730;

    /**
     * Sources whose form submissions are stored as leads in nx_entries.
     *
     * @var string[]
     */
    const LEAD_SOURCES = [ 'popup_notification', 'exit_intent_custom' ];

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        $common = [
            'range'   => [
                'type'              => 'string',
                'default'           => '7',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'nx_id'   => [
                'type'    => 'integer',
                'default' => 0,
                'minimum' => 0,
            ],
            'compare' => [
                'type'    => 'boolean',
                'default' => false,
            ],
            'type'    => [
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'sanitize_key',
            ],
        ];

        register_rest_route( $this->namespace, '/analytics/report/summary', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'summary' ],
            'permission_callback' => [ $this, 'can_read' ],
            'args'                => $common,
        ] );
        register_rest_route( $this->namespace, '/analytics/report/notifications', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'notifications' ],
            'permission_callback' => [ $this, 'can_read_pro' ],
            'args'                => $common,
        ] );
        register_rest_route( $this->namespace, '/analytics/report/notification/(?P<id>\d+)', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'notification' ],
            'permission_callback' => [ $this, 'can_read_pro' ],
            'args'                => $common,
        ] );
        register_rest_route( $this->namespace, '/analytics/report/audience', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'audience' ],
            'permission_callback' => [ $this, 'can_read_pro' ],
            'args'                => $common,
        ] );
        register_rest_route( $this->namespace, '/analytics/report/leads', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'leads_report' ],
            'permission_callback' => [ $this, 'can_read_pro' ],
            'args'                => $common,
        ] );
        register_rest_route( $this->namespace, '/analytics/report/data', [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => [ $this, 'reset' ],
            'permission_callback' => [ $this, 'can_reset' ],
            'args'                => [
                'nx_id' => [
                    'type'    => 'integer',
                    'default' => 0,
                    'minimum' => 0,
                ],
            ],
        ] );
        register_rest_route( $this->namespace, '/analytics/report/export', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'export' ],
            'permission_callback' => [ $this, 'can_export' ],
            'args'                => $common + [
                'module' => [
                    'type'    => 'string',
                    'enum'    => [ 'overview', 'notifications' ],
                    'default' => 'overview',
                ],
            ],
        ] );
    }

    /**
     * Same rule as the existing analytics/get route.
     */
    public function can_read() {
        return current_user_can( 'read_notificationx_analytics' ) && Settings::get_instance()->get( 'settings.enable_analytics', true );
    }

    /**
     * Free shows the basic numbers (views, clicks, CTR, leads) on the
     * Overview; the detailed reports are Pro. Tracking still runs on Free, so
     * the history is there after an upgrade.
     *
     * @return bool|WP_Error
     */
    public function can_read_pro() {
        if ( ! $this->can_read() ) {
            return false;
        }
        if ( ! $this->is_pro() ) {
            return new WP_Error( 'nx_pro_required', __( 'This report is part of NotificationX Pro.', 'notificationx' ), [ 'status' => 403 ] );
        }
        return true;
    }

    /**
     * Resetting needs the same right as the per-notification "Reset analytics".
     */
    public function can_reset() {
        return $this->can_read() && current_user_can( 'edit_notificationx' );
    }

    /**
     * DELETE analytics/report/data — clear views, clicks and Audience data of
     * one notification (nx_id) or of every notification. Leads are entries
     * and are not touched.
     */
    public function reset( WP_REST_Request $request ) {
        global $wpdb;
        $nx_id = absint( $request['nx_id'] );
        if ( $nx_id ) {
            \NotificationX\Core\Analytics::get_instance()->delete_analytics( $nx_id );
        } else {
            $wpdb->query( 'DELETE FROM ' . Database::$table_stats ); // phpcs:ignore WordPress.DB
            Tracker::get_instance()->delete_data( 0 );
        }
        delete_option( 'nx_analytics_rolled_at' );
        return new WP_REST_Response( [ 'reset' => true, 'nx_id' => $nx_id ] );
    }

    /**
     * CSV export is a Pro feature.
     */
    public function can_export() {
        return $this->can_read() && $this->is_pro();
    }

    /**
     * Tests set this through reflection to exercise the Pro reports.
     *
     * @var bool|null
     */
    private static $pro_override = null;

    protected function is_pro() {
        return null !== self::$pro_override ? self::$pro_override : \NotificationX\NotificationX::get_instance()->is_pro();
    }

    /**
     * Which notifications a report covers: 0 (all), one ID, or a list of IDs
     * (a type filter). Returns the SQL to append and adds its arguments.
     *
     * @param int|int[] $scope Scope.
     * @param array     $args  Prepare arguments, appended to.
     */
    protected function scope_sql( $scope, &$args ) {
        if ( is_array( $scope ) ) {
            $ids = $scope ? array_map( 'intval', $scope ) : [ 0 ];
            array_push( $args, ...$ids );
            return ' AND nx_id IN (' . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ')';
        }
        if ( $scope ) {
            $args[] = (int) $scope;
            return ' AND nx_id = %d';
        }
        return '';
    }

    /**
     * The scope a request asks for: one notification (nx_id) wins over a
     * type filter (type), which wins over everything.
     *
     * @return int|int[]
     */
    protected function request_scope( WP_REST_Request $request ) {
        $nx_id = absint( $request['nx_id'] );
        if ( $nx_id ) {
            return $nx_id;
        }
        $type = sanitize_key( (string) $request['type'] );
        if ( '' === $type ) {
            return 0;
        }
        global $wpdb;
        return array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( 'SELECT nx_id FROM ' . Database::$table_posts . ' WHERE type = %s', $type ) ) ); // phpcs:ignore WordPress.DB
    }

    /**
     * Turn a range token into an inclusive [start, end] window of stat dates.
     *
     * Tokens: '7' | '14' | '30' | '90' (last N days, today included), 'all',
     * 'custom:YYYY-MM-DD:YYYY-MM-DD'. Stats are bucketed by UTC day
     * (Analytics::insert_analytics() uses gmdate), so the window is too.
     * Free accepts only FREE_RANGES and falls back to 7 days.
     *
     * @param string $token Range token.
     * @return array{start:string,end:string,days:int,token:string}
     */
    public function resolve_window( $token ) {
        global $wpdb;
        $token = (string) $token;
        if ( ! $this->is_pro() && ! in_array( $token, self::FREE_RANGES, true ) ) {
            $token = '7';
        }
        $today = gmdate( 'Y-m-d' );

        if ( 'all' === $token ) {
            $first = $wpdb->get_var( 'SELECT MIN(created_at) FROM ' . Database::$table_stats ); // phpcs:ignore WordPress.DB
            $start = $first ? $first : $today;
            return $this->window( $start, $today, 'all' );
        }
        if ( 0 === strpos( $token, 'custom:' ) ) {
            $parts = explode( ':', $token );
            if ( 3 === count( $parts ) && $this->is_date( $parts[1] ) && $this->is_date( $parts[2] ) ) {
                $start = min( $parts[1], $parts[2] );
                $end   = min( max( $parts[1], $parts[2] ), $today );
                if ( $this->days_between( $start, $end ) > self::MAX_DAYS ) {
                    $start = gmdate( 'Y-m-d', strtotime( $end . ' -' . ( self::MAX_DAYS - 1 ) . ' days' ) );
                }
                return $this->window( $start, $end, $token );
            }
            $token = '7';
        }
        $days  = in_array( $token, [ '7', '14', '30', '90' ], true ) ? (int) $token : 7;
        $start = gmdate( 'Y-m-d', strtotime( $today . ' -' . ( $days - 1 ) . ' days' ) );
        return $this->window( $start, $today, (string) $days );
    }

    /**
     * The window of the same length that ends the day before $window starts.
     */
    public function previous_window( $window ) {
        $end   = gmdate( 'Y-m-d', strtotime( $window['start'] . ' -1 day' ) );
        $start = gmdate( 'Y-m-d', strtotime( $end . ' -' . ( $window['days'] - 1 ) . ' days' ) );
        return $this->window( $start, $end, 'previous' );
    }

    protected function window( $start, $end, $token ) {
        return [
            'start' => $start,
            'end'   => $end,
            'days'  => $this->days_between( $start, $end ),
            'token' => $token,
        ];
    }

    protected function days_between( $start, $end ) {
        return (int) round( ( strtotime( $end ) - strtotime( $start ) ) / DAY_IN_SECONDS ) + 1;
    }

    protected function is_date( $value ) {
        $d = \DateTime::createFromFormat( 'Y-m-d', $value );
        return $d && $d->format( 'Y-m-d' ) === $value;
    }

    /**
     * Views and clicks per day (or per notification) inside a window.
     *
     * @param array  $window  Window.
     * @param int    $nx_id   0 for every notification.
     * @param string $group   'date' or 'nx_id'.
     * @return array
     */
    protected function stats( $window, $nx_id = 0, $group = 'date' ) {
        global $wpdb;
        $col   = 'nx_id' === $group ? 'nx_id' : 'created_at';
        $where = 'created_at BETWEEN %s AND %s';
        $args  = [ $window['start'], $window['end'] ];
        $where .= $this->scope_sql( $nx_id, $args );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- table and column names are fixed strings.
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT {$col} AS k, SUM(CAST(views AS UNSIGNED)) AS views, SUM(CAST(clicks AS UNSIGNED)) AS clicks FROM " . Database::$table_stats . " WHERE {$where} GROUP BY {$col}", $args ), ARRAY_A );
        $out  = [];
        foreach ( (array) $rows as $row ) {
            $out[ $row['k'] ] = [
                'views'  => (int) $row['views'],
                'clicks' => (int) $row['clicks'],
            ];
        }
        return $out;
    }

    /**
     * Popup / Exit Intent form submissions per notification inside a window.
     */
    protected function leads( $window, $nx_id = 0 ) {
        global $wpdb;
        $in    = implode( ',', array_fill( 0, count( self::LEAD_SOURCES ), '%s' ) );
        $where = "source IN ($in) AND created_at >= %s AND created_at < %s";
        $args  = array_merge( self::LEAD_SOURCES, [ $window['start'] . ' 00:00:00', gmdate( 'Y-m-d', strtotime( $window['end'] . ' +1 day' ) ) . ' 00:00:00' ] );
        $where .= $this->scope_sql( $nx_id, $args );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
        $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT nx_id, COUNT(*) AS leads FROM ' . Database::$table_entries . " WHERE {$where} GROUP BY nx_id", $args ), ARRAY_A );
        $out  = [];
        foreach ( (array) $rows as $row ) {
            $out[ (int) $row['nx_id'] ] = (int) $row['leads'];
        }
        return $out;
    }

    /**
     * Tracked events (Core\Tracker rollup) per day or per notification:
     * 'seen' is a real impression, unlike the legacy 'views' which count loads.
     *
     * @param array  $window Window.
     * @param int    $nx_id  0 for every notification.
     * @param string $group  'day' or 'nx_id'.
     * @return array
     */
    protected function tracked( $window, $nx_id = 0, $group = 'nx_id' ) {
        global $wpdb;
        $col   = 'day' === $group ? 'day' : 'nx_id';
        $where = "dim = 'all' AND day BETWEEN %s AND %s";
        $args  = [ $window['start'], $window['end'] ];
        $where .= $this->scope_sql( $nx_id, $args );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT {$col} AS k, SUM(views) AS seen, SUM(clicks) AS clicks, SUM(closes) AS closes, SUM(submits) AS submits, SUM(hovers) AS hovers FROM " . Database::$table_stats_daily . " WHERE {$where} GROUP BY {$col}", $args ), ARRAY_A );
        $out  = [];
        foreach ( (array) $rows as $row ) {
            $out[ $row['k'] ] = [
                'seen'    => (int) $row['seen'],
                'clicks'  => (int) $row['clicks'],
                'closes'  => (int) $row['closes'],
                'submits' => (int) $row['submits'],
                'hovers'  => (int) $row['hovers'],
            ];
        }
        return $out;
    }

    protected function totals( $by_key, $leads = [] ) {
        $views  = array_sum( wp_list_pluck( $by_key, 'views' ) );
        $clicks = array_sum( wp_list_pluck( $by_key, 'clicks' ) );
        return [
            'views'  => $views,
            'clicks' => $clicks,
            'ctr'    => $this->ctr( $clicks, $views ),
            'leads'  => array_sum( $leads ),
        ];
    }

    /**
     * Click-through rate as a percent with one decimal.
     */
    public function ctr( $clicks, $views ) {
        return $views > 0 ? round( $clicks / $views * 100, 1 ) : 0;
    }

    /**
     * Percent change, or null when there is no baseline to compare with.
     */
    public function change( $current, $previous ) {
        if ( ! $previous ) {
            return null;
        }
        return round( ( $current - $previous ) / $previous * 100, 1 );
    }

    /**
     * Zero-filled daily series for a window.
     */
    protected function series( $window, $by_date ) {
        $series = [];
        for ( $i = 0; $i < $window['days']; $i++ ) {
            $date     = gmdate( 'Y-m-d', strtotime( $window['start'] . ' +' . $i . ' days' ) );
            $row      = isset( $by_date[ $date ] ) ? $by_date[ $date ] : [ 'views' => 0, 'clicks' => 0 ];
            $series[] = [
                'date'   => $date,
                'views'  => $row['views'],
                'clicks' => $row['clicks'],
            ];
        }
        return $series;
    }

    /**
     * Notifications (every one, enabled or not) keyed by nx_id.
     */
    protected function posts( $nx_id = 0 ) {
        global $wpdb;
        $sql = 'SELECT nx_id, title, type, source, theme, enabled, created_at FROM ' . Database::$table_posts;
        if ( $nx_id ) {
            $sql = $wpdb->prepare( $sql . ' WHERE nx_id = %d', $nx_id ); // phpcs:ignore WordPress.DB
        }
        $rows = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB
        $out  = [];
        foreach ( (array) $rows as $row ) {
            $out[ (int) $row['nx_id'] ] = [
                'nx_id'      => (int) $row['nx_id'],
                'title'      => '' !== (string) $row['title'] ? $row['title'] : sprintf( /* translators: %d: notification ID */ __( 'Notification #%d', 'notificationx' ), $row['nx_id'] ),
                'type'       => $row['type'],
                'type_label' => $this->type_label( $row['type'] ),
                'source'     => $row['source'],
                'theme'      => $row['theme'],
                'enabled'    => (bool) $row['enabled'],
                'is_lead'    => in_array( $row['source'], self::LEAD_SOURCES, true ),
            ];
        }
        return $out;
    }

    protected function type_label( $type ) {
        static $labels = [];
        if ( ! isset( $labels[ $type ] ) ) {
            $obj             = TypeFactory::get_instance()->get( $type );
            $labels[ $type ] = $obj && ! empty( $obj->title ) ? wp_strip_all_tags( $obj->title ) : ucwords( str_replace( '_', ' ', (string) $type ) );
        }
        return $labels[ $type ];
    }

    /**
     * GET analytics/report/summary
     */
    public function summary( WP_REST_Request $request ) {
        $pro     = $this->is_pro();
        // Filtering by notification or type is Pro.
        $nx_id   = $pro ? $this->request_scope( $request ) : 0;
        $window  = $this->resolve_window( $request['range'] );
        $compare = $this->is_pro() && rest_sanitize_boolean( $request['compare'] );

        $by_date = $this->stats( $window, $nx_id );
        $by_nx   = $this->stats( $window, $nx_id, 'nx_id' );
        $leads   = $this->leads( $window, $nx_id );
        $totals  = $this->totals( $by_date, $leads );

        $previous = null;
        $changes  = null;
        if ( $compare ) {
            $prev_window = $this->previous_window( $window );
            $previous    = $this->totals( $this->stats( $prev_window, $nx_id ), $this->leads( $prev_window, $nx_id ) );
            $changes     = [
                'views'  => $this->change( $totals['views'], $previous['views'] ),
                'clicks' => $this->change( $totals['clicks'], $previous['clicks'] ),
                'leads'  => $this->change( $totals['leads'], $previous['leads'] ),
                // CTR moves in percentage points.
                'ctr'    => $previous['views'] ? round( $totals['ctr'] - $previous['ctr'], 1 ) : null,
            ];
            $previous['window'] = $prev_window;
        }

        $posts = $this->posts();
        $types = [];
        $top   = [];
        foreach ( $by_nx as $id => $row ) {
            $post = isset( $posts[ $id ] ) ? $posts[ $id ] : null;
            if ( ! $post ) {
                continue; // Stats of a deleted notification.
            }
            $type = $post['type'];
            if ( ! isset( $types[ $type ] ) ) {
                $types[ $type ] = [ 'type' => $type, 'label' => $post['type_label'], 'views' => 0, 'clicks' => 0 ];
            }
            $types[ $type ]['views']  += $row['views'];
            $types[ $type ]['clicks'] += $row['clicks'];
            $top[] = $post + $row + [
                'ctr'   => $this->ctr( $row['clicks'], $row['views'] ),
                'leads' => isset( $leads[ $id ] ) ? $leads[ $id ] : 0,
            ];
        }
        usort( $top, function ( $a, $b ) {
            return $b['views'] - $a['views'];
        } );
        $types = array_values( $types );
        usort( $types, function ( $a, $b ) {
            return $b['views'] - $a['views'];
        } );

        return new WP_REST_Response( [
            'window'   => $window,
            'totals'   => $totals,
            'previous' => $previous,
            'changes'  => $changes,
            'series'   => $this->series( $window, $by_date ),
            'types'    => $pro ? $types : [],
            // Free: the basic totals and trend only.
            'top'      => $pro ? array_slice( $top, 0, 8 ) : [],
            'locked'   => ! $pro,
            'tracked'  => [
                // Legacy views count loads and skip these types; seen
                // impressions for every type are in the Audience report.
                'untracked_types' => [ 'popup', 'gdpr', 'inline' ],
                'since'           => Tracker::get_instance()->tracking_since(),
            ],
        ] );
    }

    /**
     * GET analytics/report/notifications
     */
    public function notifications( WP_REST_Request $request ) {
        $window = $this->resolve_window( $request['range'] );
        Tracker::get_instance()->maybe_rollup();
        $by_nx  = $this->stats( $window, 0, 'nx_id' );
        $leads  = $this->leads( $window );
        $seen   = $this->tracked( $window );
        $rows   = [];
        foreach ( $this->posts() as $id => $post ) {
            $s      = isset( $by_nx[ $id ] ) ? $by_nx[ $id ] : [ 'views' => 0, 'clicks' => 0 ];
            $t      = isset( $seen[ $id ] ) ? $seen[ $id ] : [ 'seen' => 0, 'closes' => 0 ];
            $rows[] = $post + $s + [
                'ctr'    => $this->ctr( $s['clicks'], $s['views'] ),
                'leads'  => isset( $leads[ $id ] ) ? $leads[ $id ] : 0,
                'seen'   => $t['seen'],
                'closes' => $t['closes'],
            ];
        }
        return new WP_REST_Response( [
            'window'         => $window,
            'rows'           => $rows,
            'tracking_since' => Tracker::get_instance()->tracking_since(),
        ] );
    }

    /**
     * GET analytics/report/notification/{id}
     */
    public function notification( WP_REST_Request $request ) {
        $id    = absint( $request['id'] );
        $posts = $this->posts( $id );
        if ( empty( $posts[ $id ] ) ) {
            return new WP_Error( 'nx_not_found', __( 'Notification not found.', 'notificationx' ), [ 'status' => 404 ] );
        }
        $window  = $this->resolve_window( $request['range'] );
        $by_date = $this->stats( $window, $id );
        $totals  = $this->totals( $by_date, $this->leads( $window, $id ) );
        $changes = null;
        if ( $this->is_pro() ) {
            $prev    = $this->previous_window( $window );
            $ptotals = $this->totals( $this->stats( $prev, $id ), $this->leads( $prev, $id ) );
            $changes = [
                'views'  => $this->change( $totals['views'], $ptotals['views'] ),
                'clicks' => $this->change( $totals['clicks'], $ptotals['clicks'] ),
                'ctr'    => $ptotals['views'] ? round( $totals['ctr'] - $ptotals['ctr'], 1 ) : null,
            ];
        }
        return new WP_REST_Response( [
            'window'       => $window,
            'notification' => $posts[ $id ],
            'totals'       => $totals,
            'changes'      => $changes,
            'series'       => $this->series( $window, $by_date ),
            'edit_url'     => admin_url( 'admin.php?page=nx-edit&id=' . $id ),
        ] );
    }

    /**
     * GET analytics/report/leads — form submissions of Popup and Exit Intent
     * notifications as a report: totals, daily trend, conversion rate (leads
     * per time seen), the notifications and sources that collect them, and
     * the weekday they arrive. The entries themselves stay on the Entries page.
     */
    public function leads_report( WP_REST_Request $request ) {
        global $wpdb;
        Tracker::get_instance()->maybe_rollup();
        $scope   = $this->request_scope( $request );
        $window  = $this->resolve_window( $request['range'] );
        $compare = $this->is_pro() && rest_sanitize_boolean( $request['compare'] );

        $in    = implode( ',', array_fill( 0, count( self::LEAD_SOURCES ), '%s' ) );
        $args  = array_merge( self::LEAD_SOURCES, [ $window['start'] . ' 00:00:00', gmdate( 'Y-m-d', strtotime( $window['end'] . ' +1 day' ) ) . ' 00:00:00' ] );
        $where = "source IN ($in) AND created_at >= %s AND created_at < %s" . $this->scope_sql( $scope, $args );
        $table = Database::$table_entries;

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
        $by_day = [];
        foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT DATE(created_at) AS d, COUNT(*) AS n FROM {$table} WHERE {$where} GROUP BY DATE(created_at)", $args ), ARRAY_A ) as $row ) {
            $by_day[ $row['d'] ] = (int) $row['n'];
        }
        $weekday = array_fill( 0, 7, 0 ); // 0 = Monday.
        foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT WEEKDAY(created_at) AS w, COUNT(*) AS n FROM {$table} WHERE {$where} GROUP BY WEEKDAY(created_at)", $args ), ARRAY_A ) as $row ) {
            $weekday[ (int) $row['w'] ] = (int) $row['n'];
        }
        $by_source = [];
        foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT source, COUNT(*) AS n FROM {$table} WHERE {$where} GROUP BY source", $args ), ARRAY_A ) as $row ) {
            $by_source[ $row['source'] ] = (int) $row['n'];
        }
        // phpcs:enable

        $leads = $this->leads( $window, $scope );
        $seen  = $this->tracked( $window, $scope );
        $posts = $this->posts();
        $rows  = [];
        foreach ( $posts as $id => $post ) {
            if ( ! $post['is_lead'] || ( is_array( $scope ) && ! in_array( $id, $scope, true ) ) || ( is_int( $scope ) && $scope && $scope !== $id ) ) {
                continue;
            }
            $n    = isset( $leads[ $id ] ) ? $leads[ $id ] : 0;
            $s    = isset( $seen[ $id ] ) ? $seen[ $id ]['seen'] : 0;
            if ( ! $n && ! $s ) {
                continue;
            }
            $rows[] = $post + [
                'leads'      => $n,
                'seen'       => $s,
                'conversion' => $this->ctr( $n, $s ),
            ];
        }
        usort( $rows, function ( $a, $b ) {
            return $b['leads'] - $a['leads'] ?: $b['seen'] - $a['seen'];
        } );

        $total     = array_sum( $by_day );
        $seen_lead = array_sum( wp_list_pluck( $rows, 'seen' ) );
        $series    = [];
        for ( $i = 0; $i < $window['days']; $i++ ) {
            $date     = gmdate( 'Y-m-d', strtotime( $window['start'] . ' +' . $i . ' days' ) );
            $series[] = [ 'date' => $date, 'leads' => isset( $by_day[ $date ] ) ? $by_day[ $date ] : 0 ];
        }

        $change = null;
        if ( $compare ) {
            $prev   = $this->previous_window( $window );
            $change = $this->change( $total, array_sum( $this->leads( $prev, $scope ) ) );
        }

        return new WP_REST_Response( [
            'window'     => $window,
            'totals'     => [
                'leads'      => $total,
                'seen'       => $seen_lead,
                'conversion' => $this->ctr( $total, $seen_lead ),
                'per_day'    => $window['days'] ? round( $total / $window['days'], 1 ) : 0,
            ],
            'change'     => $change,
            'series'     => $series,
            'weekday'    => $weekday,
            'sources'    => [
                'popup_notification' => isset( $by_source['popup_notification'] ) ? $by_source['popup_notification'] : 0,
                'exit_intent_custom' => isset( $by_source['exit_intent_custom'] ) ? $by_source['exit_intent_custom'] : 0,
            ],
            'top'        => array_slice( $rows, 0, 8 ),
            'entries_url' => admin_url( 'admin.php?page=nx-feedback-entries' ),
        ] );
    }

    /**
     * Rollup rows of one dimension inside a window, biggest first.
     */
    protected function dimension( $window, $dim, $nx_id = 0, $limit = 0 ) {
        global $wpdb;
        $where = 'dim = %s AND day BETWEEN %s AND %s';
        $args  = [ $dim, $window['start'], $window['end'] ];
        $where .= $this->scope_sql( $nx_id, $args );
        $sql = 'SELECT val, SUM(views) AS seen, SUM(clicks) AS clicks, SUM(closes) AS closes, SUM(submits) AS submits, SUM(hovers) AS hovers, SUM(visitors) AS visitors FROM ' . Database::$table_stats_daily . " WHERE {$where} GROUP BY val ORDER BY seen DESC, clicks DESC";
        if ( $limit ) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ); // phpcs:ignore WordPress.DB
        return array_map( function ( $row ) {
            return [
                'key'      => (string) $row['val'],
                'seen'     => (int) $row['seen'],
                'clicks'   => (int) $row['clicks'],
                'closes'   => (int) $row['closes'],
                'submits'  => (int) $row['submits'],
                'hovers'   => (int) $row['hovers'],
                'visitors' => (int) $row['visitors'],
                'ctr'      => $this->ctr( (int) $row['clicks'], (int) $row['seen'] ),
            ];
        }, (array) $rows );
    }

    /**
     * GET analytics/report/audience — who saw the notifications: devices,
     * countries, pages and where visitors came from (incl. AI assistants).
     * Built from the Core\Tracker rollup only.
     */
    public function audience( WP_REST_Request $request ) {
        $tracker = Tracker::get_instance();
        $tracker->maybe_rollup();
        $nx_id  = $this->request_scope( $request );
        $window = $this->resolve_window( $request['range'] );

        $all    = $this->dimension( $window, 'all', $nx_id );
        $totals = [ 'seen' => 0, 'clicks' => 0, 'closes' => 0, 'submits' => 0, 'hovers' => 0, 'visitors' => 0 ];
        foreach ( $all as $row ) {
            foreach ( $totals as $k => $v ) {
                $totals[ $k ] += $row[ $k ];
            }
        }
        // Unfiltered: true unique visitors per day across all notifications.
        // Filtered: per-notification uniques added up (one person can count
        // once per notification). Days are added up either way, because the
        // daily salt makes the same person unrecognisable from day to day.
        if ( ! $nx_id ) {
            $site               = $this->dimension( $window, 'site', 0 );
            $totals['visitors'] = $site ? (int) $site[0]['visitors'] : $totals['visitors'];
        }
        $totals['ctr']         = $this->ctr( $totals['clicks'], $totals['seen'] );
        $totals['close_rate']  = $this->ctr( $totals['closes'], $totals['seen'] );
        $totals['engagement']  = $this->ctr( $totals['hovers'], $totals['seen'] );
        $totals['per_visitor'] = $totals['visitors'] ? round( $totals['seen'] / $totals['visitors'], 1 ) : 0;

        $by_day = $this->tracked( $window, $nx_id, 'day' );
        $series = [];
        for ( $i = 0; $i < $window['days']; $i++ ) {
            $date     = gmdate( 'Y-m-d', strtotime( $window['start'] . ' +' . $i . ' days' ) );
            $series[] = [
                'date'   => $date,
                'seen'   => isset( $by_day[ $date ] ) ? $by_day[ $date ]['seen'] : 0,
                'clicks' => isset( $by_day[ $date ] ) ? $by_day[ $date ]['clicks'] : 0,
                'closes' => isset( $by_day[ $date ] ) ? $by_day[ $date ]['closes'] : 0,
                'hovers' => isset( $by_day[ $date ] ) ? $by_day[ $date ]['hovers'] : 0,
            ];
        }

        $names     = Helper::nx_get_all_country();
        $countries = array_map( function ( $row ) use ( $names ) {
            $row['label'] = '' === $row['key'] ? __( 'Unknown', 'notificationx' ) : ( isset( $names[ $row['key'] ] ) ? $names[ $row['key'] ] : $row['key'] );
            return $row;
        }, $this->dimension( $window, 'country', $nx_id ) );

        $sources = array_map( function ( $row ) use ( $tracker ) {
            $row['ai'] = $tracker->ai_name( $row['key'] );
            return $row;
        }, $this->dimension( $window, 'source', $nx_id ) );

        $ai = [ 'seen' => 0, 'clicks' => 0, 'assistants' => [] ];
        foreach ( $sources as $row ) {
            if ( ! $row['ai'] ) {
                continue;
            }
            $name = $row['ai'];
            if ( ! isset( $ai['assistants'][ $name ] ) ) {
                $ai['assistants'][ $name ] = [ 'key' => $name, 'seen' => 0, 'clicks' => 0 ];
            }
            $ai['assistants'][ $name ]['seen']   += $row['seen'];
            $ai['assistants'][ $name ]['clicks'] += $row['clicks'];
            $ai['seen']                          += $row['seen'];
            $ai['clicks']                        += $row['clicks'];
        }
        $ai['assistants'] = array_values( $ai['assistants'] );
        usort( $ai['assistants'], function ( $a, $b ) {
            return $b['seen'] - $a['seen'];
        } );
        $ai['ctr']   = $this->ctr( $ai['clicks'], $ai['seen'] );
        $ai['share'] = $this->ctr( $ai['seen'], $totals['seen'] );

        return new WP_REST_Response( [
            'window'    => $window,
            'since'     => $tracker->tracking_since(),
            'retention' => $tracker->retention_days(),
            'totals'    => $totals,
            'series'    => $series,
            'devices'   => $this->dimension( $window, 'device', $nx_id ),
            'countries' => $countries,
            'pages'     => $this->dimension( $window, 'page', $nx_id, 10 ),
            'sources'   => array_slice( array_values( array_filter( $sources, function ( $row ) {
                return '' !== $row['key'];
            } ) ), 0, 10 ),
            'channels'  => $this->dimension( $window, 'channel', $nx_id ),
            'ai'        => $ai,
        ] );
    }

    /**
     * GET analytics/report/export — CSV of the active screen.
     */
    public function export( WP_REST_Request $request ) {
        $module = 'notifications' === $request['module'] ? 'notifications' : 'overview';
        $lines  = [];
        if ( 'notifications' === $module ) {
            $data    = $this->notifications( $request )->get_data();
            $lines[] = [ 'ID', 'Notification', 'Type', 'Status', 'Views', 'Clicks', 'CTR %', 'Leads' ];
            foreach ( $data['rows'] as $row ) {
                $lines[] = [ $row['nx_id'], $row['title'], $row['type_label'], $row['enabled'] ? 'Enabled' : 'Disabled', $row['views'], $row['clicks'], $row['ctr'], $row['leads'] ];
            }
        } else {
            $data    = $this->summary( $request )->get_data();
            $lines[] = [ 'Date (UTC)', 'Views', 'Clicks', 'CTR %' ];
            foreach ( $data['series'] as $row ) {
                $lines[] = [ $row['date'], $row['views'], $row['clicks'], $this->ctr( $row['clicks'], $row['views'] ) ];
            }
        }
        $csv = '';
        foreach ( $lines as $line ) {
            $csv .= implode( ',', array_map( [ $this, 'csv_cell' ], $line ) ) . "\n";
        }
        return new WP_REST_Response( [
            'filename' => sprintf( 'notificationx-%s-%s-to-%s.csv', $module, $data['window']['start'], $data['window']['end'] ),
            'csv'      => $csv,
        ] );
    }

    /**
     * Quote a CSV cell and neutralise spreadsheet formulas.
     */
    public function csv_cell( $value ) {
        $value = (string) $value;
        if ( '' !== $value && in_array( $value[0], [ '=', '+', '-', '@', "\t", "\r" ], true ) && ! is_numeric( $value ) ) {
            $value = "'" . $value;
        }
        return '"' . str_replace( '"', '""', $value ) . '"';
    }
}
