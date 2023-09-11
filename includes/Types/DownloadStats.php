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
        $this->id = 'download_stats';
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
        ];
        parent::__construct();
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
            'url'     => "https://ps.w.org/notificationx/assets/icon-256x256.gif?rev=2783824",
            'alt'     => '',
            'classes' => 'greview_icon',
        );
        return $entry;
    }

}
