<?php
/**
 * NotificationX Elementor Countdown Timer Widget
 *
 * @package NotificationX\Extensions\Elementor
 */

namespace NotificationX\Extensions\Elementor;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Countdown Timer widget for Elementor.
 */
class CountdownWidget extends Widget_Base {

    public function get_name() {
        return 'nx-countdown-timer';
    }

    public function get_title() {
        return esc_html__( 'NotificationX Countdown Timer', 'notificationx' );
    }

    public function get_icon() {
        return 'eicon-countdown';
    }

    public function get_categories() {
        return [ 'notificationx' ];
    }

    public function get_keywords() {
        return [ 'countdown', 'timer', 'notificationx', 'nx', 'sale', 'offer' ];
    }

    public function get_script_depends() {
        return [ 'nx-countdown' ];
    }

    public function get_style_depends() {
        return [ 'nx-countdown' ];
    }

    // ── Controls ──────────────────────────────────────────────────────────

    protected function register_controls() {

        // ── Timer Settings ────────────────────────────────────────────────
        $this->start_controls_section(
            'section_timer_settings',
            [
                'label' => esc_html__( 'Timer Settings', 'notificationx' ),
            ]
        );

        $this->add_control(
            'nx_countdown_type',
            [
                'label'   => esc_html__( 'Type', 'notificationx' ),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'due_date'  => esc_html__( 'Default (Due Date)', 'notificationx' ),
                    'evergreen' => esc_html__( 'Evergreen Timer', 'notificationx' ),
                ],
                'default' => 'due_date',
            ]
        );

        $this->add_control(
            'nx_countdown_due_time',
            [
                'label'       => esc_html__( 'Countdown Due Date', 'notificationx' ),
                'type'        => Controls_Manager::DATE_TIME,
                'default'     => gmdate( 'Y-m-d', strtotime( '+7 days' ) ),
                'description' => esc_html__( 'Set the due date and time.', 'notificationx' ),
                'condition'   => [ 'nx_countdown_type' => 'due_date' ],
            ]
        );

        $this->add_control(
            'nx_evergreen_hours',
            [
                'label'       => esc_html__( 'Hours', 'notificationx' ),
                'type'        => Controls_Manager::NUMBER,
                'default'     => 11,
                'placeholder' => esc_html__( 'Hours', 'notificationx' ),
                'condition'   => [ 'nx_countdown_type' => 'evergreen' ],
            ]
        );

        $this->add_control(
            'nx_evergreen_minutes',
            [
                'label'       => esc_html__( 'Minutes', 'notificationx' ),
                'type'        => Controls_Manager::NUMBER,
                'default'     => 59,
                'placeholder' => esc_html__( 'Minutes', 'notificationx' ),
                'condition'   => [ 'nx_countdown_type' => 'evergreen' ],
            ]
        );

        $this->add_control(
            'nx_evergreen_recurring',
            [
                'label'        => esc_html__( 'Recurring Countdown', 'notificationx' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => [ 'nx_countdown_type' => 'evergreen' ],
            ]
        );

        $this->add_control(
            'nx_evergreen_recurring_restart_after',
            [
                'label'       => esc_html__( 'Restart After (Hours)', 'notificationx' ),
                'type'        => Controls_Manager::NUMBER,
                'default'     => 0,
                'description' => esc_html__( 'How long to wait before restarting. 0 = restart immediately.', 'notificationx' ),
                'condition'   => [
                    'nx_countdown_type'        => 'evergreen',
                    'nx_evergreen_recurring'   => 'yes',
                ],
            ]
        );

        $this->add_control(
            'nx_evergreen_recurring_stop_time',
            [
                'label'       => esc_html__( 'Recurring End Date', 'notificationx' ),
                'type'        => Controls_Manager::DATE_TIME,
                'default'     => gmdate( 'Y-m-d', strtotime( '+7 days' ) ),
                'description' => esc_html__( 'The absolute end date for the recurring countdown.', 'notificationx' ),
                'condition'   => [
                    'nx_countdown_type'        => 'evergreen',
                    'nx_evergreen_recurring'   => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // ── Content Settings ──────────────────────────────────────────────
        $this->start_controls_section(
            'section_content_settings',
            [
                'label' => esc_html__( 'Content Settings', 'notificationx' ),
            ]
        );

        $this->add_control(
            'nx_layout_heading',
            [
                'label' => esc_html__( 'Layout', 'notificationx' ),
                'type'  => Controls_Manager::HEADING,
            ]
        );

        $this->add_control(
            'nx_countdown_layout',
            [
                'label'   => esc_html__( 'View', 'notificationx' ),
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
                    'grid' => [
                        'title' => esc_html__( 'Grid View', 'notificationx' ),
                        'icon'  => 'eicon-columns',
                    ],
                    'list' => [
                        'title' => esc_html__( 'List View', 'notificationx' ),
                        'icon'  => 'eicon-editor-list-ul',
                    ],
                ],
                'default'   => 'grid',
                'toggle'    => false,
            ]
        );

        $this->add_responsive_control(
            'nx_countdown_label_view',
            [
                'label'   => esc_html__( 'Label Position', 'notificationx' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'nx-countdown-label-block',
                'options' => [
                    'nx-countdown-label-block'  => esc_html__( 'Block', 'notificationx' ),
                    'nx-countdown-label-inline' => esc_html__( 'Inline', 'notificationx' ),
                ],
            ]
        );

        $this->add_responsive_control(
            'nx_countdown_alignment',
            [
                'label'     => esc_html__( 'Alignment', 'notificationx' ),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'left'   => [ 'title' => esc_html__( 'Left', 'notificationx' ),   'icon' => 'eicon-text-align-left' ],
                    'center' => [ 'title' => esc_html__( 'Center', 'notificationx' ), 'icon' => 'eicon-text-align-center' ],
                    'right'  => [ 'title' => esc_html__( 'Right', 'notificationx' ),  'icon' => 'eicon-text-align-right' ],
                ],
                'default'   => 'center',
                'selectors' => [
                    '{{WRAPPER}} .nx-countdown-item > div' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'nx_divider_after_layout',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        $this->add_control(
            'nx_show_days',
            [
                'label'        => esc_html__( 'Display Days', 'notificationx' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'nx_days_label',
            [
                'label'       => esc_html__( 'Days Label', 'notificationx' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => esc_html__( 'Days', 'notificationx' ),
                'description' => esc_html__( 'Leave blank to hide.', 'notificationx' ),
                'condition'   => [ 'nx_show_days' => 'yes' ],
                'dynamic'     => [ 'active' => true ],
            ]
        );

        $this->add_control(
            'nx_show_hours',
            [
                'label'        => esc_html__( 'Display Hours', 'notificationx' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'nx_hours_label',
            [
                'label'       => esc_html__( 'Hours Label', 'notificationx' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => esc_html__( 'Hours', 'notificationx' ),
                'description' => esc_html__( 'Leave blank to hide.', 'notificationx' ),
                'condition'   => [ 'nx_show_hours' => 'yes' ],
                'dynamic'     => [ 'active' => true ],
            ]
        );

        $this->add_control(
            'nx_show_minutes',
            [
                'label'        => esc_html__( 'Display Minutes', 'notificationx' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'nx_minutes_label',
            [
                'label'       => esc_html__( 'Minutes Label', 'notificationx' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => esc_html__( 'Minutes', 'notificationx' ),
                'description' => esc_html__( 'Leave blank to hide.', 'notificationx' ),
                'condition'   => [ 'nx_show_minutes' => 'yes' ],
                'dynamic'     => [ 'active' => true ],
            ]
        );

        $this->add_control(
            'nx_show_seconds',
            [
                'label'        => esc_html__( 'Display Seconds', 'notificationx' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'nx_seconds_label',
            [
                'label'       => esc_html__( 'Seconds Label', 'notificationx' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => esc_html__( 'Seconds', 'notificationx' ),
                'description' => esc_html__( 'Leave blank to hide.', 'notificationx' ),
                'condition'   => [ 'nx_show_seconds' => 'yes' ],
                'dynamic'     => [ 'active' => true ],
            ]
        );

        $this->add_control(
            'nx_separator_heading',
            [
                'label' => esc_html__( 'Separator', 'notificationx' ),
                'type'  => Controls_Manager::HEADING,
            ]
        );

        $this->add_control(
            'nx_show_separator',
            [
                'label'        => esc_html__( 'Display Separator', 'notificationx' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'nx-countdown-show-separator',
                'default'      => '',
            ]
        );

        $this->add_control(
            'nx_separator_style',
            [
                'label'     => esc_html__( 'Separator Style', 'notificationx' ),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'dotted',
                'options'   => [
                    'solid'  => esc_html__( 'Solid ( : )', 'notificationx' ),
                    'dotted' => esc_html__( 'Dotted ( · )', 'notificationx' ),
                ],
                'condition' => [ 'nx_show_separator' => 'nx-countdown-show-separator' ],
            ]
        );

        $this->add_control(
            'nx_separator_color',
            [
                'label'     => esc_html__( 'Separator Color', 'notificationx' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nx-countdown-digits::after' => 'color: {{VALUE}};',
                ],
                'condition' => [ 'nx_show_separator' => 'nx-countdown-show-separator' ],
            ]
        );

        $this->end_controls_section();

        // ── Expire Action ─────────────────────────────────────────────────
        $this->start_controls_section(
            'section_expire_action',
            [
                'label' => esc_html__( 'Expire Action', 'notificationx' ),
            ]
        );

        $this->add_control(
            'nx_expire_type',
            [
                'label'       => esc_html__( 'On Expire', 'notificationx' ),
                'type'        => Controls_Manager::SELECT,
                'description' => esc_html__( 'Action to take when the countdown reaches zero.', 'notificationx' ),
                'options'     => [
                    'none' => esc_html__( 'None', 'notificationx' ),
                    'text' => esc_html__( 'Show Message', 'notificationx' ),
                    'url'  => esc_html__( 'Redirect to URL', 'notificationx' ),
                ],
                'default'     => 'none',
            ]
        );

        $this->add_control(
            'nx_expiry_text_title',
            [
                'label'     => esc_html__( 'Expiry Title', 'notificationx' ),
                'type'      => Controls_Manager::TEXTAREA,
                'default'   => esc_html__( 'The countdown is finished!', 'notificationx' ),
                'condition' => [ 'nx_expire_type' => 'text' ],
                'dynamic'   => [ 'active' => true ],
            ]
        );

        $this->add_control(
            'nx_expiry_text',
            [
                'label'     => esc_html__( 'Expiry Content', 'notificationx' ),
                'type'      => Controls_Manager::WYSIWYG,
                'default'   => esc_html__( 'Thank you for being part of this event.', 'notificationx' ),
                'condition' => [ 'nx_expire_type' => 'text' ],
            ]
        );

        $this->add_control(
            'nx_expiry_redirect_url',
            [
                'label'     => esc_html__( 'Redirect URL', 'notificationx' ),
                'type'      => Controls_Manager::TEXT,
                'default'   => '#',
                'condition' => [ 'nx_expire_type' => 'url' ],
                'dynamic'   => [ 'active' => true ],
            ]
        );

        $this->end_controls_section();

        // ═══════════════════════════════════════════════════════════════════
        // STYLE TABS
        // ═══════════════════════════════════════════════════════════════════

        // ── Countdown Box Styles ──────────────────────────────────────────
        $this->start_controls_section(
            'section_style_box',
            [
                'label' => esc_html__( 'Countdown Box', 'notificationx' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'nx_box_use_gradient',
            [
                'label'        => esc_html__( 'Use Gradient Background?', 'notificationx' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'      => 'nx_box_bg_gradient',
                'label'     => esc_html__( 'Box Background', 'notificationx' ),
                'types'     => [ 'classic', 'gradient' ],
                'selector'  => '{{WRAPPER}} .nx-countdown-item > div',
                'condition' => [ 'nx_box_use_gradient' => 'yes' ],
            ]
        );

        $this->add_control(
            'nx_box_bg_color',
            [
                'label'     => esc_html__( 'Box Background Color', 'notificationx' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nx-countdown-item > div' => 'background-color: {{VALUE}};',
                ],
                'condition' => [ 'nx_box_use_gradient' => '' ],
            ]
        );

        $this->add_responsive_control(
            'nx_box_spacing',
            [
                'label'     => esc_html__( 'Space Between Boxes', 'notificationx' ),
                'type'      => Controls_Manager::SLIDER,
                'default'   => [ 'size' => 15 ],
                'range'     => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
                'selectors' => [
                    '{{WRAPPER}} .nx-countdown-item > div'  => 'margin-right:{{SIZE}}px; margin-left:{{SIZE}}px;',
                    '{{WRAPPER}} .nx-countdown-container'   => 'margin-right:-{{SIZE}}px; margin-left:-{{SIZE}}px; --nx-countdown-box-spacing:{{SIZE}}px;',
                ],
            ]
        );

        $this->add_responsive_control(
            'nx_box_padding',
            [
                'label'      => esc_html__( 'Padding', 'notificationx' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em' ],
                'selectors'  => [
                    '{{WRAPPER}} .nx-countdown-item > div' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'nx_box_border',
                'label'    => esc_html__( 'Border', 'notificationx' ),
                'selector' => '{{WRAPPER}} .nx-countdown-item > div',
            ]
        );

        $this->add_control(
            'nx_box_border_radius',
            [
                'label'     => esc_html__( 'Border Radius', 'notificationx' ),
                'type'      => Controls_Manager::DIMENSIONS,
                'selectors' => [
                    '{{WRAPPER}} .nx-countdown-item > div' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'nx_box_shadow',
                'selector' => '{{WRAPPER}} .nx-countdown-item > div',
            ]
        );

        $this->end_controls_section();

        // ── Color & Typography ────────────────────────────────────────────
        $this->start_controls_section(
            'section_style_typography',
            [
                'label' => esc_html__( 'Color &amp; Typography', 'notificationx' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'nx_digits_heading',
            [
                'label' => esc_html__( 'Digits', 'notificationx' ),
                'type'  => Controls_Manager::HEADING,
            ]
        );

        $this->add_control(
            'nx_digits_color',
            [
                'label'     => esc_html__( 'Digits Color', 'notificationx' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#fec503',
                'selectors' => [
                    '{{WRAPPER}} .nx-countdown-digits' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'nx_digits_typography',
                'global'   => [ 'default' => Global_Typography::TYPOGRAPHY_SECONDARY ],
                'selector' => '{{WRAPPER}} .nx-countdown-digits',
            ]
        );

        $this->add_control(
            'nx_digits_bg_color',
            [
                'label'     => esc_html__( 'Background Color', 'notificationx' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nx-countdown-digits' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'nx_digits_padding',
            [
                'label'      => esc_html__( 'Padding', 'notificationx' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .nx-countdown-digits' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'nx_digits_border',
                'selector' => '{{WRAPPER}} .nx-countdown-digits',
            ]
        );

        $this->add_responsive_control(
            'nx_digits_border_radius',
            [
                'label'      => esc_html__( 'Border Radius', 'notificationx' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .nx-countdown-digits' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'nx_digits_box_shadow',
                'selector' => '{{WRAPPER}} .nx-countdown-digits',
            ]
        );

        $this->add_control(
            'nx_labels_heading',
            [
                'label' => esc_html__( 'Labels', 'notificationx' ),
                'type'  => Controls_Manager::HEADING,
            ]
        );

        $this->add_control(
            'nx_label_color',
            [
                'label'     => esc_html__( 'Label Color', 'notificationx' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#aaaaaa',
                'selectors' => [
                    '{{WRAPPER}} .nx-countdown-label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'nx_label_typography',
                'global'   => [ 'default' => Global_Typography::TYPOGRAPHY_SECONDARY ],
                'selector' => '{{WRAPPER}} .nx-countdown-label',
            ]
        );

        $this->end_controls_section();

        // ── Individual Box Styling ────────────────────────────────────────
        $this->start_controls_section(
            'section_style_individual',
            [
                'label' => esc_html__( 'Individual Box Styling', 'notificationx' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $unit_labels = [
            'days'    => esc_html__( 'Days', 'notificationx' ),
            'hours'   => esc_html__( 'Hours', 'notificationx' ),
            'minutes' => esc_html__( 'Minutes', 'notificationx' ),
            'seconds' => esc_html__( 'Seconds', 'notificationx' ),
        ];

        foreach ( $unit_labels as $unit => $label ) {
            $this->add_control(
                "nx_{$unit}_heading",
                [
                    'label' => $label,
                    'type'  => Controls_Manager::HEADING,
                ]
            );

            $this->add_control(
                "nx_{$unit}_bg_color",
                [
                    'label'     => esc_html__( 'Background Color', 'notificationx' ),
                    'type'      => Controls_Manager::COLOR,
                    'selectors' => [
                        "{{WRAPPER}} .nx-countdown-item > div.nx-countdown-{$unit}" => 'background-color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                "nx_{$unit}_digit_color",
                [
                    'label'     => esc_html__( 'Digit Color', 'notificationx' ),
                    'type'      => Controls_Manager::COLOR,
                    'selectors' => [
                        "{{WRAPPER}} .nx-countdown-item > div.nx-countdown-{$unit} .nx-countdown-digits" => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                "nx_{$unit}_label_color",
                [
                    'label'     => esc_html__( 'Label Color', 'notificationx' ),
                    'type'      => Controls_Manager::COLOR,
                    'selectors' => [
                        "{{WRAPPER}} .nx-countdown-item > div.nx-countdown-{$unit} .nx-countdown-label" => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                "nx_{$unit}_border_color",
                [
                    'label'     => esc_html__( 'Border Color', 'notificationx' ),
                    'type'      => Controls_Manager::COLOR,
                    'selectors' => [
                        "{{WRAPPER}} .nx-countdown-item > div.nx-countdown-{$unit}" => 'border-color: {{VALUE}};',
                    ],
                ]
            );
        }

        $this->end_controls_section();

        // ── Expire Message Style ──────────────────────────────────────────
        $this->start_controls_section(
            'section_style_expire',
            [
                'label'     => esc_html__( 'Expire Message', 'notificationx' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [ 'nx_expire_type' => 'text' ],
            ]
        );

        $this->add_responsive_control(
            'nx_expire_alignment',
            [
                'label'     => esc_html__( 'Alignment', 'notificationx' ),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'left'   => [ 'title' => esc_html__( 'Left', 'notificationx' ),   'icon' => 'eicon-text-align-left' ],
                    'center' => [ 'title' => esc_html__( 'Center', 'notificationx' ), 'icon' => 'eicon-text-align-center' ],
                    'right'  => [ 'title' => esc_html__( 'Right', 'notificationx' ),  'icon' => 'eicon-text-align-right' ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .nx-countdown-finish-message' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'nx_expire_title_color',
            [
                'label'     => esc_html__( 'Title Color', 'notificationx' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nx-countdown-finish-message .nx-expiry-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'nx_expire_title_typography',
                'global'   => [ 'default' => Global_Typography::TYPOGRAPHY_SECONDARY ],
                'selector' => '{{WRAPPER}} .nx-countdown-finish-message .nx-expiry-title',
            ]
        );

        $this->add_control(
            'nx_expire_text_color',
            [
                'label'     => esc_html__( 'Text Color', 'notificationx' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nx-expiry-text' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'nx_expire_text_typography',
                'global'   => [ 'default' => Global_Typography::TYPOGRAPHY_SECONDARY ],
                'selector' => '{{WRAPPER}} .nx-expiry-text',
            ]
        );

        $this->end_controls_section();
    }

    // ── Render ────────────────────────────────────────────────────────────

    protected function render() {
        $settings = $this->get_settings_for_display();

        // Build target date string with GMT offset (matches the JS `new Date(dateStr)` parsing)
        $gmt_offset = get_option( 'gmt_offset' );
        $offset_str = ( $gmt_offset < 0 ? '' : '+' ) . str_replace(
            [ '.25', '.5', '.75' ],
            [ ':15', ':30', ':45' ],
            (string) $gmt_offset
        );

        $due_date_str = '';
        if ( $settings['nx_countdown_type'] === 'due_date' && ! empty( $settings['nx_countdown_due_time'] ) ) {
            $due_date_str = gmdate( 'M d Y G:i:s', strtotime( $settings['nx_countdown_due_time'] ) ) . ' ' . $offset_str;
        }

        // Evergreen time in seconds
        $evergreen_seconds = 0;
        if ( $settings['nx_countdown_type'] === 'evergreen' ) {
            $evergreen_seconds  = absint( $settings['nx_evergreen_hours']   ?? 0 ) * HOUR_IN_SECONDS;
            $evergreen_seconds += absint( $settings['nx_evergreen_minutes'] ?? 0 ) * MINUTE_IN_SECONDS;
        }

        // Separator class
        $separator_class = '';
        if ( ! empty( $settings['nx_show_separator'] ) ) {
            $separator_class = 'nx-countdown-show-separator nx-countdown-separator-' . esc_attr( $settings['nx_separator_style'] );
        }

        // ── Wrapper attributes ────────────────────────────────────────────
        $this->add_render_attribute( 'nx-countdown', [
            'class'                    => 'nx-countdown-wrapper',
            'data-countdown-id'        => esc_attr( $this->get_id() ),
            'data-countdown-type'      => esc_attr( $settings['nx_countdown_type'] ),
            'data-expire-type'         => esc_attr( $settings['nx_expire_type'] ),
        ] );

        if ( $settings['nx_expire_type'] === 'text' ) {
            $this->add_render_attribute( 'nx-countdown', [
                'data-expiry-title' => wp_kses_post( $settings['nx_expiry_text_title'] ?? '' ),
                'data-expiry-text'  => wp_kses_post( $settings['nx_expiry_text'] ?? '' ),
            ] );
        } elseif ( $settings['nx_expire_type'] === 'url' ) {
            $this->add_render_attribute( 'nx-countdown', 'data-redirect-url', esc_url( $settings['nx_expiry_redirect_url'] ?? '' ) );
        }

        if ( $settings['nx_countdown_type'] === 'evergreen' ) {
            $this->add_render_attribute( 'nx-countdown', 'data-evergreen-time', absint( $evergreen_seconds ) );
            if ( $settings['nx_evergreen_recurring'] === 'yes' ) {
                $restart_after = isset( $settings['nx_evergreen_recurring_restart_after'] ) ? $settings['nx_evergreen_recurring_restart_after'] : 0;
                $stop_time     = isset( $settings['nx_evergreen_recurring_stop_time'] )
                    ? gmdate( 'M d Y G:i:s', strtotime( $settings['nx_evergreen_recurring_stop_time'] ) ) . ' ' . $offset_str
                    : '';
                $this->add_render_attribute( 'nx-countdown', [
                    'data-evergreen-recurring'      => absint( $restart_after ),
                    'data-evergreen-recurring-stop' => esc_attr( $stop_time ),
                ] );
            }
        }

        // ── List attributes ───────────────────────────────────────────────
        $label_view    = $settings['nx_countdown_label_view'] ?? 'nx-countdown-label-block';
        $layout_class  = 'nx-countdown-layout-' . ( ! empty( $settings['nx_countdown_layout'] ) ? esc_attr( $settings['nx_countdown_layout'] ) : 'grid' );

        $this->add_render_attribute( 'nx-countdown-list', [
            'id'        => 'nx-countdown-' . esc_attr( $this->get_id() ),
            'class'     => [ 'nx-countdown-container', esc_attr( $layout_class ), esc_attr( $label_view ), esc_attr( $separator_class ) ],
            'data-date' => esc_attr( $due_date_str ),
        ] );
        ?>

        <div <?php $this->print_render_attribute_string( 'nx-countdown' ); ?>>

            <ul <?php $this->print_render_attribute_string( 'nx-countdown-list' ); ?>>

                <?php if ( ! empty( $settings['nx_show_days'] ) ) : ?>
                <li class="nx-countdown-item">
                    <div class="nx-countdown-days">
                        <span data-days class="nx-countdown-digits">00</span>
                        <?php if ( ! empty( $settings['nx_days_label'] ) ) : ?>
                            <span class="nx-countdown-label"><?php echo esc_html( $settings['nx_days_label'] ); ?></span>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endif; ?>

                <?php if ( ! empty( $settings['nx_show_hours'] ) ) : ?>
                <li class="nx-countdown-item">
                    <div class="nx-countdown-hours">
                        <span data-hours class="nx-countdown-digits">00</span>
                        <?php if ( ! empty( $settings['nx_hours_label'] ) ) : ?>
                            <span class="nx-countdown-label"><?php echo esc_html( $settings['nx_hours_label'] ); ?></span>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endif; ?>

                <?php if ( ! empty( $settings['nx_show_minutes'] ) ) : ?>
                <li class="nx-countdown-item">
                    <div class="nx-countdown-minutes">
                        <span data-minutes class="nx-countdown-digits">00</span>
                        <?php if ( ! empty( $settings['nx_minutes_label'] ) ) : ?>
                            <span class="nx-countdown-label"><?php echo esc_html( $settings['nx_minutes_label'] ); ?></span>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endif; ?>

                <?php if ( ! empty( $settings['nx_show_seconds'] ) ) : ?>
                <li class="nx-countdown-item">
                    <div class="nx-countdown-seconds">
                        <span data-seconds class="nx-countdown-digits">00</span>
                        <?php if ( ! empty( $settings['nx_seconds_label'] ) ) : ?>
                            <span class="nx-countdown-label"><?php echo esc_html( $settings['nx_seconds_label'] ); ?></span>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endif; ?>

            </ul>

        </div>

        <?php
    }

    // ── Editor live-preview JS template ──────────────────────────────────

    protected function content_template() {
        ?>
        <#
        var widgetId = view.getID();

        var layoutClass = 'nx-countdown-layout-' + ( settings.nx_countdown_layout || 'grid' );
        var listClasses = 'nx-countdown-container ' + layoutClass + ' ' + ( settings.nx_countdown_label_view || 'nx-countdown-label-block' );
        if ( settings.nx_show_separator ) {
            listClasses += ' nx-countdown-show-separator nx-countdown-separator-' + settings.nx_separator_style;
        }

        // Evergreen duration in seconds
        var evergreenSeconds = ( parseInt( settings.nx_evergreen_hours   || 0, 10 ) * 3600 )
                             + ( parseInt( settings.nx_evergreen_minutes || 0, 10 ) * 60 );

        // Build data attrs for the wrapper (mirroring render())
        var wrapperData = ' data-countdown-id="'   + widgetId                        + '"'
                        + ' data-countdown-type="' + settings.nx_countdown_type       + '"'
                        + ' data-expire-type="'    + settings.nx_expire_type          + '"';

        if ( settings.nx_countdown_type === 'evergreen' ) {
            wrapperData += ' data-evergreen-time="' + evergreenSeconds + '"';
            if ( settings.nx_evergreen_recurring === 'yes' ) {
                wrapperData += ' data-evergreen-recurring="'      + ( settings.nx_evergreen_recurring_restart_after || 0 )  + '"';
                wrapperData += ' data-evergreen-recurring-stop="' + ( settings.nx_evergreen_recurring_stop_time    || '' ) + '"';
            }
        }

        if ( settings.nx_expire_type === 'text' ) {
            wrapperData += ' data-expiry-title="' + ( settings.nx_expiry_text_title || '' ) + '"';
            wrapperData += ' data-expiry-text="'  + ( settings.nx_expiry_text       || '' ) + '"';
        } else if ( settings.nx_expire_type === 'url' ) {
            wrapperData += ' data-redirect-url="' + ( settings.nx_expiry_redirect_url || '' ) + '"';
        }

        // For due_date, pass the raw ISO string — the JS does new Date(dateStr).getTime()
        var dateStr = ( settings.nx_countdown_type === 'due_date' )
                      ? ( settings.nx_countdown_due_time || '' ).replace( ' ', 'T' )
                      : '';
        #>
        <div class="nx-countdown-wrapper"{{{ wrapperData }}}>
            <ul id="nx-countdown-{{ widgetId }}" class="{{ listClasses }}" data-date="{{ dateStr }}">

                <# if ( settings.nx_show_days ) { #>
                <li class="nx-countdown-item">
                    <div class="nx-countdown-days">
                        <span data-days class="nx-countdown-digits">00</span>
                        <# if ( settings.nx_days_label ) { #>
                            <span class="nx-countdown-label">{{{ settings.nx_days_label }}}</span>
                        <# } #>
                    </div>
                </li>
                <# } #>

                <# if ( settings.nx_show_hours ) { #>
                <li class="nx-countdown-item">
                    <div class="nx-countdown-hours">
                        <span data-hours class="nx-countdown-digits">00</span>
                        <# if ( settings.nx_hours_label ) { #>
                            <span class="nx-countdown-label">{{{ settings.nx_hours_label }}}</span>
                        <# } #>
                    </div>
                </li>
                <# } #>

                <# if ( settings.nx_show_minutes ) { #>
                <li class="nx-countdown-item">
                    <div class="nx-countdown-minutes">
                        <span data-minutes class="nx-countdown-digits">00</span>
                        <# if ( settings.nx_minutes_label ) { #>
                            <span class="nx-countdown-label">{{{ settings.nx_minutes_label }}}</span>
                        <# } #>
                    </div>
                </li>
                <# } #>

                <# if ( settings.nx_show_seconds ) { #>
                <li class="nx-countdown-item">
                    <div class="nx-countdown-seconds">
                        <span data-seconds class="nx-countdown-digits">00</span>
                        <# if ( settings.nx_seconds_label ) { #>
                            <span class="nx-countdown-label">{{{ settings.nx_seconds_label }}}</span>
                        <# } #>
                    </div>
                </li>
                <# } #>

            </ul>
        </div>
        <?php
    }
}
