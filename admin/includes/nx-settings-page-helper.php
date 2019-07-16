<?php

function notificationx_settings_args(){
    return apply_filters('notificationx_settings_tab', array(
        'general' => array(
            'title' => __( 'General', 'notificationx' ),
            'priority' => 10,
            'button_text' => __( 'Save Settings' ),
            'sections' => apply_filters('nx_general_settings_sections', array(
                'notification' => apply_filters('nx_notification_settings', array(
                    'title' => __( 'Notification', 'notificationx' ),
                    'priority' => 10,
                    'fields' => array(
                        'cache_limit' => array(
                            'type'      => 'text',
                            'label'     => __('Cache Limit' , 'notificationx'),
                            'default'   => '100',
                            'priority'	=> 10
                        )
                    ),
                )),
                'powered_by' => apply_filters('nx_powered_by_settings', array(
                    'title' => __( 'Powered By', 'notificationx' ),
                    'priority' => 15,
                    'fields' => array(
                        'disable_powered_by' => array(
                            'type'        => 'checkbox',
                            'label'       => __('Disable Powered By' , 'notificationx'),
                            'default'     => 0,
                            'disable'     => true,
                            'priority'    => 10,
                            'description' => __('Click, if you want to disable powered by text from notification' , 'notificationx'),
                        )
                    ),
                ))
            )),
        ),
        'cache_settings_tab' => array(
            'title' => __( 'Cache Settings', 'notificationx' ),
            'priority' => 11,
            'button_text' => __( 'Save Settings' ),
            'sections' => apply_filters('nx_cache_settings_sections', array(
                'cache_settings' => apply_filters('nx_cache_settings_tab', array(
                    'priority' => 5,
                    'fields' => array(
                        'download_stats_cache_duration' => array(
                            'type'        => 'text',
                            'label'       => __('Download Stats Cache Duration' , 'notificationx'),
                            'description' => __(' minutes (Schedule Duration to fetch new data).' , 'notificationx'),
                            'default'     => '3',
                            'priority'    => 3
                        ),
                        'reviews_cache_duration' => array(
                            'type'        => 'text',
                            'label'       => __('Reviews Cache Duration' , 'notificationx'),
                            'description' => __(' minutes (Schedule Duration to fetch new data).' , 'notificationx'),
                            'default'     => '3',
                            'priority'    => 3
                        )
                    ),
                )),
            )),
        )
    ));
}