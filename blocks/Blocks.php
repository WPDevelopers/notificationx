<?php
/**
 * Functions to register client-side assets (scripts and stylesheets) for the
 * Gutenberg block.
 *
 * @package notificationx
 */

namespace NotificationX\Blocks;

use NotificationX\Core\Helper;
use NotificationX\GetInstance;

/**
 *
 * @method static Blocks get_instance($args = null)
 */
class Blocks {
    /**
     * Instance of NotificationX
     *
     * @var Blocks
     */
    use GetInstance;


    public function __construct() {
        StyleHandler::get_instance();
        add_action( 'init', [ $this, 'notificationx_block_init' ] );
    }

    /**
     * Registers all block assets so that they can be enqueued through Gutenberg in
     * the corresponding context.
     *
     * @see https://wordpress.org/gutenberg/handbook/designers-developers/developers/tutorials/block-tutorial/applying-styles-with-stylesheets/
     */
    function notificationx_block_init() {
        // Skip block registration if Gutenberg is not enabled/merged.
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }
        $dir = dirname( __FILE__ );

        // Enqueue Controls CSS & JS
        $controls_css = 'controls/dist/index.css';
        wp_register_style(
            'notificationx-block-controls-css',
            plugins_url( $controls_css, __FILE__ ),
            [],
            filemtime( "{$dir}/{$controls_css}" )
        );

        $asset_file = include NOTIFICATIONX_PATH . 'blocks/controls/dist/index.asset.php';
        $index_js   = 'controls/dist/index.js';
        // The controls bundle relies on the global `lodash` but its asset file
        // doesn't declare it; add it explicitly so the bundle works on any
        // editor screen (not just where style-handler.js happens to load it).
        wp_register_script(
            'notificationx-block-controls',
            plugins_url( $index_js, __FILE__ ),
            array_merge( $asset_file['dependencies'], [ 'lodash' ] ),
            $asset_file['version'],
            false
        );

        // The inline block's edit.js reads `nx_style_handler.editor_type` to pick
        // the right preview-device store. Attach that global to the lightweight,
        // always-loaded controls handle so it is available on every editor screen
        // — without dragging in the heavy style-handler.js / wp-edit-site bundle.
        // The site editor overrides editor_type to 'edit-site' in StyleHandler.
        wp_localize_script( 'notificationx-block-controls', 'nx_style_handler', [
            'sth_nonce'   => wp_create_nonce( 'nx_style_handler_nonce' ),
            'editor_type' => 'edit-post',
        ] );

        $asset_file                   = include NOTIFICATIONX_PATH . 'blocks/notificationx/index.asset.php';
        $index_js                     = 'notificationx/index.js';
        wp_register_script(
            'notificationx-block-editor',
            plugins_url( $index_js, __FILE__ ),
            array_merge($asset_file['dependencies'], ['notificationx-block-controls']),
            $asset_file['version'],
            false // Editor script: must load in the head, which is the existing default.
        );

        $editor_css = 'notificationx/editor.css';
        wp_register_style(
            'notificationx-block-editor',
            plugins_url( $editor_css, __FILE__ ),
            array( 'notificationx-block-controls-css' ),
            filemtime( "{$dir}/{$editor_css}" )
        );

        $style_css = 'notificationx/style.css';
        wp_register_style(
            'notificationx-block',
            plugins_url( $style_css, __FILE__ ),
            [],
            filemtime( "{$dir}/{$style_css}" )
        );
        wp_register_script(
            'notificationx-block-frontend',
            plugins_url( 'notificationx/frontend.js', __FILE__ ),
            [],
            filemtime( "{$dir}/notificationx/frontend.js" ),
            true
        );
        wp_localize_script('notificationx-block-frontend', 'notificationxBlockRest', [
            'root'      => rest_url(),
        ]);
        register_block_type( 'notificationx-pro/notificationx',
            [
                'editor_script'   => 'notificationx-block-editor',
                'editor_style'    => 'notificationx-block-editor',
                // 'style'           => 'notificationx-block',
                // 'script'          => 'notificationx-block-frontend',
                'render_callback' => [ $this, 'notificationx_render_callback' ],
                'attributes'      => array(
                    'nx_id'   => array(
                        'type' => 'string',
                    ),
                    'blockId' => array(
                        'type' => 'string',
                    ),
                    'product_id' => array(
                        'type' => 'string',
                    ),
                ),
            ]
        );
        register_block_type( 'notificationx-pro/notificationx-render',
            [
                'render_callback' => [ $this, 'gutenberg_examples_dynamic_render_callback' ],
                'attributes'      => array(
                    'nx_id'   => array(
                        'type' => 'string',
                    ),
                    'blockId' => array(
                        'type' => 'string',
                    ),
                    'product_id' => array(
                        'type' => 'string',
                    ),
                    'post_type' => array(
                        'type' => 'string',
                    ),
                ),
            ]
        );

        // ── NotificationX Countdown Timer block ──────────────────────────
        $countdown_asset_path = NOTIFICATIONX_PATH . 'blocks/countdown/index.asset.php';
        $countdown_asset      = file_exists( $countdown_asset_path )
            ? include $countdown_asset_path
            : [ 'dependencies' => [], 'version' => NOTIFICATIONX_VERSION ];

        wp_register_script(
            'notificationx-countdown-editor',
            plugins_url( 'countdown/index.js', __FILE__ ),
            array_merge( $countdown_asset['dependencies'], [ 'notificationx-block-controls' ] ),
            $countdown_asset['version'],
            false // Editor script: must load in the head, which is the existing default.
        );
        wp_set_script_translations( 'notificationx-countdown-editor', 'notificationx' );

        $countdown_editor_css = 'countdown/editor.css';
        wp_register_style(
            'notificationx-countdown-editor',
            plugins_url( $countdown_editor_css, __FILE__ ),
            [ 'notificationx-block-controls-css' ],
            filemtime( "{$dir}/{$countdown_editor_css}" )
        );

        // Frontend base layout CSS — distinct handle from Elementor's `nx-countdown`
        // (which is only registered on Elementor hooks), same source file.
        wp_register_style(
            'notificationx-countdown-style',
            NOTIFICATIONX_PUBLIC_URL . 'css/nx-countdown.css',
            [],
            NOTIFICATIONX_VERSION
        );
        wp_register_script(
            'notificationx-countdown-frontend',
            plugins_url( 'countdown/frontend.js', __FILE__ ),
            [],
            filemtime( "{$dir}/countdown/frontend.js" ),
            true
        );

        register_block_type( 'notificationx/countdown', [
            'editor_script'   => 'notificationx-countdown-editor',
            'editor_style'    => 'notificationx-countdown-editor',
            // `style` is injected into both the editor canvas iframe and the
            // frontend (only when the block is present), so the base layout CSS
            // applies in the editor preview too.
            'style'           => 'notificationx-countdown-style',
            'render_callback' => [ $this, 'countdown_render_callback' ],
            'attributes'      => $this->countdown_block_attributes(),
        ] );
    }

    function notificationx_render_callback( $block_attributes, $content ) {
        if( ! is_admin() ){
            wp_enqueue_style('notificationx-block');
            wp_enqueue_script('notificationx-block-frontend');
        }
        if ( is_admin() || $this->isRestUrl() ) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
            do_action( 'nx_ignore_analytics' );
        }
        $nx_id      = ! empty( $block_attributes['nx_id'] ) ? esc_attr($block_attributes['nx_id']) : '';
        $product_id = ! empty( $block_attributes['product_id'] ) ? $block_attributes['product_id'] : '';
        $block_id   = ! empty( $block_attributes['blockId'] ) ? esc_attr($block_attributes['blockId']) : '';
        $html  = '<div class="' . $block_id . ' notificationx-block-wrapper" data-nx_id="' . $nx_id . '">';
        $html .= do_shortcode( "[notificationx_inline product_id='{$product_id}' id='{$nx_id}']" );
        $html .= '</div>';
        return $html;
    }

    function gutenberg_examples_dynamic_render_callback( $block_attributes, $content ) {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
        do_action( 'nx_ignore_analytics' );
        $nx_id          = ! empty( $block_attributes['nx_id'] ) ? esc_attr($block_attributes['nx_id']) : '';
        $product_id     = ! empty( $block_attributes['product_id'] ) ? $block_attributes['product_id'] : '';
        $post_type     = ! empty( $block_attributes['post_type'] ) ? $block_attributes['post_type'] : '';
        $html      = '<div class="' . $block_attributes['blockId'] . ' notificationx-block-wrapper">';
        if( 'wp_template' == $post_type ) {
            add_filter('nx_is_preview',function(){
                return true;
            });
            $product_id = wp_rand();
        }
        $shortcode = do_shortcode( "[notificationx_inline post_type='{$post_type}' product_id='{$product_id}' id='{$nx_id}' show_link=false]" );
        if ( $shortcode ) {
            $html .= $shortcode;
        } else {
            $html .= '<p class="nx-shortcode-notice">' . __( 'There is no data in this notification.', 'notificationx' ) . '</p>';
        }
        $html .= '</div>';

        return wp_kses($html, Helper::nx_allowed_html());
    }

    /**
     * Server-side attribute schema for the countdown block.
     *
     * Defaults MUST match the JS attribute defaults (blocks/countdown/components/attributes.js)
     * — Gutenberg omits attributes left at their default from the saved markup,
     * so the frontend relies on these defaults for any unchanged value.
     *
     * @return array
     */
    private function countdown_block_attributes() {
        return [
            'blockId'               => [ 'type' => 'string' ],
            'blockStyles'           => [ 'type' => 'object' ],
            'countdownType'         => [ 'type' => 'string',  'default' => 'due_date' ],
            'dueTime'               => [ 'type' => 'string',  'default' => '' ],
            'evergreenHours'        => [ 'type' => 'number',  'default' => 11 ],
            'evergreenMinutes'      => [ 'type' => 'number',  'default' => 59 ],
            'evergreenRecurring'    => [ 'type' => 'boolean', 'default' => false ],
            'recurringRestartAfter' => [ 'type' => 'number',  'default' => 0 ],
            'recurringStopTime'     => [ 'type' => 'string',  'default' => '' ],
            'layout'                => [ 'type' => 'string',  'default' => 'grid' ],
            'labelView'             => [ 'type' => 'string',  'default' => 'nx-countdown-label-block' ],
            'showDays'              => [ 'type' => 'boolean', 'default' => true ],
            'daysLabel'             => [ 'type' => 'string',  'default' => 'Days' ],
            'showHours'             => [ 'type' => 'boolean', 'default' => true ],
            'hoursLabel'            => [ 'type' => 'string',  'default' => 'Hours' ],
            'showMinutes'           => [ 'type' => 'boolean', 'default' => true ],
            'minutesLabel'          => [ 'type' => 'string',  'default' => 'Minutes' ],
            'showSeconds'           => [ 'type' => 'boolean', 'default' => true ],
            'secondsLabel'          => [ 'type' => 'string',  'default' => 'Seconds' ],
            'showSeparator'         => [ 'type' => 'boolean', 'default' => false ],
            'separatorStyle'        => [ 'type' => 'string',  'default' => 'dotted' ],
            'expireType'            => [ 'type' => 'string',  'default' => 'none' ],
            'expiryTitle'           => [ 'type' => 'string',  'default' => 'The countdown is finished!' ],
            'expiryText'            => [ 'type' => 'string',  'default' => 'Thank you for being part of this event.' ],
            'redirectUrl'           => [ 'type' => 'string',  'default' => '#' ],
        ];
    }

    /**
     * Renders the countdown block on the frontend.
     *
     * Mirrors \NotificationX\Extensions\Elementor\CountdownWidget::render() markup
     * (so the shared frontend logic stays identical) and prints the per-instance
     * scoped CSS computed in the editor.
     *
     * @param array $attributes Block attributes.
     * @return string
     */
    function countdown_render_callback( $attributes, $content = '' ) {
        if ( ! is_admin() ) {
            wp_enqueue_style( 'notificationx-countdown-style' );
            wp_enqueue_script( 'notificationx-countdown-frontend' );
        }

        $a = wp_parse_args( (array) $attributes, [
            'blockId'               => '',
            'blockStyles'           => [],
            'countdownType'         => 'due_date',
            'dueTime'               => '',
            'evergreenHours'        => 11,
            'evergreenMinutes'      => 59,
            'evergreenRecurring'    => false,
            'recurringRestartAfter' => 0,
            'recurringStopTime'     => '',
            'layout'                => 'grid',
            'labelView'             => 'nx-countdown-label-block',
            'showDays'              => true,
            'daysLabel'             => 'Days',
            'showHours'             => true,
            'hoursLabel'            => 'Hours',
            'showMinutes'           => true,
            'minutesLabel'          => 'Minutes',
            'showSeconds'           => true,
            'secondsLabel'          => 'Seconds',
            'showSeparator'         => false,
            'separatorStyle'        => 'dotted',
            'expireType'            => 'none',
            'expiryTitle'           => '',
            'expiryText'            => '',
            'redirectUrl'           => '#',
        ] );

        $block_id = ! empty( $a['blockId'] ) ? sanitize_html_class( $a['blockId'] ) : 'nx-countdown-' . wp_rand( 1000, 9999 );

        // GMT offset string (matches the JS `new Date(dateStr)` parsing).
        $gmt_offset = get_option( 'gmt_offset' );
        $offset_str = ( $gmt_offset < 0 ? '' : '+' ) . str_replace(
            [ '.25', '.5', '.75' ],
            [ ':15', ':30', ':45' ],
            (string) $gmt_offset
        );

        $due_date_str = '';
        if ( 'due_date' === $a['countdownType'] && ! empty( $a['dueTime'] ) ) {
            $due_date_str = gmdate( 'M d Y G:i:s', strtotime( $a['dueTime'] ) ) . ' ' . $offset_str;
        }

        $evergreen_seconds = 0;
        if ( 'evergreen' === $a['countdownType'] ) {
            $evergreen_seconds  = absint( $a['evergreenHours'] ) * HOUR_IN_SECONDS;
            $evergreen_seconds += absint( $a['evergreenMinutes'] ) * MINUTE_IN_SECONDS;
        }

        $separator_class = '';
        if ( ! empty( $a['showSeparator'] ) ) {
            $separator_class = 'nx-countdown-show-separator nx-countdown-separator-' . sanitize_html_class( $a['separatorStyle'] );
        }

        // ── Wrapper data attributes ──
        $wrapper_attrs  = ' data-countdown-id="' . esc_attr( $block_id ) . '"';
        $wrapper_attrs .= ' data-countdown-type="' . esc_attr( $a['countdownType'] ) . '"';
        $wrapper_attrs .= ' data-expire-type="' . esc_attr( $a['expireType'] ) . '"';

        if ( 'text' === $a['expireType'] ) {
            $wrapper_attrs .= ' data-expiry-title="' . esc_attr( wp_kses_post( $a['expiryTitle'] ) ) . '"';
            $wrapper_attrs .= ' data-expiry-text="' . esc_attr( wp_kses_post( $a['expiryText'] ) ) . '"';
        } elseif ( 'url' === $a['expireType'] ) {
            $wrapper_attrs .= ' data-redirect-url="' . esc_url( $a['redirectUrl'] ) . '"';
        }

        if ( 'evergreen' === $a['countdownType'] ) {
            $wrapper_attrs .= ' data-evergreen-time="' . absint( $evergreen_seconds ) . '"';
            if ( ! empty( $a['evergreenRecurring'] ) ) {
                $stop_time = ! empty( $a['recurringStopTime'] )
                    ? gmdate( 'M d Y G:i:s', strtotime( $a['recurringStopTime'] ) ) . ' ' . $offset_str
                    : '';
                $wrapper_attrs .= ' data-evergreen-recurring="' . absint( $a['recurringRestartAfter'] ) . '"';
                $wrapper_attrs .= ' data-evergreen-recurring-stop="' . esc_attr( $stop_time ) . '"';
            }
        }

        $layout_class = 'nx-countdown-layout-' . sanitize_html_class( ! empty( $a['layout'] ) ? $a['layout'] : 'grid' );
        $label_view   = sanitize_html_class( ! empty( $a['labelView'] ) ? $a['labelView'] : 'nx-countdown-label-block' );
        $list_classes = trim( 'nx-countdown-container ' . $layout_class . ' ' . $label_view . ' ' . $separator_class );

        // ── Time-unit items ──
        $units = [
            'days'    => [ 'show' => $a['showDays'],    'label' => $a['daysLabel'],    'attr' => 'data-days' ],
            'hours'   => [ 'show' => $a['showHours'],   'label' => $a['hoursLabel'],   'attr' => 'data-hours' ],
            'minutes' => [ 'show' => $a['showMinutes'], 'label' => $a['minutesLabel'], 'attr' => 'data-minutes' ],
            'seconds' => [ 'show' => $a['showSeconds'], 'label' => $a['secondsLabel'], 'attr' => 'data-seconds' ],
        ];

        $items_html = '';
        foreach ( $units as $unit => $data ) {
            if ( empty( $data['show'] ) ) {
                continue;
            }
            $label_html = '';
            if ( ! empty( $data['label'] ) ) {
                $label_html = '<span class="nx-countdown-label">' . esc_html( $data['label'] ) . '</span>';
            }
            $items_html .= '<li class="nx-countdown-item"><div class="nx-countdown-' . esc_attr( $unit ) . '">'
                . '<span ' . $data['attr'] . ' class="nx-countdown-digits">00</span>'
                . $label_html
                . '</div></li>';
        }

        $html  = $this->countdown_inline_styles( $block_id, $a['blockStyles'] );
        $html .= '<div class="' . esc_attr( $block_id ) . ' nx-countdown-wrapper"' . $wrapper_attrs . '>';
        $html .= '<ul id="nx-countdown-' . esc_attr( $block_id ) . '" class="' . esc_attr( $list_classes ) . '" data-date="' . esc_attr( $due_date_str ) . '">';
        $html .= $items_html;
        $html .= '</ul></div>';

        return $html;
    }

    /**
     * Builds the per-instance scoped <style> tag from the editor-computed CSS.
     *
     * @param string       $block_id     Unique block class (scopes every selector).
     * @param array|object $block_styles { desktop, tab, mobile } raw CSS strings.
     * @return string
     */
    private function countdown_inline_styles( $block_id, $block_styles ) {
        $block_styles = (array) $block_styles;
        $desktop = isset( $block_styles['desktop'] ) ? (string) $block_styles['desktop'] : '';
        $tab     = isset( $block_styles['tab'] )     ? (string) $block_styles['tab']     : '';
        $mobile  = isset( $block_styles['mobile'] )  ? (string) $block_styles['mobile']  : '';

        $css = $desktop;
        if ( '' !== trim( $tab ) ) {
            $css .= '@media (max-width:1024px){' . $tab . '}';
        }
        if ( '' !== trim( $mobile ) ) {
            $css .= '@media (max-width:767px){' . $mobile . '}';
        }

        $css = trim( $css );
        if ( '' === $css ) {
            return '';
        }

        // Defensive: CSS never needs "<"; strip it to prevent a </style> breakout.
        $css = str_replace( '<', '', $css );

        return '<style>' . $css . '</style>';
    }

    function isRestUrl() {
        if ( empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
            return false;
        }
        return true;
    }

}
