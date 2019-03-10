<?php

function notificationx_builder_args() {
    return array(
        'id'           => 'notificationx_metabox_wrapper',
        'title'        => __('NotificationX', 'notificationx'),
        'object_types' => array( 'notificationx' ),
        'context'      => 'normal',
        'priority'     => 'high',
        'show_header'  => false,
        'tabnumber'    => true,
        'layout'       => 'horizontal',
        'tabs'         => apply_filters('nx_builder_tabs', array(
            'source_tab' => array(
                'title'         => __('Source', 'notificationx'),
                'icon'          => 'database.svg',
                'sections'      => array(
                    'config'        => array(
                        'title'             => __('Select Source', 'notificationx'),
                        'fields'            => array(
                            'display_type'  => array(
                                'type'      => 'select',
                                'label'     => __('I would like to display' , 'notificationx'),
                                'default'   => 'press_bar',
                                'options'   => NotificationX_Helper::notification_types(),
                                'toggle'   => [
                                    'comments'    => NotificationX_Helper::comments_toggle_data(),
                                    'press_bar'   => NotificationX_Helper::press_bar_toggle_data(),
                                    'conversions' => NotificationX_Helper::conversions_toggle_data(),
                                ],
                                'hide' => NotificationX_Helper::hide_data( 'display_types' ),
                                'priority' => 50
                            ),
                            'conversion_from'  => array(
                                'type'     => 'select',
                                'label'    => __('From' , 'notificationx'),
                                'default'  => 'custom',
                                'options'  => NotificationX_Helper::conversion_from(),
                                'priority' => 60,
                                'toggle'   => NotificationX_Helper::conversion_toggle(),
                            ),
                            'press_content' => array(
                                'type'     => 'editor',
                                'label'    => __('Content' , 'notificationx'),
                                'priority' => 70,
                            ),
                        ),
                    ),
                )
            ),
            'design_tab' => array(
                'title'      => __('Design', 'notificationx'),
                'icon'       => 'magic-wand.svg',
                'sections'   => array(
                    'bar_themes' => array(
                        'title'      => __('Themes', 'notificationx'),
                        'priority' => 3,
                        'fields'   => array(
                            'bar_theme' => array(
                                'type'      => 'theme',
                                'priority'	=> 5,
                                'default'	=> 'theme-one',
                                'options'   => NotificationX_Helper::bar_colored_themes(),
                            ),
                        )
                    ),
                    'comment_themes' => array(
                        'title'      => __('Comments Themes', 'notificationx'),
                        'priority' => 4,
                        'fields'   => array(
                            'comment_theme' => array(
                                'type'      => 'theme',
                                'priority'	=> 5,
                                'default'	=> 'theme-one',
                                'options'   => NotificationX_Helper::comment_colored_themes(),
                            ),
                        )
                    ),
                    'themes' => array(
                        'title'      => __('Themes', 'notificationx'),
                        'priority' => 5,
                        'fields'   => array(
                            'theme' => array(
                                'type'      => 'theme',
                                'priority'	=> 5,
                                'default'	=> 'theme-one',
                                'options'   => NotificationX_Helper::colored_themes(),
                            ),
                        )
                    ),
                )
            ),
            'display_tab' => array(
                'title'         => __('Display', 'notificationx'),
                'icon'          => 'screen.svg',
                'sections'      => array(
                    'appearance'        => array(
                        'title'    => __('Appearance', 'notificationx'),
                        'priority' => 10,
                        'fields'   => array(
                            'pressbar_position'  => array(
                                'type'      => 'select',
                                'label'     => __('Position' , 'notificationx'),
                                'priority'	=> 40,
                                'options'   => [
                                    'top'       => __('Top' , 'notificationx'),
                                    'bottom'    => __('Bottom' , 'notificationx'),
                                ],
                            ),
                            'conversion_position'  => array(
                                'type'      => 'select',
                                'label'     => __('Position' , 'notificationx'),
                                'priority'	=> 50,
                                'options'   => [
                                    'bottom_left'       => __('Bottom Left' , 'notificationx'),
                                    'bottom_right'      => __('Bottom Right' , 'notificationx'),
                                ],
                            ),
                        ),
                    ),
                    'visibility'        => array(
                        'title'    => __('Visibility', 'notificationx'),
                        'priority' => 1000,
                        'fields'   => array(
                            'show_on'  => array(
                                'type'      => 'select',
                                'label'     => __('Show On' , 'notificationx'),
                                'priority'	=> 10,
                                'options'   => [
                                    'everywhere'       => __('Show Everywhere' , 'notificationx'),
                                    'on_selected'      => __('Show On Selected' , 'notificationx'),
                                    'hide_on_selected' => __('Hide On Selected' , 'notificationx'),
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
                                'label'     => __('Locations' , 'notificationx'),
                                'priority'	=> 20,
                                'options'   => NotificationX_Locations::locations(),
                            ),
                            'show_on_display'  => array(
                                'type'      => 'select',
                                'label'     => __('Display' , 'notificationx'),
                                'priority'	=> 200,
                                'options'   => [
                                    'always'          => __('Always' , 'notificationx'),
                                    'logged_out_user' => __('Logged Out User' , 'notificationx'),
                                    'logged_in_user'  => __('Logged In User' , 'notificationx'),
                                ],
                            )
                        ),
                    ),
                )
            ),
            'finalize_tab' => array(
                'title'         => __('Finalize', 'notificationx'),
                'icon'          => 'cog.svg',
                'sections'      => array(
                    
                )
            ),
        ))
    );
}