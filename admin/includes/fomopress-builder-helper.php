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
            'icon'          => 'database.svg',
            'sections'      => apply_filters('fomopress_source_tab_sections', array(
                'config'        => array(
                    'title'             => __('Select Source', 'fomopress'),
                    'fields'            => array(
                        'display_type'  => apply_filters( 'fomopress_display_type', array(
                            'type'      => 'select',
                            'label'     => __('I would like to display' , 'fomopress'),
                            'default'   => 'press_bar',
                            'options'   => FomoPress_Helper::notification_types(),
                            'toggle'   => [
                                'comments'    => FomoPress_Helper::comments_toggle_data(),
                                'press_bar'   => FomoPress_Helper::press_bar_toggle_data(),
                                'conversions' => FomoPress_Helper::conversions_toggle_data(),
                            ],
                            'hide'   => [
                                'comments' => array(
                                    'fields' => [ 'custom_template', 'custom_contents', 'show_custom_image', 'image_url' ]
                                ),
                                'press_bar' => array(
                                    'fields' => [ 'custom_template', 'comments_template', 'custom_contents', 'notification_preview', 'all_locations', 'countdown_text', 'countdown_time', 'image_url' ]
                                ),
                            ],
                            'priority' => 50
                        ) ),
                        'conversion_from'  => apply_filters( 'fomopress_conversion_from_field', array(
                            'type'      => 'select',
                            'label'     => __('From' , 'fomopress'),
                            'default'   => 'custom',
                            'options'   => FomoPress_Helper::conversion_from(),
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
        'design_tab' => array(
            'title'      => __('Design', 'fomopress'),
            'icon'       => 'screen.svg',
            'sections'   => apply_filters('fomopress_design_tab_sections', array(
                'themes' => array(
                    'title'      => __('Themes', 'fomopress'),
                    'priority' => 5,
                    'fields'   => array(
                        'theme' => array(
                            'type'      => 'theme',
                            'priority'	=> 5,
                            'default'	=> 'theme-one',
                            'toggle'	=> [
                                'customize' => [
                                    'sections' => [  ]
                                ]
                            ],
                            'options'   => apply_filters('fomopress_colored_themes', array(
                                'theme-one' => FOMOPRESS_ADMIN_URL . 'assets/img/themes/1.png',
                                'theme-two' => FOMOPRESS_ADMIN_URL . 'assets/img/themes/1.png',
                                'theme-three' => FOMOPRESS_ADMIN_URL . 'assets/img/themes/1.png',
                                'customize' => FOMOPRESS_ADMIN_URL . 'assets/img/themes/customize.png',
                            )),
                        ),
                    )
                ),
            ))
        ),
        'display_tab' => array(
            'title'         => __('Display', 'fomopress'),
            'icon'          => 'screen.svg',
            'sections'      => apply_filters('fomopress_display_tab_sections', array(
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
                            ],
                            'hide' => [
                                'everywhere' => [ 
                                    'fields' => [ 'all_locations' ]
                                ],
                            ],
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
            'icon'          => 'cog.svg',
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