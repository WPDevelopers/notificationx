<?php

/**
 * Popup Notification
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Popup;

use NotificationX\Admin\InfoTooltipManager;
use NotificationX\GetInstance;
use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;
use NotificationX\Extensions\Extension;

/**
 * Popup Extension
 * @method static Popup get_instance($args = null)
 */
class PopupNotification extends Extension {
    /**
     * Instance of Popup
     *
     * @var Popup
     */
    use GetInstance;

    public $priority        = 15;
    public $id              = 'popup_notification';
    public $doc_link        = 'https://notificationx.com/docs/how-to-show-announcement-using-notificationx/';
    public $types           = 'popup';
    public $module          = 'modules_popup';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
    }

    public function init() {
        parent::init();
    }

    public function init_extension()
    {
        $this->title = __('Announcement', 'notificationx');
        $this->module_title = __('Announcement', 'notificationx');
        $this->themes = [
            'theme-one' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-one.png',
                'defaults' => [
                    'popup_title'             => __('Want to build credibility & boost sales?', 'notificationx'),
                    'popup_content'           => __('We help you optimize conversions & drive sales', 'notificationx'),
                    'popup_button_text'       => __('Get Started with Free Plan', 'notificationx'),
                    'position'                => 'center',
                ],
                'column'  => "5",
            ],
            'theme-two' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-two.png',
                'defaults' => [
                    'popup_title'             => __('Boost your sales using SureCart', 'notificationx'),
                    'popup_content'           => __('<iframe width="560" height="315" src="https://www.youtube.com/embed/dw176Jmk74M?si=3suUqkCkQuYQrh2G" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>', 'notificationx'),
                    'popup_button_text'       => __('See How', 'notificationx'),
                    'position'                => 'center',
                ],
                'column'  => "5",
            ],
            'theme-three' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-three.png',
                'defaults' => [
                    'popup_title'                    => __('All Offers', 'notificationx'),
                    'popup_button_text'              => __('Latest Offers', 'notificationx'),
                    'popup_button_icon'              => 'latest_offer.svg',
                    'position'                       => 'center',
                ],
                'column'  => "5",
            ],
            'theme-four' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-four.webp',
                'defaults' => [
                    'popup_title'             => __('Need Help?', 'notificationx'),
                    'popup_content'           => __('Get the latest news and updates delivered to your inbox.', 'notificationx'),
                    'popup_button_text'       => __('Submit', 'notificationx'),
                    'position'                => 'center',
                ],
                'column'  => "5",
            ],
            'theme-five' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-five.webp',
                'defaults' => [
                    'popup_title'             => __('Need Help?', 'notificationx'),
                    'popup_content'           => __('Subscribe to receive exclusive offers and updates directly in your inbox.', 'notificationx'),
                    'popup_email_placeholder' => __('Enter your email address', 'notificationx'),
                    'popup_button_text'       => __('Submit', 'notificationx'),
                    'position'                => 'center',
                ],
                'is_pro' => true,
                'column'  => "5",
            ],
            'theme-six' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-six.webp',
                'defaults' => [
                    'popup_title'             => __('Get latest news & updates', 'notificationx'),
                    'popup_email_placeholder' => __('Your email address', 'notificationx'),
                    'popup_button_text'       => __('Submit Now', 'notificationx'),
                    'position'                => 'center',
                ],
                'is_pro' => true,
                'column'  => "5",
            ],
            'theme-seven' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-seven.webp',
                'defaults' => [
                    'popup_title'               => __('Want latest updates?', 'notificationx'),
                    'popup_subtitle'            => __('Would like to get the lastes news & updates instantly?', 'notificationx'),
                    'popup_email_placeholder'   => __('Enter email address', 'notificationx'),
                    'popup_button_text'         => __('Get In Touch', 'notificationx'),
                    'popup_icon'                => 'mail_icon.svg',
                    'popup_button_icon'         => 'mail_icon.svg',
                    'position'                  => 'center',
                ],
                'is_pro' => true,
                'column'  => "5",
            ],
        ];
    }

     public function init_fields() {
        parent::init_fields();
        add_filter('nx_design_tab_fields', [$this, 'design_fields'], 99);
        add_filter('nx_content_fields', [$this, 'content_fields'], 999);
        add_filter('nx_customize_fields', [$this, 'customize_fields'], 999);
        add_filter('nx_display_fields', [$this, 'display_fields'], 999);
    }

    public function display_fields($fields) {
        // Hide image selection 
        if (isset($fields['image-section'])) {
            $fields['image-section'] = Rules::is('source', $this->id, true, $fields['image-section']);
        }
        return $fields;
    }

    public function design_fields( $fields ) {
        if (isset($fields['advance_design_section']['fields']['design'])) {
            $fields['advance_design_section']['fields']['design'] = Rules::is('source', $this->id, true, $fields['advance_design_section']['fields']['design']);
        }
        if (isset($fields['advance_design_section']['fields']['typography'])) {
            $fields['advance_design_section']['fields']['typography'] = Rules::is('source', $this->id, true, $fields['advance_design_section']['fields']['typography']);
        }
        if (isset($fields['advance_design_section']['fields']['image-appearance'])) {
            $fields['advance_design_section']['fields']['image-appearance'] = Rules::is('source', $this->id, true, $fields['advance_design_section']['fields']['image-appearance']);
        }
        if (isset($fields['advance_design_section']['fields']['link_button_design'])) {
            $fields['advance_design_section']['fields']['link_button_design'] = Rules::is('source', $this->id, true, $fields['advance_design_section']['fields']['link_button_design']);
        }

        // Popup Container Design Section
        $fields['advance_design_section']['fields']['popup_design'] = [
            'label'    => __("Popup Design", 'notificationx'),
            'name'     => "popup_design",
            'type'     => "section",
            'priority' => 5,
            'rules'    => Rules::logicalRule([
                Rules::is('source', $this->id, false),
                Rules::is('advance_edit', true),
            ]),
            'fields' => [
                [
                    'label' => __("Background Color", 'notificationx'),
                    'name'  => "popup_bg_color",
                    'type'  => "colorpicker",
                ],
                [
                    'label' => __("Overlay Background Color", 'notificationx'),
                    'name'  => "overlay_color",
                    'type'  => "colorpicker",
                    'help'  => __('Background color of the overlay behind the popup', 'notificationx'),
                ],
                [
                    'label'       => __('Popup Width', 'notificationx'),
                    'name'        => "popup_width",
                    'type'        => "number",
                    'description' => 'px',
                    'help'        => __('Maximum width of the popup container', 'notificationx'),
                ],
                [
                    'label'       => __('Border Radius', 'notificationx'),
                    'name'        => "popup_border_radius",
                    'type'        => "number",
                    'description' => 'px',
                    'help'        => __('Rounded corners for the popup container', 'notificationx'),
                ],
                [
                    'label'       => __('Popup Padding', 'notificationx'),
                    'name'        => "popup_padding",
                    'type'        => "text",
                    'help'        => __('Internal spacing of popup content (e.g., 30px or 20px 30px)', 'notificationx'),
                ],
                [
                    'label' => __("Close Button Color", 'notificationx'),
                    'name'  => "close_btn_color",
                    'type'  => "colorpicker",
                ],
                [
                    'label'       => __('Close Button Size', 'notificationx'),
                    'name'        => "close_btn_size",
                    'type'        => "number",
                    'description' => 'px',
                ],
            ]
        ];

        // Typography Section
        $fields['advance_design_section']['fields']['popup_typography'] = [
            'label'    => __("Typography", 'notificationx'),
            'name'     => "popup_typography",
            'type'     => "section",
            'priority' => 6,
            'rules'    => Rules::logicalRule([
                Rules::is('source', $this->id, false),
                Rules::is('advance_edit', true),
            ]),
            'fields' => [
                [
                    'label'   => __("Title Color", 'notificationx'),
                    'name'    => "popup_title_color",
                    'type'    => "colorpicker",
                ],
                [
                    'label'       => __('Title Font Size', 'notificationx'),
                    'name'        => "popup_title_font_size",
                    'type'        => "number",
                    'description' => 'px',
                    'help'        => __('Font size for the popup title', 'notificationx'),
                ],
                [
                    'label'       => __('Title Font Weight', 'notificationx'),
                    'name'        => "popup_title_font_weight",
                    'type'        => "select",
                    'options'     => GlobalFields::get_instance()->normalize_fields([
                        '300' => __('Light (300)', 'notificationx'),
                        '400' => __('Normal (400)', 'notificationx'),
                        '500' => __('Medium (500)', 'notificationx'),
                        '600' => __('Semi Bold (600)', 'notificationx'),
                        '700' => __('Bold (700)', 'notificationx'),
                        '800' => __('Extra Bold (800)', 'notificationx'),
                    ]),
                ],
                [
                    'label' => __("Subtitle Color", 'notificationx'),
                    'name'  => "popup_subtitle_color",
                    'type'  => "colorpicker",
                    'rules' => Rules::is('themes', 'popup_notification_theme-seven'),
                ],
                [
                    'label'       => __('Subtitle Font Size', 'notificationx'),
                    'name'        => "popup_subtitle_font_size",
                    'type'        => "number",
                    'description' => 'px',
                    'help'        => __('Font size for the popup subtitle', 'notificationx'),
                    'rules'       => Rules::is('themes', 'popup_notification_theme-seven'),
                ],
                [
                    'label' => __("Content/Message Color", 'notificationx'),
                    'name'  => "popup_content_color",
                    'type'  => "colorpicker",
                    'rules' => Rules::logicalRule([
                        Rules::is('themes', 'popup_notification_theme-one'),
                        Rules::is('themes', 'popup_notification_theme-two'),
                        Rules::is('themes', 'popup_notification_theme-three'),
                        Rules::is('themes', 'popup_notification_theme-four'),
                        Rules::is('themes', 'popup_notification_theme-five'),
                    ], 'or'),
                ],
                [
                    'label'       => __('Content/Message Font Size', 'notificationx'),
                    'name'        => "popup_content_font_size",
                    'type'        => "number",
                    'description' => 'px',
                    'help'        => __('Font size for the popup content/message text', 'notificationx'),
                    'rules'       => Rules::logicalRule([
                        Rules::is('themes', 'popup_notification_theme-one'),
                        Rules::is('themes', 'popup_notification_theme-two'),
                        Rules::is('themes', 'popup_notification_theme-three'),
                        Rules::is('themes', 'popup_notification_theme-four'),
                        Rules::is('themes', 'popup_notification_theme-five'),
                    ], 'or'),
                ],
            ]
        ];

        // Button Design Section
        $fields['advance_design_section']['fields']['popup_button_design'] = [
            'label'    => __("Button Design", 'notificationx'),
            'name'     => "popup_button_design",
            'type'     => "section",
            'priority' => 7,
            'rules'    => Rules::logicalRule([
                Rules::is('source', $this->id, false),
                Rules::is('advance_edit', true),
            ]),
            'fields' => [
                [
                    'label' => __("Background Color", 'notificationx'),
                    'name'  => "popup_button_bg_color",
                    'type'  => "colorpicker",
                ],
                [
                    'label' => __("Hover Background Color", 'notificationx'),
                    'name'  => "popup_button_hover_bg_color",
                    'type'  => "colorpicker",
                    'help'  => __('Background color when button is hovered', 'notificationx'),
                ],
                [
                    'label' => __("Text Color", 'notificationx'),
                    'name'  => "popup_button_text_color",
                    'type'  => "colorpicker",
                ],
                [
                    'label' => __("Hover Text Color", 'notificationx'),
                    'name'  => "popup_button_hover_text_color",
                    'type'  => "colorpicker",
                    'help'  => __('Text color when button is hovered', 'notificationx'),
                ],
                [
                    'label' => __("Border Color", 'notificationx'),
                    'name'  => "popup_button_border_color",
                    'type'  => "colorpicker",
                ],
                [
                    'label' => __("Hover Border Color", 'notificationx'),
                    'name'  => "popup_button_border_hover_color",
                    'type'  => "colorpicker",
                ],
                [
                    'label'       => __('Border Width', 'notificationx'),
                    'name'        => "popup_button_border_width",
                    'type'        => "number",
                    'description' => 'px',
                    'help'        => __('Width of the button border', 'notificationx'),
                ],
                [
                    'label'       => __('Font Size', 'notificationx'),
                    'name'        => "popup_button_font_size",
                    'type'        => "number",
                    'description' => 'px',
                ],
                [
                    'label'       => __('Font Weight', 'notificationx'),
                    'name'        => "popup_button_font_weight",
                    'type'        => "select",
                    'options'     => GlobalFields::get_instance()->normalize_fields([
                        '300' => __('Light (300)', 'notificationx'),
                        '400' => __('Normal (400)', 'notificationx'),
                        '500' => __('Medium (500)', 'notificationx'),
                        '600' => __('Semi Bold (600)', 'notificationx'),
                        '700' => __('Bold (700)', 'notificationx'),
                    ]),
                ],
                [
                    'label'       => __('Border Radius', 'notificationx'),
                    'name'        => "popup_button_border_radius",
                    'type'        => "number",
                    'description' => 'px',
                    'help'        => __('Rounded corners for the button', 'notificationx'),
                ],
                [
                    'label'       => __('Padding', 'notificationx'),
                    'name'        => "popup_button_padding",
                    'type'        => "text",
                    'help'        => __('Button spacing in CSS format (e.g., 12px 24px)', 'notificationx'),
                ],
                [
                    'label'       => __('Button Width', 'notificationx'),
                    'name'        => "popup_button_width",
                    'type'        => "select",
                    'default'     => 'auto',
                    'options'     => GlobalFields::get_instance()->normalize_fields([
                        'auto' => __('Auto Width', 'notificationx'),
                        '100%' => __('Full Width', 'notificationx'),
                        'custom' => __('Custom Width', 'notificationx'),
                    ]),
                ],
                [
                    'label'       => __('Custom Button Width', 'notificationx'),
                    'name'        => "popup_button_custom_width",
                    'type'        => "number",
                    'description' => 'px',
                    'rules'       => Rules::is('popup_button_width', 'custom'),
                ],
            ]
        ];

        // Email Input Design Section (for email collection themes)
        $fields['advance_design_section']['fields']['popup_email_design'] = [
            'label'    => __("Email Input Design", 'notificationx'),
            'name'     => "popup_email_design",
            'type'     => "section",
            'priority' => 8,
            'rules'    => Rules::logicalRule([
                Rules::is('source', $this->id, false),
                Rules::is('advance_edit', true),
                Rules::logicalRule([
                    Rules::is('themes', 'popup_notification_theme-five'),
                    Rules::is('themes', 'popup_notification_theme-six'),
                    Rules::is('themes', 'popup_notification_theme-seven'),
                ], 'or'),
            ]),
            'fields' => [
                [
                    'label' => __("Input Background Color", 'notificationx'),
                    'name'  => "popup_email_bg_color",
                    'type'  => "colorpicker",
                ],
                [
                    'label' => __("Input Text Color", 'notificationx'),
                    'name'  => "popup_email_text_color",
                    'type'  => "colorpicker",
                ],
                [
                    'label' => __("Input Border Color", 'notificationx'),
                    'name'  => "popup_email_border_color",
                    'type'  => "colorpicker",
                ],
                [
                    'label' => __("Input Focus Border Color", 'notificationx'),
                    'name'  => "popup_email_focus_border_color",
                    'type'  => "colorpicker",
                    'help'  => __('Border color when input is focused', 'notificationx'),
                ],
                [
                    'label' => __("Placeholder Text Color", 'notificationx'),
                    'name'  => "popup_email_placeholder_color",
                    'type'  => "colorpicker",
                ],
                [
                    'label'       => __('Input Font Size', 'notificationx'),
                    'name'        => "popup_email_font_size",
                    'type'        => "number",
                    'description' => 'px',
                ],
                [
                    'label'       => __('Input Border Width', 'notificationx'),
                    'name'        => "popup_email_border_width",
                    'type'        => "number",
                    'description' => 'px',
                ],
                [
                    'label'       => __('Input Border Radius', 'notificationx'),
                    'name'        => "popup_email_border_radius",
                    'type'        => "number",
                    'description' => 'px',
                ],
                [
                    'label'       => __('Input Padding', 'notificationx'),
                    'name'        => "popup_email_padding",
                    'type'        => "text",
                    'help'        => __('Input field spacing in CSS format (e.g., 12px 16px)', 'notificationx'),
                ],
                [
                    'label'       => __('Input Height', 'notificationx'),
                    'name'        => "popup_email_height",
                    'type'        => "number",
                    'description' => 'px',
                    'help'        => __('Height of the email input field', 'notificationx'),
                ],
            ]
        ];

        // Repeater Items Design Section (for theme-three only)
        $fields['advance_design_section']['fields']['popup_repeater_design'] = [
            'label'    => __("Content Items Design", 'notificationx'),
            'name'     => "popup_repeater_design",
            'type'     => "section",
            'priority' => 9,
            'rules'    => Rules::logicalRule([
                Rules::is('source', $this->id, false),
                Rules::is('advance_edit', true),
                Rules::is('themes', 'popup_notification_theme-three'),
            ]),
            'fields' => [
                [
                    'label' => __("Item Background Color", 'notificationx'),
                    'name'  => "popup_repeater_item_bg_color",
                    'type'  => "colorpicker",
                    'help'  => __('Background color for each content item', 'notificationx'),
                ],
                [
                    'label' => __("Highlight Text Color", 'notificationx'),
                    'name'  => "popup_repeater_highlight_color",
                    'type'  => "colorpicker",
                    'help'  => __('Color for the highlight text (e.g., "30% OFF")', 'notificationx'),
                ],
                [
                    'label' => __("Item Title Color", 'notificationx'),
                    'name'  => "popup_repeater_title_color",
                    'type'  => "colorpicker",
                ],
                [
                    'label'       => __('Item Title Font Size', 'notificationx'),
                    'name'        => "popup_repeater_title_font_size",
                    'type'        => "number",
                    'description' => 'px',
                ],
                [
                    'label'       => __('Item Title Font Weight', 'notificationx'),
                    'name'        => "popup_repeater_title_font_weight",
                    'type'        => "select",
                    'options'     => GlobalFields::get_instance()->normalize_fields([
                        '400' => __('Normal (400)', 'notificationx'),
                        '500' => __('Medium (500)', 'notificationx'),
                        '600' => __('Semi Bold (600)', 'notificationx'),
                        '700' => __('Bold (700)', 'notificationx'),
                    ]),
                ],
                [
                    'label' => __("Item Subtitle Color", 'notificationx'),
                    'name'  => "popup_repeater_subtitle_color",
                    'type'  => "colorpicker",
                ],
                [
                    'label'       => __('Item Subtitle Font Size', 'notificationx'),
                    'name'        => "popup_repeater_subtitle_font_size",
                    'type'        => "number",
                    'description' => 'px',
                ],
                [
                    'label'       => __('Item Border Radius', 'notificationx'),
                    'name'        => "popup_repeater_item_border_radius",
                    'type'        => "number",
                    'description' => 'px',
                    'help'        => __('Rounded corners for each content item', 'notificationx'),
                ],
                [
                    'label'       => __('Item Padding', 'notificationx'),
                    'name'        => "popup_repeater_item_padding",
                    'type'        => "text",
                    'help'        => __('Internal spacing for each content item (e.g., 16px)', 'notificationx'),
                ],
                [
                    'label'       => __('Item Spacing', 'notificationx'),
                    'name'        => "popup_repeater_item_spacing",
                    'type'        => "number",
                    'description' => 'px',
                    'help'        => __('Space between content items', 'notificationx'),
                ],
            ]
        ];

        return $fields;
    }

    public function content_fields( $fields ) {
        if (isset($fields['utm_options'])) {
            $fields['utm_options'] = Rules::is('source', $this->id, true, $fields['utm_options']);
        }
        if (isset($fields['content'])) {
            $fields['content'] = Rules::is('source', $this->id, true, $fields['content']);
        }

        $fields['popup_content'] = [
            'label'    => __('Popup Content', 'notificationx'),
            'name'     => 'popup_content',
            'type'     => 'section',
            'priority' => 5,
            'rules'    => Rules::is('source', $this->id),
            'fields'   => [
                [
                    'label'    => __('Title', 'notificationx'),
                    'name'     => 'popup_title',
                    'type'     => 'text',
                    'priority' => 10,
                    'default'  => __('Special Offer!', 'notificationx'),
                ],
                [
                    'label'    => __('Subtitle', 'notificationx'),
                    'name'     => 'popup_subtitle',
                    'type'     => 'text',
                    'priority' => 12,
                    'default'  => __('Would like to get the latest news & updates instantly?', 'notificationx'),
                    'rules'    => Rules::is('themes', 'popup_notification_theme-seven'),
                ],
                [
                    'label'       => __('Popup Icon', 'notificationx'),
                    'name'        => 'popup_icon',
                    'type'        => 'icon-picker',
                    'priority'    => 15,
                    'iconPrefix'  => NOTIFICATIONX_ADMIN_URL . 'images/icons/',
                    'default'     => 'mail_icon.svg',
                    'rules'       => Rules::is('themes', 'popup_notification_theme-seven'),
                    'options'     => [
                        [
                            'icon'  => 'mail_icon.svg',
                            'label' => __('Mail Icon', 'notificationx')
                        ],
                    ],
                ],
                [
                    'label'    => __('Content', 'notificationx'),
                    'name'     => 'popup_content',
                    'type'     => 'textarea',
                    'priority' => 20,
                    'default'  => __('Don\'t miss out on this amazing deal!', 'notificationx'),
                    'rules'    => Rules::logicalRule([
                        Rules::is('themes', 'popup_notification_theme-one'),
                        Rules::is('themes', 'popup_notification_theme-two'),
                        Rules::is('themes', 'popup_notification_theme-seven'),
                    ], 'or'),
                ],
                // Form Field Toggles - only for form submission themes (4-7)
                [
                    'label'    => __('Show Name Field', 'notificationx'),
                    'name'     => 'popup_show_name_field',
                    'type'     => 'toggle',
                    'priority' => 25,
                    'default'  => false,
                    'rules'    => Rules::logicalRule([
                        Rules::is('themes', 'popup_notification_theme-four'),
                        Rules::is('themes', 'popup_notification_theme-five'),
                        Rules::is('themes', 'popup_notification_theme-six'),
                        Rules::is('themes', 'popup_notification_theme-seven'),
                    ], 'or'),
                    'is_pro'   => true,
                ],
                [
                    'label'    => __('Name Field Text', 'notificationx'),
                    'name'     => 'popup_name_placeholder',
                    'type'     => 'text',
                    'priority' => 30,
                    'default'  => __('Enter your name', 'notificationx'),
                    'rules'    => Rules::logicalRule([
                        Rules::logicalRule([
                            Rules::is('themes', 'popup_notification_theme-four'),
                            Rules::is('themes', 'popup_notification_theme-five'),
                            Rules::is('themes', 'popup_notification_theme-six'),
                            Rules::is('themes', 'popup_notification_theme-seven'),
                        ], 'or'),
                        Rules::is('popup_show_name_field', true),
                    ]),
                ],
                [
                    'label'    => __('Show Email Field', 'notificationx'),
                    'name'     => 'popup_show_email_field',
                    'type'     => 'toggle',
                    'priority' => 35,
                    'info'     => InfoTooltipManager::get_instance()->render('popup_notification_email_field'),
                    'rules'    => Rules::logicalRule([
                        Rules::is('themes', 'popup_notification_theme-four'),
                        Rules::is('themes', 'popup_notification_theme-five'),
                        Rules::is('themes', 'popup_notification_theme-six'),
                        Rules::is('themes', 'popup_notification_theme-seven'),
                    ], 'or'),
                    'is_pro'   => true,
                ],
                 [
                    'label'    => __('Email Field Text', 'notificationx'),
                    'name'     => 'popup_email_placeholder',
                    'type'     => 'text',
                    'priority' => 40,
                    'default'  => __('Enter your email', 'notificationx'),
                    'rules'    => Rules::logicalRule([
                        Rules::logicalRule([
                            Rules::is('themes', 'popup_notification_theme-four'),
                            Rules::is('themes', 'popup_notification_theme-five'),
                            Rules::is('themes', 'popup_notification_theme-six'),
                            Rules::is('themes', 'popup_notification_theme-seven'),
                        ], 'or'),
                        Rules::is('popup_show_email_field', true),
                    ]),
                ],
                [
                    'label'    => __('Show Message Field', 'notificationx'),
                    'name'     => 'popup_show_message_field',
                    'type'     => 'toggle',
                    'priority' => 45,
                    'info'     => InfoTooltipManager::get_instance()->render('popup_notification_message_field'),
                    'rules'    => Rules::logicalRule([
                        Rules::is('themes', 'popup_notification_theme-four'),
                        Rules::is('themes', 'popup_notification_theme-five'),
                        Rules::is('themes', 'popup_notification_theme-six'),
                        Rules::is('themes', 'popup_notification_theme-seven'),
                    ], 'or'),
                    'default'  => true,
                ],
                [
                    'label'    => __('Message Field Text', 'notificationx'),
                    'name'     => 'popup_message_placeholder',
                    'type'     => 'text',
                    'priority' => 50,
                    'default'  => __('Enter your message...', 'notificationx'),
                    'rules'    => Rules::logicalRule([
                        Rules::logicalRule([
                            Rules::is('themes', 'popup_notification_theme-four'),
                            Rules::is('themes', 'popup_notification_theme-five'),
                            Rules::is('themes', 'popup_notification_theme-six'),
                            Rules::is('themes', 'popup_notification_theme-seven'),
                        ], 'or'),
                        Rules::is('popup_show_message_field', true),
                    ]),
                ],

                // Common Button Text field for all themes
                [
                    'label'    => __('Button Text', 'notificationx'),
                    'name'     => 'popup_button_text',
                    'type'     => 'text',
                    'priority' => 55,
                    'default'  => __('Get Offer', 'notificationx'),
                ],
                // Button URL field - only for themes 1-3 (promotional themes with external links)
                [
                    'label'    => __('Button URL', 'notificationx'),
                    'name'     => 'popup_button_url',
                    'type'     => 'text',
                    'priority' => 60,
                    'default'  => '#',
                    'rules'    => Rules::logicalRule([
                        Rules::is('themes', 'popup_notification_theme-one'),
                        Rules::is('themes', 'popup_notification_theme-two'),
                        Rules::is('themes', 'popup_notification_theme-three'),
                    ], 'or'),
                ],

                // Button Icon field - only for theme-three and theme-seven
                [
                    'label'       => __('Button Icon', 'notificationx'),
                    'name'        => 'popup_button_icon',
                    'type'        => 'icon-picker',
                    'priority'    => 62,
                    'iconPrefix'  => NOTIFICATIONX_ADMIN_URL . 'images/icons/',
                    'default'     => 'mail_icon.svg',
                    'options'     => [
                        [
                            'icon'  => 'mail_icon.svg',
                            'label' => __('Mail Icon', 'notificationx')
                        ],
                        [
                            'icon'  => 'latest_offer.svg',
                            'label' => __('Latest OFfer', 'notificationx')
                        ],
                        [
                            'icon'  => 'shop_now.svg',
                            'label' => __('Shop Now', 'notificationx')
                        ],
                        [
                            'icon'  => 'shop_now_white.svg',
                            'label' => __('Shop Now White', 'notificationx')
                        ],
                    ],
                    'description' => __('Select an icon to display with the button text', 'notificationx'),
                    'rules'       => Rules::logicalRule([
                        Rules::is('themes', 'popup_notification_theme-three'),
                        Rules::is('themes', 'popup_notification_theme-seven'),
                    ], 'or'),
                ],
                'open_in_new_tab' => [
                    'label'   => __('Open URL in New Tab', 'notificationx'),
                    'name'    => 'open_in_new_tab',
                    'type'    => 'toggle',
                    'default' => false,
                    'rules'   => Rules::logicalRule([
                        Rules::is('themes', 'popup_notification_theme-one'),
                        Rules::is('themes', 'popup_notification_theme-two'),
                        Rules::is('themes', 'popup_notification_theme-three'),
                    ], 'or'),
                ],
                // Repeater fields - only for theme-three
                [
                    'label'    => __('Content Items', 'notificationx'),
                    'name'     => 'popup_content_repeater',
                    'type'     => 'repeater',
                    'priority' => 65,
                    'rules'    => Rules::is('themes', 'popup_notification_theme-three'),
                     'button'  => [
                        'label' => __('Add New', 'notificationx')
                    ],
                    'fields'   => [
                        [
                            'label' => __('Highlight Text', 'notificationx'),
                            'name'  => 'repeater_highlight_text',
                            'type'  => 'text',
                            'default' => __('30% OFF', 'notificationx'),
                            'help'  => __('Text that will be highlighted in a different color (e.g., "30% OFF")', 'notificationx'),
                        ],
                        [
                            'label' => __('Title', 'notificationx'),
                            'name'  => 'repeater_title',
                            'type'  => 'text',
                            'default' => __('Feature Title', 'notificationx'),
                        ],
                        [
                            'label' => __('Subtitle', 'notificationx'),
                            'name'  => 'repeater_subtitle',
                            'type'  => 'text',
                            'default' => __('Feature description', 'notificationx'),
                        ],
                    ],
                    'default' => [
                        [
                            'repeater_highlight_text' => __('30% OFF', 'notificationx'),
                            'repeater_title'          => __('Boost Sales', 'notificationx'),
                            'repeater_subtitle'       => __('Increase conversions with social proof', 'notificationx'),
                        ],
                        [
                            'repeater_highlight_text' => __('50% OFF', 'notificationx'),
                            'repeater_title'          => __('Build Trust', 'notificationx'),
                            'repeater_subtitle'       => __('Show real customer activity', 'notificationx'),
                        ],
                        [
                            'repeater_highlight_text' => __('LIMITED', 'notificationx'),
                            'repeater_title'          => __('Drive Action', 'notificationx'),
                            'repeater_subtitle'       => __('Create urgency and FOMO', 'notificationx'),
                        ],
                    ],
                ],
            ]
        ];

        return $fields;
    }

    /**
     * Generate entry key
     *
     * @param string $key
     * @return string
    */
    public function key($key = '') {
        return $this->id . '_' . $key;
    }


     /**
     * Handle popup form submission
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_popup_submission($request) {
        $params = $request->get_params();

        // Prepare entry data
        $data = [
            'title' => $params['title'] ?: __('Popup Submission', 'notificationx'),
            'timestamp' => $params['timestamp'] ?: time(),
        ];

        // Add email if provided
        if (!empty($params['email'])) {
            $data['email'] = $params['email'];
        }

        // Add message if provided
        if (!empty($params['message'])) {
            $data['message'] = $params['message'];
        }

        if (!empty($params['name'])) {
            $data['name'] = $params['name'];
        }

        // Add theme information
        if (!empty($params['theme'])) {
            $data['theme'] = $params['theme'];
        }

        // Add IP address
        $data['ip'] = $this->get_user_ip();

        // Save entry using the standard NotificationX pattern
        $entry_key = $this->key($params['nx_id']);
        $entry = [
            'nx_id' => $params['nx_id'],
            'source' => $this->id,
            'entry_key' => $entry_key,
            'data' => $data,
        ];

        // Save the entry
        $this->update_notification($entry);

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Submission saved successfully', 'notificationx'),
        ], 200);
    }


    /**
     * Get user IP address
     *
     * @return string
     */
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '';
        }
    }


     /**
     * This method is an implementable method for All Extension coming forward.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function customize_fields($fields) {
        $fields["timing"]['fields']['delay_between'] = Rules::is('source', $this->id, true, $fields["timing"]['fields']['delay_between']);
        $fields["timing"]['fields']['display_for'] = Rules::is('source', $this->id, true, $fields["timing"]['fields']['display_for']);
        
        if (isset($fields['behaviour'])) {
            $fields['behaviour'] = Rules::is('source', $this->id, true, $fields['behaviour']);
        }
        if (isset($fields['sound_section'])) {
            $fields['sound_section'] = Rules::is('source', $this->id, true, $fields['sound_section']);
        }
        if (isset($fields['queue_management'])) {
			$fields['queue_management'] = Rules::is('source', $this->id, true, $fields['queue_management']);
		}

        $_fields             = &$fields["appearance"]['fields'];
        $conversion_position = &$_fields['position']['options'];

        $conversion_position['center'] = [
            'label' => __('Center', 'notificationx'),
            'value' => 'center',
            'rules' => Rules::is('source', $this->id),
        ];
        // Popup specific settings
        $fields['popup_settings'] = [
            'label'    => __('Popup Settings', 'notificationx'),
            'name'     => 'popup_settings',
            'type'     => 'section',
            'priority' => 15,
            'rules'    => Rules::is('source', $this->id),
            'fields'   => [
                'show_close_button' => [
                    'label'   => __('Show Close Button', 'notificationx'),
                    'name'    => 'show_close_button',
                    'type'    => 'toggle',
                    'default' => true,
                ],
                'close_on_button_click' => [
                    'label'   => __('Close on Button Click', 'notificationx'),
                    'name'    => 'close_on_button_click',
                    'type'    => 'toggle',
                    'default' => true,
                ],
                'close_button_position' => [
                    'label'   => __('Close Button Position', 'notificationx'),
                    'name'    => 'close_button_position',
                    'type'    => 'select',
                    'default' => 'top-right',
                    'options' => GlobalFields::get_instance()->normalize_fields([
                        'top-right'    => __('Top Right', 'notificationx'),
                        'top-left'     => __('Top Left', 'notificationx'),
                        'bottom-right' => __('Bottom Right', 'notificationx'),
                        'bottom-left'  => __('Bottom Left', 'notificationx'),
                    ]),
                    'rules' => Rules::is('show_close_button', true),
                ],
            ]
        ];

        return $fields;
    }

    public function doc(){
        return sprintf(__('<p>Create compelling Announcements that capture visitor interest and help you generate more leads on your WordPress site. Need help? Check out our <a href="%1$s" target="_blank">step-by-step guides</a> to build impactful Announcements.</p>
        <p>🎦 Watch the quick video <a target="_blank" href="%2$s">tutorial</a> for an easy walk-through.</p>
        <p><strong>Recommended Blogs:</strong></p>
        <p>🔥 <a target="_blank" href="%3$s">How to Create High-Performing Announcements with NotificationX?</a></p>
        <p><strong>Pro Tips:</strong></p>
        <p>✨ Use attention-grabbing headlines and clear action prompts to encourage more sign-ups.</p>', 'notificationx'),
        'https://notificationx.com/docs/how-to-show-announcement-using-notificationx/',
        'https://www.youtube.com/playlist?list=PLWHp1xKHCfxAj4AAs3kmzmDZKvjv6eycK',
        'https://notificationx.com/blog/announcement-notifications-on-wordpress'
    );
    }

}
