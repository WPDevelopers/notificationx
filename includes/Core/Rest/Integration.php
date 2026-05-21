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

    /**
     * Grace window during which the legacy md5(home_url()) key is still accepted
     * after upgrade, so existing Zapier zaps keep firing while customers rotate.
     */
    const LEGACY_KEY_GRACE_DAYS = 14;

    const OPT_API_KEY                = 'nx_integration_api_key';
    const OPT_GRACE_STARTED_AT       = 'nx_integration_api_key_grace_started_at';
    const OPT_LEGACY_KEY_LAST_USED   = 'nx_integration_legacy_key_last_used_at';

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
        add_action('admin_notices', [$this, 'legacy_api_key_notice']);
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
     * Returns the site's integration API key, generating and persisting one if it doesn't exist yet.
     * Generating the key also opens the legacy-key grace window so existing Zapier zaps keep
     * firing while customers rotate.
     */
    public static function get_api_key() {
        $key = get_option( self::OPT_API_KEY );
        if ( empty( $key ) ) {
            $key = wp_generate_password( 32, false );
            update_option( self::OPT_API_KEY, $key, false );
            if ( ! get_option( self::OPT_GRACE_STARTED_AT ) ) {
                update_option( self::OPT_GRACE_STARTED_AT, time(), false );
            }
        }
        return $key;
    }

    /**
     * Unix timestamp at which the legacy md5(home_url()) key stops being accepted.
     */
    public static function legacy_key_grace_ends_at(): int {
        $started = (int) get_option( self::OPT_GRACE_STARTED_AT );
        if ( ! $started ) {
            return 0;
        }
        return $started + ( self::LEGACY_KEY_GRACE_DAYS * DAY_IN_SECONDS );
    }

    /**
     * Validates an incoming API key.
     * The new random key is always accepted. The legacy md5(home_url()) key is accepted only
     * during the post-upgrade grace window; we record every use so the admin notice can prompt
     * the customer to rotate. After the grace window the legacy key is rejected.
     */
    public static function is_valid_api_key( string $api_key ): bool {
        if ( hash_equals( self::get_api_key(), $api_key ) ) {
            return true;
        }
        $is_legacy = hash_equals( md5( home_url( '', 'http' ) ), $api_key )
            || hash_equals( md5( home_url( '', 'https' ) ), $api_key );
        if ( ! $is_legacy ) {
            return false;
        }
        update_option( self::OPT_LEGACY_KEY_LAST_USED, time(), false );
        $grace_ends_at = self::legacy_key_grace_ends_at();
        return $grace_ends_at > 0 && time() < $grace_ends_at;
    }

    /**
     * Persistent dashboard notice — surfaced whenever the legacy key has been used recently
     * so the customer can rotate before (or after) the grace window closes.
     */
    public function legacy_api_key_notice() {
        if ( ! current_user_can( 'edit_notificationx_settings' ) ) {
            return;
        }
        $last_used = (int) get_option( self::OPT_LEGACY_KEY_LAST_USED );
        if ( ! $last_used ) {
            return;
        }
        $grace_ends_at = self::legacy_key_grace_ends_at();
        $settings_url  = admin_url( 'admin.php?page=nx-settings' );
        if ( $grace_ends_at > 0 && time() < $grace_ends_at ) {
            $deadline = wp_date( get_option( 'date_format' ), $grace_ends_at );
            $message  = sprintf(
                /* translators: %s: rotation deadline date. */
                __( 'A Zapier (or other webhook) integration is still calling NotificationX with the legacy API key. Rotate it to the new key before %s — after that, requests using the old key will be rejected.', 'notificationx' ),
                '<strong>' . esc_html( $deadline ) . '</strong>'
            );
            $class = 'notice notice-warning';
        } else {
            $message = __( 'A Zapier (or other webhook) integration is still calling NotificationX with the legacy API key. Those calls are now being rejected — update the integration with the new key to restore it.', 'notificationx' );
            $class   = 'notice notice-error';
        }
        printf(
            '<div class="%1$s"><p>%2$s <a href="%3$s">%4$s</a></p></div>',
            esc_attr( $class ),
            wp_kses_post( $message ),
            esc_url( $settings_url ),
            esc_html__( 'Get the new key', 'notificationx' )
        );
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
            if (isset($response_data['data']['id'])){
                $post = PostType::get_instance()->get_post($response_data['data']['id']);
                if($post['source']){
                    do_action( "nx_api_response_success_{$post['source']}", $response_data['data'] );
                }
            }
            do_action( 'nx_api_response_success', $response_data['data'] );
        }

        return apply_filters( 'nx_api_response', $response_data );
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
