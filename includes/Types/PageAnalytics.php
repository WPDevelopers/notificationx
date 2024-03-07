<?php
/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Types;
use NotificationX\GetInstance;
use NotificationX\Modules;

/**
 * Extension Abstract for all Extension.
 * @method static PageAnalytics get_instance($args = null)
 */
class PageAnalytics extends Types {
    /**
     * Instance of Admin
     *
     * @var Admin
     */
	use GetInstance;

    public $priority = 70;
    public $is_pro = true;
    public $module = ['modules_google_analytics'];
    public $default_source    = 'google';


    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->id = 'page_analytics';
        $this->title = __('Page Analytics', 'notificationx');
        $this->popup = [
            "denyButtonText" => __("<a href='https://notificationx.com/docs/google-analytics/' target='_blank'>More Info</a>", "notificationx"),
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            "html"=> __('
                <span>Connect Google Analytics to display the total number of real-time site visitors</span>
                <video id="pro_alert_video_popup" type="text/html" allowfullscreen width="450" height="235" autoplay loop muted>
                    <source src="https://notificationx.com/wp-content/uploads/2024/01/Google-Analytics-Integration-With-NotificationX-How-To-Show-Active-Users-Traffic-in-WordPress.mp4" type="video/mp4">
                </video>
            ', 'notificationx')
        ];
        parent::__construct();
        $this->themes = [
            'pa-theme-one'   => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/analytics/ga-theme-one.jpg',
                'template' => [
                    'first_param'        => 'tag_siteview',
                    'second_param'       => __('marketers', 'notificationx'),
                    'third_param'        => 'ga_title',
                    'custom_third_param' => __('Surfed this page', 'notificationx'),
                    'ga_fourth_param'    => __('in last ', 'notificationx'),
                    'ga_fifth_param'     => __('30', 'notificationx'),
                    'sixth_param'        => 'tag_day',
                ],
                'defaults'                => [
                    'link_button'        => false,
                    'link_type'          => 'none',
                    'show_default_image' => true,
                ],
            ],
            'pa-theme-two'   => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/analytics/pa-theme-one.png',
                'image_shape' => 'rounded',
                'template' => [
                    'first_param'        => 'tag_siteview',
                    'second_param'       => __('people visited', 'notificationx'),
                    'third_param'        => 'ga_title',
                    'custom_third_param' => __('this page', 'notificationx'),
                    'ga_fourth_param'    => __('in last ', 'notificationx'),
                    'ga_fifth_param'     => __('1', 'notificationx'),
                    'sixth_param'        => 'tag_day',
                ],
                'defaults'                => [
                    'link_button'        => false,
                    'link_type'          => 'none',
                    'show_default_image' => true,
                ],
            ],
            'pa-theme-three' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/analytics/pa-theme-two.png',
                'image_shape' => 'circle',
                'template' => [
                    'first_param'        => 'tag_realtime_siteview',
                    'second_param'       => __('people looking', 'notificationx'),
                    'third_param'        => 'ga_title',
                    'custom_third_param' => __('this deal', 'notificationx'),
                    'ga_fourth_param'    => __('right now', 'notificationx'),
                    // need to set this two param unless they won't show up when changing the first param.
                    'ga_fifth_param'     => __('30', 'notificationx'),
                    'sixth_param'        => 'tag_day',
                ],
                'defaults'                => [
                    'link_button'      => false,
                    'link_type'        => 'none',
                    'show_default_image' => true,
                ],
            ],
            'pa-theme-four' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/analytics/pa-theme-four.png',
                'image_shape' => 'circle',
                'template' => [
                    'first_param'        => 'tag_current_page_view',
                    'second_param'       => __('People Is Now Visiting', 'notificationx'),
                    'third_param'        => 'tag_custom',
                    'custom_third_param' => __('Holiday Deal Page', 'notificationx'),
                    'ga_fourth_param'    => __('Check out now & grab exceptional deals', 'notificationx'),
                    // need to set this two param unless they won't show up when changing the first param.
                    'ga_fifth_param'     => __('30', 'notificationx'),
                    'sixth_param'        => 'tag_day',
                ],
                'defaults'                => [
                    'link_button_text'   => __('Grab Now','notificationx'),
                    'link_button'        => true,
                    'link_type'          => 'custom',
                    'custom_url'         => '#',
                    'show_default_image' => true,
                ],
            ],
        ];

        $this->templates = [
            'pa_template_new' => [
                'first_param' => [
                    'tag_siteview'          => __('Total Site View', 'notificationx'),
                    'tag_realtime_siteview' => __('Realtime site view', 'notificationx')
                ],
                'third_param' => [
                    'ga_title'  => __('Site Title', 'notificationx'),
                ],
                'sixth_param' => [
                    'tag_day'   => __('Day', 'notificationx'),
                    'tag_month' => __('Month', 'notificationx'),
                    'tag_year'  => __('Year', 'notificationx'),
                ],
                '_themes' => [
                    'page_analytics_pa-theme-one',
                    'page_analytics_pa-theme-two',
                    'page_analytics_pa-theme-three',
                ],
            ],
            'pa_template_current_page_view' => [
                'first_param' => [
                    'tag_current_page_view' => __('Current Page View', 'notificationx')
                ],
                'third_param' => [
                    'tag_ga_page_title'  => __('Page Title', 'notificationx'),
                ],
                'sixth_param' => [
                    'tag_day'   => __('Day', 'notificationx'),
                    'tag_month' => __('Month', 'notificationx'),
                    'tag_year'  => __('Year', 'notificationx'),
                ],
                '_themes' => [
                    'page_analytics_pa-theme-four',
                ],
            ],
        ];

    }

    /**
     * Runs when modules is enabled.
     *
     * @return void
     */
    public function init(){
        parent::init();

    }

    public function preview_entry($entry, $settings){
        $entry = array_merge($entry, [
            "title"             => "WPDeveloper",

        ]);
        return $entry;
    }


}
