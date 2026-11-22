<?php
/**
 * Exit Intent popups designed with the block editor ("Build With Gutenberg").
 *
 * The design is an `nx_exit_intent_eb` post edited in the block editor, linked
 * to the campaign through `gutenberg_id`. Mirrors the Notification Bar's
 * Gutenberg option (`nx_bar_eb`), without changing the bar's code.
 *
 * @package NotificationX\Extensions
 * @since x.x.x
 */

namespace NotificationX\Extensions\ExitIntent;

use NotificationX\GetInstance;
use NotificationX\Core\Helper;
use NotificationX\Core\Rules;
use WP_Error;

/**
 * Exit Intent block designs.
 *
 * @method static GutenbergDesign get_instance($args = null)
 */
class GutenbergDesign {
    use GetInstance;

    const POST_TYPE = 'nx_exit_intent_eb';
    const SOURCE    = 'exit_intent_custom';

    /**
     * Token in the seed JSONs replaced by the URL of Free's bundled Exit Intent images.
     */
    const IMAGE_TOKEN = '{{NX_IMG}}';

    public function __construct() {
        add_action( 'init', [ $this, 'register_post_type' ] );
        add_filter( 'wp_robots', [ $this, 'no_index' ] );
        add_filter( 'templately_cloud_push_post_type', [ $this, 'templately_label' ] );

        // Builder (Design → Themes → Custom), after ExitIntentNotification builds the tab (priority 100).
        add_filter( 'nx_design_tab_fields', [ $this, 'builder_fields' ], 101 );
        // Same content sections and step Elementor hides, for block designs too.
        add_filter( 'nx_content_fields', [ $this, 'hide_content_sections' ], 1002 );
        add_filter( 'nx_metabox_tabs', [ $this, 'hide_content_tab' ], 11 );
        add_filter( 'nx_save_post', [ $this, 'strip_builder_flags' ], 10, 3 );

        // Front end: after ExitIntentNotification::inject_elementor_html() (priority 10).
        add_filter( 'nx_filtered_post', [ $this, 'inject_gutenberg_html' ], 11, 2 );
        // The builder Preview (and its share links) build the settings without nx_filtered_post.
        add_filter( 'nx_preview_settings_' . self::SOURCE, [ $this, 'inject_gutenberg_preview' ] );
        add_filter( 'body_class', [ $this, 'body_class' ] );
        // After plugins that dequeue the block library (Elementor's "Optimized Gutenberg Loading" runs at 999).
        add_action( 'wp_enqueue_scripts', [ $this, 'keep_core_block_styles' ], 1000 );
        add_filter( 'rocket_rucss_safelist', [ $this, 'rocket_rucss_safelist' ], 11 );

        // Lifecycle: the design belongs to its campaign.
        add_filter( 'nx_save_post', [ $this, 'own_design_on_save' ], 11, 3 );
        add_action( 'nx_saved_post', [ $this, 'sync_title' ], 10, 3 );
        add_filter( 'nx_get_post', [ $this, 'refresh_on_load' ], 10 );
        add_action( 'nx_delete_post', [ $this, 'delete_with_campaign' ], 10, 2 );
    }

    /**
     * Whether an ID is one of our designs (any status).
     *
     * @param mixed $id Post ID.
     * @return bool
     */
    public static function is_design( $id ) {
        $id = absint( $id );
        return $id && self::POST_TYPE === get_post_type( $id );
    }

    /**
     * IDs of the other Exit Intent campaigns that link this design.
     *
     * @param int $gutenberg_id Design ID.
     * @param int $except_nx_id Campaign to leave out.
     * @return int[]
     */
    protected static function campaigns_using( $gutenberg_id, $except_nx_id = 0 ) {
        $ids = [];
        try {
            $posts = \NotificationX\Core\PostType::get_instance()->get_posts( [ 'source' => self::SOURCE ] );
        } catch ( \Exception $e ) {
            return $ids;
        }
        foreach ( (array) $posts as $campaign ) {
            $nx_id = isset( $campaign['nx_id'] ) ? (int) $campaign['nx_id'] : 0;
            if ( $nx_id && $nx_id !== (int) $except_nx_id && ! empty( $campaign['gutenberg_id'] ) && (int) $campaign['gutenberg_id'] === (int) $gutenberg_id ) {
                $ids[] = $nx_id;
            }
        }
        return $ids;
    }

    /**
     * Copy a design (a duplicated campaign gets its own, so Remove or delete on
     * one never breaks the other).
     *
     * @param int $gutenberg_id Design ID.
     * @return int New design ID, or 0.
     */
    public function copy_design( $gutenberg_id ) {
        $source = get_post( absint( $gutenberg_id ) );
        if ( ! $source || self::POST_TYPE !== $source->post_type ) {
            return 0;
        }
        $id = wp_insert_post(
            [
                'post_title'   => $source->post_title,
                'post_content' => wp_slash( $source->post_content ),
                'post_status'  => 'publish',
                'post_type'    => self::POST_TYPE,
            ],
            true
        );
        return is_wp_error( $id ) ? 0 : (int) $id;
    }

    /**
     * On save, a campaign keeps only a design of its own: an ID that isn't one
     * of our designs is dropped, and a design another campaign already uses
     * (a duplicated campaign) is copied.
     *
     * @param array $post  Post being saved.
     * @param array $data  Submitted data.
     * @param int   $nx_id Campaign ID (0 for a new one).
     * @return array
     */
    public function own_design_on_save( $post, $data, $nx_id ) {
        if ( empty( $post['source'] ) || self::SOURCE !== $post['source'] || empty( $post['data']['gutenberg_id'] ) ) {
            return $post;
        }
        $id = $post['data']['gutenberg_id'];
        if ( ! self::is_design( $id ) ) {
            $post['data']['gutenberg_id']        = false;
            $post['data']['gutenberg_edit_link'] = '';
            return $post;
        }
        if ( self::campaigns_using( $id, $nx_id ) ) {
            $copy = $this->copy_design( $id );
            $post['data']['gutenberg_id']        = $copy ? $copy : false;
            $post['data']['gutenberg_edit_link'] = $copy ? (string) get_edit_post_link( $copy, 'raw' ) : '';
        }
        return $post;
    }

    /**
     * Name the design after its campaign, so it is recognisable in Templately
     * and the block editor.
     *
     * @param array $post  Saved post.
     * @param array $data  Saved data.
     * @param int   $nx_id Campaign ID.
     * @return void
     */
    public function sync_title( $post, $data, $nx_id ) {
        if ( empty( $data['source'] ) || self::SOURCE !== $data['source'] || empty( $data['gutenberg_id'] ) || ! self::is_design( $data['gutenberg_id'] ) ) {
            return;
        }
        $title = isset( $post['title'] ) ? trim( wp_strip_all_tags( (string) $post['title'] ) ) : '';
        if ( '' === $title ) {
            return;
        }
        /* translators: %s: campaign title. */
        $wanted = sprintf( __( 'Design for NotificationX Exit Intent - %s', 'notificationx' ), $title );
        if ( (string) get_post_field( 'post_title', (int) $data['gutenberg_id'], 'raw' ) !== $wanted ) {
            wp_update_post( [ 'ID' => (int) $data['gutenberg_id'], 'post_title' => $wanted ] );
        }
    }

    /**
     * When a campaign is opened: refresh the edit link for the current user
     * (empty when they can't edit the design), and drop a design that was
     * deleted, so the Custom tab offers Build again.
     *
     * @param array $post Campaign.
     * @return array
     */
    public function refresh_on_load( $post ) {
        if ( ! is_array( $post ) || empty( $post['source'] ) || self::SOURCE !== $post['source'] || empty( $post['gutenberg_id'] ) ) {
            return $post;
        }
        if ( ! self::is_design( $post['gutenberg_id'] ) || 'trash' === get_post_status( $post['gutenberg_id'] ) ) {
            $post['gutenberg_id']        = false;
            $post['gutenberg_edit_link'] = '';
            return $post;
        }
        $post['gutenberg_edit_link'] = (string) get_edit_post_link( (int) $post['gutenberg_id'], 'raw' );
        return $post;
    }

    /**
     * Deleting a campaign deletes its design (unless another campaign still
     * links it).
     *
     * @param int   $nx_id Campaign ID.
     * @param array $post  The deleted campaign.
     * @return void
     */
    public function delete_with_campaign( $nx_id, $post ) {
        if ( ! is_array( $post ) || empty( $post['source'] ) || self::SOURCE !== $post['source'] || empty( $post['gutenberg_id'] ) ) {
            return;
        }
        if ( self::campaigns_using( $post['gutenberg_id'], $nx_id ) ) {
            return;
        }
        $this->remove( $post['gutenberg_id'] );
    }

    /**
     * The designs of these campaigns, for an export file: title and blocks only.
     *
     * @param array $campaigns Exported campaigns.
     * @return array<int, array{title:string, content:string}>
     */
    public static function export_designs( $campaigns ) {
        $designs = [];
        foreach ( (array) $campaigns as $campaign ) {
            if ( empty( $campaign['source'] ) || self::SOURCE !== $campaign['source'] || empty( $campaign['gutenberg_id'] ) ) {
                continue;
            }
            // The ID comes from the campaign's own data: export only our designs.
            $design = get_post( absint( $campaign['gutenberg_id'] ) );
            if ( ! $design || self::POST_TYPE !== $design->post_type ) {
                continue;
            }
            $designs[ $design->ID ] = [
                'title'   => $design->post_title,
                'content' => $design->post_content,
            ];
        }
        return $designs;
    }

    /**
     * Re-create a design from an export file. Only the title and the blocks
     * are taken from the file; the content goes through the normal save
     * (kses for users without unfiltered_html).
     *
     * @param mixed $design Untrusted `['title' => ..., 'content' => ...]`.
     * @return int New design ID, or 0.
     */
    public function import_design( $design ) {
        if ( ! is_array( $design ) || empty( $design['content'] ) || ! is_string( $design['content'] ) ) {
            return 0;
        }
        $title = isset( $design['title'] ) && is_string( $design['title'] ) ? sanitize_text_field( $design['title'] ) : '';
        $id    = wp_insert_post(
            [
                'post_title'   => $title,
                'post_content' => wp_slash( $design['content'] ),
                'post_status'  => 'publish',
                'post_type'    => self::POST_TYPE,
            ],
            true
        );
        return is_wp_error( $id ) ? 0 : (int) $id;
    }

    /**
     * Design IDs whose block assets this page load put on the page, so the
     * runtime does not have to fetch them (see enqueue_assets()).
     *
     * @var array<int, bool>
     */
    protected $assets_on_page = [];

    /**
     * Payload keys only the builder uses; never sent to visitors.
     */
    const BUILDER_ONLY_KEYS = [ 'gutenberg_edit_link', 'gutenberg_exit_theme', 'import_gutenberg_exit_theme', 'import_gutenberg_exit_theme_next', 'is_gb_confirmed', 'is_gutenberg' ];

    /**
     * Whether a design may be rendered for the current visitor: only our post
     * type, published without a password, unless the viewer can edit it.
     *
     * @param \WP_Post|null $post Design post.
     * @return bool
     */
    public static function can_render( $post ) {
        if ( ! $post instanceof \WP_Post || self::POST_TYPE !== $post->post_type ) {
            return false;
        }
        if ( 'publish' === $post->post_status && '' === $post->post_password ) {
            return true;
        }
        return current_user_can( 'edit_post', $post->ID );
    }

    /**
     * The design post for a campaign's gutenberg_id (WPML translation first),
     * or null when it may not be rendered.
     *
     * @param int|string $gutenberg_id Design ID.
     * @return \WP_Post|null
     */
    public static function design_post( $gutenberg_id ) {
        $gutenberg_id = absint( $gutenberg_id );
        if ( ! $gutenberg_id ) {
            return null;
        }
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML's documented filter.
        $post_id = apply_filters( 'wpml_object_id', $gutenberg_id, self::POST_TYPE, true );
        $post    = $post_id ? get_post( $post_id ) : null;
        return self::can_render( $post ) && '' !== trim( (string) $post->post_content ) ? $post : null;
    }

    /**
     * Render a design: blocks, then embeds, shortcodes and image attributes —
     * the core parts of the_content, without other plugins' content filters
     * (share buttons, related posts) ending up in the popup.
     *
     * @param string $content Block markup.
     * @return string
     */
    public static function render( $content ) {
        $html = do_blocks( $content );
        if ( isset( $GLOBALS['wp_embed'] ) && $GLOBALS['wp_embed'] instanceof \WP_Embed ) {
            $html = $GLOBALS['wp_embed']->run_shortcode( $html );
            $html = $GLOBALS['wp_embed']->autoembed( $html );
        }
        $html = do_shortcode( $html );
        if ( function_exists( 'wp_filter_content_tags' ) ) {
            $html = wp_filter_content_tags( $html, 'nx_exit_intent' );
        }
        return $html;
    }

    /**
     * The layout CSS core generated while rendering the design (container
     * widths, flex gaps, element colours: `.wp-container-…`, `.wp-elements-…`),
     * as a `<style>` to ship with the popup HTML. Block themes print these
     * rules before NotificationX's asset pass runs, and cached pages, the
     * Preview and share links have none, so without it a design falls back to
     * the theme's global content width.
     *
     * @param string $html Rendered design.
     * @return string `<style>…</style>` or ''.
     */
    public static function layout_styles( $html ) {
        if ( ! class_exists( '\WP_Style_Engine_CSS_Rules_Store' ) || ! class_exists( '\WP_Style_Engine' ) || ! method_exists( '\WP_Style_Engine', 'compile_stylesheet_from_css_rules' ) ) {
            return '';
        }
        if ( ! preg_match_all( '/\b(wp-container-[a-z0-9-]+|wp-elements-[a-z0-9-]+)\b/', $html, $m ) ) {
            return '';
        }
        $classes = array_unique( $m[1] );
        $rules   = [];
        foreach ( \WP_Style_Engine_CSS_Rules_Store::get_store( 'block-supports' )->get_all_rules() as $rule ) {
            $selector = $rule->get_selector();
            foreach ( $classes as $class ) {
                if ( preg_match( '/\.' . preg_quote( $class, '/' ) . '(?![a-z0-9-])/', $selector ) ) {
                    $rules[] = $rule;
                    break;
                }
            }
        }
        if ( ! $rules ) {
            return '';
        }
        $css = \WP_Style_Engine::compile_stylesheet_from_css_rules( $rules );
        return '' === trim( (string) $css ) ? '' : '<style class="nx-exit-intent-gutenberg-layout">' . str_replace( '<', '', $css ) . '</style>';
    }

    /**
     * Keep the design's own column widths on tablets.
     *
     * Core stacks Columns blocks below 782px, with `flex-basis: 100% !important`
     * over each column's width. In the popup, tablets (768px and up) keep the
     * desktop layout (_exit-intent.scss), so a column's width (its inline
     * `flex-basis`) is restated for 768–781px; CSS can't fall back to the
     * inline value. Phones (below 768px) stack as before.
     *
     * @since x.x.x
     *
     * @param string $html Rendered design.
     * @return string A <style> element, or ''.
     */
    public static function column_styles( $html ) {
        if ( ! preg_match_all( '/<div[^>]*class="wp-block-column\b[^"]*"[^>]*style="[^"]*flex-basis:\s*([0-9]+(?:\.[0-9]+)?(?:%|px|em|rem|vw))/', $html, $m ) ) {
            return '';
        }
        $css = '';
        foreach ( array_unique( $m[1] ) as $width ) {
            $css .= '.nx-exit-intent-gutenberg-body .wp-block-columns:not(.is-not-stacked-on-mobile)>.wp-block-column[style*="flex-basis:' . $width . '"]{flex-basis:' . $width . '!important}';
        }
        return '<style class="nx-exit-intent-gutenberg-columns">@media (min-width:768px) and (max-width:781px){' . $css . '}</style>';
    }

    /**
     * Width and corner radius of the popup, read from the design's outer Group
     * block ("Content width" and border radius in the block editor).
     *
     * @param string $content Block markup.
     * @return array{width:string, radius:string}
     */
    public static function layout( $content ) {
        $layout = [ 'width' => '600px', 'radius' => '0' ];
        foreach ( parse_blocks( $content ) as $block ) {
            if ( empty( $block['blockName'] ) ) {
                continue;
            }
            $attrs = isset( $block['attrs'] ) ? $block['attrs'] : [];
            $width = isset( $attrs['layout']['contentSize'] ) ? (string) $attrs['layout']['contentSize'] : '';
            if ( preg_match( '/^\d+(\.\d+)?(px|rem|em|vw|%)$/', $width ) ) {
                $layout['width'] = $width;
            }
            $radius = isset( $attrs['style']['border']['radius'] ) ? $attrs['style']['border']['radius'] : '';
            if ( is_string( $radius ) && preg_match( '/^\d+(\.\d+)?(px|rem|em|%)$/', $radius ) ) {
                $layout['radius'] = $radius;
            }
            break;
        }
        return $layout;
    }

    /**
     * Put a linked block design into the `notice` payload: `mode` becomes
     * `gutenberg` with the rendered `gutenberg_html`, unless an Elementor design
     * applies (Elementor wins, as for bars). Builder-only keys are removed.
     *
     * @param array $settings Notification settings.
     * @param array $params   Request params.
     * @return array
     */
    public function inject_gutenberg_html( $settings, $params ) {
        if ( empty( $settings['source'] ) || self::SOURCE !== $settings['source'] ) {
            return $settings;
        }
        foreach ( self::BUILDER_ONLY_KEYS as $key ) {
            unset( $settings[ $key ] );
        }
        if ( empty( $settings['gutenberg_id'] ) || ( isset( $settings['mode'] ) && 'elementor' === $settings['mode'] ) ) {
            return $settings;
        }
        $post = self::design_post( $settings['gutenberg_id'] );
        if ( ! $post ) {
            return $settings;
        }
        $html                         = self::render( $post->post_content );
        $settings['mode']             = 'gutenberg';
        $settings['gutenberg_html']   = self::layout_styles( $html ) . self::column_styles( $html ) . $html;
        $settings['gutenberg_layout'] = self::layout( $post->post_content );
        $settings['gutenberg_url']    = get_permalink( $post );
        return $settings;
    }

    /**
     * The builder Preview: show the linked block design, as on the live site.
     *
     * @param array $settings Preview settings (the builder's current values).
     * @return array
     */
    public function inject_gutenberg_preview( $settings ) {
        return $this->inject_gutenberg_html( is_array( $settings ) ? $settings : [], [] );
    }

    /**
     * Put a design's block assets on the current page while the page's assets
     * are collected (wp_enqueue_scripts): each block enqueues its own CSS and
     * JS, as in post content. The popup HTML arrives later over REST, where
     * those enqueues would be lost. The output is discarded. Same approach as
     * FrontEnd::enqueue_gutenberg_bar_assets() for bars (which is unchanged).
     *
     * @param int|string $gutenberg_id Design ID.
     * @return void
     */
    public function enqueue_assets( $gutenberg_id ) {
        $gutenberg_id = absint( $gutenberg_id );
        if ( ! $gutenberg_id || isset( $this->assets_on_page[ $gutenberg_id ] ) || ! doing_action( 'wp_enqueue_scripts' ) ) {
            return;
        }
        $post = self::design_post( $gutenberg_id );
        if ( ! $post ) {
            return;
        }
        // This runs inside <head>: a block that echoes would push later head tags into <body>.
        ob_start();
        try {
            self::render( $post->post_content );
        } finally {
            ob_end_clean();
        }
        $this->assets_on_page[ $gutenberg_id ] = ! self::needs_essential_blocks_styles( $post );
        $this->blocks_on_page = array_values( array_unique( array_merge( $this->blocks_on_page, self::block_names( parse_blocks( $post->post_content ) ) ) ) );
    }

    /**
     * Core block names used by the designs whose assets this page load added.
     *
     * @var string[]
     */
    protected $blocks_on_page = [];

    /**
     * Keep the core block styles a design needs when the page has no block
     * library: Elementor's "Optimized Gutenberg Loading" dequeues
     * `wp-block-library` on Elementor-built pages, and classic themes don't
     * load core block styles one by one. Only then, enqueue the stylesheets
     * of the core blocks the designs use (plus core's shared block CSS), so
     * the popup isn't left without them. Does nothing when the block library
     * or a block's own style is already on the page.
     *
     * @return void
     */
    public function keep_core_block_styles() {
        if ( empty( $this->blocks_on_page ) || wp_style_is( 'wp-block-library', 'enqueued' ) ) {
            return;
        }
        $version = get_bloginfo( 'version' );
        $files   = [];
        if ( file_exists( ABSPATH . WPINC . '/css/dist/block-library/common.min.css' ) ) {
            $files['common'] = 'css/dist/block-library/common.min.css';
        }
        foreach ( $this->blocks_on_page as $name ) {
            if ( 0 !== strpos( $name, 'core/' ) ) {
                continue;
            }
            $slug = substr( $name, 5 );
            if ( wp_style_is( 'wp-block-' . $slug, 'enqueued' ) || ! preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
                continue;
            }
            if ( file_exists( ABSPATH . WPINC . "/blocks/{$slug}/style.min.css" ) ) {
                $files[ $slug ] = "blocks/{$slug}/style.min.css";
            }
        }
        foreach ( $files as $slug => $file ) {
            $handle = 'nx-exit-intent-block-' . $slug;
            wp_enqueue_style( $handle, includes_url( $file ), [], $version );
            wp_style_add_data( $handle, 'rtl', 'replace' );
            wp_style_add_data( $handle, 'suffix', '.min' );
        }
    }

    /**
     * Essential Blocks writes a post's CSS files only after the post's own page
     * is viewed or saved; until then the runtime fetches them (as for bars).
     *
     * @param \WP_Post $post Design post.
     * @return bool
     */
    protected static function needs_essential_blocks_styles( $post ) {
        if ( false === strpos( (string) $post->post_content, '<!-- wp:essential-blocks/' ) ) {
            return false;
        }
        $upload = wp_upload_dir();
        $base   = $upload['basedir'] . '/eb-style/';
        return ! file_exists( $base . 'eb-style-' . $post->ID . '.min.css' ) || ! file_exists( $base . 'frontend/frontend-' . $post->ID . '.min.css' );
    }

    /**
     * Tell the runtime which designs' assets are already on the page.
     *
     * @param string[] $classes Body classes.
     * @return string[]
     */
    public function body_class( $classes ) {
        foreach ( array_keys( array_filter( $this->assets_on_page ) ) as $id ) {
            $classes[] = 'nx-block-assets-' . $id;
        }
        return $classes;
    }

    /**
     * WP Rocket's Remove Unused CSS never sees the popup HTML (it arrives over
     * REST), so keep the stylesheets of the blocks used by active designs.
     *
     * @param array $list Safelisted CSS files and selectors.
     * @return array
     */
    public function rocket_rucss_safelist( $list ) {
        try {
            $posts = \NotificationX\Core\PostType::get_instance()->get_posts( [ 'source' => self::SOURCE ] );
        } catch ( \Exception $e ) {
            return $list;
        }
        foreach ( (array) $posts as $campaign ) {
            if ( empty( $campaign['enabled'] ) || empty( $campaign['gutenberg_id'] ) ) {
                continue;
            }
            $post = self::design_post( $campaign['gutenberg_id'] );
            if ( ! $post ) {
                continue;
            }
            foreach ( self::block_names( parse_blocks( $post->post_content ) ) as $name ) {
                if ( 0 === strpos( $name, 'core/' ) ) {
                    $list[] = includes_url( 'blocks/' . substr( $name, 5 ) . '/style.min.css' );
                } elseif ( 'notificationx/countdown' === $name ) {
                    $list[] = NOTIFICATIONX_PUBLIC_URL . 'css/nx-countdown.css';
                }
            }
            $list[] = '.nx-exit-intent-gutenberg';
        }
        return array_values( array_unique( $list ) );
    }

    /**
     * @param array $blocks Parsed blocks.
     * @return string[] Block names, inner blocks included.
     */
    protected static function block_names( $blocks ) {
        $names = [];
        foreach ( $blocks as $block ) {
            if ( ! empty( $block['blockName'] ) ) {
                $names[] = $block['blockName'];
            }
            if ( ! empty( $block['innerBlocks'] ) ) {
                $names = array_merge( $names, self::block_names( $block['innerBlocks'] ) );
            }
        }
        return array_values( array_unique( $names ) );
    }

    /**
     * Whether the block editor can edit block designs on this site (off with
     * the Classic Editor plugin, or when a filter turns it off).
     *
     * @return bool
     */
    public static function block_editor_available() {
        return function_exists( 'use_block_editor_for_post_type' ) && post_type_exists( self::POST_TYPE )
            ? (bool) use_block_editor_for_post_type( self::POST_TYPE )
            : false;
    }

    /**
     * Seed options for the modal's radio-card.
     *
     * @return array
     */
    protected function seed_options() {
        $options = [];
        foreach ( self::seeds() as $key => $seed ) {
            $options[ $key ] = [
                'label'  => $seed['title'],
                'value'  => $key,
                'icon'   => $seed['icon'],
                'column' => '6',
                'title'  => $seed['title'],
            ];
        }
        return $options;
    }

    /**
     * Add "Build With Gutenberg", "Edit With Gutenberg" and "Remove" to the
     * Exit Intent Custom tab, next to the Elementor buttons. A popup uses one
     * builder at a time: each Build button hides while the other builder's
     * design is linked.
     *
     * @param array $fields Design tab fields.
     * @return array
     */
    public function builder_fields( $fields ) {
        if ( empty( $fields['themes']['fields']['themes_section']['fields']['themes_tab']['fields'] ) ) {
            return $fields;
        }
        $tabs =& $fields['themes']['fields']['themes_section']['fields']['themes_tab']['fields'];
        foreach ( $tabs as $index => $tab ) {
            if ( ! is_array( $tab ) || empty( $tab['name'] ) ) {
                continue;
            }
            if ( 'exit_intent_custom_tab' === $tab['name'] && isset( $tab['fields'] ) ) {
                $tabs[ $index ]['fields'] = $this->custom_tab_fields( $tab['fields'] );
            } elseif ( 'exit_intent_ai_tab' === $tab['name'] && isset( $tab['fields']['exit_intent_ai_tab']['fields'] ) ) {
                $tabs[ $index ]['fields']['exit_intent_ai_tab']['fields'] = $this->ai_tab_fields( $tab['fields']['exit_intent_ai_tab']['fields'] );
            }
        }
        return $fields;
    }

    /**
     * @param array $custom Custom tab fields (Elementor's).
     * @return array
     */
    protected function custom_tab_fields( $custom ) {
        // Build With Elementor (and its install button) stays hidden while a block design is linked.
        foreach ( [ 'nx-exit-intent_with_elementor', 'nx-exit-intent_with_elementor_install' ] as $key ) {
            if ( isset( $custom[ $key ] ) ) {
                $custom[ $key ] = Rules::is( 'gutenberg_id', false, false, $custom[ $key ] );
            }
        }

        $custom['gutenberg_edit_link'] = [
            'name'     => 'gutenberg_edit_link',
            'type'     => 'button',
            'text'     => __( 'Edit With Gutenberg', 'notificationx' ),
            'href'     => -1,
            'target'   => '_blank',
            'priority' => 11,
            'rules'    => Rules::logicalRule( [
                Rules::is( 'gutenberg_edit_link', false, true ),
                Rules::is( 'gutenberg_edit_link', '', true ),
                Rules::isOfType( 'gutenberg_edit_link', 'string' ),
                Rules::is( 'gutenberg_id', false, true ),
                Rules::is( 'source', self::SOURCE ),
            ] ),
        ];
        $custom['nx-exit-intent_gutenberg_no_edit'] = [
            'name'     => 'nx-exit-intent_gutenberg_no_edit',
            'type'     => 'message',
            'class'    => 'nx-warning',
            'html'     => false,
            'priority' => 12,
            'message'  => __( 'This popup uses a block design. Ask a site editor to open it in the block editor.', 'notificationx' ),
            'rules'    => Rules::logicalRule( [
                Rules::is( 'gutenberg_id', false, true ),
                Rules::is( 'gutenberg_edit_link', '' ),
                Rules::is( 'source', self::SOURCE ),
            ] ),
        ];
        $custom['nx-exit-intent_with_gutenberg-remove'] = [
            'name'     => 'nx-exit-intent_with_gutenberg-remove',
            'type'     => 'button',
            'text'     => __( 'Remove', 'notificationx' ),
            'priority' => 16,
            'rules'    => Rules::logicalRule( [
                Rules::is( 'gutenberg_id', false, true ),
                Rules::is( 'source', self::SOURCE ),
            ] ),
            'ajax'     => [
                'on'       => 'click',
                'api'      => '/notificationx/v1/exit-intent/gutenberg/remove',
                'data'     => [ 'gutenberg_id' => '@gutenberg_id' ],
                'hideSwal' => true,
            ],
            'trigger'  => [
                [
                    'type'   => 'setFieldValue',
                    'action' => [
                        'gutenberg_id'         => false,
                        'gutenberg_edit_link'  => '',
                        'gutenberg_exit_theme' => 'theme-one',
                        'is_gb_confirmed'      => false,
                        'themes'               => self::SOURCE . '_theme-one',
                    ],
                ],
            ],
        ];
        $custom['nx-exit-intent_with_gutenberg'] = [
            'name'           => 'nx-exit-intent_with_gutenberg',
            'type'           => 'modal',
            'priority'       => 21,
            'button'         => [
                'name'    => 'build_with_gutenberg',
                'text'    => __( 'Build With Gutenberg', 'notificationx' ),
                'trigger' => [
                    [
                        'type'   => 'setFieldValue',
                        'action' => [ 'import_gutenberg_exit_theme' => false ],
                    ],
                ],
            ],
            'confirm_button' => [
                'type'   => 'button',
                'name'   => 'import_gutenberg_exit_theme',
                'group'  => true,
                'fields' => [
                    [
                        'type'    => 'button',
                        'name'    => 'import_gutenberg_exit_theme',
                        'default' => false,
                        'text'    => [
                            'normal'  => __( 'Import', 'notificationx' ),
                            'saved'   => __( 'Import', 'notificationx' ),
                            'loading' => __( 'Importing...', 'notificationx' ),
                        ],
                        // No hideSwal: a failed import shows its message.
                        'ajax'    => [
                            'on'      => 'click',
                            'api'     => '/notificationx/v1/exit-intent/gutenberg/import',
                            'data'    => [ 'theme_id' => '@gutenberg_exit_theme' ],
                            'trigger' => '@is_gb_confirmed:true',
                            'swal'    => [
                                'icon' => 'success',
                                'text' => __( 'Design imported', 'notificationx' ),
                            ],
                        ],
                        'rules'   => Rules::is( 'is_gb_confirmed', true, true ),
                    ],
                    [
                        'type'    => 'button',
                        'name'    => 'import_gutenberg_exit_theme_next',
                        'default' => false,
                        'text'    => __( 'Next', 'notificationx' ),
                        'rules'   => Rules::is( 'is_gb_confirmed', true ),
                        'trigger' => [
                            [
                                'type'   => 'setContext',
                                'action' => [ 'config.active' => 'display_tab' ],
                            ],
                            [
                                'type'   => 'setFieldValue',
                                'action' => [ 'import_gutenberg_exit_theme_next' => true ],
                            ],
                        ],
                    ],
                ],
            ],
            'cancel'         => 'import_gutenberg_exit_theme_next',
            'body'           => [
                'header' => __( 'Choose your design', 'notificationx' ),
                'fields' => [
                    'themes' => [
                        'type'    => 'radio-card',
                        'name'    => 'gutenberg_exit_theme',
                        'style'   => [ 'label' => [ 'position' => 'top' ] ],
                        'rules'   => Rules::is( 'is_gb_confirmed', true, true ),
                        'default' => 'theme-one',
                        'options' => $this->seed_options(),
                    ],
                ],
            ],
            'rules'          => Rules::logicalRule( [
                Rules::is( 'elementor_id', false ),
                Rules::is( 'gutenberg_id', false ),
                Rules::is( 'is_gutenberg', true ),
                Rules::is( 'is_gb_confirmed', true, true ),
                Rules::is( 'source', self::SOURCE ),
            ] ),
        ];

        // Hidden state. Same names as the Notification Bar's, scoped to Exit Intent.
        $custom['is_gutenberg']    = [
            'name'    => 'is_gutenberg',
            'type'    => 'hidden',
            'default' => self::block_editor_available(),
            'rules'   => Rules::is( 'source', self::SOURCE ),
        ];
        $custom['gutenberg_id']    = [
            'name'    => 'gutenberg_id',
            'type'    => 'hidden',
            'default' => false,
            'rules'   => Rules::is( 'source', self::SOURCE ),
        ];
        $custom['is_gb_confirmed'] = [
            'name'    => 'is_gb_confirmed',
            'type'    => 'hidden',
            'default' => false,
            'rules'   => Rules::is( 'source', self::SOURCE ),
        ];
        return $custom;
    }

    /**
     * While a block design is linked, the Build With AI tab says to remove it
     * first: an AI design would otherwise be hidden behind the block design.
     *
     * @param array $ai AI tab fields.
     * @return array
     */
    protected function ai_tab_fields( $ai ) {
        foreach ( $ai as $key => $field ) {
            $ai[ $key ] = Rules::isOfType( 'gutenberg_id', 'number', true, $field );
        }
        $ai['exit_intent_ai_gutenberg_note'] = [
            'name'     => 'exit_intent_ai_gutenberg_note',
            'type'     => 'message',
            'class'    => 'nx-warning',
            'html'     => false,
            'priority' => 5,
            'message'  => __( 'This popup uses a block design. Remove it on the Custom tab to start from Build With AI.', 'notificationx' ),
            'rules'    => Rules::isOfType( 'gutenberg_id', 'number' ),
        ];
        return $ai;
    }

    /**
     * Hide every Exit Intent content section while a block design is linked
     * (the block design owns that content), as Elementor designs do.
     *
     * @param array $fields Content fields.
     * @return array
     */
    public function hide_content_sections( $fields ) {
        foreach ( $fields as $key => $field ) {
            if ( 0 === strpos( $key, 'exit_intent_' ) && '_section' === substr( $key, -8 ) ) {
                $fields[ $key ] = Rules::isOfType( 'gutenberg_id', 'number', true, $field );
            }
        }
        return $fields;
    }

    /**
     * Hide the then-empty Content step while a block design is linked.
     *
     * @param array $tabs Builder steps.
     * @return array
     */
    public function hide_content_tab( $tabs ) {
        if ( empty( $tabs['content_tab'] ) ) {
            return $tabs;
        }
        $tabs['content_tab'] = Rules::add(
            Rules::_logicalRule( [
                Rules::_is( 'source', self::SOURCE, true ),
                Rules::_isOfType( 'gutenberg_id', 'number', true ),
            ], 'or' ),
            $tabs['content_tab']
        );
        return $tabs;
    }

    /**
     * Builder-only flags are not saved with an Exit Intent campaign.
     *
     * @param array $post  Post being saved.
     * @param array $data  Submitted data.
     * @param int   $nx_id Notification ID.
     * @return array
     */
    public function strip_builder_flags( $post, $data, $nx_id ) {
        if ( isset( $post['source'] ) && self::SOURCE === $post['source'] && isset( $post['data'] ) && is_array( $post['data'] ) ) {
            unset( $post['data']['is_gutenberg'], $post['data']['is_gb_confirmed'] );
        }
        return $post;
    }

    /**
     * Name block designs in Templately's "Save … In Templately" button.
     *
     * @param string $post_type Post type or label.
     * @return string
     */
    public function templately_label( $post_type ) {
        return self::POST_TYPE === $post_type ? 'NX Exit Intent' : $post_type;
    }

    /**
     * The starting designs offered in the "Choose your design" modal. Each key
     * is a file in jsons-gb/; only these keys can be imported.
     *
     * @return array<string, array{title:string, icon:string}>
     */
    public static function seeds() {
        $images = NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/exit-intent-gutenberg/';
        return [
            'theme-one'   => [ 'title' => __( 'Coupon code', 'notificationx' ),                'icon' => $images . 'theme-one.png' ],
            'theme-two'   => [ 'title' => __( 'Flash sale with countdown', 'notificationx' ),  'icon' => $images . 'theme-two.png' ],
            'theme-three' => [ 'title' => __( 'Image and text', 'notificationx' ),             'icon' => $images . 'theme-three.png' ],
            'theme-four'  => [ 'title' => __( 'Free shipping announcement', 'notificationx' ), 'icon' => $images . 'theme-four.png' ],
        ];
    }

    /**
     * Register the post type that holds block designs.
     *
     * Same set-up as `nx_bar_eb`: edited in the block editor, not listed in the
     * admin menu, viewable at its permalink (the runtime fetches a design's
     * block assets from there when a cached page lacks them), never indexed.
     *
     * @return void
     */
    public function register_post_type() {
        register_post_type(
            self::POST_TYPE,
            [
                'label'                 => __( 'NotificationX Exit Intent (Gutenberg)', 'notificationx' ),
                'show_in_rest'          => true,
                'public'                => false,
                'publicly_queryable'    => true,
                'exclude_from_search'   => true,
                'show_ui'               => true,
                'can_export'            => true,
                'show_in_menu'          => false,
                'show_in_nav_menus'     => false,
                'rewrite'               => false,
                'rest_base'             => 'nx-exit-intent-designs',
                'capability_type'       => 'block',
                'rest_controller_class' => 'WP_REST_Blocks_Controller',
                'capabilities'          => [
                    // Same map as nx_bar_eb (core's wp_block map).
                    'read'                   => 'edit_posts',
                    'create_posts'           => 'publish_posts',
                    'edit_posts'             => 'edit_posts',
                    'edit_published_posts'   => 'edit_published_posts',
                    'delete_published_posts' => 'delete_published_posts',
                    'delete_posts'           => 'delete_posts',
                    'edit_others_posts'      => 'edit_others_posts',
                    'delete_others_posts'    => 'delete_others_posts',
                ],
                'map_meta_cap'          => true,
                'supports'              => [ 'title', 'editor', 'revisions', 'custom-fields' ],
            ]
        );
    }

    /**
     * Keep a design's permalink out of search engines.
     *
     * @param array $robots wp_robots directives.
     * @return array
     */
    public function no_index( $robots ) {
        if ( is_singular( self::POST_TYPE ) ) {
            $robots['noindex']  = true;
            $robots['nofollow'] = true;
        }
        return $robots;
    }

    /**
     * Read a starting design. Only keys from seeds() are accepted, so no path is
     * ever built from request input.
     *
     * @param string $theme_id Seed key.
     * @return array{title:string, content:string}|WP_Error
     */
    public static function read_seed( $theme_id ) {
        $theme_id = is_string( $theme_id ) ? $theme_id : '';
        $seeds    = self::seeds();
        if ( ! isset( $seeds[ $theme_id ] ) ) {
            return new WP_Error( 'nx_exit_intent_unknown_design', __( 'That design does not exist.', 'notificationx' ), [ 'status' => 400 ] );
        }
        $file = __DIR__ . '/jsons-gb/' . $theme_id . '.json';
        $json = is_readable( $file ) ? file_get_contents( $file ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local, bundled file.
        $data = $json ? json_decode( $json, true ) : null;
        if ( ! is_array( $data ) || empty( $data['content'] ) || ! is_string( $data['content'] ) ) {
            return new WP_Error( 'nx_exit_intent_bad_design', __( 'The design could not be read.', 'notificationx' ), [ 'status' => 500 ] );
        }
        $images = untrailingslashit( NOTIFICATIONX_PUBLIC_URL . 'image/exit-intent' );
        return [
            'title'   => $seeds[ $theme_id ]['title'],
            'content' => str_replace( self::IMAGE_TOKEN, $images, $data['content'] ),
        ];
    }

    /**
     * Create a block design from a starting design.
     *
     * @param string $theme_id Seed key.
     * @return array{context: array}|WP_Error The builder's context on success.
     */
    public function import( $theme_id ) {
        $seed = self::read_seed( $theme_id );
        if ( is_wp_error( $seed ) ) {
            return $seed;
        }
        $post_id = wp_insert_post(
            [
                /* translators: %s: name of the starting design. */
                'post_title'   => wp_slash( sprintf( __( 'Design for NotificationX Exit Intent - %s', 'notificationx' ), $seed['title'] ) ),
                // wp_insert_post() unslashes: keep the block attributes' JSON escapes (\u003e …).
                'post_content' => wp_slash( $seed['content'] ),
                'post_status'  => 'publish',
                'post_type'    => self::POST_TYPE,
            ],
            true
        );
        if ( is_wp_error( $post_id ) || ! $post_id ) {
            return new WP_Error( 'nx_exit_intent_import_failed', __( 'The design could not be created.', 'notificationx' ), [ 'status' => 500 ] );
        }
        return [
            'context' => [
                'themes'              => null,
                'gutenberg_id'        => $post_id,
                'gutenberg_edit_link' => (string) get_edit_post_link( $post_id, 'raw' ),
            ],
        ];
    }

    /**
     * Delete a block design and its WPML translations. Only `nx_exit_intent_eb`
     * posts are ever deleted.
     *
     * @param int|string $post_id Design ID.
     * @return bool Whether the design was deleted.
     */
    public function remove( $post_id ) {
        $post_id = absint( $post_id );
        if ( ! $post_id ) {
            return false;
        }
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML's documented filter.
        $languages = apply_filters( 'wpml_active_languages', null );
        if ( is_array( $languages ) ) {
            $deleted = false;
            foreach ( array_keys( $languages ) as $lang ) {
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML's documented filter.
                $translated = apply_filters( 'wpml_object_id', $post_id, self::POST_TYPE, false, $lang );
                if ( $translated && Helper::delete_owned_post( $translated, self::POST_TYPE ) ) {
                    $deleted = true;
                }
            }
            return $deleted;
        }
        return Helper::delete_owned_post( $post_id, self::POST_TYPE );
    }
}
