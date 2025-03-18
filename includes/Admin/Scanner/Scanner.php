<?php
namespace NotificationX\Admin\Scanner;

use NotificationX\GetInstance;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

class Scanner 
{
    use GetInstance;
    private static $_namespace = 'notificationx';
    private static $_version   = 1;
    private static $_apiBase   = "https://notificationx-api.test/cookie-scanner/v1";

    public function __construct() 
    {
        add_action('rest_api_init', [$this, 'rest_init']);
    }

    public static function _namespace()
    {
        return self::$_namespace . '/v' . self::$_version;
    }

    public function rest_init()
    {
        $namespace = self::_namespace();

        // Register the scan initiation endpoint
        register_rest_route($namespace, '/scan', array(
            'methods'   => WP_REST_Server::CREATABLE,
            'callback'  => array($this, 'initiate_scan'),
            'permission_callback' => '__return_true',
        ));

        // Register the scan status endpoint
        register_rest_route($namespace, '/scan/status', array(
            'methods'   => WP_REST_Server::READABLE,
            'callback'  => array($this, 'check_scan_status'),
            'permission_callback' => '__return_true',
            'args' => array(
                'scan_id' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_string($param);
                    }
                ),
            ),
        ));
    }

    public function initiate_scan(WP_REST_Request $request)
    {
        $url = $request->get_param('url');

        if (empty($url)) {
            return new WP_REST_Response(['error' => 'URL parameter is required'], 400);
        }
        // Trigger the scan process (implement this function as needed)
        $this->trigger_scan($url);

        return new WP_REST_Response( [ 'status' => 'Scan started.' ], 200);
    }

    public function check_scan_status(WP_REST_Request $request)
    {
        $scanId = $request->get_param('scan_id');

        // Retrieve the scan status from the database (implement this function as needed)
        $status = $this->get_scan_status($scanId);

        if ($status === null) {
            return new WP_REST_Response(['error' => 'Invalid scan ID'], 404);
        }

        if( !empty( $status['status'] ) && $status['status'] == 'completed' ) {
            
        }

        return new WP_REST_Response(['data' => $status], 200);
    }

    private function trigger_scan($url)
    {
        // API endpoint that will process the scan
        $apiEndpoint = self::$_apiBase . "?url=" . urlencode($url);

        // Make an HTTP GET request
        $response = wp_remote_get($apiEndpoint, [
            'timeout'   => 200, // Set timeout (adjust as needed)
            'sslverify' => false, // Bypass SSL verification temporarily (for local dev)
        ]);

        // Check for errors in the response
        if (is_wp_error($response)) {
            return new WP_REST_Response(['error' => $response->get_error_message()], 500);
        }

        // Decode response body
        $responseBody = json_decode(wp_remote_retrieve_body($response), true);

        return wp_send_json_success($responseBody);
    }

    

    private function get_scan_status($scanId)
    {
       // API endpoint that will process the scan
        $apiEndpoint = self::$_apiBase . "/status.php?scan_id=" . urlencode($scanId);

        // Make an HTTP GET request
        $response = wp_remote_get($apiEndpoint, [
            'timeout'   => 200, // Set timeout (adjust as needed)
            'sslverify' => false, // Bypass SSL verification temporarily (for local dev)
        ]);

        // Check for errors in the response
        if (is_wp_error($response)) {
            return new WP_REST_Response(['error' => $response->get_error_message()], 500);
        }

        // Decode response body
        $responseBody = json_decode(wp_remote_retrieve_body($response), true);
        return $responseBody;
    }
}
?>
