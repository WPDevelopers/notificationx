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
    public $doc_link        = 'https://notificationx.com/docs/google-reviews-with-notificationx/';
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
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('admin_menu', [$this, 'add_feedback_entries_menu'], 35);
    }

    /**
     * Add Feedback Entries menu
     */
    public function add_feedback_entries_menu() {
        add_submenu_page(
            'nx-admin',
            __('Feedback Entries', 'notificationx'),
            __('Feedback Entries', 'notificationx'),
            'read_notificationx',
            'nx-feedback-entries',
            [\NotificationX\Admin\Admin::get_instance(), 'views'],
            25
        );
    }

    /**
     * Register REST API routes for popup form submission
     */
    public function register_rest_routes() {
        register_rest_route('notificationx/v1', '/popup-submit', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_popup_submission'],
            'permission_callback' => '__return_true',
            'args' => [
                'nx_id' => [
                    'required' => true,
                    'type'     => 'string',
                ],
                'email' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ],
                'message' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
                'name' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
                'timestamp' => [
                    'type' => 'integer',
                ],
            ],
        ]);

        // Feedback entries endpoint
        register_rest_route('notificationx/v1', '/feedback-entries', [
            'methods' => 'GET',
            'callback' => [$this, 'get_feedback_entries'],
            'permission_callback' => function() {
                return current_user_can('read_notificationx');
            },
            'args' => [
                'page' => [
                    'default' => 1,
                    'type' => 'integer',
                    'minimum' => 1,
                ],
                'per_page' => [
                    'default' => 20,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 200,
                ],
                's' => [
                    'default' => '',
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // Delete feedback entry endpoint
        register_rest_route('notificationx/v1', '/feedback-entries/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_feedback_entry'],
            'permission_callback' => function() {
                return current_user_can('edit_notificationx');
            },
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);

        // Bulk delete feedback entries endpoint
        register_rest_route('notificationx/v1', '/feedback-entries/bulk-delete', [
            'methods' => 'POST',
            'callback' => [$this, 'bulk_delete_feedback_entries'],
            'permission_callback' => function() {
                return current_user_can('edit_notificationx');
            },
            'args' => [
                'ids' => [
                    'required' => true,
                    'type' => 'array',
                    'items' => [
                        'type' => 'integer',
                    ],
                ],
            ],
        ]);
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
                    'popup_bg_color'          => '#ffffff',
                    'popup_title_color'       => '#ffffff',
                    'popup_desc_color'        => '#333333',
                    'popup_button_bg_color'   => '#ff6b35',
                    'popup_button_text_color' => '#ffffff',
                    'overlay_color'           => 'rgba(0, 0, 0, 0.5)',
                    'position'                => 'center',
                ],
            ],
            'theme-two' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-two.png',
                'defaults' => [
                    'popup_title'             => __('Boost your sales using SureCart', 'notificationx'),
                    'popup_content'           => __('<iframe width="560" height="315" src="https://www.youtube.com/embed/dw176Jmk74M?si=3suUqkCkQuYQrh2G" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>', 'notificationx'),
                    'popup_button_text'       => __('See How', 'notificationx'),
                    'popup_bg_color'          => '#ffffff',
                    'popup_title_color'       => '#333333',
                    'popup_desc_color'        => '#666666',
                    'popup_button_bg_color'   => '#8b5cf6',
                    'popup_button_text_color' => '#ffffff',
                    'overlay_color'           => 'rgba(0, 0, 0, 0.5)',
                    'position'                => 'center',
                ],
            ],
            'theme-three' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-three.png',
                'defaults' => [
                    'popup_title'                    => __('All Offers', 'notificationx'),
                    'popup_button_text'              => __('Latest Offers', 'notificationx'),
                    'popup_button_icon'              => 'latest_offer.svg',
                    'popup_button_bg_color'          => '#d97706',
                    'popup_button_text_color'        => '#ffffff',
                    'popup_button_border_color'      => '#d97706',
                    'popup_bg_color'                 => '#fef7ed',
                    'popup_title_color'              => '#333333',
                    'popup_desc_color'               => '#666666',
                    'popup_repeater_highlight_color' => '#FF6B1B',
                    'overlay_color'                  => 'rgba(0, 0, 0, 0.5)',
                    'position'                       => 'center',
                ],
            ],
            'theme-four' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-four.webp',
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
                    'position'                => 'center',
                ],
            ],
            'theme-five' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-five.webp',
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
                    'position'                => 'center',
                ],
            ],
            'theme-six' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/popup/popup-theme-six.webp',
                'defaults' => [
                    'popup_title'             => __('Get latest news & updates', 'notificationx'),
                    'popup_email_placeholder' => __('Your email address', 'notificationx'),
                    'popup_button_text'       => __('Submit Now', 'notificationx'),
                    'popup_bg_color'          => '#1f2937',
                    'popup_title_color'       => '#ffffff',
                    'popup_desc_color'        => '#d1d5db',
                    'popup_button_bg_color'   => '#10b981',
                    'popup_button_text_color' => '#ffffff',
                    'overlay_color'           => 'rgba(0, 0, 0, 0.7)',
                    'position'                => 'center',
                ],
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
                    'popup_button_bg_color'     => '#f59e0b',
                    'popup_button_text_color'   => '#ffffff',
                    'popup_button_border_color' => '#f59e0b',
                    'popup_bg_color'            => '#fef3c7',
                    'popup_title_color'         => '#92400e',
                    'popup_desc_color'          => '#b6ac9fff',
                    'overlay_color'             => 'rgba(0, 0, 0, 0.5)',
                    'position'                  => 'center',
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
                    'label' => __("Highlight Text Color", 'notificationx'),
                    'name'  => "popup_repeater_highlight_color",
                    'type'  => "colorpicker",
                    'default'  => "#FF6B1B",
                    'help'  => __('Color for the highlight text (e.g., "30% OFF")', 'notificationx'),
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
                ],
                [
                    'label'    => __('Show Email Field', 'notificationx'),
                    'name'     => 'popup_show_email_field',
                    'type'     => 'toggle',
                    'priority' => 30,
                    'info'     => InfoTooltipManager::get_instance()->render('popup_notification_email_field'),
                    'rules'    => Rules::logicalRule([
                        Rules::is('themes', 'popup_notification_theme-four'),
                        Rules::is('themes', 'popup_notification_theme-five'),
                        Rules::is('themes', 'popup_notification_theme-six'),
                        Rules::is('themes', 'popup_notification_theme-seven'),
                    ], 'or'),
                    'default'  => [
                        'popup_notification_theme-four' => false,  // Default false for theme-four
                        'popup_notification_theme-five' => true,   // Default true for theme-five
                        'popup_notification_theme-six' => true,    // Default true for theme-six
                        'popup_notification_theme-seven' => true,  // Default true for theme-seven
                    ],
                ],
                [
                    'label'    => __('Show Message Field', 'notificationx'),
                    'name'     => 'popup_show_message_field',
                    'type'     => 'toggle',
                    'priority' => 35,
                    'info'     => InfoTooltipManager::get_instance()->render('popup_notification_message_field'),
                    'rules'    => Rules::logicalRule([
                        Rules::is('themes', 'popup_notification_theme-four'),
                        Rules::is('themes', 'popup_notification_theme-five'),
                        Rules::is('themes', 'popup_notification_theme-six'),
                        Rules::is('themes', 'popup_notification_theme-seven'),
                    ], 'or'),
                    'default'  => [
                        'popup_notification_theme-four' => true,   // Default true for theme-four
                        'popup_notification_theme-five' => true,   // Default true for theme-five
                        'popup_notification_theme-six' => true,    // Default true for theme-six
                        'popup_notification_theme-seven' => true,  // Default true for theme-seven
                    ],
                ],
                // Name placeholder field - only for form themes when name field is enabled
                [
                    'label'    => __('Name Field Placeholder', 'notificationx'),
                    'name'     => 'popup_name_placeholder',
                    'type'     => 'text',
                    'priority' => 40,
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

                // Email placeholder field - shows when email field is enabled for any theme
                [
                    'label'    => __('Email Address Placeholder', 'notificationx'),
                    'name'     => 'popup_email_placeholder',
                    'type'     => 'text',
                    'priority' => 45,
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

                // Message placeholder field - only for form themes when message field is enabled
                [
                    'label'    => __('Message Field Placeholder', 'notificationx'),
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
     * Generate entry key
     *
     * @param string $key
     * @return string
     */
    public function key($key = '') {
        return $this->id . '_' . $key;
    }

    /**
     * Get feedback entries
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_feedback_entries($request) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nx_entries';

        // Get pagination parameters
        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 20;
        $search = $request->get_param('s') ?: '';
        $offset = ($page - 1) * $per_page;

        // Build WHERE clause
        $where_conditions = ["e.source = %s"];
        $where_values = [$this->id];

        // Add search functionality
        if (!empty($search)) {
            $where_conditions[] = "(e.data LIKE %s OR e.created_at LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where_conditions);

        // Get total count for pagination
        $total_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} e WHERE {$where_clause}",
            ...$where_values
        );
        $total_items = (int) $wpdb->get_var($total_query);

        // Get paginated entries with notification information
        $posts_table = $wpdb->prefix . 'nx_posts';
        $entries_query = $wpdb->prepare(
            "SELECT e.*, p.title as notification_name, p.nx_id as notification_id
             FROM {$table_name} e
             LEFT JOIN {$posts_table} p ON e.nx_id = p.nx_id
             WHERE {$where_clause}
             ORDER BY e.created_at DESC
             LIMIT %d OFFSET %d",
            ...array_merge($where_values, [$per_page, $offset])
        );
        $entries = $wpdb->get_results($entries_query, ARRAY_A);

        $formatted_entries = [];
        foreach ($entries as $entry) {
            $data = maybe_unserialize($entry['data']);
            $formatted_entries[] = [
                'id'                => $entry['entry_id'],
                'date'              => $entry['created_at'],
                'name'              => $data['name'] ?? '',
                'email'             => $data['email'] ?? '',
                'message'           => $data['message'] ?? '',
                'title'             => $data['title'] ?? '',
                'theme'             => $data['theme'] ?? '',
                'ip'                => $data['ip'] ?? '',
                'notification_name' => $entry['notification_name'] ?? '',
                'notification_id'   => $entry['notification_id'] ?? 0,
                'nx_id'             => $entry['nx_id'] ?? 0,
            ];
        }

        return new \WP_REST_Response([
            'entries' => $formatted_entries,
            'total' => $total_items,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ], 200);
    }

    /**
     * Delete feedback entry
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function delete_feedback_entry($request) {
        global $wpdb;

        $entry_id = $request->get_param('id');
        $table_name = $wpdb->prefix . 'nx_entries';

        $result = $wpdb->delete(
            $table_name,
            [
                'entry_id' => $entry_id,
                'source' => $this->id
            ],
            ['%d', '%s']
        );

        if ($result === false) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Failed to delete entry', 'notificationx'),
            ], 500);
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Entry deleted successfully', 'notificationx'),
        ], 200);
    }

    /**
     * Bulk delete feedback entries
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function bulk_delete_feedback_entries($request) {
        global $wpdb;

        $entry_ids = $request->get_param('ids');
        $table_name = $wpdb->prefix . 'nx_entries';

        if (empty($entry_ids) || !is_array($entry_ids)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('No entries selected for deletion', 'notificationx'),
            ], 400);
        }

        // Sanitize entry IDs
        $entry_ids = array_map('absint', $entry_ids);
        $entry_ids = array_filter($entry_ids); // Remove any zero values

        if (empty($entry_ids)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Invalid entry IDs provided', 'notificationx'),
            ], 400);
        }

        // Create placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($entry_ids), '%d'));

        // Prepare the query with source filter
        $query = $wpdb->prepare(
            "DELETE FROM {$table_name} WHERE entry_id IN ({$placeholders}) AND source = %s",
            array_merge($entry_ids, [$this->id])
        );

        $result = $wpdb->query($query);

        if ($result === false) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Failed to delete entries', 'notificationx'),
            ], 500);
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => sprintf(
                /* translators: %d: Number of entries deleted */
                _n('%d entry deleted successfully', '%d entries deleted successfully', $result, 'notificationx'),
                $result
            ),
            'deleted_count' => $result,
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
        if (isset($fields['behaviour'])) {
            $fields['behaviour'] = Rules::is('source', $this->id, true, $fields['behaviour']);
        }
        if (isset($fields['sound_section'])) {
            $fields['sound_section'] = Rules::is('source', $this->id, true, $fields['sound_section']);
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
        return sprintf(__('<p>Create engaging Announcement to capture visitor attention and boost conversions on your WordPress site. Need help? Follow our <a href="%1$s" target="_blank">step-by-step guides</a> for creating effective Announcement.</p>
        <p>🎦 Watch the video <a target="_blank" href="%2$s">tutorial</a> for a quick guide.</p>
        <p><strong>Recommended Blogs:</strong></p>
        <p>🔥 <a target="_blank" href="%3$s">How to Create Effective Announcement with NotificationX?</a></p>
        <p><strong>Pro Tips:</strong></p>
        <p>✨ Use compelling headlines and clear call-to-action buttons for better conversion rates.</p>', 'notificationx'),
        'https://notificationx.com/docs/popup-notifications/',
        'https://youtu.be/popup-tutorial',
        'https://notificationx.com/blog/popup-notifications/'
    );
    }

}
