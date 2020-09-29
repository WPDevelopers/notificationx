<?php

function notificationx_metabox_args(){
    return array(
        'id'           => 'notificationx_metabox_wrapper',
        'title'        => __('NotificationX', 'notificationx'),
        'object_types' => array( 'notificationx' ),
        'context'      => 'normal',
        'priority'     => 'high',
        'show_header'  => false,
        'tabnumber'    => true,
        'layout'       => 'horizontal',
        'tabs'         => apply_filters('nx_metabox_tabs', array(
            'source_tab' => array(
                'title'         => __('Source', 'notificationx'),
                'icon'          => 'database.svg',
                'sections'      => apply_filters('nx_source_tab_sections', array(
                    'config'        => array(
                        'title'             => __('Select Source', 'notificationx'),
                        'fields'            => array(
                            'has_no_cron' => array(
                                'type'     => 'message',
                                'message'    => __('You have cron disabled. To use this extension make sure CRON is enabled in your WordPress Setup. <a href="URL">Click Here To Learn How To Enable</a>.', 'notificationx'),
                                'priority' => 0,
                            ),
                            'display_type'  => apply_filters( 'nx_display_type', array(
                                'type'         => 'theme',
                                'type_content' => 'text',
                                'inner_title'  => __('Notification Type' , 'notificationx'),
                                'default'      => 'comments',
                                'options'      => NotificationX_Helper::notification_types(),
                                'hide'         => NotificationX_Helper::hide_data( 'display_types' ),
                                'priority'     => 50,
                                'dependency'   => array(
                                    'comments'       => NotificationX_ToggleFields::comments(),
                                    'press_bar'      => NotificationX_Helper::press_bar_toggle_data(),
                                    'conversions'    => NotificationX_ToggleFields::conversions(),
                                    'reviews'        => NotificationX_ToggleFields::reviews(),
                                    'download_stats' => NotificationX_ToggleFields::stats(),
                                    'elearning'      => NotificationX_ToggleFields::elearning(),
                                    'donation'      => NotificationX_ToggleFields::donation(),
                                    'form'          => NotificationX_ToggleFields::form()
                                )
                            ) ),
                            'form_source' => apply_filters('nx_form_source', array(
                                'type'        => 'theme',
                                'inner_title' => __('Source' , 'notificationx'),
                                'default'     => 'cf7',
                                'options'     => NotificationX_Helper::form_source(),
                                'priority'    => 50.1,
                            )),
                            'reviews_source'  => apply_filters('nx_reviews_source', array(
                                'type'        => 'theme',
                                'inner_title' => __('Source' , 'notificationx'),
                                'default'     => 'wp_reviews',
                                'options'     => NotificationX_Helper::reviews_source(),
                                'priority'    => 51,
                            )),
                            'stats_source'  => apply_filters('nx_stats_source', array(
                                'type'        => 'theme',
                                'inner_title' => __('Source' , 'notificationx'),
                                'default'     => 'wp_stats',
                                'options'     => NotificationX_Helper::stats_source(),
                                'priority'    => 52,
                            )),
                            'elearning_source'  => apply_filters('nx_elearning_source', array(
                                'type'        => 'theme',
                                'inner_title' => __('Source' , 'notificationx'),
                                'default'     => 'tutor',
                                'options'     => NotificationX_Helper::elearning_source(),
                                'priority'    => 53,
                                'dependency'  => array(
                                    'tutor' => NotificationX_ToggleFields::tutor(),
                                ),
                            )),
                            'donation_source'  => apply_filters('nx_donation_source', array(
                                'type'        => 'theme',
                                'inner_title' => __('Source' , 'notificationx'),
                                'default'     => 'give',
                                'options'     => NotificationX_Helper::donation_source(),
                                'priority'    => 53,
                                'dependency'  => array(
                                    'give' => NotificationX_ToggleFields::give(),
                                ),
                            )),
                            'comments_source'  => apply_filters('nx_comments_source', array(
                                'type'         => 'theme',
                                'inner_title'  => __('Source' , 'notificationx'),
                                'default'      => 'wp_comments',
                                'options'      => NotificationX_Helper::comments_source(),
                                'priority'     => 54,
                            )),
                            'conversion_from'  => apply_filters('nx_conversion_from', array(
                                'type'        => 'theme',
                                'inner_title' => __('Source' , 'notificationx'),
                                'default'     => 'woocommerce',
                                'options'     => NotificationX_Helper::conversion_from(),
                                'priority'    => 60,
                                'dependency'  => array(
                                    'woocommerce' => NotificationX_ToggleFields::woocommerce(),
                                    'edd' => NotificationX_ToggleFields::edd()
                                ),
                                'hide' => array(
                                    'woocommerce' => NotificationX_ToggleFields::woocommerce_hide(),
                                    'edd' => NotificationX_ToggleFields::edd_hide()
                                ),
                            ))
                        ),
                    ),
                ))
            ),
            'design_tab' => array(
                'title'      => __('Design', 'notificationx'),
                'icon'       => 'magic-wand.svg',
                'sections'   => apply_filters('nx_design_tab_sections', array(
                    'bar_themes' => array(
                        'title'      => __('Themes', 'notificationx'),
                        'priority' => 2,
                        'fields'   => array(
                            'bar_theme' => array(
                                'type'      => 'theme',
                                'priority'	=> 5,
                                'default'	=> 'theme-one',
                                'options'   => NotificationX_Helper::bar_colored_themes(),
                            ),
                            'bar_advance_edit' => array(
                                'type'      => 'adv_checkbox',
                                'priority'	=> 10,
                                'default'	=> 0,
                                'dependency' => array(
                                    1 => [
                                        'sections' => ['bar_design', 'bar_typography']
                                    ]
                                ),
                                'hide' => array(
                                    0 => [
                                        'sections' => ['bar_design', 'bar_typography']
                                    ]
                                )
                            ),
                        )
                    ),
                    'form_themes' => array(
                        'title'      => __('Themes', 'notificationx'),
                        'priority' => 3,
                        'fields'   => array(
                            'form_theme' => array(
                                'type'      => 'theme',
                                'priority'	=> 5,
                                'default'	=> 'theme-one',
                                'options'   => NotificationX_Helper::form_themes(),
                            ),
                            'form_advance_edit' => array(
                                'type'      => 'adv_checkbox',
                                'priority'	=> 10,
                                'default'	=> 0,
                                'dependency' => array(
                                    1 => [
                                        'sections' => ['design', 'image_design', 'typography']
                                    ]
                                ),
                                'hide' => array(
                                    0 => [
                                        'sections' => ['design', 'image_design', 'typography']
                                    ]
                                )
                            ),
                        )
                    ),
                    'comment_themes' => array(
                        'title'      => __('Themes', 'notificationx'),
                        'priority' => 4,
                        'fields'   => array(
                            'comment_theme' => array(
                                'type'      => 'theme',
                                'priority'	=> 5,
                                'default'	=> 'theme-one',
                                'options'   => NotificationX_Helper::comment_colored_themes(),
                            ),
                            'comment_advance_edit' => array(
                                'type'      => 'adv_checkbox',
                                'priority'	=> 10,
                                'default'	=> 0,
                                'dependency' => array(
                                    1 => [
                                        'sections' => ['comment_design', 'comment_image_design', 'comment_typography']
                                    ]
                                ),
                                'hide' => array(
                                    0 => [
                                        'sections' => ['comment_design', 'comment_image_design', 'comment_typography']
                                    ]
                                )
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
                            'advance_edit' => array(
                                'type'      => 'adv_checkbox',
                                'priority'	=> 10,
                                'default'	=> 0,
                                'dependency' => array(
                                    1 => [
                                        'sections' => ['design', 'image_design', 'typography']
                                    ]
                                ),
                                'hide' => array(
                                    0 => [
                                        'sections' => ['design', 'image_design', 'typography']
                                    ]
                                ),
                            ),
                        )
                    ),
                    'elearning_themes' => array(
                        'title'      => __('Themes', 'notificationx'),
                        'priority' => 5,
                        'fields'   => array(
                            'elearning_theme' => array(
                                'type'      => 'theme',
                                'priority'	=> 5,
                                'default'	=> 'theme-one',
                                'options'   => NotificationX_Helper::elearning_themes(),
                            ),
                            'elearning_advance_edit' => array(
                                'type'      => 'adv_checkbox',
                                'priority'	=> 10,
                                'default'	=> 0,
                                'dependency' => array(
                                    1 => [
                                        'sections' => ['design', 'image_design', 'typography']
                                    ]
                                ),
                                'hide' => array(
                                    0 => [
                                        'sections' => ['design', 'image_design', 'typography']
                                    ]
                                ),
                            ),
                        )
                    ),
                    'donation_themes' => array(
                        'title'      => __('Themes', 'notificationx'),
                        'priority' => 5,
                        'fields'   => array(
                            'donation_theme' => array(
                                'type'      => 'theme',
                                'priority'	=> 5,
                                'default'	=> 'theme-one',
                                'options'   => NotificationX_Helper::donation_themes(),
                            ),
                            'donation_advance_edit' => array(
                                'type'      => 'adv_checkbox',
                                'priority'	=> 10,
                                'default'	=> 0,
                                'dependency' => array(
                                    1 => [
                                        'sections' => ['design', 'image_design', 'typography']
                                    ]
                                ),
                                'hide' => array(
                                    0 => [
                                        'sections' => ['design', 'image_design', 'typography']
                                    ]
                                ),
                            ),
                        )
                    ),
                    'design' => array(
                        'title'    => __('Design', 'notificationx'),
                        'priority' => 6,
                        'reset'    => true,
                        'fields'   => array(
                            'bg_color' => array(
                                'type'      => 'colorpicker',
                                'label'     => __('Background Color' , 'notificationx'),
                                'priority'	=> 5,
                                'default'	=> ''
                            ),
                            'text_color' => array(
                                'type'      => 'colorpicker',
                                'label'     => __('Text Color' , 'notificationx'),
                                'priority'	=> 10,
                                'default'	=> ''
                            ),
                            'border' => array(
                                'type'      => 'checkbox',
                                'label'     => __('Want Border?' , 'notificationx'),
                                'priority'	=> 15,
                                'default'	=> 0,
                                'dependency' => array(
                                    1 => [
                                        'fields' => [ 'border_size', 'border_style', 'border_color' ]
                                    ],
                                ),
                                'hide' => array(
                                    0 => [
                                        'fields' => [ 'border_size', 'border_style', 'border_color' ]
                                    ]
                                )
                            ),
                            'border_size' => array(
                                'type'      => 'number',
                                'label'     => __('Border Size' , 'notificationx'),
                                'priority'	=> 20,
                                'default'	=> '1',
                                'description'	=> 'px',
                            ),
                            'border_style' => array(
                                'type'     => 'select',
                                'label'    => __('Border Style' , 'notificationx'),
                                'priority' => 25,
                                'default'  => 'solid',
                                'options'  => [
                                    'solid'  => __('Solid', 'notificationx'),
                                    'dashed' => __('Dashed', 'notificationx'),
                                    'dotted' => __('Dotted', 'notificationx'),
                                ],
                            ),
                            'border_color' => array(
                                'type'     => 'colorpicker',
                                'label'    => __('Border Color' , 'notificationx'),
                                'priority' => 30,
                                'default'  => ''
                            ),
                        )
                    ),
                    // 'form_design' => array(
                    //     'title'    => __('Design', 'notificationx'),
                    //     'priority' => 6,
                    //     'reset'    => true,
                    //     'fields'   => array(
                    //         'form_bg_color' => array(
                    //             'type'      => 'colorpicker',
                    //             'label'     => __('Background Color' , 'notificationx'),
                    //             'priority'	=> 5,
                    //             'default'	=> ''
                    //         ),
                    //         'form_text_color' => array(
                    //             'type'      => 'colorpicker',
                    //             'label'     => __('Text Color' , 'notificationx'),
                    //             'priority'	=> 10,
                    //             'default'	=> ''
                    //         ),
                    //         'form_border' => array(
                    //             'type'      => 'checkbox',
                    //             'label'     => __('Want Border?' , 'notificationx'),
                    //             'priority'	=> 15,
                    //             'default'	=> 0,
                    //             'dependency' => array(
                    //                 1 => [
                    //                     'fields' => [ 'form_border_size', 'form_border_style', 'form_border_color' ]
                    //                 ],
                    //             ),
                    //             'hide' => array(
                    //                 0 => [
                    //                     'fields' => [ 'form_border_size', 'form_border_style', 'form_border_color' ]
                    //                 ]
                    //             )
                    //         ),
                    //         'form_border_size' => array(
                    //             'type'      => 'number',
                    //             'label'     => __('Border Size' , 'notificationx'),
                    //             'priority'	=> 20,
                    //             'default'	=> '1',
                    //             'description'	=> 'px',
                    //         ),
                    //         'form_border_style' => array(
                    //             'type'     => 'select',
                    //             'label'    => __('Border Style' , 'notificationx'),
                    //             'priority' => 25,
                    //             'default'  => 'solid',
                    //             'options'  => [
                    //                 'solid'  => __('Solid', 'notificationx'),
                    //                 'dashed' => __('Dashed', 'notificationx'),
                    //                 'dotted' => __('Dotted', 'notificationx'),
                    //             ],
                    //         ),
                    //         'form_border_color' => array(
                    //             'type'     => 'colorpicker',
                    //             'label'    => __('Border Color' , 'notificationx'),
                    //             'priority' => 30,
                    //             'default'  => ''
                    //         ),
                    //     )
                    // ),
                    'comment_design' => array(
                        'title'    => __('Design', 'notificationx'),
                        'priority' => 7,
                        'reset'    => true,
                        'fields'   => array(
                            'comment_bg_color' => array(
                                'type'      => 'colorpicker',
                                'label'     => __('Background Color' , 'notificationx'),
                                'priority'	=> 5,
                                'default'	=> ''
                            ),
                            'comment_text_color' => array(
                                'type'      => 'colorpicker',
                                'label'     => __('Text Color' , 'notificationx'),
                                'priority'	=> 10,
                                'default'	=> ''
                            ),
                            'comment_border' => array(
                                'type'      => 'checkbox',
                                'label'     => __('Want Border?' , 'notificationx'),
                                'priority'	=> 15,
                                'default'	=> 0,
                                'dependency' => array(
                                    1 => [
                                        'fields' => [ 'comment_border_size', 'comment_border_style', 'comment_border_color' ]
                                    ],
                                ),
                                'hide' => array(
                                    0 => [
                                        'fields' => [ 'comment_border_size', 'comment_border_style', 'comment_border_color' ]
                                    ]
                                ),
                            ),
                            'comment_border_size' => array(
                                'type'      => 'number',
                                'label'     => __('Border Size' , 'notificationx'),
                                'priority'	=> 20,
                                'default'	=> '1',
                                'description'	=> 'px',
                            ),
                            'comment_border_style' => array(
                                'type'      => 'select',
                                'label'     => __('Border Style' , 'notificationx'),
                                'priority'	=> 25,
                                'default'	=> 'solid',
                                'options'	=> [
                                    'solid' => __('Solid', 'notificationx'),
                                    'dashed' => __('Dashed', 'notificationx'),
                                    'dotted' => __('Dotted', 'notificationx'),
                                ],
                            ),
                            'comment_border_color' => array(
                                'type'      => 'colorpicker',
                                'label'     => __('Border Color' , 'notificationx'),
                                'priority'	=> 30,
                                'default'	=> ''
                            ),
                        )
                    ),
                    'image_design' => array(
                        'title'      => __('Image Appearance', 'notificationx'),
                        'priority' => 8,
                        'reset'    => true,
                        'fields'   => array(
                            'image_shape' => array(
                                'type'      => 'select',
                                'label'     => __('Image Shape' , 'notificationx'),
                                'priority'	=> 5,
                                'default'	=> 'circle',
                                'options'	=> [
                                    'circle' => __('Circle', 'notificationx'),
                                    'rounded' => __('Rounded', 'notificationx'),
                                    'square' => __('Square', 'notificationx'),
                                ],
                            ),
                            'image_position' => array(
                                'type'      => 'select',
                                'label'     => __('Position' , 'notificationx'),
                                'priority'	=> 10,
                                'default'	=> 'left',
                                'options'	=> [
                                    'left' => __('Left', 'notificationx'),
                                    'right' => __('Right', 'notificationx'),
                                ],
                            ),
                        )
                    ),
                    // 'form_image_design' => array(
                    //     'title'      => __('Image Appearance', 'notificationx'),
                    //     'priority' => 8,
                    //     'reset'    => true,
                    //     'fields'   => array(
                    //         'form_image_shape' => array(
                    //             'type'      => 'select',
                    //             'label'     => __('Image Shape' , 'notificationx'),
                    //             'priority'	=> 5,
                    //             'default'	=> 'circle',
                    //             'options'	=> [
                    //                 'circle' => __('Circle', 'notificationx'),
                    //                 'rounded' => __('Rounded', 'notificationx'),
                    //                 'square' => __('Square', 'notificationx'),
                    //             ],
                    //         ),
                    //         'form_image_position' => array(
                    //             'type'      => 'select',
                    //             'label'     => __('Position' , 'notificationx'),
                    //             'priority'	=> 10,
                    //             'default'	=> 'left',
                    //             'options'	=> [
                    //                 'left' => __('Left', 'notificationx'),
                    //                 'right' => __('Right', 'notificationx'),
                    //             ],
                    //         ),
                    //     )
                    // ),
                    'comment_image_design' => array(
                        'title'      => __('Image Appearance', 'notificationx'),
                        'priority' => 9,
                        'reset'    => true,
                        'fields'   => array(
                            'comment_image_shape' => array(
                                'type'      => 'select',
                                'label'     => __('Image Shape' , 'notificationx'),
                                'priority'	=> 5,
                                'default'	=> 'circle',
                                'options'	=> [
                                    'circle' => __('Circle', 'notificationx'),
                                    'rounded' => __('Rounded', 'notificationx'),
                                    'square' => __('Square', 'notificationx'),
                                ],
                            ),
                            'comment_image_position' => array(
                                'type'      => 'select',
                                'label'     => __('Position' , 'notificationx'),
                                'priority'	=> 10,
                                'default'	=> 'left',
                                'options'	=> [
                                    'left' => __('Left', 'notificationx'),
                                    'right' => __('Right', 'notificationx'),
                                ],
                            ),
                        )
                    ),
                    'typography' => array(
                        'title'      => __('Typography', 'notificationx'),
                        'priority' => 10,
                        'reset'    => true,
                        'fields'   => array(
                            'first_font_size' => array(
                                'type'      => 'number',
                                'label'     => __('Font Size' , 'notificationx'),
                                'priority'	=> 5,
                                'default'	=> '13',
                                'description'	=> 'px',
                                'help'	=> __( 'This font size will be applied for <mark>first</mark> row', 'notificationx' ),
                            ),
                            'second_font_size' => array(
                                'type'      => 'number',
                                'label'     => __('Font Size' , 'notificationx'),
                                'priority'	=> 10,
                                'default'	=> '14',
                                'description'	=> 'px',
                                'help'	=> __( 'This font size will be applied for <mark>second</mark> row', 'notificationx' ),
                            ),
                            'third_font_size' => array(
                                'type'      => 'number',
                                'label'     => __('Font Size' , 'notificationx'),
                                'priority'	=> 15,
                                'default'	=> '11',
                                'description'	=> 'px',
                                'help'	=> __( 'This font size will be applied for <mark>third</mark> row', 'notificationx' ),
                            ),
                        )
                    ),
                    // 'form_typography' => array(
                    //     'title'      => __('Typography', 'notificationx'),
                    //     'priority' => 10,
                    //     'reset'    => true,
                    //     'fields'   => array(
                    //         'form_first_font_size' => array(
                    //             'type'      => 'number',
                    //             'label'     => __('Font Size' , 'notificationx'),
                    //             'priority'	=> 5,
                    //             'default'	=> '13',
                    //             'description'	=> 'px',
                    //             'help'	=> __( 'This font size will be applied for <mark>first</mark> row', 'notificationx' ),
                    //         ),
                    //         'form_second_font_size' => array(
                    //             'type'      => 'number',
                    //             'label'     => __('Font Size' , 'notificationx'),
                    //             'priority'	=> 10,
                    //             'default'	=> '14',
                    //             'description'	=> 'px',
                    //             'help'	=> __( 'This font size will be applied for <mark>second</mark> row', 'notificationx' ),
                    //         ),
                    //         'form_third_font_size' => array(
                    //             'type'      => 'number',
                    //             'label'     => __('Font Size' , 'notificationx'),
                    //             'priority'	=> 15,
                    //             'default'	=> '11',
                    //             'description'	=> 'px',
                    //             'help'	=> __( 'This font size will be applied for <mark>third</mark> row', 'notificationx' ),
                    //         ),
                    //     )
                    // ),
                    'comment_typography' => array(
                        'title'      => __('Typography', 'notificationx'),
                        'priority' => 11,
                        'reset'    => true,
                        'fields'   => array(
                            'comment_first_font_size' => array(
                                'type'      => 'number',
                                'label'     => __('Font Size' , 'notificationx'),
                                'priority'	=> 5,
                                'default'	=> '13',
                                'description'	=> 'px',
                                'help'	=> __( 'This font size will be applied for <mark>first</mark> row', 'notificationx' ),
                            ),
                            'comment_second_font_size' => array(
                                'type'      => 'number',
                                'label'     => __('Font Size' , 'notificationx'),
                                'priority'	=> 10,
                                'default'	=> '14',
                                'description'	=> 'px',
                                'help'	=> __( 'This font size will be applied for <mark>second</mark> row', 'notificationx' ),
                            ),
                            'comment_third_font_size' => array(
                                'type'      => 'number',
                                'label'     => __('Font Size' , 'notificationx'),
                                'priority'	=> 15,
                                'default'	=> '11',
                                'description'	=> 'px',
                                'help'	=> __( 'This font size will be applied for <mark>third</mark> row', 'notificationx' ),
                            ),
                        )
                    ),
                    'bar_design' => array(
                        'title'      => __('Design', 'notificationx'),
                        'priority' => 12,
                        'reset'    => true,
                        'fields'   => array(
                            'bar_bg_color' => array(
                                'type'      => 'colorpicker',
                                'label'     => __('Background Color' , 'notificationx'),
                                'priority'	=> 5,
                                'default'	=> ''
                            ),
                            'bar_text_color' => array(
                                'type'      => 'colorpicker',
                                'label'     => __('Text Color' , 'notificationx'),
                                'priority'	=> 10,
                                'default'	=> ''
                            ),
                            'bar_btn_bg' => array(
                                'type'      => 'colorpicker',
                                'label'     => __('Button Background Color' , 'notificationx'),
                                'priority'	=> 11,
                                'default'	=> ''
                            ),
                            'bar_btn_text_color' => array(
                                'type'      => 'colorpicker',
                                'label'     => __('Button Text Color' , 'notificationx'),
                                'priority'	=> 12,
                                'default'	=> ''
                            ),
                            'bar_counter_bg' => array(
                                'type'      => 'colorpicker',
                                'label'     => __('Countdown Background Color' , 'notificationx'),
                                'priority'	=> 13,
                                'default'	=> ''
                            ),
                            'bar_counter_text_color' => array(
                                'type'      => 'colorpicker',
                                'label'     => __('Countdown Text Color' , 'notificationx'),
                                'priority'	=> 14,
                                'default'	=> ''
                            ),
                            'bar_close_color' => array(
                                'type'      => 'colorpicker',
                                'label'     => __('Close Button Color' , 'notificationx'),
                                'priority'	=> 15,
                                'default'	=> ''
                            ),
                            'bar_close_position' => array(
                                'type'      => 'select',
                                'label'     => __('Close Button Position' , 'notificationx'),
                                'priority'	=> 15,
                                'default'	=> 'right',
                                'options'	=> [
                                    'left' => __('Left', 'notificationx'),
                                    'right' => __('Right', 'notificationx'),
                                ]
                            ),
                        )
                    ),
                    'bar_typography' => array(
                        'title'      => __('Typography', 'notificationx'),
                        'priority' => 13,
                        'reset'    => true,
                        'fields'   => array(
                            'bar_font_size' => array(
                                'type'      => 'number',
                                'label'     => __('Font Size' , 'notificationx'),
                                'priority'	=> 5,
                                'default'	=> '13',
                                'description'	=> 'px',
                                'help'	=> __( 'This font size will be applied for <mark>first</mark> row', 'notificationx' ),
                            ),
                        )
                    ),
                ))
            ),
            'content_tab' => array(
                'title'         => __('Content', 'notificationx'),
                'icon'          => 'pencil.svg',
                'sections'      => apply_filters('nx_content_tab_sections', array(
                    'content_config'        => array(
                        'title'             => __('Content', 'notificationx'),
                        'fields'            => array(
                            'press_content' => array(
                                'type'     => 'editor',
                                'label'    => __('Content' , 'notificationx'),
                                'priority' => 50,
                            ),
                            'button_text' => array(
                                'type'     => 'text',
                                'label'    => __('Button Text' , 'notificationx'),
                                'priority' => 60,
                            ),
                            'button_url' => array(
                                'type'     => 'text',
                                'label'    => __('Button URL' , 'notificationx'),
                                'priority' => 70,
                            ),
                        ),
                    ),
                    'countdown_timer' => array(
                        'title'  => __('Countdown Timer', 'notificationx'),
                        'fields' => array(
                            'enable_countdown' => array(
                                'label' => __('Enable Countdown', 'notificationx'),
                                'type'  => 'checkbox',
                                'dependency'  => [
                                    1 => [
                                        'fields' => ['countdown_text', 'countdown_expired_text', 'evergreen_timer', 'countdown_start_date', 'countdown_end_date']
                                    ]
                                ],
                                'hide' => array(
                                    0 => [
                                        'fields' => ['countdown_text', 'countdown_expired_text', 'evergreen_timer', 'countdown_start_date', 'countdown_end_date', 'time_rotation', 'time_randomize', 'time_reset' ]
                                    ]
                                )
                            ),
                            'evergreen_timer' => array(
                                'label'       => __('Evergreen Timer', 'notificationx'),
                                'type'        => 'checkbox',
                                'is_pro'      => true,
                                'switch'      => true,
                                'description' => sprintf( '%s, <a target="_blank" href="%s">%s</a>', __( 'To configure Evergreen Timer', 'notificationx' ), 'https://notificationx.com/docs/evergreen-timer/', 'check out this doc' ),
                                'hide'        => array(
                                    0 => [
                                        'fields' => [ 'time_rotation', 'time_randomize', 'time_reset' ]
                                    ],
                                ),
                                'dependency' => array(
                                    1 => [
                                        'fields' => [ 'time_rotation', 'time_randomize', 'time_reset' ]
                                    ],
                                )
                            ),
                            'countdown_text' => array(
                                'label' => __('Countdown Text', 'notificationx'),
                                'type'  => 'text',
                            ),
                            'countdown_expired_text' => array(
                                'label' => __('Expired Text', 'notificationx'),
                                'type'  => 'text',
                                'default'  => __('Expired', 'notificationx'),
                            ),
                            'countdown_start_date' => array(
                                'label' => __('Start Date', 'notificationx'),
                                'type'  => 'datepicker',
                            ),
                            'countdown_end_date' => array(
                                'label' => __('End Date', 'notificationx'),
                                'type'  => 'datepicker',
                            ),
                            'time_rotation' => array(
                                'label' => __('Time Rotation', 'notificationx'),
                                'type'  => 'text',
                                'description' => 'hours'
                            ),
                            'time_randomize' => array(
                                'label' => __('Randomize', 'notificationx'),
                                'type'  => 'checkbox'
                            ),
                            'time_randomize_between' => array(
                                'label' => __('Time Between', 'notificationx'),
                                'type'  => 'datepicker',
                                'multiple' => true,
                                'fields'   => [
                                    'start_time' => array(
                                        'type'     => 'number',
                                        'only'  => 'timepicker',
                                        'label'    => __('Start Time' , 'notificationx'),
                                        'priority' => 0,
                                        'default' => 6
                                    ),
                                    'end_time' => array(
                                        'type'     => 'number',
                                        'only'  => 'timepicker',
                                        'label'    => __('End Time' , 'notificationx'),
                                        'priority' => 1,
                                        'default' => 12
                                    ),
                                ]
                            ),
                            'time_reset' => array(
                                'label' => __('Daily Time Reset', 'notificationx'),
                                'type'  => 'checkbox',
                            ),
                            'close_forever' => array(
                                'label' => __('Permanent Close', 'notificationx'),
                                'type'  => 'checkbox',
                            ),
                        )
                    ),
                    'link_options' => array(
                        'title' => __('Link Options', 'notificationx'),
                        'fields' => array(
                            'comments_url' => array(
                                'label' => __('Link Type', 'notificationx'),
                                'type'  => 'select',
                                'priority'	=> 10,
                                'default'	=> 'comment_url',
                                'options'	=> apply_filters('nx_comment_link_options', array(
                                    'none'             => __('None', 'notificationx'),
                                    'comment_url'      => __( 'Comment URL', 'notificationx' ),
                                )),
                            ),
                        )
                    ),
                    'conversion_link_options' => array(
                        'title' => __('Link Options', 'notificationx'),
                        'fields' => array(
                            'conversion_url' => array(
                                'label' => __('Link Type', 'notificationx'),
                                'type'  => 'select',
                                'priority'	=> 10,
                                'default'	=> 'product_page',
                                'options'	=> apply_filters('nx_conversion_link_options', array(
                                    'none'             => __('None', 'notificationx'),
                                    'product_page' => __( 'Product Page', 'notificationx' ),
                                )),
                            ),
                        )
                    ),
                    'rs_link_options' => array(
                        'title' => __('Link Options', 'notificationx'),
                        'fields' => array(
                            'rs_url' => array(
                                'label' => __('Link Type', 'notificationx'),
                                'type'  => 'select',
                                'priority'	=> 10,
                                'default'	=> 'product_page',
                                'options'	=> apply_filters('nx_rs_link_options', array(
                                    'none'             => __('None', 'notificationx'),
                                    'product_page' => __( 'Product Page', 'notificationx' ),
                                )),
                            ),
                        )
                    ),
                    'elearning_link_options' => array(
                        'title' => __('Link Options', 'notificationx'),
                        'fields' => array(
                            'elearning_url' => array(
                                'label' => __('Link Type', 'notificationx'),
                                'type'  => 'select',
                                'priority'	=> 10,
                                'default'	=> 'product_page',
                                'options'	=> apply_filters('nx_elearning_link_options', array(
                                    'none'             => __('None', 'notificationx'),
                                    'product_page' => __( 'Course Page', 'notificationx' ),
                                )),
                            ),
                        )
                    ),
                    'donation_link_options' => array(
                        'title' => __('Link Options', 'notificationx'),
                        'fields' => array(
                            'donation_url' => array(
                                'label' => __('Link Type', 'notificationx'),
                                'type'  => 'select',
                                'priority'	=> 10,
                                'default'	=> 'product_page',
                                'options'	=> apply_filters('nx_donation_link_options', array(
                                    'none'         => __('None', 'notificationx'),
                                    'product_page' => __( 'Donation Form Page', 'notificationx' ),
                                )),
                            ),
                        )
                    ),
                ))
            ),
            'display_tab' => array(
                'title'         => __('Display', 'notificationx'),
                'icon'          => 'screen.svg',
                'sections'      => apply_filters('nx_display_tab_sections', array(
                    'image' => array(
                        'title'    => __('Image', 'notificationx'),
                        'priority' => 100,
                        'fields'   => array(
                            'show_default_image'  => array(
                                'type'      => 'checkbox',
                                'label'     => __('Show Default Image' , 'notificationx'),
                                'priority'	=> 5,
                                'hide' => array(
                                    0 => array(
                                        'fields' => [ 'image_url', 'default_avatar' ]
                                    ),
                                ),
                                'dependency' => array(
                                    1 => array(
                                        'fields' => [ 'image_url', 'default_avatar' ]
                                    ),
                                ),
                                'description' => __('If checked, this will show in notifications.', 'notificationx'),
                            ),
                            'default_avatar'  => array(
                                'type'      => 'theme',
                                'label'     => __('Choose an Image' , 'notificationx'),
                                'priority'	=> 6,
                                'description' => __('If checked, this will show in notifications.', 'notificationx'),
                                'options' => array(
                                    'verified.svg' => array(
                                        'source' => NOTIFICATIONX_PUBLIC_URL . 'assets/img/icons/verified.svg',
                                        'title' => __( 'Verified', 'notificationx' )
                                    ),
                                    'flames.svg' => array(
                                        'source' => NOTIFICATIONX_PUBLIC_URL . 'assets/img/icons/flames.svg',
                                        'title' => __( 'Flames', 'notificationx' )
                                    ),
                                    'flames.gif' => array(
                                        'source' => NOTIFICATIONX_PUBLIC_URL . 'assets/img/icons/flames.gif',
                                        'title' => __( 'Flames GIF', 'notificationx' )
                                    ),
                                    'pink-face-looped.gif' => array(
                                        'source' => NOTIFICATIONX_PUBLIC_URL . 'assets/img/icons/pink-face-looped.gif',
                                        'title' => __( 'Pink Face', 'notificationx' )
                                    ),
                                    'blue-face-non-looped.gif' => array(
                                        'source' => NOTIFICATIONX_PUBLIC_URL . 'assets/img/icons/blue-face-non-looped.gif',
                                        'title' => __( 'Blue Face', 'notificationx' )
                                    ),
                                    //TODO: none
                                )
                            ),
                            'image_url'  => array(
                                'type'      => 'media',
                                'label'     => __('Upload an Image' , 'notificationx'),
                                'priority'	=> 10,
                            ),
                            'show_notification_image'  => array(
                                'type'      => 'select',
                                'label'     => __('Image' , 'notificationx'),
                                'priority'	=> 15,
                                'options'   => apply_filters('nx_show_image_options', array(
                                    'product_image' => __('Featured Image' , 'notificationx'),
                                    'gravatar'      => __('Gravatar' , 'notificationx'),
                                    'none'          => __('None' , 'notificationx'),
                                )),
                            ),
                        )
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
                                'dependency' => array(
                                    'on_selected' => [
                                        'fields' => [ 'all_locations' ]
                                    ],
                                    'hide_on_selected' => [
                                        'fields' => [ 'all_locations' ]
                                    ]
                                ),
                                'hide' => [
                                    'everywhere' => [
                                        'fields' => [ 'all_locations' ]
                                    ],
                                ],
                                'default'	=> 'everywhere'
                            ),
                            'all_locations'  => array(
                                'type'      => 'select',
                                'label'     => __('Locations' , 'notificationx'),
                                'priority'	=> 20,
                                'options'   => NotificationX_Locations::locations(),
                            ),
                            'show_on_display'  => array(
                                'type'      => 'select',
                                'label'     => __('Display For' , 'notificationx'),
                                'priority'	=> 200,
                                'options'   => [
                                    'always'          => __('Everyone' , 'notificationx'),
                                    'logged_out_user' => __('Logged Out User' , 'notificationx'),
                                    'logged_in_user'  => __('Logged In User' , 'notificationx'),
                                ],
                                'help' => sprintf('<a target="_blank" rel="nofollow" href="https://notificationx.com/in/pro-display-control">%s</a>', __('More Control in Pro', 'notificationx') )
                            ),
                        ),
                    ),
                ))
            ),
            'customize_tab' => array(
                'title'         => __('Customize', 'notificationx'),
                'icon'          => 'cog.svg',
                'sections'      => apply_filters('nx_customize_tab_sections', array(
                    'appearance'        => array(
                        'title'    => __('Appearance', 'notificationx'),
                        'priority' => 100,
                        'fields'   => array(
                            'pressbar_position'  => array(
                                'type'      => 'select',
                                'label'     => __('Position' , 'notificationx'),
                                'priority'	=> 40,
                                'options'   => [
                                    'top'       => __('Top' , 'notificationx'),
                                    'bottom'      => __('Bottom' , 'notificationx'),
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
                            'conversion_size'  => array(
                                'type'      => 'number',
                                'label'     => __('Notification Size' , 'notificationx'),
                                'priority'	=> 51,
                                'default'   => 500,
                                'help' => __('Set a max width for notification.', 'notificationx'),
                            ),
                            'sticky_bar'  => array(
                                'type'        => 'checkbox',
                                'label'       => __('Sticky Bar?' , 'notificationx'),
                                'priority'    => 60,
                                'default'     => 0,
                                'description' => __('If checked, this will fixed Notification Bar at top or bottom.', 'notificationx'),
                            ),
                            'pressbar_body'  => array(
                                'type'      => 'checkbox',
                                'label'     => __('Display Overlapping' , 'notificationx'),
                                'priority'	=> 61,
                                'description' => __('Show Notification Bar overlapping content instead of pushing', 'notificationx'),
                            ),
                            'close_button'  => array(
                                'type'        => 'checkbox',
                                'label'       => __('Display Close Option' , 'notificationx'),
                                'default'     => true,
                                'priority'    => 70,
                                'description' => __('Display a close button.', 'notificationx'),
                            ),
                            'hide_on_mobile'  => array(
                                'type'        => 'checkbox',
                                'label'       => __('Mobile Visibility' , 'notificationx'),
                                'priority'    => 200,
                                'default'     => 0,
                                'description' => __('Hide NotificationX on mobile.', 'notificationx'),
                            ),
                            'sound_checkbox'  => array(
                                'type'     => 'checkbox',
                                'priority' => 201,
                                'default'  => 0,
                                'class'    => 'hidden',
                            ),
                        ),
                    ),
                    'queue_management' => array(
                        'title' => __( 'Queue Management', 'notificationx' ),
                        'priority' => 150,
                        'fields' => array(
                            'global_queue_active' => array(
                                'type'        => 'checkbox',
                                'label'       => __('Enable Global Queue' , 'notificationx'),
                                'description' => sprintf('%s <a href="%s" target="_blank">%s</a>', __('Activate global queue system for this notification.', 'notificationx'), 'https://notificationx.com/docs/centralized-queue', 'Check out this doc.'),
                                'priority'    => 0,
                                'default'     => 0,
                                'is_pro'      => true,
                                'dependency'  => array(
                                    0 => array(
                                        'sections' => array( 'timing' )
                                    )
                                ),
                                'hide' => array(
                                    1 => array(
                                        'sections' => array( 'timing' )
                                    )
                                )
                            )
                        )
                    ),
                    'timing'        => array(
                        'title'       => __('Timing', 'notificationx'),
                        'priority'    => 200,
                        'collapsable' => true,
                        'fields'      => array(
                            'delay_before'  => array(
                                'type'        => 'number',
                                'label'       => __('Delay Before First Notification' , 'notificationx'),
                                'description' => __('seconds', 'notificationx'),
                                'help'        => __('Initial Delay', 'notificationx'),
                                'priority'    => 40,
                                'default'     => 5,
                            ),
                            'initial_delay'  => array(
                                'type'        => 'number',
                                'label'       => __('Initial Delay' , 'notificationx'),
                                'description' => __('seconds', 'notificationx'),
                                'help'        => __('Initial Delay', 'notificationx'),
                                'priority'    => 45,
                                'default'     => 5,
                            ),
                            'auto_hide'  => array(
                                'type'        => 'checkbox',
                                'label'       => __('Auto Hide' , 'notificationx'),
                                'description' => __('If checked, notification bar will be hidden after the time set below.', 'notificationx'),
                                'priority'    => 50,
                                'dependency' => array(
                                    1 => [
                                        'fields' => [ 'hide_after' ]
                                    ],
                                ),
                                'hide' => array(
                                    0 => [
                                        'fields' => [ 'hide_after' ]
                                    ],
                                ),
                                'default'     => false,
                            ),
                            'hide_after'  => array(
                                'type'        => 'number',
                                'label'       => __('Hide After' , 'notificationx'),
                                'description' => __('seconds', 'notificationx'),
                                'help'        => __('Hide after 60 seconds', 'notificationx'),
                                'priority'    => 55,
                                'default'     => 60,
                            ),
                            'display_for'  => array(
                                'type'        => 'number',
                                'label'       => __('Display For' , 'notificationx'),
                                'description' => __('seconds', 'notificationx'),
                                'help'        => __('Display each notification for * seconds', 'notificationx'),
                                'priority'    => 60,
                                'default'     => 5,
                            ),
                            'delay_between'  => array(
                                'type'        => 'number',
                                'label'       => __('Delay Between' , 'notificationx'),
                                'description' => __('seconds', 'notificationx'),
                                'help'        => __('Delay between each notification', 'notificationx'),
                                'priority'    => 70,
                                'default'     => 5,
                            ),
                        ),
                    ),
                    'behaviour'        => array(
                        'title'       => __('Behaviour', 'notificationx'),
                        'priority'    => 300,
                        'collapsable' => true,
                        'fields'      => array(
                            'display_last'  => array(
                                'type'        => 'number',
                                'label'       => __('Display The Last' , 'notificationx'),
                                'description' => 'conversions',
                                'default'     => 20,
                                'priority'    => 40,
                                'max'         => 20,
                            ),
                            'display_from'  => array(
                                'type'        => 'number',
                                'label'       => __('Display From The Last' , 'notificationx'),
                                'priority'    => 45,
                                'default'     => 2,
                                'description' => 'days',
                            ),
                            'loop'  => array(
                                'type'        => 'checkbox',
                                'label'       => __('Loop Notification' , 'notificationx'),
                                'priority'    => 50,
                                'default'     => true,
                            ),
                            'link_open'  => array(
                                'type'        => 'checkbox',
                                'label'       => __('Open Link In New Tab' , 'notificationx'),
                                'priority'    => 60,
                                'default'     => false,
                            ),
                        ),
                    ),
                ))
            ),
        ))
    );
}