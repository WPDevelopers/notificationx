<?php
/**
 * Exit Intent Popup Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\ExitIntent;

use NotificationX\GetInstance;
use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;
use NotificationX\Extensions\Extension;

/**
 * Exit Intent Popup Extension
 * @method static ExitIntentNotification get_instance($args = null)
 */
class ExitIntentNotification extends Extension {
    use GetInstance;

    public $priority        = 16;
    public $id              = 'exit_intent_custom';
    public $doc_link        = 'https://notificationx.com/docs/';
    public $types           = 'exit_intent';
    public $module          = 'modules_exit_intent';

    public function __construct() {
        parent::__construct();
    }

    public function init_extension() {
        $this->title        = __( 'Exit Intent Popup', 'notificationx' );
        $this->module_title = __( 'Exit Intent Popup', 'notificationx' );
        $this->themes       = [
            'theme-one' => [
                'source'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/exit-intent/exit-intent-theme-one.png',
                'defaults' => [
                    'exit_intent_title'        => __( 'Wait! Before You Go……', 'notificationx' ),
                    'exit_intent_subtitle'     => __( "We'd love to get your feedback and help us make this better for everyone.", 'notificationx' ),
                    'exit_intent_button_text'  => __( 'SUBMIT', 'notificationx' ),
                    // 'exit_intent_show_name'           => true,
                    // 'exit_intent_show_email'          => true,
                    'exit_intent_name_label'          => __( 'Name *', 'notificationx' ),
                    'exit_intent_email_label'         => __( 'Enter Your Email *', 'notificationx' ),
                    // 'exit_intent_show_message'        => false,
                    'exit_intent_message_placeholder' => __( 'Your message...', 'notificationx' ),
                    'position'                        => 'center',
                ],
                'column' => '5',
            ],
            'theme-four' => [
                'source'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/exit-intent/exit-intent-theme-four.png',
                'defaults' => [
                    'exit_intent_t4_badge'    => __( 'Before you go...', 'notificationx' ),
                    'exit_intent_t4_title'    => __( 'Watch this short demo video', 'notificationx' ),
                    'exit_intent_t4_subtitle' => __( 'See how our product simplifies your workflow.', 'notificationx' ),
                    'exit_intent_image_url'    => [ 'url' => 'https://notificationx.com/wp-content/uploads/2026/05/exit-intend-theme-four.jpg' ],
                    'exit_intent_t4_video_url' => '',
                    'position'                => 'center',
                ],
                'column' => '5',
            ],
            'theme-three' => [
                'source'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/exit-intent/exit-intent-theme-three.png',
                'defaults' => [
                    'exit_intent_t3_title'       => __( "Wait, don't go!", 'notificationx' ),
                    'exit_intent_t3_subtitle'    => __( 'Before you leave, we have a special offer just for you!', 'notificationx' ),
                    'exit_intent_t3_offer'       => __( 'Get 15% off your next purchase!', 'notificationx' ),
                    'exit_intent_t3_coupon_text' => __( "Use code STAY15 at checkout. Don't miss out on this limited-time offer.", 'notificationx' ),
                    'exit_intent_button_text'    => __( 'Claim Offer', 'notificationx' ),
                    'exit_intent_dismiss_text'   => __( 'No, thanks!', 'notificationx' ),
                    'exit_intent_image_url'      => [ 'url' => 'https://notificationx.com/wp-content/uploads/2026/05/exit-intend-theme-three.png' ],
                    'position'                   => 'center',
                ],
                'column' => '5',
            ],
            'theme-six' => [
                'source'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/exit-intent/exit-intent-theme-seven.png',
                'defaults' => [
                    'exit_intent_t6_title'           => __( 'Limited Edition Bass Boost Headphones', 'notificationx' ),
                    // 'exit_intent_t6_show_timer'      => true,
                    'exit_intent_t6_countdown_label' => __( 'Offer Ends In', 'notificationx' ),
                    'exit_intent_countdown_end'      => '',
                    'exit_intent_t6_days_label'      => __( 'DAYS', 'notificationx' ),
                    'exit_intent_t6_hours_label'     => __( 'HOURS', 'notificationx' ),
                    'exit_intent_t6_minutes_label'   => __( 'MIN', 'notificationx' ),
                    'exit_intent_t6_seconds_label'   => __( 'SEC', 'notificationx' ),
                    'exit_intent_button_text'        => __( 'Grab Now', 'notificationx' ),
                    'exit_intent_image_url'          => [ 'url' => 'https://notificationx.com/wp-content/uploads/2026/05/exit-intend-theme-six.png' ],
                    'position'                       => 'center',
                ],
                'column' => '5',
            ],
            'theme-two' => [
                'source'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/exit-intent/exit-intent-theme-two.png',
                'defaults' => [
                    'exit_intent_sale_badge'       => __( 'Flash Sale', 'notificationx' ),
                    'exit_intent_sale_headline'    => __( '50% OFF', 'notificationx' ),
                    'exit_intent_sale_desc'        => __( 'ON ENTIRE ORDER', 'notificationx' ),
                    'exit_intent_button_text'      => __( 'Shop The Flash Sale Now', 'notificationx' ),
                    'exit_intent_dismiss_text'     => __( 'NO, THANKS!', 'notificationx' ),
                    'exit_intent_image_url'        => [ 'url' => 'https://notificationx.com/wp-content/uploads/2026/05/exit-intend-theme-two-five-seven.jpg' ],
                    'position'                     => 'center',
                ],
                'column' => '5',
            ],
             'theme-seven' => [
                'source'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/exit-intent/exit-intent-theme-six.png',
                'defaults' => [
                    'exit_intent_t7_headline'          => __( 'Turn Your House Into a Home', 'notificationx' ),
                    'exit_intent_t7_discount_text'     => __( 'Your First Order Comes With a Surprise Deal!', 'notificationx' ),
                    'exit_intent_t7_description'       => __( 'Handpicked décor that feels like home the moment it arrives.', 'notificationx' ),
                    'exit_intent_t7_email_placeholder' => __( 'Enter your email', 'notificationx' ),
                    'exit_intent_button_text'          => __( 'SEND COUPON', 'notificationx' ),
                    'exit_intent_image_url'            => [ 'url' => 'https://notificationx.com/wp-content/uploads/2026/05/exit-intend-theme-five.png' ],
                    'position'                         => 'center',
                ],
                'column' => '5',
            ],
            'theme-five' => [
                'source'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/exit-intent/exit-intent-theme-five.png',
                'defaults' => [
                    'exit_intent_t5_title'           => __( 'Flash Sale', 'notificationx' ),
                    'exit_intent_t5_headline'        => __( '50% OFF', 'notificationx' ),
                    'exit_intent_t5_desc'            => __( 'ON ENTIRE ORDER', 'notificationx' ),
                    'exit_intent_t5_countdown_label' => __( 'LIMITED-TIME OFFER! SALE ENDS IN', 'notificationx' ),
                    'exit_intent_countdown_end'      => '',
                    'exit_intent_t5_days_label'      => __( 'DAYS', 'notificationx' ),
                    'exit_intent_t5_hours_label'     => __( 'HRS', 'notificationx' ),
                    'exit_intent_t5_minutes_label'   => __( 'MIN', 'notificationx' ),
                    'exit_intent_t5_seconds_label'   => __( 'SEC', 'notificationx' ),
                    'exit_intent_t5_timer_bg'        => '#fff0f5',
                    'exit_intent_t5_timer_color'    => '#e91e63',
                    'exit_intent_button_text'        => __( 'Shop The Flash Sale Now', 'notificationx' ),
                    'exit_intent_dismiss_text'       => __( 'NO, THANKS!', 'notificationx' ),
                    'exit_intent_image_url'          => [ 'url' => 'https://notificationx.com/wp-content/uploads/2026/05/exit-intend-theme-two-five-seven.jpg' ],
                    'position'                       => 'center',
                ],
                'column' => '5',
            ],
        ];
    }

    public function init_fields() {
        parent::init_fields();
        add_filter( 'nx_content_fields',        [ $this, 'content_fields' ],       999 );
        add_filter( 'nx_design_tab_fields',     [ $this, 'design_fields' ],        99  );
        add_filter( 'nx_customize_fields',      [ $this, 'customize_fields' ],     999 );
        add_filter( 'nx_display_fields',        [ $this, 'display_fields' ],       999 );
    }

    public function display_fields( $fields ) {
        if ( isset( $fields['image-section'] ) ) {
            $fields['image-section'] = Rules::is( 'source', $this->id, true, $fields['image-section'] );
        }
        return $fields;
    }

    public function content_fields( $fields ) {
        // Hide standard content fields for this source
        if ( isset( $fields['utm_options'] ) ) {
            $fields['utm_options'] = Rules::is( 'source', $this->id, true, $fields['utm_options'] );
        }
        if ( isset( $fields['content'] ) ) {
            $fields['content'] = Rules::is( 'source', $this->id, true, $fields['content'] );
        }
        if ( isset( $fields['link_options'] ) ) {
            $fields['link_options'] = Rules::is( 'source', $this->id, true, $fields['link_options'] );
        }

        // ── Theme Two content fields ─────────────────────────────────────────────
        $fields['exit_intent_theme_two_section'] = [
            'label'    => __( 'Exit Intent Content', 'notificationx' ),
            'name'     => 'exit_intent_theme_two_section',
            'type'     => 'section',
            'priority' => 5,
            'rules'    => Rules::logicalRule( [
                Rules::is( 'source', $this->id ),
                Rules::is( 'themes', $this->id . '_theme-two' ),
            ] ),
            'fields'   => [
                [
                    'label'    => __( 'Right Panel Image', 'notificationx' ),
                    'name'     => 'exit_intent_image_url',
                    'type'     => 'media',
                    'priority' => 5,
                    'default'  => [
                        'url' => 'https://notificationx.com/wp-content/uploads/2026/05/exit-intend-theme-two-five-seven.jpg',
                    ],
                    'help'     => __( 'Upload or select the image to display in the right panel.', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Sale Badge Text', 'notificationx' ),
                    'name'     => 'exit_intent_sale_badge',
                    'type'     => 'text',
                    'priority' => 10,
                    'default'  => __( 'Flash Sale', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Sale Headline', 'notificationx' ),
                    'name'     => 'exit_intent_sale_headline',
                    'type'     => 'text',
                    'priority' => 20,
                    'default'  => __( '50% OFF', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Sale Description', 'notificationx' ),
                    'name'     => 'exit_intent_sale_desc',
                    'type'     => 'text',
                    'priority' => 30,
                    'default'  => __( 'ON ENTIRE ORDER', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Button Text', 'notificationx' ),
                    'name'     => 'exit_intent_button_text',
                    'type'     => 'text',
                    'priority' => 60,
                    'default'  => __( 'Shop The Flash Sale Now', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Button URL', 'notificationx' ),
                    'name'     => 'exit_intent_button_url',
                    'type'     => 'text',
                    'priority' => 61,
                    'default'  => '',
                    'help'     => __( 'Where the CTA button sends visitors. Leave empty to just dismiss the popup.', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Open in New Tab', 'notificationx' ),
                    'name'     => 'exit_intent_button_new_tab',
                    'type'     => 'toggle',
                    'priority' => 62,
                    'default'  => true,
                ],
                [
                    'label'    => __( 'Dismiss Link Text', 'notificationx' ),
                    'name'     => 'exit_intent_dismiss_text',
                    'type'     => 'text',
                    'priority' => 70,
                    'default'  => __( 'NO, THANKS!', 'notificationx' ),
                ],
            ],
        ];

        // ── Theme Three content fields ────────────────────────────────────────────
        $fields['exit_intent_theme_three_section'] = [
            'label'    => __( 'Exit Intent Content', 'notificationx' ),
            'name'     => 'exit_intent_theme_three_section',
            'type'     => 'section',
            'priority' => 5,
            'rules'    => Rules::logicalRule( [
                Rules::is( 'source', $this->id ),
                Rules::is( 'themes', $this->id . '_theme-three' ),
            ] ),
            'fields'   => [
                [
                    'label'    => __( 'Character Image', 'notificationx' ),
                    'name'     => 'exit_intent_image_url',
                    'type'     => 'media',
                    'priority' => 5,
                    'default'  => [
                        'url' => 'https://notificationx.com/wp-content/uploads/2026/05/exit-intend-theme-three.png',
                    ],
                    'help'     => __( 'Upload or select a character/illustration image. It will appear above the popup card.', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Headline', 'notificationx' ),
                    'name'     => 'exit_intent_t3_title',
                    'type'     => 'text',
                    'priority' => 10,
                    'default'  => __( "Wait, don't go!", 'notificationx' ),
                ],
                [
                    'label'    => __( 'Subtitle', 'notificationx' ),
                    'name'     => 'exit_intent_t3_subtitle',
                    'type'     => 'text',
                    'priority' => 20,
                    'default'  => __( 'Before you leave, we have a special offer just for you!', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Offer Text', 'notificationx' ),
                    'name'     => 'exit_intent_t3_offer',
                    'type'     => 'text',
                    'priority' => 30,
                    'default'  => __( 'Get 15% off your next purchase!', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Coupon / Details Text', 'notificationx' ),
                    'name'     => 'exit_intent_t3_coupon_text',
                    'type'     => 'text',
                    'priority' => 40,
                    'default'  => __( "Use code STAY15 at checkout. Don't miss out on this limited-time offer.", 'notificationx' ),
                ],
                [
                    'label'    => __( 'Button Text', 'notificationx' ),
                    'name'     => 'exit_intent_button_text',
                    'type'     => 'text',
                    'priority' => 50,
                    'default'  => __( 'Claim Offer', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Button URL', 'notificationx' ),
                    'name'     => 'exit_intent_button_url',
                    'type'     => 'text',
                    'priority' => 51,
                    'default'  => '',
                    'help'     => __( 'Where the CTA button sends visitors. Leave empty to just dismiss the popup.', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Open in New Tab', 'notificationx' ),
                    'name'     => 'exit_intent_button_new_tab',
                    'type'     => 'toggle',
                    'priority' => 52,
                    'default'  => true,
                ],
                [
                    'label'    => __( 'Dismiss Link Text', 'notificationx' ),
                    'name'     => 'exit_intent_dismiss_text',
                    'type'     => 'text',
                    'priority' => 60,
                    'default'  => __( 'No, thanks!', 'notificationx' ),
                ],
            ],
        ];

        // ── Theme Four content fields ─────────────────────────────────────────────
        $fields['exit_intent_theme_four_section'] = [
            'label'    => __( 'Exit Intent Content', 'notificationx' ),
            'name'     => 'exit_intent_theme_four_section',
            'type'     => 'section',
            'priority' => 5,
            'rules'    => Rules::logicalRule( [
                Rules::is( 'source', $this->id ),
                Rules::is( 'themes', $this->id . '_theme-four' ),
            ] ),
            'fields'   => [
                [
                    'label'    => __( 'Video Thumbnail', 'notificationx' ),
                    'name'     => 'exit_intent_image_url',
                    'type'     => 'media',
                    'priority' => 5,
                    'default'  => [
                        'url' => 'https://notificationx.com/wp-content/uploads/2026/05/exit-intend-theme-four.jpg',
                    ],
                    'help'     => __( 'Upload or select the video thumbnail/preview image.', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Badge Text', 'notificationx' ),
                    'name'     => 'exit_intent_t4_badge',
                    'type'     => 'text',
                    'priority' => 10,
                    'default'  => __( 'Before you go...', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Headline', 'notificationx' ),
                    'name'     => 'exit_intent_t4_title',
                    'type'     => 'text',
                    'priority' => 20,
                    'default'  => __( 'Watch this short demo video', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Subtitle', 'notificationx' ),
                    'name'     => 'exit_intent_t4_subtitle',
                    'type'     => 'text',
                    'priority' => 30,
                    'default'  => __( 'See how our product simplifies your workflow.', 'notificationx' ),
                ],
                [
                    'label'       => __( 'Video URL', 'notificationx' ),
                    'name'        => 'exit_intent_t4_video_url',
                    'type'        => 'text',
                    'priority'    => 40,
                    'default'     => '',
                    'placeholder' => 'https://www.youtube.com/watch?v=...',
                    'help'        => __( 'Paste a YouTube, Vimeo, or other video platform URL.', 'notificationx' ),
                ],
            ],
        ];

        // ── Theme Five content fields ────────────────────────────────────────────
        $fields['exit_intent_theme_five_section'] = [
            'label'    => __( 'Exit Intent Content', 'notificationx' ),
            'name'     => 'exit_intent_theme_five_section',
            'type'     => 'section',
            'priority' => 5,
            'rules'    => Rules::logicalRule( [
                Rules::is( 'source', $this->id ),
                Rules::is( 'themes', $this->id . '_theme-five' ),
            ] ),
            'fields'   => [
                [
                    'label'    => __( 'Right Panel Image', 'notificationx' ),
                    'name'     => 'exit_intent_image_url',
                    'type'     => 'media',
                    'priority' => 5,
                    'default'  => [
                        'url' => 'https://notificationx.com/wp-content/uploads/2026/05/exit-intend-theme-two-five-seven.jpg',
                    ],
                    'help'     => __( 'Upload or select the image to display in the right panel.', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Title', 'notificationx' ),
                    'name'     => 'exit_intent_t5_title',
                    'type'     => 'text',
                    'priority' => 10,
                    'default'  => __( 'Flash Sale', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Headline', 'notificationx' ),
                    'name'     => 'exit_intent_t5_headline',
                    'type'     => 'text',
                    'priority' => 20,
                    'default'  => __( '50% OFF', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Description', 'notificationx' ),
                    'name'     => 'exit_intent_t5_desc',
                    'type'     => 'text',
                    'priority' => 30,
                    'default'  => __( 'ON ENTIRE ORDER', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Button Text', 'notificationx' ),
                    'name'     => 'exit_intent_button_text',
                    'type'     => 'text',
                    'priority' => 60,
                    'default'  => __( 'Shop The Flash Sale Now', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Button URL', 'notificationx' ),
                    'name'     => 'exit_intent_button_url',
                    'type'     => 'text',
                    'priority' => 61,
                    'default'  => '',
                    'help'     => __( 'Where the CTA button sends visitors. Leave empty to just dismiss the popup.', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Open in New Tab', 'notificationx' ),
                    'name'     => 'exit_intent_button_new_tab',
                    'type'     => 'toggle',
                    'priority' => 62,
                    'default'  => true,
                ],
                [
                    'label'    => __( 'Dismiss Link Text', 'notificationx' ),
                    'name'     => 'exit_intent_dismiss_text',
                    'type'     => 'text',
                    'priority' => 70,
                    'default'  => __( 'NO, THANKS!', 'notificationx' ),
                ],
            ],
        ];

        // ── Theme Five Timer Settings ────────────────────────────────────────────
        $fields['exit_intent_theme_five_timer_section'] = [
            'label'    => __( 'Timer Settings', 'notificationx' ),
            'name'     => 'exit_intent_theme_five_timer_section',
            'type'     => 'section',
            'priority' => 6,
            'rules'    => Rules::logicalRule( [
                Rules::is( 'source', $this->id ),
                Rules::is( 'themes', $this->id . '_theme-five' ),
            ] ),
            'fields'   => [
                [
                    'label'    => __( 'Show Countdown Timer', 'notificationx' ),
                    'name'     => 'exit_intent_t5_show_timer',
                    'type'     => 'toggle',
                    'priority' => 5,
                    'default'  => true,
                ],
                [
                    'label'    => __( 'Countdown Label', 'notificationx' ),
                    'name'     => 'exit_intent_t5_countdown_label',
                    'type'     => 'text',
                    'priority' => 10,
                    'default'  => __( 'LIMITED-TIME OFFER! SALE ENDS IN', 'notificationx' ),
                    'rules'    => Rules::is( 'exit_intent_t5_show_timer', true ),
                ],
                [
                    'label'    => __( 'Sale End Date & Time', 'notificationx' ),
                    'name'     => 'exit_intent_countdown_end',
                    'type'     => 'date',
                    'priority' => 20,
                    'default'  => '',
                    'help'     => __( 'Pick the date and time when the sale ends. Leave empty to display static demo numbers.', 'notificationx' ),
                    'rules'    => Rules::is( 'exit_intent_t5_show_timer', true ),
                ],
                [
                    'label'    => __( 'Days Label', 'notificationx' ),
                    'name'     => 'exit_intent_t5_days_label',
                    'type'     => 'text',
                    'priority' => 30,
                    'default'  => __( 'DAYS', 'notificationx' ),
                    'rules'    => Rules::is( 'exit_intent_t5_show_timer', true ),
                ],
                [
                    'label'    => __( 'Hours Label', 'notificationx' ),
                    'name'     => 'exit_intent_t5_hours_label',
                    'type'     => 'text',
                    'priority' => 40,
                    'default'  => __( 'HRS', 'notificationx' ),
                    'rules'    => Rules::is( 'exit_intent_t5_show_timer', true ),
                ],
                [
                    'label'    => __( 'Minutes Label', 'notificationx' ),
                    'name'     => 'exit_intent_t5_minutes_label',
                    'type'     => 'text',
                    'priority' => 50,
                    'default'  => __( 'MIN', 'notificationx' ),
                    'rules'    => Rules::is( 'exit_intent_t5_show_timer', true ),
                ],
                [
                    'label'    => __( 'Seconds Label', 'notificationx' ),
                    'name'     => 'exit_intent_t5_seconds_label',
                    'type'     => 'text',
                    'priority' => 60,
                    'default'  => __( 'SEC', 'notificationx' ),
                    'rules'    => Rules::is( 'exit_intent_t5_show_timer', true ),
                ],
                [
                    'label'    => __( 'Timer Box Background', 'notificationx' ),
                    'name'     => 'exit_intent_t5_timer_bg',
                    'type'     => 'colorpicker',
                    'priority' => 70,
                    'default'  => '#fff0f5',
                    'rules'    => Rules::is( 'exit_intent_t5_show_timer', true ),
                ],
                [
                    'label'    => __( 'Timer Number Color', 'notificationx' ),
                    'name'     => 'exit_intent_t5_timer_color',
                    'type'     => 'colorpicker',
                    'priority' => 80,
                    'default'  => '#e91e63',
                    'rules'    => Rules::is( 'exit_intent_t5_show_timer', true ),
                ],
            ],
        ];

        // ── Theme Seven content fields ───────────────────────────────────────────
        $fields['exit_intent_theme_seven_section'] = [
            'label'    => __( 'Exit Intent Content', 'notificationx' ),
            'name'     => 'exit_intent_theme_seven_section',
            'type'     => 'section',
            'priority' => 5,
            'rules'    => Rules::logicalRule( [
                Rules::is( 'source', $this->id ),
                Rules::is( 'themes', $this->id . '_theme-seven' ),
            ] ),
            'fields'   => [
                [
                    'label'    => __( 'Left Panel Image', 'notificationx' ),
                    'name'     => 'exit_intent_image_url',
                    'type'     => 'media',
                    'priority' => 5,
                    'default'  => [
                        'url' => 'https://notificationx.com/wp-content/uploads/2026/05/exit-intend-theme-two-five-seven.jpg',
                    ],
                    'help'     => __( 'Upload or select the image to display in the left panel.', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Headline', 'notificationx' ),
                    'name'     => 'exit_intent_t7_headline',
                    'type'     => 'text',
                    'priority' => 10,
                    'default'  => __( 'Home Is Where Your Story Begins', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Discount Banner Text', 'notificationx' ),
                    'name'     => 'exit_intent_t7_discount_text',
                    'type'     => 'text',
                    'priority' => 20,
                    'default'  => __( 'Get 15% Off Your First Order!', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Description', 'notificationx' ),
                    'name'     => 'exit_intent_t7_description',
                    'type'     => 'text',
                    'priority' => 30,
                    'default'  => __( 'Discover timeless pieces that turn any space into a sanctuary.', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Email Placeholder', 'notificationx' ),
                    'name'     => 'exit_intent_t7_email_placeholder',
                    'type'     => 'text',
                    'priority' => 40,
                    'default'  => __( 'Enter your email', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Button Text', 'notificationx' ),
                    'name'     => 'exit_intent_button_text',
                    'type'     => 'text',
                    'priority' => 50,
                    'default'  => __( 'SEND COUPON', 'notificationx' ),
                ],
            ],
        ];

        // ── Theme Six content fields ─────────────────────────────────────────────
        $fields['exit_intent_theme_six_section'] = [
            'label'    => __( 'Exit Intent Content', 'notificationx' ),
            'name'     => 'exit_intent_theme_six_section',
            'type'     => 'section',
            'priority' => 5,
            'rules'    => Rules::logicalRule( [
                Rules::is( 'source', $this->id ),
                Rules::is( 'themes', $this->id . '_theme-six' ),
            ] ),
            'fields'   => [
                [
                    'label'    => __( 'Product Image', 'notificationx' ),
                    'name'     => 'exit_intent_image_url',
                    'type'     => 'media',
                    'priority' => 5,
                    'default'  => [
                        'url' => 'https://notificationx.com/wp-content/uploads/2026/05/exit-intend-theme-six.png',
                    ],
                    'help'     => __( 'Upload or select the product image to display at the top of the popup.', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Headline', 'notificationx' ),
                    'name'     => 'exit_intent_t6_title',
                    'type'     => 'text',
                    'priority' => 10,
                    'default'  => __( 'Limited Edition Bass Boost Headphones', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Button Text', 'notificationx' ),
                    'name'     => 'exit_intent_button_text',
                    'type'     => 'text',
                    'priority' => 60,
                    'default'  => __( 'Grab Now', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Button URL', 'notificationx' ),
                    'name'     => 'exit_intent_button_url',
                    'type'     => 'text',
                    'priority' => 61,
                    'default'  => '',
                    'help'     => __( 'Where the CTA button sends visitors. Leave empty to just dismiss the popup.', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Open in New Tab', 'notificationx' ),
                    'name'     => 'exit_intent_button_new_tab',
                    'type'     => 'toggle',
                    'priority' => 62,
                    'default'  => true,
                ],
            ],
        ];

        // ── Theme Six Timer Settings ─────────────────────────────────────────────
        $fields['exit_intent_theme_six_timer_section'] = [
            'label'    => __( 'Timer Settings', 'notificationx' ),
            'name'     => 'exit_intent_theme_six_timer_section',
            'type'     => 'section',
            'priority' => 6,
            'rules'    => Rules::logicalRule( [
                Rules::is( 'source', $this->id ),
                Rules::is( 'themes', $this->id . '_theme-six' ),
            ] ),
            'fields'   => [
                [
                    'label'    => __( 'Show Countdown Timer', 'notificationx' ),
                    'name'     => 'exit_intent_t6_show_timer',
                    'type'     => 'toggle',
                    'priority' => 5,
                    'default'  => true,
                ],
                [
                    'label'    => __( 'Countdown Label', 'notificationx' ),
                    'name'     => 'exit_intent_t6_countdown_label',
                    'type'     => 'text',
                    'priority' => 10,
                    'default'  => __( 'Offer Ends In', 'notificationx' ),
                    'rules'    => Rules::is( 'exit_intent_t6_show_timer', true ),
                ],
                [
                    'label'    => __( 'Sale End Date & Time', 'notificationx' ),
                    'name'     => 'exit_intent_countdown_end',
                    'type'     => 'date',
                    'priority' => 20,
                    'default'  => '',
                    'help'     => __( 'Pick the date and time when the sale ends. Leave empty to display static demo numbers.', 'notificationx' ),
                    'rules'    => Rules::is( 'exit_intent_t6_show_timer', true ),
                ],
                [
                    'label'    => __( 'Days Label', 'notificationx' ),
                    'name'     => 'exit_intent_t6_days_label',
                    'type'     => 'text',
                    'priority' => 30,
                    'default'  => __( 'DAYS', 'notificationx' ),
                    'rules'    => Rules::is( 'exit_intent_t6_show_timer', true ),
                ],
                [
                    'label'    => __( 'Hours Label', 'notificationx' ),
                    'name'     => 'exit_intent_t6_hours_label',
                    'type'     => 'text',
                    'priority' => 40,
                    'default'  => __( 'HOURS', 'notificationx' ),
                    'rules'    => Rules::is( 'exit_intent_t6_show_timer', true ),
                ],
                [
                    'label'    => __( 'Minutes Label', 'notificationx' ),
                    'name'     => 'exit_intent_t6_minutes_label',
                    'type'     => 'text',
                    'priority' => 50,
                    'default'  => __( 'MIN', 'notificationx' ),
                    'rules'    => Rules::is( 'exit_intent_t6_show_timer', true ),
                ],
                [
                    'label'    => __( 'Seconds Label', 'notificationx' ),
                    'name'     => 'exit_intent_t6_seconds_label',
                    'type'     => 'text',
                    'priority' => 60,
                    'default'  => __( 'SEC', 'notificationx' ),
                    'rules'    => Rules::is( 'exit_intent_t6_show_timer', true ),
                ],
            ],
        ];

        // ── Theme One content fields ─────────────────────────────────────────────
        $fields['exit_intent_content_section'] = [
            'label'    => __( 'Exit Intent Content', 'notificationx' ),
            'name'     => 'exit_intent_content_section',
            'type'     => 'section',
            'priority' => 5,
            'rules'    => Rules::logicalRule( [
                Rules::is( 'source', $this->id ),
                Rules::is( 'themes', $this->id . '_theme-two',   true ),
                Rules::is( 'themes', $this->id . '_theme-three', true ),
                Rules::is( 'themes', $this->id . '_theme-four',  true ),
                Rules::is( 'themes', $this->id . '_theme-five',  true ),
                Rules::is( 'themes', $this->id . '_theme-six',   true ),
                Rules::is( 'themes', $this->id . '_theme-seven', true ),
            ] ),
            'fields'   => [
                // ── Main copy ────────────────────────────────────────────
                [
                    'label'    => __( 'Title', 'notificationx' ),
                    'name'     => 'exit_intent_title',
                    'type'     => 'text',
                    'priority' => 10,
                    'default'  => __( 'Wait! Before You Go...', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Subtitle', 'notificationx' ),
                    'name'     => 'exit_intent_subtitle',
                    'type'     => 'text',
                    'priority' => 20,
                    'default'  => __( "We'd love to understand what's holding you back", 'notificationx' ),
                ],
                // ── Form fields ──────────────────────────────────────────
                [
                    'label'    => __( 'Show Name Field', 'notificationx' ),
                    'name'     => 'exit_intent_show_name',
                    'type'     => 'toggle',
                    'default'  => true,
                    'priority' => 70,
                    'is_pro'   => true,
                ],
                [
                    'label'    => __( 'Name Placeholder', 'notificationx' ),
                    'name'     => 'exit_intent_name_label',
                    'type'     => 'text',
                    'default'  => __( 'Name *', 'notificationx' ),
                    'priority' => 75,
                    'rules'    => Rules::is( 'exit_intent_show_name', true ),
                ],
                [
                    'label'    => __( 'Show Email Field', 'notificationx' ),
                    'name'     => 'exit_intent_show_email',
                    'type'     => 'toggle',
                    'default'  => true,
                    'priority' => 80,
                    'is_pro'   => true,
                ],
                [
                    'label'    => __( 'Email Placeholder', 'notificationx' ),
                    'name'     => 'exit_intent_email_label',
                    'type'     => 'text',
                    'default'  => __( 'Enter Your Email *', 'notificationx' ),
                    'priority' => 85,
                    'rules'    => Rules::is( 'exit_intent_show_email', true ),
                ],
                [
                    'label'    => __( 'Show Message Field', 'notificationx' ),
                    'name'     => 'exit_intent_show_message',
                    'type'     => 'toggle',
                    'default'  => true,
                    'priority' => 87,
                ],
                [
                    'label'    => __( 'Message Placeholder', 'notificationx' ),
                    'name'     => 'exit_intent_message_placeholder',
                    'type'     => 'text',
                    'default'  => __( 'Your message...', 'notificationx' ),
                    'priority' => 88,
                    'rules'    => Rules::is( 'exit_intent_show_message', true ),
                ],

                // ── Button ───────────────────────────────────────────────
                [
                    'label'    => __( 'Button Text', 'notificationx' ),
                    'name'     => 'exit_intent_button_text',
                    'type'     => 'text',
                    'default'  => __( 'SUBMIT', 'notificationx' ),
                    'priority' => 90,
                ],
            ],
        ];

        return $fields;
    }

    public function design_fields( $fields ) {
        // Hide the inner sub-sections of the global advance_design_section for this source,
        // but keep advance_design_section itself visible so its `advance_edit` toggle renders.
        foreach ( [ 'design', 'typography', 'image-appearance', 'link_button_design' ] as $key ) {
            if ( isset( $fields['advance_design_section']['fields'][ $key ] ) ) {
                $fields['advance_design_section']['fields'][ $key ] = Rules::is(
                    'source', $this->id, true, $fields['advance_design_section']['fields'][ $key ]
                );
            }
        }

        // Inline every theme's design fields directly into advance_design_section so they appear
        // flat under the existing "Advanced Design" heading — no per-theme sub-headers.
        // Priorities are auto-assigned starting at 20 so all theme controls sort BEFORE the
        // global Custom CSS section (priority 150).
        $design   = &$fields['advance_design_section']['fields'];
        $priority = 20;
        $merge    = function( $theme_slug, $list ) use ( &$design, &$priority ) {
            $rule = $this->theme_design_rules( $theme_slug );
            foreach ( $list as $field ) {
                if ( ! empty( $field['rules'] ) ) {
                    $field['rules'] = Rules::logicalRule( [ $rule, $field['rules'] ] );
                } else {
                    $field['rules'] = $rule;
                }
                if ( ! isset( $field['priority'] ) ) {
                    $field['priority'] = $priority;
                }
                $priority++;
                $design[ $field['name'] ] = $field;
            }
        };

        $merge( 'theme-one',   $this->theme_one_design_fields() );
        $merge( 'theme-two',   $this->theme_two_design_fields() );
        $merge( 'theme-three', $this->theme_three_design_fields() );
        $merge( 'theme-four',  $this->theme_four_design_fields() );
        $merge( 'theme-five',  $this->theme_five_design_fields() );
        $merge( 'theme-six',   $this->theme_six_design_fields() );
        $merge( 'theme-seven', $this->theme_seven_design_fields() );

        return $fields;
    }

    /** Build a rule scoped to source + advance_edit + a specific theme. */
    private function theme_design_rules( $theme_slug ) {
        return Rules::logicalRule( [
            Rules::is( 'source', $this->id ),
            Rules::is( 'advance_edit', true ),
            Rules::is( 'themes', $this->id . '_' . $theme_slug ),
        ] );
    }

    private function font_weight_options() {
        return GlobalFields::get_instance()->normalize_fields( [
            '400' => __( 'Normal (400)', 'notificationx' ),
            '500' => __( 'Medium (500)', 'notificationx' ),
            '600' => __( 'Semi Bold (600)', 'notificationx' ),
            '700' => __( 'Bold (700)', 'notificationx' ),
            '800' => __( 'Extra Bold (800)', 'notificationx' ),
        ] );
    }

    /** ───────────────────────── Theme One — Feedback Form ───────────────────────── */
    private function theme_one_design_fields() {
        return [
                // Container
                [ 'label' => __( 'Popup Max Width', 'notificationx' ),         'name' => 'exit_intent_max_width',         'type' => 'number',      'default' => 540,                  'description' => 'px' ],
                [ 'label' => __( 'Border Radius', 'notificationx' ),           'name' => 'exit_intent_border_radius',     'type' => 'number',      'default' => 12,                   'description' => 'px' ],
                [ 'label' => __( 'Background Color', 'notificationx' ),        'name' => 'exit_intent_bg_color',          'type' => 'colorpicker', 'default' => '#EDE7FF' ],
                [ 'label' => __( 'Overlay Background Color', 'notificationx' ),'name' => 'exit_intent_overlay_color',     'type' => 'colorpicker', 'default' => 'rgba(0,0,0,0.5)',
                  'help'  => __( 'Color of the backdrop behind the popup.', 'notificationx' ) ],
                [ 'label' => __( 'Show Background Pattern', 'notificationx' ), 'name' => 'exit_intent_show_pattern',      'type' => 'toggle',      'default' => true ],
                [ 'label' => __( 'Pattern Color', 'notificationx' ),           'name' => 'exit_intent_pattern_color',     'type' => 'colorpicker', 'default' => '#D4C3FF',
                  'rules' => Rules::is( 'exit_intent_show_pattern', true ) ],

                // Title
                [ 'label' => __( 'Title Color', 'notificationx' ),       'name' => 'exit_intent_title_color',       'type' => 'colorpicker', 'default' => '#1a1a2e' ],
                [ 'label' => __( 'Title Font Size', 'notificationx' ),   'name' => 'exit_intent_title_font_size',   'type' => 'number',      'default' => 26, 'description' => 'px' ],
                [ 'label' => __( 'Title Font Weight', 'notificationx' ), 'name' => 'exit_intent_title_font_weight', 'type' => 'select',      'default' => '700',
                  'options' => $this->font_weight_options() ],

                // Subtitle
                [ 'label' => __( 'Subtitle Color', 'notificationx' ),     'name' => 'exit_intent_subtitle_color',     'type' => 'colorpicker', 'default' => '#4a4a6a' ],
                [ 'label' => __( 'Subtitle Font Size', 'notificationx' ), 'name' => 'exit_intent_subtitle_font_size', 'type' => 'number',      'default' => 14, 'description' => 'px' ],

                // Inputs
                [ 'label' => __( 'Input Background Color', 'notificationx' ), 'name' => 'exit_intent_input_bg',            'type' => 'colorpicker', 'default' => '#ffffff' ],
                [ 'label' => __( 'Input Border Color', 'notificationx' ),     'name' => 'exit_intent_input_border_color',  'type' => 'colorpicker', 'default' => '#dddddd' ],
                [ 'label' => __( 'Input Border Radius', 'notificationx' ),    'name' => 'exit_intent_input_border_radius', 'type' => 'number',      'default' => 6, 'description' => 'px' ],
                [ 'label' => __( 'Input Text Color', 'notificationx' ),       'name' => 'exit_intent_input_text_color',    'type' => 'colorpicker', 'default' => '#333333' ],

                // Button
                [ 'label' => __( 'Button Background Color', 'notificationx' ), 'name' => 'exit_intent_btn_bg',            'type' => 'colorpicker', 'default' => '#6B21A8' ],
                [ 'label' => __( 'Button Text Color', 'notificationx' ),       'name' => 'exit_intent_btn_color',         'type' => 'colorpicker', 'default' => '#ffffff' ],
                [ 'label' => __( 'Button Border Radius', 'notificationx' ),    'name' => 'exit_intent_btn_border_radius', 'type' => 'number',      'default' => 6, 'description' => 'px' ],
                [ 'label' => __( 'Button Font Size', 'notificationx' ),        'name' => 'exit_intent_btn_font_size',     'type' => 'number',      'default' => 14, 'description' => 'px' ],
                [ 'label' => __( 'Button Font Weight', 'notificationx' ),      'name' => 'exit_intent_btn_font_weight',   'type' => 'select',      'default' => '700',
                  'options' => $this->font_weight_options() ],

                // Close button
                [ 'label' => __( 'Close Button Color', 'notificationx' ), 'name' => 'exit_intent_close_color', 'type' => 'colorpicker', 'default' => '#666666' ],
                [ 'label' => __( 'Close Button Size', 'notificationx' ),  'name' => 'exit_intent_close_size',  'type' => 'number',      'default' => 20, 'description' => 'px' ],
        ];
    }

    /** ───────────────────────── Theme Two — Flash Sale w/ Countdown ───────────────────────── */
    private function theme_two_design_fields() {
        return [
                // Container / overlay / close
                [ 'label' => __( 'Popup Max Width', 'notificationx' ),          'name' => 'exit_intent_t2_max_width',     'type' => 'number',      'default' => 760, 'description' => 'px' ],
                [ 'label' => __( 'Border Radius', 'notificationx' ),            'name' => 'exit_intent_t2_border_radius', 'type' => 'number',      'default' => 12,  'description' => 'px' ],
                [ 'label' => __( 'Left Panel Background', 'notificationx' ),    'name' => 'exit_intent_t2_bg_color',      'type' => 'colorpicker', 'default' => '#ffffff' ],
                [ 'label' => __( 'Overlay Background Color', 'notificationx' ), 'name' => 'exit_intent_overlay_color',    'type' => 'colorpicker', 'default' => 'rgba(0,0,0,0.5)' ],
                [ 'label' => __( 'Close Button Color', 'notificationx' ),       'name' => 'exit_intent_close_color',      'type' => 'colorpicker', 'default' => '#666666' ],
                [ 'label' => __( 'Close Button Size', 'notificationx' ),        'name' => 'exit_intent_close_size',       'type' => 'number',      'default' => 20, 'description' => 'px' ],

                // Sale badge
                [ 'label' => __( 'Badge Background', 'notificationx' ), 'name' => 'exit_intent_t2_badge_bg',        'type' => 'colorpicker', 'default' => '#ffe4ec' ],
                [ 'label' => __( 'Badge Text Color', 'notificationx' ),'name' => 'exit_intent_t2_badge_color',     'type' => 'colorpicker', 'default' => '#e91e63' ],
                [ 'label' => __( 'Badge Font Size', 'notificationx' ), 'name' => 'exit_intent_t2_badge_font_size', 'type' => 'number',      'default' => 12, 'description' => 'px' ],

                // Headline
                [ 'label' => __( 'Headline Color', 'notificationx' ),       'name' => 'exit_intent_t2_headline_color',       'type' => 'colorpicker', 'default' => '#1a1a2e' ],
                [ 'label' => __( 'Headline Font Size', 'notificationx' ),   'name' => 'exit_intent_t2_headline_font_size',   'type' => 'number',      'default' => 56, 'description' => 'px' ],
                [ 'label' => __( 'Headline Font Weight', 'notificationx' ), 'name' => 'exit_intent_t2_headline_font_weight', 'type' => 'select',      'default' => '800',
                  'options' => $this->font_weight_options() ],

                // Description
                [ 'label' => __( 'Description Color', 'notificationx' ),     'name' => 'exit_intent_t2_desc_color',     'type' => 'colorpicker', 'default' => '#4a4a6a' ],
                [ 'label' => __( 'Description Font Size', 'notificationx' ), 'name' => 'exit_intent_t2_desc_font_size', 'type' => 'number',      'default' => 14, 'description' => 'px' ],

                // CTA button
                [ 'label' => __( 'Button Background', 'notificationx' ),     'name' => 'exit_intent_t2_btn_bg',            'type' => 'colorpicker', 'default' => '#e91e63' ],
                [ 'label' => __( 'Button Text Color', 'notificationx' ),     'name' => 'exit_intent_t2_btn_color',         'type' => 'colorpicker', 'default' => '#ffffff' ],
                [ 'label' => __( 'Button Border Radius', 'notificationx' ),  'name' => 'exit_intent_t2_btn_border_radius', 'type' => 'number',      'default' => 6, 'description' => 'px' ],
                [ 'label' => __( 'Button Font Size', 'notificationx' ),      'name' => 'exit_intent_t2_btn_font_size',     'type' => 'number',      'default' => 14, 'description' => 'px' ],
                [ 'label' => __( 'Button Font Weight', 'notificationx' ),    'name' => 'exit_intent_t2_btn_font_weight',   'type' => 'select',      'default' => '700',
                  'options' => $this->font_weight_options() ],

                // Dismiss link
                [ 'label' => __( 'Dismiss Text Color', 'notificationx' ),     'name' => 'exit_intent_t2_dismiss_color',     'type' => 'colorpicker', 'default' => '#9a9aa8' ],
                [ 'label' => __( 'Dismiss Font Size', 'notificationx' ),      'name' => 'exit_intent_t2_dismiss_font_size', 'type' => 'number',      'default' => 12, 'description' => 'px' ],
        ];
    }

    /** ───────────────────────── Theme Three — Coupon Offer ───────────────────────── */
    private function theme_three_design_fields() {
        return [
                // Container / overlay / close
                [ 'label' => __( 'Popup Max Width', 'notificationx' ),          'name' => 'exit_intent_t3_max_width',     'type' => 'number',      'default' => 460, 'description' => 'px' ],
                [ 'label' => __( 'Border Radius', 'notificationx' ),            'name' => 'exit_intent_t3_border_radius', 'type' => 'number',      'default' => 16,  'description' => 'px' ],
                [ 'label' => __( 'Background Color', 'notificationx' ),         'name' => 'exit_intent_t3_bg_color',      'type' => 'colorpicker', 'default' => '#ffffff' ],
                [ 'label' => __( 'Overlay Background Color', 'notificationx' ), 'name' => 'exit_intent_overlay_color',    'type' => 'colorpicker', 'default' => 'rgba(0,0,0,0.5)' ],
                [ 'label' => __( 'Close Button Color', 'notificationx' ),       'name' => 'exit_intent_close_color',      'type' => 'colorpicker', 'default' => '#666666' ],
                [ 'label' => __( 'Close Button Size', 'notificationx' ),        'name' => 'exit_intent_close_size',       'type' => 'number',      'default' => 20, 'description' => 'px' ],

                // Title
                [ 'label' => __( 'Title Color', 'notificationx' ),       'name' => 'exit_intent_t3_title_color',       'type' => 'colorpicker', 'default' => '#1a1a2e' ],
                [ 'label' => __( 'Title Font Size', 'notificationx' ),   'name' => 'exit_intent_t3_title_font_size',   'type' => 'number',      'default' => 26, 'description' => 'px' ],
                [ 'label' => __( 'Title Font Weight', 'notificationx' ), 'name' => 'exit_intent_t3_title_font_weight', 'type' => 'select',      'default' => '700',
                  'options' => $this->font_weight_options() ],

                // Subtitle
                [ 'label' => __( 'Subtitle Color', 'notificationx' ),     'name' => 'exit_intent_t3_subtitle_color',     'type' => 'colorpicker', 'default' => '#4a4a6a' ],
                [ 'label' => __( 'Subtitle Font Size', 'notificationx' ), 'name' => 'exit_intent_t3_subtitle_font_size', 'type' => 'number',      'default' => 14, 'description' => 'px' ],

                // Offer line
                [ 'label' => __( 'Offer Color', 'notificationx' ),       'name' => 'exit_intent_t3_offer_color',       'type' => 'colorpicker', 'default' => '#e91e63' ],
                [ 'label' => __( 'Offer Font Size', 'notificationx' ),   'name' => 'exit_intent_t3_offer_font_size',   'type' => 'number',      'default' => 18, 'description' => 'px' ],
                [ 'label' => __( 'Offer Font Weight', 'notificationx' ), 'name' => 'exit_intent_t3_offer_font_weight', 'type' => 'select',      'default' => '700',
                  'options' => $this->font_weight_options() ],

                // Coupon block
                [ 'label' => __( 'Coupon Block Background', 'notificationx' ),    'name' => 'exit_intent_t3_coupon_bg',            'type' => 'colorpicker', 'default' => '#fff7fb' ],
                [ 'label' => __( 'Coupon Text Color', 'notificationx' ),          'name' => 'exit_intent_t3_coupon_color',         'type' => 'colorpicker', 'default' => '#1a1a2e' ],
                [ 'label' => __( 'Coupon Font Size', 'notificationx' ),           'name' => 'exit_intent_t3_coupon_font_size',     'type' => 'number',      'default' => 14, 'description' => 'px' ],
                [ 'label' => __( 'Coupon Block Border Radius', 'notificationx' ), 'name' => 'exit_intent_t3_coupon_border_radius', 'type' => 'number',      'default' => 8,  'description' => 'px' ],

                // CTA button
                [ 'label' => __( 'Button Background', 'notificationx' ),     'name' => 'exit_intent_t3_btn_bg',            'type' => 'colorpicker', 'default' => '#e91e63' ],
                [ 'label' => __( 'Button Text Color', 'notificationx' ),     'name' => 'exit_intent_t3_btn_color',         'type' => 'colorpicker', 'default' => '#ffffff' ],
                [ 'label' => __( 'Button Border Radius', 'notificationx' ),  'name' => 'exit_intent_t3_btn_border_radius', 'type' => 'number',      'default' => 6, 'description' => 'px' ],
                [ 'label' => __( 'Button Font Size', 'notificationx' ),      'name' => 'exit_intent_t3_btn_font_size',     'type' => 'number',      'default' => 14, 'description' => 'px' ],
                [ 'label' => __( 'Button Font Weight', 'notificationx' ),    'name' => 'exit_intent_t3_btn_font_weight',   'type' => 'select',      'default' => '700',
                  'options' => $this->font_weight_options() ],

                // Dismiss link
                [ 'label' => __( 'Dismiss Text Color', 'notificationx' ),     'name' => 'exit_intent_t3_dismiss_color',     'type' => 'colorpicker', 'default' => '#9a9aa8' ],
                [ 'label' => __( 'Dismiss Font Size', 'notificationx' ),      'name' => 'exit_intent_t3_dismiss_font_size', 'type' => 'number',      'default' => 12, 'description' => 'px' ],
        ];
    }

    /** ───────────────────────── Theme Four — Video Popup ───────────────────────── */
    private function theme_four_design_fields() {
        return [
                // Container / overlay / close
                [ 'label' => __( 'Popup Max Width', 'notificationx' ),          'name' => 'exit_intent_t4_max_width',     'type' => 'number',      'default' => 520, 'description' => 'px' ],
                [ 'label' => __( 'Border Radius', 'notificationx' ),            'name' => 'exit_intent_t4_border_radius', 'type' => 'number',      'default' => 16,  'description' => 'px' ],
                [ 'label' => __( 'Background Color', 'notificationx' ),         'name' => 'exit_intent_t4_bg_color',      'type' => 'colorpicker', 'default' => '#f4ecff' ],
                [ 'label' => __( 'Overlay Background Color', 'notificationx' ), 'name' => 'exit_intent_overlay_color',    'type' => 'colorpicker', 'default' => 'rgba(0,0,0,0.5)' ],
                [ 'label' => __( 'Close Button Color', 'notificationx' ),       'name' => 'exit_intent_close_color',      'type' => 'colorpicker', 'default' => '#666666' ],
                [ 'label' => __( 'Close Button Size', 'notificationx' ),        'name' => 'exit_intent_close_size',       'type' => 'number',      'default' => 20, 'description' => 'px' ],

                // Badge
                [ 'label' => __( 'Badge Background', 'notificationx' ), 'name' => 'exit_intent_t4_badge_bg',        'type' => 'colorpicker', 'default' => '#ffffff' ],
                [ 'label' => __( 'Badge Text Color', 'notificationx' ),'name' => 'exit_intent_t4_badge_color',     'type' => 'colorpicker', 'default' => '#6B21A8' ],
                [ 'label' => __( 'Badge Font Size', 'notificationx' ), 'name' => 'exit_intent_t4_badge_font_size', 'type' => 'number',      'default' => 12, 'description' => 'px' ],

                // Title
                [ 'label' => __( 'Title Color', 'notificationx' ),       'name' => 'exit_intent_t4_title_color',       'type' => 'colorpicker', 'default' => '#1a1a2e' ],
                [ 'label' => __( 'Title Font Size', 'notificationx' ),   'name' => 'exit_intent_t4_title_font_size',   'type' => 'number',      'default' => 24, 'description' => 'px' ],
                [ 'label' => __( 'Title Font Weight', 'notificationx' ), 'name' => 'exit_intent_t4_title_font_weight', 'type' => 'select',      'default' => '700',
                  'options' => $this->font_weight_options() ],

                // Subtitle
                [ 'label' => __( 'Subtitle Color', 'notificationx' ),     'name' => 'exit_intent_t4_subtitle_color',     'type' => 'colorpicker', 'default' => '#4a4a6a' ],
                [ 'label' => __( 'Subtitle Font Size', 'notificationx' ), 'name' => 'exit_intent_t4_subtitle_font_size', 'type' => 'number',      'default' => 14, 'description' => 'px' ],

                // Video wrap + play icon
                [ 'label' => __( 'Video Wrapper Background', 'notificationx' ),    'name' => 'exit_intent_t4_video_bg',     'type' => 'colorpicker', 'default' => '#000000' ],
                [ 'label' => __( 'Video Wrapper Border Radius', 'notificationx' ), 'name' => 'exit_intent_t4_video_radius', 'type' => 'number',      'default' => 12, 'description' => 'px' ],
                [ 'label' => __( 'Play Icon Background', 'notificationx' ),        'name' => 'exit_intent_t4_play_bg',      'type' => 'colorpicker', 'default' => '#ffffff' ],
                [ 'label' => __( 'Play Icon Color', 'notificationx' ),             'name' => 'exit_intent_t4_play_color',   'type' => 'colorpicker', 'default' => '#1a1a2e' ],
        ];
    }

    /** ───────────────────────── Theme Five — Live Flash Sale ───────────────────────── */
    private function theme_five_design_fields() {
        return [
                // Container / overlay / close
                [ 'label' => __( 'Popup Max Width', 'notificationx' ),          'name' => 'exit_intent_t5_max_width',     'type' => 'number',      'default' => 760, 'description' => 'px' ],
                [ 'label' => __( 'Border Radius', 'notificationx' ),            'name' => 'exit_intent_t5_border_radius', 'type' => 'number',      'default' => 12,  'description' => 'px' ],
                [ 'label' => __( 'Left Panel Background', 'notificationx' ),    'name' => 'exit_intent_t5_bg_color',      'type' => 'colorpicker', 'default' => '#ffffff' ],
                [ 'label' => __( 'Overlay Background Color', 'notificationx' ), 'name' => 'exit_intent_overlay_color',    'type' => 'colorpicker', 'default' => 'rgba(0,0,0,0.6)' ],
                [ 'label' => __( 'Close Button Color', 'notificationx' ),       'name' => 'exit_intent_close_color',      'type' => 'colorpicker', 'default' => '#666666' ],
                [ 'label' => __( 'Close Button Size', 'notificationx' ),        'name' => 'exit_intent_close_size',       'type' => 'number',      'default' => 20, 'description' => 'px' ],

                // Title
                [ 'label' => __( 'Title Color', 'notificationx' ),       'name' => 'exit_intent_t5_title_color',       'type' => 'colorpicker', 'default' => '#1a1a2e' ],
                [ 'label' => __( 'Title Font Size', 'notificationx' ),   'name' => 'exit_intent_t5_title_font_size',   'type' => 'number',      'default' => 18, 'description' => 'px' ],
                [ 'label' => __( 'Title Font Weight', 'notificationx' ), 'name' => 'exit_intent_t5_title_font_weight', 'type' => 'select',      'default' => '700',
                  'options' => $this->font_weight_options() ],

                // Headline
                [ 'label' => __( 'Headline Color', 'notificationx' ),       'name' => 'exit_intent_t5_headline_color',       'type' => 'colorpicker', 'default' => '#1a1a2e' ],
                [ 'label' => __( 'Headline Font Size', 'notificationx' ),   'name' => 'exit_intent_t5_headline_font_size',   'type' => 'number',      'default' => 56, 'description' => 'px' ],
                [ 'label' => __( 'Headline Font Weight', 'notificationx' ), 'name' => 'exit_intent_t5_headline_font_weight', 'type' => 'select',      'default' => '800',
                  'options' => $this->font_weight_options() ],

                // Description
                [ 'label' => __( 'Description Color', 'notificationx' ),     'name' => 'exit_intent_t5_desc_color',     'type' => 'colorpicker', 'default' => '#4a4a6a' ],
                [ 'label' => __( 'Description Font Size', 'notificationx' ), 'name' => 'exit_intent_t5_desc_font_size', 'type' => 'number',      'default' => 14, 'description' => 'px' ],

                // Countdown label + numbers + unit labels
                [ 'label' => __( 'Countdown Label Color', 'notificationx' ),         'name' => 'exit_intent_t5_cd_label_color',     'type' => 'colorpicker', 'default' => '#1a1a2e',
                  'rules' => Rules::is( 'exit_intent_t5_show_timer', true ) ],
                [ 'label' => __( 'Countdown Label Font Size', 'notificationx' ),     'name' => 'exit_intent_t5_cd_label_font_size', 'type' => 'number',      'default' => 12, 'description' => 'px',
                  'rules' => Rules::is( 'exit_intent_t5_show_timer', true ) ],
                [ 'label' => __( 'Countdown Number Background', 'notificationx' ),   'name' => 'exit_intent_t5_cd_num_bg',          'type' => 'colorpicker', 'default' => '#fff0f5',
                  'rules' => Rules::is( 'exit_intent_t5_show_timer', true ) ],
                [ 'label' => __( 'Countdown Number Color', 'notificationx' ),        'name' => 'exit_intent_t5_cd_num_color',       'type' => 'colorpicker', 'default' => '#e91e63',
                  'rules' => Rules::is( 'exit_intent_t5_show_timer', true ) ],
                [ 'label' => __( 'Countdown Number Font Size', 'notificationx' ),    'name' => 'exit_intent_t5_cd_num_font_size',   'type' => 'number',      'default' => 22, 'description' => 'px',
                  'rules' => Rules::is( 'exit_intent_t5_show_timer', true ) ],
                [ 'label' => __( 'Countdown Number Border Radius', 'notificationx' ),'name' => 'exit_intent_t5_cd_num_radius',      'type' => 'number',      'default' => 6,  'description' => 'px',
                  'rules' => Rules::is( 'exit_intent_t5_show_timer', true ) ],
                [ 'label' => __( 'Countdown Unit Label Color', 'notificationx' ),    'name' => 'exit_intent_t5_cd_unit_color',      'type' => 'colorpicker', 'default' => '#4a4a6a',
                  'rules' => Rules::is( 'exit_intent_t5_show_timer', true ) ],
                [ 'label' => __( 'Countdown Unit Label Font Size', 'notificationx' ),'name' => 'exit_intent_t5_cd_unit_font_size',  'type' => 'number',      'default' => 11, 'description' => 'px',
                  'rules' => Rules::is( 'exit_intent_t5_show_timer', true ) ],

                // CTA button
                [ 'label' => __( 'Button Background', 'notificationx' ),     'name' => 'exit_intent_t5_btn_bg',            'type' => 'colorpicker', 'default' => '#e91e63' ],
                [ 'label' => __( 'Button Text Color', 'notificationx' ),     'name' => 'exit_intent_t5_btn_color',         'type' => 'colorpicker', 'default' => '#ffffff' ],
                [ 'label' => __( 'Button Border Radius', 'notificationx' ),  'name' => 'exit_intent_t5_btn_border_radius', 'type' => 'number',      'default' => 6, 'description' => 'px' ],
                [ 'label' => __( 'Button Font Size', 'notificationx' ),      'name' => 'exit_intent_t5_btn_font_size',     'type' => 'number',      'default' => 14, 'description' => 'px' ],
                [ 'label' => __( 'Button Font Weight', 'notificationx' ),    'name' => 'exit_intent_t5_btn_font_weight',   'type' => 'select',      'default' => '700',
                  'options' => $this->font_weight_options() ],

                // Dismiss link
                [ 'label' => __( 'Dismiss Text Color', 'notificationx' ),     'name' => 'exit_intent_t5_dismiss_color',     'type' => 'colorpicker', 'default' => '#9a9aa8' ],
                [ 'label' => __( 'Dismiss Font Size', 'notificationx' ),      'name' => 'exit_intent_t5_dismiss_font_size', 'type' => 'number',      'default' => 12, 'description' => 'px' ],
        ];
    }

    /** ───────────────────────── Theme Six — Product Countdown ───────────────────────── */
    private function theme_six_design_fields() {
        return [
                // Container / overlay / close
                [ 'label' => __( 'Popup Max Width', 'notificationx' ),          'name' => 'exit_intent_t6_max_width',     'type' => 'number',      'default' => 600, 'description' => 'px' ],
                [ 'label' => __( 'Border Radius', 'notificationx' ),            'name' => 'exit_intent_t6_border_radius', 'type' => 'number',      'default' => 12,  'description' => 'px' ],
                [ 'label' => __( 'Background Start Color', 'notificationx' ),   'name' => 'exit_intent_t6_bg_start',      'type' => 'colorpicker', 'default' => '#ffffff',
                  'help'  => __( 'Center color of the radial background gradient.', 'notificationx' ) ],
                [ 'label' => __( 'Background Mid Color', 'notificationx' ),     'name' => 'exit_intent_t6_bg_mid',        'type' => 'colorpicker', 'default' => '#fdf2f8' ],
                [ 'label' => __( 'Background End Color', 'notificationx' ),     'name' => 'exit_intent_t6_bg_end',        'type' => 'colorpicker', 'default' => '#f5f3ff',
                  'help'  => __( 'Outer color of the radial background gradient.', 'notificationx' ) ],
                [ 'label' => __( 'Overlay Background Color', 'notificationx' ), 'name' => 'exit_intent_overlay_color',    'type' => 'colorpicker', 'default' => 'rgba(0,0,0,0.5)' ],
                [ 'label' => __( 'Close Button Color', 'notificationx' ),       'name' => 'exit_intent_close_color',      'type' => 'colorpicker', 'default' => '#1f2937' ],
                [ 'label' => __( 'Close Button Size', 'notificationx' ),        'name' => 'exit_intent_close_size',       'type' => 'number',      'default' => 20, 'description' => 'px' ],

                // Title
                [ 'label' => __( 'Title Color', 'notificationx' ),       'name' => 'exit_intent_t6_title_color',       'type' => 'colorpicker', 'default' => '#1f2937' ],
                [ 'label' => __( 'Title Font Size', 'notificationx' ),   'name' => 'exit_intent_t6_title_font_size',   'type' => 'number',      'default' => 36, 'description' => 'px' ],
                [ 'label' => __( 'Title Font Weight', 'notificationx' ), 'name' => 'exit_intent_t6_title_font_weight', 'type' => 'select',      'default' => '700',
                  'options' => $this->font_weight_options() ],

                // Countdown label + numbers + unit labels
                [ 'label' => __( 'Countdown Label Color', 'notificationx' ),         'name' => 'exit_intent_t6_cd_label_color',     'type' => 'colorpicker', 'default' => '#4b5563',
                  'rules' => Rules::is( 'exit_intent_t6_show_timer', true ) ],
                [ 'label' => __( 'Countdown Label Font Size', 'notificationx' ),     'name' => 'exit_intent_t6_cd_label_font_size', 'type' => 'number',      'default' => 18, 'description' => 'px',
                  'rules' => Rules::is( 'exit_intent_t6_show_timer', true ) ],
                [ 'label' => __( 'Countdown Number Background', 'notificationx' ),   'name' => 'exit_intent_t6_cd_num_bg',          'type' => 'colorpicker', 'default' => '#f3e8f2',
                  'rules' => Rules::is( 'exit_intent_t6_show_timer', true ) ],
                [ 'label' => __( 'Countdown Number Color', 'notificationx' ),        'name' => 'exit_intent_t6_cd_num_color',       'type' => 'colorpicker', 'default' => '#374151',
                  'rules' => Rules::is( 'exit_intent_t6_show_timer', true ) ],
                [ 'label' => __( 'Countdown Number Font Size', 'notificationx' ),    'name' => 'exit_intent_t6_cd_num_font_size',   'type' => 'number',      'default' => 24, 'description' => 'px',
                  'rules' => Rules::is( 'exit_intent_t6_show_timer', true ) ],
                [ 'label' => __( 'Countdown Number Border Radius', 'notificationx' ),'name' => 'exit_intent_t6_cd_num_radius',      'type' => 'number',      'default' => 2,  'description' => 'px',
                  'rules' => Rules::is( 'exit_intent_t6_show_timer', true ) ],
                [ 'label' => __( 'Countdown Unit Label Color', 'notificationx' ),    'name' => 'exit_intent_t6_cd_unit_color',      'type' => 'colorpicker', 'default' => '#9f1239',
                  'rules' => Rules::is( 'exit_intent_t6_show_timer', true ) ],
                [ 'label' => __( 'Countdown Unit Label Font Size', 'notificationx' ),'name' => 'exit_intent_t6_cd_unit_font_size',  'type' => 'number',      'default' => 12, 'description' => 'px',
                  'rules' => Rules::is( 'exit_intent_t6_show_timer', true ) ],

                // CTA button
                [ 'label' => __( 'Button Background', 'notificationx' ),     'name' => 'exit_intent_t6_btn_bg',            'type' => 'colorpicker', 'default' => '#845e7c' ],
                [ 'label' => __( 'Button Text Color', 'notificationx' ),     'name' => 'exit_intent_t6_btn_color',         'type' => 'colorpicker', 'default' => '#ffffff' ],
                [ 'label' => __( 'Button Border Radius', 'notificationx' ),  'name' => 'exit_intent_t6_btn_border_radius', 'type' => 'number',      'default' => 4, 'description' => 'px' ],
                [ 'label' => __( 'Button Font Size', 'notificationx' ),      'name' => 'exit_intent_t6_btn_font_size',     'type' => 'number',      'default' => 24, 'description' => 'px' ],
                [ 'label' => __( 'Button Font Weight', 'notificationx' ),    'name' => 'exit_intent_t6_btn_font_weight',   'type' => 'select',      'default' => '700',
                  'options' => $this->font_weight_options() ],
        ];
    }

    /** ───────────────────────── Theme Seven — Lead Capture w/ Image ───────────────────────── */
    private function theme_seven_design_fields() {
        $font_family_options = GlobalFields::get_instance()->normalize_fields( [
            'inherit'                                          => __( 'Default (inherit)', 'notificationx' ),
            "'Playfair Display', Georgia, serif"               => __( 'Playfair Display (serif)', 'notificationx' ),
            "Georgia, 'Times New Roman', serif"                => __( 'Georgia (serif)', 'notificationx' ),
            "'Times New Roman', Times, serif"                  => __( 'Times New Roman (serif)', 'notificationx' ),
            "Inter, 'Helvetica Neue', Arial, sans-serif"       => __( 'Inter (sans-serif)', 'notificationx' ),
            "'Helvetica Neue', Helvetica, Arial, sans-serif"   => __( 'Helvetica (sans-serif)', 'notificationx' ),
        ] );

        return [
                // Container / overlay / close
                [ 'label' => __( 'Popup Max Width', 'notificationx' ),          'name' => 'exit_intent_t7_max_width',     'type' => 'number',      'default' => 750, 'description' => 'px' ],
                [ 'label' => __( 'Border Radius', 'notificationx' ),            'name' => 'exit_intent_t7_border_radius', 'type' => 'number',      'default' => 12,  'description' => 'px' ],
                [ 'label' => __( 'Background Color', 'notificationx' ),         'name' => 'exit_intent_t7_bg_color',      'type' => 'colorpicker', 'default' => '#413532',
                  'help'  => __( 'Right (content) panel background.', 'notificationx' ) ],
                [ 'label' => __( 'Image Panel Background', 'notificationx' ),   'name' => 'exit_intent_t7_image_bg',      'type' => 'colorpicker', 'default' => '#534542',
                  'help'  => __( 'Shown behind the image (visible only if the image leaves transparent areas or fails to load).', 'notificationx' ) ],
                [ 'label' => __( 'Overlay Background Color', 'notificationx' ), 'name' => 'exit_intent_overlay_color',    'type' => 'colorpicker', 'default' => 'rgba(0,0,0,0.5)' ],
                [ 'label' => __( 'Close Button Color', 'notificationx' ),       'name' => 'exit_intent_close_color',      'type' => 'colorpicker', 'default' => '#f3d5a2' ],
                [ 'label' => __( 'Close Button Size', 'notificationx' ),        'name' => 'exit_intent_close_size',       'type' => 'number',      'default' => 22, 'description' => 'px' ],

                // Headline
                [ 'label' => __( 'Headline Color', 'notificationx' ),       'name' => 'exit_intent_t7_headline_color',       'type' => 'colorpicker', 'default' => '#f3d5a2' ],
                [ 'label' => __( 'Headline Font Size', 'notificationx' ),   'name' => 'exit_intent_t7_headline_font_size',   'type' => 'number',      'default' => 30, 'description' => 'px' ],
                [ 'label' => __( 'Headline Font Weight', 'notificationx' ), 'name' => 'exit_intent_t7_headline_font_weight', 'type' => 'select',      'default' => '700',
                  'options' => $this->font_weight_options() ],
                [ 'label' => __( 'Headline Font Family', 'notificationx' ), 'name' => 'exit_intent_t7_headline_font_family', 'type' => 'select',      'default' => "'Playfair Display', Georgia, serif",
                  'options' => $font_family_options ],

                // Discount banner
                [ 'label' => __( 'Discount Banner Background', 'notificationx' ),    'name' => 'exit_intent_t7_discount_bg',           'type' => 'colorpicker', 'default' => 'rgba(255,255,255,0.1)' ],
                [ 'label' => __( 'Discount Banner Border Color', 'notificationx' ),  'name' => 'exit_intent_t7_discount_border',       'type' => 'colorpicker', 'default' => 'rgba(255,255,255,0.05)' ],
                [ 'label' => __( 'Discount Banner Text Color', 'notificationx' ),    'name' => 'exit_intent_t7_discount_color',        'type' => 'colorpicker', 'default' => '#f3d5a2' ],
                [ 'label' => __( 'Discount Banner Font Size', 'notificationx' ),     'name' => 'exit_intent_t7_discount_font_size',    'type' => 'number',      'default' => 16, 'description' => 'px' ],
                [ 'label' => __( 'Discount Banner Border Radius', 'notificationx' ), 'name' => 'exit_intent_t7_discount_radius',       'type' => 'number',      'default' => 2,  'description' => 'px' ],

                // Description
                [ 'label' => __( 'Description Color', 'notificationx' ),     'name' => 'exit_intent_t7_desc_color',     'type' => 'colorpicker', 'default' => 'rgba(243,213,162,0.9)' ],
                [ 'label' => __( 'Description Font Size', 'notificationx' ), 'name' => 'exit_intent_t7_desc_font_size', 'type' => 'number',      'default' => 16, 'description' => 'px' ],

                // Email input
                [ 'label' => __( 'Input Background Color', 'notificationx' ),    'name' => 'exit_intent_t7_input_bg',           'type' => 'colorpicker', 'default' => '#ffffff' ],
                [ 'label' => __( 'Input Border Color', 'notificationx' ),        'name' => 'exit_intent_t7_input_border_color', 'type' => 'colorpicker', 'default' => '#6b7280' ],
                [ 'label' => __( 'Input Border Radius', 'notificationx' ),       'name' => 'exit_intent_t7_input_border_radius','type' => 'number',      'default' => 0, 'description' => 'px' ],
                [ 'label' => __( 'Input Text Color', 'notificationx' ),          'name' => 'exit_intent_t7_input_text_color',   'type' => 'colorpicker', 'default' => '#1f2937' ],

                // CTA button
                [ 'label' => __( 'Button Background', 'notificationx' ),     'name' => 'exit_intent_t7_btn_bg',            'type' => 'colorpicker', 'default' => '#f3d5a2' ],
                [ 'label' => __( 'Button Text Color', 'notificationx' ),     'name' => 'exit_intent_t7_btn_color',         'type' => 'colorpicker', 'default' => '#4d3e3e' ],
                [ 'label' => __( 'Button Border Radius', 'notificationx' ),  'name' => 'exit_intent_t7_btn_border_radius', 'type' => 'number',      'default' => 0, 'description' => 'px' ],
                [ 'label' => __( 'Button Font Size', 'notificationx' ),      'name' => 'exit_intent_t7_btn_font_size',     'type' => 'number',      'default' => 14, 'description' => 'px' ],
                [ 'label' => __( 'Button Font Weight', 'notificationx' ),    'name' => 'exit_intent_t7_btn_font_weight',   'type' => 'select',      'default' => '700',
                  'options' => $this->font_weight_options() ],
        ];
    }

    public function customize_fields( $fields ) {
        // Hide standard timing/behaviour irrelevant to exit intent
        foreach ( [ 'delay_between', 'display_for' ] as $key ) {
            if ( isset( $fields['timing']['fields'][ $key ] ) ) {
                $fields['timing']['fields'][ $key ] = Rules::is( 'source', $this->id, true, $fields['timing']['fields'][ $key ] );
            }
        }
        foreach ( [ 'behaviour', 'sound_section', 'queue_management', 'appearance' ] as $key ) {
            if ( isset( $fields[ $key ] ) ) {
                $fields[ $key ] = Rules::is( 'source', $this->id, true, $fields[ $key ] );
            }
        }

        // Add center position option
        if ( isset( $fields['appearance']['fields']['position']['options'] ) ) {
            $fields['appearance']['fields']['position']['options']['center'] = [
                'label' => __( 'Center', 'notificationx' ),
                'value' => 'center',
                'rules' => Rules::is( 'source', $this->id ),
            ];
        }

        $fields['exit_intent_settings'] = [
            'label'    => __( 'Exit Intent Settings', 'notificationx' ),
            'name'     => 'exit_intent_settings',
            'type'     => 'section',
            'priority' => 15,
            'rules'    => Rules::is( 'source', $this->id ),
            'fields'   => [
                'show_close_button' => [
                    'label'   => __( 'Show Close Button', 'notificationx' ),
                    'name'    => 'show_close_button',
                    'type'    => 'toggle',
                    'default' => true,
                ],
                'exit_intent_sensitivity' => [
                    'label'   => __( 'Trigger Sensitivity', 'notificationx' ),
                    'name'    => 'exit_intent_sensitivity',
                    'type'    => 'select',
                    'default' => '20',
                    'help'    => __( 'Distance from top of viewport (px) that triggers the popup.', 'notificationx' ),
                    'options' => GlobalFields::get_instance()->normalize_fields( [
                        '10' => __( 'High (10px)', 'notificationx' ),
                        '20' => __( 'Medium (20px)', 'notificationx' ),
                        '50' => __( 'Low (50px)', 'notificationx' ),
                    ] ),
                ],
                'exit_intent_cookie_days' => [
                    'label'       => __( 'Do Not Show Again For', 'notificationx' ),
                    'name'        => 'exit_intent_cookie_days',
                    'type'        => 'number',
                    'default'     => 7,
                    'description' => __( 'days', 'notificationx' ),
                    'help'        => __( 'Once dismissed, hide the popup for this many days.', 'notificationx' ),
                ],
                'exit_intent_mobile_disable' => [
                    'label'   => __( 'Disable on Mobile', 'notificationx' ),
                    'name'    => 'exit_intent_mobile_disable',
                    'type'    => 'toggle',
                    'default' => true,
                    'help'    => __( 'Exit intent detection is unreliable on touch devices.', 'notificationx' ),
                ],
            ],
        ];

        return $fields;
    }

    public function doc() {
        return sprintf(
            __( '<p>Display a targeted popup when visitors are about to leave your site to recover abandoning users. Need help? Check out our <a href="%1$s" target="_blank">documentation</a>.</p>', 'notificationx' ),
            $this->doc_link
        );
    }
}
