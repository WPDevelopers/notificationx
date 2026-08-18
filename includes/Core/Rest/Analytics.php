<?php

namespace NotificationX\Core\Rest;

use NotificationX\Admin\Settings;
use NotificationX\Core\Analytics as CoreAnalytics;
use NotificationX\Core\AudienceToken;
use NotificationX\FrontEnd\FrontEnd;
use NotificationX\GetInstance;
use NotificationX\NotificationX;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * @method static Analytics get_instance($args = null)
 */
class Analytics {
    /**
     * Instance of Analytics
     *
     * @var Analytics
     */
    use GetInstance;

    /**
     * Post type.
     *
     * @since 4.7.0
     * @var string
     */
    protected $post_type;
    public $namespace;
    public $rest_base;

    /**
     * Constructor.
     *
     * @since 4.7.0
     *
     * @param string $post_type Post type.
     */
    public function __construct() {
        $this->namespace = 'notificationx/v1';
        $this->rest_base = 'analytics';
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Analytics endpoint URL, carrying the signature the endpoint now requires.
     *
     * The flashing tab posts its own analytics with a bare fetch() rather than
     * through the shared frontend request helper, so it does not pick the
     * signature up the way every other frontend request does -- it has to be
     * baked into the URL it is handed at enqueue time.
     *
     * @param array|int $nx_ids Notification ids this page is displaying. Callers
     *                          that know theirs should pass it; without one the
     *                          set the server granted this page is signed
     *                          instead, which costs a query but keeps older
     *                          callers working.
     * @return string
     */
    public function get_rest_url( $nx_ids = [] ){
        $url = rest_url($this->namespace . '/' . $this->rest_base);

        $token = empty( $nx_ids )
            ? AudienceToken::create( FrontEnd::get_instance()->get_notifications_ids() )
            : AudienceToken::create( [ 'active' => array_map( 'absint', (array) $nx_ids ) ] );

        return $token ? add_query_arg( 'nx_token', $token, $url ) : $url;
    }

    /**
     * Registers the routes for the objects of the controller.
     *
     * @since 4.7.0
     *
     * @see register_rest_route()
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            // For Frontend analytics
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'insert_analytics'),
                'permission_callback' => [$this, 'can_insert_analytics'],
                'args'                => array(
                    'nx_id' => array(
                        'required'    => true,
                        'description' => __( 'Unique identifier for the object.', 'notificationx' ),
                        'type'        => 'integer',
                    ),
                    'type' => array(
                        'required'    => false,
                        'description' => __( 'Click or View', 'notificationx' ),
                        'type'        => 'string',
                    ),
                ),
            )
        );
        register_rest_route(
            $this->namespace,
            "/{$this->rest_base}/get",
            // For backend analytics
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'get_analytics' ),
                // maybe use.
                'permission_callback' => [ $this, 'can_read_analytics' ],
                'args' => array(
                    'startDate' => array(
                        'required' => true,
                        'description' => __( 'Start of the date range.', 'notificationx' ),
                        'type'        => 'string',
                    ),
                    'endDate' => array(
                        'required' => true,
                        'description' => __( 'End of the date range.', 'notificationx' ),
                        'type'        => 'string',
                    ),
                ),
            )
        );

    }

    public function can_read_analytics( $request ) {
        return current_user_can('read_notificationx_analytics') && Settings::get_instance()->get('settings.enable_analytics', true);
    }

    public function can_insert_analytics( $request ) {
        return Settings::get_instance()->get('settings.enable_analytics', true);
    }

    public function get_analytics($request){
        $params = $request->get_params();
        $result = CoreAnalytics::get_instance()->get_stats($params);
        wp_send_json($result);
    }

    public function insert_analytics($request){
        $params = $request->get_params();
        $nx_id  = absint( $params['nx_id'] );

        // The id has to be one this visitor's page render actually granted.
        // That is a stronger check than "does this notification exist": the
        // notification must have been served here, so a row can no longer be
        // created for an id that was never displayed -- which is what let the
        // stats table grow to one row per made-up id per day.
        if ( ! AudienceToken::permits( $request->get_param( 'nx_token' ), $nx_id ) ) {
            return new WP_Error(
                'nx_analytics_not_permitted',
                __( 'This notification was not served to this request.', 'notificationx' ),
                [ 'status' => 403 ]
            );
        }

        // Click-through rate is derived from clicks and views when the report is
        // read. Accepting it here let a caller write the rate itself; the
        // frontend has never sent it.
        $type = ( ! empty( $params['type'] ) && in_array( $params['type'], [ 'clicks', 'views' ], true ) )
            ? $params['type']
            : 'clicks';

        CoreAnalytics::get_instance()->insert_analytics( $nx_id, $type );

        return ['success' => true];
    }
}
