<?php

/**
 * Popup Notification
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Popup;

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
    public $doc_link        = 'https://notificationx.com/docs/google-reviews-with-notificationx/';
    public $types           = 'popup';
    public $module          = 'modules_popup';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
    }

    public function init_extension()
    {
        $this->title = __('Popup', 'notificationx');
        $this->module_title = __('Popup', 'notificationx');
        $this->themes = [
            'theme-one' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-one.png',
                'column' => "12",
                'defaults' => [
                    'popup_title'             => __('Want to build credibility & boost sales?', 'notificationx'),
                    'popup_content'           => __('We help you optimize conversions & drive sales', 'notificationx'),
                    'popup_button_text'       => __('Get Started with Free Plan', 'notificationx'),
                    'popup_bg_color'          => '#ffffff',
                    'popup_title_color'       => '#ffffff',
                    'popup_desc_color'        => '#333333',
                    'popup_button_bg_color'   => '#ff6b35',
                    'popup_button_text_color' => '#ffffff',
                    'overlay_color'           => 'rgba(0, 0, 0, 0.5)',
                ],
            ],
            'theme-two' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-two.png',
                'column' => "12",
                'defaults' => [
                    'popup_title'             => __('Boost your sales using SureCart', 'notificationx'),
                    'popup_content'           => __('Display SureCart Sales Alerts using NotificationX', 'notificationx'),
                    'popup_button_text'       => __('See How', 'notificationx'),
                    'popup_bg_color'          => '#ffffff',
                    'popup_title_color'       => '#333333',
                    'popup_desc_color'        => '#666666',
                    'popup_button_bg_color'   => '#8b5cf6',
                    'popup_button_text_color' => '#ffffff',
                    'overlay_color'           => 'rgba(0, 0, 0, 0.5)',
                ],
            ],
            'theme-three' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-three.png',
                'column' => "12",
                'defaults' => [
                    'popup_title'             => __('All Offers', 'notificationx'),
                    'popup_button_text'       => __('Latest Offers', 'notificationx'),
                    'popup_bg_color'          => '#fef7ed',
                    'popup_title_color'       => '#333333',
                    'popup_desc_color'        => '#666666',
                    'popup_button_bg_color'   => '#d97706',
                    'popup_button_text_color' => '#ffffff',
                    'overlay_color'           => 'rgba(0, 0, 0, 0.5)',
                ],
            ],
            'theme-four' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-four.webp',
                'column' => "12",
                'defaults' => [
                    'popup_title'             => __('Need Help?', 'notificationx'),
                    'popup_content'           => __('Get the latest news and updates delivered to your inbox.', 'notificationx'),
                    'popup_button_text'       => __('Submit', 'notificationx'),
                    'popup_bg_color'          => '#ffffff',
                    'popup_title_color'       => '#333333',
                    'popup_desc_color'        => '#666666',
                    'popup_button_bg_color'   => '#007cba',
                    'popup_button_text_color' => '#ffffff',
                    'overlay_color'           => 'rgba(0, 0, 0, 0.5)',
                ],
            ],
            'theme-five' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-five.webp',
                'column' => "12",
                'defaults' => [
                    'popup_title'             => __('Need Help?', 'notificationx'),
                    'popup_content'           => __('Subscribe to receive exclusive offers and updates directly in your inbox.', 'notificationx'),
                    'popup_email_placeholder' => __('Enter your email address', 'notificationx'),
                    'popup_button_text'       => __('Submit', 'notificationx'),
                    'popup_bg_color'          => '#f8fafc',
                    'popup_title_color'       => '#1e293b',
                    'popup_desc_color'        => '#64748b',
                    'popup_button_bg_color'   => '#3b82f6',
                    'popup_button_text_color' => '#ffffff',
                    'overlay_color'           => 'rgba(0, 0, 0, 0.5)',
                ],
            ],
            'theme-six' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-six.webp',
                'column' => "12",
                'defaults' => [
                    'popup_title'             => __('Get latest news & updates', 'notificationx'),
                    'popup_email_placeholder' => __('Your email address', 'notificationx'),
                    'popup_button_text'       => __('Subscribe', 'notificationx'),
                    'popup_bg_color'          => '#1f2937',
                    'popup_title_color'       => '#ffffff',
                    'popup_desc_color'        => '#d1d5db',
                    'popup_button_bg_color'   => '#10b981',
                    'popup_button_text_color' => '#ffffff',
                    'overlay_color'           => 'rgba(0, 0, 0, 0.7)',
                ],
            ],
            'theme-seven' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-seven.webp',
                'column' => "12",
                'defaults' => [
                    'popup_title'             => __('Want latest updates?', 'notificationx'),
                    'popup_subtitle'          => __('Would like to get the lastes news & updates instantly?', 'notificationx'),
                    'popup_email_placeholder' => __('Enter email address', 'notificationx'),
                    'popup_button_text'       => __('Subscribe', 'notificationx'),
                    'popup_bg_color'          => '#fef3c7',
                    'popup_title_color'       => '#92400e',
                    'popup_desc_color'        => '#b6ac9fff',
                    'popup_button_bg_color'   => '#f59e0b',
                    'popup_button_text_color' => '#ffffff',
                    'overlay_color'           => 'rgba(0, 0, 0, 0.5)',
                ],
            ],
        ];
    }

     public function init_fields() {
        parent::init_fields();
        add_filter('nx_design_tab_fields', [$this, 'design_fields'], 99);
        add_filter('nx_content_fields', [$this, 'content_fields'], 999);
        add_filter('nx_customize_fields', [$this, 'customize_fields'], 999);
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
                    'default'  => "#ffffff",
                ],
                [
                    'label' => __("Overlay Background Color", 'notificationx'),
                    'name'  => "overlay_color",
                    'type'  => "colorpicker",
                    'default'  => "rgba(0, 0, 0, 0.5)",
                    'help'  => __('Background color of the overlay behind the popup', 'notificationx'),
                ],
                [
                    'label'       => __('Popup Width', 'notificationx'),
                    'name'        => "popup_width",
                    'type'        => "number",
                    'default'     => '500',
                    'description' => 'px',
                    'help'        => __('Maximum width of the popup container', 'notificationx'),
                ],
                [
                    'label'       => __('Border Radius', 'notificationx'),
                    'name'        => "popup_border_radius",
                    'type'        => "number",
                    'default'     => '8',
                    'description' => 'px',
                    'help'        => __('Rounded corners for the popup container', 'notificationx'),
                ],
                [
                    'label'       => __('Popup Padding', 'notificationx'),
                    'name'        => "popup_padding",
                    'type'        => "text",
                    'default'     => '30px',
                    'help'        => __('Internal spacing of popup content (e.g., 30px or 20px 30px)', 'notificationx'),
                ],
                [
                    'label' => __("Close Button Color", 'notificationx'),
                    'name'  => "close_btn_color",
                    'type'  => "colorpicker",
                    'default'  => "#999999",
                ],
                [
                    'label'       => __('Close Button Size', 'notificationx'),
                    'name'        => "close_btn_size",
                    'type'        => "number",
                    'default'     => '20',
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
                    'label' => __("Title Color", 'notificationx'),
                    'name'  => "popup_title_color",
                    'type'  => "colorpicker",
                    'default'  => "#333333",
                ],
                [
                    'label'       => __('Title Font Size', 'notificationx'),
                    'name'        => "popup_title_font_size",
                    'type'        => "number",
                    'default'     => '24',
                    'description' => 'px',
                    'help'        => __('Font size for the popup title', 'notificationx'),
                ],
                [
                    'label'       => __('Title Font Weight', 'notificationx'),
                    'name'        => "popup_title_font_weight",
                    'type'        => "select",
                    'default'     => '600',
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
                    'default'  => "#666666",
                    'rules' => Rules::is('themes', 'popup_notification_theme-seven'),
                ],
                [
                    'label'       => __('Subtitle Font Size', 'notificationx'),
                    'name'        => "popup_subtitle_font_size",
                    'type'        => "number",
                    'default'     => '18',
                    'description' => 'px',
                    'help'        => __('Font size for the popup subtitle', 'notificationx'),
                    'rules'       => Rules::is('themes', 'popup_notification_theme-seven'),
                ],
                [
                    'label' => __("Content/Message Color", 'notificationx'),
                    'name'  => "popup_content_color",
                    'type'  => "colorpicker",
                    'default'  => "#666666",
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
                    'default'     => '16',
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
                    'default'  => "#007cba",
                ],
                [
                    'label' => __("Hover Background Color", 'notificationx'),
                    'name'  => "popup_button_hover_bg_color",
                    'type'  => "colorpicker",
                    'default'  => "#005a87",
                    'help'  => __('Background color when button is hovered', 'notificationx'),
                ],
                [
                    'label' => __("Text Color", 'notificationx'),
                    'name'  => "popup_button_text_color",
                    'type'  => "colorpicker",
                    'default'  => "#ffffff",
                ],
                [
                    'label' => __("Hover Text Color", 'notificationx'),
                    'name'  => "popup_button_hover_text_color",
                    'type'  => "colorpicker",
                    'default'  => "#ffffff",
                    'help'  => __('Text color when button is hovered', 'notificationx'),
                ],
                [
                    'label' => __("Border Color", 'notificationx'),
                    'name'  => "popup_button_border_color",
                    'type'  => "colorpicker",
                    'default'  => "#007cba",
                ],
                [
                    'label'       => __('Border Width', 'notificationx'),
                    'name'        => "popup_button_border_width",
                    'type'        => "number",
                    'default'     => '1',
                    'description' => 'px',
                    'help'        => __('Width of the button border', 'notificationx'),
                ],
                [
                    'label'       => __('Font Size', 'notificationx'),
                    'name'        => "popup_button_font_size",
                    'type'        => "number",
                    'default'     => '16',
                    'description' => 'px',
                ],
                [
                    'label'       => __('Font Weight', 'notificationx'),
                    'name'        => "popup_button_font_weight",
                    'type'        => "select",
                    'default'     => '500',
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
                    'default'     => '4',
                    'description' => 'px',
                    'help'        => __('Rounded corners for the button', 'notificationx'),
                ],
                [
                    'label'       => __('Padding', 'notificationx'),
                    'name'        => "popup_button_padding",
                    'type'        => "text",
                    'default'     => '12px 24px',
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
                    'default'     => '200',
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
                    'default'  => "#ffffff",
                ],
                [
                    'label' => __("Input Text Color", 'notificationx'),
                    'name'  => "popup_email_text_color",
                    'type'  => "colorpicker",
                    'default'  => "#333333",
                ],
                [
                    'label' => __("Input Border Color", 'notificationx'),
                    'name'  => "popup_email_border_color",
                    'type'  => "colorpicker",
                    'default'  => "#dddddd",
                ],
                [
                    'label' => __("Input Focus Border Color", 'notificationx'),
                    'name'  => "popup_email_focus_border_color",
                    'type'  => "colorpicker",
                    'default'  => "#007cba",
                    'help'  => __('Border color when input is focused', 'notificationx'),
                ],
                [
                    'label' => __("Placeholder Text Color", 'notificationx'),
                    'name'  => "popup_email_placeholder_color",
                    'type'  => "colorpicker",
                    'default'  => "#999999",
                ],
                [
                    'label'       => __('Input Font Size', 'notificationx'),
                    'name'        => "popup_email_font_size",
                    'type'        => "number",
                    'default'     => '16',
                    'description' => 'px',
                ],
                [
                    'label'       => __('Input Border Width', 'notificationx'),
                    'name'        => "popup_email_border_width",
                    'type'        => "number",
                    'default'     => '1',
                    'description' => 'px',
                ],
                [
                    'label'       => __('Input Border Radius', 'notificationx'),
                    'name'        => "popup_email_border_radius",
                    'type'        => "number",
                    'default'     => '4',
                    'description' => 'px',
                ],
                [
                    'label'       => __('Input Padding', 'notificationx'),
                    'name'        => "popup_email_padding",
                    'type'        => "text",
                    'default'     => '12px 16px',
                    'help'        => __('Input field spacing in CSS format (e.g., 12px 16px)', 'notificationx'),
                ],
                [
                    'label'       => __('Input Height', 'notificationx'),
                    'name'        => "popup_email_height",
                    'type'        => "number",
                    'default'     => '48',
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
                    'default'  => "#f8f9fa",
                    'help'  => __('Background color for each content item', 'notificationx'),
                ],
                [
                    'label' => __("Item Title Color", 'notificationx'),
                    'name'  => "popup_repeater_title_color",
                    'type'  => "colorpicker",
                    'default'  => "#333333",
                ],
                [
                    'label'       => __('Item Title Font Size', 'notificationx'),
                    'name'        => "popup_repeater_title_font_size",
                    'type'        => "number",
                    'default'     => '18',
                    'description' => 'px',
                ],
                [
                    'label'       => __('Item Title Font Weight', 'notificationx'),
                    'name'        => "popup_repeater_title_font_weight",
                    'type'        => "select",
                    'default'     => '600',
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
                    'default'  => "#666666",
                ],
                [
                    'label'       => __('Item Subtitle Font Size', 'notificationx'),
                    'name'        => "popup_repeater_subtitle_font_size",
                    'type'        => "number",
                    'default'     => '14',
                    'description' => 'px',
                ],
                [
                    'label'       => __('Item Border Radius', 'notificationx'),
                    'name'        => "popup_repeater_item_border_radius",
                    'type'        => "number",
                    'default'     => '6',
                    'description' => 'px',
                    'help'        => __('Rounded corners for each content item', 'notificationx'),
                ],
                [
                    'label'       => __('Item Padding', 'notificationx'),
                    'name'        => "popup_repeater_item_padding",
                    'type'        => "text",
                    'default'     => '16px',
                    'help'        => __('Internal spacing for each content item (e.g., 16px)', 'notificationx'),
                ],
                [
                    'label'       => __('Item Spacing', 'notificationx'),
                    'name'        => "popup_repeater_item_spacing",
                    'type'        => "number",
                    'default'     => '12',
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
                [
                    'label'    => __('Message Placeholder', 'notificationx'),
                    'name'     => 'popup_message',
                    'type'     => 'textarea',
                    'priority' => 20,
                    'default'  => __('Jot down your quaries & submit to get instant response', 'notificationx'),
                    'rules'    => Rules::logicalRule([
                        Rules::is('themes', 'popup_notification_theme-four'),
                        Rules::is('themes', 'popup_notification_theme-five'),
                    ], 'or'),
                ],

                // Email placeholder field - only for themes 5, 6, 7 (email collection themes)
                [
                    'label'    => __('Email Address Placeholder', 'notificationx'),
                    'name'     => 'popup_email_placeholder',
                    'type'     => 'text',
                    'priority' => 25,
                    'default'  => __('Enter your email', 'notificationx'),
                    'rules'    => Rules::logicalRule([
                        Rules::is('themes', 'popup_notification_theme-five'),
                        Rules::is('themes', 'popup_notification_theme-six'),
                        Rules::is('themes', 'popup_notification_theme-seven'),
                    ], 'or'),
                ],

                // Common Button Text field for all themes
                [
                    'label'    => __('Button Text', 'notificationx'),
                    'name'     => 'popup_button_text',
                    'type'     => 'text',
                    'priority' => 30,
                    'default'  => __('Get Offer', 'notificationx'),
                ],

                // Button URL field - only for themes 1-3 (promotional themes with external links)
                [
                    'label'    => __('Button URL', 'notificationx'),
                    'name'     => 'popup_button_url',
                    'type'     => 'text',
                    'priority' => 40,
                    'default'  => '#',
                    'rules'    => Rules::logicalRule([
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
                    'priority' => 50,
                    'rules'    => Rules::is('themes', 'popup_notification_theme-three'),
                    'fields'   => [
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
                            'repeater_title' => __('Boost Sales', 'notificationx'),
                            'repeater_subtitle' => __('Increase conversions with social proof', 'notificationx'),
                        ],
                        [
                            'repeater_title' => __('Build Trust', 'notificationx'),
                            'repeater_subtitle' => __('Show real customer activity', 'notificationx'),
                        ],
                        [
                            'repeater_title' => __('Drive Action', 'notificationx'),
                            'repeater_subtitle' => __('Create urgency and FOMO', 'notificationx'),
                        ],
                    ],
                ],
            ]
        ];

        return $fields;
    }

     /**
     * This method is an implementable method for All Extension coming forward.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function customize_fields($fields) {
        if (isset($fields['behaviour'])) {
            $fields['behaviour'] = Rules::is('source', $this->id, true, $fields['behaviour']);
        }
        if (isset($fields['sound_section'])) {
            $fields['sound_section'] = Rules::is('source', $this->id, true, $fields['sound_section']);
        }

        $_fields             = &$fields["appearance"]['fields'];
        $conversion_position = &$_fields['position']['options'];
        $conversion_position['center']  = Rules::is('source', $this->id, true, $conversion_position['center']);

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
                'close_on_overlay_click' => [
                    'label'   => __('Close on Overlay Click', 'notificationx'),
                    'name'    => 'close_on_overlay_click',
                    'type'    => 'toggle',
                    'default' => true,
                ],
                'close_on_button_click' => [
                    'label'   => __('Close on Button Click', 'notificationx'),
                    'name'    => 'close_on_button_click',
                    'type'    => 'toggle',
                    'default' => true,
                ],
                'show_close_button' => [
                    'label'   => __('Show Close Button', 'notificationx'),
                    'name'    => 'show_close_button',
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
                'open_in_new_tab' => [
                    'label'   => __('Open URL in New Tab', 'notificationx'),
                    'name'    => 'open_in_new_tab',
                    'type'    => 'toggle',
                    'default' => false,
                ],
            ]
        ];

        return $fields;
    }

    public function doc(){
        return sprintf(__('<p>Create engaging popup notifications to capture visitor attention and boost conversions on your WordPress site. Need help? Follow our <a href="%1$s" target="_blank">step-by-step guides</a> for creating effective popup notifications.</p>
        <p>🎦 Watch the video <a target="_blank" href="%2$s">tutorial</a> for a quick guide.</p>
        <p><strong>Recommended Blogs:</strong></p>
        <p>🔥 <a target="_blank" href="%3$s">How to Create Effective Popup Notifications with NotificationX?</a></p>
        <p><strong>Pro Tips:</strong></p>
        <p>✨ Use compelling headlines and clear call-to-action buttons for better conversion rates.</p>', 'notificationx'),
        'https://notificationx.com/docs/popup-notifications/',
        'https://youtu.be/popup-tutorial',
        'https://notificationx.com/blog/popup-notifications/'
    );
    }

}
