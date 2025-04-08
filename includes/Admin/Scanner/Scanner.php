<?php
namespace NotificationX\Admin\Scanner;

use NotificationX\Admin\Entries;
use NotificationX\Core\Database;
use NotificationX\Core\Helper;
use NotificationX\Core\Limiter;
use NotificationX\Core\PostType;
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
    private static $_apiBase   = "https://api.notificationx.com/cookie-scanner/v1";
    // private static $_apiBase   = "https://notificationx-api.test/cookie-scanner/v1";
    private static $is_pro     = false;
    public function __construct() 
    {
        add_action('rest_api_init', [$this, 'rest_init']);
        if( NotificationX::is_pro() ) {
            self::$is_pro = true;
        }
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
            'permission_callback' => '__return_true',
        ));

        // Register the scan status endpoint
        register_rest_route($namespace, '/scan/status', array(
            'methods'   => WP_REST_Server::EDITABLE,
            'callback'  => array($this, 'check_scan_status'),
            'permission_callback' => '__return_true',
        ));

            
        // Register the scan history endpoint
        register_rest_route($namespace, '/scan/history', array(
            'methods'   => WP_REST_Server::EDITABLE,
            'callback'  => array($this, 'get_scan_history'),
            'permission_callback' => '__return_true',
        ));
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
        $scanId = $request->get_param('scan_id');
        $nx_id = $request->get_param('nx_id');

        // Retrieve the scan status from the database (implement this function as needed)
        $status = $this->get_scan_status($scanId);

        // Handle invalid scan ID
        if ($status === null) {
            return new WP_REST_Response(['error' => 'Invalid scan ID'], 404);
        }

        // Process the scan result if the status is 'completed'
        if (!empty($status['status']) && $status['status'] === 'completed') {
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

            // Prepare stats entry with the category count
            if (!empty($stats) && is_array($stats)) {
                $stats['category_count'] = $categoryCount;
            }
            if (!empty($stats) && is_array($stats)) {
                $stats['categorized'] = $categorized;
            }
            if( empty( $nx_id ) ) {
                return new WP_REST_Response(['data' => $status], 200);
            }
            
            // If cookies or stats exist, prepare them for database insertion
            if ((!empty($cookies) && is_array($cookies)) || (!empty($stats) && is_array($stats))) {
                $entriesToInsert = [];

                // Prepare cookies entry
                if (!empty($cookies) && is_array($cookies)) {
                    $entriesToInsert[] = [
                        'nx_id'      => $nx_id,
                        'source'     => 'gdpr_notification',
                        'entry_key'  => $scanId . '_cookies',
                        'data'       => $cookies,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                }

                // Prepare stats entry
                if (!empty($stats) && is_array($stats)) {
                    $entriesToInsert[] = [
                        'nx_id'      => $nx_id,
                        'source'     => 'gdpr_notification',
                        'entry_key'  => $scanId . '_stats',
                        'data'       => $stats,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                }

                // Insert data into the database
                foreach ($entriesToInsert as $entry) {
                    // Check if an entry already exists
                    $isExists = Database::get_instance()->get_posts(
                        Database::$table_entries, 'count(*)', [
                            'nx_id'     => $nx_id,
                            'source'    => 'gdpr_notification',
                            'entry_key' => $entry['entry_key'],
                        ]
                    );

                    if (empty($isExists[0]['count(*)'])) {
                        $post = PostType::get_instance()->get_post($nx_id);
                        $canEntry = apply_filters("nx_can_entry_gdpr_notification", true, $entry, $post);
                        if ($canEntry) {
                            Limiter::get_instance()->remove($nx_id, 1);
                            Entries::get_instance()->insert_entry($entry);
                        }
                    }
                }
            }
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

    /**
     * Retrieves the current status of a scan from the external scan API using the scan ID.
     * 
     * Makes an HTTP GET request to the scan API to fetch the status of the scan.
     * 
     * @param string $scanId The scan ID to check the status of.
     * 
     * @return array The scan status data from the external API.
     */
    private function get_scan_status($scanId)
    {
        // API endpoint to check the scan status
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


    /**
     * fetch scan history.
     *
     * @param WP_REST_Request $request
     * @return void
    */
    public function get_scan_history(WP_REST_Request $request)
    {
        $nxId = $request->get_param('nx_id');

        if (empty($nxId)) {
            return new WP_REST_Response(['error' => 'Missing nx_id parameter'], 400);
        }

        // Fetch the scan history entry from the database
        $history = Entries::get_instance()->get_entries([
            'nx_id'     => absint( $nxId ),
            'source'    => 'gdpr_notification',
        ], '*', '', '', true);

        // Separate _cookie and _stats entries
        $cookies = [];
        $stats = [];

        

        foreach ($history as $entry) {
            if (isset($entry['entry_key'])) {
                if (strpos($entry['entry_key'], '_cookie') !== false) {
                    $cookies[$entry['entry_key']] = $entry; // Store cookies by entry_key
                } elseif (strpos($entry['entry_key'], '_stats') !== false) {
                    $stats[$entry['entry_key']] = $entry; // Store stats by entry_key
                }
            }
        }

        // Merge _stats into corresponding _cookie entry
        foreach ($stats as $stats_key => $stats_entry) {
            $cookie_key = str_replace('_stats', '_cookies', $stats_key); // Find corresponding _cookie key
            if (isset($cookies[$cookie_key])) {
                $cookies[$cookie_key]['stats'] = !empty($stats_entry['data']) ? $stats_entry['data'] : []; // Ensure it's an array
            }
        }
        // Convert merged cookies to array values
        $merged_history = array_values($cookies);

        if (empty($merged_history)) {
            return wp_send_json_success([
                'message' => __('No scan history found', 'notificationx'),
            ]);
        }

        return wp_send_json_success([
            'message' => __('Scanned fetch successfully', 'notificationx'),
            'data'    => json_encode($merged_history)
        ]);
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
