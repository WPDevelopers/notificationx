<?php

/**
 * PressBar Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\PressBar;

use NotificationX\Core\Analytics;
use NotificationX\Core\GetData;
use NotificationX\Core\Helper;
use NotificationX\Core\PostType;
use NotificationX\Core\Rules;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\GlobalFields;
use NotificationX\FrontEnd\Preview;
use NotificationX\GetInstance;

use Elementor\Core\Files\CSS\Post as Post_CSS;
use NotificationX\NotificationX;

/**
 * PressBar Extension
 * @method static PressBar get_instance($args = null)
 */
class PressBar extends Extension {
    /**
     * Instance of Admin
     *
     * @var Admin
     */
    use GetInstance;

    public $priority        = 5;
    public $id              = 'press_bar';
    public $doc_link        = 'https://notificationx.com/docs/notification-bar/';
    public $types           = 'notification_bar';
    public $module          = 'modules_bar';
    public $module_priority = 1;
    public $default_theme   = 'press_bar_theme-one';
    public $bar_themes;
    public $block_themes;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        add_action('init', [$this, 'register_post_type']);
		add_filter( 'get_edit_post_link', function($link, $id){
            $post = get_post( $id );
            if ( $post && 'nx_bar' === $post->post_type && class_exists('\Elementor\Plugin') ) {
                return \Elementor\Plugin::$instance->documents->get($id)->get_edit_url();
            }
            return $link;
        }, 10, 3 );
    }

    public function init_extension()
    {
        $this->title        = __('Press Bar', 'notificationx');
        $this->module_title = __('Notification Bar', 'notificationx');
        $popup = "";

        $this->themes = [
            'theme-one'   => [
                'source'        => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/nx-bar-theme-one.jpg',
                'column'        => "12",
                'defaults' => [
                    'enable_countdown'            => 1,
                    'nx_bar_border_radius_left'   => 0,
                    'nx_bar_border_radius_right'  => 0,
                    'nx_bar_border_radius_top'    => 0,
                    'nx_bar_border_radius_bottom' => 0,
                    'button_icon'                 => 'none',
                    'bar_bg_color'                => '#dddddd',
                    'press_content'               => __('<b>Save Big & Get Lifetime unlimited <strong>NotificationX</strong> for $99</b>','notificationx'),
                    'button_text'                 => __('Get Offer', 'notificationx'),
                    'link_button_bg_color'        => '#000',
                    'link_button_text_color'      => '#ffffff',
                ],
            ],
            'theme-two'   => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/nx-bar-theme-two.jpg',
                'column'  => "12",
                'defaults' => [
                    'enable_countdown'            => 0,
                    'nx_bar_border_radius_left'   => 0,
                    'nx_bar_border_radius_right'  => 0,
                    'nx_bar_border_radius_top'    => 0,
                    'nx_bar_border_radius_bottom' => 0,
                    'button_icon'                 => 'none',
                    'bar_bg_color'                => '#5807a2',
                    'press_content'               => __('<b>We\'re excited to introduce something new!</b>','notificationx'),
                    'button_text'                 => __('Show Me!', 'notificationx'),
                    'link_button_bg_color'        => '#9c2bff',
                    'link_button_text_color'      => '#ffffff',
                ],
            ],
            'theme-three' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/nx-bar-theme-three.jpg',
                'column'  => "12",
                'defaults' => [
                    'enable_countdown'            => 1,
                    'nx_bar_border_radius_left'   => 0,
                    'nx_bar_border_radius_right'  => 0,
                    'nx_bar_border_radius_top'    => 0,
                    'nx_bar_border_radius_bottom' => 0,
                    'button_icon'                 => 'none',
                    'bar_bg_color'                => '#3f4462',
                    'press_content'               => __('<b>Save Big & Get Lifetime unlimited <strong>NotificationX</strong> for $99</b>','notificationx'),
                    'button_text'                 => __('Get Offer!', 'notificationx'),
                    'link_button_bg_color'        => '#6A4BFF',
                    'link_button_text_color'      => '#ffffff',
                ],
            ],
            'theme-four' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/press_bar_theme-four-preview.webp',
                'column'  => "12",
                'defaults' => [
                    'enable_countdown'            => 1,
                    'press_content'               => __('<p><span style="color: #F54747;">4 Years</span> Of Seamlessly Creating NotificationX!</p>','notificationx'),
                    'button_text'                 => __('Grab Deal Now', 'notificationx'),
                    'link_button_bg_color'        => '#ffffff',
                    'link_button_text_color'      => '#000',
                    'nx_bar_border_radius_left'   => 16,
                    'nx_bar_border_radius_right'  => 16,
                    'nx_bar_border_radius_top'    => 16,
                    'nx_bar_border_radius_bottom' => 16,
                    'button_icon'                 => 'none',
                ],
            ],
            'theme-five' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/press_bar_theme-five-preview.webp',
                'column'  => "12",
                'defaults' => [
                    'press_content'               => __('<p><span style="color: #fff;">🎁 Flash 30%</span> Sale is On Now! Don’t miss out on this opportunity</p>','notificationx'),
                    'enable_countdown'            => 1,
                    'nx_bar_border_radius_left'   => 16,
                    'nx_bar_border_radius_right'  => 16,
                    'nx_bar_border_radius_top'    => 16,
                    'nx_bar_border_radius_bottom' => 16,
                    'button_icon'                 => 'none',
                    'button_text'                 => __('Save $20', 'notificationx'),
                    'link_button_bg_color'        => '#2e72ff',
                    'link_button_text_color'      => '#ffffff',
                    'bar_bg_color'                => '',
                ],
            ],
            'theme-six' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/press_bar_theme-six-preview.webp',
                'column'  => "12",
                'defaults' => [
                    'press_content'               => __('<p><span style="color: #000;">🎁 Flash 30%</span> Sale is On Now! Don’t miss out on this opportunity</p>','notificationx'),
                    'enable_countdown'            => 1,
                    'nx_bar_border_radius_left'   => 16,
                    'nx_bar_border_radius_right'  => 16,
                    'nx_bar_border_radius_top'    => 16,
                    'nx_bar_border_radius_bottom' => 16,
                    'button_text'                 => __('Shop Now', 'notificationx'),
                    'bar_bg_color'                => 'linear-gradient(90deg, #94F9FC 0%, #E2DAFE 100%)',
                    'button_icon'                => 'shop-icon.svg',
                ],
            ],
            'theme-seven' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/press_bar_theme-seven-preview.webp',
                'column'  => "12",
                'defaults' => [
                    'press_content'               => __('<p><span style="color: #9F7800;">4 years</span> Of Seamlessly Creating NotificationX!</p>','notificationx'),
                    'enable_countdown'            => 0,
                    'nx_bar_border_radius_left'   => 0,
                    'nx_bar_border_radius_right'  => 0,
                    'nx_bar_border_radius_top'    => 0,
                    'nx_bar_border_radius_bottom' => 0,
                    'button_text'                 => __('Shop Now', 'notificationx'),
                    'bar_bg_color'                => '#F4F1E8',
                    'button_icon'                => 'shop_now.svg',
                    'link_button_bg_color'        => '#e3dac2',
                    'link_button_text_color'      => '#000',
                ],
            ],
        ];
        $this->bar_themes = array(
            'theme-one'   => [
                'label'         => 'theme-one',
                'value'         => 'theme-one',
                'icon'          => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/bar-elementor/theme-one.jpg',
                'column'        => '12',
                "title"         => "Nx Theme One",
                'enable_coupon' => true,
            ],
            'theme-two'   => [
                'label'  => 'theme-two',
                'value'  => 'theme-two',
                'icon'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/bar-elementor/theme-two.jpg',
                'column' => '12',
                "title"  => "Nx Theme Two",
            ],
            'theme-three' => [
                'label'  => 'theme-three',
                'value'  => 'theme-three',
                'icon'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/bar-elementor/theme-three.jpg',
                'column' => '12',
                "title"  => "Nx Theme Three",
            ],
            'theme-four'  => [
                'label'  => 'theme-four',
                'value'  => 'theme-four',
                'icon'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/bar-elementor/theme-four.jpg',
                'column' => '12',
                "title"  => "Theme Four - Cookies Layout",
            ],
            'theme-five'  => [
                'label'  => 'theme-five',
                'value'  => 'theme-five',
                'icon'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/bar-elementor/theme-five.jpg',
                'column' => '12',
                "title"  => "Theme Five - Cookies Layout",
            ],
        );
        $this->block_themes = array(
            'theme-one' => [
                'label'  => 'theme-one',
                'value'  => 'theme-one',
                'icon'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/bar-gutenberg/theme-one.png',
                'column' => '12',
                "title"  => "Nx Theme One",
            ],
            'theme-two' => [
                'label'  => 'theme-two',
                'value'  => 'theme-two',
                'icon'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/bar-gutenberg/theme-two.png',
                'column' => '12',
                "title"  => "Nx Theme Two",
            ],
            'theme-three' => [
                'label'  => 'theme-three',
                'value'  => 'theme-three',
                'icon'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/bar-gutenberg/theme-three.png',
                'column' => '12',
                "title"  => "Nx Theme Three",
            ],
            'theme-four' => [
                'label'  => 'theme-four',
                'value'  => 'theme-four',
                'icon'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/bar-gutenberg/theme-four.png',
                'column' => '12',
                "title"  => "Nx Theme Four",
            ],
            'theme-five'   => [
                'label'  => 'theme-five',
                'value'  => 'theme-five',
                'icon'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/nx-bar-theme-one.jpg',
                'column' => '12',
                "title"  => "Nx Theme Five",
                "popup"  => $popup,
            ],
            'theme-six'   => [
                'label'  => 'theme-six',
                'value'  => 'theme-six',
                'icon'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/nx-bar-theme-two.jpg',
                'column' => '12',
                "title"  => "Nx Theme Six",
                "popup"  => $popup,
            ],
            'theme-seven' => [
                'label'  => 'theme-seven',
                'value'  => 'theme-seven',
                'icon'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/nx-bar-theme-three.jpg',
                'column' => '12',
                "title"  => "Nx Theme Seven",
                "popup"  => $popup,
            ],
        );
        // check if essential blocks is installed.
        if(!Helper::is_plugin_active('essential-blocks/essential-blocks.php')){
            $popup = array(
                // forcing the popup without the is_pro.
                "forced"            => true,
                "showConfirmButton" => true,
                "showCloseButton"   => true,
                "title"             => "You are missing a dependency.",
                "customClass"       => array(
                    "container"     => "pressbar-gutenberg-theme-popup",
                    // "closeButton"   => "pro-video-close-button",
                    // "icon"          => "pro-video-icon",
                    // "title"         => "pro-video-title",
                    // "content"       => "pro-video-content",
                    // "actions"       => "nx-pro-alert-actions",
                    // "confirmButton" => "pro-video-confirm-button",
                    // "denyButton"    => "pro-video-deny-button"
                ),
                "denyButtonText"    => sprintf("<a href='%s' target='_blank'>%s</a>", admin_url('plugin-install.php?s=Essential%2520Blocks&tab=search&type=term'), __("Install Essential Blocks", 'notificationx')),
                "confirmButtonText" => "<a href='https://essential-blocks.com/' target='_blank'>More Info</a>",
                "html"              => "
                    <span>Highlight your sales, low stock updates with inline growth alert to boost sales</span>
                "
            );
        }
    }

    public function init() {
        parent::init();
        add_filter("nx_theme_preview_{$this->id}", [$this, 'theme_preview'], 10, 2);
        add_filter("nx_get_post", [$this, 'nx_get_post'], 9);
        add_filter("nx_delete_post", [$this, 'nx_delete_post'], 10, 2);
        add_filter("nx_filtered_post", [$this, 'add_scripts'], 10, 2);
    }

    /**
     * This functions is hooked
     *
     * @return void
     */
    public function admin_actions() {
        parent::admin_actions();
        add_filter('templately_cloud_push_post_type', [$this, 'templately_cloud_push_post_type']);
    }

    /**
     * This functions is hooked
     *
     * @hooked nx_public_action
     * @return void
     */
    public function public_actions() {
        parent::public_actions();
        // add_action('wp_head', [$this, 'print_bar_notice'], 100);
        add_filter("nx_filtered_data_{$this->id}", array($this, 'insert_views'), 11, 3);
        add_filter("rocket_rucss_safelist", array($this, 'rocket_rucss_safelist'), 11);
    }


    public function init_fields() {
        parent::init_fields();
        add_filter('nx_design_tab_fields', [$this, 'design_tab_fields']);
        add_filter('nx_design_tab_fields', [$this, 'design_tab_fields_for_button'], 20);
        add_filter('nx_customize_fields', [$this, 'customize_fields']);
        add_filter('nx_content_fields', [$this, 'content_fields'], 22);
        add_filter('nx_display_fields', [$this, 'hide_image_field']);
        add_filter('nx_display_fields', [$this, 'display_fields']);
        add_filter('nx_source_trigger', [$this, '_source_trigger'], 20);
    }

    public function save_post($post, $data, $nx_id) {
        unset($post['data']['is_elementor']);
        unset($post['data']['is_confirmed']);
        unset($post['data']['is_gutenberg']);
        unset($post['data']['is_gb_confirmed']);
        $post['data']['countdown_start_date'] = !empty( $data['countdown_start_date'] ) ? Helper::mysql_time($data['countdown_start_date']) : '';
        $post['data']['countdown_end_date'] = !empty( $data['countdown_end_date'] ) ? Helper::mysql_time($data['countdown_end_date']) : '';
        $post['data']['countdown_rand'] = rand();
        return $post;
    }

    public function saved_post($post, $data, $nx_id) {
        if(!empty($data['elementor_id'])){
            $title = !empty($post['title']) ? $post['title'] : $nx_id;
            $my_post = array(
                'ID'           => $data['elementor_id'],
                'post_title'   => "NxBar: " . $title,
            );
            wp_update_post( $my_post );
        }
    }

    public function theme_preview($url, $post) {
        if ( !empty($post['gutenberg_id']) && !empty($post['gutenberg_bar_theme']) && !empty($this->block_themes[$post['gutenberg_bar_theme']])) {
            return $this->block_themes[$post['gutenberg_bar_theme']]['icon'];
        }
        if (!empty($post['elementor_id']) && !empty($post['elementor_bar_theme']) && !empty($this->bar_themes[$post['elementor_bar_theme']])) {
            return $this->bar_themes[$post['elementor_bar_theme']]['icon'];
        }
        return $url;
    }

    /**
     * Get themes for the extension.
     *
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function insert_views($content, $settings) {
        Analytics::get_instance()->insert_views([], $settings);
        return $content;
    }

    /**
     * Get themes for the extension.
     *
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function _source_trigger($triggers) {
        $triggers[$this->id]['position'] = "@position:top";
        return $triggers;
    }

    /** 
     * NX Bar design tab fields for button.
     * 
     * @param array $fields
     * @return array
     */
    public function design_tab_fields_for_button($fields) {
        $_fields = &$fields['advance_design_section']['fields'];
        $border_fields = [
			 'nx_bar_border_radius' => [
                'name'    => "nx_bar_border_radius",
                'type'    => "section",
                'label'   => __('Border Radius', 'notificationx'),
                'fields' => [
                    [
                        'help'        => __('Left', 'notificationx'),
                        'name'        => "nx_bar_border_radius_left",
                        'type'        => "number",
                        'default'     => '0',
                        'description' => 'px',
                    ],
                    [
                        'help'        => __('Right', 'notificationx'),
                        'name'        => "nx_bar_border_radius_right",
                        'type'        => "number",
                        'default'     => '0',
                        'description' => 'px',
                    ],
                    [
                        'help'        => __('Top', 'notificationx'),
                        'name'        => "nx_bar_border_radius_top",
                        'type'        => "number",
                        'default'     => '0',
                        'description' => 'px',
                    ],
                    [
                        'help'        => __('Bottom', 'notificationx'),
                        'name'        => "nx_bar_border_radius_bottom",
                        'type'        => "number",
                        'default'     => '0',
                        'description' => 'px',
                    ],
                ]
            ],
		];
        if( !empty($_fields['link_button_design']['fields'] ) ) {
            $_fields['link_button_design']['fields'] = array_merge( $_fields['link_button_design']['fields'], $border_fields);
        }
        return $fields;
    }

    /**
     * This method is an implementable method for All Extension coming forward.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function design_tab_fields($fields) {
        $_fields                     = &$fields['advance_design_section']['fields'];
        $_fields['design']           = Rules::is('source', $this->id, true, $_fields['design']);
        $_fields['typography']       = Rules::is('source', $this->id, true, $_fields['typography']);
        $_fields['image-appearance'] = Rules::is('source', $this->id, true, $_fields['image-appearance']);
        $_fields['bar_editor']       = [
            'label'  => "Editor",
            'name'   => "bar_editor",
            'type'   => "section",
            'priority' => 2,
            'rules'  => ["and", ['is', 'source', $this->id], ['is', 'advance_edit', true]],
            'fields' => [],
        ];
        $_fields["bar_design"]       = [
            // @todo Move to extension.
            'label'  => "Design",
            'name'   => "bar_design",
            'type'   => "section",
            'priority' => 5,
            'rules'  => ["and", ['is', 'source', $this->id], ['is', 'advance_edit', true]],
            'fields' => [
                [
                    'label' => __('Background Color', 'notificationx'),
                    'name'  => "bar_bg_color",
                    'type'  => "gradientpicker",
                ],
                [
                    'label' => __("Background Image", 'notificationx'),
                    'name'  => "bar_bg_image",
                    'button'  => __('Upload', 'notificationx'),
                    'type'  => "media",
                    'default' => "",
                ],
                [
                    'label' => __('Text Color', 'notificationx'),
                    'name'  => "bar_text_color",
                    'type'  => "colorpicker",
                ],
                // [
                //     'label' => __('Button Background Color', 'notificationx'),
                //     'name'  => "bar_btn_bg",
                //     'type'  => "colorpicker",
                // ],
                // [
                //     'label' => __('Button Text Color', 'notificationx'),
                //     'name'  => "bar_btn_text_color",
                //     'type'  => "colorpicker",
                // ],
                [
                    'label' => __('Countdown Background Color', 'notificationx'),
                    'name'  => "bar_counter_bg",
                    'type'  => "colorpicker",
                ],
                [
                    'label' => __('Countdown Text Color', 'notificationx'),
                    'name'  => "bar_counter_text_color",
                    'type'  => "colorpicker",
                ],
                [
                    'label' => __('Close Button Color', 'notificationx'),
                    'name'  => "bar_close_color",
                    'type'  => "colorpicker",
                ],
                [
                    'label'       => __('Close Button Size', 'notificationx'),
                    'name'        => "bar_close_button_size",
                    'type'        => "number",
                    'default'     => '10',
                    'description' => 'px',
                ],
                [
                    'name'    => "close_button_section",
                    'type'    => "section",
                    'fields' => [
                       [
                            'name'  => 'closed_button_section_label',
                            'type'  => 'section',
                            'fields'    => [
                                [
                                    'name'    => 'closed_button_section_label_text',
                                    'type'    => 'message',
                                    'class'   => 'nx-close-button-label',
                                    'html'    => true,
                                    'message' => __('Close Button Position', 'notificationx'),
                                ]
                            ]
                        ],
                        [
                            'name'  => 'closed_button_section_fields',
                            'type'  => 'section',
                            'fields'    => [
                                [
                                    'label'   => __('Close Button Position', 'notificationx'),
                                    'name'    => "bar_close_position",
                                    'type'    => "select",
                                    'default' => 'right',
                                    'options' => GlobalFields::get_instance()->normalize_fields([
                                        'left'  => __('Left', 'notificationx'),
                                        'right' => __('Right', 'notificationx'),
                                    ]),
                                ],
                                [
                                    'label'       => __('Close Button Position Top', 'notificationx'),
                                    'help'        => __('Top', 'notificationx'),
                                    'name'        => "bar_position_left_top",
                                    'type'        => "number",
                                    'default'     => '15',
                                    'description' => 'px',
                                    'rules'       => Rules::logicalRule([
                                        Rules::is('bar_close_position', 'left'),
                                    ]),
                                ],
                                [
                                    'label'       => __('Close Button Position Left', 'notificationx'),
                                    'help'        => __('Left', 'notificationx'),
                                    'name'        => "bar_position_left_left",
                                    'type'        => "number",
                                    'default'     => '15',
                                    'description' => 'px',
                                    'rules'       => Rules::logicalRule([
                                        Rules::is('bar_close_position', 'left'),
                                    ]),
                                ],
                                [
                                    'label'       => __('Close Button Position Top', 'notificationx'),
                                    'help'        => __('Top', 'notificationx'),
                                    'name'        => "bar_position_right_top",
                                    'type'        => "number",
                                    'default'     => '15',
                                    'description' => 'px',
                                    'rules'       => Rules::logicalRule([
                                        Rules::is('bar_close_position', 'right'),
                                    ]),
                                ],
                                [
                                    'label'       => __('Close Button Position Right', 'notificationx'),
                                    'help'        => __('Right', 'notificationx'),
                                    'name'        => "bar_position_right_right",
                                    'type'        => "number",
                                    'default'     => '15',
                                    'description' => 'px',
                                    'rules'       => Rules::logicalRule([
                                        Rules::is('bar_close_position', 'right'),
                                    ]),
                                ],
                            ]
                        ]
                    ]
                ],
            ],
        ];

        $_fields["bar_typography"] = [
            // @todo Move to extension.
            'label'  => __('Typography', 'notificationx'),
            'name'   => "bar_typography",
            'type'   => "section",
            'priority' => 10,
            'rules'  => ["and", ['is', 'source', $this->id], ['is', 'advance_edit', true]],
            'fields' => [
                [
                    'label'       => __('Font Size', 'notificationx'),
                    'name'        => "bar_font_size",
                    'type'        => "number",
                    'default'     => '13',
                    'priority'    => 5,
                    'description' => 'px',
                    'help'        => __('This font size will be applied for <mark>first</mark> row', 'notificationx'),
                ],
            ],
        ];

        $is_installed = Helper::is_plugin_installed('elementor/elementor.php');
        $install_activate_text = $is_installed ? __("Activate", 'notificationx') : __("Install", 'notificationx');



        $fields['themes']['fields'][] = array(
            'name'    => 'nx-bar_with_elementor_install_message',
            'type'    => 'message',
            'class'   => 'nx-warning',
            'html'    => true,
            'message' => sprintf(__("To Design Notification Bar with <strong>Elementor Page Builder</strong>, You need to %s the Elementor first: &nbsp;&nbsp;&nbsp;", 'notificationx'), $install_activate_text),
            'rules'   => Rules::logicalRule([
                Rules::is('is_elementor', false),
                Rules::is('gutenberg_id', false),
                Rules::is('source', $this->id),
            ]),
        );

        $fields['themes']['fields']['nx_bar_import_design'] = [
            'name'   => 'nx_bar_import_design',
            'type'   => 'section',
            'fields' => [],
            'rules'  => Rules::logicalRule([
                Rules:: is('source', $this->id),
            ]),
        ];

        $import_design = [];
        // $import_design = $fields['advance_design_section']['fields'];
        $import_design = &$fields['themes']['fields']['nx_bar_import_design']['fields'];

        $import_design[] = [
            'name'   => 'elementor_edit_link',
            'type'   => 'button',
            'text'   => __('Edit With Elementor', 'notificationx'),
            'href'   => -1,
            'priority' => 1,
            'target' => '_blank',
            'rules'  => Rules::logicalRule([
                Rules::is('elementor_edit_link', false, true),
                Rules::isOfType('elementor_edit_link', 'string'),
                Rules:: is('elementor_id', false, true),
                // Rules:: is('is_elementor', true),
                // Rules:: is('source', $this->id),
            ]),
        ];
        $import_design[] = [
            'name'  => 'nx-bar_with_elementor-remove',
            'type'  => 'button',
            'priority' => 2,
            'text'  => __('Remove Elementor Design', 'notificationx'),
            'rules' => Rules::logicalRule([
                Rules::is('elementor_id', false, true),
                Rules::is('is_elementor', true),
                Rules::is('source', $this->id),
            ]),
            'ajax'    => [
                'on'   => 'click',
                'api'  => '/notificationx/v1/elementor/remove',
                'data' => [
                    'elementor_id' => '@elementor_id',
                ],
                'hideSwal' => true,
            ],
            'trigger' => [
                [
                    'type'   => 'setFieldValue',
                    'action' => [
                        'elementor_id' => false,
                        'elementor_edit_link' => '',
                        'is_confirmed' => false,
                        'themes' => 'press_bar_theme-one',
                    ]
                ],
            ],
        ];

        $import_design[] = [
            'name'   => 'nx-bar_with_elementor',
            'type'   => 'modal',
            'button' => [
                'name' => 'build_with_elementor',
                'text' => __('Build With Elementor', 'notificationx'),
                'trigger' => [
                    [
                        'type'   => 'setFieldValue',
                        'action' => [
                            'import_elementor_theme' => false
                        ]
                    ],
                ],
            ],
            'confirm_button' => [
                'type'   => 'button',
                'name'   => 'import_elementor_theme',
                'group'  => true,
                'fields' => [
                    [
                        'type'    => 'button',
                        'name'    => 'import_elementor_theme',
                        "default" => false,
                        'text'    => [
                            'normal'  => __('Import', 'notificationx'),
                            'saved'   => __('Import', 'notificationx'),
                            'loading' => __('Importing...', 'notificationx'),
                        ],
                        'ajax'    => [
                            'on'   => 'click',
                            'api'  => '/notificationx/v1/elementor/import',
                            'data' => [
                                'theme_id' => '@elementor_bar_theme',
                            ],
                            'trigger' => '@is_confirmed:true',
                            'hideSwal' => true,
                        ],
                        'rules' => Rules::is('is_confirmed', true, true),
                    ],
                    [
                        'type'    => 'button',
                        'name'    => 'import_elementor_theme_next',
                        "default" => false,
                        'text'    => __('Next', 'notificationx'),
                        'rules'   => Rules::is('is_confirmed', true),
                        'trigger' => [
                            [
                                'type'   => 'setContext',
                                'action' => [
                                    'config.active' => 'display_tab'
                                ]
                            ],
                            [
                                'type'   => 'setFieldValue',
                                'action' => [
                                    'import_elementor_theme_next' => true
                                ]
                            ],
                        ],
                    ],
                ],
            ],
            'cancel' => "import_elementor_theme_next",
            'body'   => [
                'header' => __('Choose Your ', 'notificationx'),
                'fields' => [
                    'themes' => [
                        'type'  => 'radio-card',
                        'name'  => "elementor_bar_theme",
                        'style' => [
                            'label' => [
                                'position' => 'top'
                            ]
                        ],
                        'rules'   => Rules::is('is_confirmed', true, true),
                        'default' => 'theme-one',
                        'options' => $this->bar_themes,
                    ],
                    'test' => [
                        'name'    => 'builder_modal_message',
                        'type'    => 'message',
                        'message' => 'Hello World',
                        'rules'   => Rules::is('is_confirmed', true)
                    ]
                ],
            ],
            'rules'  => Rules::logicalRule([
                Rules::is('gutenberg_id', false),
                Rules::is('elementor_id', false),
                Rules::is('is_elementor', true),
                Rules::is('is_confirmed', true, true),
                Rules::is('source', $this->id)
            ]),
        ];

        $import_design[] = [
            'name'        => 'nx-bar_with_elementor_install',
            'type'        => 'button',
            'priority'    => 3,
            'text'    => [
                'normal'  => $is_installed ? __('Activate Elementor', 'notificationx') : __('Install Elementor', 'notificationx'),
                'saved'   => $is_installed ? __('Activated Elementor', 'notificationx') : __('Installed Elementor', 'notificationx'),
                'loading' => $is_installed ? __('Activating Elementor...', 'notificationx') : __('Installing Elementor...', 'notificationx'),
            ],
            'style'       => [
                'description' => [
                    'position' => 'left'
                ]
            ],
            // 'classes' => "nx-ele-bar-button nx-bar_with_elementor_install nx-on-click-install",
            'rules'   => Rules::logicalRule([
                Rules::is('is_elementor', false),
                Rules::is('gutenberg_id', false),
                Rules::is('source', $this->id),
            ]),
            // 'data-nonce' => wp_create_nonce('wpdeveloper_upsale_core_install_notificationx'),
            // 'data-slug' => 'elementor',
            // 'data-plugin_file' => 'elementor.php',
            'ajax'      => [
                'on'   => 'click',
                'api'  => '/notificationx/v1/core-install',
                'data' => [
                    'source'       => $this->id,
                    'slug'         => "elementor",
                    'file'         => "elementor.php",
                    'is_installed' => $is_installed,
                ],
                'swal' => [
                    'icon' => 'success',
                    'text' => __('Successfully Activated', 'notificationx'),
                ],
                'trigger' => '@is_elementor:true',
            ],
        ];
        $import_design[] = [
            'name'    => 'is_elementor',
            'type'    => 'hidden',
            'default' => class_exists('\Elementor\Plugin'),
            'rules'   => Rules::is('source', $this->id),
        ];
        $import_design[] = [
            'name'    => 'elementor_id',
            'type'    => 'hidden',
            'default' => false,
            'rules'   => Rules::is('source', $this->id),
        ];
        $import_design[] = [
            'type'    => 'hidden',
            'name'    => 'is_confirmed',
            'default' => false
        ];
        // $import_design[] = [
        //     'name'    => 'elementor_edit_link',
        //     'type'    => 'hidden',
        //     'rules'   => Rules::is('source', $this->id),
        // ];


        // Block pattern

        $import_design[] = [
            'name'   => 'gutenberg_edit_link',
            'type'   => 'button',
            'text'   => __('Edit With Gutenberg', 'notificationx'),
            'href'   => -1,
            'priority' => 4,
            'target' => '_blank',
            'rules'  => Rules::logicalRule([
                Rules::is('gutenberg_edit_link', false, true),
                Rules::isOfType('gutenberg_edit_link', 'string'),
                Rules:: is('gutenberg_id', false, true),
                // Rules:: is('is_gutenberg', true),
                // Rules:: is('source', $this->id),
            ]),
        ];
        $import_design[] = [
            'name'  => 'nx-bar_with_gutenberg-remove',
            'type'  => 'button',
            'text'  => __('Remove Gutenberg Design', 'notificationx'),
            'priority' => 5,
            'rules' => Rules::logicalRule([
                Rules::is('gutenberg_id', false, true),
                Rules::is('is_gutenberg', true),
                Rules::is('source', $this->id),
            ]),
            'ajax'    => [
                'on'   => 'click',
                'api'  => '/notificationx/v1/gutenberg/remove',
                'data' => [
                    'gutenberg_id' => '@gutenberg_id',
                ],
                'hideSwal' => true,
            ],
            'trigger' => [
                [
                    'type'   => 'setFieldValue',
                    'action' => [
                        'gutenberg_id' => false,
                        'gutenberg_edit_link' => '',
                        'is_gb_confirmed' => false,
                        'themes' => 'press_bar_theme-one',
                    ]
                ],
            ],
        ];

        $import_design[] = [
            'name'   => 'nx-bar_with_gutenberg',
            'type'   => 'modal',
            'button' => [
                'name' => 'build_with_gutenberg',
                'text' => __('Build With Gutenberg', 'notificationx'),
                'trigger' => [
                    [
                        'type'   => 'setFieldValue',
                        'action' => [
                            'import_gutenberg_theme' => false
                        ]
                    ],
                ],
            ],
            'confirm_button' => [
                'type'   => 'button',
                'name'   => 'import_gutenberg_theme',
                'group'  => true,
                'fields' => [
                    [
                        'type'    => 'button',
                        'name'    => 'import_gutenberg_theme',
                        "default" => false,
                        'text'    => [
                            'normal'  => __('Import', 'notificationx'),
                            'saved'   => __('Import', 'notificationx'),
                            'loading' => __('Importing...', 'notificationx'),
                        ],
                        'ajax'    => [
                            'on'   => 'click',
                            'api'  => '/notificationx/v1/gutenberg/import',
                            'data' => [
                                'theme_id' => '@gutenberg_bar_theme',
                            ],
                            'trigger' => '@is_gb_confirmed:true',
                            'hideSwal' => true,
                        ],
                        'rules' => Rules::is('is_gb_confirmed', true, true),
                    ],
                    [
                        'type'    => 'button',
                        'name'    => 'import_gutenberg_theme_next',
                        "default" => false,
                        'text'    => __('Next', 'notificationx'),
                        'rules'   => Rules::is('is_gb_confirmed', true),
                        'trigger' => [
                            [
                                'type'   => 'setContext',
                                'action' => [
                                    'config.active' => 'display_tab'
                                ]
                            ],
                            [
                                'type'   => 'setFieldValue',
                                'action' => [
                                    'import_gutenberg_theme_next' => true
                                ]
                            ],
                        ],
                    ],
                ],
            ],
            'cancel' => "import_gutenberg_theme_next",
            'body'   => [
                'header' => __('Choose Your ', 'notificationx'),
                'fields' => [
                    'themes' => [
                        'type'  => 'radio-card',
                        'name'  => "gutenberg_bar_theme",
                        'style' => [
                            'label' => [
                                'position' => 'top'
                            ]
                        ],
                        'rules'   => Rules::is('is_gb_confirmed', true, true),
                        'default' => 'theme-one',
                        'options' => $this->block_themes,
                    ],
                    'test' => [
                        'name'    => 'builder_modal_message',
                        'type'    => 'message',
                        'message' => 'Hello World',
                        'rules'   => Rules::is('is_gb_confirmed', true)
                    ]
                ],
            ],
            'rules'  => Rules::logicalRule([
                Rules::is('elementor_id', false),
                Rules::is('gutenberg_id', false),
                Rules::is('is_gutenberg', true),
                Rules::is('is_gb_confirmed', true, true),
                Rules::is('source', $this->id)
            ]),
        ];
        $import_design[] = [
            'name'    => 'is_gutenberg',
            'type'    => 'hidden',
            'default' => function_exists('use_block_editor_for_post_type') ? use_block_editor_for_post_type('nx_bar_eb') : false,
            'rules'   => Rules::is('source', $this->id),
        ];
        $import_design[] = [
            'name'    => 'gutenberg_id',
            'type'    => 'hidden',
            'default' => false,
            'rules'   => Rules::is('source', $this->id),
        ];
        $import_design[] = [
            'type'    => 'hidden',
            'name'    => 'is_gb_confirmed',
            'default' => false
        ];
        $_fields['bar_editor']['fields'] = array_merge($_fields['bar_editor']['fields']);
        return $fields;
    }

    /**
     * Display fields
    */
    public function display_fields( $fields ) {
        $fields['visibility']['fields']['bar_reappearance'] = array(
            'label'   => __( 'Bar Reappearance', 'notificationx' ),
            'type'    => 'select',
            'name'    => 'bar_reappearance',
            'default' => 'show_welcomebar_every_page',
            'rules'   => Rules::logicalRule([
                Rules::is('source', $this->id),
            ]),
            'options'  => GlobalFields::get_instance()->normalize_fields([
                'dont_show_welcomebar'       => __( "Don't show the Bar again for the user", 'notificationx' ),
                'show_welcomebar_next_visit' => __( 'Show the Bar again when the user visits the website next time', 'notificationx' ),
                'show_welcomebar_every_page' => __( 'Show the Bar when the user refreshes/goes to another page', 'notificationx' ),
            ]),
        );
        return $fields;
    }

     /**
     * Get All Roles
     * dynamically
     *
     * @return array
     */
    public function get_roles() {
        $roles = wp_roles()->role_names;
        return $roles;
    }

    /**
     * This method is an implementable method for All Extension coming forward.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function customize_fields($fields) {
        $wp_roles  = $this->get_roles();

        $fields['queue_management'] = Rules::is('source', $this->id, true, $fields['queue_management']);
        $_fields             = &$fields["appearance"]['fields'];
        $conversion_position = &$_fields['position']['options'];

        $_fields['size']         = Rules::is('source', $this->id, true, $_fields['size']);
        $conversion_position['bottom_left']  = Rules::is('source', $this->id, true, $conversion_position['bottom_left']);
        $conversion_position['bottom_right'] = Rules::is('source', $this->id, true, $conversion_position['bottom_right']);

        if (isset($fields['sound_section'])) {
            $fields['sound_section'] = Rules::includes('source', $this->id, true, $fields['sound_section']);
        }

        $conversion_position['top'] = [
            'label' => __('Top', 'notificationx'),
            'value' => 'top',
            'rules' => Rules::is('source', $this->id),
        ];
        $conversion_position['bottom'] = [
            'label' => __('Bottom', 'notificationx'),
            'value' => 'bottom',
            'rules' => Rules::is('source', $this->id),
        ];

        $_fields['sticky_bar'] = [
            'label'       => __("Sticky Bar?", 'notificationx'),
            'name'        => "sticky_bar",
            'type'        => "checkbox",
            'default'     => 0,
            'priority'    => 60,
            'description' => __('If checked, this will fixed Notification Bar at top or bottom.', 'notificationx'),
            'rules'       => Rules::is('source', $this->id),
        ];

        $_fields['pressbar_body'] = [
            'label'       => __("Display Overlapping", 'notificationx'),
            'name'        => "pressbar_body",
            'type'        => "checkbox",
            'default'     => 0,
            'priority'    => 61,
            'description' => __('Show Notification Bar overlapping content instead of pushing.', 'notificationx'),
            'rules'       => Rules::is('source', $this->id),
        ];

        //
        $fields["timing"]['fields']['delay_before']  = Rules::is('source', $this->id, true, $fields["timing"]['fields']['delay_before']);
        $fields["timing"]['fields']['display_for']   = Rules::is('source', $this->id, true, $fields["timing"]['fields']['display_for']);
        $fields["timing"]['fields']['delay_between'] = Rules::is('source', $this->id, true, $fields["timing"]['fields']['delay_between']);

        $fields["timing"]['fields']['auto_hide'] = [
            'label'       => __("Auto Hide", 'notificationx'),
            'name'        => "auto_hide",
            'type'        => "checkbox",
            'priority'    => 50,
            'default'     => false,
            'description' => __('If checked, notification bar will be hidden after the time set below.', 'notificationx'),
            'rules'       => Rules::is('source', $this->id),
        ];

        $fields["timing"]['fields']['hide_after'] = [
            'label'       => __("Hide After", 'notificationx'),
            'name'        => "hide_after",
            'type'        => "number",
            'priority'    => 55,
            'default'     => 60,
            'description' => __('seconds', 'notificationx'),
            'help'        => __('Hide after 60 seconds', 'notificationx'),
            'rules'       => ['is', 'auto_hide', true],
            // 'rules'       => Rules::is('source', $this->id),
        ];
        $fields["timing"]['fields']['appear_condition'] = [
            'label'       => __('Notification Bar will Appear', 'notificationx'),
            'name'        => 'appear_condition',
            'type'        => 'radio-card',
            'priority'    => 60,
            'classes'  => 'radio-card-v2',
            'default'     => 'delay',
            'options'     => [
                'delay' => [
                    'value' => 'after_few_seconds',
                    'label' => __('After a few seconds', 'notificationx'),
                ],
                'scroll' => [
                    'value' => 'on_scroll',
                    'label' => __('On Scroll', 'notificationx'),
                ],
            ],
            'rules'       => Rules::is('source', $this->id),
        ];

        // This field appears only when "On Scroll" is selected
        $fields["timing"]['fields']['scroll_offset'] = [
            'label'    => __('Scroll Offset', 'notificationx'),
            'name'     => 'scroll_offset',
            'type'     => 'group',
            'priority' => 65,
            'fields'   => [
                'scroll_trigger_value' => [
                    'name'    => 'scroll_trigger_value',
                    'type'    => 'number',
                    'min'     => 0,
                    'default' => 100,
                ],
                'scroll_trigger_mode' => [
                    'type'    => 'select',
                    'name'    => 'scroll_trigger_mode',
                    'options' => GlobalFields::get_instance()->normalize_fields([
                        'px'      => __('PX', 'notificationx'),
                        'vh'      => __('VH', 'notificationx'),
                        'percent' => __('%', 'notificationx'),
                    ]),
                    'default' => 'px',
                ],
            ],
            'rules' => ['is', 'appear_condition', 'on_scroll'],
        ];

        $fields["timing"]['fields']['initial_delay'] = [
            'label'       => __("Initial Delay", 'notificationx'),
            'name'        => "initial_delay",
            'type'        => "number",
            'priority'    => 70,
            'default'     => 5,
            'help'        => __('Initial Delay', 'notificationx'),
            'description' => __('seconds', 'notificationx'),
            'rules'       => Rules::logicalRule([
                Rules::is('source', $this->id),
                Rules::is('appear_condition', 'after_few_seconds'),
            ]),
        ];

        $fields['targeting'] = [
            'label'    => __('Targeting', 'notificationx'),
            'type'     => 'section',
            'id'       => 'targeting',
            'classes'  => 'nx-targeting',
            'priority' => 100,
            'fields'   => []
        ];

        // Country Targeting
        $fields['targeting']['fields']['country_targeting'] = [
            'label'    => __('Country Targeting', 'notificationx'),
            'name'     => 'country_targeting',
            'type'     => 'better-select',
            'priority' => 10,
            'is_pro'   => true,
            'multiple' => true,
            'values'  => [  'label' => "All Country", 'value' => 'all' ],
            'option'  => GlobalFields::get_instance()->normalize_fields(Helper::nx_get_all_country()),
            'ajax'   => [
                'api'  => "/notificationx/v1/get-data",
                'data' => [
                    'type'   => "@type",
                    'source' => "@source",
                    // 'field'  => "country_targeting",
                ],
            ],
            'rules'    => Rules::is('source', $this->id),
        ];

        // User Role Targeting
        $wp_roles_with_default = [];
        if( is_array( $wp_roles ) ) {
            $wp_roles_with_default = array_merge(
                [ 'all_users' => __('Show for All Users', 'notificationx') ],
                $wp_roles
            );
        }
        $fields['targeting']['fields']['targeting_user_roles'] = [
            'label'    => __('Set Target Audience', 'notificationx'),
            'name'     => 'targeting_user_roles',
            'type'     => 'select',
            'priority' => 20,
            'is_pro'   => true,
            'default'  => ['all_users'],
            'options'  => GlobalFields::get_instance()->normalize_fields($wp_roles_with_default),
            'multiple' => true,
            'rules'    => Rules::is('source', $this->id),
        ];


        $fields["behaviour"]['fields']['display_last'] = Rules::is('source', $this->id, true, $fields["behaviour"]['fields']['display_last']);
        $fields["behaviour"]['fields']['display_from'] = Rules::is('source', $this->id, true, $fields["behaviour"]['fields']['display_from']);
        $fields["behaviour"]['fields']['loop']         = Rules::is('source', $this->id, true, $fields["behaviour"]['fields']['loop']);
        $fields["behaviour"] = Rules::logicalRule([
            Rules::logicalRule([
                Rules::isOfType('elementor_id', 'number', true),
                Rules::isOfType('gutenberg_id', 'number', true),
            ], 'and'),
            Rules::is('source', $this->id, true),
        ], 'or', $fields["behaviour"]);
        //Rules::isOfType('elementor_id', 'number', true, $fields["behaviour"]);

        // Add Schedule section
        $_fields['schedule'] = array(
            'name'     => 'schedule',
            'label'    => __('Schedule', 'notificationx'),
            'type'     => 'section',
            'is_pro'   => true,
            'priority' => 96,
            'fields'   => array(
                'schedule_type' => array(
                    'name'     => 'schedule_type',
                    'type'     => 'radio-card',
                    'label'    => __('Schedule Type', 'notificationx'),
                    'priority' => 10,
                    'classes'  => 'radio-card-v2',
                    'is_pro'   => true,
                    'default'  => 'daily',
                    'options'  => array(
                        'daily' => array(
                            'value' => 'daily',
                            'label' => __('Daily', 'notificationx'),
                            // 'icon'  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/schedule/daily.png',
                        ),
                        'weekly' => array(
                            'value' => 'weekly',
                            'label' => __('Weekly', 'notificationx'),
                            // 'icon'  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/schedule/weekly.png',
                        ),
                        'custom' => array(
                            'value' => 'custom',
                            'label' => __('Custom', 'notificationx'),
                            // 'icon'  => NOTIFICATIONX_ADMIN_URL . 'images/extensions/schedule/custom.png',
                        ),
                    ),
                ),
                'daily_from_time' => array(
                    'name'     => 'daily_from_time',
                    'type'     => 'timepicker',
                    'label'    => __('From', 'notificationx'),
                    'priority' => 20,
                    'is_pro'   => true,
                    'format'   => 'h:i A',
                    'rules'    => Rules::is('schedule_type', 'daily'),
                ),
                'daily_to_time' => array(
                    'name'     => 'daily_to_time',
                    'type'     => 'timepicker',
                    'label'    => __('To', 'notificationx'),
                    'priority' => 30,
                    'is_pro'   => true,
                    'format'   => 'h:i A',
                    'rules'    => Rules::is('schedule_type', 'daily'),
                ),
                'weekly_days' => array(
                    'name'     => 'weekly_days',
                    'type'     => 'select',
                    'label'    => __('Select Days', 'notificationx'),
                    'priority' => 40,
                    'is_pro'   => true,
                    'multiple' => true,
                    'options'  => GlobalFields::get_instance()->normalize_fields([
                        'monday'    => __('Monday', 'notificationx'),
                        'tuesday'   => __('Tuesday', 'notificationx'),
                        'wednesday' => __('Wednesday', 'notificationx'),
                        'thursday'  => __('Thursday', 'notificationx'),
                        'friday'    => __('Friday', 'notificationx'),
                        'saturday'  => __('Saturday', 'notificationx'),
                        'sunday'    => __('Sunday', 'notificationx'),
                    ]),
                    'rules'    => Rules::is('schedule_type', 'weekly'),
                ),
                'weekly_from_time' => array(
                    'name'     => 'weekly_from_time',
                    'type'     => 'timepicker',
                    'label'    => __('From', 'notificationx'),
                    'priority' => 50,
                    'is_pro'   => true,
                    'format'   => 'h:i A',
                    'rules'    => Rules::is('schedule_type', 'weekly'),
                ),
                'weekly_to_time' => array(
                    'name'     => 'weekly_to_time',
                    'type'     => 'timepicker',
                    'label'    => __('To', 'notificationx'),
                    'priority' => 60,
                    'format'   => 'h:i A',
                    'is_pro'   => true,
                    'rules'    => Rules::is('schedule_type', 'weekly'),
                ),
                'custom_schedule' => array(
                    'name'     => 'custom_schedule',
                    'type'     => 'daterange',
                    'label'    => __('Custom Schedule', 'notificationx'),
                    'priority' => 65,
                    'is_pro'   => true,
                    'format'   => 'h:i A',
                    'rules'    => Rules::is('schedule_type', 'custom'),
                ),
                'custom_from_time' => array(
                    'name'     => 'custom_from_time',
                    'type'     => 'timepicker',
                    'label'    => __('From', 'notificationx'),
                    'priority' => 70,
                    'is_pro'   => true,
                    'format'   => 'h:i A',
                    'rules'    => Rules::is('schedule_type', 'custom'),
                ),
                'custom_to_time' => array(
                    'name'     => 'custom_to_time',
                    'type'     => 'timepicker',
                    'label'    => __('To', 'notificationx'),
                    'priority' => 75,
                    'is_pro'   => true,
                    'format'   => 'h:i A',
                    'rules'    => Rules::is('schedule_type', 'custom'),
                ),
            ),
            'rules' => Rules::logicalRule([
                Rules::is('source', $this->id),
            ]),
        );

        return $fields;
    }

    /**
     * This method is an implementable method for All Extension coming forward.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    // public function source_fields($fields) {
    //     $conversion_position                      = &$fields["source_tab"]['fields']['source'];
    //     $conversion_position['condition']['type'] = '!press_bar';
    //     return $fields;
    // }

    public function before_delete_post($postid) {
        $post_meta             = get_post_meta($postid, '_nx_bar_elementor_type_id', true);
        $this->nx_elementor_id = [
            'post_meta' => $post_meta,
            'postid'    => $postid,
        ];
    }

    public function nx_delete_post($postid, $post) {
        $elementor_id = isset($post['elementor_id']) ? $post['elementor_id'] : false;
        $gutenberg_id = isset($post['gutenberg_id']) ? $post['gutenberg_id'] : false;

        $this->delete_elementor_post($elementor_id);
        $this->gutenberg_remove($gutenberg_id);
    }

    public function delete_elementor_post($elementor_id) {
        if(!empty($elementor_id)){
            $languages = apply_filters( 'wpml_active_languages', NULL );
            if(is_array($languages)){
                foreach ($languages as $lang => $val) {
                    $elementor_post_id = apply_filters( 'wpml_object_id', $elementor_id, 'nx_bar', false, $lang);
                    if($elementor_post_id){
                        wp_delete_post($elementor_post_id, true);
                    }
                }
                return;
            }
            wp_delete_post($elementor_id, true);
        }
    }

    /**
     * Register Post Type for NotificationX Bar.
     *
     * @return void
     */
    public static function register_post_type() {
        // var_dump(current_action());die;
        $args = [
            'label'               => __('NotificationX Bar', 'notificationx'),
            'public'              => true,
            'show_ui'             => false,
            'rewrite'             => false,
            'menu_icon'           => 'dashicons-admin-page',
            'show_in_menu'        => true,
            'show_in_nav_menus'   => false,
            'exclude_from_search' => true,
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => ['title', 'content', 'author', 'elementor'],
        ];
        register_post_type('nx_bar', $args);

        // $args = [
        //     'label'               => __('NotificationX Bar', 'notificationx'),
        //     'public'              => true,
        //     'show_ui'             => true,
        //     'rewrite'             => false,
        //     'menu_icon'           => 'dashicons-admin-page',
        //     'show_in_menu'        => true,
        //     'show_in_nav_menus'   => false,
        //     // 'show_in_rest'        => false,
        //     'exclude_from_search' => true,
        //     'capability_type'     => 'post',
        //     'hierarchical'        => false,
        //     'supports'            => ['title', 'content', 'author'],
        // ];
        // register_post_type('nx_bar_eb', $args);

        register_post_type(
            'nx_bar_eb',
            array(
                'label'              => __('NotificationX Bar (Gutenberg)', 'notificationx'),
                'show_in_rest'       => true,
                'public'             => true,
                'show_ui'            => true,
                'can_export'         => true,
                'show_in_menu'       => false,
                'show_in_nav_menus'  => false,
                'rewrite'            => false,
                // 'publicly_queryable' => false,
                'template_lock'      => 'block',
                'rest_base'          => 'NotificationX',
                'capability_type'    => 'block',
                'rest_controller_class' => 'WP_REST_Blocks_Controller',
                'capabilities'    => array(
                    // You need to be able to edit posts, in order to read blocks in their raw form.
                    'read'                   => 'edit_posts',
                    // You need to be able to publish posts, in order to create blocks.
                    'create_posts'           => 'publish_posts',
                    'edit_posts'             => 'edit_posts',
                    'edit_published_posts'   => 'edit_published_posts',
                    'delete_published_posts' => 'delete_published_posts',
                    // Enables trashing draft posts as well.
                    'delete_posts'           => 'delete_posts',
                    'edit_others_posts'      => 'edit_others_posts',
                    'delete_others_posts'    => 'delete_others_posts',
                ),
                'map_meta_cap'          => true,
                'supports'              => array(
                    'title',
                    'editor',
                    'revisions',
                    'custom-fields',
                ),
            )
        );
    }

    public function convert_to_php_array($array) {
        $new_array = [];
        foreach ($array as $arr) {
            preg_match('/(.*)[\[](.*)[\]]/', $arr['name'], $matches);
            if (!empty($matches) && is_array($matches)) {
                $new_array[$matches[1]][$matches[2]] = $arr['value'];
            } else {
                $new_array[$arr['name']] = $arr['value'];
            }
        }
        return $new_array;
    }

    /**
     * This methods is responsible for creating nx_bar post
     * to enable elementor to design the bar for you.
     *
     * @return void
     */
    public function create_bar_of_type_bar_with_elementor($params) {
        if (!isset($params['theme_id'])) {
            return;
        }

        $theme     = sanitize_text_field($params['theme_id']);


        $importer = new Importer();

        $ID = $importer->create_nx([
            'theme'      => $theme,
            'post_title' => 'Design for NotificationX Bar - ',
        ]);

        if ($ID && !is_wp_error($ID)) {
            update_post_meta($ID, '_wp_page_template', 'elementor_canvas');
            wp_send_json_success(array(
                'context' => [
                    'themes'               => null,
                    'elementor_id'        => $ID,
                    'elementor_edit_link' => \Elementor\Plugin::$instance->documents->get($ID)->get_edit_url(),
                    // 'bar_edit_link'       => get_edit_post_link($ID, 'internal'),
                    // 'visit'               => get_permalink($ID),
                ]
            ));
        } else {
            wp_send_json_error('failed');
        }
    }


    /**
     * Undocumented function
     *
     * @param array $options
     * @return array
     */
    public function content_fields($fields) {

        $fields['content']['fields']['press_sliding_text'] = array(
            'name'     => 'sliding_text',
            'label'    => __('Text Content', 'notificationx'),
            'type'     => 'section',
            'classes'   => NotificationX::is_pro() ? 'pro-activated' : 'pro-deactivated',
            'priority' => 51,
            'fields'   => array(
                'bar_content_type' => array(
                    'name'     => 'bar_content_type',
                    'type'     => 'radio-card',
                    'label'    => __('Content', 'notificationx'),
                    'priority' => 5,
                    'classes'  => 'radio-card-v2',
                    'default'  => 'static',
                    'options'  => array(
                        'static' => array(
                            'label' => __('Static Text', 'notificationx'),
                            'value' => 'static',
                            // 'icon'  => 'dashicons-align-left',
                        ),
                        'sliding' => array(
                            'label' => __('Slide Multiple Text', 'notificationx'),
                            'value' => 'sliding',
                            'is_pro' => true,
                            // 'icon'  => 'dashicons-align-right',
                        ),
                    ),
                ),
                'press_content' => array(
                    'name'        => 'press_content',
                    'type'        => 'nx-editor',
                    'label'       => __('Static Text', 'notificationx'),
                    'placeholder' => __('Write something here...', 'notificationx'),
                    'priority'    => 7,
                    'rules'       => Rules::logicalRule([
                        Rules::is('bar_content_type', 'static'),
                        Rules::is('source', $this->id),
                    ]),
                ),
                'sliding_content' => array(
                    'name'        => 'sliding_content',
                    'type'        => 'simple-repeater',
                    'label'       => __('Sliding Text Items', 'notificationx'),
                    'priority'    => 10,
                    'button'      => array(
                        'label'    => __('Add New', 'notificationx'),
                    ),
                    '_default'     => array(
                        array(
                            'title' => __('🚀 Supercharge Your Marketing Forever!', 'notificationx'),
                        ),
                        array(
                            'title' => __('Get <strong>Lifetime Access to NotificationX</strong> for just <strong>$299</strong>', 'notificationx'),
                        ),
                    ),
                    '_fields'      => array(
                        array(
                            'name'     => 'title',
                            'type'     => 'editor',
                            'label'    => __('Slide Text', 'notificationx'),
                            'priority' => 10,
                        ),
                    ),
                    'rules'       => Rules::logicalRule([
                        Rules::is('bar_content_type', 'sliding'),
                        Rules::is('source', $this->id),
                    ]),
                ),
                'sliding_interval' => array(
                    'name'        => 'sliding_interval',
                    'type'        => 'number',
                    'label'       => __('Sliding Interval', 'notificationx'),
                    'priority'    => 20,
                    'default'     => 3000,
                    'description' => 'ms',
                    'help'        => __('Time interval between slides in milliseconds', 'notificationx'),
                    'rules'       => Rules::logicalRule([
                        Rules::is('bar_content_type', 'sliding'),
                        Rules::is('source', $this->id),
                    ]),
                ),
            ),
            'rules' => Rules::logicalRule([
                Rules::is('source', $this->id),
            ]),
        );

        $fields['content']['fields']['button_text'] = array(
            'name'     => 'button_text',
            'type'     => 'text',
            'label'    => __('Button Text', 'notificationx'),
            'priority' => 60,
            'default'  => __('Get Offer', 'notificationx'),
            'rules' => Rules::logicalRule([
                // Rules::isOfType('elementor_id', 'number', true),
                Rules::is('source', $this->id),
            ]),
        );
        $fields['content']['fields']['button_url'] = array(
            'name'     => 'button_url',
            'type'     => 'text',
            'label'    => __('Button URL', 'notificationx'),
            'default'  => '#',
            'priority' => 70,
            'rules' => Rules::logicalRule([
                // Rules::isOfType('elementor_id', 'number', true),
                Rules::is('source', $this->id),
            ]),
        );
        $fields['content']['fields']['button_icon'] = array(
            'name'       => 'button_icon',
            'type'       => 'icon-picker',
            'label'      => __('Button Icon', 'notificationx'),
            'priority'   => 75,
            'iconPrefix' => NOTIFICATIONX_ADMIN_URL . 'images/icons/',
            'options'    => [
                [
                    'icon'  => 'shop_now.svg',
                    'label' => __('Shop Now', 'notificationx')
                ],
                [
                    'icon'  => 'shop_now_white.svg',
                    'label' => __('Shop Now White ', 'notificationx')
                ],
            ],
            'description' => __('Select an icon to display before the button text', 'notificationx'),
            'rules' => Rules::logicalRule([
                // Rules::isOfType('elementor_id', 'number', true),
                Rules::is('source', $this->id),
            ]),
        );
        $fields['content']['fields']['bar_transition_speed'] = [
            'label'       => __('Transition Speed', 'notificationx'),
            'name'        => "bar_transition_speed",
            'type'        => "number",
            'default'     => '500',
            'description' => 'ms',
            'priority'    => 100,
            'help'        => __('Transition speed in milliseconds', 'notificationx'),
            'rules'       => Rules::logicalRule([
                Rules::is('bar_content_type', 'sliding'),
                Rules::is('source', $this->id),
            ]),
        ];
        $fields['content']['fields']['bar_transition_style'] = [
            'label'    => __('Transition Style', 'notificationx'),
            'name'     => "bar_transition_style",
            'type'     => "select",
            'priority' => 110,
            'default'  => 'slide_right',
            'options'  => GlobalFields::get_instance()->normalize_fields([
                'slide_right' => __('Slide in Right', 'notificationx'),
                'slide_left'  => __('Slide in Left', 'notificationx'),
            ]),
            'rules'       => Rules::logicalRule([
                Rules::is('bar_content_type', 'sliding'),
                Rules::is('source', $this->id),
            ]),
        ];

        $fields['bar_coupon'] = array(
            'name'     => 'bar_coupon',
            'label'    => __('Coupon', 'notificationx'),
            'type'     => 'section',
            'priority' => 94,
            'dependency_class'  => [
                'name'     => 'enable_coupon',
                'is'       => true,
                'classes'  => 'coupon_active',
                'selector' => '#bar_coupon',
            ],
            'fields'   => array(
                'enable_coupon' => array(
                    'name'    => 'enable_coupon',
                    'label'   => __('Enable Coupon', 'notificationx'),
                    'type'    => 'toggle',
                    'default' => false,
                ),
                'coupon_text' => array(
                    'name'     => 'coupon_text',
                    'label'    => __('Button Text', 'notificationx'),
                    'type'     => 'text',
                    'default'  => __('SAVE20', 'notificationx'),
                    'priority' => 10,
                    'rules' => Rules::logicalRule([
                        Rules::is('enable_coupon', true),
                    ]),
                ),
                'coupon_code' => array(
                    'name'     => 'coupon_code',
                    'label'    => __('Coupon Code', 'notificationx'),
                    'type'     => 'text',
                    'default'  => __('SAVE20', 'notificationx'),
                    'priority' => 12,
                    'rules' => Rules::logicalRule([
                        Rules::is('enable_coupon', true),
                    ]),
                ),
                'coupon_copied_text' => array(
                    'name'     => 'coupon_copied_text',
                    'label'    => __('Coupon Copied Text', 'notificationx'),
                    'type'     => 'text',
                    'default'  => __('Copied!', 'notificationx'),
                    'priority' => 20,
                    'rules' => Rules::logicalRule([
                        Rules::is('enable_coupon', true),
                    ]),
                ),
                'coupon_tooltip' => array(
                    'name'     => 'coupon_tooltip',
                    'label'    => __('Coupon Tooltip', 'notificationx'),
                    'type'     => 'textarea',
                    'default'  => __('Use this coupon code to get 20% off', 'notificationx'),
                    'priority' => 30,
                    'rules'    => Rules::logicalRule([
                        Rules::is('enable_coupon', true),
                    ]),
                ),
                'coupon_bg_color' => array(
                    'name'     => 'coupon_bg_color',
                    'label'    => __('Coupon Background Color', 'notificationx'),
                    'type'     => 'colorpicker',
                    'default'  => '#ffffff',
                    'priority' => 40,
                    'rules'    => Rules::logicalRule([
                        Rules::is('enable_coupon', true),
                    ]),
                ),
                'coupon_text_color' => array(
                    'name'     => 'coupon_text_color',
                    'label'    => __('Coupon Text Color', 'notificationx'),
                    'type'     => 'colorpicker',
                    'default'  => '#000000',
                    'priority' => 50,
                    'rules'    => Rules::logicalRule([
                        Rules::is('enable_coupon', true),
                    ]),
                ),
                'coupon_border_color' => array(
                    'name'     => 'coupon_border_color',
                    'label'    => __('Coupon Border Color', 'notificationx'),
                    'type'     => 'colorpicker',
                    'default'  => '#dddddd',
                    'priority' => 60,
                    'rules'    => Rules::logicalRule([
                        Rules::is('enable_coupon', true),
                    ]),
                ),
            ),
            'rules' => Rules::logicalRule([
                Rules::is('source', $this->id),
            ]),
        );

        $fields['countdown_timer'] = array(
            'name'     => 'countdown_timer',
            'label'    => __('Countdown Timer', 'notificationx'),
            'type'     => 'section',
            'priority' => 95,
            'fields'   => array(
                'enable_countdown'       => array(
                    'name'  => 'enable_countdown',
                    'label' => __('Enable Countdown', 'notificationx'),
                    'type'  => 'checkbox',
                    'default' => true,
                ),
                'evergreen_timer'        => array(
                    'name'        => 'evergreen_timer',
                    'label'       => __('Evergreen Timer', 'notificationx'),
                    'type'        => 'checkbox',
                    'is_pro'      => true,
                    'switch'      => true,
                    'description' => sprintf('%s, <a target="_blank" href="%s">%s</a>', __('To configure Evergreen Timer', 'notificationx'), 'https://notificationx.com/docs/evergreen-timer/', 'check out this doc'),
                    'rules'       => ['is', 'enable_countdown', true],
                ),
                'countdown_text'         => array(
                    'name'  => 'countdown_text',
                    'label' => __('Countdown Text', 'notificationx'),
                    'type'  => 'text',
                    'default' => __('Ending in', 'notificationx'),
                    'rules'  => Rules::logicalRule([
                        // Rules::is('elementor_id', false),
                        // Rules::is('gutenberg_id', false),
                        Rules::is('enable_countdown', true),
                    ]),
                ),
                'countdown_expired_text' => array(
                    'name'    => 'countdown_expired_text',
                    'label'   => __('Expired Text', 'notificationx'),
                    'type'    => 'text',
                    'default' => __('Expired', 'notificationx'),
                    'rules'  => Rules::logicalRule([
                        Rules::is('elementor_id', false),
                        Rules::is('gutenberg_id', false),
                        Rules::is('evergreen_timer', false),
                        Rules::is('enable_countdown', true),
                    ]),
                ),
                'countdown_start_date'   => array(
                    'name'  => 'countdown_start_date',
                    'label' => __('Start Date', 'notificationx'),
                    'type'  => 'date',
                    // 'default' => date('Y-m-d H:i:s', time()),
                    'rules' => ["and", ['is', 'evergreen_timer', false], ['is', 'enable_countdown', true]],
                ),
                'countdown_end_date'     => array(
                    'name'  => 'countdown_end_date',
                    'label' => __('End Date', 'notificationx'),
                    'type'  => 'date',
                    // @todo Something
                    'default' => date('Y-m-d H:i:s', time() + 7 * 24 * 60 * 60),
                    'rules' => ["and", ['is', 'evergreen_timer', false], ['is', 'enable_countdown', true]],
                ),
                'time_randomize'         => array(
                    'name'  => 'time_randomize',
                    'label' => __('Randomize', 'notificationx'),
                    'type'  => 'checkbox',
                    'rules' => ["and", ['is', 'evergreen_timer', true], ['is', 'enable_countdown', true]],
                ),
                'time_randomize_between' => array(
                    'name'     => 'time_randomize_between',
                    'label'    => __('Time Between', 'notificationx'),
                    'type'     => 'group',
                    'fields'   => [
                        'start_time' => array(
                            'name'     => 'start_time',
                            'type'     => 'number',
                            'label'    => __('Start Time', 'notificationx'),
                            'priority' => 0,
                            'default'  => 6,
                        ),
                        'end_time'   => array(
                            'name'     => 'end_time',
                            'type'     => 'number',
                            'label'    => __('End Time', 'notificationx'),
                            'priority' => 1,
                            'default'  => 12,
                        ),
                    ],
                    'rules' => ["and", ['is', 'evergreen_timer', true], ['is', 'enable_countdown', true], ['is', 'time_randomize', true]],
                ),
                'time_rotation'          => array(
                    'name'        => 'time_rotation',
                    'label'       => __('Time Rotation', 'notificationx'),
                    'type'        => 'number',
                    'description' => 'hours',
                    'default'     => 315,
                    'rules'       => ["and", ['is', 'evergreen_timer', true], ['is', 'enable_countdown', true], ['is', 'time_randomize', false]]
                ),
                'time_reset'             => array(
                    'name'  => 'time_reset',
                    'label' => __('Daily Time Reset', 'notificationx'),
                    'type'  => 'checkbox',
                    'rules' => ["and", ['is', 'evergreen_timer', true], ['is', 'enable_countdown', true]],
                ),
                'close_forever'          => array(
                    'name'  => 'close_forever',
                    'label' => __('Permanent Close', 'notificationx'),
                    'type'  => 'checkbox',
                ),
                'close_after_expire'          => array(
                    'name'  => 'close_after_expire',
                    'label' => __('Close After Expire', 'notificationx'),
                    'type'  => 'checkbox',
                ),
            ),
            'rules' => Rules::logicalRule([
                Rules::is('source', $this->id),
            ]),
        );

        $fields["content"]['fields']['template_adv']      = Rules::is('source', $this->id, true, $fields["content"]['fields']['template_adv']);
        $fields["content"]['fields']['advanced_template'] = Rules::is('source', $this->id, true, $fields["content"]['fields']['advanced_template']);
        $fields["content"]                                = Rules::logicalRule([
            Rules::logicalRule([
                Rules::isOfType('elementor_id', 'number', true),
                Rules::isOfType('gutenberg_id', 'number', true),
            ], 'and'),
            Rules::is('source', $this->id, true),
        ], 'or', $fields["content"]);

        $fields['content']['fields']['random_order'] = Rules::is('source', $this->id, true, $fields["content"]['fields']['random_order']);

        return $fields;
    }

    public function print_bar_notice($settings, $is_shortcode = false) {
        $settings = new GetData($settings, \ArrayObject::ARRAY_AS_PROPS);
        $elementor_post_id = isset($settings->elementor_id) ? $settings->elementor_id : '';
        $gb_post_id = isset($settings->gutenberg_id) ? $settings->gutenberg_id : '';

        if ($elementor_post_id != '' && get_post_status($elementor_post_id) === 'publish' && class_exists('\Elementor\Plugin')) {
            $elementor_post_id = apply_filters( 'wpml_object_id', $elementor_post_id, 'nx_bar', true);
            return \Elementor\Plugin::$instance->frontend->get_builder_content_for_display($elementor_post_id, false);
        } else if (!empty($gb_post_id)) {
            $gb_post_id = apply_filters( 'wpml_object_id', $gb_post_id, 'wp_block', true);
            $post       = get_post($gb_post_id);
            $content    = $post->post_content;
            $content    = do_blocks($content);

            return $content;
        } else {
            return !empty($settings->press_content) ? do_shortcode($settings->press_content) : '';
        }
    }

    public function add_scripts($settings, $params){
        if(!empty($settings['gutenberg_id'])){
            $settings['gutenberg_url'] = get_permalink($settings['gutenberg_id']);
        }
        return $settings;
    }

    public function hide_image_field($fields) {
        $fields['image-section'] = Rules::is('source', $this->id, true, $fields['image-section']);
        return $fields;
    }

    public function nx_get_post($post) {
        if (isset($post['source']) && $post['source'] == $this->id && !empty($post['elementor_id']) && class_exists('\Elementor\Plugin')) {
            try {
                $document = \Elementor\Plugin::$instance->documents->get($post['elementor_id']);
                if(!empty($document)){
                    $post['elementor_edit_link'] = $document->get_edit_url();
                    $bar_post = $document->get_post();
                    if(!empty($bar_post->post_title)){
                        foreach ($this->bar_themes as $key => $theme) {
                            if(strpos($bar_post->post_title, $theme['title']) !== false){
                                $post['elementor_bar_theme'] = $theme['value'];
                                unset($post['theme']);
                                unset($post['themes']);
                                break;
                            }
                        }
                    }
                }
                else{
                    unset($post['elementor_id']);
                }
            } finally {
            }
        }
        return $post;
    }

    public function gutenberg_import($params) {
        // Get the JSON file content
        $json_file = file_get_contents(__DIR__ . "/jsons-gb/" . $params['theme_id'] . '.json');

        // Decode the JSON content into an array
        $pattern_data = json_decode($json_file, true);

        // Get the sync status from the pattern data
        $sync_status = $pattern_data['syncStatus'];

        // Create an array of arguments for the wp_block post
        $post_args = array(
            'post_title'   => $pattern_data['title'],      // use the pattern title as the post title
            'post_content' => $pattern_data['content'],   // use the pattern content as the post content
            'post_status'  => 'publish',                  // set the post status to publish
            'post_type'    => 'nx_bar_eb',              // set the post type to wp_block
        );

        // Insert the wp_block post
        $post_id = wp_insert_post($post_args);


        // Check for errors
        if ($post_id && !is_wp_error($post_id)) {
            // Display the error message
            if (!empty($sync_status)) {
                update_post_meta($post_id, 'wp_pattern_sync_status', $sync_status);
            }
            // Display a success message
            return [
                'success' => true,
                'data' => [
                    'context' => [
                        'themes'              => null,
                        'gutenberg_id'        => $post_id,
                        'gutenberg_edit_link' => get_edit_post_link($post_id, 'link'),
                    ],
                ],
            ];
        } else {
            return [
                'success' => true,
                'data'    => 'failed',
            ];
        }
    }

    public function gutenberg_remove($pid){
        if(!empty($pid)){
            $languages = apply_filters( 'wpml_active_languages', NULL );
            if(is_array($languages)){
                foreach ($languages as $lang => $val) {
                    $wpml_pid = apply_filters( 'wpml_object_id', $pid, 'wp_block', false, $lang);
                    if($wpml_pid){
                        wp_delete_post($wpml_pid, true);
                    }
                }
                return;
            }
            wp_delete_post($pid, true);
        }
    }

    public function templately_cloud_push_post_type($post_type){

        if($post_type == 'nx_bar_eb'){
            $post_type = 'NX Bar';
        }
        return $post_type;
    }

    public function preview_settings($settings){
        if(!empty($settings['gutenberg_id'])){
            $settings['gutenberg_url'] = get_permalink($settings['gutenberg_id']);
        }
        return $settings;
    }

    /**
     *
     * https://docs.wp-rocket.me/article/1529-remove-unused-css
     *
     * @param array $list
     * @return array
     */
    public function rocket_rucss_safelist($list){
        try {
            $posts = PostType::get_instance()->get_posts(['source' => $this->id]);
            foreach ($posts as $post) {
                if(class_exists('Elementor\Core\Files\CSS\Post') && !empty($post['elementor_id'])){
                    $css = Post_CSS::create( $post['elementor_id'] );
                    if(!empty($css)){
                        // maybe need to remove the domain from url
                        $list[] = $css->get_url();
                    }
                }
            }
        } catch (\Exception $th) {
            //throw $th;
        }
        return $list;
    }

    public function doc() {
        return sprintf(__('<p>You can showcase the notification bar to run instant popup campaigns on WordPress sites. For further assistance, check out our step-by-step guides on adding notification bars built with both <a target="_blank" href="%1$s">Elementor</a> and <a target="_blank" href="%2$s">Gutenberg</a>.</p>
		<p>🎦 Watch the <a target = "_blank" href = "%3$s">video tutorial</a> for a quick guide.</p>
		<p><strong>Recommended Blog                     : </strong></p>
		<p>🔥 How to <a target="_blank" href="%4$s">design a Notification Bar with Elementor Page Builder.</a></p>
		<p>🔥 <a href="%5$s" target="_blank">Evergreen Dynamic Notification Bar</a> to Boost Sales in WordPress.</p>', 'notificationx'),
        'https://notificationx.com/docs/notification-bar/',
        'https://notificationx.com/docs/configure-a-notification-bar-in-gutenberg/',
        'https://www.youtube.com/watch?v=l7s9FXgzbEM',
        'https://notificationx.com/docs/notification-bar-with-elementor/',
        'https://notificationx.com/blog/dynamic-notification-bar-wordpress/'
        );
    }
}
