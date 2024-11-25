<?php

/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Types;

use NotificationX\Core\Rules;
use NotificationX\Extensions\ExtensionFactory;
use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;
use NotificationX\Modules;

/**
 * Extension Abstract for all Extension.
 * @method static GDPR get_instance($args = null)
 */
class GDPR extends Types {
    /**
     * Instance of GDPR
     *
     * @var GDPR
     */
    use GetInstance;

    public $priority = 10;
    public $themes = [];
    public $module = [
        'modules_gdpr',
    ];
    public $default_source = 'gdpr_notification';


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        $this->id    = 'gdpr';
        $this->title = __('GDPR Notification', 'notificationx');
        parent::__construct();

        // nx_comment_colored_themes
        $this->themes = [
            'theme-light-one'        => [
                'source'  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/gdpr/light-1.jpg',
                'image_shape' => 'circle',
                'template'  => [
                    'first_param'         => 'tag_name',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('commented on', 'notificationx'),
                    'third_param'         => 'tag_post_title',
                    'custom_third_param'  => __('Anonymous Post', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
            'theme-light-two'        => [
                'source'  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/gdpr/light-2.jpg',
                'image_shape' => 'circle',
                'template'  => [
                    'first_param'         => 'tag_name',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('commented on', 'notificationx'),
                    'third_param'         => 'tag_post_title',
                    'custom_third_param'  => __('Anonymous Post', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
            'theme-light-three'      => [
                'source'  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/gdpr/light-3.jpg',
                'image_shape' => 'square',
                'template'  => [
                    'first_param'         => 'tag_name',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('commented on', 'notificationx'),
                    'third_param'         => 'tag_post_title',
                    'custom_third_param'  => __('Anonymous Post', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
            'theme-light-four'   => [
                'source'  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/gdpr/light-4.jpg',
                'image_shape' => 'rounded',
                'template'  => [
                    'first_param'         => 'tag_name',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('commented on', 'notificationx'),
                    'third_param'         => 'tag_post_comment',
                    'custom_third_param'  => __('Anonymous Post', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
            'theme-dark-one'   => [
                'source'  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/gdpr/dark-1.jpg',
                'image_shape' => 'rounded',
                'template'  => [
                    'first_param'         => 'tag_name',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('commented on', 'notificationx'),
                    'third_param'         => 'tag_post_comment',
                    'custom_third_param'  => __('Anonymous Post', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
            'theme-dark-two'   => [
                'source'  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/gdpr/dark-2.jpg',
                'image_shape' => 'rounded',
                'template'  => [
                    'first_param'         => 'tag_name',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('commented on', 'notificationx'),
                    'third_param'         => 'tag_post_comment',
                    'custom_third_param'  => __('Anonymous Post', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
            'theme-dark-three'   => [
                'source'  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/gdpr/dark-3.jpg',
                'image_shape' => 'rounded',
                'template'  => [
                    'first_param'         => 'tag_name',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('commented on', 'notificationx'),
                    'third_param'         => 'tag_post_comment',
                    'custom_third_param'  => __('Anonymous Post', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
            'theme-dark-four'   => [
                'source'  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/gdpr/dark-4.jpg',
                'image_shape' => 'rounded',
                'template'  => [
                    'first_param'         => 'tag_name',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('commented on', 'notificationx'),
                    'third_param'         => 'tag_post_comment',
                    'custom_third_param'  => __('Anonymous Post', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
            'theme-banner-light-one' => [
                'source'  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/gdpr/banner-light-1.jpg',
                'image_shape' => 'circle',
                'template'  => [
                    'first_param'         => 'tag_name',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('commented on', 'notificationx'),
                    'third_param'         => 'tag_post_comment',
                    'custom_third_param'  => __('Anonymous Post', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
            'theme-banner-light-two' => [
                'source'  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/gdpr/banner-light-2.jpg',
                'image_shape' => 'circle',
                'template'  => [
                    'first_param'         => 'tag_name',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('commented on', 'notificationx'),
                    'third_param'         => 'tag_post_comment',
                    'custom_third_param'  => __('Anonymous Post', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
            'theme-banner-dark-one' => [
                'source'  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/gdpr/banner-dark-1.jpg',
                'image_shape' => 'circle',
                'template'  => [
                    'first_param'         => 'tag_name',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('commented on', 'notificationx'),
                    'third_param'         => 'tag_post_comment',
                    'custom_third_param'  => __('Anonymous Post', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
            'theme-banner-dark-two' => [
                'source'  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/gdpr/banner-dark-2.jpg',
                'image_shape' => 'circle',
                'template'  => [
                    'first_param'         => 'tag_name',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('commented on', 'notificationx'),
                    'third_param'         => 'tag_post_comment',
                    'custom_third_param'  => __('Anonymous Post', 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ],
            ],
        ];
    }

    /**
     * Hooked to nx_before_metabox_load action.
     *
     * @return void
     */
    public function init_fields() {
        parent::init_fields();
        add_filter('nx_design_tab_fields', [$this, 'add_design_fields'], 9);
        add_filter('nx_content_gdpr', [$this, 'add_content_fields'], 9);
        // add_filter('nx_notification_template', [$this, 'notification_template'], 9);
        // add_filter('nx_customize_fields', [$this, 'customize_fields'], 20);
    }

    public function add_content_fields( $fields ) {

        // dd($fields);
        $_fields = &$fields['fields'];
        $_fields['gdpr_title'] = [
            'label'    => __('Title', 'notificationx'),
            'name'     => 'gdpr_title',
            'type'     => 'text',
            'priority' => 101,
            'default' => __('We value your privacy', 'notificationx'),       
        ];
        $_fields['gdpr_message'] = [
            'label'    => __('Message', 'notificationx'),
            'name'     => 'gdpr_message',
            'type'     => 'textarea',
            'priority' => 102,
            'default' => __('We value your privacy', 'notificationx'),       
        ];
        $_fields['gdpr_accept_btn'] = [
            'label'    => __('Accept All Button', 'notificationx'),
            'name'     => 'gdpr_accept_btn',
            'type'     => 'text',
            'priority' => 103,
            'default' => __('Accept All', 'notificationx'),       
        ];
        $_fields['gdpr_reject_btn'] = [
            'label'    => __('Reject All Button', 'notificationx'),
            'name'     => 'gdpr_reject_btn',
            'type'     => 'text',
            'priority' => 104,
            'default' => __('Reject All', 'notificationx'),       
        ];
        $_fields['gdpr_customize_btn'] = [
            'label'    => __('Customize Button', 'notificationx'),
            'name'     => 'gdpr_customize_btn',
            'type'     => 'text',
            'priority' => 105,
            'default' => __('Customize Button', 'notificationx'),       
        ];
        $_fields['gdpr_cookies_policy_toggle'] = [
            'label'    => __('Cookies Policy Link', 'notificationx'),
            'name'     => 'gdpr_cookies_policy_toggle',
            'type'     => 'toggle',
            'priority' => 106,
            'default' => true,       
        ];
        $_fields['gdpr_cookies_policy_link_text'] = [
            'label'    => __('Cookies Policy Link Text', 'notificationx'),
            'name'     => 'gdpr_cookies_policy_link_text',
            'type'     => 'text',
            'priority' => 107,
            'placeholder' => __('Link Text', 'notificationx'),
            'rules' => Rules::logicalRule([
                Rules::is('gdpr_cookies_policy_toggle', true),
            ]),      
        ];
        $_fields['gdpr_cookies_policy_link_url'] = [
            'label'    => __('Cookies Policy URL', 'notificationx'),
            'name'     => 'gdpr_cookies_policy_link_url',
            'type'     => 'text',
            'priority' => 108,
            'placeholder' => __('Cookies Policy URL', 'notificationx'),
            'rules' => Rules::logicalRule([
                Rules::is('gdpr_cookies_policy_toggle', true),
            ]),      
        ];
        $_fields['gdpr_custom_logo'] = [
            'label'    => __('Custom Logo', 'notificationx'),
            'name'     => 'gdpr_custom_logo',
            'type'     => 'media',
            'priority' => 109,       
        ];
        return $fields;
    }

    public function add_design_fields( $fields ) {
        // dd($fields['themes']['fields']);
        $_fields = &$fields['themes']['fields'];
        $_fields['gdpr_design'] = [
            'name'   => "gdpr_design",
            'type'   => "section",
            'priority'=> 15,
            'fields' => [
                [
                    'label'   => __("Position", 'notificationx'),
                    'name'    => "gdpr_position",
                    'type'    => "select",
                    'default' => 'bottom-left',
                    'options' => GlobalFields::get_instance()->normalize_fields([
                        'bottom-left'  => __('Bottom Left', 'notificationx'),
                        'bottom-right' => __('Bottom Right', 'notificationx'),
                        'center' => __('Center', 'notificationx'),
                    ]),
                    'rules'   => Rules::logicalRule([
                        // Rules::is( 'type', 'gdpr' ),
                        Rules::includes('themes', [ 'gdpr_theme-light-one', 'gdpr_theme-light-two', 'gdpr_theme-light-three',
                     'gdpr_theme-light-four', 'gdpr_theme-dark-one', 'gdpr_theme-dark-two', 'gdpr_theme-dark-three', 'gdpr_theme-dark-four' ], false),
                    ]),
                ],
                [
                    'label'   => __("Position", 'notificationx'),
                    'name'    => "gdpr_banner_position",
                    'type'    => "select",
                    'default' => 'bottom',
                    'options' => GlobalFields::get_instance()->normalize_fields([
                        'bottom'  => __('Bottom', 'notificationx'),
                        'top' => __('Top', 'notificationx'),
                    ]),
                    'rules'   => Rules::logicalRule([
                        // Rules::is( 'type', 'gdpr' ),
                        Rules::includes('themes', [ 'gdpr_theme-banner-light-one', 'gdpr_theme-banner-light-two', 'gdpr_theme-banner-dark-one', 'gdpr_theme-banner-dark-two' ], false),
                    ]),
                ],
                [
                    'label'       => __('Display Close Option', 'notificationx'),
                    'name'        => 'link_button',
                    'type'        => 'checkbox',
                    'priority'    => 10,
                    'is_pro'      => true,
                    'default'     => false,
                    'description' => __('Display a close button', 'notificationx'),
                    'rules'       => Rules::includes('source', ['gdpr_notification']),
                ],
            ]
        ];

        return $fields;
    }

}
