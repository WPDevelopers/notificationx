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
 */
class PageAnalytics extends Types {
    /**
     * Instance of Admin
     *
     * @var Admin
     */
	use GetInstance;

    public $priority = 50;
    public $is_pro = true;
    public $module = ['modules_google_analytics'];
    public $default_source    = 'google';


    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->id = 'page_analytics';
        $this->title = __('Page Analytics', 'notificationx');
        parent::__construct();
        $this->themes = [
            'pa-theme-one'   => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/analytics/ga-theme-one.jpg',
                'template' => [
                    'first_param'        => 'tag_siteview',
                    'second_param'       => __('people visited', 'notificationx-pro'),
                    'third_param'        => 'tag_title',
                    'custom_third_param' => __('this page', 'notificationx-pro'),
                    'ga_fourth_param'    => __('in last ', 'notificationx-pro'),
                    'ga_fifth_param'     => __('7', 'notificationx-pro'),
                    'sixth_param'        => 'tag_day',
                ],
            ],
            'pa-theme-two'   => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/analytics/pa-theme-one.png',
                'image_shape' => 'rounded',
                'template' => [
                    'first_param'        => 'tag_siteview',
                    'second_param'       => __('marketers', 'notificationx-pro'),
                    'third_param'        => 'tag_title',
                    'custom_third_param' => __('Surfed this page', 'notificationx-pro'),
                    'ga_fourth_param'    => __('in last ', 'notificationx-pro'),
                    'ga_fifth_param'     => __('30', 'notificationx-pro'),
                    'sixth_param'        => 'tag_day',
                ],
            ],
            'pa-theme-three' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/analytics/pa-theme-two.png',
                'image_shape' => 'circle',
                'template' => [
                    'first_param'        => 'tag_realtime_siteview',
                    'second_param'       => __('people looking', 'notificationx-pro'),
                    'third_param'        => 'tag_title',
                    'custom_third_param' => __('this deal', 'notificationx-pro'),
                    'ga_fourth_param'    => __('right now', 'notificationx-pro'),
                    // need to set this two param unless they won't show up when changing the first param.
                    'ga_fifth_param'     => __('30', 'notificationx-pro'),
                    'sixth_param'        => 'tag_day',
                ],
            ],
        ];

        $this->templates = [
            'pa_template_new' => [
                'first_param' => [
                    'tag_siteview'          => __('Total Site View', 'notificationx-pro'),
                    'tag_realtime_siteview' => __('Realtime site view', 'notificationx-pro')
                ],
                'third_param' => [
                    'tag_title' => __('Site Title', 'notificationx-pro'),
                ],
                'sixth_param' => [
                    'tag_day'   => __('Day', 'notificationx-pro'),
                    'tag_month' => __('Month', 'notificationx-pro'),
                    'tag_year'  => __('Year', 'notificationx-pro'),
                ],
                '_themes' => [
                    'page_analytics_pa-theme-one',
                    'page_analytics_pa-theme-two',
                    'page_analytics_pa-theme-three',
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


}
