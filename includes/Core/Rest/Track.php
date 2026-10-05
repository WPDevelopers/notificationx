<?php
/**
 * Public endpoint the frontend runtime sends notification events to.
 *
 * @package NotificationX
 */

namespace NotificationX\Core\Rest;

use NotificationX\Core\Tracker;
use NotificationX\GetInstance;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * @method static Track get_instance($args = null)
 */
class Track {
    use GetInstance;

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( 'notificationx/v1', '/track', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'track' ],
            // Anonymous visitors send these; Tracker::ingest() checks the
            // site token, the rate limit and that each notification exists.
            'permission_callback' => [ Tracker::get_instance(), 'enabled' ],
        ] );
    }

    public function track( WP_REST_Request $request ) {
        // navigator.sendBeacon() may arrive as text/plain.
        $payload = $request->get_json_params();
        if ( ! is_array( $payload ) ) {
            $payload = json_decode( (string) $request->get_body(), true );
        }
        $stored = Tracker::get_instance()->ingest( $payload );
        if ( is_wp_error( $stored ) ) {
            return $stored;
        }
        return new WP_REST_Response( [ 'stored' => $stored ], 202 );
    }
}
