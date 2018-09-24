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
            'sections'      => apply_filters('fomopress_source_tab_section', array(
                'config'        => array(
                    'title'             => __('Select Source', 'fomopress'),
                    'fields'            => array(
                        'display_type'  => apply_filters( 'fomopress_display_type', array(
                            'type'      => 'select',
                            'label'     => __('I would like to display' , 'fomopress'),
                            'default'   => 'press_bar',
                            'options'   => [
                                'conversions' => __('Converstion' , 'fomopress'),
                                'press_bar'   => __('Notification Bar' , 'fomopress'),
                            ],
                            'toggle'   => [
                                'comments' => [
                                    'sections'    => [ 'behaviour' ],
                                    'fields'   => [ 'conversion_position' ]
                                ],
                                'conversions' => [
                                    'sections'    => [ 'behaviour' ],
                                    'fields'   => [ 'conversion_from', 'conversion_position' ]
                                ],
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
                            'toggle'   => [
                                // 'woocommerce' => array(
                                //     'fields' => array( 'notification_template' )
                                // ),
                            ],
                            'hide'   => [
                                // 'custom' => array(
                                //     'fields' => array( 'notification_template' )
                                // ),
                            ],
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
                        'notification_template'  => apply_filters( 'fomopress_content_section', array(
                            'type'     => 'template',
                            'label'    => __('Notification Template' , 'fomopress'),
                            'priority' => 100,
                            'defaults' => [
                                __('{{name}} from {{city}} signed up for', 'ibx-wpfomo'), '{{title}}', '{{time}}'
                            ],
                            'variables' => [
                                '{{name}}', '{{city}}', '{{title}}', '{{time}}'
                            ],
                        ) )
                    ),
                ),
                'countdown_timer' => array(
                    'title'  => __('Countdown Timer', 'fomopress'),
                    'fields' => array(
                        'enable_countdown' => array(
                            'label' => __('Enable Countdown', 'fomopress'),
                            'type'  => 'checkbox',
                        )
                    )
                )
            ))
        ),
        'display_tab' => array(
            'title'         => __('Display', 'fomopress'),
            'sections'      => apply_filters('fomopress_display_tab_sections', array(
                'visibility'        => array(
                    'title'    => __('Visibility', 'fomopress'),
                    'priority' => 1000,
                    'fields'   => array(
                        'show_on'  => apply_filters( 'fomopress_show_on', array(
                            'type'      => 'select',
                            'label'     => __('Show On' , 'fomopress'),
                            'priority'	=> 50,
                            'options'   => [
                                'everywhere'       => __('Show Everywhere' , 'fomopress'),
                                'on_selected'      => __('Show On Selected' , 'fomopress'),
                                'hide_on_selected' => __('Hide On Selected' , 'fomopress'),
                            ],
                        )),
                        'show_on_display'  => apply_filters( 'fomopress_show_on_display', array(
                            'type'      => 'select',
                            'label'     => __('Display' , 'fomopress'),
                            'priority'	=> 60,
                            'options'   => [
                                'always'          => __('Always' , 'fomopress'),
                                'logged_out_user' => __('Logged Out User' , 'fomopress'),
                                'logged_in_user'  => __('Logged In User' , 'fomopress'),
                            ],
                        ))
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