<?php

namespace NotificationX\Core\Rest;

use NotificationX\GetInstance;
use NotificationX\Admin\Admin;
use WP_REST_Controller;
use WP_REST_Server;

/**
 * @method static Entries get_instance($args = null)
 */
class Entries {
    /**
     * Instance of NotificationX
     *
     * @var NotificationX
     */
    use GetInstance;

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
        $this->rest_base = 'regenerate';
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registers the routes for the objects of the controller.
     *
     * @since 4.7.0
     *
     * @see register_rest_route()
     */
    public function register_routes() {
        // For entries page.
        // register_rest_route($namespace, '/entries/(?P<nx_id>[0-9]+)', array(
        //     array(
        //         'methods'             => WP_REST_Server::READABLE,
        //         'callback'            => array($this, 'get_entries'),
        //         'permission_callback' => '__return_true',
        //         'args'                => [],
        //     ),
        // ));

        // Regenerate Notices
		register_rest_route($this->namespace, '/regenerate/(?P<nx_id>[0-9]+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'regenerate'),
                'permission_callback' => array($this, 'check_permission'),
                'args'                => array(
                    'nx_id' => array(
                        'description' => __( 'Unique identifier for the object.', 'notificationx' ),
                        'type'        => 'integer',
                    ),
                ),
            ),
        ));

        // Reset Notices
		register_rest_route($this->namespace, '/reset/(?P<nx_id>[0-9]+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'reset'),
                'permission_callback' => array($this, 'check_permission'),
                'args'                => array(
                    'nx_id' => array(
                        'description' => __( 'Unique identifier for the object.', 'notificationx' ),
                        'type'        => 'integer',
                    ),
                ),
            ),
        ));
    }

    /**
     * Update one item from the collection
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function regenerate($request) {
        $params = $request->get_params();
        Admin::get_instance()->regenerate_notifications($params);
        wp_send_json_success();
        // return new \WP_Error('cant-update', __('message', 'notificationx'), array('status' => 500));
    }

    /**
     * Reset analytics based on notification id
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function reset($request) {
        $params = $request->get_params();
        $updated_analytics = Admin::get_instance()->reset_notifications($params);
        wp_send_json_success($updated_analytics, 200);
    }



    public function get_entries($request){
        $params = $request->get_params();
        $result = Entries::get_instance()->get_entries([
            'nx_id' => absint( $params['nx_id'] )
        ]);
        wp_send_json($result);
    }

    /**
     * Check if a given request has access to get items
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|bool
     */
    public function check_permission( $request ) {
        return current_user_can( 'edit_posts' );
    }
}