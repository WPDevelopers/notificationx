<?php

/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Types;

use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;
use NotificationX\Core\Rules;
use NotificationX\Core\Helper;

/**
 * Extension Abstract for all Extension.
 * @method static DownloadStats get_instance($args = null)
 */
class DownloadStats extends Types {
    /**
     * Instance of Admin
     *
     * @var Admin
     */
    use GetInstance;

    public $priority = 30;
    public $themes = [];
    public $module = [
        'modules_wordpress',
        'modules_freemius',
    ];
    public $default_source    = 'wp_stats';
    public $default_theme = 'download_stats_today-download';
    public $link_type = 'stats_page';


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        $this->id = 'download_stats';
        // Verified card Installs: shorten Freemius' raw install count (see below).
        add_filter('nx_filtered_entry_freemius_stats', [$this, 'vc_installs_count'], 20, 2);
    }

    /**
     * Verified card Installs (download_stats_vc-installs) on Freemius Stats.
     *
     * Freemius sends the raw active-install count (e.g. 123456), which would
     * not fit the card's 64px tile; WP.org stats are already shortened ("40K",
     * WPOrgStats::conversion_data). Shorten it the same way, only for the vc
     * key and only when it is still a plain number. Every other theme and
     * source is unchanged.
     *
     * @param array $entry    Entry data.
     * @param array $settings Notification settings.
     * @return array
     */
    public function vc_installs_count($entry, $settings) {
        if (
            isset($settings['themes'], $entry['active_installs'])
            && 'download_stats_vc-installs' === $settings['themes']
            && is_numeric($entry['active_installs'])
        ) {
            $entry['active_installs'] = Helper::nice_number($entry['active_installs']);
        }
        return $entry;
    }

    public function init()
    {
        parent::init();
        $this->title = __('Download Stats', 'notificationx');
        $this->themes = [
            'today-download' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/today-download.png',
                'image_shape' => 'square',
                'template'  => [
                    'first_param'  => 'tag_plugin_theme_name',
                    'custom_first_param' => '',
                    'second_param' => __('has been downloaded' , 'notificationx'),
                    'third_param'  => 'tag_today',
                    'custom_third_param' => '',
                    'fourth_param' => 'tag_today_text',
                    'custom_fourth_param' => '',
                ],
            ],
            '7day-download'  => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/7day-download.png',
                'image_shape' => 'rounded',
                'template'  => [
                    'first_param'  => 'tag_plugin_theme_name',
                    'custom_first_param' => '',
                    'second_param' => __('has been downloaded', 'notificationx'),
                    'third_param'  => 'tag_last_week',
                    'custom_third_param' => '',
                    'fourth_param' => 'tag_last_week_text',
                    'custom_fourth_param' => '',
                ],
            ],
            'actively_using' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/actively-using.png',
                'image_shape' => 'rounded',
                'template'  => [
                    'first_param' => 'tag_active_installs',
                    'custom_first_param' => '',
                    'second_param' => __('people are actively using' , 'notificationx'),
                    'third_param' => 'tag_plugin_theme_name',
                    'custom_third_param' => '',
                ],
            ],
            'total-download' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/total-download.png',
                'image_shape' => 'circle',
                'template'  => [
                    'first_param'  => 'tag_plugin_theme_name',
                    'custom_first_param' => '',
                    'second_param' => __('has been downloaded', 'notificationx'),
                    'third_param'  => 'tag_all_time',
                    'custom_third_param' => '',
                    'fourth_param' => 'tag_all_time_text',
                    'custom_fourth_param' => '',
                ],
            ],
            // "Verified card" (vc) theme, kept at the END of the list so every
            // existing card keeps its picker position. Rendered by the frontend
            // VerifiedCard (nxdev/notificationx/frontend/themes/vc/); the full key
            // is listed in GlobalFields::vc_theme_keys(). No 'defaults', so the
            // theme trigger sets @link_button:false.
            // Counter — icon + name over "has been downloaded", the count as a
            // large figure, then the call to action. No time.
            'vc-counter' => [
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/vc/vc-counter.png',
                'image_shape' => 'rounded',
                'template'    => [
                    'first_param'  => 'tag_plugin_theme_name',
                    'second_param' => __('has been downloaded', 'notificationx'),
                    'third_param'  => 'tag_last_week',
                    'fourth_param' => 'tag_last_week_text',
                ],
            ],
            // Installs — the active-install count in a tile, "people are actively
            // using", a small icon + the plugin or theme name. No time. Its own
            // template group offers only the active-install count (the short
            // "40K" fits the tile; "12 times today" would not).
            'vc-installs' => [
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/vc/vc-installs.png',
                'image_shape' => 'rounded',
                'template'    => [
                    'first_param'  => 'tag_active_installs',
                    'second_param' => __('people are actively using', 'notificationx'),
                    'third_param'  => 'tag_plugin_theme_name',
                ],
            ],
        ];
        $this->res_themes = [
            'res-today-download' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_wporg/wporg-res-theme-1.png',
                'image_shape' => 'square',
                '_template'  => 'wp_stats_template_new',
                'is_pro'    => true,
            ],
            'res-7day-download'  => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_wporg/wporg-res-theme-2.png',
                'image_shape' => 'rounded',
                '_template'  => 'wp_stats_template_new',
                'is_pro'    => true,
            ],
            'res-actively_using' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_wporg/wporg-res-theme-3.png',
                'image_shape' => 'rounded',
                '_template'  => 'actively_using_template_new',
                'is_pro'    => true,
            ],
            'res-total-download' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_wporg/wporg-res-theme-4.png',
                'image_shape' => 'circle',
                '_template'  => 'wp_stats_template_new',
                'is_pro'    => true,
            ],
        ];
        $this->templates = [
            'wp_stats_template_new' => [
                'first_param' => [
                    'tag_plugin_theme_name' => __('Plugin/Theme Name', 'notificationx'),

                ],
                'third_param' => [
                    'tag_today'           => __('Today', 'notificationx'),
                    'tag_last_week'       => __('In last 7 days', 'notificationx'),
                    'tag_all_time'        => __('Total', 'notificationx'),
                    'tag_active_installs' => __('Total Active Install', 'notificationx'),

                ],
                'fourth_param' => [
                    'tag_today_text'           => __('Try it out', 'notificationx'),
                    'tag_last_week_text'       => __('Get Started for Free.', 'notificationx'),
                    'tag_all_time_text'        => __('Why Don\'t You?', 'notificationx'),
                    'tag_active_installs_text' => __('in total active', 'notificationx'),
                ],
                '_themes' => [
                    'download_stats_today-download',
                    'download_stats_7day-download',
                    'download_stats_total-download',
                    'download_stats_vc-counter',
                ]
            ],
            'actively_using_template_new' => [
                'first_param' => [
                    'tag_today'           => __('Today', 'notificationx'),
                    'tag_last_week'       => __('In last 7 days', 'notificationx'),
                    'tag_all_time'        => __('Total', 'notificationx'),
                    'tag_active_installs' => __('Total Active Install', 'notificationx'),
                ],
                'third_param' => [
                    'tag_plugin_theme_name' => __('Plugin/Theme Name', 'notificationx'),
                ],
                '_themes' => [
                    'download_stats_actively_using',
                ]
            ],
            // Verified card Installs: its own group, only the active-install count.
            'vc_installs_template' => [
                'first_param' => [
                    'tag_active_installs' => __('Total Active Install', 'notificationx'),
                ],
                'third_param' => [
                    'tag_plugin_theme_name' => __('Plugin/Theme Name', 'notificationx'),
                ],
                '_themes' => [
                    'download_stats_vc-installs',
                ]
            ],
        ];
    }

    /**
     * Hooked to nx_before_metabox_load action.
     *
     * @return void
     */
    public function init_fields() {
        parent::init_fields();
        add_filter('nx_link_types', [$this, 'link_types']);
        add_filter('nx_content_fields', [$this, 'content_fields'], 20);
    }
    /**
     * Content Fields function
     *
     * @param array $options
     * @return array
     */
    public function content_fields($fields) {
        $fields['content']['fields']['random_order'] = Rules::is('type', $this->id, true, $fields["content"]['fields']['random_order']);
        return $fields;
    }

    /**
     * Adds option to Link Type field in Content tab.
     *
     * @param array $options
     * @return array
     */
    public function link_types($options) {
        $_options = GlobalFields::get_instance()->normalize_fields([
            'stats_page' => __('Product Page', 'notificationx'),
        ], 'type', $this->id);

        return array_merge($options, $_options);
    }

    public function preview_entry($entry, $settings){
        $entry['image_data'] = array(
            'url'     => NOTIFICATIONX_PUBLIC_URL . 'image/icons/nx-plugin-icon-256.gif',
            'alt'     => '',
            'classes' => 'greview_icon',
        );
        return $entry;
    }

}
