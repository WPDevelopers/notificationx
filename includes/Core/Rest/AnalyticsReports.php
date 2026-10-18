<?php
/**
 * Analytics dashboard endpoints.
 *
 * Free registers the data reset (DELETE analytics/report/data). The reports
 * themselves (summary, notifications, audience, leads, export) are
 * NotificationX Pro's: its NotificationXPro\Core\Rest\AnalyticsReports
 * extends this class and adds them in register_routes(); GetInstance builds
 * the Pro class when Pro is active.
 *
 * @package NotificationX
 */

namespace NotificationX\Core\Rest;

use NotificationX\Admin\Settings;
use NotificationX\Core\Database;
use NotificationX\Core\Tracker;
use NotificationX\GetInstance;
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

}
