<?php
/**
 * NotificationX Elementor Form Widget
 *
 * A simple Name / Email / Message form whose submissions are saved into
 * the NotificationX `nx_entries` table via the existing
 * `notificationx/v1/popup-submit` REST endpoint. The widget must be bound
 * to an existing NotificationX campaign of source `popup_notification`
 * or `exit_intent_custom`.
 *
 * @package NotificationX\Extensions\Elementor
 */

namespace NotificationX\Extensions\Elementor;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use NotificationX\Core\PostType;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Column-width options shared across all field controls.
 * Keys are the CSS percentage values rendered into `flex-basis`.
 */
if ( ! function_exists( __NAMESPACE__ . '\\nx_form_widths' ) ) {
    function nx_form_widths() {
        return [
            '100' => '100%',
            '75'  => '75%',
            '66'  => '66%',
            '50'  => '50%',
            '33'  => '33%',
            '25'  => '25%',
        ];
    }
}

class FormWidget extends Widget_Base {

    public function get_name() {
        return 'nx-form';
    }

    public function get_title() {
        return esc_html__( 'NotificationX Form', 'notificationx' );
    }

    public function get_icon() {
        return 'eicon-form-horizontal';
    }

    public function get_categories() {
        return [ 'notificationx' ];
    }

    public function get_keywords() {
        return [ 'form', 'contact', 'lead', 'notificationx', 'nx', 'entries', 'subscribe' ];
    }

    public function get_script_depends() {
        return [ 'nx-elementor-form' ];
    }

    public function get_style_depends() {
        return [ 'nx-elementor-form' ];
    }

    /**
     * Existing NX Popup / Exit-Intent campaigns to bind submissions to.
     *
     * @return array<int|string,string>
     */
    private function get_nx_campaigns() {
        $options = [ '' => esc_html__( '— Select a Campaign —', 'notificationx' ) ];

        if ( ! class_exists( PostType::class ) ) {
            return $options;
        }

        $posts = PostType::get_instance()->get_posts(
            [], // can't AND two source values via a simple where; filter below.
            'nx_id, title, source'
        );

        if ( is_array( $posts ) ) {
            foreach ( $posts as $post ) {
                if ( empty( $post['source'] ) ) {
                    continue;
                }
                if ( ! in_array( $post['source'], [ 'popup_notification', 'exit_intent_custom' ], true ) ) {
                    continue;
                }
                $label = $post['title'] ?: sprintf( __( 'Campaign #%d', 'notificationx' ), $post['nx_id'] );
                $options[ (string) $post['nx_id'] ] = sprintf(
                    '%s (%s)',
                    $label,
                    $post['source'] === 'exit_intent_custom' ? __( 'Exit Intent', 'notificationx' ) : __( 'Popup', 'notificationx' )
                );
            }
        }

        return $options;
    }

    protected function register_controls() {

        // ── Form Settings ─────────────────────────────────────────────────
        $this->start_controls_section(
            'section_form_settings',
            [ 'label' => esc_html__( 'Form Settings', 'notificationx' ) ]
        );

        $this->add_control(
            'nx_campaign_id',
            [
                'label'       => esc_html__( 'Bind to NotificationX Campaign', 'notificationx' ),
                'type'        => Controls_Manager::SELECT,
                'options'     => $this->get_nx_campaigns(),
                'default'     => '',
                'description' => esc_html__( 'Submissions are saved as entries against the selected Popup or Exit-Intent campaign.', 'notificationx' ),
            ]
        );

        $this->add_control(
            'nx_form_title',
            [
                'label'   => esc_html__( 'Form Title', 'notificationx' ),
                'type'    => Controls_Manager::TEXT,
                'default' => esc_html__( 'Get in touch', 'notificationx' ),
                'dynamic' => [ 'active' => true ],
            ]
        );

        // Name field
        $this->add_control(
            'nx_name_heading',
            [ 'label' => esc_html__( 'Name Field', 'notificationx' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ]
        );
        $this->add_control(
            'nx_show_name',
            [ 'label' => esc_html__( 'Show Name', 'notificationx' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ]
        );
        $this->add_responsive_control(
            'nx_name_width',
            [
                'label'     => esc_html__( 'Column Width', 'notificationx' ),
                'type'      => Controls_Manager::SELECT,
                'options'   => nx_form_widths(),
                'default'   => '100',
                'condition' => [ 'nx_show_name' => 'yes' ],
            ]
        );
        $this->add_control(
            'nx_name_label',
            [ 'label' => esc_html__( 'Label', 'notificationx' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'Name', 'notificationx' ), 'condition' => [ 'nx_show_name' => 'yes' ] ]
        );
        $this->add_control(
            'nx_name_placeholder',
            [ 'label' => esc_html__( 'Placeholder', 'notificationx' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'Your name', 'notificationx' ), 'condition' => [ 'nx_show_name' => 'yes' ] ]
        );
        $this->add_control(
            'nx_name_required',
            [ 'label' => esc_html__( 'Required', 'notificationx' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '', 'condition' => [ 'nx_show_name' => 'yes' ] ]
        );

        // Email field
        $this->add_control(
            'nx_email_heading',
            [ 'label' => esc_html__( 'Email Field', 'notificationx' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ]
        );
        $this->add_control(
            'nx_show_email',
            [ 'label' => esc_html__( 'Show Email', 'notificationx' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ]
        );
        $this->add_responsive_control(
            'nx_email_width',
            [
                'label'     => esc_html__( 'Column Width', 'notificationx' ),
                'type'      => Controls_Manager::SELECT,
                'options'   => nx_form_widths(),
                'default'   => '100',
                'condition' => [ 'nx_show_email' => 'yes' ],
            ]
        );
        $this->add_control(
            'nx_email_label',
            [ 'label' => esc_html__( 'Label', 'notificationx' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'Email', 'notificationx' ), 'condition' => [ 'nx_show_email' => 'yes' ] ]
        );
        $this->add_control(
            'nx_email_placeholder',
            [ 'label' => esc_html__( 'Placeholder', 'notificationx' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'you@example.com', 'notificationx' ), 'condition' => [ 'nx_show_email' => 'yes' ] ]
        );
        $this->add_control(
            'nx_email_required',
            [ 'label' => esc_html__( 'Required', 'notificationx' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'condition' => [ 'nx_show_email' => 'yes' ] ]
        );

        // Message field
        $this->add_control(
            'nx_message_heading',
            [ 'label' => esc_html__( 'Message Field', 'notificationx' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ]
        );
        $this->add_control(
            'nx_show_message',
            [ 'label' => esc_html__( 'Show Message', 'notificationx' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ]
        );
        $this->add_responsive_control(
            'nx_message_width',
            [
                'label'     => esc_html__( 'Column Width', 'notificationx' ),
                'type'      => Controls_Manager::SELECT,
                'options'   => nx_form_widths(),
                'default'   => '100',
                'condition' => [ 'nx_show_message' => 'yes' ],
            ]
        );
        $this->add_control(
            'nx_message_label',
            [ 'label' => esc_html__( 'Label', 'notificationx' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'Message', 'notificationx' ), 'condition' => [ 'nx_show_message' => 'yes' ] ]
        );
        $this->add_control(
            'nx_message_placeholder',
            [ 'label' => esc_html__( 'Placeholder', 'notificationx' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'Write your message…', 'notificationx' ), 'condition' => [ 'nx_show_message' => 'yes' ] ]
        );
        $this->add_control(
            'nx_message_rows',
            [ 'label' => esc_html__( 'Rows', 'notificationx' ), 'type' => Controls_Manager::NUMBER, 'default' => 4, 'min' => 1, 'max' => 20, 'condition' => [ 'nx_show_message' => 'yes' ] ]
        );
        $this->add_control(
            'nx_message_required',
            [ 'label' => esc_html__( 'Required', 'notificationx' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '', 'condition' => [ 'nx_show_message' => 'yes' ] ]
        );

        // Submit
        $this->add_control(
            'nx_submit_heading',
            [ 'label' => esc_html__( 'Submit', 'notificationx' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ]
        );
        $this->add_responsive_control(
            'nx_submit_width',
            [
                'label'   => esc_html__( 'Column Width', 'notificationx' ),
                'type'    => Controls_Manager::SELECT,
                'options' => nx_form_widths(),
                'default' => '100',
            ]
        );
        $this->add_control(
            'nx_submit_text',
            [ 'label' => esc_html__( 'Button Text', 'notificationx' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'Submit', 'notificationx' ) ]
        );
        $this->add_control(
            'nx_success_message',
            [ 'label' => esc_html__( 'Success Message', 'notificationx' ), 'type' => Controls_Manager::TEXTAREA, 'default' => esc_html__( 'Thanks! Your submission has been received.', 'notificationx' ) ]
        );
        $this->add_control(
            'nx_error_message',
            [ 'label' => esc_html__( 'Error Message', 'notificationx' ), 'type' => Controls_Manager::TEXTAREA, 'default' => esc_html__( 'Something went wrong. Please try again.', 'notificationx' ) ]
        );

        $this->end_controls_section();

        // ── Style: Form ──────────────────────────────────────────────────
        $this->start_controls_section(
            'section_style_form',
            [ 'label' => esc_html__( 'Form', 'notificationx' ), 'tab' => Controls_Manager::TAB_STYLE ]
        );

        $this->add_responsive_control(
            'nx_form_align',
            [
                'label'   => esc_html__( 'Alignment', 'notificationx' ),
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
                    'left'   => [ 'title' => esc_html__( 'Left', 'notificationx' ),   'icon' => 'eicon-text-align-left' ],
                    'center' => [ 'title' => esc_html__( 'Center', 'notificationx' ), 'icon' => 'eicon-text-align-center' ],
                    'right'  => [ 'title' => esc_html__( 'Right', 'notificationx' ),  'icon' => 'eicon-text-align-right' ],
                ],
                'default'   => 'left',
                'selectors' => [ '{{WRAPPER}} .nx-form-title' => 'text-align: {{VALUE}};' ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [ 'name' => 'nx_title_typography', 'selector' => '{{WRAPPER}} .nx-form-title' ]
        );

        $this->add_control(
            'nx_title_color',
            [
                'label'     => esc_html__( 'Title Color', 'notificationx' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [ '{{WRAPPER}} .nx-form-title' => 'color: {{VALUE}};' ],
            ]
        );

        $this->end_controls_section();

        // ── Style: Fields ────────────────────────────────────────────────
        $this->start_controls_section(
            'section_style_fields',
            [ 'label' => esc_html__( 'Fields', 'notificationx' ), 'tab' => Controls_Manager::TAB_STYLE ]
        );

        $this->add_control(
            'nx_label_color',
            [
                'label'     => esc_html__( 'Label Color', 'notificationx' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [ '{{WRAPPER}} .nx-form-label' => 'color: {{VALUE}};' ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [ 'name' => 'nx_label_typography', 'selector' => '{{WRAPPER}} .nx-form-label' ]
        );

        $this->add_control(
            'nx_input_text_color',
            [
                'label'     => esc_html__( 'Input Text Color', 'notificationx' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [ '{{WRAPPER}} .nx-form-input' => 'color: {{VALUE}};' ],
            ]
        );

        $this->add_control(
            'nx_input_bg_color',
            [
                'label'     => esc_html__( 'Input Background', 'notificationx' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [ '{{WRAPPER}} .nx-form-input' => 'background-color: {{VALUE}};' ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'nx_input_border',
                'selector' => '{{WRAPPER}} .nx-form-input',
            ]
        );

        $this->add_control(
            'nx_input_radius',
            [
                'label'      => esc_html__( 'Border Radius', 'notificationx' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors'  => [ '{{WRAPPER}} .nx-form-input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
            ]
        );

        $this->add_control(
            'nx_input_padding',
            [
                'label'      => esc_html__( 'Padding', 'notificationx' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'selectors'  => [ '{{WRAPPER}} .nx-form-input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
            ]
        );

        $this->add_control(
            'nx_field_spacing',
            [
                'label'      => esc_html__( 'Row Gap (Vertical)', 'notificationx' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
                'default'    => [ 'unit' => 'px', 'size' => 14 ],
                'selectors'  => [ '{{WRAPPER}} .nx-form-rows' => 'row-gap: {{SIZE}}{{UNIT}};' ],
            ]
        );

        $this->add_control(
            'nx_column_gap',
            [
                'label'      => esc_html__( 'Column Gap (Horizontal)', 'notificationx' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
                'default'    => [ 'unit' => 'px', 'size' => 14 ],
                'selectors'  => [ '{{WRAPPER}} .nx-form-rows' => 'column-gap: {{SIZE}}{{UNIT}}; --nx-gap: {{SIZE}}{{UNIT}};' ],
            ]
        );

        $this->end_controls_section();

        // ── Style: Button ────────────────────────────────────────────────
        $this->start_controls_section(
            'section_style_button',
            [ 'label' => esc_html__( 'Button', 'notificationx' ), 'tab' => Controls_Manager::TAB_STYLE ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [ 'name' => 'nx_button_typography', 'selector' => '{{WRAPPER}} .nx-form-submit' ]
        );

        $this->start_controls_tabs( 'nx_button_tabs' );

        $this->start_controls_tab( 'nx_button_tab_normal', [ 'label' => esc_html__( 'Normal', 'notificationx' ) ] );
        $this->add_control(
            'nx_button_color',
            [ 'label' => esc_html__( 'Text Color', 'notificationx' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => [ '{{WRAPPER}} .nx-form-submit' => 'color: {{VALUE}};' ] ]
        );
        $this->add_control(
            'nx_button_bg',
            [ 'label' => esc_html__( 'Background', 'notificationx' ), 'type' => Controls_Manager::COLOR, 'default' => '#2271b1', 'selectors' => [ '{{WRAPPER}} .nx-form-submit' => 'background-color: {{VALUE}};' ] ]
        );
        $this->end_controls_tab();

        $this->start_controls_tab( 'nx_button_tab_hover', [ 'label' => esc_html__( 'Hover', 'notificationx' ) ] );
        $this->add_control(
            'nx_button_color_h',
            [ 'label' => esc_html__( 'Text Color', 'notificationx' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .nx-form-submit:hover' => 'color: {{VALUE}};' ] ]
        );
        $this->add_control(
            'nx_button_bg_h',
            [ 'label' => esc_html__( 'Background', 'notificationx' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .nx-form-submit:hover' => 'background-color: {{VALUE}};' ] ]
        );
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [ 'name' => 'nx_button_border', 'selector' => '{{WRAPPER}} .nx-form-submit' ]
        );

        $this->add_control(
            'nx_button_radius',
            [
                'label'      => esc_html__( 'Border Radius', 'notificationx' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors'  => [ '{{WRAPPER}} .nx-form-submit' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
            ]
        );

        $this->add_control(
            'nx_button_padding',
            [
                'label'      => esc_html__( 'Padding', 'notificationx' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'default'    => [ 'top' => '12', 'right' => '20', 'bottom' => '12', 'left' => '20', 'unit' => 'px', 'isLinked' => false ],
                'selectors'  => [ '{{WRAPPER}} .nx-form-submit' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
            ]
        );

        $this->add_responsive_control(
            'nx_button_align',
            [
                'label'   => esc_html__( 'Alignment', 'notificationx' ),
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [ 'title' => esc_html__( 'Left', 'notificationx' ),   'icon' => 'eicon-text-align-left' ],
                    'center'     => [ 'title' => esc_html__( 'Center', 'notificationx' ), 'icon' => 'eicon-text-align-center' ],
                    'flex-end'   => [ 'title' => esc_html__( 'Right', 'notificationx' ),  'icon' => 'eicon-text-align-right' ],
                    'stretch'    => [ 'title' => esc_html__( 'Justified', 'notificationx' ), 'icon' => 'eicon-text-align-justify' ],
                ],
                'default'   => 'stretch',
                'selectors' => [ '{{WRAPPER}} .nx-form-actions' => 'justify-content: {{VALUE}};' ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Build the class string for a row given per-breakpoint width settings.
     *
     * @param array  $s        Settings array.
     * @param string $base_key The control base name (e.g. `nx_name_width`).
     * @return string
     */
    private function row_width_classes( $s, $base_key ) {
        $classes = [ 'nx-form-row' ];
        $map = [
            ''        => $s[ $base_key ]              ?? '100',
            'tablet-' => $s[ $base_key . '_tablet' ]  ?? '',
            'mobile-' => $s[ $base_key . '_mobile' ]  ?? '',
        ];
        foreach ( $map as $prefix => $val ) {
            if ( $val !== '' && $val !== null ) {
                $classes[] = 'nx-w-' . $prefix . $val;
            }
        }
        return implode( ' ', array_map( 'sanitize_html_class', $classes ) );
    }

    protected function render() {
        $s = $this->get_settings_for_display();

        $campaign_id = absint( $s['nx_campaign_id'] ?? 0 );
        $rest_url    = esc_url_raw( rest_url( 'notificationx/v1/popup-submit' ) );
        $nonce       = wp_create_nonce( 'wp_rest' );

        $form_id     = 'nx-form-' . esc_attr( $this->get_id() );
        $show_name    = ! empty( $s['nx_show_name'] );
        $show_email   = ! empty( $s['nx_show_email'] );
        $show_message = ! empty( $s['nx_show_message'] );

        // Don't render the form at all if no campaign is selected — show a
        // friendly editor-only notice.
        if ( ! $campaign_id ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                printf(
                    '<div class="nx-form-notice">%s</div>',
                    esc_html__( 'Select a NotificationX campaign in the Form Settings to start collecting entries.', 'notificationx' )
                );
            }
            return;
        }
        ?>
        <div class="nx-form-wrapper">

            <?php if ( ! empty( $s['nx_form_title'] ) ) : ?>
                <h3 class="nx-form-title"><?php echo esc_html( $s['nx_form_title'] ); ?></h3>
            <?php endif; ?>

            <form
                id="<?php echo esc_attr( $form_id ); ?>"
                class="nx-form"
                data-nx-id="<?php echo esc_attr( $campaign_id ); ?>"
                data-rest-url="<?php echo esc_attr( $rest_url ); ?>"
                data-rest-nonce="<?php echo esc_attr( $nonce ); ?>"
                data-success="<?php echo esc_attr( $s['nx_success_message'] ?? '' ); ?>"
                data-error="<?php echo esc_attr( $s['nx_error_message'] ?? '' ); ?>"
                novalidate
            >
                <div class="nx-form-rows">

                <?php if ( $show_name ) : ?>
                    <div class="<?php echo esc_attr( $this->row_width_classes( $s, 'nx_name_width' ) ); ?>">
                        <?php if ( ! empty( $s['nx_name_label'] ) ) : ?>
                            <label class="nx-form-label" for="<?php echo esc_attr( $form_id ); ?>-name">
                                <?php echo esc_html( $s['nx_name_label'] ); ?>
                                <?php if ( ! empty( $s['nx_name_required'] ) ) echo ' <span class="nx-form-required">*</span>'; ?>
                            </label>
                        <?php endif; ?>
                        <input
                            type="text"
                            id="<?php echo esc_attr( $form_id ); ?>-name"
                            name="name"
                            class="nx-form-input"
                            placeholder="<?php echo esc_attr( $s['nx_name_placeholder'] ?? '' ); ?>"
                            <?php if ( ! empty( $s['nx_name_required'] ) ) echo 'required'; ?>
                        >
                    </div>
                <?php endif; ?>

                <?php if ( $show_email ) : ?>
                    <div class="<?php echo esc_attr( $this->row_width_classes( $s, 'nx_email_width' ) ); ?>">
                        <?php if ( ! empty( $s['nx_email_label'] ) ) : ?>
                            <label class="nx-form-label" for="<?php echo esc_attr( $form_id ); ?>-email">
                                <?php echo esc_html( $s['nx_email_label'] ); ?>
                                <?php if ( ! empty( $s['nx_email_required'] ) ) echo ' <span class="nx-form-required">*</span>'; ?>
                            </label>
                        <?php endif; ?>
                        <input
                            type="email"
                            id="<?php echo esc_attr( $form_id ); ?>-email"
                            name="email"
                            class="nx-form-input"
                            placeholder="<?php echo esc_attr( $s['nx_email_placeholder'] ?? '' ); ?>"
                            <?php if ( ! empty( $s['nx_email_required'] ) ) echo 'required'; ?>
                        >
                    </div>
                <?php endif; ?>

                <?php if ( $show_message ) : ?>
                    <div class="<?php echo esc_attr( $this->row_width_classes( $s, 'nx_message_width' ) ); ?>">
                        <?php if ( ! empty( $s['nx_message_label'] ) ) : ?>
                            <label class="nx-form-label" for="<?php echo esc_attr( $form_id ); ?>-message">
                                <?php echo esc_html( $s['nx_message_label'] ); ?>
                                <?php if ( ! empty( $s['nx_message_required'] ) ) echo ' <span class="nx-form-required">*</span>'; ?>
                            </label>
                        <?php endif; ?>
                        <textarea
                            id="<?php echo esc_attr( $form_id ); ?>-message"
                            name="message"
                            class="nx-form-input nx-form-textarea"
                            rows="<?php echo absint( $s['nx_message_rows'] ?? 4 ); ?>"
                            placeholder="<?php echo esc_attr( $s['nx_message_placeholder'] ?? '' ); ?>"
                            <?php if ( ! empty( $s['nx_message_required'] ) ) echo 'required'; ?>
                        ></textarea>
                    </div>
                <?php endif; ?>

                <div class="<?php echo esc_attr( $this->row_width_classes( $s, 'nx_submit_width' ) ); ?> nx-form-actions">
                    <button type="submit" class="nx-form-submit">
                        <?php echo esc_html( $s['nx_submit_text'] ?? __( 'Submit', 'notificationx' ) ); ?>
                    </button>
                </div>

                </div>

                <div class="nx-form-message" role="status" aria-live="polite"></div>
            </form>
        </div>
        <?php
    }
}
