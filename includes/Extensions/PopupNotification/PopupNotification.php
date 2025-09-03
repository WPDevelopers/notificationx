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
            'popup_theme-one' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-one.png',
                'column' => "12",
                'defaults' => [
                    'popup_title' => __('Limited-Time Discount Offer', 'notificationx'),
                    'popup_content' => __('<strong>30% OFF</strong> on all products!<br>Use code: <strong>SALE30</strong>', 'notificationx'),
                    'popup_button_text' => __('Get Offer', 'notificationx'),
                    'popup_bg_color' => '#ffffff',
                    'popup_title_color' => '#ffffff',
                    'popup_desc_color' => '#333333',
                    'popup_button_bg_color' => '#ff6b35',
                    'popup_button_text_color' => '#ffffff',
                    'overlay_color' => 'rgba(0, 0, 0, 0.5)',
                ],
            ],
            'popup_theme-two' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-two.png',
                'column' => "12",
                'defaults' => [
                    'popup_title' => __('Boost your sales using SureCart', 'notificationx'),
                    'popup_content' => __('Display SureCart Sales Alerts using NotificationX', 'notificationx'),
                    'popup_button_text' => __('See How', 'notificationx'),
                    'popup_bg_color' => '#ffffff',
                    'popup_title_color' => '#333333',
                    'popup_desc_color' => '#666666',
                    'popup_button_bg_color' => '#8b5cf6',
                    'popup_button_text_color' => '#ffffff',
                    'overlay_color' => 'rgba(0, 0, 0, 0.5)',
                ],
            ],
            'popup_theme-three' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-three.png',
                'column' => "12",
                'defaults' => [
                    'popup_title' => __('Want to build credibility & boost sales?', 'notificationx'),
                    'popup_content' => __('We help you optimize conversions & drive sales', 'notificationx'),
                    'popup_button_text' => __('Get Started with Free Plan', 'notificationx'),
                    'popup_bg_color' => '#fef7ed',
                    'popup_title_color' => '#333333',
                    'popup_desc_color' => '#666666',
                    'popup_button_bg_color' => '#d97706',
                    'popup_button_text_color' => '#ffffff',
                    'overlay_color' => 'rgba(0, 0, 0, 0.5)',
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
                    'label' => __("Overlay Color", 'notificationx'),
                    'name'  => "overlay_color",
                    'type'  => "colorpicker",
                    'default'  => "rgba(0, 0, 0, 0.5)",
                ],
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
                    'help'        => __('This font size will be applied for <mark>Title</mark> only', 'notificationx'),
                ],
                [
                    'label' => __("Description Color", 'notificationx'),
                    'name'  => "popup_desc_color",
                    'type'  => "colorpicker",
                    'default'  => "#666666",
                ],
                [
                    'label'       => __('Description Font Size', 'notificationx'),
                    'name'        => "popup_desc_font_size",
                    'type'        => "number",
                    'default'     => '16',
                    'description' => 'px',
                    'help'        => __('This font size will be applied for <mark>Description</mark> only', 'notificationx'),
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
                [
                    'label'       => __('Popup Width', 'notificationx'),
                    'name'        => "popup_width",
                    'type'        => "number",
                    'default'     => '500',
                    'description' => 'px',
                    'help'        => __('Maximum width of the popup', 'notificationx'),
                ],
                [
                    'label'       => __('Border Radius', 'notificationx'),
                    'name'        => "popup_border_radius",
                    'type'        => "number",
                    'default'     => '8',
                    'description' => 'px',
                ],
            ]
        ];

        $fields['advance_design_section']['fields']['popup_button_design'] = [
            'label'    => __("Button Design", 'notificationx'),
            'name'     => "popup_button_design",
            'type'     => "section",
            'priority' => 6,
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
                    'label' => __("Text Color", 'notificationx'),
                    'name'  => "popup_button_text_color",
                    'type'  => "colorpicker",
                    'default'  => "#ffffff",
                ],
                [
                    'label' => __("Border Color", 'notificationx'),
                    'name'  => "popup_button_border_color",
                    'type'  => "colorpicker",
                    'default'  => "#007cba",
                ],
                [
                    'label'       => __('Font Size', 'notificationx'),
                    'name'        => "popup_button_font_size",
                    'type'        => "number",
                    'default'     => '16',
                    'description' => 'px',
                ],
                [
                    'label'       => __('Border Radius', 'notificationx'),
                    'name'        => "popup_button_border_radius",
                    'type'        => "number",
                    'default'     => '4',
                    'description' => 'px',
                ],
                [
                    'label'       => __('Padding', 'notificationx'),
                    'name'        => "popup_button_padding",
                    'type'        => "text",
                    'default'     => '12px 24px',
                    'help'        => __('CSS padding format (e.g., 12px 24px)', 'notificationx'),
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
                    'label'    => __('Popup Title', 'notificationx'),
                    'name'     => 'popup_title',
                    'type'     => 'text',
                    'priority' => 10,
                    'default'  => __('Special Offer!', 'notificationx'),
                ],
                [
                    'label'    => __('Popup Content', 'notificationx'),
                    'name'     => 'popup_content',
                    'type'     => 'textarea',
                    'priority' => 20,
                    'default'  => __('Don\'t miss out on this amazing deal!', 'notificationx'),
                    'help'     => __('You can use HTML tags for formatting', 'notificationx'),
                ],
                [
                    'label'    => __('Button Text', 'notificationx'),
                    'name'     => 'popup_button_text',
                    'type'     => 'text',
                    'priority' => 30,
                    'default'  => __('Get Offer', 'notificationx'),
                ],
                [
                    'label'    => __('Button URL', 'notificationx'),
                    'name'     => 'popup_button_url',
                    'type'     => 'text',
                    'priority' => 40,
                    'default'  => '#',
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
        if (isset($fields['appearance'])) {
            $fields['appearance'] = Rules::is('source', $this->id, true, $fields['appearance']);
        }
        if (isset($fields['queue_management'])) {
            $fields['queue_management'] = Rules::is('source', $this->id, true, $fields['queue_management']);
        }
        if (isset($fields['timing'])) {
            $fields['timing'] = Rules::is('source', $this->id, true, $fields['timing']);
        }
        if (isset($fields['behaviour'])) {
            $fields['behaviour'] = Rules::is('source', $this->id, true, $fields['behaviour']);
        }
        if (isset($fields['sound_section'])) {
            $fields['sound_section'] = Rules::is('source', $this->id, true, $fields['sound_section']);
        }

        $_fields             = &$fields["appearance"]['fields'];
        $conversion_position = &$_fields['position']['options'];
        $conversion_position['bottom_left']  = Rules::is('source', $this->id, true, $conversion_position['bottom_left']);
        $conversion_position['bottom_right'] = Rules::is('source', $this->id, true, $conversion_position['bottom_right']);

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
