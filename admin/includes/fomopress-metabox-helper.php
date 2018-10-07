<?php

return array(
    'id'           => 'fomopress_metabox_wrapper',
    'title'        => __('FomoPress', 'fomopress'),
    'object_types' => array( 'fomopress' ),
    'context'      => 'normal',
    'priority'     => 'high',
    'show_header'  => false,
    'tabnumber'    => true,
    'layout'       => 'horizontal',
    'tabs'         => apply_filters( 'fomopress_metabox_tabs', array(
        'source_tab' => array(
            'title'         => __('Source', 'fomopress'),
            'sections'      => apply_filters('fomopress_source_tab_sections', array(
                'config'        => array(
                    'title'             => __('Select Source', 'fomopress'),
                    'fields'            => array(
                        'display_type'  => apply_filters( 'fomopress_display_type', array(
                            'type'      => 'select',
                            'label'     => __('I would like to display' , 'fomopress'),
                            'default'   => 'press_bar',
                            'options'   => [
                                'press_bar'   => __('Notification Bar' , 'fomopress'),
                                'comments'    => __('WP Comments' , 'fomopress'),
                                'conversions' => __('Converstion' , 'fomopress'),
                            ],
                            'toggle'   => [
                                'comments'    => fomopress_comments_toggle_data(),
                                'press_bar'   => fomopress_press_bar_toggle_data(),
                                'conversions' => fomopress_conversions_toggle_data(),
                            ],
                            'hide'   => [
                                'comments' => array(
                                    'fields' => [ 'custom_template', 'custom_contents', 'show_custom_image' ]
                                ),
                                'press_bar' => array(
                                    'fields' => [ 'custom_template', 'comments_template', 'custom_contents', 'notification_preview' ]
                                )
                            ],
                            'priority' => 50
                        ) ),
                        'conversion_from'  => apply_filters( 'fomopress_conversion_from', array(
                            'type'      => 'select',
                            'label'     => __('From' , 'fomopress'),
                            'default'   => 'custom',
                            'options'   => [
                                'custom'      => __( 'Custom', 'fomopress' )
                            ],
                            'priority'	=> 60,
                            'toggle'        => array(
                                'custom'        => array(
                                    'sections' => [ 'image' ],
                                    'fields' => [ 'custom_template', 'custom_contents' ]
                                ),
                            ),
                        ) )
                    ),
                ),
            ))
        ),
        'content_tab' => array(
            'title'         => __('Content', 'fomopress'),
            'sections'      => apply_filters('fomopress_content_tab_sections', array(
                'content_config'        => array(
                    'title'             => __('Content', 'fomopress'),
                    'fields'            => array(
                        'press_content' => array(
                            'type'     => 'editor',
                            'label'    => __('Content' , 'fomopress'),
                            'priority' => 50,
                        ),
                        'button_text' => array(
                            'type'     => 'text',
                            'label'    => __('Button Text' , 'fomopress'),
                            'priority' => 60,
                        ),
                        'button_url' => array(
                            'type'     => 'text',
                            'label'    => __('Button URL' , 'fomopress'),
                            'priority' => 70,
                        ),
                        'custom_template'  => array(
                            'type'     => 'template',
                            'label'    => __('Notification Template' , 'fomopress'),
                            'priority' => 100,
                            'defaults' => [
                                __('{{name}} from {{city}} signed up for', 'fomopress'), '{{title}}', '{{time}}'
                            ],
                            'variables' => [
                                '{{title}}', '{{name}}', '{{email}}', '{{city}}', '{{country}}'
                            ],
                        ),
                        'custom_contents'  => array(
                            'type'     => 'group',
                            'priority' => 110,
                            'title'    => __('Conversion', 'fomopress'),
                            'fields'   => [
                                'title' => array(
                                    'type'     => 'text',
                                    'label'    => __('Title' , 'fomopress'),
                                    'priority' => 5,
                                ),
                                'name' => array(
                                    'type'     => 'text',
                                    'label'    => __('Name' , 'fomopress'),
                                    'priority' => 10,
                                ),
                                'email' => array(
                                    'type'     => 'text',
                                    'label'    => __('Email Address' , 'fomopress'),
                                    'priority' => 15,
                                ),
                                'city' => array(
                                    'type'     => 'text',
                                    'label'    => __('City' , 'fomopress'),
                                    'priority' => 20,
                                ),
                                'country' => array(
                                    'type'     => 'text',
                                    'label'    => __('Country' , 'fomopress'),
                                    'priority' => 25,
                                ),
                                'image' => array(
                                    'type'     => 'media',
                                    'label'    => __('Image' , 'fomopress'),
                                    'priority' => 30,
                                ),
                                'url' => array(
                                    'type'     => 'text',
                                    'label'    => __('URL' , 'fomopress'),
                                    'priority' => 35,
                                ),
                            ],
                        ),
                    ),
                ),
                'countdown_timer' => array(
                    'title'  => __('Countdown Timer', 'fomopress'),
                    'fields' => array(
                        'enable_countdown' => array(
                            'label' => __('Enable Countdown', 'fomopress'),
                            'type'  => 'checkbox',
                        ),
                        'countdown_text' => array(
                            'label' => __('Countdown Text', 'fomopress'),
                            'type'  => 'text',
                        ),
                        'countdown_time' => array(
                            'label' => __('Countdown Time', 'fomopress'),
                            'type'  => 'time',
                        )
                    )
                )
            ))
        ),
        'display_tab' => array(
            'title'         => __('Display', 'fomopress'),
            'sections'      => apply_filters('fomopress_display_tab_sections', array(
                'image' => array(
                    'title'    => __('Image', 'fomopress'),
                    'priority' => 100,
                    'fields'   => array(
                        'show_default_image'  => array(
                            'type'      => 'checkbox',
                            'label'     => __('Show Default Image' , 'fomopress'),
                            'priority'	=> 5,
                            'description' => __('If checked, this will show in notifications.', 'fomopress'),
                        ),
                        'image_url'  => array(
                            'type'      => 'media',
                            'label'     => __('Default Image' , 'fomopress'),
                            'priority'	=> 10,
                        ),
                        'show_custom_image'  => array(
                            'type'      => 'checkbox',
                            'label'     => __('Show Image' , 'fomopress'),
                            'priority'	=> 15,
                            'default'	=> true,
                            'description' => __('If checked, this will show in notifications.', 'fomopress'),
                        ),
                    )
                ),
                'visibility'        => array(
                    'title'    => __('Visibility', 'fomopress'),
                    'priority' => 1000,
                    'fields'   => array(
                        'show_on'  => array(
                            'type'      => 'select',
                            'label'     => __('Show On' , 'fomopress'),
                            'priority'	=> 10,
                            'options'   => [
                                'everywhere'       => __('Show Everywhere' , 'fomopress'),
                                'on_selected'      => __('Show On Selected' , 'fomopress'),
                                'hide_on_selected' => __('Hide On Selected' , 'fomopress'),
                            ],
                            'toggle' => [
                                'on_selected' => [ 
                                    'fields' => [ 'all_locations' ]
                                ],
                                'hide_on_selected' => [ 
                                    'fields' => [ 'all_locations' ]
                                ]
                            ]
                        ),
                        'all_locations'  => array(
                            'type'      => 'select',
                            'label'     => __('Locations' , 'fomopress'),
                            'priority'	=> 20,
                            'options'   => FomoPress_Locations::locations(),
                        ),
                        'show_on_display'  => array(
                            'type'      => 'select',
                            'label'     => __('Display' , 'fomopress'),
                            'priority'	=> 200,
                            'options'   => [
                                'always'          => __('Always' , 'fomopress'),
                                'logged_out_user' => __('Logged Out User' , 'fomopress'),
                                'logged_in_user'  => __('Logged In User' , 'fomopress'),
                            ],
                        )
                    ),
                ),
            ))
        ),
        'customize_tab' => array(
            'title'         => __('Customize', 'fomopress'),
            'sections'      => apply_filters('fomopress_customize_tab_sections', array(
                'appearance'        => array(
                    'title'    => __('Appearance', 'fomopress'),
                    'priority' => 100,
                    'fields'   => array(
                        'pressbar_position'  => array(
                            'type'      => 'select',
                            'label'     => __('Position' , 'fomopress'),
                            'priority'	=> 40,
                            'options'   => [
                                'top'       => __('Top' , 'fomopress'),
                                'bottom'      => __('Bottom' , 'fomopress'),
                            ],
                        ),
                        'conversion_position'  => array(
                            'type'      => 'select',
                            'label'     => __('Position' , 'fomopress'),
                            'priority'	=> 50,
                            'options'   => [
                                'bottom_left'       => __('Bottom Left' , 'fomopress'),
                                'bottom_right'      => __('Bottom Right' , 'fomopress'),
                            ],
                        ),
                        'sticky_bar'  => array(
                            'type'        => 'checkbox',
                            'label'       => __('Sticky Bar?' , 'fomopress'),
                            'priority'    => 60,
                            'default'     => 0,
                            'description' => __('If checked, this will fixed Notification Bar at top or bottom.', 'fomopress'),
                        ),
                        'close_button'  => array(
                            'type'        => 'checkbox',
                            'label'       => __('Show Close Button' , 'fomopress'),
                            'default'     => true,
                            'priority'    => 70,
                            'description' => __('It will display the close button at the top right corner.', 'fomopress'),
                        ),
                        'hide_on_mobile'  => array(
                            'type'        => 'checkbox',
                            'label'       => __('Hide On Mobile' , 'fomopress'),
                            'priority'    => 200,
                            'default'     => 0,
                            'description' => __(' It will hide the notification on mobile devices.', 'fomopress'),
                        ),
                    ),
                ),
                'timing'        => array(
                    'title'       => __('Timing', 'fomopress'),
                    'priority'    => 200,
                    'collapsable' => true,
                    'fields'      => array(
                        'delay_before'  => array(
                            'type'        => 'number',
                            'label'       => __('Delay before 1st notification' , 'fomopress'),
                            'description' => __('seconds', 'fomopress'),
                            'help'        => __('Initial Delay', 'fomopress'),
                            'priority'    => 40,
                            'default'     => 5,
                        ),
                        'initial_delay'  => array(
                            'type'        => 'number',
                            'label'       => __('Initial Delay' , 'fomopress'),
                            'description' => __('seconds', 'fomopress'),
                            'help'        => __('Initial Delay', 'fomopress'),
                            'priority'    => 45,
                            'default'     => 5,
                        ),
                        'auto_hide'  => array(
                            'type'        => 'checkbox',
                            'label'       => __('Auto Hide' , 'fomopress'),
                            'description' => __('If checked, notification bar will be hidden after the time set below.', 'fomopress'),
                            'priority'    => 50,
                            'default'     => 60,
                        ),
                        'hide_after'  => array(
                            'type'        => 'number',
                            'label'       => __('Hide After' , 'fomopress'),
                            'description' => __('seconds', 'fomopress'),
                            'help'        => __('Hide after 60 seconds', 'fomopress'),
                            'priority'    => 55,
                            'default'     => 60,
                        ),
                        'display_for'  => array(
                            'type'        => 'number',
                            'label'       => __('Display For' , 'fomopress'),
                            'description' => __('seconds', 'fomopress'),
                            'help'        => __('Display each notification for * seconds', 'fomopress'),
                            'priority'    => 60,
                            'default'     => 5,
                        ),
                        'delay_between'  => array(
                            'type'        => 'number',
                            'label'       => __('Delay Between' , 'fomopress'),
                            'description' => __('seconds', 'fomopress'),
                            'help'        => __('Delay between each notification', 'fomopress'),
                            'priority'    => 70,
                            'default'     => 5,
                        ),
                    ),
                ),
                'behaviour'        => array(
                    'title'       => __('Behaviour', 'fomopress'),
                    'priority'    => 300,
                    'collapsable' => true,
                    'fields'      => array(
                        'display_last'  => array(
                            'type'        => 'number',
                            'label'       => __('Display the last' , 'fomopress'),
                            'description' => 'conversions',
                            'default'     => 30,
                            'priority'    => 40,
                        ),
                        'display_from'  => array(
                            'type'        => 'number',
                            'label'       => __('Display From The Last' , 'fomopress'),
                            'priority'    => 45,
                            'default'     => 2,
                            'description' => 'days',
                        ),
                        'loop'  => array(
                            'type'        => 'checkbox',
                            'label'       => __('Loop notification' , 'fomopress'),
                            'priority'    => 50,
                            'default'     => true,
                        ),
                        'link_open'  => array(
                            'type'        => 'checkbox',
                            'label'       => __('Open link in new tab' , 'fomopress'),
                            'priority'    => 60,
                            'default'     => false,
                        ),
                    ),
                ),
            ))
        ),
    ))
);