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
                'source'   => NOTIFICATIONX_ADMIN_URL . 'images/themes/theme-blank.jpg',
                'defaults' => [
                    'exit_intent_title'        => __( 'Wait! Before You Go...', 'notificationx' ),
                    'exit_intent_subtitle'     => __( "We'd love to understand what's holding you back", 'notificationx' ),
                    'exit_intent_question'     => __( "What's stopping you from getting {product} today?", 'notificationx' ),
                    'exit_intent_product_name' => __( 'our product', 'notificationx' ),
                    'exit_intent_button_text'  => __( 'SUBMIT', 'notificationx' ),
                    'exit_intent_show_name'    => true,
                    'exit_intent_show_email'   => true,
                    'exit_intent_show_reason'  => true,
                    'exit_intent_name_label'   => __( 'Name *', 'notificationx' ),
                    'exit_intent_email_label'  => __( 'Enter Your Email *', 'notificationx' ),
                    'position'                 => 'center',
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

        $fields['exit_intent_content_section'] = [
            'label'    => __( 'Exit Intent Content', 'notificationx' ),
            'name'     => 'exit_intent_content_section',
            'type'     => 'section',
            'priority' => 5,
            'rules'    => Rules::is( 'source', $this->id ),
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
                [
                    'label'    => __( 'Question', 'notificationx' ),
                    'name'     => 'exit_intent_question',
                    'type'     => 'text',
                    'priority' => 30,
                    'default'  => __( "What's stopping you from getting {product} today?", 'notificationx' ),
                    'help'     => __( 'Use {product} as a placeholder for the product name below.', 'notificationx' ),
                ],
                [
                    'label'    => __( 'Product / Brand Name', 'notificationx' ),
                    'name'     => 'exit_intent_product_name',
                    'type'     => 'text',
                    'priority' => 40,
                    'default'  => __( 'our product', 'notificationx' ),
                    'help'     => __( 'Replaces {product} in the question above.', 'notificationx' ),
                ],

                // ── Reason dropdown ──────────────────────────────────────
                [
                    'label'    => __( 'Show Reason Dropdown', 'notificationx' ),
                    'name'     => 'exit_intent_show_reason',
                    'type'     => 'toggle',
                    'default'  => true,
                    'priority' => 50,
                ],
                [
                    'label'    => __( 'Dropdown Placeholder', 'notificationx' ),
                    'name'     => 'exit_intent_reason_placeholder',
                    'type'     => 'text',
                    'default'  => __( 'Select Reason', 'notificationx' ),
                    'priority' => 55,
                    'rules'    => Rules::is( 'exit_intent_show_reason', true ),
                ],
                [
                    'label'    => __( 'Reason Options', 'notificationx' ),
                    'name'     => 'exit_intent_reasons',
                    'type'     => 'repeater',
                    'priority' => 60,
                    'rules'    => Rules::is( 'exit_intent_show_reason', true ),
                    'button'   => [ 'label' => __( 'Add Reason', 'notificationx' ) ],
                    'fields'   => [
                        [
                            'label'   => __( 'Reason Text', 'notificationx' ),
                            'name'    => 'reason_label',
                            'type'    => 'text',
                            'default' => '',
                        ],
                    ],
                    'default' => [
                        [ 'reason_label' => __( 'It\'s too expensive', 'notificationx' ) ],
                        [ 'reason_label' => __( 'I need more information', 'notificationx' ) ],
                        [ 'reason_label' => __( 'I\'m not ready yet', 'notificationx' ) ],
                        [ 'reason_label' => __( 'Found a better alternative', 'notificationx' ) ],
                    ],
                ],

                // ── Form fields ──────────────────────────────────────────
                [
                    'label'    => __( 'Show Name Field', 'notificationx' ),
                    'name'     => 'exit_intent_show_name',
                    'type'     => 'toggle',
                    'default'  => true,
                    'priority' => 70,
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
                ],
                [
                    'label'    => __( 'Email Placeholder', 'notificationx' ),
                    'name'     => 'exit_intent_email_label',
                    'type'     => 'text',
                    'default'  => __( 'Enter Your Email *', 'notificationx' ),
                    'priority' => 85,
                    'rules'    => Rules::is( 'exit_intent_show_email', true ),
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
        // Hide irrelevant standard design sections for this source
        foreach ( [ 'advance_design_section', 'image-appearance' ] as $key ) {
            if ( isset( $fields[ $key ] ) ) {
                $fields[ $key ] = Rules::is( 'source', $this->id, true, $fields[ $key ] );
            }
        }

        // ── Exit Intent Advanced Design ──────────────────────────────────
        $fields['exit_intent_design_section'] = [
            'label'    => __( 'Advanced Design', 'notificationx' ),
            'name'     => 'exit_intent_design_section',
            'type'     => 'section',
            'priority' => 5,
            'rules'    => Rules::logicalRule( [
                Rules::is( 'source', $this->id ),
                Rules::is( 'advance_edit', true ),
            ] ),
            'fields'   => [

                // Container
                [
                    'label'       => __( 'Popup Max Width', 'notificationx' ),
                    'name'        => 'exit_intent_max_width',
                    'type'        => 'number',
                    'default'     => 540,
                    'description' => 'px',
                ],
                [
                    'label'       => __( 'Border Radius', 'notificationx' ),
                    'name'        => 'exit_intent_border_radius',
                    'type'        => 'number',
                    'default'     => 12,
                    'description' => 'px',
                ],
                [
                    'label'   => __( 'Background Color', 'notificationx' ),
                    'name'    => 'exit_intent_bg_color',
                    'type'    => 'colorpicker',
                    'default' => '#EDE7FF',
                ],
                [
                    'label'   => __( 'Overlay Background Color', 'notificationx' ),
                    'name'    => 'exit_intent_overlay_color',
                    'type'    => 'colorpicker',
                    'default' => 'rgba(0,0,0,0.5)',
                    'help'    => __( 'Color of the backdrop behind the popup.', 'notificationx' ),
                ],
                [
                    'label'   => __( 'Show Background Pattern', 'notificationx' ),
                    'name'    => 'exit_intent_show_pattern',
                    'type'    => 'toggle',
                    'default' => true,
                ],
                [
                    'label'   => __( 'Pattern Color', 'notificationx' ),
                    'name'    => 'exit_intent_pattern_color',
                    'type'    => 'colorpicker',
                    'default' => '#D4C3FF',
                    'rules'   => Rules::is( 'exit_intent_show_pattern', true ),
                ],

                // Typography — Title
                [
                    'label'       => __( 'Title Color', 'notificationx' ),
                    'name'        => 'exit_intent_title_color',
                    'type'        => 'colorpicker',
                    'default'     => '#1a1a2e',
                ],
                [
                    'label'       => __( 'Title Font Size', 'notificationx' ),
                    'name'        => 'exit_intent_title_font_size',
                    'type'        => 'number',
                    'default'     => 26,
                    'description' => 'px',
                ],
                [
                    'label'   => __( 'Title Font Weight', 'notificationx' ),
                    'name'    => 'exit_intent_title_font_weight',
                    'type'    => 'select',
                    'default' => '700',
                    'options' => GlobalFields::get_instance()->normalize_fields( [
                        '400' => __( 'Normal (400)', 'notificationx' ),
                        '600' => __( 'Semi Bold (600)', 'notificationx' ),
                        '700' => __( 'Bold (700)', 'notificationx' ),
                        '800' => __( 'Extra Bold (800)', 'notificationx' ),
                    ] ),
                ],

                // Typography — Subtitle
                [
                    'label'   => __( 'Subtitle Color', 'notificationx' ),
                    'name'    => 'exit_intent_subtitle_color',
                    'type'    => 'colorpicker',
                    'default' => '#4a4a6a',
                ],
                [
                    'label'       => __( 'Subtitle Font Size', 'notificationx' ),
                    'name'        => 'exit_intent_subtitle_font_size',
                    'type'        => 'number',
                    'default'     => 14,
                    'description' => 'px',
                ],

                // Typography — Question
                [
                    'label'   => __( 'Question Color', 'notificationx' ),
                    'name'    => 'exit_intent_question_color',
                    'type'    => 'colorpicker',
                    'default' => '#1a1a2e',
                ],
                [
                    'label'       => __( 'Question Font Size', 'notificationx' ),
                    'name'        => 'exit_intent_question_font_size',
                    'type'        => 'number',
                    'default'     => 15,
                    'description' => 'px',
                ],

                // Input fields
                [
                    'label'   => __( 'Input Background Color', 'notificationx' ),
                    'name'    => 'exit_intent_input_bg',
                    'type'    => 'colorpicker',
                    'default' => '#ffffff',
                ],
                [
                    'label'   => __( 'Input Border Color', 'notificationx' ),
                    'name'    => 'exit_intent_input_border_color',
                    'type'    => 'colorpicker',
                    'default' => '#dddddd',
                ],
                [
                    'label'   => __( 'Input Focus Border Color', 'notificationx' ),
                    'name'    => 'exit_intent_input_focus_color',
                    'type'    => 'colorpicker',
                    'default' => '#7600ff',
                ],
                [
                    'label'       => __( 'Input Border Radius', 'notificationx' ),
                    'name'        => 'exit_intent_input_border_radius',
                    'type'        => 'number',
                    'default'     => 6,
                    'description' => 'px',
                ],
                [
                    'label'   => __( 'Input Text Color', 'notificationx' ),
                    'name'    => 'exit_intent_input_text_color',
                    'type'    => 'colorpicker',
                    'default' => '#333333',
                ],
                [
                    'label'   => __( 'Input Placeholder Color', 'notificationx' ),
                    'name'    => 'exit_intent_placeholder_color',
                    'type'    => 'colorpicker',
                    'default' => '#999999',
                ],

                // Button
                [
                    'label'   => __( 'Button Background Color', 'notificationx' ),
                    'name'    => 'exit_intent_btn_bg',
                    'type'    => 'colorpicker',
                    'default' => '#6B21A8',
                ],
                [
                    'label'   => __( 'Button Hover Background', 'notificationx' ),
                    'name'    => 'exit_intent_btn_hover_bg',
                    'type'    => 'colorpicker',
                    'default' => '#581C87',
                ],
                [
                    'label'   => __( 'Button Text Color', 'notificationx' ),
                    'name'    => 'exit_intent_btn_color',
                    'type'    => 'colorpicker',
                    'default' => '#ffffff',
                ],
                [
                    'label'       => __( 'Button Border Radius', 'notificationx' ),
                    'name'        => 'exit_intent_btn_border_radius',
                    'type'        => 'number',
                    'default'     => 6,
                    'description' => 'px',
                ],
                [
                    'label'       => __( 'Button Font Size', 'notificationx' ),
                    'name'        => 'exit_intent_btn_font_size',
                    'type'        => 'number',
                    'default'     => 14,
                    'description' => 'px',
                ],
                [
                    'label'   => __( 'Button Font Weight', 'notificationx' ),
                    'name'    => 'exit_intent_btn_font_weight',
                    'type'    => 'select',
                    'default' => '700',
                    'options' => GlobalFields::get_instance()->normalize_fields( [
                        '400' => __( 'Normal', 'notificationx' ),
                        '600' => __( 'Semi Bold', 'notificationx' ),
                        '700' => __( 'Bold', 'notificationx' ),
                    ] ),
                ],

                // Close button
                [
                    'label'   => __( 'Close Button Color', 'notificationx' ),
                    'name'    => 'exit_intent_close_color',
                    'type'    => 'colorpicker',
                    'default' => '#666666',
                ],
                [
                    'label'       => __( 'Close Button Size', 'notificationx' ),
                    'name'        => 'exit_intent_close_size',
                    'type'        => 'number',
                    'default'     => 20,
                    'description' => 'px',
                ],
            ],
        ];

        return $fields;
    }

    public function customize_fields( $fields ) {
        // Hide standard timing/behaviour irrelevant to exit intent
        foreach ( [ 'delay_between', 'display_for' ] as $key ) {
            if ( isset( $fields['timing']['fields'][ $key ] ) ) {
                $fields['timing']['fields'][ $key ] = Rules::is( 'source', $this->id, true, $fields['timing']['fields'][ $key ] );
            }
        }
        foreach ( [ 'behaviour', 'sound_section', 'queue_management' ] as $key ) {
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
