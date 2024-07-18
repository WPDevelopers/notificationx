<?php

/**
 * Extension Factory
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Core;

use NotificationX\Admin\ImportExport;
use NotificationX\Types\ContactForm;
use NotificationX\Admin\Settings;
use NotificationX\CoreInstaller;
use NotificationX\Extensions\PressBar\PressBar;
use NotificationX\Admin\Reports\ReportEmail;
use NotificationX\Extensions\ExtensionFactory;
use NotificationX\Extensions\Google\GoogleReviews;
use NotificationX\FrontEnd\FrontEnd;
use NotificationX\GetInstance;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;


/**
 * @method static REST get_instance($args = null)
 */
class REST {
    /**
     * Instance of REST
     *
     * @var REST
     */
    use GetInstance;

    private static $_namespace = 'notificationx';
    private static $_version = 1;

    public static function _namespace(){
        return  self::$_namespace . '/v' . self::$_version;
    }

    /**
     * Invoked Automatically
     */
    public function __construct(){
        Rest\Posts::get_instance();
        Rest\Integration::get_instance();
        Rest\Entries::get_instance();
        Rest\Analytics::get_instance();
        Rest\BulkAction::get_instance();

        add_action('rest_api_init', [$this, 'register_routes']);
        $enable_rest_api = Settings::get_instance()->get('settings.enable_rest_api', false);
        if($enable_rest_api){
            add_action('rest_authentication_errors', [$this, 'rest_authentication_errors'], 999);
            add_filter('bb_exclude_endpoints_from_restriction', [$this, 'bb_exclude_endpoints'], 10, 2);
        }


        // third party
        add_filter('jwt_auth_whitelist', [$this, 'jwt_whitelist']);
    }

    /**
     * Checks for a current route being requested, and processes the allowlist
     *
     * @param $access
     *
     * @return WP_Error|null|boolean
     */
    public function rest_authentication_errors( $access ) {
        $namespace = self::_namespace();
        $current_route = $this->get_current_route();
        if($access instanceof \WP_Error && ($current_route == "/$namespace/notice" || $current_route == "/$namespace/analytics")){
            return true;
        }

        // If we got all the way here, return the unmodified $access response
        return $access;
    }

    /**
     * Exclude endpoints from bbPress restriction
     *
     * @param array $endpoints
     * @param string $current_endpoint
     * @return array
     */
    public function bb_exclude_endpoints( $endpoints, $current_endpoint ) {
        $namespace   = self::_namespace();
        $endpoints[] = "/$namespace/notice";
        $endpoints[] = "/$namespace/analytics";
        return $endpoints;
    }

    /**
     * Current REST route getter.
     *
     * @return string
     */
    private function get_current_route() {
        $rest_route = $GLOBALS['wp']->query_vars['rest_route'];

        return ( empty( $rest_route ) || '/' == $rest_route ) ?
            $rest_route :
            untrailingslashit( $rest_route );
    }

    /**
     * Check if a given request has access to get items
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|bool
     */
    public function read_permission( $request ) {
        return current_user_can('read_notificationx');
    }
    public function edit_permission( $request ) {
        return current_user_can('edit_notificationx');
    }
    public function settings_permission( $request ) {
        return current_user_can('edit_notificationx_settings');
    }
    public function activate_plugin_permission( $request ) {
        $params = $request->get_params();
        if(isset($params['is_installed'])){
            if($params['is_installed']){
                return current_user_can('activate_plugins');
            }
            else{
                return current_user_can('install_plugins');
            }
        }
        return current_user_can('activate_plugins') && current_user_can('install_plugins');
    }

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes() {
        $namespace = self::_namespace();
        register_rest_route( $namespace, '/builder', array(
            'methods'   => WP_REST_Server::READABLE,
            'callback'  => array( $this, 'get_builder' ),
            'permission_callback' => array($this, 'read_permission'),
        ));
        register_rest_route( $namespace, '/core-install', array(
            'methods'   => WP_REST_Server::EDITABLE,
            'callback'  => array( $this, 'core_install' ),
            'permission_callback' => array($this, 'activate_plugin_permission'),
        ));
        // Elementor Import
        register_rest_route( $namespace, '/elementor/import', array(
            'methods'   => WP_REST_Server::EDITABLE,
            'callback'  => array( $this, 'elementor_import' ),
            'permission_callback' => array($this, 'edit_permission'),
        ));
        register_rest_route( $namespace, '/elementor/remove', array(
            'methods'   => WP_REST_Server::EDITABLE,
            'callback'  => array( $this, 'elementor_remove' ),
            'permission_callback' => array($this, 'edit_permission'),
        ));
        // Gutenberg Import
        register_rest_route( $namespace, '/gutenberg/import', array(
            'methods'   => WP_REST_Server::EDITABLE,
            'callback'  => array( $this, 'gutenberg_import' ),
            'permission_callback' => array($this, 'edit_permission'),
        ));
        register_rest_route( $namespace, '/gutenberg/remove', array(
            'methods'   => WP_REST_Server::EDITABLE,
            'callback'  => array( $this, 'gutenberg_remove' ),
            'permission_callback' => array($this, 'edit_permission'),
        ));
        // Reporting Import
        register_rest_route( $namespace, '/reporting-test', array(
            'methods'   => WP_REST_Server::EDITABLE,
            'callback'  => array( $this, 'reporting_test' ),
            'permission_callback' => array($this, 'settings_permission'),
        ));

        // NX Settings
        register_rest_route($namespace, '/settings', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'save_settings' ),
                'permission_callback' => array($this, 'settings_permission'),
                'args'                => array(),
            ),
        ));

        // NX Settings
        register_rest_route($namespace, '/miscellaneous', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'miscellaneous' ),
                'permission_callback' => array($this, 'settings_permission'),
                'args'                => array(),
            ),
        ));

        // ajax select
        register_rest_route($namespace, '/get-data', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'get_data'),
                'permission_callback' => array($this, 'read_permission'),
                'args'                => [],
            ),
        ));
        // For Frontend Notice
        register_rest_route($namespace, '/notice', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'notice'),
                'permission_callback' => '__return_true',
                'args'                => [],
            ),
        ));
        // For entries page.
        // register_rest_route($namespace, '/entries/(?P<nx_id>[0-9]+)', array(
        //     array(
        //         'methods'             => WP_REST_Server::READABLE,
        //         'callback'            => array($this, 'get_entries'),
        //         'permission_callback' => '__return_true',
        //         'args'                => [],
        //     ),
        // ));

        // import/export
        register_rest_route( $namespace, '/import', array(
            'methods'   => WP_REST_Server::EDITABLE,
            'callback'  => array( ImportExport::get_instance(), 'import' ),
            'permission_callback' => array($this, 'edit_permission'),
        ));
        register_rest_route( $namespace, '/export', array(
            'methods'   => WP_REST_Server::EDITABLE,
            'callback'  => array( ImportExport::get_instance(), 'export' ),
            'permission_callback' => array($this, 'edit_permission'),
        ));
    }

    public function get_builder( $request ){
        return PostType::get_instance()->get_localize_scripts();
    }

    /**
     * Elementor Import for PressBar design.
     *
     * @param [type] $request
     * @return void
     */
    public function elementor_import( $request ){
        $params = $request->get_params();
        PressBar::get_instance()->create_bar_of_type_bar_with_elementor($params);
        return true;
    }

    /**
     * Elementor Import for PressBar design.
     *
     * @param [type] $request
     * @return void
     */
    public function elementor_remove( $request ){
        $params = $request->get_params();
        PressBar::get_instance()->delete_elementor_post($params['elementor_id']);
        return true;
    }

    /**
     * Gutenberg Import for PressBar design.
     *
     * @param [type] $request
     * @return void
     */
    public function gutenberg_import( $request ){
        $params = $request->get_params();
        return PressBar::get_instance()->gutenberg_import($params);
    }

    /**
     * Gutenberg Import for PressBar design.
     *
     * @param [type] $request
     * @return void
     */
    public function gutenberg_remove( $request ){
        $params = $request->get_params();
        PressBar::get_instance()->gutenberg_remove($params['gutenberg_id']);
        return true;
    }

    /**
     * Analytics Reporting
     *
     * @param WP_REST_Request $request
     * @return void|boolean|array
     */
    public function reporting_test( $request ){
        return ReportEmail::get_instance()->reporting( $request );
    }

    public function get_notificationX( $request ){
        if( $request->get_method() === 'GET' ) {
            return PostType::get_instance()->get_post_with_analytics();
        }
        if( $request->get_method() === 'POST' ) {
            $params = $request->get_params();
            return PostType::get_instance()->save_post($params);
        }
    }

    /**
     * Get data for specific type
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function get_data( $request ){

        $params = $request->get_params();
        if( ! $request->has_param('type') ) {
            return $this->error( 'type' );
        }

        switch( $params['type'] ) {
            case 'ContactForm' :
                return ContactForm::restResponse( $request->get_json_params() );
                break;
            case 'reviews' :
                switch ($params['source']) {
                    case 'google_reviews':
                        return GoogleReviews::get_instance()->restResponse($request->get_json_params() );
                        break;

                    default:
                        # code...
                        break;
                }
                break;
            default:
            $extension = ExtensionFactory::get_instance()->get( $params['source'] );
                if (!empty($extension) && method_exists($extension, 'restResponse')) {
                    $result = $extension->restResponse($request->get_json_params());
                    return $result;
                }
                break;
        }

        return $this->error();
    }

    /**
     *
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function save_settings($request) {
        //   $item = $this->prepare_item_for_database( $request );

        $result = Settings::get_instance()->save_settings($request->get_params());
        if($result){
            return rest_ensure_response([
                'success' => true,
            ]);
        }
        else{
            return rest_ensure_response([
                'success' => false,
            ]);
        }
    }

    /**
     *
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_REST_Response
     */
    public function miscellaneous($request) {
        $params = $request->get_params();

        $result = apply_filters('nx_rest_miscellaneous', null, $params);
        if($result !== null){
            return rest_ensure_response([
                'success' => true,
            ]);
        }
        else{
            return rest_ensure_response([
                'success' => false,
            ]);
        }
    }

    /**
     * Return notices for frontend.
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return void
     */
    public function notice($request) {
        $params = $request->get_params();
        return FrontEnd::get_instance()->get_notifications_data( $params );

    }

    public function rest_data($nonce = true){
        return apply_filters('nx_rest_data', array(
            'root'             => rest_url(),
            'namespace'        => $this->_namespace(),
            'nonce'            => $nonce ? wp_create_nonce( 'wp_rest' ) : '',
            'omit_credentials' => Settings::get_instance()->get( 'settings.omit_credentials', true ),
        ));
    }

    /**
     * Undocumented function
     *
     * @param \WP_REST_Request $request
     * @return
     */
    public function core_install( \WP_REST_Request $request ){
        $params = $request->get_params();
        $slug = $params['slug'];
        $file = $params['file'];
        $result = CoreInstaller::get_instance()->install_plugin($slug, $file);
        return $result == null;
    }

    /**
     * This is function will throw error for API
     *
     * @param string $type
     * @return \WP_Error
     */
    public function error( $type = '' ) {
        switch( $type ) {
            case 'api':
                return $this->formattedError( 'api_error', __( 'Unauthorized Access: You have to logged in first.', 'notificationx' ), 401 );
                break;
            case 'type':
                return $this->formattedError( 'type_error', __( 'Invalid Type: You have to give a type.', 'notificationx' ), 401 );
                break;
            default:
                return $this->formattedError( 'response_error', __( '400 Bad Request.', 'notificationx' ), 400 );
        }
    }

    /**
     * This function is responsible for format Error Message by \WP_Error
     *
     * @param string $code
     * @param string $message
     * @param integer $http_code
     * @param array $args
     * @return \WP_Error
     */
    private function formattedError( $code, $message, $http_code, $args = [] ){
        return new \WP_Error( "nx_$code", $message, [ 'status' => $http_code ] );
    }

    /**
     * JWT Whitelist
     *
     * @param array $endpoints
     * @return array
     */
    public function jwt_whitelist( $endpoints ) {
        $__endpoints = array(
            '/wp-json/notificationx/v1',
            '/wp-json/notificationx/v1/nx',
            '/wp-json/notificationx/v1/nx/*',
            '/wp-json/notificationx/v1/api-connect',
            '/wp-json/notificationx/v1/notification/*',
            '/wp-json/notificationx/v1/regenerate/*',
            '/wp-json/notificationx/v1/reset/*',
            '/wp-json/notificationx/v1/analytics',
            '/wp-json/notificationx/v1/analytics/get',
            '/wp-json/notificationx/v1/bulk-action/delete',
            '/wp-json/notificationx/v1/bulk-action/regenerate',
            '/wp-json/notificationx/v1/bulk-action/enable',
            '/wp-json/notificationx/v1/bulk-action/disable',
            '/wp-json/notificationx/v1/builder',
            '/wp-json/notificationx/v1/core-install',
            '/wp-json/notificationx/v1/elementor/import',
            '/wp-json/notificationx/v1/gutenberg/import',
            '/wp-json/notificationx/v1/elementor/remove',
            '/wp-json/notificationx/v1/gutenberg/remove',
            '/wp-json/notificationx/v1/reporting-test',
            '/wp-json/notificationx/v1/settings',
            '/wp-json/notificationx/v1/miscellaneous',
            '/wp-json/notificationx/v1/get-data',
            '/wp-json/notificationx/v1/notice',
            '/wp-json/notificationx/v1/import',
            '/wp-json/notificationx/v1/export',
            '/wp-json/notificationx/v1/license/activate',
            '/wp-json/notificationx/v1/license/deactivate',
            '/wp-json/notificationx/v1/license/submit-otp',
            '/wp-json/notificationx/v1/license/resend-otp',
        );

        return array_unique( array_merge( $endpoints, $__endpoints ) );
    }
}
