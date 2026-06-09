<?php
/**
 * Exit Intent Popup Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\ExitIntent;

use NotificationX\NotificationX;
use NotificationX\GetInstance;
use NotificationX\Core\Rules;
use NotificationX\Core\Helper;
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

    /**
     * Elementor seed-theme registry for the "Build With Elementor" modal.
     * Populated alongside the JSON files in jsons/ (see Task 07).
     *
     * @var array<string, array{label:string, value:string, icon:string, column:string, title:string}>
     */
    public $elementor_themes = [];

    public function __construct() {
        parent::__construct();
        add_action( 'init', [ $this, 'register_post_type' ] );
        add_filter( 'get_edit_post_link', [ $this, 'filter_edit_post_link' ], 10, 3 );
        add_filter( 'nx_filtered_post', [ $this, 'inject_elementor_html' ], 10, 2 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_elementor_assets' ], 20 );
        add_action( 'wp_head', [ $this, 'print_section_constraint_css' ], 99 );
        add_action( 'elementor/documents/register_controls', [ $this, 'register_popup_layout_controls' ] );
        add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'enqueue_editor_live_preview' ] );
    }

    /**
     * Print the popup section's width constraint inline in <head>.
     *
     * Two cases:
     * 1. Sections that have the `nx-exit-intent-section` class baked in via the
     *    seed JSON's css_classes setting — constrained by the first rule.
     * 2. The Elementor editor preview iframe loads the nx_exit_intent post
     *    directly as its own page (body.single-nx_exit_intent). The second
     *    rule constrains ANY section on that page, including legacy imports
     *    made before css_classes was added to the seed JSON.
     */
    public function print_section_constraint_css() {
        // Width is governed by the Layout panel's Width control via the
        // `--nx-exit-width` CSS variable; fall back to the historical 540px cap
        // when it is unset (older popups / default).
        $vars = '';
        if ( is_singular( 'nx_exit_intent' ) ) {
            // Editor preview / direct view: resolve the variable from the saved
            // Layout-panel settings on this document.
            $layout = $this->get_popup_layout_settings( get_the_ID() );
            if ( ! empty( $layout['width'] ) ) {
                $vars .= '--nx-exit-width:' . $layout['width'] . ';';
            }
            if ( ! empty( $layout['height'] ) ) {
                $vars .= '--nx-exit-height:' . $layout['height'] . ';';
            }
        }
        echo "<style id='nx-exit-intent-section-constraint'>\n";
        if ( '' !== $vars ) {
            echo "body.single-nx_exit_intent{" . $vars . "}\n";
        }
        echo ".nx-exit-intent-section{width:100%!important;max-width:var(--nx-exit-width,540px)!important;margin-left:auto!important;margin-right:auto!important;}\n";
        if ( is_singular( 'nx_exit_intent' ) ) {
            echo "body.single-nx_exit_intent .elementor-section,body.single-nx_exit_intent .e-con{width:100%!important;max-width:var(--nx-exit-width,540px)!important;margin-left:auto!important;margin-right:auto!important;}\n";
        }
        echo "</style>\n";
    }

    /**
     * Server-side: inject the Elementor-rendered HTML + a `mode` flag into the
     * REST payload so the React popup shell can render it.
     *
     * Runs for every campaign settings array via the `nx_filtered_post` filter;
     * scoped to Exit Intent campaigns that have a linked, published nx_exit_intent.
     */
    public function inject_elementor_html( $settings, $params ) {
        if ( empty( $settings['source'] ) || $settings['source'] !== $this->id ) {
            return $settings;
        }
        $elementor_id = isset( $settings['elementor_id'] ) ? (int) $settings['elementor_id'] : 0;
        if ( ! $elementor_id || ! class_exists( '\\Elementor\\Plugin' ) ) {
            $settings['mode'] = 'built_in';
            return $settings;
        }
        if ( get_post_status( $elementor_id ) !== 'publish' ) {
            $settings['mode'] = 'built_in';
            return $settings;
        }

        $resolved_id = apply_filters( 'wpml_object_id', $elementor_id, 'nx_exit_intent', true );
        $html = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $resolved_id, false );

        // Surface the Layout-panel settings (registered in register_popup_layout_controls)
        // so the popup runtime can honour them, and set --nx-exit-width on a wrapper so
        // the section width constraint reflects the configured Width on the frontend.
        $layout       = $this->get_popup_layout_settings( $resolved_id );
        $wrapper_vars = '';
        if ( ! empty( $layout['width'] ) ) {
            $wrapper_vars .= '--nx-exit-width:' . $layout['width'] . ';';
        }
        if ( ! empty( $layout['height'] ) ) {
            $wrapper_vars .= '--nx-exit-height:' . $layout['height'] . ';';
        }
        if ( '' !== $wrapper_vars ) {
            $html = '<div class="nx-exit-intent-elementor-wrap" style="' . esc_attr( $wrapper_vars ) . '">' . $html . '</div>';
        }

        $settings['mode']           = 'elementor';
        $settings['elementor_html'] = $html;
        $settings['popup_layout']   = $layout;
        return $settings;
    }

    /**
     * Read the Layout-panel settings off an nx_exit_intent Elementor document and
     * normalise them into a flat array the popup runtime can consume.
     *
     * @param int $elementor_id
     * @return array
     */
    protected function get_popup_layout_settings( $elementor_id ) {
        $defaults = [
            'width'             => '',
            'height_mode'       => 'fit',
            'height'            => '',
            'horizontal'        => 'center',
            'vertical'          => 'center',
            'overlay'           => true,
            'close_button'      => true,
            'entrance_animation' => '',
            'exit_animation'    => '',
        ];

        if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
            return $defaults;
        }
        $document = \Elementor\Plugin::$instance->documents->get( $elementor_id );
        if ( ! $document ) {
            return $defaults;
        }

        $size = static function ( $value ) {
            if ( is_array( $value ) && isset( $value['size'] ) && '' !== $value['size'] ) {
                return $value['size'] . ( isset( $value['unit'] ) ? $value['unit'] : 'px' );
            }
            return '';
        };

        $height_mode = $document->get_settings( 'nx_popup_height' );

        // Width precedence: the Layout-panel Width control if the user set one,
        // otherwise the design's own top-level container width (boxed_width).
        // Without this, every popup falls back to the CSS 540px default, which
        // crushes wider designs (e.g. the two-column 896px themes).
        $width = $size( $document->get_settings( 'nx_popup_width' ) );
        if ( '' === $width ) {
            $width = $this->resolve_design_width( $document );
        }

        return [
            'width'              => $width,
            'height_mode'        => $height_mode ? $height_mode : 'fit',
            'height'             => 'custom' === $height_mode ? $size( $document->get_settings( 'nx_popup_custom_height' ) ) : '',
            'horizontal'         => $document->get_settings( 'nx_popup_horizontal' ) ?: 'center',
            'vertical'           => $document->get_settings( 'nx_popup_vertical' ) ?: 'center',
            'overlay'            => 'yes' === $document->get_settings( 'nx_popup_overlay' ),
            'close_button'       => 'yes' === $document->get_settings( 'nx_popup_close_button' ),
            'entrance_animation' => (string) $document->get_settings( 'nx_popup_entrance_animation' ),
            'exit_animation'     => (string) $document->get_settings( 'nx_popup_exit_animation' ),
        ];
    }

    /**
     * Derive the popup's intended width from its Elementor design — the widest
     * px width among the top-level elements (boxed_width / width / content_width).
     * Used as the default popup width when no Layout-panel Width is configured,
     * so each design keeps its own width instead of the 540px CSS fallback.
     * Only top-level elements are inspected so inner column widths are ignored.
     *
     * @param \Elementor\Core\Base\Document $document
     * @return string e.g. "896px", or '' when no explicit px width is defined.
     */
    protected function resolve_design_width( $document ) {
        if ( ! $document || ! method_exists( $document, 'get_elements_data' ) ) {
            return '';
        }
        $data = $document->get_elements_data();
        if ( empty( $data ) || ! is_array( $data ) ) {
            return '';
        }
        $max = 0;
        foreach ( $data as $element ) {
            if ( empty( $element['settings'] ) || ! is_array( $element['settings'] ) ) {
                continue;
            }
            foreach ( [ 'boxed_width', 'width', 'content_width' ] as $key ) {
                $val = isset( $element['settings'][ $key ] ) ? $element['settings'][ $key ] : null;
                if ( is_array( $val ) && isset( $val['size'] ) && is_numeric( $val['size'] )
                    && ( ! isset( $val['unit'] ) || 'px' === $val['unit'] ) ) {
                    $max = max( $max, (float) $val['size'] );
                }
            }
        }
        return $max > 0 ? $max . 'px' : '';
    }

    /**
     * Make sure Elementor's per-document CSS/JS is enqueued on every page where
     * an Exit Intent campaign can fire — get_builder_content_for_display alone
     * registers some assets, but not on pages other than the document's own URL.
     */
    public function enqueue_elementor_assets() {
        if ( ! class_exists( '\\Elementor\\Core\\Files\\CSS\\Post' ) ) {
            return;
        }
        $ids = [];
        try {
            $posts = \NotificationX\Core\PostType::get_instance()->get_posts( [ 'source' => $this->id ] );
            foreach ( $posts as $post ) {
                if ( ! empty( $post['elementor_id'] ) && empty( $post['enabled'] ) === false ) {
                    $ids[] = (int) $post['elementor_id'];
                }
            }
        } catch ( \Exception $e ) {
            return;
        }
        foreach ( $ids as $id ) {
            try {
                $css = \Elementor\Core\Files\CSS\Post::create( $id );
                if ( $css ) {
                    $css->enqueue();
                }
            } catch ( \Exception $e ) {
                // swallow — one broken doc shouldn't break the rest.
            }
        }
    }

    /**
     * Register the post type that backs Elementor-designed Exit Intent popups.
     *
     * Mirrors PressBar's `nx_bar`: not public, but publicly_queryable so Elementor's
     * preview routes resolve. `supports[]` must include `elementor` for Elementor's
     * documents API to treat this as a first-class document.
     *
     * @return void
     */
    public static function register_post_type() {
        register_post_type( 'nx_exit_intent', [
            'label'               => __( 'NotificationX Exit Intent', 'notificationx' ),
            'public'              => false,
            'publicly_queryable'  => true,
            'show_ui'             => false,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => false,
            'exclude_from_search' => true,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'menu_icon'           => 'dashicons-admin-page',
            'supports'            => [ 'title', 'content', 'author', 'elementor' ],
        ] );
    }

    /**
     * Add a "Layout" section to the Elementor document-settings panel, but ONLY
     * for nx_exit_intent documents. Mirrors Elementor Pro's Popup → Layout panel
     * (Width / Height / Position / Overlay / Close Button / Animations).
     *
     * Hooked on `elementor/documents/register_controls`, which fires for every
     * document right after its built-in controls register. We bail for any other
     * post type so these controls never leak into normal pages/posts.
     *
     * Width is wired live via the `--nx-exit-width` CSS variable that
     * print_section_constraint_css()/inject_elementor_html() read; the remaining
     * controls persist into the document's page settings (`_elementor_page_settings`)
     * so the popup runtime can consume them.
     *
     * @param \Elementor\Core\Base\Document $document
     * @return void
     */
    public function register_popup_layout_controls( $document ) {
        if ( ! is_object( $document ) || ! method_exists( $document, 'get_post' ) ) {
            return;
        }
        $post = $document->get_post();
        if ( ! $post || 'nx_exit_intent' !== $post->post_type ) {
            return;
        }

        $cm = '\Elementor\Controls_Manager';

        $document->start_controls_section(
            'nx_popup_layout',
            [
                'label' => __( 'Layout', 'notificationx' ),
                'tab'   => $cm::TAB_SETTINGS,
            ]
        );

        $document->add_responsive_control(
            'nx_popup_width',
            [
                'label'      => __( 'Width', 'notificationx' ),
                'type'       => $cm::SLIDER,
                'size_units' => [ 'px', '%', 'vw' ],
                'range'      => [
                    'px' => [ 'min' => 100, 'max' => 1140 ],
                    '%'  => [ 'min' => 10,  'max' => 100 ],
                    'vw' => [ 'min' => 10,  'max' => 100 ],
                ],
                // No default: when the user hasn't set a Width, the popup falls back
                // to the design's own container width (resolve_design_width()), not a
                // fixed 540px — otherwise wider designs (e.g. the 896px two-column
                // themes) get crushed. An explicit value here still wins.
                //
                // NOTE: no `selectors` key — Elementor silently drops document-level
                // controls that carry `selectors`. The value is applied server-side
                // via the --nx-exit-width variable in print_section_constraint_css()
                // (preview) and inject_elementor_html() (frontend) instead.
                'default'    => [ 'unit' => 'px', 'size' => '' ],
            ]
        );

        $document->add_control(
            'nx_popup_height',
            [
                'label'   => __( 'Height', 'notificationx' ),
                'type'    => $cm::SELECT,
                'default' => 'fit',
                'options' => [
                    'fit'    => __( 'Fit To Content', 'notificationx' ),
                    'custom' => __( 'Custom Height', 'notificationx' ),
                ],
            ]
        );

        $document->add_responsive_control(
            'nx_popup_custom_height',
            [
                'label'      => __( 'Custom Height', 'notificationx' ),
                'type'       => $cm::SLIDER,
                'size_units' => [ 'px', 'vh' ],
                'range'      => [
                    'px' => [ 'min' => 100, 'max' => 1000 ],
                    'vh' => [ 'min' => 10,  'max' => 100 ],
                ],
                'default'    => [ 'unit' => 'px', 'size' => 480 ],
                'condition'  => [ 'nx_popup_height' => 'custom' ],
                // No `selectors` (see nx_popup_width note); applied via --nx-exit-height.
            ]
        );

        $document->add_control(
            'nx_popup_position_heading',
            [
                'label'     => __( 'Position', 'notificationx' ),
                'type'      => $cm::HEADING,
                'separator' => 'before',
            ]
        );

        $document->add_responsive_control(
            'nx_popup_horizontal',
            [
                'label'   => __( 'Horizontal', 'notificationx' ),
                'type'    => $cm::CHOOSE,
                'default' => 'center',
                'options' => [
                    'start'  => [ 'title' => __( 'Start', 'notificationx' ),  'icon' => 'eicon-h-align-left' ],
                    'center' => [ 'title' => __( 'Center', 'notificationx' ), 'icon' => 'eicon-h-align-center' ],
                    'end'    => [ 'title' => __( 'End', 'notificationx' ),    'icon' => 'eicon-h-align-right' ],
                ],
            ]
        );

        $document->add_responsive_control(
            'nx_popup_vertical',
            [
                'label'   => __( 'Vertical', 'notificationx' ),
                'type'    => $cm::CHOOSE,
                'default' => 'center',
                'options' => [
                    'start'  => [ 'title' => __( 'Top', 'notificationx' ),    'icon' => 'eicon-v-align-top' ],
                    'center' => [ 'title' => __( 'Middle', 'notificationx' ), 'icon' => 'eicon-v-align-middle' ],
                    'end'    => [ 'title' => __( 'Bottom', 'notificationx' ), 'icon' => 'eicon-v-align-bottom' ],
                ],
            ]
        );

        $document->add_control(
            'nx_popup_overlay',
            [
                'label'        => __( 'Overlay', 'notificationx' ),
                'type'         => $cm::SWITCHER,
                'label_on'     => __( 'Show', 'notificationx' ),
                'label_off'    => __( 'Hide', 'notificationx' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'separator'    => 'before',
            ]
        );

        $document->add_control(
            'nx_popup_close_button',
            [
                'label'        => __( 'Close Button', 'notificationx' ),
                'type'         => $cm::SWITCHER,
                'label_on'     => __( 'Show', 'notificationx' ),
                'label_off'    => __( 'Hide', 'notificationx' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $document->add_control(
            'nx_popup_entrance_animation',
            [
                'label'     => __( 'Entrance Animation', 'notificationx' ),
                'type'      => $cm::ANIMATION,
                'separator' => 'before',
            ]
        );

        $document->add_control(
            'nx_popup_exit_animation',
            [
                'label' => __( 'Exit Animation', 'notificationx' ),
                'type'  => $cm::EXIT_ANIMATION,
            ]
        );

        $document->end_controls_section();
    }

    /**
     * Live-preview bridge for the Layout panel inside the Elementor editor.
     *
     * Document-level controls can't carry a `selectors` key (Elementor drops
     * them), so Width/Height don't get Elementor's built-in live CSS injection.
     * This registers page-settings change callbacks that write the
     * `--nx-exit-width` / `--nx-exit-height` variables straight onto the preview
     * iframe's <body> as the slider moves — so changes reflect instantly instead
     * of only after a reload.
     *
     * Scoped to nx_exit_intent documents only.
     *
     * @return void
     */
    public function enqueue_editor_live_preview() {
        if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
            return;
        }
        $post_id = \Elementor\Plugin::$instance->editor->get_post_id();
        if ( ! $post_id || 'nx_exit_intent' !== get_post_type( $post_id ) ) {
            return;
        }

        $js = <<<'JS'
( function ( $ ) {
    function toCss( value ) {
        if ( value && '' !== value.size && null != value.size ) {
            return value.size + ( value.unit || 'px' );
        }
        return '';
    }
    function previewBody() {
        var frame = elementor.$preview && elementor.$preview[0];
        var win   = frame && frame.contentWindow;
        return win && win.document ? win.document.body : null;
    }
    function applyVar( name, value ) {
        var body = previewBody();
        if ( ! body ) { return; }
        var css = toCss( value );
        if ( css ) { body.style.setProperty( name, css ); }
        else { body.style.removeProperty( name ); }
    }
    $( window ).on( 'elementor:init', function () {
        if ( 'undefined' === typeof elementor || ! elementor.settings || ! elementor.settings.page ) {
            return;
        }
        elementor.settings.page.addChangeCallback( 'nx_popup_width', function ( value ) {
            applyVar( '--nx-exit-width', value );
        } );
        elementor.settings.page.addChangeCallback( 'nx_popup_custom_height', function ( value ) {
            applyVar( '--nx-exit-height', value );
        } );
    } );
} )( jQuery );
JS;

        wp_add_inline_script( 'elementor-editor', $js );
    }

    /**
     * Create a new `nx_exit_intent` Elementor document from a seed theme and
     * respond to the admin with the new post ID + Elementor edit URL.
     *
     * Counterpart of PressBar::create_bar_of_type_bar_with_elementor().
     *
     * @param array $params { theme_id: string }
     * @return void Always responds via wp_send_json_{success,error}.
     */
    public function create_exit_intent_with_elementor( $params ) {
        if ( empty( $params['theme_id'] ) ) {
            wp_send_json_error( 'missing_theme_id' );
        }

        $theme    = sanitize_text_field( $params['theme_id'] );
        $importer = new Importer();

        $ID = $importer->create_nx( [
            'theme'      => $theme,
            'post_title' => 'Design for NotificationX Exit Intent - ',
        ] );

        if ( $ID && ! is_wp_error( $ID ) ) {
            // Strip theme chrome — popup HTML inlines into the host page.
            update_post_meta( $ID, '_wp_page_template', 'elementor_canvas' );
            wp_send_json_success( [
                'context' => [
                    'themes'              => null,
                    'elementor_id'        => $ID,
                    'elementor_edit_link' => \Elementor\Plugin::$instance->documents->get( $ID )->get_edit_url(),
                ],
            ] );
        } else {
            wp_send_json_error( is_wp_error( $ID ) ? $ID->get_error_message() : 'failed' );
        }
    }

    /**
     * Delete the linked `nx_exit_intent` Elementor post (and its WPML translations).
     *
     * @param int|string $elementor_id
     * @return void
     */
    public function delete_elementor_post( $elementor_id ) {
        if ( empty( $elementor_id ) ) {
            return;
        }
        $languages = apply_filters( 'wpml_active_languages', null );
        if ( is_array( $languages ) ) {
            foreach ( $languages as $lang => $val ) {
                $translated_id = apply_filters( 'wpml_object_id', $elementor_id, 'nx_exit_intent', false, $lang );
                if ( $translated_id ) {
                    wp_delete_post( $translated_id, true );
                }
            }
            return;
        }
        wp_delete_post( $elementor_id, true );
    }

    /**
     * Redirect WP "Edit" links for nx_exit_intent posts straight into the Elementor editor.
     *
     * @param string $link
     * @param int    $id
     * @param string $context
     * @return string
     */
    public function filter_edit_post_link( $link, $id, $context ) {
        $post = get_post( $id );
        if ( $post && 'nx_exit_intent' === $post->post_type && class_exists( '\\Elementor\\Plugin' ) ) {
            return \Elementor\Plugin::$instance->documents->get( $id )->get_edit_url();
        }
        return $link;
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
                'is_pro'   => true,
            ],
            'theme-two' => [
                'source'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/exit-intent/exit-intent-theme-two.png',
                'defaults' => [
                    'exit_intent_sale_badge'       => __( 'Flash Sale', 'notificationx' ),
                    'exit_intent_sale_headline'    => __( '50% OFF', 'notificationx' ),
                    'exit_intent_sale_desc'        => __( 'ON ENTIRE ORDER', 'notificationx' ),
                    'exit_intent_button_text'      => __( 'Shop The Flash Sale Now', 'notificationx' ),
                    'exit_intent_dismiss_text'     => __( 'NO, THANKS!', 'notificationx' ),
                    'exit_intent_image_url'        => [ 'url' => 'https://notificationx.com/wp-content/uploads/2026/05/exit-intend-theme-two.jpg' ],
                    'position'                     => 'center',
                ],
                'column' => '5',
                'is_pro'   => true,
            ],
             'theme-seven' => [
                'source'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/exit-intent/exit-intent-theme-six.png',
                'defaults' => [
                    'exit_intent_t7_headline'          => __( 'Turn Your House Into a Home', 'notificationx' ),
                    'exit_intent_t7_discount_text'     => __( 'Your First Order Comes With a Surprise Deal!', 'notificationx' ),
                    'exit_intent_t7_description'       => __( 'Handpicked decor that feels like home the moment it arrives.', 'notificationx' ),
                    'exit_intent_t7_email_placeholder' => __( 'Enter your email', 'notificationx' ),
                    'exit_intent_button_text'          => __( 'SEND COUPON', 'notificationx' ),
                    'exit_intent_image_url'            => [ 'url' => 'https://notificationx.com/wp-content/uploads/2026/05/exit-intend-theme-five.png' ],
                    'position'                         => 'center',
                ],
                'column' => '5',
                'is_pro'   => true,
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
                    'exit_intent_image_url'          => [ 'url' => 'https://notificationx.com/wp-content/uploads/2026/05/exit-intend-theme-six.jpg' ],
                    'position'                       => 'center',
                ],
                'column' => '5',
                'is_pro'   => true,
            ],
        ];

        // Elementor seed themes for the "Build With Elementor" modal.
        // Each entry has a matching JSON file under jsons/{value}.json (Task 07).
        // Preview images live under assets/admin/images/extensions/themes/exit-intent-elementor/.
        // Until per-theme Elementor previews are rendered, we fall back to the existing
        // built-in screenshots so the modal renders with a real image.
        // Order intentionally mirrors the built-in $themes preset order above
        // (one, four, three, six, two, seven, five) so the Elementor modal lists
        // the same designs in the same sequence as the Default-theme picker.
        //
        // Unlike $themes (processed by Extension::__nx_themes), this array is fed
        // straight to the radio-card field, so we must resolve the pro badge here:
        // only flag a theme as pro when the Pro plugin is NOT active.
        $is_pro_badge = ! NotificationX::is_pro();
        $this->elementor_themes = [
            'theme-one' => [
                'label'  => 'theme-one',
                'value'  => 'theme-one',
                'icon'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/exit-intent/exit-intent-theme-one.png',
                'column' => '6',
                'title'  => 'Exit Intent Theme One',
            ],
            'theme-four' => [
                'label'  => 'theme-four',
                'value'  => 'theme-four',
                'icon'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/exit-intent/exit-intent-theme-four.png',
                'column' => '6',
                'title'  => 'Exit Intent Theme Four',
            ],
            'theme-three' => [
                'label'  => 'theme-three',
                'value'  => 'theme-three',
                'icon'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/exit-intent/exit-intent-theme-three.png',
                'column' => '6',
                'title'  => 'Exit Intent Theme Three',
            ],
            'theme-six' => [
                'label'  => 'theme-six',
                'value'  => 'theme-six',
                'icon'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/exit-intent/exit-intent-theme-six.png',
                'column' => '6',
                'title'  => 'Exit Intent Theme Six',
                'is_pro' => $is_pro_badge,
            ],
            'theme-two' => [
                'label'  => 'theme-two',
                'value'  => 'theme-two',
                'icon'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/exit-intent/exit-intent-theme-two.png',
                'column' => '6',
                'title'  => 'Exit Intent Theme Two',
                'is_pro' => $is_pro_badge,
            ],
            'theme-seven' => [
                'label'  => 'theme-seven',
                'value'  => 'theme-seven',
                'icon'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/exit-intent/exit-intent-theme-seven.png',
                'column' => '6',
                'title'  => 'Exit Intent Theme Seven',
                'is_pro' => $is_pro_badge,
            ],
            'theme-five' => [
                'label'  => 'theme-five',
                'value'  => 'theme-five',
                'icon'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/exit-intent/exit-intent-theme-five.png',
                'column' => '6',
                'title'  => 'Exit Intent Theme Five',
                'is_pro' => $is_pro_badge,
            ],
        ];
    }

    public function init_fields() {
        parent::init_fields();
        add_filter( 'nx_content_fields',        [ $this, 'content_fields' ],       999 );
        add_filter( 'nx_design_tab_fields',     [ $this, 'design_fields' ],        99  );
        add_filter( 'nx_design_tab_fields',     [ $this, 'builder_tabs_fields' ],  100 );
        add_filter( 'nx_customize_fields',      [ $this, 'customize_fields' ],     999 );
        add_filter( 'nx_display_fields',        [ $this, 'display_fields' ],       999 );

        // Hide per-theme content sections when an Elementor doc is linked (Task 06).
        // We deliberately do NOT hide the themes radio-card itself — keeping it
        // visible on the Default tab lets users switch back to a built-in theme
        // after they've imported an Elementor design.
        add_filter( 'nx_content_fields',        [ $this, 'suppress_when_elementor' ],   1001 );

        // Hide the whole Content wizard step when an Elementor design is linked —
        // suppress_when_elementor() empties every content section in that case, so
        // the step would otherwise render as a blank tab.
        add_filter( 'nx_metabox_tabs',          [ $this, 'hide_content_tab_for_elementor' ] );
    }

    /**
     * Remove the Content step from the wizard for Exit Intent campaigns that are
     * built with Elementor. Elementor owns all of the popup's content there (every
     * `exit_intent_*_section` is suppressed by suppress_when_elementor()), leaving
     * the Content tab empty. Built-in themes keep the Content step, and all other
     * sources are untouched.
     *
     * The tab stays visible UNLESS (source is Exit Intent AND elementor_id is a
     * number), expressed below as the OR'd "show when" rule.
     */
    public function hide_content_tab_for_elementor( $tabs ) {
        if ( empty( $tabs['content_tab'] ) ) {
            return $tabs;
        }
        $show_rule = Rules::_logicalRule( [
            Rules::_is( 'source', $this->id, true ),
            Rules::_isOfType( 'elementor_id', 'number', true ),
        ], 'or' );
        $tabs['content_tab'] = Rules::add( $show_rule, $tabs['content_tab'] );
        return $tabs;
    }

    /**
     * Hide every per-theme content section once an Elementor design is linked.
     * Elementor owns all of these fields; showing them would let users edit
     * values with no visible effect on the rendered popup.
     */
    public function suppress_when_elementor( $fields ) {
        foreach ( $fields as $key => $field ) {
            if ( strpos( $key, 'exit_intent_' ) === 0 && substr( $key, -8 ) === '_section' ) {
                $fields[ $key ] = Rules::isOfType( 'elementor_id', 'number', true, $field );
            }
        }
        return $fields;
    }

    /**
     * @deprecated Kept for back-compat only; the filter that called this has
     * been removed so the themes radio stays visible on the Default tab even
     * when an Elementor design is linked (lets users switch back).
     */
    public function suppress_themes_radio( $fields ) {
        if ( ! isset( $fields['themes']['fields']['themes']['rules'] ) ) {
            return $fields;
        }
        $existing   = $fields['themes']['fields']['themes']['rules'];
        // Additional clause: either we're NOT on Exit Intent (so this rule
        // shouldn't change anything for other sources), OR elementor_id is
        // not yet set. Combined via AND with the existing tab-gate so other
        // sources (e.g. PressBar) keep their tab-based hiding intact.
        $additional = Rules::logicalRule( [
            Rules::is( 'source', $this->id, true ),
            Rules::isOfType( 'elementor_id', 'number', true ),
        ], 'or' );
        $fields['themes']['fields']['themes']['rules'] = Rules::logicalRule( [
            $existing,
            $additional,
        ], 'and' );
        return $fields;
    }

    /**
     * Restructures the Themes section into two tabs:
     *   1. Default — original 7 React themes grid (the `themes` radio-card,
     *      already gated to themes_tab='for_desktop' in GlobalFields).
     *   2. Custom — Build With Elementor button + modal, plus Edit/Remove
     *      and Install fallback after a doc is linked.
     *
     * Matches Notification Bar's Default/Custom tab structure.
     */
    public function builder_tabs_fields( $fields ) {
        if ( empty( $fields['themes']['fields']['themes_section']['fields']['themes_tab']['fields'] ) ) {
            return $fields;
        }
        $tab =& $fields['themes']['fields']['themes_section']['fields']['themes_tab']['fields'];

        // Hide the global "For Desktop" / "For Mobile" tabs for Exit Intent only — we
        // ship our own Default + Custom tabs below.
        foreach ( [ 'for_desktop', 'for_mobile' ] as $key ) {
            if ( isset( $tab[ $key ] ) ) {
                $tab[ $key ] = Rules::is( 'source', $this->id, true, $tab[ $key ] );
            }
        }

        // Expand the global themes radio-card so it ALSO renders when our new
        // Default tab is active. Without this, the radio would never appear for
        // Exit Intent — its existing rule is hard-coded to themes_tab='for_desktop'.
        if ( isset( $fields['themes']['fields']['themes'] ) ) {
            $fields['themes']['fields']['themes']['rules'] = Rules::logicalRule( [
                Rules::is( 'themes_tab', 'for_desktop' ),
                Rules::is( 'themes_tab', 'exit_intent_default_tab' ),
            ], 'or' );
        }

        $is_installed          = Helper::is_plugin_installed( 'elementor/elementor.php' );
        $install_activate_text = $is_installed
            ? __( 'Activate Elementor', 'notificationx' )
            : __( 'Install Elementor', 'notificationx' );

        // Default tab — unique name (no collision with global for_desktop).
        $tab[] = [
            'label' => __( 'Default', 'notificationx' ),
            'name'  => 'exit_intent_default_tab',
            'id'    => 'exit_intent_default_tab',
            'type'  => 'section',
            'icon'  => NOTIFICATIONX_ADMIN_URL . 'images/icons/nxbar-presets-icon.svg',
            'rules' => Rules::is( 'source', $this->id ),
        ];

        // The Custom tab — only renders for Exit Intent campaigns.
        $tab[] = [
            'label'  => __( 'Custom', 'notificationx' ),
            'name'   => 'exit_intent_custom_tab',
            'id'     => 'exit_intent_custom_tab',
            'type'   => 'section',
            'icon'   => NOTIFICATIONX_ADMIN_URL . 'images/icons/nxbar-custom-tab.svg',
            'rules'  => Rules::is( 'source', $this->id ),
            'fields' => [

                // Preview image / empty-state illustration (reuses NxBarPresets).
                'exit_intent_custom_preview' => [
                    'name'     => 'exit_intent_custom_preview',
                    'type'     => 'exit-intent-custom',
                    'label'    => __( 'Exit Intent', 'notificationx' ),
                    'priority' => 5,
                ],

                // Edit With Elementor — shown when an Elementor doc is linked.
                'elementor_edit_link' => [
                    'name'     => 'elementor_edit_link',
                    'type'     => 'button',
                    'text'     => __( 'Edit With Elementor', 'notificationx' ),
                    'href'     => -1,
                    'priority' => 10,
                    'target'   => '_blank',
                    'rules'    => Rules::logicalRule( [
                        Rules::is( 'elementor_edit_link', false, true ),
                        Rules::isOfType( 'elementor_edit_link', 'string' ),
                        Rules::is( 'elementor_id', false, true ),
                    ] ),
                ],

                // Remove — deletes the Elementor doc and resets state.
                'nx-exit-intent_with_elementor-remove' => [
                    'name'     => 'nx-exit-intent_with_elementor-remove',
                    'type'     => 'button',
                    'text'     => __( 'Remove', 'notificationx' ),
                    'priority' => 15,
                    'rules'    => Rules::logicalRule( [
                        Rules::is( 'elementor_id', false, true ),
                        Rules::is( 'is_elementor', true ),
                        Rules::is( 'source', $this->id ),
                    ] ),
                    'ajax'     => [
                        'on'       => 'click',
                        'api'      => '/notificationx/v1/exit-intent/elementor/remove',
                        'data'     => [ 'elementor_id' => '@elementor_id' ],
                        'hideSwal' => true,
                    ],
                    'trigger'  => [
                        [
                            'type'   => 'setFieldValue',
                            'action' => [
                                'elementor_id'        => false,
                                'elementor_edit_link' => '',
                                'elementor_exit_theme' => 'theme-one',
                                'is_confirmed'        => false,
                                'themes'              => $this->id . '_theme-one',
                            ],
                        ],
                    ],
                ],

                // Build With Elementor — modal with seed-theme picker.
                'nx-exit-intent_with_elementor' => [
                    'name'     => 'nx-exit-intent_with_elementor',
                    'type'     => 'modal',
                    'priority' => 20,
                    'button'   => [
                        'name'    => 'build_with_elementor',
                        'text'    => __( 'Build With Elementor', 'notificationx' ),
                        'trigger' => [
                            [
                                'type'   => 'setFieldValue',
                                'action' => [ 'import_elementor_theme' => false ],
                            ],
                        ],
                    ],
                    'confirm_button' => [
                        'type'   => 'button',
                        'name'   => 'import_elementor_theme',
                        'group'  => true,
                        'fields' => [
                            [
                                'type'    => 'button',
                                'name'    => 'import_elementor_theme',
                                'default' => false,
                                'text'    => [
                                    'normal'  => __( 'Import', 'notificationx' ),
                                    'saved'   => __( 'Import', 'notificationx' ),
                                    'loading' => __( 'Importing...', 'notificationx' ),
                                ],
                                'ajax'    => [
                                    'on'       => 'click',
                                    'api'      => '/notificationx/v1/exit-intent/elementor/import',
                                    'data'     => [ 'theme_id' => '@elementor_exit_theme' ],
                                    'trigger'  => '@is_confirmed:true',
                                    'hideSwal' => true,
                                ],
                                'rules' => Rules::is( 'is_confirmed', true, true ),
                            ],
                            [
                                'type'    => 'button',
                                'name'    => 'import_elementor_theme_next',
                                'default' => false,
                                'text'    => __( 'Next', 'notificationx' ),
                                'rules'   => Rules::is( 'is_confirmed', true ),
                                'trigger' => [
                                    [
                                        'type'   => 'setContext',
                                        'action' => [ 'config.active' => 'display_tab' ],
                                    ],
                                    [
                                        'type'   => 'setFieldValue',
                                        'action' => [ 'import_elementor_theme_next' => true ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'cancel' => 'import_elementor_theme_next',
                    'body'   => [
                        'header' => __( 'Choose Your ', 'notificationx' ),
                        'fields' => [
                            'themes' => [
                                'type'    => 'radio-card',
                                'name'    => 'elementor_exit_theme',
                                'style'   => [ 'label' => [ 'position' => 'top' ] ],
                                'rules'   => Rules::is( 'is_confirmed', true, true ),
                                'default' => 'theme-one',
                                'options' => $this->elementor_themes,
                            ],
                        ],
                    ],
                    'rules' => Rules::logicalRule( [
                        Rules::is( 'elementor_id', false ),
                        Rules::is( 'is_elementor', true ),
                        Rules::is( 'is_confirmed', true, true ),
                        Rules::is( 'source', $this->id ),
                    ] ),
                ],

                // Install / Activate Elementor — fallback when Elementor isn't active.
                'nx-exit-intent_with_elementor_install' => [
                    'name'     => 'nx-exit-intent_with_elementor_install',
                    'type'     => 'button',
                    'priority' => 25,
                    'text'     => [
                        'normal'  => $install_activate_text,
                        'saved'   => $is_installed ? __( 'Activated Elementor', 'notificationx' ) : __( 'Installed Elementor', 'notificationx' ),
                        'loading' => $is_installed ? __( 'Activating Elementor...', 'notificationx' ) : __( 'Installing Elementor...', 'notificationx' ),
                    ],
                    'rules' => Rules::logicalRule( [
                        Rules::is( 'is_elementor', false ),
                        Rules::is( 'source', $this->id ),
                    ] ),
                    'ajax' => [
                        'on'      => 'click',
                        'api'     => '/notificationx/v1/core-install',
                        'data'    => [
                            'source'       => $this->id,
                            'slug'         => 'elementor',
                            'file'         => 'elementor.php',
                            'is_installed' => $is_installed,
                        ],
                        'swal'    => [
                            'icon' => 'success',
                            'text' => __( 'Successfully Activated', 'notificationx' ),
                        ],
                        'trigger' => '@is_elementor:true',
                    ],
                ],

                // Hidden state fields (Task 05).
                'is_elementor' => [
                    'name'    => 'is_elementor',
                    'type'    => 'hidden',
                    'default' => class_exists( '\\Elementor\\Plugin' ),
                    'rules'   => Rules::is( 'source', $this->id ),
                ],
                'elementor_id' => [
                    'name'    => 'elementor_id',
                    'type'    => 'hidden',
                    'default' => false,
                    'rules'   => Rules::is( 'source', $this->id ),
                ],
                'is_confirmed' => [
                    'name'    => 'is_confirmed',
                    'type'    => 'hidden',
                    'default' => false,
                ],
            ],
        ];

        return $fields;
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
                [ 'label' => __( 'Popup Max Width', 'notificationx' ),          'name' => 'exit_intent_t5_max_width',     'type' => 'number',      'default' => 900, 'description' => 'px' ],
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
        foreach ( [ 'timing', 'behaviour', 'sound_section', 'queue_management', 'appearance', 'animation' ] as $key ) {
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

        // NOTE: keep this key unique. The Announcement extension (PopupNotification)
        // also registers a section literally named `exit_intent_settings` for its
        // "Convert to Exit Intent" feature; sharing the key here let Popup's filter
        // (same `nx_customize_fields` priority, registered later) overwrite this
        // section with a `source == popup_notification` rule, hiding it for the
        // Exit Intent type and leaving the whole Customize tab blank.
        $fields['exit_intent_popup_settings'] = [
            'label'    => __( 'Exit Intent Settings', 'notificationx' ),
            'name'     => 'exit_intent_popup_settings',
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
                'exit_intent_position' => [
                    'label'   => __( 'Position', 'notificationx' ),
                    'name'    => 'exit_intent_position',
                    'type'    => 'select',
                    'default' => 'center',
                    'options' => GlobalFields::get_instance()->normalize_fields( [
                        'center'       => __( 'Center', 'notificationx' ),
                        'bottom-left'  => __( 'Bottom Left', 'notificationx' ),
                        'bottom-right' => __( 'Bottom Right', 'notificationx' ),
                    ] ),
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
        return sprintf(__('
        <p>Show a targeted message at the exact moment someone is about to close your tab & bring them back into the funnel. Need help? Check out our <a target="_blank" href="%1$s">documentation</a>.</p>', 
        'notificationx'),
        'https://notificationx.com/docs/how-to-configure-exit-intent-popup/',
        );
    }
}
