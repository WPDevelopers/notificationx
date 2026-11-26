<?php
namespace NotificationX\Admin\Scanner;

use NotificationX\Core\Helper;
use NotificationX\GetInstance;
use NotificationX\NotificationX;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

class Scanner 
{
    use GetInstance;
    private static $_namespace = 'notificationx';
    private static $_version   = 1;
    private static $_apiBase   = "";
    private static $is_pro     = false;
    public function __construct() 
    {
        add_action('rest_api_init', [$this, 'rest_init']);
        if( NotificationX::is_pro() ) {
            self::$is_pro = true;
        }
        self::$_apiBase = (defined('NX_DEBUG') && NX_DEBUG) 
        ? 'https://notificationx-api.test/cookie-scanner/v1' 
        : 'https://api.notificationx.com/cookie-scanner/v1';
    }

    public static function _namespace()
    {
        return self::$_namespace . '/v' . self::$_version;
    }

   /**
     * Registers custom REST API endpoints for scan initiation, scan status, and scan history.
     * 
     * - /scan: Initiates a new scan based on the provided URL.
     * - /scan/status: Retrieves the status of a scan using the provided scan ID.
     * - /scan/history: Fetches the scan history based on the notification ID (nx_id).
     * 
     * @return void
     */
    public function rest_init()
    {
        $namespace = self::_namespace();

        // Register the scan initiation endpoint
        register_rest_route($namespace, '/scan', array(
            'methods'   => WP_REST_Server::CREATABLE,
            'callback'  => array($this, 'initiate_scan'),
            'permission_callback' => array($this, 'edit_permission'),
        ));

        // Register the scan status endpoint
        register_rest_route($namespace, '/scan/status', array(
            'methods'   => WP_REST_Server::EDITABLE,
            'callback'  => array($this, 'check_scan_status'),
            'permission_callback' => array($this, 'edit_permission'),
            'args'      => array(
                'scan_id' => array(
                    'description'       => 'The scan ID returned when the scan started.',
                    'required'          => true,
                    'validate_callback' => array($this, 'validate_scan_id'),
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

    }

    /**
     * Validates the scan_id argument of the status route.
     *
     * The scanner API's ID format is not documented, so this only requires a
     * non-empty string (or integer) of at most 128 characters. The ID is
     * URL-encoded before it is sent to the API.
     *
     * @param mixed $value The raw scan_id value.
     * @return bool
     */
    public function validate_scan_id($value)
    {
        if (!is_string($value) && !is_int($value)) {
            return false;
        }
        $value = trim((string) $value);
        return '' !== $value && strlen($value) <= 128;
    }

    /**
     * Permission check for the scanner routes.
     * The cookie scanner is only used from the notification builder,
     * which requires the edit_notificationx capability.
     *
     * @return bool
     */
    public function edit_permission()
    {
        return current_user_can('edit_notificationx');
    }

    /**
     * Initiates a scan process for the provided URL.
     * 
     * Validates the URL parameter and triggers the scan if it's valid.
     * Responds with the scan status.
     *
     * @param WP_REST_Request $request The REST request object containing the URL.
     * 
     * @return WP_REST_Response The response indicating the status of the scan initiation.
     */
    public function initiate_scan(WP_REST_Request $request)
    {
        $url = $request->get_param('url');

        // Check if the URL parameter is provided
        if (empty($url)) {
            return new WP_REST_Response(['error' => 'URL parameter is required'], 200);
        }

        // Trigger the scan process (implement this function as needed)
        $this->trigger_scan($url);

        return new WP_REST_Response(['status' => 'Scan started.'], 200);
    }

    /**
     * Retrieves the current status of a scan based on the provided scan ID.
     * 
     * Queries the status from an external service and returns the results.
     * If the scan is completed, it processes the result and inserts cookie and stats entries into the database.
     *
     * @param WP_REST_Request $request The REST request object containing the scan ID.
     * 
     * @return WP_REST_Response The response with the scan status or an error message.
     */
    public function check_scan_status(WP_REST_Request $request)
    {
        $scanId = trim((string) $request->get_param('scan_id'));

        // Handle invalid scan ID before calling the scanner API.
        if ('' === $scanId) {
            $data = [ 'status' => 'failed', 'message' => __('Invalid scan ID','notificationx') ];
            return new WP_REST_Response(['data' => $data ], 400);
        }

        // Retrieve the scan status from the scanner API.
        $status = $this->get_scan_status($scanId);

        // The scanner API could not be reached or gave no usable reply. The
        // error details stay on the server; the UI gets a generic message.
        if (is_wp_error($status)) {
            $data = [ 'status' => 'failed', 'message' => __('Could not reach the cookie scanner service. Please try again later.','notificationx') ];
            return new WP_REST_Response(['data' => $data ], 502);
        }

        // Process the scan result if the status is 'completed'
        if ( is_array($status) && !empty($status['status']) && $status['status'] === 'completed') {
            // Update scan count to the options
            $pre_count = get_option('nx_scan_count', 0);
            update_option('nx_scan_count', $pre_count + 1);
            update_option('nx_scan_date',  Helper::nx_get_current_datetime() );

            $cookies = $status['result'];  // Extract scanned cookies
            $stats   = $status['stats'];   // Extract scan statistics

            // Use the helper function to categorize cookies and get the category count
            $cookieData    = $this->categorizeCookiesAndCount($cookies);
            $categoryCount = $cookieData['category_count'];
            $categorized   = $cookieData['categorized'];
            $data          = [ 'last_scan_date' => Helper::nx_get_current_datetime(), 'status' => 'completed', 'stats' => $stats, 'cookies' => $cookies, 'category_count' => $categoryCount, 'categorized' => $categorized ];
            return new WP_REST_Response(['data' => $data], 200);
        }

        return new WP_REST_Response(['data' => $status], 200);
    }

    /**
     * Triggers a scan process by making an HTTP GET request to an external API.
     * 
     * Sends the provided URL to the external scan API and handles the response.
     * 
     * @param string $url The URL to be scanned.
     * 
     * @return WP_REST_Response The response containing the result of the scan.
     */
    private function trigger_scan($url)
    {
        // API endpoint that will process the scan
        if( self::$is_pro ) {
            $apiEndpoint = self::$_apiBase . "?nxpro=active&url=" . urlencode($url);
        }else{
            $apiEndpoint = self::$_apiBase . "?url=" . urlencode($url);
        }

        // Make an HTTP GET request
        $response = wp_remote_get($apiEndpoint, [
            'timeout'   => 200, // Set timeout (adjust as needed)
            // Verify the certificate, except against the local dev API (NX_DEBUG).
            'sslverify' => ! ( defined('NX_DEBUG') && NX_DEBUG ),
        ]);

        // Check for errors in the response
        if (is_wp_error($response)) {
            return new WP_REST_Response(['error' => $response->get_error_message()], 500);
        }

        // Decode response body
        $responseBody = json_decode(wp_remote_retrieve_body($response), true);

        return wp_send_json_success($responseBody);
    }

    /**
     * Retrieves the current status of a scan from the external scan API using the scan ID.
     * 
     * Makes an HTTP GET request to the scan API to fetch the status of the scan.
     * 
     * @param string $scanId The scan ID to check the status of.
     * 
     * @return array|\WP_Error The scan status data from the external API, or a
     *                         WP_Error on a network error, a non-2xx reply or a
     *                         body that is not a JSON object.
     */
    private function get_scan_status($scanId)
    {
        // API endpoint to check the scan status
        $apiEndpoint = self::$_apiBase . "/status.php?scan_id=" . urlencode($scanId);

        // Make an HTTP GET request
        $response = wp_remote_get($apiEndpoint, [
            'timeout'   => 200, // Set timeout (adjust as needed)
            // Verify the certificate, except against the local dev API (NX_DEBUG).
            'sslverify' => ! ( defined('NX_DEBUG') && NX_DEBUG ),
        ]);

        // Check for errors in the response
        if (is_wp_error($response)) {
            return $response;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return new \WP_Error('nx_scan_status_http', 'Unexpected HTTP status from the cookie scanner API.', ['status' => $code]);
        }

        // Decode response body
        $responseBody = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($responseBody)) {
            return new \WP_Error('nx_scan_status_body', 'The cookie scanner API reply is not a JSON object.');
        }
        return $responseBody;
    }


    // Helper function to categorize cookies and calculate category count
    public function categorizeCookiesAndCount($cookies) {
        // Define cookie categories
        $cookieCategoryPrefix = [
            'necessary' => ['PHPSESSID', 'wordpress_logged_in', 'wp-settings', 'wp-settings-time', 'wpEmojiSettingsSupports', 'cookieyes-consent', 'elementor', 'csrftoken', 'auth', 'session', 'secure', 'cart', 'checkout', 'wp_woocommerce'],
            'functional' => ['lang', 'preferences', 'remember_me', 'theme', 'consent', 'locale', 'user_settings', 'cookie_preference'],
            'analytics' => ['_ga', '_gid', '_gat', 'fbp', 'utm', 'amplitude', 'mixpanel', 'hotjar', 'segment', 'ahoy', 'kissmetrics', 'analytics', 'visitor_id', 'sbjs_udata', 'sbjs_current', 'sbjs_first', 'sbjs_first_add', 'sbjs_current_add'],
            'performance' => ['_hj', 'cf_use_ob', 'cf_clearance', 'AWSALB', 'load_balancer', 'page_speed', 'cdn_cache', 'pingdom', 'new_relic'],
            'advertisement' => ['ads', '_fbp', '_gcl', '_dc_gtm', 'doubleclick', 'IDE', 'adroll', 'criteo', 'twitter_ads', 'bing_ads', 'remarketing', 'test_cookie'],
        ];

        // Initialize category counts
        $categorized = [
            'necessary'     => 0,
            'functional'    => 0,
            'analytics'     => 0,
            'performance'   => 0,
            'advertisement' => 0,
        ];

        // Categorize cookies based on partial matching of cookie names (prefixes)
        if (!empty($cookies) && is_array($cookies)) {
            foreach ($cookies as $cookie) {
                foreach ($cookieCategoryPrefix as $category => $cookieNames) {
                    foreach ($cookieNames as $cookieName) {
                        if (isset($cookie['name']) && strpos($cookie['name'], $cookieName) !== false) { // Check if cookie contains the prefix
                            $categorized[$category]++;
                            break; // No need to check other prefixes for this cookie
                        }
                    }
                }
            }
        }

        // Calculate the category count (only categories with count > 0)
        $categoryCount = count(array_filter($categorized, function($count) {
            return $count > 0;
        }));

        return [
            'category_count' => $categoryCount,
            'categorized' => $categorized
        ];
    }

}
?>
