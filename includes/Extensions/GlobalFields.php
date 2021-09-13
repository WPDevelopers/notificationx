<?php

/**
 * Register Global Fields
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions;

use NotificationX\Core\Rules;
use NotificationX\Core\Database;
use NotificationX\Core\Locations;
use NotificationX\GetInstance;
use NotificationX\Core\Modules;
use NotificationX\NotificationX;
use NotificationX\Types\TypeFactory;

/**
 * ExtensionFactory Class
 */
class GlobalFields {
    /**
     * Instance of GlobalFields
     *
     * @var GlobalFields
     */
    use GetInstance;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {

        // dump(Rules::logicalRule([ Rules::is('test'), Rules::is('test2') ]));
    }

    public function tabs() {
        do_action('nx_before_metabox_load');

        $tabs = [
            'id'             => 'notificationx_metabox_wrapper',
            'title'          => __('NotificationX', 'notificationx'),
            'object_types'   => array('notificationx'),
            'context'        => 'normal',
            'priority'       => 'high',
            'show_header'    => false,
            'tabnumber'      => true,
            'layout'         => 'horizontal',
            'version'        => defined('NOTIFICATIONX_VERSION') ? NOTIFICATIONX_VERSION : null,
            'pro_version'    => defined('NOTIFICATIONX_PRO_VERSION') ? NOTIFICATIONX_PRO_VERSION : null,
            'is_pro_active'  => NotificationX::get_instance()->is_pro(),
            'is_pro_sources' => apply_filters('nx_is_pro_sources', []),
            'config'         => [
                'active'  => "source_tab",
                'completionTrack' => true,
                'sidebar' => true,
                'step' => [
                    'show' => true,
                    'buttons' => [
                        'prev' => 'Previous',
                        'next' => 'Next',
                    ]
                ],
            ],
            'submit' => [
                'show' => false,
            ],
            'tabs'         => [
                "source_tab" => [
                    'label' => "Source",
                    'id'    => "source_tab",
                    'name'  => "source_tab",
                    'icon'  => [
                        'type' => 'tabs',
                        'name' => 'source'
                    ],
                    'classes' => "source_tab",
                    'fields'  => apply_filters('nx_source_fields', [
                        'source_error' => [
                            'type' => 'message',
                            'name' => 'source_error',
                            'messages' => apply_filters('source_error_message', []),
                            'rules' => '',
                        ],
                        'type_section' => [
                            'label'   => "Notification Type",
                            'name'   => "type_section",
                            'type'   => "section",
                            'fields' => [
                                'type' => [
                                    // 'label'   => "Notification Type",
                                    'name'    => "type",
                                    'type'    => "radio-card",
                                    'default' => "comments",
                                    'style'   => [
                                        'label' => [
                                            'position' => 'top'
                                        ]
                                    ],
                                    'options' => (array) array_map(function ($type) {
                                        return [
                                            'value'    => $type->id,
                                            'label'    => $type->title,
                                            'is_pro'   => $type->is_pro && ! NotificationX::is_pro(),
                                            'priority' => $type->priority
                                        ];
                                    }, array_values(TypeFactory::get_instance()->get_all())),
                                    'validation_rules' => [
                                        'required' => true,
                                        'label'    => "Type",
                                    ],
                                    'trigger' => [
                                        'defaults' => apply_filters( 'nx_type_trigger', [] ),
                                    ]
                                ],
                            ]
                        ],
                        'source_section' => [
                            'label'            => "Source",
                            'name'   => "source_section",
                            'type'   => "section",
                            'fields' => [
                                'source' => [
                                    // 'label'            => "Source",
                                    'name'             => "source",
                                    'type'             => "radio-card",
                                    'options'          => apply_filters('nx_sources', []),
                                    'default'          => 'woocommerce',
                                    'style'   => [
                                        'label' => [
                                            'position' => 'top'
                                        ]
                                    ],
                                    'validation_rules' => [
                                        'required' => true,
                                        'label'    => "Source",
                                    ],
                                    'trigger' => [
                                        'defaults' => apply_filters( 'nx_source_trigger', [
                                            "custom_notification" => [
                                                'show_notification_image' => '@show_notification_image:featured_image',
                                            ],
                                            "custom_notification_conversions" => [
                                                'show_notification_image' => '@show_notification_image:featured_image',
                                            ],
                                            "edd" => [
                                                'show_notification_image' => '@show_notification_image:featured_image',
                                            ],
                                            "envato" => [
                                                'show_notification_image' => '@show_notification_image:featured_image',
                                            ],
                                            "give" => [
                                                'show_notification_image' => '@show_notification_image:featured_image',
                                            ],
                                            "learndash" => [
                                                'show_notification_image' => '@show_notification_image:featured_image',
                                            ],
                                            "tutor" => [
                                                'show_notification_image' => '@show_notification_image:featured_image',
                                            ],
                                            "reviewx" => [
                                                'show_notification_image' => '@show_notification_image:featured_image',
                                            ],
                                            "woocommerce" => [
                                                'show_notification_image' => '@show_notification_image:featured_image',
                                            ],
                                            "woo_reviews" => [
                                                'show_notification_image' => '@show_notification_image:featured_image',
                                            ],
                                            "wp_reviews" => [
                                                'show_notification_image' => '@show_notification_image:featured_image',
                                            ],
                                            "freemius_conversions" => [
                                                'show_notification_image' => '@show_notification_image:featured_image',
                                            ],
                                            "freemius_reviews" => [
                                                'show_notification_image' => '@show_notification_image:featured_image',
                                            ],
                                            "freemius_stats" => [
                                                'show_notification_image' => '@show_notification_image:featured_image',
                                            ],
                                            "convertkit" => [
                                                'show_notification_image' => '@show_notification_image:gravatar',
                                            ],
                                            "zapier" => [
                                                'show_notification_image' => '@show_notification_image:gravatar',
                                            ],
                                            "mailchimp" => [
                                                'show_notification_image' => '@show_notification_image:gravatar',
                                            ],
                                            "wp_comments" => [
                                                'show_notification_image' => '@show_notification_image:gravatar',
                                            ],
                                            "cf7" => [
                                                'show_notification_image' => '@show_notification_image:gravatar',
                                            ],
                                            "njf" => [
                                                'show_notification_image' => '@show_notification_image:gravatar',
                                            ],
                                            "grvf" => [
                                                'show_notification_image' => '@show_notification_image:gravatar',
                                            ],
                                            "wpf" => [
                                                'show_notification_image' => '@show_notification_image:gravatar',
                                            ],
                                        ] ),
                                    ]
                                ],
                            ]
                        ],
                    ]),
                ],
                "design_tab" => [
                    'label' => "Design",
                    'id'    => "design_tab",
                    'name'  => "design_tab",
                    'icon'  => [
                        'type' => 'tabs',
                        'name' => 'design'
                    ],
                    'classes' => "design_tab",
                    'fields'  => apply_filters('nx_design_tab_fields', [
                        "themes" => [
                            'label'  => "Themes",
                            'name'   => "themes",
                            'type'   => "section",
                            'fields' => [
                                [
                                    // 'label'            => "Themes",
                                    'name'             => "themes",
                                    'type'             => "radio-card",
                                    // 'default'          => "conversions_theme-one",
                                    'options'          => apply_filters('nx_themes', []),
                                    'priority'         => 10,
                                    'style'   => [
                                        'label' => [
                                            'position' => 'top'
                                        ]
                                    ],
                                    'validation_rules' => [
                                        'required' => true,
                                        'label'    => "Theme",
                                    ],
                                    'trigger' => [
                                        'defaults' => apply_filters('nx_themes_trigger', []),
                                    ],
                                ],
                                [
                                    'label'    => "Advanced Design",
                                    'name'     => "advance_edit",
                                    'type'     => "toggle",
                                    'default'  => false,
                                    'priority' => 20,
                                ],
                            ]
                        ],
                        'advance_design_section' => [
                            'label' => __('Advanced Design', 'notificationx'),
                            'type' => 'section',
                            'name' => 'advance_design_section',
                            'classes' => 'wprf-no-bg',
                            'rules' => Rules::is('advance_edit', true),
                            'fields' => [
                                "design" => [
                                    'label' => "Design",
                                    'name'  => "design",
                                    'type'  => "section",
                                    'rules' => Rules::is('advance_edit', true),
                                    // 'rules' => Rules::is( 'advance_edit', true ),
                                    'fields' => [
                                        [
                                            'label' => "Background Color",
                                            'name'  => "bg_color",
                                            'type'  => "colorpicker",
                                            'default'  => "#fff",
                                        ],
                                        [
                                            'label' => "Text Color",
                                            'name'  => "text_color",
                                            'type'  => "colorpicker",
                                            'default'  => "#000",
                                        ],
                                        [
                                            'label'   => "Want Border?",
                                            'name'    => "border",
                                            'type'    => "checkbox",
                                            'default' => 0,
                                        ],
                                        [
                                            'label' => "Border Size",
                                            'name'  => "border_size",
                                            'type'  => "number",
                                            'default' => 1,
                                            'rules' => Rules::is( 'border', true ),
                                        ],
                                        [
                                            'label'   => "Border Style",
                                            'name'    => "border_style",
                                            'type'    => "select",
                                            'default' => 'solid',
                                            'options' => $this->normalize_fields([
                                                'solid'  => __('Solid', 'notificationx'),
                                                'dashed' => __('Dashed', 'notificationx'),
                                                'dotted' => __('Dotted', 'notificationx'),
                                            ]),
                                            'rules' => Rules::is( 'border', true ),
                                        ],
                                        [
                                            'label'   => __('Border Color', 'notificationx'),
                                            'name'    => "border_color",
                                            'type'    => "colorpicker",
                                            'default' => "#000",
                                            'rules'   => Rules::is( 'border', true ),
                                        ],
                                    ]
                                ],
                                "typography" => [
                                    'label'  => __('Typography', 'notificationx'),
                                    'name'   => "typography",
                                    'type'   => "section",
                                    'rules'  => Rules::is( 'advance_edit', true ),
                                    'fields' => [
                                        [
                                            'label'       => __('Font Size', 'notificationx'),
                                            'name'        => "first_font_size",
                                            'type'        => "number",
                                            'default'     => '13',
                                            'description' => 'px',
                                            'help'        => __('This font size will be applied for <mark>first</mark> row', 'notificationx'),
                                        ],
                                        [
                                            'label'       => __('Font Size', 'notificationx'),
                                            'name'        => "second_font_size",
                                            'type'        => "number",
                                            'default'     => '14',
                                            'description' => 'px',
                                            'help'        => __('This font size will be applied for <mark>second</mark> row', 'notificationx'),
                                        ],
                                        [
                                            'label'       => __('Font Size', 'notificationx'),
                                            'name'        => "third_font_size",
                                            'type'        => "number",
                                            'default'     => '11',
                                            'description' => 'px',
                                            'help'        => __('This font size will be applied for <mark>third</mark> row', 'notificationx'),
                                        ],
                                    ]
                                ],
                                "image-appearance" => [
                                    'label'  => __('Image Appearance', 'notificationx'),
                                    'name'   => "image-appearance",
                                    'type'   => "section",
                                    'rules'  => Rules::is( 'advance_edit', true ),
                                    'fields' => [
                                        'image_shape' => [
                                            'label'    => __('Image Shape', 'notificationx'),
                                            'name'     => "image_shape",
                                            'type'     => "select",
                                            'default'  => 'circle',
                                            'priority' => 5,
                                            'options'  => $this->normalize_fields([
                                                'circle'  => __('Circle', 'notificationx'),
                                                'rounded' => __('Rounded', 'notificationx'),
                                                'square'  => __('Square', 'notificationx'),
                                            ]),
                                        ],
                                        'image_position' => [
                                            'label'   => __('Position', 'notificationx'),
                                            'name'    => "image_position",
                                            'type'    => "select",
                                            'default' => 'left',
                                            'priority' => 15,
                                            'options' => $this->normalize_fields([
                                                'left'  => __('Left', 'notificationx'),
                                                'right' => __('Right', 'notificationx'),
                                            ]),
                                        ],
                                    ]
                                ],
                            ]
                        ]
                    ])
                ],
                "content_tab" => [
                    'label' => "Content",
                    'id'    => "content_tab",
                    'name'  => "content_tab",
                    'icon'  => [
                        'type' => 'tabs',
                        'name' => 'content'
                    ],
                    'classes' => "content_tab",
                    'fields'  => apply_filters('nx_content_fields', [
                        'content' => [
                            'label'    => __("Content", 'notificationx'),
                            'name'     => "content",
                            'type'     => "section",
                            'priority' => 90,
                            'fields'   => [
                                "notification-template" => [
                                    'label'    => __("Notification Template", 'notificationx'),
                                    'name'     => "notification-template",
                                    'type'     => "group",
                                    'display'  => 'inline',
                                    'priority' => 90,
                                    'fields'   => apply_filters('nx_notification_template',  [
                                        "first_param" => [
                                            // 'label' => __("First Parameter", 'notificationx'),
                                            'name'     => "first_param",
                                            'type'     => "select",
                                            'priority' => 3,
                                            'default'    => 'tag_name',
                                            'options'  => $this->normalize_fields([
                                                'tag_custom' => __('Custom', 'notificationx'),
                                            ]),
                                        ],
                                        "custom_first_param" => [
                                            // 'label' => __("Custom First Parameter", 'notificationx'),
                                            'name'     => "custom_first_param",
                                            'type'     => "text",
                                            'priority' => 5,
                                            'default'    => __('Someone', 'notificationx'),
                                            'rules'    => Rules::is( 'notification-template.first_param', 'tag_custom' ),
                                        ],
                                        "second_param" => [
                                            // 'label' => __("Second Param", 'notificationx'),
                                            'name'     => "second_param",
                                            'type'     => "text",
                                            'priority' => 10,
                                            'default'    => __('recently purchased', 'notificationx'),
                                        ],
                                        "third_param" => [
                                            // 'label' => __("Third Parameter", 'notificationx'),
                                            'name'     => "third_param",
                                            'type'     => "select",
                                            'priority' => 20,
                                            'default'    => 'tag_title',
                                            'options'  => $this->normalize_fields([
                                                'tag_custom' => __('Custom', 'notificationx'),
                                            ]),
                                        ],
                                        "custom_third_param" => [
                                            // 'label' => __("Custom Third Param", 'notificationx'),
                                            'name'     => "custom_third_param",
                                            'type'     => "text",
                                            'priority' => 25,
                                            'default'    => __('Some time ago', 'notificationx'),
                                            'rules'    => Rules::is( 'notification-template.third_param', 'tag_custom' ),
                                        ],
                                        "fourth_param" => [
                                            // 'label' => __("Fourth Parameter", 'notificationx'),
                                            'name'     => "fourth_param",
                                            'type'     => "select",
                                            'priority' => 30,
                                            'default'    => 'tag_time',
                                            'options'  => $this->normalize_fields([
                                                'tag_custom' => __('Custom', 'notificationx'),
                                            ]),
                                        ],
                                        "custom_fourth_param" => [
                                            // 'label' => __("Custom Fourth Parameter", 'notificationx'),
                                            'name'     => "custom_fourth_param",
                                            'type'     => "text",
                                            'priority' => 35,
                                            'default'    => __('Some time ago', 'notificationx'),
                                            'rules'    => Rules::is( 'notification-template.fourth_param', 'tag_custom' ),
                                        ],
                                        "fifth_param" => [
                                            // 'label' => __("Fifth Parameter", 'notificationx'),
                                            'name'     => "fifth_param",
                                            'type'     => "select",
                                            'priority' => 40,
                                            'options'  => $this->normalize_fields([
                                                'tag_custom' => __('Custom', 'notificationx'),
                                            ]),
                                        ],
                                        "custom_fifth_param" => [
                                            // 'label' => __("Custom Fifth Parameter", 'notificationx'),
                                            'name'     => "custom_fifth_param",
                                            'type'     => "text",
                                            'priority' => 45,
                                            'rules'    => Rules::is( 'notification-template.fifth_param', 'tag_custom' ),
                                        ],
                                        "sixth_param" => [
                                            // 'label' => __("Sixth Parameter", 'notificationx'),
                                            'name'     => "sixth_param",
                                            'type'     => "select",
                                            'priority' => 50,
                                            // 'default'    => 'tag_custom',
                                            'options'  => $this->normalize_fields([
                                                'tag_custom' => __('Custom', 'notificationx'),
                                            ]),
                                        ],
                                        "custom_sixth_param" => [
                                            // 'label' => __("Custom Sixth Parameter", 'notificationx'),
                                            'name'     => "custom_sixth_param",
                                            'type'     => "text",
                                            'priority' => 55,
                                            'rules'    => Rules::is( 'notification-template.sixth_param', 'tag_custom' ),
                                        ],

                                    ]),
                                    'rules' => Rules::includes( 'source', apply_filters('nx_notification_template_dependency', []) ),
                                ],
                                'template_adv' => [
                                    'label'    => "Advanced Template",
                                    'name'     => "template_adv",
                                    'type'     => "toggle",
                                    'default'  => false,
                                    'is_pro'   => true,
                                    'priority' => 91,
                                ],
                                'advanced_template' => [
                                    'name'     => 'advanced_template',
                                    'type'     => 'advanced-template',
                                    'label'    => __('Advanced Template', 'notificationx'),
                                    'priority' => 92,
                                    'rules'    => ['is', 'template_adv', true],
                                ],
                                'random_order' => array(
                                    'name'        => 'random_order',
                                    'label'       => __('Random Order', 'notificationx'),
                                    'type'        => 'checkbox',
                                    'priority'    => 93,
                                    'default'     => 0,
                                    'is_pro'      => true,
                                    'description' => __('Enable to show notification in random order.', 'notificationx'),
                                ),
                            ],
                        ],
                        'link_options' => [
                            'label'    => __('Link Options', 'notificationx'),
                            'name'     => "link_options",
                            'type'     => "section",
                            'priority' => 105,
                            'fields'   => [
                                "link_type" => [
                                    'label'   => __("Link Type", 'notificationx'),
                                    'name'    => "link_type",
                                    'type'    => "select",
                                    'default'   => 'none',
                                    'options' => apply_filters('nx_link_types', $this->normalize_fields([
                                        'none' => __('None', 'notificationx'),
                                    ])),
                                ],
                            ],
                            // must be called after nx_link_types filter.
                            'rules' => [ 'includes', 'source', apply_filters('nx_link_types_dependency', []) ],
                        ],
                    ]),
                ],
                "display_tab" => [
                    'label' => "Display",
                    'id'    => "display_tab",
                    'name'  => "display_tab",
                    'icon'  => [
                        'type' => 'tabs',
                        'name' => 'display'
                    ],
                    'classes' => "display_tab",
                    'fields'  => apply_filters('nx_display_fields', [
                        "image-section" => [
                            'label' => "IMAGE",
                            'name'  => "image-section",
                            'type'  => "section",
                            // 'condition' => [
                            //     'type' => '',
                            // ],
                            'fields' => [
                                [
                                    'label' => "Show Default Image",
                                    'name'  => "show_default_image",
                                    'type'  => "checkbox",
                                    'default' => false,
                                ],
                                [
                                    'label'       => "Choose an Image",
                                    'name'        => "default_avatar",
                                    'type'        => "radio-card",
                                    'default'     => "verified.svg",
                                    'description' => __('If checked, this will show in notifications.', 'notificationx'),
                                    'rules'       => Rules::is( 'show_default_image', true ),
                                    'style'       => [
                                        'size' => 'medium'
                                    ],
                                    'options'     => array(
                                        array(
                                            'value' => 'verified.svg',
                                            'label' => __('Verified', 'notificationx'),
                                            'icon'  => NOTIFICATIONX_PUBLIC_URL . 'image/icons/verified.svg',
                                        ),
                                        array(
                                            'value' => 'flames.svg',
                                            'label' => __('Flames', 'notificationx'),
                                            'icon'  => NOTIFICATIONX_PUBLIC_URL . 'image/icons/flames.svg',
                                        ),
                                        array(
                                            'value' => 'flames.gif',
                                            'label' => __('Flames GIF', 'notificationx'),
                                            'icon'  => NOTIFICATIONX_PUBLIC_URL . 'image/icons/flames.gif',
                                        ),
                                        array(
                                            'value' => 'pink-face-looped.gif',
                                            'label' => __('Pink Face', 'notificationx'),
                                            'icon'  => NOTIFICATIONX_PUBLIC_URL . 'image/icons/pink-face-looped.gif',
                                        ),
                                        array(
                                            'value' => 'blue-face-non-looped.gif',
                                            'label' => __('Blue Face', 'notificationx'),
                                            'icon'  => NOTIFICATIONX_PUBLIC_URL . 'image/icons/blue-face-non-looped.gif',
                                        ),
                                        //TODO: none
                                    )
                                ],
                                [
                                    'label' => "Upload an Image",
                                    'name'  => "image_url",
                                    'button'  => __('Upload', 'notificationx'),
                                    'type'  => "media",
                                    'default' => "",
                                    'rules' => Rules::is( 'show_default_image', true ),
                                ],
                                [
                                    'label'   => "Image",
                                    'name'    => "show_notification_image",
                                    'type'    => "select",
                                    'default' => "none",
                                    'rules'   => Rules::includes( 'source', [
                                        "cf7",
                                        "custom_notification",
                                        "custom_notification_conversions",
                                        "edd",
                                        "envato",
                                        "grvf",
                                        "give",
                                        // "ifttt",
                                        "learndash",
                                        "njf",
                                        "tutor",
                                        "wpf",
                                        "reviewx",
                                        "woocommerce",
                                        "woo_reviews",
                                        "wp_comments",
                                        "wp_reviews",
                                        "zapier",
                                        "mailchimp",
                                        "convertkit",
                                        "freemius_conversions",
                                        "freemius_reviews",
                                        "freemius_stats",
                                    ] ),
                                    'options' => apply_filters('nx_show_image_options', array(
                                        'none'           => [
                                            'value' => 'none',
                                            'label' => __('None', 'notificationx'),
                                        ],
                                        'featured_image' => [
                                            'value' => 'featured_image',
                                            'label' => __('Featured Image', 'notificationx'),
                                            'rules'  => [
                                                'includes',
                                                'source',
                                                [
                                                    "cf7",
                                                    "custom_notification",
                                                    "custom_notification_conversions",
                                                    "edd",
                                                    "envato",
                                                    "grvf",
                                                    "give",
                                                    // "ifttt",
                                                    "learndash",
                                                    "njf",
                                                    "tutor",
                                                    "wpf",
                                                    "reviewx",
                                                    "woocommerce",
                                                    "woo_reviews",
                                                    "wp_reviews",
                                                    "freemius_conversions",
                                                    "freemius_reviews",
                                                    "freemius_stats",
                                                ]
                                            ],
                                        ],
                                        'gravatar'       => [
                                            // @todo move the rules to a filter.
                                            'value' => 'gravatar',
                                            'label' => __('Gravatar', 'notificationx'),
                                            'rules'  => [
                                                'includes',
                                                'source',[
                                                    "cf7",
                                                    "custom_notification",
                                                    "custom_notification_conversions",
                                                    "edd",
                                                    "grvf",
                                                    "give",
                                                    // "ifttt",
                                                    "learndash",
                                                    "njf",
                                                    "tutor",
                                                    "wpf",
                                                    "reviewx",
                                                    "woocommerce",
                                                    "woo_reviews",
                                                    "wp_comments",
                                                    "wp_reviews",
                                                    "zapier",
                                                    "mailchimp",
                                                    "convertkit",
                                                    "freemius_conversions",
                                                    "freemius_reviews",
                                                    "freemius_stats",
                                                ],
                                            ],
                                        ],
                                    )),
                                ],
                            ],
                        ],
                        "visibility" => [
                            'label'  => "Visibility",
                            'name'   => "visibility",
                            'type'   => "section",
                            'fields' => [
                                "show_on" => [
                                    'label'    => "Show On",
                                    'name'     => "show_on",
                                    'type'     => "select",
                                    'default'  => "everywhere",
                                    'priority' => 5,
                                    'options'  => $this->normalize_fields([
                                        'everywhere'       => __('Show Everywhere', 'notificationx'),
                                        'on_selected'      => __('Show On Selected', 'notificationx'),
                                        'hide_on_selected' => __('Hide On Selected', 'notificationx'),
                                    ]),
                                ],
                                "all_locations" => [
                                    'label'    => "Locations",
                                    'name'     => "all_locations",
                                    'type'     => "select",
                                    'default'    => "",
                                    'multiple' => true,
                                    'priority' => 10,
                                    'rules'    => [ 'includes', 'show_on', [
                                        'on_selected',
                                        'hide_on_selected',
                                    ] ],
                                    'options' => $this->normalize_fields(Locations::get_instance()->get_locations()),
                                ],
                                "show_on_display" => [
                                    'label'    => "Display For",
                                    'name'     => "show_on_display",
                                    'type'     => "select",
                                    'default'    => "always",
                                    'priority' => 15,
                                    'options'  => $this->normalize_fields([
                                        'always'          => __('Everyone', 'notificationx'),
                                        'logged_out_user' => __('Logged Out User', 'notificationx'),
                                        'logged_in_user'  => __('Logged In User', 'notificationx'),
                                    ]),
                                    'help' => sprintf('<a target="_blank" rel="nofollow" href="https://notificationx.com/in/pro-display-control">%s</a>', __('More Control in Pro', 'notificationx')),
                                ],
                            ],
                        ],
                    ]),
                ],
                "customize_tab" => [
                    'label' => "Customize",
                    'id'    => "customize_tab",
                    'name'  => "customize_tab",
                    'icon'  => [
                        'type' => 'tabs',
                        'name' => 'customize'
                    ],
                    'classes' => "customize_tab",
                    'fields'  => apply_filters('nx_customize_fields', [
                        'appearance' => [
                            'label'  => "Appearance",
                            'name'   => "appearance",
                            'type'   => "section",
                            'fields' => [
                                'position' => [
                                    'label'    => "Position",
                                    'name'     => "position",      // combined "pressbar_position" && "conversion_position"
                                    'type'     => "select",
                                    'default'    => 'bottom_left',
                                    'priority' => 50,
                                    'options'  => [
                                        'bottom_left' => [
                                            'label' => __('Bottom Left', 'notificationx'),
                                            'value' => 'bottom_left',
                                        ],
                                        'bottom_right' => [
                                            'label' => __('Bottom Right', 'notificationx'),
                                            'value' => 'bottom_right',
                                        ],
                                    ],
                                ],
                                'size' => [
                                    'label' => __("Notification Size", 'notificationx'),
                                    'name'  => "size",
                                    'type'     => "number",
                                    'default'  => 500,
                                    'priority' => 51,
                                    'help'     => __('Set a max width for notification.', 'notificationx'),
                                ],
                                'close_button' => [
                                    'label'       => __("Display Close Option", 'notificationx'),
                                    'name'        => "close_button",
                                    'type'        => "checkbox",
                                    'default'     => 1,
                                    'priority'    => 70,
                                    'description' => __('Display a close button.', 'notificationx'),
                                ],
                                'hide_on_mobile' => [
                                    'label'       => __("Mobile Visibility", 'notificationx'),
                                    'name'        => "hide_on_mobile",
                                    'type'        => "checkbox",
                                    'default'     => 1,
                                    'priority'    => 200,
                                    'description' => __('Hide NotificationX on mobile.', 'notificationx'),
                                ],
                            ]
                        ],
                        'queue_management' => [
                            'label'    => "Queue Management",
                            'name'     => "queue_management",
                            'type'     => "section",
                            'priority' => 150,
                            // @todo is_pro
                            'is_pro' => true,
                            'fields' => [
                                [
                                    'label'    => __("Enable Global Queue", 'notificationx'),
                                    'name'     => "global_queue",
                                    'type'     => "checkbox",
                                    'priority' => 0,
                                    'default'  => false,
                                    'is_pro'   => true,
                                    'description' => sprintf('%s <a href="%s" target="_blank">%s</a>', __('Activate global queue system for this notification.', 'notificationx'), 'https://notificationx.com/docs/centralized-queue', 'Check out this doc.'),
                                ],
                            ]
                        ],
                        'timing' => [
                            'label'    => "Timing",
                            'name'     => "timing",
                            'type'     => "section",
                            'priority' => 200,
                            // 'rules'    => Rules::is( 'global_queue', false ),
                            'rules'    => Rules::is( 'global_queue', true, true ),
                            'fields'   => [
                                'delay_before' => [
                                    'label'       => "Delay Before First Notification",
                                    'name'        => "delay_before",
                                    'type'        => "number",
                                    'priority'    => 40,
                                    'default'       => 5,
                                    'help'        => __('Initial Delay', 'notificationx'),
                                    'description' => __('seconds', 'notificationx'),

                                ],
                                'display_for' => [
                                    'name'        => "display_for",
                                    'type'        => "number",
                                    'label'       => "Display For",
                                    'description' => __('seconds', 'notificationx'),
                                    'help'        => __('Display each notification for * seconds', 'notificationx'),
                                    'priority'    => 60,
                                    'default'       => 5,
                                ],
                                'delay_between' => [
                                    'name'        => "delay_between",
                                    'type'        => "number",
                                    'label'       => "Delay Between",
                                    'description' => __('seconds', 'notificationx'),
                                    'help'        => __('Delay between each notification', 'notificationx'),
                                    'priority'    => 70,
                                    'default'       => 5,
                                ],
                            ]
                        ],
                        'behaviour' => [
                            'label'       => "Behaviour",
                            'name'        => "behaviour",
                            'type'        => "section",
                            'priority'    => 300,
                            'collapsable' => true,
                            'fields'      => [
                                "display_last" => [
                                    'name'        => "display_last",
                                    'type'        => 'number',
                                    'label'       => __('Display The Last', 'notificationx'),
                                    'description' => 'conversions',
                                    'default'       => 20,
                                    'priority'    => 40,
                                    'max'         => 20,
                                ],
                                'display_from' => [
                                    'name'        => 'display_from',
                                    'type'        => 'number',
                                    'label'       => __('Display From The Last', 'notificationx'),
                                    'priority'    => 45,
                                    'default'       => 2,
                                    'description' => 'days',
                                ],
                                'loop' => [
                                    'name'     => 'loop',
                                    'type'     => 'checkbox',
                                    'label'    => __('Loop Notification', 'notificationx'),
                                    'priority' => 50,
                                    'default'    => true,
                                    'rules'    => Rules::is( 'global_queue', true, true ),
                                ],
                                'link_open' => [
                                    'name'     => 'link_open',
                                    'type'     => 'checkbox',
                                    'label'    => __('Open Link In New Tab', 'notificationx'),
                                    'priority' => 60,
                                    'default'    => false,
                                ],
                            ]
                        ],
                    ]),
                ],
            ],
            'instructions' => apply_filters( 'nx_instructions', [] ),
        ];
        $tabs['tabs'] = apply_filters('nx_metabox_tabs', $tabs['tabs']);
        // @todo Something
        if (!empty($_GET['post']))
            $tabs['saved_value'] = get_post_meta($_GET['post'], '_nx_meta', true);
        return $tabs;
    }


    public function normalize_fields($fields, $key = '', $value = [], $return = []) {

        foreach ($fields as $val => $label) {
            if (empty($return[$val]) && !is_array($label)) {
                $return[$val] = [
                    'value' => $val,
                    'label' => $label,
                ];
            }
            elseif (empty($return[$val])){
                $return[$val] = $label;
            }
            if(!empty($key)){
                $return[$val] = Rules::includes($key, $value, false, $return[$val]);
            }
        }

        return $return;
    }

    public function common_name_fields($display_name = false) {
        $fields = [
            'tag_name'       => __('Full Name', 'notificationx'),
            'tag_first_name' => __('First Name', 'notificationx'),
            'tag_last_name'  => __('Last Name', 'notificationx'),
        ];
        if ($display_name)
            $fields['tag_display_name'] = __('Display Name', 'notificationx');
        return $fields;
    }

    public function common_time_fields() {
        return [
            'tag_time'   => __('Definite Time', 'notificationx'),
            'tag_custom' => __('Some time ago', 'notificationx'),
        ];
    }

}
