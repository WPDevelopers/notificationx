<?php

/**
 * Extension Factory
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Admin;

use NotificationX\GetInstance;

class XSS {
    use GetInstance;

    public function __construct()
    {
        add_filter('nx_settings', [$this, 'save_settings']);
        add_filter('nx_settings_tab', [$this, 'settings_tab']);
    }

    public function save_settings($settings){
        if(isset($settings['xss_code'])){
            unset($settings['xss_code']);
        }
        return $settings;
    }


    public function settings_tab($tabs){

        $tabs[ 'tab-help-settings' ] = apply_filters('nx_settings_tab_help', [
            'id'       => 'tab-help-settings',
            'label'    => __('Help', 'notificationx'),
            'priority' => 50,
            'fields'   => [
                'xss_settings' => array(
                    'name'     => 'xss_settings',
                    'type'     => "section",
                    'label'    => __('Cross Domain Tracking', 'notificationx'),
                    'priority' => 30,
                    'fields'   => array(
                        'xss_code' => array(
                            'name'        => 'xss_code',
                            'type'        => 'textarea',
                            'is_pro'      => true,
                            'copyOnClick' => true,
                            'readOnly'    => true,
                            'label'       => __('Cross Domain Tracking Code', 'notificationx'),
                            'description' => __('Show your Notification Alerts in another site.', 'notificationx'),
                            'default'     => apply_filters('nx_settings_xss_code_default', "<div id='notificationx-frontend'></div>\n<script>....</script>\n<script src='....../crossSite.js'></script>"),
                            'priority'    => 1
                        ),
                    ),
                ),
            ],
        ]);

        return $tabs;
    }

}