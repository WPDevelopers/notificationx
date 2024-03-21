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
 * @method static GlobalFields get_instance($args = null)
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
        if(defined('NX_DEBUG') && NX_DEBUG){
            do_action( 'qm/start', __METHOD__ );
            do_action( 'qm/debug', __METHOD__ );
        }

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
                'active'          => "source_tab",
                'completionTrack' => true,
                'sidebar'         => true,
                'content_heading' => [],
                'step'            => [
                    'show'    => true,
                    'buttons' => [
                        'prev'    => __('Previous', 'notificationx'),
                        'next'    => __('Next', 'notificationx'),
                    ]
                ],

            ],
            'submit' => [
                'show' => false,
            ],
            'tabs'         => [
                "source_tab" => [
                    'label' => __("Source", 'notificationx'),
                    'id'    => "source_tab",
                    'name'  => "source_tab",
                    'icon'  => [
                        'type' => 'tabs',
                        'name' => 'source'
                    ],
                    'classes' => "source_tab",
                    'fields'  => apply_filters('nx_source_fields', [
                        'type_section' => [
                            'label'   => __("Notification Type", 'notificationx'),
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
                                            'priority' => $type->priority,
                                            'popup'    => apply_filters('nx_pro_alert_popup', $type->popup),
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
                            'label'            => __("Source", 'notificationx'),
                            'name'   => "source_section",
                            'type'   => "section",
                            'fields' => [
                                'source_error' => [
                                    'type' => 'message',
                                    'name' => 'source_error',
                                    'messages' => apply_filters('source_error_message', []),
                                    'rules' => '',
                                ],
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
                                            "surecart" => [
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
                                            "woocommerce_sales" => [
                                                'show_notification_image' => '@show_notification_image:featured_image',
                                            ],
                                        ] ),
                                    ]
                                ],
                            ]
                        ],
                    ]),
                    // 'rules'   => Rules::is('source', false)
                ],
                "design_tab" => [
                    'label' => __("Design", 'notificationx'),
                    'id'    => "design_tab",
                    'name'  => "design_tab",
                    'icon'  => [
                        'type' => 'tabs',
                        'name' => 'design'
                    ],
                    'classes' => "design_tab",
                    'fields'  => apply_filters('nx_design_tab_fields', [
                        'design_error' => [
                            'type' => 'message',
                            'name' => 'design_error',
                            'messages' => apply_filters('design_error_message', []),
                            'rules' => '',
                        ],
                        "themes" => [
                            'label'  => __("Themes", 'notificationx'),
                            'name'   => "themes",
                            'type'   => "section",
                            'fields' => [
                                'themes' => [
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
                                'advance_edit' => [
                                    'label'    => __("Advanced Design", 'notificationx'),
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
                                    'label'    => __("Design", 'notificationx'),
                                    'name'     => "design",
                                    'type'     => "section",
                                    'priority' => 5,
                                    'rules'    => Rules::is('advance_edit', true),
                                    // 'rules' => Rules::is( 'advance_edit', true ),
                                    'fields' => [
                                        [
                                            'label' => __("Background Color", 'notificationx'),
                                            'name'  => "bg_color",
                                            'type'  => "colorpicker",
                                            'default'  => "#fff",
                                        ],
                                        [
                                            'label' => __("Text Color", 'notificationx'),
                                            'name'  => "text_color",
                                            'type'  => "colorpicker",
                                            'default'  => "#000",
                                        ],
                                        [
                                            'label'   => __("Want Border?", 'notificationx'),
                                            'name'    => "border",
                                            'type'    => "checkbox",
                                            'default' => 0,
                                        ],
                                        [
                                            'label' => __("Border Size", 'notificationx'),
                                            'name'  => "border_size",
                                            'type'  => "number",
                                            'default' => 1,
                                            'rules' => Rules::is( 'border', true ),
                                        ],
                                        [
                                            'label'   => __("Border Style", 'notificationx'),
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
                                        [
                                            'label'   => __('Discount Text Color', 'notificationx'),
                                            'name'    => "discount_text_color",
                                            'type'    => "colorpicker",
                                            'default' => "#fff",
                                            'rules'   => Rules::logicalRule([
                                                Rules::is( 'type', 'offer_announcement' ),
                                                Rules::includes('themes', [ 'announcements_theme-1', 'announcements_theme-2' ], false),
                                            ]),
                                        ],
                                        [
                                            'label'   => __('Discount Background', 'notificationx'),
                                            'name'    => "discount_background",
                                            'type'    => "colorpicker",
                                            'rules'   => Rules::logicalRule([
                                                Rules::is( 'type', 'offer_announcement' ),
                                                Rules::includes('themes', [ 'announcements_theme-1', 'announcements_theme-2' ], false),
                                            ]),
                                        ],
                                    ]
                                ],
                                "typography" => [
                                    'label'    => __('Typography', 'notificationx'),
                                    'name'     => "typography",
                                    'type'     => "section",
                                    'priority' => 10,
                                    'rules'    => Rules::is( 'advance_edit', true ),
                                    'fields'   => [
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
                                    'label'    => __('Image Appearance', 'notificationx'),
                                    'name'     => "image-appearance",
                                    'type'     => "section",
                                    'priority' => 15,
                                    'rules'    => Rules::is( 'advance_edit', true ),
                                    'fields'   => [
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
                    'label' => __("Content", 'notificationx'),
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
                                    'label'    => __("Advanced Template", 'notificationx'),
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
                                    'rules'       => Rules::includes('source', ['woocommerce', 'woo_reviews', "edd", "reviewx", "woo_inline", "edd_inline","surecart","custom_notification", 'woocommerce_sales','woocommerce_sales_reviews','woocommerce_sales_inline']),
                                ),
                                'product_control' => array(
                                    'label'    => __('Show Purchase Of', 'notificationx'),
                                    'name'     => 'product_control',
                                    'type'     => 'select',
                                    'priority' => 94,
                                    'default'  => 'none',
                                    'is_pro'   => true,
                                    'disable' => true,
                                    'options'  => GlobalFields::get_instance()->normalize_fields([
                                        'none'             => __('All', 'notificationx'),
                                        'product_category' => __('Product Category', 'notificationx'),
                                        'manual_selection' => __('Selected Product', 'notificationx'),
                                    ]),
                                    'rules'       => Rules::includes('source', ['woocommerce','woocommerce_sales', 'woo_reviews', "edd", "reviewx", "woo_inline", "edd_inline","surecart",'woocommerce_sales_reviews','woocommerce_sales_inline']),
                                ),
                                'category_list' => array(
                                    'label'    => __('Select Product Category', 'notificationx'),
                                    'name'     => 'category_list',
                                    'type'     => 'select',
                                    'multiple' => true,
                                    'priority' => 95,
                                    'options'  => apply_filters('nx_conversion_category_list', []),
                                    'rules'       => Rules::logicalRule([
                                        Rules::includes('source', ['woocommerce' , 'woo_reviews', "edd", "reviewx", "woo_inline", "edd_inline", "surecart", 'woocommerce_sales','woocommerce_sales_reviews','woocommerce_sales_inline']),
                                        Rules::is( 'product_control', 'product_category' ),
                                    ]),
                                ),
                                'product_list' => array(
                                    'label'    => __('Select Product', 'notificationx'),
                                    'name'     => 'product_list',
                                    'type'     => 'select-async',
                                    'multiple' => true,
                                    'priority' => 96,
                                    'options'  => apply_filters('nx_conversion_product_list', [
                                        [
                                            'label'    => "Type for more result...",
                                            'value'    => null,
                                            'disabled' => true,
                                        ],
                                    ]),
                                    'rules'       => Rules::logicalRule([
                                        Rules::includes('source', ['woocommerce', 'woocommerce_sales', 'woo_reviews', "edd", "reviewx", "woo_inline", "edd_inline","surecart",'woocommerce_sales_reviews','woocommerce_sales_inline']),
                                        Rules::is( 'product_control', 'manual_selection' ),
                                    ]),
                                    'ajax'   => [
                                        'api'  => "/notificationx/v1/get-data",
                                        'data' => [
                                            'type'   => "@type",
                                            'source' => "@source",
                                            'field'  => "product_list",
                                        ],
                                        // 'target' => "product_list",
                                        'rules'  => Rules::logicalRule([
                                            Rules::includes('source', ['woocommerce', 'woo_reviews', "edd", "reviewx", "woo_inline", "edd_inline", "surecart", 'woocommerce_sales','woocommerce_sales_reviews','woocommerce_sales_inline']),
                                            Rules::is( 'product_control', 'manual_selection' ),
                                        ]),
                                    ],
                                ),
                                'product_exclude_by' => array(
                                    'label'    => __('Exclude By', 'notificationx'),
                                    'name'     => 'product_exclude_by',
                                    'type'     => 'select',
                                    'priority' => 97,
                                    'default'  => 'none',
                                    'is_pro'   => true,
                                    'disable' => true,
                                    'options'  => GlobalFields::get_instance()->normalize_fields([
                                        'none'             => __('None', 'notificationx'),
                                        'product_category' => __('Product Category', 'notificationx'),
                                        'manual_selection' => __('Selected Product', 'notificationx'),
                                    ]),
                                    'rules' => Rules::includes('source', ['woocommerce', 'woo_reviews', "edd", "reviewx", "woo_inline", "edd_inline","surecart", 'woocommerce_sales','woocommerce_sales_reviews','woocommerce_sales_inline']),
                                ),
                                'exclude_categories' => array(
                                    'label'    => __('Select Product Category', 'notificationx'),
                                    'name'     => 'exclude_categories',
                                    'type'     => 'select',
                                    'multiple' => true,
                                    'priority' => 98,
                                    'options'  => apply_filters('nx_conversion_category_list', []),
                                    'rules'       => Rules::logicalRule([
                                        Rules::includes('source', ['woocommerce', 'woo_reviews', "edd", "reviewx", "woo_inline", "edd_inline", "surecart", 'woocommerce_sales','woocommerce_sales_reviews','woocommerce_sales_inline']),
                                        Rules::is( 'product_exclude_by', 'product_category' ),
                                    ]),
                                ),
                                'exclude_products' => array(
                                    'label'    => __('Select Product', 'notificationx'),
                                    'name'     => 'exclude_products',
                                    'type'     => 'select-async',
                                    'multiple' => true,
                                    'priority' => 99,
                                    'options'  => apply_filters('nx_conversion_product_list', [
                                        [
                                            'label'    => "Type for more result...",
                                            'value'    => null,
                                            'disabled' => true,
                                        ],
                                    ]),
                                    'rules'       => Rules::logicalRule([
                                        Rules::includes('source', ['woocommerce', 'woocommerce_sales', 'woo_reviews', "edd", "reviewx", "woo_inline", "edd_inline","surecart",'woocommerce_sales_reviews','woocommerce_sales_inline']),
                                        Rules::is( 'product_exclude_by', 'manual_selection' ),
                                    ]),
                                    'ajax'   => [
                                        'api'  => "/notificationx/v1/get-data",
                                        'data' => [
                                            'type'   => "@type",
                                            'source' => "@source",
                                            'field'  => "exclude_products",
                                        ],
                                        'rules'       => Rules::logicalRule([
                                            Rules::includes('source', ['woocommerce', 'woo_reviews', "edd", "reviewx", "woo_inline", "edd_inline", 'woocommerce_sales','woocommerce_sales_reviews','woocommerce_sales_inline']),
                                            Rules::is( 'product_exclude_by', 'manual_selection' ),
                                        ]),
                                    ],
                                ),
                                'order_status'  => array(
                                    'label'    => __('Order Status', 'notificationx'),
                                    'name'     => 'order_status',
                                    'type'     => 'select',
                                    'multiple' => true,
                                    'is_pro'   => true,
                                    'priority' => 99.5,
                                    'default'  => ['wc-completed', 'wc-processing'],
                                    'help'     => __("By default it will show Processing & Completed status."),
                                    'options'  => apply_filters('nx_woo_order_status', []),
                                    'rules'    => Rules::logicalRule([
                                        Rules::includes('source', ['woocommerce', 'woocommerce_sales', "woo_inline","woocommerce_sales_inline"]),
                                        Rules::includes('themes', [ 'woo_inline_stock-theme-one', 'woo_inline_stock-theme-two', 'woocommerce_sales_inline_stock-theme-one', 'woocommerce_sales_inline_stock-theme-two'], true),
                                    ]),

                                ),
                                'surecart_order_status'  => array(
                                    'label'    => __('Order Status', 'notificationx'),
                                    'name'     => 'surecart_order_status',
                                    'type'     => 'select',
                                    'multiple' => true,
                                    'is_pro'   => true,
                                    'priority' => 99.6,
                                    'default'  => ['processing','fulfilled'],
                                    'help'     => __("By default it will show Processing & Fulfilled status."),
                                    'options'  => apply_filters('nx_surecart_order_status', []),
                                    'rules'    => Rules::logicalRule([
                                        Rules::includes('source', ["surecart"]),
                                    ]),
                                ),
                                'combine_multiorder' => [
                                    'label'       => __('Combine Multi Order', 'notificationx'),
                                    'name'        => 'combine_multiorder',
                                    'type'        => 'checkbox',
                                    'priority'    => 99.7,
                                    'default'     => true,
                                    'description' => __('Combine order like, 2 more products.', 'notificationx'),
                                    'rules' => Rules::logicalRule([
                                        Rules::is('notification-template.first_param', 'tag_sales_count', true),
                                        Rules::includes('source', [ 'woocommerce', 'edd', 'woocommerce_sales' ]),
                                    ]),
                                ],
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
                                'link_button' => [
                                    'label'       => __('Button', 'notificationx'),
                                    'name'        => 'link_button',
                                    'type'        => 'checkbox',
                                    'priority'    => 100,
                                    'is_pro'      => true,
                                    'default'     => false,
                                    // 'default'     => [
                                    //     'youtube_channel-1' => true,
                                    //     'youtube_channel-2' => true,
                                    //     'youtube_video-3'   => true,
                                    //     'youtube_video-4'   => true,
                                    // ],
                                    'description' => __('Enable button with link', 'notificationx'),
                                    'rules'       => Rules::logicalRule([
                                        Rules::includes('type', ['conversions','video','woocommerce', 'woocommerce_sales','page_analytics']),
                                        Rules::is('link_type','none',true),
                                    ]),
                                ],
                            ],
                            // must be called after nx_link_types filter.
                            'rules' => [ 'includes', 'source', apply_filters('nx_link_types_dependency', []) ],
                        ],
                    ]),
                ],
                "display_tab" => [
                    'label' => __("Display", 'notificationx'),
                    'id'    => "display_tab",
                    'name'  => "display_tab",
                    'icon'  => [
                        'type' => 'tabs',
                        'name' => 'display'
                    ],
                    'classes' => "display_tab",
                    'fields'  => apply_filters('nx_display_fields', [
                        "image-section" => [
                            'label' => __("IMAGE", 'notificationx'),
                            'name'  => "image-section",
                            'type'  => "section",
                            // 'condition' => [
                            //     'type' => '',
                            // ],
                            'fields' => [
                                'show_default_image' => [
                                    'label' => __("Show Default Image", 'notificationx'),
                                    'name'  => "show_default_image",
                                    'type'  => "checkbox",
                                    'default' => false,
                                ],
                                'default_avatar' => [
                                    'label'       =>__( "Choose an Image", 'notificationx'),
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
                                    'label' => __("Upload an Image", 'notificationx'),
                                    'name'  => "image_url",
                                    'button'  => __('Upload', 'notificationx'),
                                    'type'  => "media",
                                    'default' => "",
                                    'rules' => Rules::is( 'show_default_image', true ),
                                ],
                                'show_notification_image' => [
                                    'label'   => __("Image", 'notificationx'),
                                    'name'    => "show_notification_image",
                                    'type'    => "select",
                                    'default' => "none",
                                    'rules'   => Rules::includes( 'source', [
                                        "fluentform",
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
                                        "surecart",
                                        "woo_reviews",
                                        "wp_comments",
                                        "wp_reviews",
                                        "zapier",
                                        "mailchimp",
                                        "convertkit",
                                        "freemius_conversions",
                                        "freemius_reviews",
                                        "freemius_stats",
                                        "youtube",
                                        "woocommerce_sales",
                                        'woocommerce_sales_reviews',
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
                                                    "surecart",
                                                    "woo_reviews",
                                                    "wp_reviews",
                                                    "freemius_conversions",
                                                    "freemius_reviews",
                                                    "freemius_stats",
                                                    "woocommerce_sales",
                                                    'woocommerce_sales_reviews',
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
                                                    "fluentform",
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
                                                    "surecart",
                                                    "woo_reviews",
                                                    "wp_comments",
                                                    "wp_reviews",
                                                    "zapier",
                                                    "mailchimp",
                                                    "convertkit",
                                                    "freemius_conversions",
                                                    "freemius_reviews",
                                                    "freemius_stats",
                                                    "woocommerce_sales",
                                                    'woocommerce_sales_reviews',
                                                ],
                                            ],
                                        ],
                                    )),
                                ],
                            ],
                        ],
                        "visibility" => [
                            'label'  => __("Visibility", 'notificationx'),
                            'name'   => "visibility",
                            'type'   => "section",
                            'fields' => [
                                "show_on" => [
                                    'label'    => __("Show On", 'notificationx'),
                                    'name'     => "show_on",
                                    'type'     => "select",
                                    'default'  => "everywhere",
                                    'priority' => 5,
                                    'options'  => apply_filters('nx_show_on_options', $this->normalize_fields([
                                        'everywhere'       => __('Show Everywhere', 'notificationx'),
                                        'on_selected'      => __('Show On Selected', 'notificationx'),
                                        'hide_on_selected' => __('Hide On Selected', 'notificationx'),
                                    ])),
                                ],
                                "all_locations" => [
                                    'label'    => __("Locations", 'notificationx'),
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
                                    'label'    => __("Display For", 'notificationx'),
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
                    'label' => __("Customize", 'notificationx'),
                    'id'    => "customize_tab",
                    'name'  => "customize_tab",
                    'icon'  => [
                        'type' => 'tabs',
                        'name' => 'customize'
                    ],
                    'classes' => "customize_tab",
                    'fields'  => apply_filters('nx_customize_fields', [
                        'appearance' => [
                            'label'  => __("Appearance", 'notificationx'),
                            'name'   => "appearance",
                            'type'   => "section",
                            'fields' => [
                                'position' => [
                                    'label'    => __("Position", 'notificationx'),
                                    'name'     => "position",                        // combined "pressbar_position" && "conversion_position"
                                    'type'     => "select",
                                    'default'  => 'bottom_left',
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
                                'close_icon_position' => [
                                    'label'    => __("Close Icon Position", 'notificationx'),
                                    'name'     => "close_icon_position",
                                    'type'     => "select",
                                    'default'    => 'top_right',
                                    'priority' => 51,
                                    'options'  => [
                                        'top_right' => [
                                            'label' => __('Top Right', 'notificationx'),
                                            'value' => 'top_right',
                                        ],
                                        'top_left' => [
                                            'label' => __('Top Left', 'notificationx'),
                                            'value' => 'top_left',
                                        ]
                                    ],
                                    'rules'       => Rules::includes('source', ['press_bar']),
                                ],
                                'size' => [
                                    'label'   => __("Notification Size", 'notificationx'),
                                    'name'    => "size",
                                    'type'    => "responsive-number",
                                    'default' => [
                                        "desktop" => 500,
                                        "tablet"  => 500,
                                        "mobile"  => 500,
                                    ],
                                    'priority' => 51,
                                    'min'      => 300,
                                    'controls' => [
                                        "desktop" => [
                                            "icon" => NOTIFICATIONX_ADMIN_URL . 'images/responsive/desktop.svg',
                                            'size' => 18,
                                        ],
                                        "tablet" => [
                                            "icon" => NOTIFICATIONX_ADMIN_URL . 'images/responsive/tablet.svg',
                                            'size' => 14,
                                        ],
                                        "mobile" => [
                                            "icon" => NOTIFICATIONX_ADMIN_URL . 'images/responsive/mobile.svg',
                                            'size' => 12,
                                        ],
                                    ],
                                    'help' => __('Set a max width for notification.', 'notificationx'),
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
                        'appearance' => [
                            'label'  => __("Appearance", 'notificationx'),
                            'name'   => "appearance",
                            'type'   => "section",
                            'fields' => [
                                'position' => [
                                    'label'    => __("Position", 'notificationx'),
                                    'name'     => "position",                        // combined "pressbar_position" && "conversion_position"
                                    'type'     => "select",
                                    'default'  => 'bottom_left',
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
                                    'label'   => __("Notification Size", 'notificationx'),
                                    'name'    => "size",
                                    'type'    => "responsive-number",
                                    'default' => [
                                        "desktop" => 500,
                                        "tablet"  => 500,
                                        "mobile"  => 500,
                                    ],
                                    'priority' => 51,
                                    'min'      => 300,
                                    'controls' => [
                                        "desktop" => [
                                            "icon" => NOTIFICATIONX_ADMIN_URL . 'images/responsive/desktop.svg',
                                            'size' => 18,
                                        ],
                                        "tablet" => [
                                            "icon" => NOTIFICATIONX_ADMIN_URL . 'images/responsive/tablet.svg',
                                            'size' => 14,
                                        ],
                                        "mobile" => [
                                            "icon" => NOTIFICATIONX_ADMIN_URL . 'images/responsive/mobile.svg',
                                            'size' => 12,
                                        ],
                                    ],
                                    'help' => __('Set a max width for notification.', 'notificationx'),
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
                        'animation' => [
                            'label'  => __("Animation", 'notificationx'),
                            'name'   => "animation",
                            'type'   => "section",
                            'fields' => [
                                'animation_notification_show' => [
                                    'label'    => __("Notification Show", 'notificationx'),
                                    'name'     => "animation_notification_show",
                                    'type'     => "select",
                                    'default'  => 'default',
                                    'classes'  => NotificationX::is_pro() ? '' : 'animation-pro-disabled',
                                    'priority' => 5,
                                    'options'  => [
                                        'default' => [
                                            'label'    => __('Default', 'notificationx'),
                                            'value'    => 'default',
                                            'selected' => 'selected',
                                        ],
                                        'animate__fadeIn' => [
                                            'label'    => __('Fade In', 'notificationx'),
                                            'value'    => 'animate__fadeIn',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__fadeInUp' => [
                                            'label'    => __('Fade In Up', 'notificationx'),
                                            'value'    => 'animate__fadeInUp',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__fadeInDown' => [
                                            'label'    => __('Fade In Down', 'notificationx'),
                                            'value'    => 'animate__fadeInDown',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__fadeInDownBig' => [
                                            'label'    => __('Fade In Down Big', 'notificationx'),
                                            'value'    => 'animate__fadeInDownBig',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__fadeInLeft' => [
                                            'label'    => __('Fade In Left', 'notificationx'),
                                            'value'    => 'animate__fadeInLeft',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__fadeInRight' => [
                                            'label' => __('Fade In Right', 'notificationx'),
                                            'value' => 'animate__fadeInRight',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__lightSpeedInLeft' => [
                                            'label' => __('Light Speed In Left', 'notificationx'),
                                            'value' => 'animate__lightSpeedInLeft',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__lightSpeedInRight' => [
                                            'label' => __('Light Speed In Right', 'notificationx'),
                                            'value' => 'animate__lightSpeedInRight',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__zoomIn' => [
                                            'label' => __('Zoom In', 'notificationx'),
                                            'value' => 'animate__zoomIn',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__slideInUp' => [
                                            'label' => __('Slide In Up', 'notificationx'),
                                            'value' => 'animate__slideInUp',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__slideInLeft' => [
                                            'label' => __('Slide In Left', 'notificationx'),
                                            'value' => 'animate__slideInLeft',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__slideInRight' => [
                                            'label' => __('Slide In Right', 'notificationx'),
                                            'value' => 'animate__slideInRight',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__slideInDown' => [
                                            'label' => __('Slide In Down', 'notificationx'),
                                            'value' => 'animate__slideInDown',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                    ],
                                ],
                                'animation_notification_hide' => [
                                    'label'    => __("Notification Hide", 'notificationx'),
                                    'name'     => "animation_notification_hide",
                                    'type'     => "select",
                                    'default'  => 'default',
                                    'classes'  => NotificationX::is_pro() ? '' : 'animation-pro-disabled',
                                    'priority' => 10,
                                    'options'  => [
                                        'default' => [
                                            'label' => __('Default', 'notificationx'),
                                            'value' => 'default',
                                        ],
                                        'animate__fadeOut' => [
                                            'label'    => __('Fade Out', 'notificationx'),
                                            'value'    => 'animate__fadeOut',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__fadeOutDown' => [
                                            'label'    => __('Fade Out Down', 'notificationx'),
                                            'value'    => 'animate__fadeOutDown',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__fadeOutRight' => [
                                            'label'    => __('Fade Out Right', 'notificationx'),
                                            'value'    => 'animate__fadeOutRight',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__fadeOutUp' => [
                                            'label'    => __('Fade Out Up', 'notificationx'),
                                            'value'    => 'animate__fadeOutUp',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__lightSpeedOutLeft' => [
                                            'label'    => __('Light Speed Out Left', 'notificationx'),
                                            'value'    => 'animate__lightSpeedOutLeft',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__zoomOut' => [
                                            'label'    => __('Zoom Out', 'notificationx'),
                                            'value'    => 'animate__zoomOut',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__slideOutDown' => [
                                            'label'    => __('Slide Out Down', 'notificationx'),
                                            'value'    => 'animate__slideOutDown',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__slideOutLeft' => [
                                            'label'    => __('Slide Out Left', 'notificationx'),
                                            'value'    => 'animate__slideOutLeft',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__slideOutRight' => [
                                            'label'    => __('Slide Out Right', 'notificationx'),
                                            'value'    => 'animate__slideOutRight',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                        'animate__slideOutUp' => [
                                            'label'    => __('Slide Out Up', 'notificationx'),
                                            'value'    => 'animate__slideOutUp',
                                            'disabled' => NotificationX::is_pro() ? false : true,
                                        ],
                                    ],
                                ],
                                // 'animation_notification_duration' => [
                                //     'label'    => __("Duration", 'notificationx'),
                                //     'name'     => "animation_notification_duration",
                                //     'type'     => "select",
                                //     'default'  => 'default',
                                //     'priority' => 15,
                                //     'options'  => [
                                //         'default' => [
                                //             'label' => __('Default', 'notificationx'),
                                //             'value' => 'default',
                                //         ],
                                //         'animate__faster' => [
                                //             'label'    => __('Faster', 'notificationx'),
                                //             'value'    => 'animate__faster',
                                //             'disabled' => NotificationX::is_pro() ? false : true,
                                //         ],
                                //         'animate__fast' => [
                                //             'label'    => __('Fast', 'notificationx'),
                                //             'value'    => 'animate__fast',
                                //             'disabled' => NotificationX::is_pro() ? false : true,
                                //         ],
                                //     ],
                                // ],
                            ]
                        ],
                        'queue_management' => [
                            'label'    => __("Queue Management", 'notificationx'),
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
                                    'description' => sprintf('%s <a href="%s" target="_blank">%s</a>', __('Activate global queue system for this notification.', 'notificationx'), 'https://notificationx.com/docs/centralized-queue', __('Check out this doc.', 'notificationx')),
                                ],
                            ]
                        ],
                        'timing' => [
                            'label'    => __("Timing", 'notificationx'),
                            'name'     => "timing",
                            'type'     => "section",
                            'priority' => 200,
                            // 'rules'    => Rules::is( 'global_queue', false ),
                            'rules'    => Rules::is( 'global_queue', true, true ),
                            'fields'   => [
                                'delay_before' => [
                                    'label'       => __("Delay Before First Notification", 'notificationx'),
                                    'name'        => "delay_before",
                                    'type'        => "number",
                                    'priority'    => 40,
                                    'default'     => defined('NX_DEBUG') && NX_DEBUG ? 1 : 5,
                                    'help'        => __('Initial Delay', 'notificationx'),
                                    'description' => __('seconds', 'notificationx'),

                                ],
                                'display_for' => [
                                    'name'        => "display_for",
                                    'type'        => "number",
                                    'label'       => __("Display For", 'notificationx'),
                                    'description' => __('seconds', 'notificationx'),
                                    'help'        => __('Display each notification for * seconds', 'notificationx'),
                                    'priority'    => 60,
                                    'default'     => defined('NX_DEBUG') && NX_DEBUG ? 2 : 5,
                                ],
                                'delay_between' => [
                                    'name'        => "delay_between",
                                    'type'        => "number",
                                    'label'       => __("Delay Between", 'notificationx'),
                                    'description' => __('seconds', 'notificationx'),
                                    'help'        => __('Delay between each notification', 'notificationx'),
                                    'priority'    => 70,
                                    'default'     => defined('NX_DEBUG') && NX_DEBUG ? 1 : 5,
                                ],
                            ]
                        ],
                        'behaviour' => [
                            'label'       => __("Behavior", 'notificationx'),
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
        $tabs = apply_filters('nx_metabox_config', $tabs);

        if(defined('NX_DEBUG') && NX_DEBUG){
            do_action( 'qm/stop', __METHOD__ );
        }
        return $tabs;
    }


    public function normalize_fields($fields, $key = '', $value = [], $return = []) {
        foreach ($fields as $val => $label) {
            $val = !empty( $label['value'] ) ? $label['value'] : $val;
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
