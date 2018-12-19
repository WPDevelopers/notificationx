<?php

function notificationx_settings_args(){
    return apply_filters('fomopress_settings_tab', array(
    
        'general' => array(
            'title' => __( 'General', 'notificationx' ),
            'priority' => 10,
            'button_text' => __( 'Save Settings' ),
            'sections' => apply_filters('fomopress_general_settings_sections', array(
                'notification' => apply_filters('fomopress_notification_settings', array(
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
                'powered_by' => apply_filters('fomopress_powered_by_settings', array(
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
        )
    
    ));
}