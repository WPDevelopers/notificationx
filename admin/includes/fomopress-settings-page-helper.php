<?php

return apply_filters('fomopress_settings_tab', array(

    'general' => array(
        'title' => __( 'General', 'fomopress' ),
        'priority' => 10,
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
            ))
        )),
    )

));