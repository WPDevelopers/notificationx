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
                'post_title'   => sprintf( __( 'Design for NotificationX Exit Intent - %s', 'notificationx' ), $seed['title'] ),
                'post_content' => $seed['content'],
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
