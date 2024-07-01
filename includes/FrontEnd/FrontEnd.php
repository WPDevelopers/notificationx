<?php

/**
 * FrontEnd Class
 *
 * @package NotificationX\FrontEnd
 */

namespace NotificationX\FrontEnd;

use NotificationX\Admin\Entries;
use NotificationX\Admin\Settings;
use NotificationX\Core\Analytics;
use NotificationX\Core\GetData;
use NotificationX\NotificationX;
use NotificationX\Core\Locations;
use NotificationX\Core\PostType;
use NotificationX\Core\REST;
use NotificationX\GetInstance;
use NotificationX\Extensions\PressBar\PressBar;
use NotificationX\Core\Helper;
use NotificationX\Extensions\ExtensionFactory;

/**
 * This class is responsible for all Front-End actions.
 * @method static FrontEnd get_instance($args = null)
 */
class FrontEnd {
    /**
     * Instance of FrontEnd
     *
     * @var FrontEnd
     */
    use GetInstance;
    /**
     * Assets Path and URL
     */
    const ASSET_URL  = NOTIFICATIONX_ASSETS . 'public/';
    const ASSET_PATH = NOTIFICATIONX_ASSETS_PATH . 'public/';
    protected $notificationXArr = [];

    /**
     * Initially Invoked
     * when its initialized.
     */
    public function __construct() {
        Analytics::get_instance();
        if (!is_admin() || !empty($_GET['frontend'])) {
            add_action('init', [$this, 'init'], 10);
        }
        add_filter('nx_frontend_localize_data', [$this, 'get_localize_data']);
        Preview::get_instance();
    }

    /**
     * This method is reponsible for Admin Menu of
     * NotificationX
     *
     * @return void
     */
    public function init() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts'], 10);
        add_filter('nx_fallback_data', [$this, 'fallback_data'], 10, 3);
        add_filter('nx_filtered_data', [$this, 'filtered_data'], 9999, 3);
        add_filter('nx_filtered_post', [$this, 'filtered_post'], 9999, 2);
        add_action('wp_print_footer_scripts', [$this, 'footer_scripts']);

    }

    /**
     * This method is responsible for enqueueing scripts for public use.
     *
     * @return void
     */
    public function enqueue_scripts() {

        wp_register_script('notificationx-public', Helper::file('public/js/frontend.js', true), [], NOTIFICATIONX_VERSION, true);
        wp_register_style('notificationx-public', Helper::file('public/css/frontend.css', true), [], NOTIFICATIONX_VERSION, 'all');
        // wp_register_style('notificationx-icon-pack', Helper::file('public/icon/style.css', true), [], NOTIFICATIONX_VERSION, 'all');

        $exit = false;
        if(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'wp-admin/widgets.php') !== false){
            $exit = ['total' => 0];
        }

        $exit = apply_filters('nx_before_enqueue_scripts', $exit);
        if(!empty($exit)){
            $this->notificationXArr = $exit;
            return;
        }

        if (!$exit && empty($_GET['elementor-preview'])) {
            $this->notificationXArr = $this->get_notifications_ids();
            if ($this->notificationXArr['total'] > 0) {
                $lang = get_locale();
                $lang = str_replace('_', '-', $lang);
                $lang = strtolower($lang);
                $_lang = explode('-', $lang);
                if ($lang !== "en" && $lang !== "en-us") {
                    $script = Helper::file("public/locale/$lang.js", false);
                    if (file_exists($script)) {
                        wp_enqueue_script('notificationx-moment-locale', Helper::file("public/locale/$lang.js", true), [], NOTIFICATIONX_VERSION, true);
                    } else if (!empty($_lang[1])) {
                        $lang = $_lang[0];
                        $script = Helper::file("public/locale/$lang.js", false);
                        if (file_exists($script)) {
                            wp_enqueue_script('notificationx-moment-locale', Helper::file("public/locale/$lang.js", true), [], NOTIFICATIONX_VERSION, true);
                        }
                    }
                }

                wp_enqueue_style('notificationx-public');
                wp_enqueue_script('notificationx-public');
                wp_enqueue_style('dashicons');
                do_action('notificationx_scripts', $this->notificationXArr);
            }
        } else {
            // @todo maybe elementor edit mode CSS. to move to top.
            // LATER
        }
    }

    public function footer_scripts() {
        if (!empty($this->notificationXArr['total']) && $this->notificationXArr['total'] > 0) {
            $this->notificationXArr = apply_filters('nx_frontend_localize_data', $this->notificationXArr);
            ?>
            <script data-no-optimize="1">
                (function() {
                    window.notificationXArr = window.notificationXArr || [];
                    window.notificationXArr.push(<?php echo json_encode($this->notificationXArr); ?>);
                })();
            </script>
            <?php
        }
    }

    // @todo deprecated. use get_localize_data instead
    public function localizeScripts() {
        return [];
    }

    public function get_localize_data($data) {
        $data['rest']       = REST::get_instance()->rest_data(false);
        $data['assets']     = self::ASSET_URL;
        $data['is_pro']     = false;
        $data['gmt_offset'] = get_option('gmt_offset');
        $data['lang']       = get_locale();
        $data['extra']      = [
            'is_singular' => is_singular(),
            'query'       => $GLOBALS['wp_query']->query,
            'queried_id'  => get_queried_object_id(),
            'pid'         => !empty($GLOBALS['post']->ID) ? $GLOBALS['post']->ID : 0,
        ];
        $data['localeData'] = load_script_textdomain('notificationx-public', 'notificationx');
        return $data;
    }

    public function get_notifications_data($params) {
        $_params = $params;
        $result  = [
            'global'    => [],
            'active'    => [],
            'pressbar'  => [],
            'shortcode' => [],
        ];
        if (!empty($_params['all_active'])) {
            $params = $this->get_notifications_ids();
        }
        $params    = wp_parse_args(
            $params,
            [
                'global'           => [],
                'active'           => [],
                'pressbar'         => [],
                'shortcode'        => [],
                'inline_shortcode' => false,
            ]
        );
        $global    = $params['global'];
        $active    = $params['active'];
        $pressbar  = $params['pressbar'];
        $shortcode = $params['shortcode'];
        $all       = array_merge($global, $active, $shortcode);
        $_defaults = array(
            'none'            => '',
            'name'            => __('Someone', 'notificationx'),
            'first_name'      => __('Someone', 'notificationx'),
            'last_name'       => __('Someone', 'notificationx'),
            'anonymous_title' => __('Anonymous Title', 'notificationx'),
            'sometime'        => __('Some time ago', 'notificationx'),
        );

        // foreach (['global', 'active'] as $key => $type) {
        // foreach ($params[$type] as $id) {
        // $result[$type][$id] = [];
        // }
        // }

        if (!empty($all)) {
            $notifications = $this->get_notifications($all);
            $entries       = $this->get_entries($all, $notifications, $params);

            foreach ($entries as $entry) {
                $nx_id    = $entry['nx_id'];
                $settings = $notifications[$nx_id];

                $type   = $settings['type'];
                $source = $settings['source'];

                if (!empty($entry['timestamp'])) {
                    $timestamp    = $entry['timestamp'];
                    $display_from = !empty($settings['display_from']) ? $settings['display_from'] : 2;
                    $display_from = strtotime("-$display_from days");
                    if (!is_numeric($timestamp)) {
                        $entry['timestamp'] = $timestamp = strtotime($timestamp);
                    }
                    if ($timestamp && $display_from > $timestamp) {
                        if (apply_filters("nx_entry_display_$source", true, $entry, $settings)) {
                            continue;
                        }
                    }
                }

                $defaults = apply_filters("nx_fallback_data_$source", $_defaults, $entry, $settings);
                $defaults = apply_filters('nx_fallback_data', $defaults, $entry, $settings);

                $entry               = $this->apply_defaults($entry, $defaults);
                $entry['image_data'] = $this->get_image_url($entry, $settings);
                if (!empty($entry['title'])) {
                    $entry['title'] = strip_tags(html_entity_decode($entry['title']));
                }

                $entry = apply_filters("nx_filtered_entry_$type", $entry, $settings);
                $entry = apply_filters("nx_filtered_entry_$source", $entry, $settings);
                $entry = apply_filters('nx_filtered_entry', $entry, $settings);
                $entry = $this->link_url($entry, $settings, $params);

                // @todo shortcode
                // @todo check if the current page have shortcode.
                if (in_array($nx_id, $shortcode)) {
                    $position             = $settings['position'];
                    $settings['position'] = "notificationx-shortcode-$nx_id";
                    if (empty($result['shortcode'][$nx_id]['post'])) {
                        $result['shortcode'][$nx_id]['post'] = $settings;
                    }
                    $result['shortcode'][$nx_id]['entries'][] = $entry;
                    $settings['position']                       = $position;
                    if ($settings['show_on'] === 'only_shortcode' || 'inline' === $settings['type'] || 'woocommerce_sales_inline' == $settings['source']) {
                        continue;
                    }
                    if (empty($global) && empty($active)) {
                        continue;
                    }
                }

                if (!empty($settings['global_queue'])) {
                    if (empty($result['global'][$nx_id]['post'])) {
                        $result['global'][$nx_id]['post'] = $settings;
                    }
                    $result['global'][$nx_id]['entries'][] = $entry;
                } else {
                    if (empty($result['active'][$nx_id]['post'])) {
                        $result['active'][$nx_id]['post'] = $settings;
                    }
                    $result['active'][$nx_id]['entries'][] = $entry;
                }
            }

            foreach ($result as &$group) {
                foreach ($group as &$value) {
                    $value['entries'] = apply_filters("nx_filtered_data_{$value['post']['type']}", $value['entries'], $value['post'], $params);
                    $value['entries'] = apply_filters("nx_filtered_data_{$value['post']['source']}", $value['entries'], $value['post'], $params);
                    $value['entries'] = apply_filters('nx_filtered_data', $value['entries'], $value['post'], $params);
                    $value['post']    = apply_filters('nx_filtered_post', $value['post'], $params);
                }
            }
            $result = apply_filters('nx_filtered_notice', $result, $params);
        }

        if (!empty($pressbar)) {
            $notifications = $this->get_notifications($pressbar);
            foreach ($notifications as $key => $settings) {
                $_nx_id            = $settings['nx_id'];
                $elementor_post_id = isset($settings['elementor_id']) ? $settings['elementor_id'] : '';
                if ($elementor_post_id == '' || get_post_status($elementor_post_id) !== 'publish' | !class_exists('\Elementor\Plugin')) {
                    $settings['elementor_id'] = false;
                }
                if (!empty($_params['all_active']) && $elementor_post_id) {
                    continue;
                }

                // $settings['button_url'] = apply_filters("nx_notification_link_{$settings['source']}", $settings['button_url'], $settings);
                $settings['button_url'] = apply_filters('nx_notification_link', $settings['button_url'], $settings);
                if (!empty($settings['button_url']) && strpos($settings['button_url'], '//') === false) {
                    $settings['button_url'] = "//{$settings['button_url']}";
                }
                $bar_content = $this->get_bar_content($settings, false, $params);
                if ($bar_content !== '&nbsp;' || !empty($settings['enable_countdown'])) {
                    $settings = apply_filters('nx_filtered_post', $settings, $params);
                    $result['pressbar'][$_nx_id]['post']    = $settings;
                    $result['pressbar'][$_nx_id]['content'] = $bar_content;
                }

                unset($_nx_id);
            }
        }

        $result['settings'] = $this->get_settings();
        return $result;
    }

    public function get_settings(){

        $branding_url       = apply_filters('nx_branding_url', NOTIFICATIONX_PLUGIN_URL . '?utm_source=' . esc_url(home_url()) . '&utm_medium=notificationx');
        $settings = [
            'disable_powered_by' => Settings::get_instance()->get('settings.disable_powered_by'),
            'affiliate_link'     => $branding_url,
            'enable_analytics'   => Settings::get_instance()->get('settings.enable_analytics', true),
            'analytics_from'     => Settings::get_instance()->get('settings.analytics_from'),
            'delay_before'       => Settings::get_instance()->get('settings.delay_before', 5),
            'display_for'        => Settings::get_instance()->get('settings.display_for', 5),
            'delay_between'      => Settings::get_instance()->get('settings.delay_between', 5),
            'loop'               => Settings::get_instance()->get('settings.loop', 5),
            'random'             => Settings::get_instance()->get('settings.random', 5),
            'analytics_nonce'    => wp_create_nonce('analytics_nonce'),
        ];
        return $settings;
    }

    public function get_notifications_ids($return_posts = false, $args = []) {
        $args = wp_parse_args($args, [
            'enabled'    => true,
            'updated_at' => ['<=', Helper::mysql_time()],
            // 'global_queue' => true,
        ]);
        $notifications = PostType::get_instance()->get_posts($args);

        $active_notifications = $global_notifications = $bar_notifications = array();

        foreach ($notifications as $key => $settings) {
            // $settings        = NotificationX::get_instance()->normalize_post($post);

            // IF PRESSBAR TIME RE_CONFIG THEN REMOVE COOKIE
            // if( $settings['source'] == 'press_bar'){
            // if( $_settings->enable_countdown && ( Helper::current_timestamp($_settings->countdown_start_date) < time() || Helper::current_timestamp($_settings->countdown_end_date) > time() ) ) {
            // unset( $_COOKIE["notificationx_{$settings['nx_id']}"] );
            // \setcookie("notificationx_{$settings['nx_id']}", null);
            // }
            // }

            /**
             * Check if it's a pro source and pro plugin is disabled.
             */
            if(!NotificationX::is_pro()){
                $ext = ExtensionFactory::get_instance()->get($settings['source']);
                if($ext && $ext->is_pro){
                    continue;
                }
            }

            $countdown_rand = !empty($settings['countdown_rand']) ? "-{$settings['countdown_rand']}" : '';

            if (!empty($_COOKIE["notificationx_{$settings['nx_id']}$countdown_rand"]) && $_COOKIE["notificationx_{$settings['nx_id']}$countdown_rand"] == true) {
                unset($notifications[$key]);
                continue;
            }

            if($this->is_logged_in($settings['show_on_display'])){
                continue;
            }

            $locations  = isset($settings['all_locations']) ? $settings['all_locations'] : [];
            $custom_ids = isset($settings['custom_ids']) ? $settings['custom_ids'] : [];

            if($this->check_show_on($locations, $custom_ids, $settings['show_on'])){
                continue;
            }

            /**
             * Check for hiding in mobile device
             */
            if ($settings['hide_on_mobile'] && wp_is_mobile()) {
                continue;
            }

            $show_on_exclude = apply_filters('nx_show_on_exclude', false, $settings);
            if ($show_on_exclude) {
                continue;
            }

            // excluding pressBar on rest request. PressBar is printed directly in head.
            // if((empty($args['source']) || $args['source'] !== 'press_bar') && $settings['source'] == 'press_bar'){
            // continue;
            // }

            $active_global_queue = boolval($settings['global_queue']);
            if ($settings['source'] == 'press_bar') {
                // if(
                // !$_settings->elementor_id &&
                // $_settings->enable_countdown &&
                // !$_settings->evergreen_timer &&
                // (
                // strtotime($_settings->countdown_start_date) > time() ||
                // strtotime($_settings->countdown_end_date) < time()
                // )
                // ){
                // continue;
                // }

                $bar_notifications[] = $return_posts ? $settings : $settings['nx_id'];
                if (!empty($settings['elementor_id']) && class_exists('\Elementor\Plugin')) {
                    // @todo Find a function to only load css instead of building content.
                    \Elementor\Plugin::$instance->frontend->get_builder_content($settings['elementor_id'], false);
                }
            } elseif ($active_global_queue && NotificationX::is_pro()) {
                $global_notifications[] = $return_posts ? $settings : $settings['nx_id'];
            } else {
                $active_notifications[] = $return_posts ? $settings : $settings['nx_id'];
            }

            unset($notifications[$key]);
        }
        // do_action('nx_active_notificationx', $notifications);

        // @todo maybe combine two hooks.

        return apply_filters(
            'get_notifications_ids',
            [
                'global'   => $global_notifications,
                'active'   => $active_notifications,
                'pressbar' => $bar_notifications,
                'total'    => (count($global_notifications) + count($active_notifications) + count($bar_notifications)),
            ],
            $notifications
        );
    }

    public function is_logged_in($show_on_display){
        $logged_in       = is_user_logged_in();

        if (($logged_in && 'logged_out_user' == $show_on_display) || (!$logged_in && 'logged_in_user' == $show_on_display)) {
            return true;
        }
        return false;
    }

    /**
     * @todo filter is not extensive enough.
     */
    public function check_show_on($locations, $custom_ids, $show_on){
        $check_location = false;

        if ($locations == 'is_custom' || is_array($locations) && in_array('is_custom', $locations)) {
            $custom_ids = !empty($custom_ids) ? $custom_ids : [];
        }
        if (!empty($locations)) {
            // @todo need to pass url.
            $check_location = Locations::get_instance()->check_location($locations, $custom_ids);
        }

        $check_location = apply_filters('nx_check_location', $check_location, $custom_ids, $show_on);

        if ($show_on == 'on_selected') {
            // show if the page is on selected
            if (!$check_location) {
                return true;
            }
        } elseif ($show_on == 'hide_on_selected') {
            // hide if the page is on selected
            if ($check_location) {
                return true;
            }
        } elseif ($show_on === 'only_shortcode') {
            return true;
        }
        return false;
    }

    public function get_notifications($ids) {
        $results       = [];
        $notifications = PostType::get_instance()->get_posts_by_ids($ids);

        foreach ($notifications as $key => $value) {
            /**
             * Check for hiding in mobile device
             */
            if (!empty($value['hide_on_mobile']) && wp_is_mobile()) {
                continue;
            }

            /**
             * Check if it's a pro source and pro plugin is disabled.
             */
            if(!NotificationX::is_pro()){
                $ext = ExtensionFactory::get_instance()->get($value['source']);
                if($ext && $ext->is_pro){
                    continue;
                }
            }

            $results[$value['nx_id']] = $value;
        }
        return $results;
    }

    public function get_entries($ids, $notifications,$params) {
        $entries = [];
        if (!empty($ids) && is_array($ids)) {
            $query = [];
            foreach ($ids as $id) {
                if (!empty($notifications[$id])) {
                    $post         = $notifications[$id];
                    $global_query =  " nx_id = " . absint($id) . " AND source = '" . esc_sql($post['source']) . "'";
                    $_q = apply_filters("nx_get_entries_query_part_{$notifications[$id]['source']}",$global_query, $notifications[$id], $params );
                    $query[$id] = " (" . $_q . ")";
                }
            }
            if (!empty($query)) {
                $entries = Entries::get_instance()->get_entries('WHERE' . implode(' OR ', $query));
                foreach ($entries as $key => $value) {
                    if (!empty($value['data'])) {
                        $entries[$key] = array_merge($value, $value['data']);
                        unset($entries[$key]['data']);
                    }
                }
            }
        }
        if (!is_array($entries)) {
            $entries = [];
        }
        $entries = apply_filters('nx_frontend_get_entries', $entries, $ids, $notifications,$params);
        return $entries;
    }

    /**
     * This function is responsible for make ready the link for notifications.
     *
     * @param array $data
     * @return void
     */
    public static function link_url($entry, $post, $params = []) {
        if (empty($entry)) {
            return false;
        }
        $link = isset($entry['link']) ? $entry['link'] : '';
        // removing link if link type is none.
        if (empty($post['link_type']) || $post['link_type'] === 'none') {
            $link = '';
        }

        $link          = apply_filters("nx_notification_link_{$post['source']}", $link, $post, $entry, $params);
        $entry['link'] = apply_filters('nx_notification_link', $link, $post, $entry, $params);
        return $entry;
    }

    /**
     * This function is responsible for getting the image url
     * using Product ID or from default image settings.
     *
     * @param array $data
     * @param array $settings
     * @return array of image data, contains url and title as alt text
     */
    public function get_image_url($data, $settings) {
        $source     = $settings['source'];
        $alt_title  = isset($data['name']) ? $data['name'] : '';
        $image_type = isset($settings['show_notification_image']) ? $settings['show_notification_image'] : false;
        if (empty($alt_title)) {
            $alt_title = isset($data['title']) ? $data['title'] : '';
        }
        $image_data = [
            'url' => '',
            'alt' => $alt_title,
        ];

        if ($settings['show_default_image']) {
            if (!empty($settings['image_url']['url'])) {
                $image             = wp_get_attachment_image_src($settings['image_url']['id'], [100, 100], true);
                $image_data['url'] = $image[0];
            } else {
                $default_avatar    = $settings['default_avatar'];
                $image_data['url'] = NOTIFICATIONX_PUBLIC_URL . 'image/icons/' . $default_avatar;
            }
        } else {
            if ($image_type === 'gravatar') {
                $_data = array_change_key_case($data);
                if (isset($_data['email'])) {
                    $image_data['url'] = get_avatar_url($_data['email'], ['size' => '100']);
                }
            }
        }

        $image_data['classes'] = $image_type;
        $image_data            = apply_filters("nx_notification_image_$source", $image_data, $data, $settings);
        $image_data            = apply_filters('nx_notification_image', $image_data, $data, $settings);

        if (!empty($image_data['url'])) {
            return $image_data;
        }

        return false;
    }

    public function apply_defaults($entry, $defaults) {

        foreach ($defaults as $key => $value) {
            // @todo mukul remove  `|| empty(trim($entry[$key]))`
            if (empty($entry[$key]) || (is_string($entry[$key]) && empty(trim($entry[$key])))) {
                $entry[$key] = $value;
            }
        }

        return $entry;
    }

    public function fallback_data($data, $saved_data, $settings) {
        if ((empty($saved_data['name']) || $data['name'] == __('Someone', 'notificationx')) && isset($saved_data['first_name']) || isset($saved_data['last_name'])) {
            $first_name   = isset($saved_data['first_name']) ? $saved_data['first_name'] : '';
            $last_name    = isset($saved_data['last_name']) ? $saved_data['last_name'] : '';
            $data['name'] = Helper::name($first_name, $last_name);
        }
        if (!empty($saved_data['name']) && empty($saved_data['first_name']) && empty($saved_data['last_name'])) {
            $data['first_name'] = $saved_data['name'];
        }
        $data['title'] = isset($saved_data['post_title']) ? $saved_data['post_title'] : (isset($data['title']) ? $data['title'] : '');
        return $data;
    }

    /**
     * Add NotificationX in Footer
     *
     * @return void
     */
    public function filtered_data($entries, $post, $params) {
        if (is_array($entries) && (!defined('NX_DEBUG') || !NX_DEBUG)) {
            if (!empty($post['display_last']) && !in_array($post['source'], ['google', 'woo_inline', 'edd_inline', 'tutor_inline', 'learndash_inline', 'google_reviews', 'youtube','woocommerce_sales_inline'])) {
                $entries = array_slice($entries, 0, $post['display_last']);
            }
            foreach ($entries as $index => $entry) {
                $_entry = apply_filters("nx_frontend_keep_entry_{$post['source']}", [
                    'nx_id'      => $entry['nx_id'],
                    'timestamp'  => isset($entry['timestamp']) ? $entry['timestamp'] : Helper::current_timestamp($entry['updated_at']),
                    'updated_at' => isset( $entry['updated_at'] ) ? $entry['updated_at'] : '',
                    'image_data' => $entry['image_data'],
                    'link'       => $entry['link'],
                ], $entry, $post, $params);
                if (!empty($params['inline_shortcode']) && isset($entry['product_id'])) {
                    $_entry['product_id'] = $entry['product_id'];
                }

                $template_arr = array_values($post['notification-template']);
                if ($post['template_adv']) {
                    $adv_template = $post['advanced_template'];
                    $pattern = "/{{(.+?)}}/i";
                    if (preg_match_all($pattern, $adv_template, $matches)) {
                        $template_arr = $matches[1];
                    }
                }
                if (is_array($template_arr)) {
                    foreach ($template_arr as $entry_key) {
                        $_entry_key = $entry_key;
                        if ($entry_key == 'tag_siteview' || $entry_key == 'tag_realtime_siteview') {
                            $entry_key = 'views';
                        } elseif ($entry_key == 'ga_title' || $entry_key == 'ga_page_title') {
                            $entry_key = 'title';
                        } elseif (strpos($entry_key, 'tag_product_') === 0) {
                            $entry_key = str_replace('tag_product_', '', $entry_key);
                        } elseif (strpos($entry_key, 'tag_') === 0) {
                            $entry_key = str_replace('tag_', '', $entry_key);
                        } elseif (strpos($entry_key, 'product_') === 0) {
                            $entry_key = str_replace('product_', '', $entry_key);
                        }

                        if (isset($entry[$entry_key])) {
                            $_entry[$entry_key] = $entry[$entry_key];
                        }
                        if (isset($entry[$_entry_key])) {
                            $_entry[$_entry_key] = $entry[$_entry_key];
                        }
                    }
                    $entries[$index] = $_entry;
                }
            }
        }
        return $entries;
    }

    /**
     * Remove unnecessary props from post in frontend.
     *
     * @return void
     */
    public function filtered_post($post, $params = null) {
        if (is_array($post) && empty($params['inline_shortcode']) && (!defined('NX_DEBUG') || !NX_DEBUG)) {
            $ignore_props = [
                'all_locations',
                'category_list',
                'combine_multiorder_text',
                'content_trim_length',
                'convertkit_form',
                'currentTab',
                'custom_contents',
                'custom_ids',
                'default_avatar',
                'elementor_edit_link',
                'enabled',
                'exclude_categories',
                'exclude_products',
                'form_list',
                'freemius_item_type',
                'freemius_plugins',
                'freemius_themes',
                'give_form_list',
                'give_forms_control',
                'image_url',
                'inline_location',
                'is_confirmed',
                'is_elementor',
                'is_inline',
                'ld_course_list',
                'ld_product_control',
                'mailchimp_list',
                'max_stock',
                'nx-bar_with_elementor',
                'nx-bar_with_elementor-remove',
                'nx-bar_with_elementor_install',
                'order_status',
                'press_content',
                'preview',
                'product_control',
                'product_exclude_by',
                'product_list',
                'rest_route',
                'show_default_image',
                'show_notification_image',
                'show_on',
                'show_on_display',
                'source_error',
                'utm_campaign',
                'utm_medium',
                'utm_source',
                'wp_reviews_product_type',
                'wp_reviews_slug',
                'wp_stats_product_type',
                'wp_stats_slug',
                '_locale',
                '__product_list',
                '__form_list',
                '__ld_course_list',
                '__give_form_list',
            ];
            foreach ($ignore_props as $prop) {
                if (isset($post[$prop])) {
                    unset($post[$prop]);
                }
            }
        }
        if (isset($post['template_adv'], $post['advanced_template'])) {
            $post['advanced_template'] = do_shortcode($post['advanced_template']);
        }

        return $post;
    }

    public function get_bar_content($settings, $suppress_filters = false, $params = []){
        $bar_content  = PressBar::get_instance()->print_bar_notice($settings);
        if(!$suppress_filters){
            $bar_content  = apply_filters("nx_filtered_data_{$settings['source']}", $bar_content, $settings, $params);
        }

        // checking if content is empty
        $_bar_content = str_replace(array("\r\n", "\n", "\r"), '', $bar_content);
        $_bar_content = trim(strip_tags($_bar_content));
        if (empty($_bar_content) && !empty($settings['enable_countdown'])) {
            $bar_content = '&nbsp;';
        }
        return $bar_content;
    }

}
