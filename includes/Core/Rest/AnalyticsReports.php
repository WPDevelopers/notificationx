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
    }

    /**
     * Same rule as the existing analytics/get route.
     */
    public function can_read() {
        return current_user_can( 'read_notificationx_analytics' ) && Settings::get_instance()->get( 'settings.enable_analytics', true );
    }

    /**
     * Every report is Pro. Free shows the all-time totals (the header cards,
     * from Analytics::get_total_count()) and a preview of the reports; a
     * range chart next to all-time cards would show two different "views"
     * numbers. Tracking still runs on Free, so the history is there after an
     * upgrade.
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
     * Tests set this through reflection to exercise the Pro reports.
     *
     * @var bool|null
     */
    private static $pro_override = null;

    protected function is_pro() {
        return null !== self::$pro_override ? self::$pro_override : \NotificationX\NotificationX::get_instance()->is_pro();
    }
























}
