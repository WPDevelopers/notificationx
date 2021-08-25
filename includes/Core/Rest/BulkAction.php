<?php

namespace NotificationX\Core\Rest;

use NotificationX\Admin\Admin;
use NotificationX\Core\PostType;
use NotificationX\GetInstance;
use WP_REST_Server;

class BulkAction {
    /**
     * Instance of BulkAction
     *
     * @var BulkAction
     */
    use GetInstance;

    /**
     * Post type.
     *
     * @since 4.7.0
     * @var string
     */
    protected $post_type;

    /**
     * Constructor.
     *
     * @since 4.7.0
     *
     * @param string $post_type Post type.
     */
    public function __construct() {
        $this->namespace = 'notificationx/v1';
        $this->rest_base = 'bulk-action';
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
        register_rest_route($this->namespace, "/{$this->rest_base}/delete",
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'delete'),
                'permission_callback' => [$this, 'edit_permission'],
            )
        );
        register_rest_route($this->namespace, "/{$this->rest_base}/regenerate",
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'regenerate'),
                'permission_callback' => [$this, 'read_permission'],
            )
        );

    }

    public function read_permission( $request ) {
        return current_user_can('read_notificationx');
    }
    public function edit_permission( $request ) {
        return current_user_can('edit_notificationx');
    }

    public function delete($request){
        $count = 0;
        $params = $request->get_params();
        if(!empty($params['ids']) && is_array($params['ids'])){
            foreach ($params['ids'] as $key => $nx_id) {
                $count += PostType::get_instance()->delete_post($nx_id);
            }
        }
        return [
            'success' => true,
            'count'   => $count,
        ];
    }

    public function regenerate($request){
        $count = 0;
        $params = $request->get_params();
        if(!empty($params['ids']) && is_array($params['ids'])){
            foreach ($params['ids'] as $key => $nx_id) {
                $count += Admin::get_instance()->regenerate_notifications(['nx_id' => $nx_id]);
            }
        }
        return [
            'success' => true,
            'count'   => $count,
        ];
    }
}