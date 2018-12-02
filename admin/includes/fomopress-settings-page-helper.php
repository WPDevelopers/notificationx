<?php

function fomopress_settings_array(){
    return apply_filters('fomopress_settings_tab', array(
    
        'general' => array(
            'title' => __( 'General', 'fomopress' ),
            'priority' => 10,
            'button_text' => __( 'Save Settings' ),
            'sections' => apply_filters('fomopress_general_settings_sections', array(
                'notification' => apply_filters('fomopress_notification_settings', array(
                    'title' => __( 'Notification', 'fomopress' ),
                    'priority' => 10,
                    'fields' => array(
                        'cache_limit' => array(
                            'type'      => 'text',
                            'label'     => __('Cache Limit' , 'fomopress'),
                            'default'   => '100',
                            'priority'	=> 10
                        )
                    ),
                )),
                'powered_by' => apply_filters('fomopress_powered_by_settings', array(
                    'title' => __( 'Powered By', 'fomopress' ),
                    'priority' => 15,
                    'fields' => array(
                        'disable_powered_by' => array(
                            'type'        => 'checkbox',
                            'label'       => __('Disable Powered By' , 'fomopress'),
                            'default'     => 0,
                            'disable'     => true,
                            'priority'    => 10,
                            'description' => __('Click, if you want to disable powered by text from notification' , 'fomopress'),
                        )
                    ),
                ))
            )),
        )
    
    ));
}