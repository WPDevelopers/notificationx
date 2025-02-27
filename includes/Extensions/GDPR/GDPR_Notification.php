<?php
/**
 * Wistia Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\GDPR;

use NotificationX\GetInstance;
use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;
use NotificationX\Extensions\Extension;

/**
 * GDPR Extension
 * @method static GDPR get_instance($args = null)
 */
class GDPR_Notification extends Extension {
    /**
     * Instance of GDPR
     *
     * @var GDPR
     */
    use GetInstance;

    public $priority        = 15;
    public $id              = 'gdpr_notification';
    public $doc_link        = 'https://notificationx.com/docs/google-reviews-with-notificationx/';
    public $types           = 'gdpr';
    public $module          = 'modules_gdpr';
    // public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/GDPR.png';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
        add_filter('nx_design_tab_fields', [$this, 'design_fields'], 99);
        add_filter('nx_content_fields', array($this, 'content_fields'), 999);
        add_filter('nx_customize_fields', array($this, 'customize_fields'), 999);
    }

    public function init_extension()
    {
        $this->title = __('GDPR', 'notificationx');
        $this->module_title = __('Cookie Notice', 'notificationx');
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

        $fields['advance_design_section']['fields']['gdpr_design'] = [
            'label'    => __("Design", 'notificationx'),
            'name'     => "gdpr_design",
            'type'     => "section",
            'priority' => 5,
            'rules'    => Rules::logicalRule([
                Rules::is('source', $this->id, false),
                Rules::is('advance_edit', true),
            ]),
            'fields' => [
                [
                    'label' => __("Background Color", 'notificationx'),
                    'name'  => "gdpr_design_bg_color",
                    'type'  => "colorpicker",
                    'default'  => "",
                ],
                [
                    'label' => __("Footer Background Color", 'notificationx'),
                    'name'  => "gdpr_design_ft_bg_color",
                    'type'  => "colorpicker",
                    'default'  => "",
                ],
                [
                    'label' => __("Title Color", 'notificationx'),
                    'name'  => "title_text_color",
                    'type'  => "colorpicker",
                    'default'  => "",
                ],
                [
                    'label'       => __('Title Font Size', 'notificationx'),
                    'name'        => "title_font_size",
                    'type'        => "number",
                    'default'     => '20',
                    'description' => 'px',
                    'help'        => __('This font size will be applied for <mark>Title</mark> only', 'notificationx'),
                ],
                [
                    'label' => __("Description Color", 'notificationx'),
                    'name'  => "description_text_color",
                    'type'  => "colorpicker",
                    'default'  => "",
                ],
                [
                    'label'       => __('Description Font Size', 'notificationx'),
                    'name'        => "description_font_size",
                    'type'        => "number",
                    'default'     => '14',
                    'description' => 'px',
                    'help'        => __('This font size will be applied for <mark>Description</mark> only', 'notificationx'),
                ],
                [
                    'label' => __("Close Button Color", 'notificationx'),
                    'name'  => "close_btn_color",
                    'type'  => "colorpicker",
                    'default'  => "",
                    'rules' => Rules::logicalRule([
                        Rules::is('themes', 'gdpr_theme-banner-light-one', true),
                        Rules::is('themes', 'gdpr_theme-light-two', true),
                        Rules::is('themes', 'gdpr_theme-light-four', true),
                        Rules::is('themes', 'gdpr_theme-banner-dark-one', true),
                        Rules::is('themes', 'gdpr_theme-dark-two', true),
                        Rules::is('themes', 'gdpr_theme-dark-four', true),
                    ]),
                ],
                [
                    'label'       => __('Close Button Size', 'notificationx'),
                    'name'        => "close_btn_size",
                    'type'        => "number",
                    'default'     => '18',
                    'description' => 'px',
                    'rules' => Rules::logicalRule([
                        Rules::is('themes', 'gdpr_theme-banner-light-one', true),
                        Rules::is('themes', 'gdpr_theme-light-two', true),
                        Rules::is('themes', 'gdpr_theme-light-four', true),
                        Rules::is('themes', 'gdpr_theme-banner-dark-one', true),
                        Rules::is('themes', 'gdpr_theme-dark-two', true),
                        Rules::is('themes', 'gdpr_theme-dark-four', true),
                    ]),
                ],
            ]
        ];

        $fields['advance_design_section']['fields']['gdpr_accept_btn'] = [
            'label'    => __("Accept Button", 'notificationx'),
            'name'     => "gdpr_accept_btn",
            'type'     => "section",
            'priority' => 6,
            'rules'    => Rules::logicalRule([
                Rules::is('source', $this->id, false),
                Rules::is('advance_edit', true),
            ]),
            'fields' => [
                [
                    'label' => __("Background Color", 'notificationx'),
                    'name'  => "gdpr_accept_btn_bg_color",
                    'type'  => "colorpicker",
                    'default'  => "",
                ],
                [
                    'label' => __("Border Color", 'notificationx'),
                    'name'  => "gdpr_accept_btn_border_color",
                    'type'  => "colorpicker",
                    'default'  => "",
                ],
                [
                    'label' => __("Text Color", 'notificationx'),
                    'name'  => "gdpr_accept_btn_text_color",
                    'type'  => "colorpicker",
                    'default'  => "",
                ],
                [
                    'label'       => __('Font Size', 'notificationx'),
                    'name'        => "gdpr_accept_btn_font_size",
                    'type'        => "number",
                    'default'     => '14',
                    'description' => 'px',
                ],
            ]
        ];

        $fields['advance_design_section']['fields']['gdpr_reject_btn'] = [
            'label'    => __("Reject Button", 'notificationx'),
            'name'     => "gdpr_reject_btn",
            'type'     => "section",
            'priority' => 7,
            'rules'    => Rules::logicalRule([
                Rules::is('source', $this->id, false),
                Rules::is('advance_edit', true),
                Rules::is('themes', 'gdpr_theme-banner-light-two', true),
                Rules::is('themes', 'gdpr_theme-light-one', true),
                Rules::is('themes', 'gdpr_theme-light-three', true),
                Rules::is('themes', 'gdpr_theme-banner-dark-two', true),
                Rules::is('themes', 'gdpr_theme-dark-one', true),
                Rules::is('themes', 'gdpr_theme-dark-three', true),
            ]),
            'fields' => [
                [
                    'label' => __("Background Color", 'notificationx'),
                    'name'  => "gdpr_reject_btn_bg_color",
                    'type'  => "colorpicker",
                    'default'  => "",
                ],
                [
                    'label' => __("Border Color", 'notificationx'),
                    'name'  => "gdpr_reject_btn_border_color",
                    'type'  => "colorpicker",
                    'default'  => "",
                ],
                [
                    'label' => __("Text Color", 'notificationx'),
                    'name'  => "gdpr_reject_btn_text_color",
                    'type'  => "colorpicker",
                    'default'  => "",
                ],
                [
                    'label'       => __('Font Size', 'notificationx'),
                    'name'        => "gdpr_reject_btn_font_size",
                    'type'        => "number",
                    'default'     => '14',
                    'description' => 'px',
                ],
            ]
        ];

        $fields['advance_design_section']['fields']['gdpr_customize_btn'] = [
            'label'    => __("Customize Button", 'notificationx'),
            'name'     => "gdpr_customize_btn",
            'type'     => "section",
            'priority' => 8,
            'rules'    => Rules::logicalRule([
                Rules::is('source', $this->id, false),
                Rules::is('advance_edit', true),
            ]),
            'fields' => [
                [
                    'label' => __("Background Color", 'notificationx'),
                    'name'  => "gdpr_customize_btn_bg_color",
                    'type'  => "colorpicker",
                    'default'  => "",
                ],
                [
                    'label' => __("Border Color", 'notificationx'),
                    'name'  => "gdpr_customize_btn_border_color",
                    'type'  => "colorpicker",
                    'default'  => "",
                ],
                [
                    'label' => __("Text Color", 'notificationx'),
                    'name'  => "gdpr_customize_btn_text_color",
                    'type'  => "colorpicker",
                    'default'  => "",
                ],
                [
                    'label'       => __('Font Size', 'notificationx'),
                    'name'        => "gdpr_customize_btn_font_size",
                    'type'        => "number",
                    'default'     => '14',
                    'description' => 'px',
                ],
            ]
        ];

        return $fields;
    }

    public function customize_fields( $fields ) {
        if (isset($fields['appearance'])) {
			$fields['appearance'] = Rules::is('source', $this->id, true, $fields['appearance']);
		}
        if (isset($fields['queue_management'])) {
			$fields['queue_management'] = Rules::is('source', $this->id, true, $fields['queue_management']);
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
        return $fields;
    }

    public function content_fields( $fields ) {
        if (isset($fields['utm_options'])) {
			$fields['utm_options'] = Rules::is('source', $this->id, true, $fields['utm_options']);
		}
        if (isset($fields['content'])) {
			$fields['content'] = Rules::is('source', $this->id, true, $fields['content']);
		}
        return $fields;
    }

    public function doc(){
        return sprintf(__('<p>You can showcase Cookie Notice effortlessly on your WordPress site to ensure compliance with visitors. Need help? Follow our <a href="%1$s" target="_blank">step-by-step guides</a> for creating a Cookie Notice on the WordPress website.</p>
        <p>🎦 Watch the video <a target="_blank" href="%2$s">tutorial</a> for a quick guide.</p>
		<p><strong>Recommended Blogs:</strong></p>
		<p>🔥 <a target="_blank" href="%3$s">How to Display WordPress Cookie Notice Using NotificationX?</a></p>', 'notificationx'),
        'https://notificationx.com/docs/how-to-configure-cookies-policy-for-website/',
        'https://youtu.be/xMiRgH436SE',
        'https://notificationx.com/blog/display-wordpress-cookie-notice/',
        );
    }

}
