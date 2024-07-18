<?php

/**
 * Admin Class File.
 *
 * @package NotificationX\Admin
 */

namespace NotificationX\FrontEnd;

use NotificationX\Core\Helper;
use NotificationX\Core\Rules;
use NotificationX\GetInstance;

/**
 * Preview Class, this class is responsible for all Preview Actions
 * @method static Preview get_instance($args = null)
 */
class Preview {
    /**
     * Instance of Preview
     *
     * @var Preview
     */
    use GetInstance;

    protected $notificationXArr = [];

    public function __construct() {
        add_action('wp_head', [$this, 'header_scripts']);
        add_action('wp_print_footer_scripts', [$this, 'footer_scripts']);

        add_filter('nx_before_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_filter('nx_inline_notifications_data', [$this, 'inline_notifications_data'], 10, 4);
        add_filter('nx_metabox_config', [$this, 'content_heading']);

        if ($this->is_preview()) {
            show_admin_bar(false);
        }

        if ($this->is_preview() && class_exists('QueryMonitor')) {
            ini_set('display_errors', 'Off');
            ini_set('error_reporting', E_ALL);
            $qm = \QueryMonitor::init();
            remove_action('plugins_loaded', [$qm, 'action_plugins_loaded']);
        }
    }


    /**
     * This method is responsible for enqueueing scripts for public use.
     *
     * @return void
     */
    public function enqueue_scripts($return) {
        if ($this->is_preview()) {
            $args = [
                'total'     => 1,
                'nxPreview' => true,
                'pressbar'  => [],
                'active'    => [],
            ];

            $settings = $this->preview_settings();
            if (empty($settings['source']) || empty($settings['type']))
                return;
            if ('inline' === $settings['type'] || 'woocommerce_sales_inline' == $settings['source'] ){
                $args['total'] = 0;
                return $args;
            }

            $source   = $settings['source'];
            if ($source === 'press_bar') {
                $args['pressbar'] = [
                    $source => [
                        'content' => FrontEnd::get_instance()->get_bar_content($settings, true),
                        'post'    => $settings,
                    ]
                ];
            } else {
                $args['active'] = [
                    $source => [
                        'entries' => [
                            $this->preview_entry($settings),
                        ],
                        'post'    => $settings,
                    ]
                ];
            }

            $args['settings'] = FrontEnd::get_instance()->get_settings();

            $this->notificationXArr = apply_filters('get_notifications_ids', $args);
            wp_enqueue_style('notificationx-public');
            wp_enqueue_script('notificationx-public');
            do_action('notificationx_scripts', $this->notificationXArr);

            add_filter('show_admin_bar', '__return_false');

            return $this->notificationXArr;
        }
        return $return;
    }

    public function header_scripts() {
        if ($this->is_preview()) {
?>
            <style>
                .notificationx-woo-shortcode-inline-wrapper > * {
                    display: inline-block;
                    /* background: rgb(255 255 0 / 20%);
                    color: red; */
                    font-weight: 600;
                }
                .notificationx-woo-shortcode-inline-wrapper > div.woo_inline_conv-theme-seven span:first-child,
                .notificationx-woo-shortcode-inline-wrapper > div.edd_inline_conv-theme-seven span:first-child, 
                .notificationx-woo-shortcode-inline-wrapper > div.woocommerce_sales_inline_conv-theme-seven span:first-child {
                    color: #61BD6D;
                }
                .notificationx-woo-shortcode-inline-wrapper > div.woo_inline_conv-theme-seven span:last-child,
                .notificationx-woo-shortcode-inline-wrapper > div.woocommerce_sales_inline_conv-theme-seven span:last-child,
                .notificationx-woo-shortcode-inline-wrapper > div.edd_inline_conv-theme-seven span:last-child,
                .notificationx-woo-shortcode-inline-wrapper > div.tutor_inline_conv-theme-seven span:last-child,
                .notificationx-woo-shortcode-inline-wrapper > div.learndash_inline_conv-theme-seven span:last-child{
                    color: #E25042;
                }

                .notificationx-woo-shortcode-inline-wrapper > div.woo_inline_stock-theme-one span,
                .notificationx-woo-shortcode-inline-wrapper > div.woocommerce_sales_inline_stock-theme-one span {
                    color: #E25042;
                }
            </style>
<?php
        }
    }

    public function footer_scripts() {
        if ($this->is_preview()) {
?>
            <script data-no-optimize="1">
                (function() {
                    document.addEventListener("click", function(event) {
                        // if (event.target.tagName === "A") {
                        event.preventDefault();
                        event.stopPropagation();
                        event.stopImmediatePropagation();
                        // }
                    });
                    document.addEventListener("submit", function(event) {
                        event.preventDefault();
                    });
                })();
            </script>
<?php
        }
    }


    public function preview_entry($settings) {
        $source = !empty($settings['source']) ? $settings['source'] : '';
        $type = !empty($settings['type']) ? $settings['type'] : '';
        $nx_id = !empty($settings['nx_id']) ? $settings['nx_id'] : '';
        $defaults = [
            'nx_id'                => $nx_id,
            'active_installs'      => rand(50, 70),
            'active_installs_text' => 'Try It Out',
            'all_time'             => rand(50, 70),
            'all_time_text'        => 'Why Don\'t You?',
            'anonymous_title'      => 'Anonymous Title',
            'author'               => '<a href="https://wpdeveloper.com/">WPDeveloper</a>',
            'author_profile'       => 'https://profiles.wordpress.org/wpdevteam/',
            'amount'               => rand(50, 70),
            'avatar'               => [
                'src' => NOTIFICATIONX_PUBLIC_URL . 'image/icons/pink-face-looped.gif',
            ],
            'picture'           => NOTIFICATIONX_PUBLIC_URL . 'image/icons/pink-face-looped.gif',
            'city'              => 'Dhaka',
            'city_country'      => 'Dhaka, Bangladesh',
            'content'           => 'Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.',
            'count'             => rand(50, 70),
            'country'           => 'Bangladesh',
            'course_title'      => 'PHP Beginners – Become a PHP Master',
            'created_at'        => wp_date('Y-m-d H:i:s', strtotime('2 days ago')),
            'day'               => 'days',
            'downloaded'        => rand(50, 70),
            'email'             => 'support@wpdeveloper.com',
            'entry_id'          => rand(1000, 9999),
            'entry_key'         => 'ChIJ0cpDbNvBVTcRGX9JNhhpC8I',
            'first_name'        => 'John',
            '_first_name'       => 'John',
            'formatted_address' => 'House 592, Road 8 Avenue 5, Dhaka 1216, Bangladesh',
            'ga_title'          => 'NotificationX',
            'icon'              => 'https://maps.gstatic.com/mapfiles/place_api/icons/v1/png_71/generic_business-71.png',
            'icons'             => array(
                '1x' => "https://ps.w.org/notificationx/assets/icon-128x128.gif?rev=2783824",
                '2x' => "https://ps.w.org/notificationx/assets/icon-256x256.gif?rev=2783824",
            ),
            'id'         => 5,
            'image_data' => array(
                'url'     => NOTIFICATIONX_PUBLIC_URL . 'image/icons/pink-face-looped.gif',
                'alt'     => '',
                'classes' => 'greview_icon',
            ),
            'ip'                => '103.108.146.88',
            'key'               => '7368a455f5c113afbfd3d8c3ea89a5ed-5719',
            'last_name'         => 'Doe',
            '_last_name'        => 'Doe',
            'last_updated'      => date('Y-m-d H:i:s', strtotime('2 days ago')),
            'last_week'         => rand(50, 70),
            'last_week_text'    => 'Get Started for Free.',
            'lat'               => 23.8371427,
            'link'              => '#',
            'lon'               => 90.3704629,
            'month'             => 'months',
            'name'              => 'John Doe',
            'names'             => 'John Doe',
            'none'              => '',
            'num_ratings'       => 2974,
            'nx_id'             => '60',
            'order_id'          => 5815,
            'place_id'          => 'ChIJ0cpDbNvBVTcRGX9JNhhpC8I',
            'place_name'        => 'WPDeveloper',
            'plugin_name'       => 'NotificationX',
            'plugin_name_text'  => 'try it out',
            'plugin_review'     => 'Lorem Ipsum is simply dummy text...',
            'place_review'      => 'Lorem Ipsum is simply dummy text...',
            'plugin_theme_name' => 'NotificationX',
            'post_link'         => '#',
            'product_id'        => 168,
            'product_title'     => 'Assorted Coffee',
            'rated'             => rand(50, 70),
            'rating'            => 4.4,
            'ratings'           => array(
                0 => 48,
                1 => 13,
                2 => 21,
                3 => 66,
                4 => 2826,
            ),
            'realtime_siteview' => rand(50, 70),
            'siteview'          => rand(50, 70),
            'slug'              => 'notificafionx',
            'sometime'          => 'Some time ago',
            'source'            => 'google_reviews',
            'status'            => 'wc-processing',
            'this_page'         => 'this page',
            'timestamp'         => date('Y-m-d H:i:s', strtotime('2 days ago')),
            'title'             => 'Hoodie with Logo',
            'today'             => rand(50, 70),
            'today_text'        => 'Try It Out',
            'type'              => 'realtime_siteview',
            'updated_at'        => date('Y-m-d H:i:s', strtotime('2 days ago')),
            'url'               => '#',
            'user_id'           => '1',
            'username'          => 'johndoe',
            'version'           => '5.5.2',
            'views'             => rand(50, 70),
            'website'           => 'https://wpdeveloper.com/',
            'year'              => 'years',
            'yesterday'         => rand(50, 70),
            'your-email'        => 'support@wpdeveloper.com',
            'your-message'      => 'Lorem Ipsum is simply dummy text.',
            'your-name'         => 'John Doe',
            'your-subject'      => 'Lorem Ipsum',
            'post_title'        => 'Hello World',
            'sales_count'       => rand(50, 70),
            'donation_count'       => rand(50, 70),
            '1day'              => __('In last 1 day', 'notificationx'),
            '7days'             => __('In last 7 days', 'notificationx'),
            '30days'            => __('In last 30 days', 'notificationx'),
            'post_comment'      => 'Lorem Ipsum is simply dummy text...',
            // 'select_a_tag'      => 'Jhon',

        ];

        if (!empty($settings['custom_contents']) && is_array($settings['custom_contents']) && count($settings['custom_contents'])) {
            $custom = !empty($settings['custom_contents'][0]) ? $settings['custom_contents'][0] : [];
            if (isset($custom['first_name'], $custom['last_name'])) {
                $custom['name'] = Helper::name($custom['first_name'], $custom['last_name']);
            }
            $defaults = array_merge($defaults, $custom);
        }

        $settings['freemius_plugins'] = '';

        $defaults['image_data'] = FrontEnd::get_instance()->apply_defaults((array) FrontEnd::get_instance()->get_image_url($defaults, $settings), $defaults['image_data']);

        $defaults  = apply_filters("nx_preview_entry_$type", $defaults, $settings);
        $defaults  = apply_filters("nx_preview_entry_$source", $defaults, $settings);
        $_defaults = apply_filters("nx_fallback_data_$source", $defaults, $defaults, $settings);
        $_defaults = apply_filters('nx_fallback_data', $_defaults, $_defaults, $settings);
        $defaults  = FrontEnd::get_instance()->apply_defaults($defaults, $_defaults);
        $defaults  = apply_filters("nx_filtered_entry_$type", $defaults, $settings);
        $defaults  = apply_filters("nx_filtered_entry_$source", $defaults, $settings);
        // $defaults  = $this->link_url($defaults, $settings);
        if (strpos($settings['theme'], 'maps_theme') !== false && 'maps_image' === $settings['show_notification_image']) {
            $defaults['image_data'] = array(
                'url'     => NOTIFICATIONX_ASSETS . 'admin/images/map.jpg',
                'alt'     => '',
                'classes' => 'greview_icon',
            );
        }
        if ('gravatar' === $settings['show_notification_image']) {
            $defaults['image_data'] = array(
                'url'     => NOTIFICATIONX_PUBLIC_URL . 'image/icons/pink-face-looped.gif',
                'alt'     => '',
                'classes' => 'greview_icon',
            );
        }
        if ('none' === $settings['show_notification_image'] && !$settings['show_default_image']) {
            $defaults['image_data'] = false;
        }
        return $defaults;
    }

    public function get_settings(){
        $settings = base64_decode($_POST['nx-preview']);
        $settings = json_decode($settings, true);
        return $settings;
    }

    public function preview_settings($settings = []) {
        $settings = !empty($settings) ? $settings : $this->get_settings();

        if (empty($settings['source'])){
            return [];
        }

        if ($settings['global_queue']) {
            $settings['global_queue']  = false;
            $settings['_global_queue'] = true;
        }
        $settings['nx_id'] = rand();
        $settings['is_preview'] = true;
        if (empty($settings['theme']) && !empty($settings['themes'])) {
            $settings['theme'] = $settings['themes'];
        }
        if ('form' === $settings['type']) {
            $settings['notification-template']['first_param'] = 'tag_first_name';
        }

        $settings = apply_filters("nx_get_post_{$settings['source']}", $settings);
        $settings = apply_filters("nx_preview_settings_{$settings['source']}", $settings);
        $settings = apply_filters('nx_get_post', $settings);
        return $settings;
    }

    public function content_heading($tabs) {
        $urls = apply_filters('nx_preview_url', [
            'default'    => trailingslashit(home_url()),
        ]);

        $tabs['config']['content_heading']['preview'] = apply_filters('nx_content_heading_preview', [
            'label'  => __('Preview', 'notificationx'),
            'type'   => 'preview-modal',
            'name'   => 'preview',
            'urls'   => $urls,
            'errors' => apply_filters('nx_content_heading_preview_errors', []),
            'rules'  => Rules::includes('themes', ['woo_inline_stock-theme-two', 'tutor_inline_conv-theme-eight', 'flashing_tab_theme-1','flashing_tab_theme-2' ,'flashing_tab_theme-3' , 'flashing_tab_theme-4','woocommerce_sales_inline_stock-theme-two','learnpress_inline_conv-theme-eight'], true),
        ]);
        return $tabs;
    }

    public function inline_notifications_data($return, $source, $id, $settings = []) {
        if ($this->is_preview() && !empty($id)) {
            $settings = $this->preview_settings($settings);
            $settings['is_preview'] = true;

            if ( ( empty($settings['source']) || empty($settings['type']) || 'inline' !== $settings['type']) && ('woocommerce_sales_inline' !== $settings['source']) ){
                return;
            }

            remove_filter('nx_inline_notifications_data', [$this, 'inline_notifications_data'], 10);

            $source = !empty($settings['source']) ? $settings['source'] : '';
            $type   = !empty($settings['type']) ? $settings['type'] : '';
            $settings['inline_location'] = is_array($settings['inline_location']) ? $settings['inline_location'] : [];
            $settings['inline_location'][] = 'woocommerce_before_add_to_cart_form';

            $defaults = [
                "nx_id"           => rand(),
                "entry_id"        => 78,
                "order_id"        => 96,
                "product_id"      => $id,
                "id"              => $id,
                // "status"          => "wc-pending",
                "title"           => get_the_title($id),
                "product_title"   => get_the_title($id),
                "link"            => "#",
                "timestamp"       => date('Y-m-d H:i:s', strtotime('2 days ago')),
                // "first_name"      => "John",
                // "last_name"       => "Doe",
                // "name"            => "John Doe",
                "email"           => "support@wpdeveloper.com",
                "source"          => $source,
                "entry_key"       => "96-7",
                "created_at"      => date('Y-m-d H:i:s', strtotime('2 days ago')),
                "updated_at"      => date('Y-m-d H:i:s', strtotime('2 days ago')),
                "none"            => "",
                // "anonymous_title" => "Anonymous Product",
                // "sometime"        => "Some time ago",
                "sales_count"     => rand(50, 70),
                "donation_count"     => rand(50, 70),
                // "30days"          => "in last 30 days",
                // "day:30"          => "30 days",
                // "1day"            => "in last 1 day",
                // "7days"           => "in last 7 days",
            ];


            $_defaults = apply_filters("nx_fallback_data_$source", $defaults, $defaults, $settings);
            $_defaults = apply_filters('nx_fallback_data', $_defaults, $_defaults, $settings);
            $defaults  = FrontEnd::get_instance()->apply_defaults($defaults, $_defaults);
            $defaults  = apply_filters("nx_preview_entry_$type", $defaults, $settings);
            $defaults  = apply_filters("nx_preview_entry_$source", $defaults, $settings);
            $defaults  = apply_filters("nx_filtered_entry_$type", $defaults, $settings);
            $defaults  = apply_filters("nx_filtered_entry_$source", $defaults, $settings);

            return [
                'shortcode' => [
                    [
                        "entries" => [$defaults],
                        "post"    => $settings,
                    ],
                ],
            ];
        }

        return $return;
    }

    public function is_preview() {
        $is_preview = false;
        if (!empty($_POST['nx-preview'])) {
            $is_preview = true;
        }
        $is_preview = apply_filters('nx_is_preview',$is_preview);
        return $is_preview;
    }
}
