<?php

namespace NotificationX\Core;

use NotificationX\Extensions\GlobalFields;
use NotificationX\Types\TypeFactory;
use NotificationX\Admin\Settings;

/**
 * This class will provide all kind of helper methods.
 */
class Helper {
    /**
     * Get all post types
     *
     * @param array $exclude
     * @return array
     */
    public static function post_types($exclude = array()) {
        $post_types = get_post_types(array(
            'public'    => true,
            'show_ui'    => true
        ), 'objects');

        unset($post_types['attachment']);

        if (count($exclude)) {
            foreach ($exclude as $type) {
                if (isset($post_types[$type])) {
                    unset($post_types[$type]);
                }
            }
        }

        return apply_filters('nx_post_types', $post_types);
    }

    /**
     * Get all taxonomies
     *
     * @param string $post_type
     * @param array $exclude
     * @return array
     */
    public static function taxonomies($post_type = '', $exclude = array()) {
        if (empty($post_type)) {
            $taxonomies = get_taxonomies(
                array(
                    'public'       => true,
                    '_builtin'     => false
                ),
                'objects'
            );
        } else {
            $taxonomies = get_object_taxonomies($post_type, 'objects');
        }

        $data = array();
        if (is_array($taxonomies)) {
            foreach ($taxonomies as $tax_slug => $tax) {
                if (!$tax->public || !$tax->show_ui) {
                    continue;
                }
                if (in_array($tax_slug, $exclude)) {
                    continue;
                }
                $data[$tax_slug] = $tax;
            }
        }
        return apply_filters('nx_loop_taxonomies', $data, $taxonomies, $post_type);
    }

    /**
     * This function is responsible for the data sanitization
     *
     * @param array $field
     * @param string|array $value
     * @return string|array
     */
    public static function sanitize_field($field, $value) {
        if (isset($field['sanitize']) && !empty($field['sanitize'])) {
            if (function_exists($field['sanitize'])) {
                $value = call_user_func($field['sanitize'], $value);
            }
            return $value;
        }

        if (is_array($field) && isset($field['type'])) {
            switch ($field['type']) {
                case 'text':
                    $value = sanitize_text_field($value);
                    break;
                case 'textarea':
                    $value = sanitize_textarea_field($value);
                    break;
                case 'email':
                    $value = sanitize_email($value);
                    break;
                default:
                    return $value;
                    break;
            }
        } else {
            $value = sanitize_text_field($value);
        }

        return $value;
    }


    /**
     * Sorting Data
     * by their type
     *
     * @param array $value
     * @param string $key
     * @return void
     */
    public static function sortBy(&$value, $key = 'comments') {
        switch ($key) {
            case 'comments':
                return self::sorter($value, 'key', 'DESC');
                break;
            default:
                return self::sorter($value, 'timestamp', 'DESC');
                break;
        }
    }

    /**
     * This function is responsible for making an array sort by their key
     * @param array $data
     * @param string $using
     * @param string $way
     * @return array
     */
    public static function sorter($data, $using = 'time_date',  $way = 'DESC') {
        if (!is_array($data)) {
            return $data;
        }
        $new_array = [];
        if ($using === 'key') {
            if ($way !== 'ASC') {
                krsort($data);
            } else {
                ksort($data);
            }
        } else {
            foreach ($data as $key => $value) {
                if (!is_array($value)) continue;
                foreach ($value as $inner_key => $single) {
                    if ($inner_key == $using) {
                        $value['tempid'] = $key;
                        $single = self::numeric_key_gen($new_array, $single);
                        $new_array[$single] = $value;
                    }
                }
            }

            if ($way !== 'ASC') {
                krsort($new_array);
            } else {
                ksort($new_array);
            }

            if (!empty($new_array)) {
                foreach ($new_array as $array) {
                    $index = $array['tempid'];
                    unset($array['tempid']);
                    $new_data[$index] = $array;
                }
                $data = $new_data;
            }
        }

        return $data;
    }

    /**
     * This function is responsible for generate unique numeric key for a given array.
     *
     * @param array $data
     * @param integer $index
     * @return integer
     */
    protected static function numeric_key_gen($data, $index = 0) {
        if (isset($data[$index])) {
            $index += 1;
            return self::numeric_key_gen($data, $index);
        }
        return $index;
    }




    /**
     * Contact Forms Key Name filter for Name Selectbox
     * @since 1.4.*
     * @param string
     * @return boolean
     */
    public static function filter_contactform_key_names($name) {
        $validKey = true;
        $filterWords = array(
            "checkbox",
            "color",
            "date",
            "datetime-local",
            "file",
            "image",
            "month",
            "number",
            "password",
            "radio",
            "range",
            "reset",
            "submit",
            "tel",
            "time",
            "week",
            "Comment",
            "message",
            "address",
            "phone",
        );
        foreach ($filterWords as $word) {
            if (!empty($name) && stripos($name, $word) === false) {
                $validKey = true;
            } else {
                $validKey = false;
                break;
            }
        }
        return $validKey;
    }

    /**
     * Contact Forms Key Name remove special characters and meaningless words for Name Selectbox
     * @since 1.4.*
     * @param string
     * @return string
     */
    public static function rename_contactform_key_names($name) {
        $result = preg_split("/[_,\-]+/", $name);
        $returnName = ucfirst($result[0]);
        return $returnName;
    }


    /**
     * Formating Number in a Nice way
     * @since 1.2.1
     * @param int|string $n
     * @return string
     */
    public static function nice_number($n) {
        $temp_number = !empty( $n ) ? str_replace(",", "", $n) : '';
        if (!empty($temp_number)) {
            $n = (0 + (int) $temp_number);
        } else {
            $n = (int) $n;
        }
        if (!is_numeric($n)) return 0;
        $is_neg = false;
        if ($n < 0) {
            $is_neg = true;
            $n = abs($n);
        }
        $number = 0;
        $suffix = '';
        switch (true) {
            case $n >= 1000000000000:
                $number = ($n / 1000000000000);
                $suffix = $n > 1000000000000 ? 'T+' : 'T';
                break;
            case $n >= 1000000000:
                $number = ($n / 1000000000);
                $suffix = $n > 1000000000 ? 'B+' : 'B';
                break;
            case $n >= 1000000:
                $number = ($n / 1000000);
                $suffix = $n > 1000000 ? 'M+' : 'M';
                break;
            case $n >= 1000:
                $number = ($n / 1000);
                $suffix = $n > 1000 ? 'K+' : 'K';
                break;
            default:
                $number = $n;
                break;
        }
        if (strpos($number, '.') !== false && strpos($number, '.') >= 0) {
            $number = number_format($number, 1);
        }
        return ($is_neg ? '-' : '') . $number . $suffix;
    }

    public static function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }

    public static function get_theme_or_plugin_list($api_data = null) {
        $data = array();
        $new_data = array();

        $needed_key = array('slug', 'title', 'installs_count', 'active_installs_count', 'free_releases_count', 'premium_releases_count', 'total_purchases', 'total_subscriptions', 'total_renewals', 'accepted_payments', 'id', 'created', 'icon');

        if (!empty($api_data->plugins)) {
            foreach ($api_data->plugins as $single_data) {
                $type = $single_data->type;
                foreach ($needed_key as $key) {
                    if ($key == 'created') {
                        if (isset($single_data->$key)) {
                            $new_data['timestamp'] = strtotime($single_data->$key);
                        }
                        continue;
                    }
                    if (isset($single_data->$key)) {
                        $new_data[$key] = $single_data->$key;
                    }
                }
                $data[$type . 's'][$new_data['id']] = $new_data;
                $new_data = array();
            }
        }

        return $data;
    }

    public static function today_to_last_week($data) {
        if (empty($data)) {
            return array();
        }
        $new_data = array();
        $timestamp = current_time('timestamp');
        $date = date('Y-m-d', $timestamp);
        $date_7_days_back = date('Y-m-d', strtotime($date . ' -8 days'));
        $counter_7days = 0;
        $counter_todays = 0;
        foreach ($data as $single_install) {
            date('Y-m-d', strtotime($single_install->created)) > $date_7_days_back ? $counter_7days++ : $counter_7days;
            date('Y-m-d', strtotime($single_install->created)) == $date ? $counter_todays++ : $counter_todays;
        }
        return array(
            'last_week' => $counter_7days,
            'today'     => $counter_todays,
        );
    }

    public static function current_timestamp( $date = null, $timezone = 'UTC' ){
        $timezone = new \DateTimeZone( $timezone );
        $datetime = new \DateTime($date, $timezone);
        return $datetime->getTimestamp();
    }

    public static function current_time($timestamp = null) {
        $type = 'Y-m-d H:i:s';
        if (empty($timestamp)) {
            $timestamp = time();
        }

        $timezone = new \DateTimeZone('UTC');
        if (is_numeric($timestamp)) {
            $datetime = new \DateTime();
            $datetime->setTimezone($timezone);
            $datetime->setTimestamp($timestamp);
        }
        else{
            $datetime = new \DateTime($timestamp);
            $datetime->setTimezone($timezone);
        }
        return $datetime->format($type);
    }

    public static function get_utc_time($timestamp = null) {
        $type = 'Y-m-d H:i:s';
        if (empty($timestamp)) {
            $timestamp = time();
        }

        // Get the WP timezone as a DateTimeZone object
        $wp_timezone = wp_timezone();
        $timezone = new \DateTimeZone('UTC');

        if (is_numeric($timestamp)) {
            $datetime = new \DateTime(null, $wp_timezone);
            $datetime->setTimezone($timezone);
            $datetime->setTimestamp($timestamp);
        }
        else{
            $datetime = new \DateTime($timestamp, $wp_timezone);
            $datetime->setTimezone($timezone);
        }
        return $datetime->format($type);
    }

    public static function mysql_time($timestamp = null) {
        $type = 'Y-m-d H:i:s';
        if (empty($timestamp)) {
            $timestamp = time();
        }

        if (is_numeric($timestamp)) {
            $datetime = new \DateTime();
            $datetime->setTimestamp($timestamp);
        }
        else{
            $datetime = new \DateTime($timestamp);
        }
        return $datetime->format($type);
    }

    /**
     * Generating Full Name with one letter from last name
     * @since 1.3.9
     * @param string $first_name
     * @param string $last_name
     * @return string
     */
    public static function name($first_name = '', $last_name = '') {
        $name = $first_name;
        $name .= !empty($last_name) ? ' ' . mb_substr($last_name, 0, 1) : '';
        return $name;
    }

    public static function get_type_title( $type ){
        $_type = TypeFactory::get_instance()->get($type);
        return ! empty( $_type->title ) ?  $_type->title : $type;
    }

    public static function is_plugin_installed( $plugin ){
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugins = get_plugins();
        return isset( $plugins[ $plugin ] );
    }

    public static function is_plugin_active( $plugin ) {
        return in_array( $plugin, (array) get_option( 'active_plugins', array() ), true ) || self::is_plugin_active_for_network( $plugin );
    }

    public static function is_plugin_active_for_network( $plugin ) {
        if ( ! is_multisite() ) {
            return false;
        }

        $plugins = get_site_option( 'active_sitewide_plugins' );
        if ( isset( $plugins[ $plugin ] ) ) {
            return true;
        }

        return false;
    }
    public static function remove_old_notice(){
        global $wp_filter;
        if( isset( $wp_filter['admin_notices']->callbacks[10] ) && is_array( $wp_filter['admin_notices']->callbacks[10] ) ) {
            foreach( $wp_filter['admin_notices']->callbacks[10] as $hash => $callbacks ) {
                if( is_array( $callbacks['function'] ) && ! empty( $callbacks['function'][0] ) && is_object( $callbacks['function'][0] ) && $callbacks['function'][0] instanceof \NotificationX_Licensing ) {
                    remove_action( 'admin_notices', $hash );
                    break;
                }
            }
        }
    }

    public static function remote_get($url, $args = array(), $raw = false, $assoc = null) {
        $defaults = array(
            'timeout'     => 20,
            'redirection' => 5,
            'httpversion' => '1.1',
            'user-agent'  => 'NotificationX/' . NOTIFICATIONX_VERSION . '; ' . home_url(),
            'body'        => null,
            'sslverify'   => false,
            'stream'      => false,
            'filename'    => null
        );
        $args = wp_parse_args($args, $defaults);
        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return false;
        }
        if($raw){
            return $response;
        }

        $body      = wp_remote_retrieve_body( $response );
        $response  = json_decode($body, $assoc);
        $_response = (array) $response;
        if (isset($_response['status']) && $_response['status'] == 'fail') {
            return false;
        }
        return $response;
    }

    /**
     * Get File Modification Time or URL
     *
     * @param string $file  File relative path for Admin
     * @param boolean $url  true for URL return
     * @return void|string|integer
     */
    public static function file( $file, $url = false ){
        $base = '';
        if(defined('NX_DEBUG') && NX_DEBUG){
            if( $url ) {
                $base = NOTIFICATIONX_DEV_ASSETS;
            }
            else{
                $base = NOTIFICATIONX_DEV_ASSETS_PATH;
            }
            if(!file_exists(path_join(NOTIFICATIONX_DEV_ASSETS_PATH, $file))){
                $base = '';
            }
        }
        if(empty($base)){
            if( $url ) {
                $base = NOTIFICATIONX_ASSETS;
            }
            else{
                $base = NOTIFICATIONX_ASSETS_PATH;
            }
        }
        return path_join($base, $file);
    }


    /**
     * This function returns an array of post titles by searching the post type and the input value
     *
     * @param string $post_type The post type to search
     * @param string|array $inputValue The input value to search by title or ID
     * @param integer $numberposts The number of posts to return
     * @return array An associative array of post IDs and titles
     */
    public static function get_post_titles_by_search($post_type, $inputValue = '', $numberposts = 10, $args = []) {
        global $wpdb;
        $product_list = [];
        $numberposts = intval( $numberposts );
        $args = wp_parse_args( $args, [
            'prefix' => '',
        ] );

        // Generate a unique cache key based on the input parameters
        $cache_key = 'get_post_titles_by_search_' . md5( $post_type . serialize( $inputValue ) . $numberposts );

        // Try to get the cached data from the object cache
        $cached_data = wp_cache_get( $cache_key );

        // If the cached data exists and is not expired, return it
        if ( false !== $cached_data ) {
            return $cached_data;
        }

        // Otherwise, run the original query
        // Start with the common part of the query
        $sql = "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'";
        $query_args = array( $post_type );

        if ( is_array( $inputValue ) && count( $inputValue ) ) {
            // If the input value is an array of post IDs, use IN clause with placeholders
            // Generate a string of placeholders like %d,%d,%d
            $placeholders = implode( ',', array_fill( 0, count( $inputValue ), '%d' ) );

            // Add the IN clause to the query
            $sql .= " AND ID IN ($placeholders)";

            // Merge the input values to the query arguments
            $query_args = array_merge( $query_args, array_map( 'intval', $inputValue ) );
        } else {
            // If the input value is a string, use LIKE clause with placeholder
            if ( ! empty( $inputValue ) ) {
                // Add the LIKE clause to the query
                $sql .= " AND post_title LIKE %s";

                // Add the input value to the query arguments with wildcards
                $query_args[] = '%' . $wpdb->esc_like( $inputValue ) . '%';
            }
        }

        // Add order and limit clauses
        $sql .= " ORDER BY post_date DESC LIMIT %d";

        // Add the number of posts to the query arguments
        $query_args[] = $numberposts;

        // Prepare and execute the query using wpdb methods
        $sql = $wpdb->prepare( $sql, $query_args );
        $products = $wpdb->get_results( $sql );

        if ( ! empty( $products ) ) {
            // Loop through the results and build the output array
            foreach ( $products as $product ) {
                $key = $args['prefix'] . $product->ID;
                $product_list[ $key ] = $product->post_title;
            }
        }

        // Store the query result in the object cache with an expiration time of one hour
        wp_cache_set( $cache_key, $product_list, '', MINUTE_IN_SECONDS );

        // Return the query result
        return $product_list;
    }

   /**
     * Checks if WPML (WordPress Multilingual Plugin) is properly set up and loaded.
     *
     * @return bool True if WPML is set up, false otherwise.
     */
    public static function is_wpml_setup()
    {
        $wpml_has_run = get_option('WPML(TM-has-run)');
        if (!empty($wpml_has_run['WPML\TM\ATE\Sitekey\Sync']) && did_action('wpml_loaded') && function_exists('load_wpml_st_basics')) {
            return true;
        }
        return false;
    }

    /**
     * Returns a list of common fields for GDPR cookie configuration settings.
     *
     * @return array An associative array of common GDPR cookie fields.
     */
    public static function gdpr_common_fields()
    {
        return [
            'enabled' => array(
                'type'     => 'toggle',
                'name'     => 'enabled',
                'label'    => __('Enabled', 'notificationx'),
                'priority' => 5,
            ), 
            'discovered' => array(
                'type'     => 'toggle',
                'name'     => 'discovered',
                'label'    => __('Discovered', 'notificationx'),
                'priority' => 5,
            ), 
            'cookies_id' => array(
                'type'     => 'text',
                'name'     => 'cookies_id',
                'label'    => __('Cookie ID', 'notificationx'),
                'priority' => 10,
            ), 
            'domain' => array(
                'type'     => 'text',
                'name'     => 'domain',
                'label'    => __('Domain', 'notificationx'),
                'priority' => 15,
            ), 
            'duration' => array(
                'type'        => 'number',
                'name'        => 'duration',
                'label'       => __('Duration', 'notificationx'),
                'min'         => 1,
                'priority'    => 20,
                'suggestions' => [
                    [
                        'value' => 30,
                        'unit'  => 'days',
                    ],
                    [
                        'value' => 90,
                        'unit'  => 'days',
                    ],
                    [
                        'value' => 180,
                        'unit'  => 'days',
                    ],
                    [
                        'value' => 365,
                        'unit'  => 'days',
                    ],
                ],
            ),
            'description' => array(
                'type'     => 'textarea',
                'name'     => 'description',
                'label'    => __('Description', 'notificationx-pro'),
                'priority' => 30,
            ), 
            'is_add_script' => array(
                'type'     => 'toggle',
                'name'     => 'is_add_script',
                'label'    => __('Add Script', 'notificationx'),
                'priority' => 35,
            ), 
            'load_inside' => array(
                'label'    => __('Add Script on', 'notificationx'),
                'name'     => 'product_control',
                'type'     => 'select',
                'priority' => 40,
                'default'  => 'head',
                'options'  => GlobalFields::get_instance()->normalize_fields([
                    'head'   => __('Header', 'notificationx'),
                    'body'   => __('Body', 'notificationx'),
                    'footer' => __('Footer', 'notificationx'),
                ]),
            ),
            'script_url_pattern' => array(
                'type'     => 'codeviewer',
                'name'     => 'script_url_pattern',
                'label'    => __('Script', 'notificationx-pro'),
                'priority' => 45,
            ), 
        ];
    }

    /**
     * Specifies the fields to be shown in the GDPR cookie list.
     *
     * @return array An array of field names visible in the GDPR cookie list.
     */
    public static function gdpr_cookie_list_visible_fields()
    {
        return ['cookies_id', 'domain', 'script_url_pattern', 'duration', 'load_inside','description'];
    }

    /**
     * Deletes specific cookies on the server and returns a list of removed cookies.
     *
     * @return void Outputs a JSON-encoded list of removed cookies.
     */
    public static function delete_server_cookies()
    {
        $urlparts = wp_parse_url(site_url('/'));
        $domain   = preg_replace('/www\./i', '', $urlparts['host']);
        $cookies_removed = array();
        $d_domains = array('_ga', '_fbp', '_gid', '_gat', '__utma', '__utmb', '__utmc', '__utmt', '__utmz');
        $d_domains = apply_filters('gdpr_d_domains_filter', $d_domains);

        // Iterate over all cookies and remove them if they match specific conditions.
        if (isset($_COOKIE) && is_array($_COOKIE) && $domain) :
            foreach ($_COOKIE as $key => $value) {
                if ($key !== 'moove_gdpr_popup' && strpos($key, 'woocommerce') === false && strpos($key, 'wc_') === false && strpos($key, 'wordpress') === false) : 
                    if ('language' === $key || 'currency' === $key) {
                        setcookie($key, null, -1, '/', 'www.' . $domain);
                        $cookies_removed[$key] = $domain;
                    } elseif (in_array($key, $d_domains) || strpos($key, '_ga') !== false || strpos($key, '_fbp') !== false) {
                        setcookie($key, null, -1, '/', '.' . $domain);
                        $cookies_removed[$key] = $domain;
                    }
                endif;
            }
        endif;

        // Parse and remove cookies from the HTTP header.
        $cookies = isset($_SERVER['HTTP_COOKIE']) ? explode(';', sanitize_text_field(wp_unslash($_SERVER['HTTP_COOKIE']))) : false;
        if (is_array($cookies)) :
            foreach ($cookies as $cookie) {
                $parts = explode('=', $cookie);
                $name  = trim($parts[0]);
                if ($name && $name !== 'moove_gdpr_popup' && strpos($name, 'woocommerce') === false && strpos($name, 'wc_') === false && strpos($name, 'wordpress') === false) :
                    setcookie($name, '', time() - 1000);
                    setcookie($name, '', time() - 1000, '/');
                    if ('language' === $name || 'currency' === $name) {
                        setcookie($name, null, -1, '/', 'www.' . $domain);
                        $cookies_removed[$name] = $domain;
                    } elseif (in_array($key, $d_domains) || strpos($name, '_ga') !== false || strpos($name, '_fbp') !== false) {
                        setcookie($name, null, -1, '/', '.' . $domain);
                        $cookies_removed[$name] = '.' . $domain;
                    } else {
                        setcookie($name, null, -1, '/');
                        $cookies_removed[$name] = $domain;
                    }
                endif;
            }
        endif;

        // Output the list of removed cookies as a JSON response.
        echo json_encode($cookies_removed);
    }
    
    public static function tab_info_title($name, $title_default, $modal = false) 
    {
        return [
            'type'    => 'text',
            'name'    => "{$name}_tab_title",
            'default' => $title_default,
            'label'   => __('Name', 'notificationx'),
            'autoFocus' => true,
        ];
    }

    public static function tab_info_desc($name, $desc_default, $modal = false) 
    {
        return [
            'type'    => 'textarea',
            'row'      => 3,
            'name'    => "{$name}_tab_desc",
            'default' => $desc_default,
            'label'   => __('Description', 'notificationx'),
        ];
    }


    public static function default_cookie_list() 
    {
        return [
            [
                'enabled'            => true,
                'default'            => true,
                'cookies_id'         => 'wordpress_logged_in',
                'load_inside'        => 'head',
                'script_url_pattern' => '',
                'description'        => __('Indicates when a user is logged in and who they are, for most interface use.','notificationx'),
                'index'              => wp_generate_uuid4(),
            ],
            [
                'enabled'            => true,
                'default'            => true,
                'cookies_id'         => 'wordpress_sec',
                'load_inside'        => 'head',
                'script_url_pattern' => '',
                'description'        => __('Used for security purposes for logged-in users.', 'notificationx'),
                'index'              => wp_generate_uuid4(),
            ],
            [
                'enabled'            => true,
                'default'            => true,
                'cookies_id'         => 'wp-settings-{user_id}',
                'load_inside'        => 'head',
                'script_url_pattern' => '',
                'description'        => __('Used to persist a user\'s WordPress admin settings.','notificationx'),
                'index'              => wp_generate_uuid4(),
            ],
            [
                'enabled'            => true,
                'default'            => true,
                'cookies_id'         => 'wp-settings-time-{user_id}',
                'load_inside'        => 'head',
                'script_url_pattern' => '',
                'description'        => __('Records the time that wp-settings-{user_id} was set.', 'notificationx'),
                'index'              => wp_generate_uuid4(),
            ],
            [
                'enabled'            => true,
                'default'            => true,
                'cookies_id'         => 'wp-settings-time-{user_id}',
                'load_inside'        => 'head',
                'script_url_pattern' => '',
                'description'        => __('Records the time that wp-settings-{user_id} was set.', 'notificationx'),
                'index'              => wp_generate_uuid4(),
            ],
            [
                'enabled'            => true,
                'default'            => true,
                'cookies_id'         => 'nx_cookie_manager',
                'script_url_pattern' => '',
                'description'        => __('Manages the cookies on the site, ensuring user consent for GDPR compliance.', 'notificationx'),
                'index'              => wp_generate_uuid4(),
            ],
        ];

    }

    // Helper function to get the image ID from data
    public static function get_image_id_from_settings($data) {
        return isset($data['image']['id']) ? $data['image']['id'] : null;
    }

    // Helper function to get custom image size
    public static function get_custom_image_size() {
        $default_size = '100_100'; // Default size
        $image_size = (string) Settings::get_instance()->get('settings.notification_image_size', $default_size);
        $image_size_parts = explode('_', $image_size);

        if (!empty($image_size_parts[0]) && is_numeric($image_size_parts[0]) && !empty($image_size_parts[1]) && is_numeric($image_size_parts[1])) {
            return [
                'width'  => (int) $image_size_parts[0],
                'height' => (int) $image_size_parts[1],
            ];
        }

        return [
            'width'  => 100, // Default width
            'height' => 100, // Default height
        ];
    }

    // Helper function to get resized image URL
    public static function get_resized_image_url($image_id, $custom_size) {
        if (!$image_id || empty($custom_size['width']) || empty($custom_size['height'])) {
            return null;
        }

        $image = wp_get_attachment_image_src(
            $image_id,
            [$custom_size['width'], $custom_size['height']],
            true // Crop the image to exact dimensions
        );

        return $image && isset($image[0]) ? $image[0] : null;
    }

    public static function nx_allowed_html()
    {
        return [
            'a' => [
                'href' => [],
                'title' => [],
                'target' => [],
                'rel' => [],
                'class' => [],
                'id' => [],
            ],
            'abbr' => [
                'title' => [],
                'class' => [],
            ],
            'style' => [],
            'b' => [
                'class' => [],
            ],
            'blockquote' => [
                'cite' => [],
                'class' => [],
            ],
            'br' => [],
            'cite' => [
                'class' => [],
            ],
            'code' => [
                'class' => [],
            ],
            'del' => [
                'datetime' => [],
                'class' => [],
            ],
            'div' => [
                'class' => [],
                'id' => [],
                'style' => [],
            ],
            'em' => [
                'class' => [],
            ],
            'h1' => [
                'class' => [],
                'id' => [],
            ],
            'h2' => [
                'class' => [],
                'id' => [],
            ],
            'h3' => [
                'class' => [],
                'id' => [],
            ],
            'h4' => [
                'class' => [],
                'id' => [],
            ],
            'h5' => [
                'class' => [],
                'id' => [],
            ],
            'h6' => [
                'class' => [],
                'id' => [],
            ],
            'hr' => [
                'class' => [],
            ],
            'i' => [
                'class' => [],
            ],
            'img' => [
                'src' => [],
                'alt' => [],
                'title' => [],
                'width' => [],
                'height' => [],
                'class' => [],
                'id' => [],
            ],
            'li' => [
                'class' => [],
            ],
            'ol' => [
                'class' => [],
            ],
            'p' => [
                'class' => [],
                'style' => [],
            ],
            'pre' => [
                'class' => [],
            ],
            'q' => [
                'cite' => [],
                'class' => [],
            ],
            'span' => [
                'class' => [],
                'style' => [],
            ],
            'strong' => [
                'class' => [],
            ],
            'table' => [
                'class' => [],
                'style' => [],
            ],
            'tbody' => [
                'class' => [],
            ],
            'td' => [
                'colspan' => [],
                'rowspan' => [],
                'class' => [],
                'style' => [],
            ],
            'tfoot' => [
                'class' => [],
            ],
            'th' => [
                'colspan' => [],
                'rowspan' => [],
                'scope' => [],
                'class' => [],
                'style' => [],
            ],
            'thead' => [
                'class' => [],
            ],
            'tr' => [
                'class' => [],
            ],
            'ul' => [
                'class' => [],
            ],
        ];        
    }
    public static function generate_time_string($data) {
        $timeString = '';
        if (isset($data['display_from']) && intval($data['display_from']) > 0) {
            $timeString .= intval($data['display_from']) . ' days ';
        }

        if (isset($data['display_from_hour']) && intval($data['display_from_hour']) > 0) {
            $timeString .= intval($data['display_from_hour']) . ' hours ';
        }

        if (isset($data['display_from_minute']) && intval($data['display_from_minute']) > 0) {
            $timeString .= intval($data['display_from_minute']) . ' minutes ';
        }

        if (!empty($timeString)) {
            $time = strtotime($timeString . ' ago');
        } else {
            $time = time(); // Default to current time if no valid inputs
        }
        return $time;
    }

    /**
     * Get the current datetime based on the WordPress site's timezone.
     *
     * @return string Formatted datetime in 'Y-m-d H:i:s' format.
     */
    public static function nx_get_current_datetime() {
        // Get the WordPress timezone setting
        $timezone = get_option('timezone_string');

        if (!$timezone) {
            // If timezone_string is empty, fallback to gmt_offset
            $gmt_offset = get_option('gmt_offset');

            if ($gmt_offset !== false) {
                $timezone = timezone_name_from_abbr("", (int) $gmt_offset * 3600, false);
            }

            // If timezone_name_from_abbr fails, manually handle GMT offsets
            if (!$timezone) {
                $timezone = sprintf('Etc/GMT%+d', -$gmt_offset); // Example: GMT+6 → Etc/GMT-6
            }
        }

        try {
            $date = new \DateTime('now', new \DateTimeZone($timezone));
            return $date->format('Y-m-d H:i:s'); // Format as MySQL datetime
        } catch (\Exception $e) {
            // If an error occurs, return UTC time as a fallback
            return gmdate('Y-m-d H:i:s');
        }
    }

    public static function nx_get_visitor_country_code() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // Prevent localhost IP from erroring
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'all'; // default fallback for local testing
        }

        $response = wp_remote_get("http://ip-api.com/json/{$ip}?fields=countryCode");

        if (is_wp_error($response)) {
            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        return isset($data['countryCode']) ? $data['countryCode'] : null;
    }

    public static function nx_get_all_country() {
        return [
            'all' => __('All Countries', 'notificationx'),
            'AF'  => __('Afghanistan', 'notificationx'),
            'AL'  => __('Albania', 'notificationx'),
            'DZ'  => __('Algeria', 'notificationx'),
            'AS'  => __('American Samoa', 'notificationx'),
            'AD'  => __('Andorra', 'notificationx'),
            'AO'  => __('Angola', 'notificationx'),
            'AI'  => __('Anguilla', 'notificationx'),
            'AQ'  => __('Antarctica', 'notificationx'),
            'AG'  => __('Antigua and Barbuda', 'notificationx'),
            'AR'  => __('Argentina', 'notificationx'),
            'AM'  => __('Armenia', 'notificationx'),
            'AW'  => __('Aruba', 'notificationx'),
            'AU'  => __('Australia', 'notificationx'),
            'AT'  => __('Austria', 'notificationx'),
            'AZ'  => __('Azerbaijan', 'notificationx'),
            'BS'  => __('Bahamas', 'notificationx'),
            'BH'  => __('Bahrain', 'notificationx'),
            'BD'  => __('Bangladesh', 'notificationx'),
            'BB'  => __('Barbados', 'notificationx'),
            'BY'  => __('Belarus', 'notificationx'),
            'BE'  => __('Belgium', 'notificationx'),
            'BZ'  => __('Belize', 'notificationx'),
            'BJ'  => __('Benin', 'notificationx'),
            'BM'  => __('Bermuda', 'notificationx'),
            'BT'  => __('Bhutan', 'notificationx'),
            'BO'  => __('Bolivia', 'notificationx'),
            'BA'  => __('Bosnia and Herzegovina', 'notificationx'),
            'BW'  => __('Botswana', 'notificationx'),
            'BR'  => __('Brazil', 'notificationx'),
            'BN'  => __('Brunei', 'notificationx'),
            'BG'  => __('Bulgaria', 'notificationx'),
            'BF'  => __('Burkina Faso', 'notificationx'),
            'BI'  => __('Burundi', 'notificationx'),
            'KH'  => __('Cambodia', 'notificationx'),
            'CM'  => __('Cameroon', 'notificationx'),
            'CA'  => __('Canada', 'notificationx'),
            'CV'  => __('Cape Verde', 'notificationx'),
            'CF'  => __('Central African Republic', 'notificationx'),
            'TD'  => __('Chad', 'notificationx'),
            'CL'  => __('Chile', 'notificationx'),
            'CN'  => __('China', 'notificationx'),
            'CO'  => __('Colombia', 'notificationx'),
            'KM'  => __('Comoros', 'notificationx'),
            'CG'  => __('Congo (Brazzaville)', 'notificationx'),
            'CD'  => __('Congo (Kinshasa)', 'notificationx'),
            'CR'  => __('Costa Rica', 'notificationx'),
            'HR'  => __('Croatia', 'notificationx'),
            'CU'  => __('Cuba', 'notificationx'),
            'CY'  => __('Cyprus', 'notificationx'),
            'CZ'  => __('Czech Republic', 'notificationx'),
            'DK'  => __('Denmark', 'notificationx'),
            'DJ'  => __('Djibouti', 'notificationx'),
            'DM'  => __('Dominica', 'notificationx'),
            'DO'  => __('Dominican Republic', 'notificationx'),
            'EC'  => __('Ecuador', 'notificationx'),
            'EG'  => __('Egypt', 'notificationx'),
            'SV'  => __('El Salvador', 'notificationx'),
            'GQ'  => __('Equatorial Guinea', 'notificationx'),
            'ER'  => __('Eritrea', 'notificationx'),
            'EE'  => __('Estonia', 'notificationx'),
            'ET'  => __('Ethiopia', 'notificationx'),
            'FJ'  => __('Fiji', 'notificationx'),
            'FI'  => __('Finland', 'notificationx'),
            'FR'  => __('France', 'notificationx'),
            'GA'  => __('Gabon', 'notificationx'),
            'GM'  => __('Gambia', 'notificationx'),
            'GE'  => __('Georgia', 'notificationx'),
            'DE'  => __('Germany', 'notificationx'),
            'GH'  => __('Ghana', 'notificationx'),
            'GR'  => __('Greece', 'notificationx'),
            'GD'  => __('Grenada', 'notificationx'),
            'GT'  => __('Guatemala', 'notificationx'),
            'GN'  => __('Guinea', 'notificationx'),
            'GW'  => __('Guinea-Bissau', 'notificationx'),
            'GY'  => __('Guyana', 'notificationx'),
            'HT'  => __('Haiti', 'notificationx'),
            'HN'  => __('Honduras', 'notificationx'),
            'HK'  => __('Hong Kong', 'notificationx'),
            'HU'  => __('Hungary', 'notificationx'),
            'IS'  => __('Iceland', 'notificationx'),
            'IN'  => __('India', 'notificationx'),
            'ID'  => __('Indonesia', 'notificationx'),
            'IR'  => __('Iran', 'notificationx'),
            'IQ'  => __('Iraq', 'notificationx'),
            'IE'  => __('Ireland', 'notificationx'),
            'IL'  => __('Israel', 'notificationx'),
            'IT'  => __('Italy', 'notificationx'),
            'JM'  => __('Jamaica', 'notificationx'),
            'JP'  => __('Japan', 'notificationx'),
            'JO'  => __('Jordan', 'notificationx'),
            'KZ'  => __('Kazakhstan', 'notificationx'),
            'KE'  => __('Kenya', 'notificationx'),
            'KI'  => __('Kiribati', 'notificationx'),
            'KR'  => __('Korea, South', 'notificationx'),
            'KW'  => __('Kuwait', 'notificationx'),
            'KG'  => __('Kyrgyzstan', 'notificationx'),
            'LA'  => __('Laos', 'notificationx'),
            'LV'  => __('Latvia', 'notificationx'),
            'LB'  => __('Lebanon', 'notificationx'),
            'LS'  => __('Lesotho', 'notificationx'),
            'LR'  => __('Liberia', 'notificationx'),
            'LY'  => __('Libya', 'notificationx'),
            'LI'  => __('Liechtenstein', 'notificationx'),
            'LT'  => __('Lithuania', 'notificationx'),
            'LU'  => __('Luxembourg', 'notificationx'),
            'MG'  => __('Madagascar', 'notificationx'),
            'MW'  => __('Malawi', 'notificationx'),
            'MY'  => __('Malaysia', 'notificationx'),
            'MV'  => __('Maldives', 'notificationx'),
            'ML'  => __('Mali', 'notificationx'),
            'MT'  => __('Malta', 'notificationx'),
            'MH'  => __('Marshall Islands', 'notificationx'),
            'MR'  => __('Mauritania', 'notificationx'),
            'MU'  => __('Mauritius', 'notificationx'),
            'MX'  => __('Mexico', 'notificationx'),
            'FM'  => __('Micronesia', 'notificationx'),
            'MD'  => __('Moldova', 'notificationx'),
            'MC'  => __('Monaco', 'notificationx'),
            'MN'  => __('Mongolia', 'notificationx'),
            'ME'  => __('Montenegro', 'notificationx'),
            'MA'  => __('Morocco', 'notificationx'),
            'MZ'  => __('Mozambique', 'notificationx'),
            'MM'  => __('Myanmar (Burma)', 'notificationx'),
            'NA'  => __('Namibia', 'notificationx'),
            'NR'  => __('Nauru', 'notificationx'),
            'NP'  => __('Nepal', 'notificationx'),
            'NL'  => __('Netherlands', 'notificationx'),
            'NZ'  => __('New Zealand', 'notificationx'),
            'NI'  => __('Nicaragua', 'notificationx'),
            'NE'  => __('Niger', 'notificationx'),
            'NG'  => __('Nigeria', 'notificationx'),
            'MK'  => __('North Macedonia', 'notificationx'),
            'NO'  => __('Norway', 'notificationx'),
            'OM'  => __('Oman', 'notificationx'),
            'PK'  => __('Pakistan', 'notificationx'),
            'PW'  => __('Palau', 'notificationx'),
            'PA'  => __('Panama', 'notificationx'),
            'PG'  => __('Papua New Guinea', 'notificationx'),
            'PY'  => __('Paraguay', 'notificationx'),
            'PE'  => __('Peru', 'notificationx'),
            'PH'  => __('Philippines', 'notificationx'),
            'PL'  => __('Poland', 'notificationx'),
            'PT'  => __('Portugal', 'notificationx'),
            'QA'  => __('Qatar', 'notificationx'),
            'RO'  => __('Romania', 'notificationx'),
            'RU'  => __('Russia', 'notificationx'),
            'RW'  => __('Rwanda', 'notificationx'),
            'SA'  => __('Saudi Arabia', 'notificationx'),
            'SN'  => __('Senegal', 'notificationx'),
            'RS'  => __('Serbia', 'notificationx'),
            'SC'  => __('Seychelles', 'notificationx'),
            'SL'  => __('Sierra Leone', 'notificationx'),
            'SG'  => __('Singapore', 'notificationx'),
            'SK'  => __('Slovakia', 'notificationx'),
            'SI'  => __('Slovenia', 'notificationx'),
            'SB'  => __('Solomon Islands', 'notificationx'),
            'SO'  => __('Somalia', 'notificationx'),
            'ZA'  => __('South Africa', 'notificationx'),
            'ES'  => __('Spain', 'notificationx'),
            'LK'  => __('Sri Lanka', 'notificationx'),
            'SD'  => __('Sudan', 'notificationx'),
            'SR'  => __('Suriname', 'notificationx'),
            'SE'  => __('Sweden', 'notificationx'),
            'CH'  => __('Switzerland', 'notificationx'),
            'SY'  => __('Syria', 'notificationx'),
            'TW'  => __('Taiwan', 'notificationx'),
            'TJ'  => __('Tajikistan', 'notificationx'),
            'TZ'  => __('Tanzania', 'notificationx'),
            'TH'  => __('Thailand', 'notificationx'),
            'TG'  => __('Togo', 'notificationx'),
            'TO'  => __('Tonga', 'notificationx'),
            'TT'  => __('Trinidad and Tobago', 'notificationx'),
            'TN'  => __('Tunisia', 'notificationx'),
            'TR'  => __('Turkey', 'notificationx'),
            'TM'  => __('Turkmenistan', 'notificationx'),
            'UG'  => __('Uganda', 'notificationx'),
            'UA'  => __('Ukraine', 'notificationx'),
            'AE'  => __('United Arab Emirates', 'notificationx'),
            'GB'  => __('United Kingdom', 'notificationx'),
            'US'  => __('United States', 'notificationx'),
            'UY'  => __('Uruguay', 'notificationx'),
            'UZ'  => __('Uzbekistan', 'notificationx'),
            'VU'  => __('Vanuatu', 'notificationx'),
            'VE'  => __('Venezuela', 'notificationx'),
            'VN'  => __('Vietnam', 'notificationx'),
            'YE'  => __('Yemen', 'notificationx'),
            'ZM'  => __('Zambia', 'notificationx'),
            'ZW'  => __('Zimbabwe', 'notificationx'),
        ];
    }


}
