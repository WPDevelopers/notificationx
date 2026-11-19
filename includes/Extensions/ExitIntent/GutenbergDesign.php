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
