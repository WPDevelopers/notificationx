<?php

namespace NotificationX\Core\Rest;

use NotificationX\Core\PostType;
use NotificationX\Core\REST;
use NotificationX\Extensions\ExtensionFactory;
use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;
use NotificationX\NotificationX;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * @method static Integration get_instance($args = null)
 */
class Integration {
    /**
     * Instance of NotificationX
     *
     * @var NotificationX
     */
    use GetInstance;

    public $namespace;
    public $rest_base;

    const OPT_API_KEY = 'nx_integration_api_key';

    /**
     * Bounds on an incoming webhook payload. A Zap or applet sends a flat object
     * with a handful of fields; these sit far above anything real so they never
     * meet legitimate traffic, while keeping a key holder from handing the site
     * an arbitrarily wide, deep or long structure to walk and store.
     */
    const MAX_PAYLOAD_FIELDS = 200;
    const MAX_PAYLOAD_DEPTH  = 10;
    const MAX_FIELD_LENGTH   = 5000;

    /**
     * Constructor.
     *
     * @since 4.7.0
     *
     * @param string $post_type Post type.
     */
    public function __construct() {
        $this->namespace = 'notificationx/v1';
        $this->rest_base = 'notification';
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
        // Settings Integration
        register_rest_route( $this->namespace, '/api-connect', array(
            'methods'   => WP_REST_Server::EDITABLE,
            'callback'  => array( $this, 'api_connect' ),
            'permission_callback' => array($this, 'settings_permission'),
        ));

        // calls from integration provider.
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_response'),
                    'permission_callback' => '__return_true',
                    'args' => array(
                        'id' => array(
                            'required' => true,
                            'description' => __('Unique identifier for the object.', 'notificationx'),
                            'type'        => 'integer',
                        ),
                        'api_key' => array(
                            'required' => true,
                            'description' => __('Unique identifier for the site.', 'notificationx'),
                            'type'        => 'string',
                        ),
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'save_response'),
                    'permission_callback' => '__return_true',
                    'args' => array(
                        'id' => array(
                            'required' => true,
                            'description' => __('Unique identifier for the object.', 'notificationx'),
                            'type'        => 'integer',
                        ),
                        'api_key' => array(
                            'required' => true,
                            'description' => __('Unique identifier for the site.', 'notificationx'),
                            'type'        => 'string',
                        ),
                    ),
                ),
            )
        );
        // OLD Fallback for Zapier
        register_rest_route(
            "notificationx",
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_response'),
                    'permission_callback' => '__return_true',
                    'args' => array(
                        'id' => array(
                            'required' => true,
                            'description' => __('Unique identifier for the object.', 'notificationx'),
                            'type'        => 'integer',
                        ),
                        'api_key' => array(
                            'required' => true,
                            'description' => __('Unique identifier for the site.', 'notificationx'),
                            'type'        => 'string',
                        ),
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'save_response'),
                    'permission_callback' => '__return_true',
                    'args' => array(
                        'id' => array(
                            'required' => true,
                            'description' => __('Unique identifier for the object.', 'notificationx'),
                            'type'        => 'integer',
                        ),
                        'api_key' => array(
                            'required' => true,
                            'description' => __('Unique identifier for the site.', 'notificationx'),
                            'type'        => 'string',
                        ),
                    ),
                ),
            )
        );
    }

    /**
     * The site's integration API key, generated and persisted on first use.
     *
     * Generation is lazy: the key is created the first time it is asked for,
     * which is when the Zapier or IFTTT settings screen renders it for the
     * merchant to copy — in other words, before anything could be configured
     * with it.
     */
    public static function get_api_key() {
        $key = get_option( self::OPT_API_KEY );
        if ( empty( $key ) ) {
            $key = wp_generate_password( 32, false );
            update_option( self::OPT_API_KEY, $key, false );
        }
        return $key;
    }

    /**
     * Validates an incoming API key.
     *
     * Only the site's own randomly generated key is accepted. There used to be
     * a second one: `md5( home_url() )`, honoured for 14 days after an upgrade
     * so existing Zapier and IFTTT integrations kept firing while merchants
     * rotated. But a key derived from the site URL is a key anyone can compute
     * from the address bar, and this endpoint writes notification entries -- so
     * for those 14 days any visitor could have posted whatever they liked into
     * a site's social proof. That whole apparatus (the grace window, the
     * upgrade seeder that opened it, the legacy-use notice) is gone rather than
     * shortened, because a guessable credential is not safer for being
     * temporary.
     *
     * Removing it costs nothing: the grace window shipped in no release, so no
     * site ever opened one.
     */
    public static function is_valid_api_key( string $api_key ): bool {
        $stored = (string) get_option( self::OPT_API_KEY );

        return $stored !== '' && hash_equals( $stored, $api_key );
    }

    public function get_response( \WP_REST_Request $request ){
        $id        = $request['id'];
		$api_key   = $request['api_key'];
        $error     = [];

		if( self::is_valid_api_key( (string) $api_key ) ) {
            $notificationx = PostType::get_instance()->get_post( $id );
            if( $notificationx ) {
                return wp_send_json( true );
            }
            /* translators: %s: notification ID */
            $error['message'] = sprintf( __( 'There is no notification created with this id: %s', 'notificationx' ), $id );
            return wp_send_json_error( $error, 401 );
		} else {
			$error['message'] = __( 'Error: API Key Invalid!', 'notificationx' );
			return wp_send_json_error( $error, 401 );
		}
    }

    /**
     * Undocumented function
     *
     * @param \WP_REST_Request $request
     * @return void
     */
    public function save_response( \WP_REST_Request $request ){
        $response_data = array(
            'data'      => '',
            'error'     => false
        );

        if ( ! isset( $request['api_key'] ) ) {
            $response_data['error'] = __('Error: You should provide an API key.', 'notificationx');
        } else {
            if ( ! self::is_valid_api_key( (string) $request['api_key'] ) ) {
                $response_data['error'] = __('Error: Invalid API key.', 'notificationx');
            }
        }

        if ( ! $response_data['error'] ) {
            $response_data['data'] = $request->get_params();
            if ( isset( $response_data['data']['api_key'] ) ) {
                unset( $response_data['data']['api_key'] );
            }
            $budget = self::MAX_PAYLOAD_FIELDS;
            $response_data['data'] = $this->sanitize_payload( $response_data['data'], $budget );
            if (isset($response_data['data']['id'])){
                $post = PostType::get_instance()->get_post($response_data['data']['id']);
                if(!empty($post['source'])){
                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
                    do_action( "nx_api_response_success_{$post['source']}", $response_data['data'] );
                }
            }
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
            do_action( 'nx_api_response_success', $response_data['data'] );
        }

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
        return apply_filters( 'nx_api_response', $response_data );
    }

    /**
     * Sanitise a webhook payload, bounded in width, depth and length.
     *
     * This replaced a plain `array_walk_recursive()`, which sanitised every leaf
     * but agreed to walk whatever arrived -- so a key holder could hand over an
     * arbitrarily wide or deeply nested structure and have the site traverse and
     * store all of it. The limits are the point; sanitising is the same as it
     * was.
     *
     * Keys are deliberately left as sent. They are what the integration named
     * its fields, and rewriting them would change the data every downstream
     * source reads.
     *
     * @param mixed $value  Payload or fragment of one.
     * @param int   $budget Remaining leaf values, by reference across the walk.
     * @param int   $depth  Current nesting depth.
     * @return mixed
     */
    protected function sanitize_payload( $value, &$budget, $depth = 0 ) {
        if ( is_array( $value ) ) {
            if ( $depth >= self::MAX_PAYLOAD_DEPTH ) {
                return [];
            }

            $clean = [];
            foreach ( $value as $key => $item ) {
                if ( $budget <= 0 ) {
                    break;
                }
                $clean[ $key ] = $this->sanitize_payload( $item, $budget, $depth + 1 );
            }

            return $clean;
        }

        --$budget;

        return substr( sanitize_text_field( (string) $value ), 0, self::MAX_FIELD_LENGTH );
    }

    /**
     * Undocumented function
     *
     * @param \WP_REST_Request $request
     * @return
     */
    public function api_connect( \WP_REST_Request $request ){
        $params = $request->get_params();
        $source = !empty($params['source']) ? $params['source'] : '';
        /**
         * @var Extension
         */
        $ext = ExtensionFactory::get_instance()->get($source);
        if($ext && method_exists($ext, 'connect')){
            return $ext->connect($params);
        }
        else{
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
            $result = apply_filters("nx_api_connect_$source", null, $params);
            if($result){
                return $result;
            }
        }
        return REST::get_instance()->error();
    }

    public function settings_permission( $request ) {
        return current_user_can('edit_notificationx_settings');
    }
}
