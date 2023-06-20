<?php
/**
 * Functions to register client-side assets (scripts and stylesheets) for the
 * Gutenberg block.
 *
 * @package notificationx
 */

namespace NotificationX\Blocks;

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
        wp_register_script(
            'notificationx-block-controls',
            plugins_url( $index_js, __FILE__ ),
            $asset_file['dependencies'],
            $asset_file['version'],
            false
        );

        $asset_file                   = include NOTIFICATIONX_PATH . 'blocks/notificationx/index.asset.php';
        $asset_file['dependencies'][] = 'notificationx-pro-blocks-edit-post';
        $index_js                     = 'notificationx/index.js';
        wp_register_script(
            'notificationx-block-editor',
            plugins_url( $index_js, __FILE__ ),
            array_merge($asset_file['dependencies'], ['notificationx-block-controls']),
            $asset_file['version']
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
    }

    function notificationx_render_callback( $block_attributes, $content ) {
        if( ! is_admin() ){
            wp_enqueue_style('notificationx-block');
            wp_enqueue_script('notificationx-block-frontend');
        }
        if ( is_admin() || $this->isRestUrl() ) {
            do_action( 'nx_ignore_analytics' );
        }
        $nx_id = ! empty( $block_attributes['nx_id'] ) ? $block_attributes['nx_id'] : '';
        $product_id     = ! empty( $block_attributes['product_id'] ) ? $block_attributes['product_id'] : '';
        $html  = '<div class="' . $block_attributes['blockId'] . ' notificationx-block-wrapper" data-nx_id="' . $nx_id . '">';
        $html .= do_shortcode( "[notificationx_inline product_id='{$product_id}' id='{$nx_id}']" );
        $html .= '</div>';
        return $html;
    }

    function gutenberg_examples_dynamic_render_callback( $block_attributes, $content ) {
        do_action( 'nx_ignore_analytics' );
        $nx_id          = ! empty( $block_attributes['nx_id'] ) ? $block_attributes['nx_id'] : '';
        $product_id     = ! empty( $block_attributes['product_id'] ) ? $block_attributes['product_id'] : '';
        $post_type     = ! empty( $block_attributes['post_type'] ) ? $block_attributes['post_type'] : '';
        $html      = '<div class="' . $block_attributes['blockId'] . ' notificationx-block-wrapper">';
        if( 'wp_template' == $post_type ) {
            add_filter('nx_is_preview',function(){
                return true;
            });
            $product_id = rand();
        }
        $shortcode = do_shortcode( "[notificationx_inline post_type='{$post_type}' product_id='{$product_id}' id='{$nx_id}' show_link=false]" );
        if ( $shortcode ) {
            $html .= $shortcode;
        } else {
            $html .= '<p class="nx-shortcode-notice">' . __( 'There is no data in this notification.', 'notificationx' ) . '</p>';
        }
        $html .= '</div>';

        return $html;
    }

    function isRestUrl() {
        if ( empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
            return false;
        }
        return true;
    }

}
