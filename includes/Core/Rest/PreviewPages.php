<?php
/**
 * Page search for the builder Preview's page picker.
 *
 * @package NotificationX\Core\Rest
 */

namespace NotificationX\Core\Rest;

use NotificationX\GetInstance;
use WP_Post;
use WP_Query;
use WP_REST_Request;
use WP_REST_Server;

/**
 * `GET notificationx/v1/preview/pages`: the pages, posts and products the
 * builder Preview can be shown on.
 *
 * Only for users who can edit notifications. Drafts, scheduled, pending and
 * private posts are listed only when the user can read each one, and they
 * point at their preview link. The preview request itself stays the
 * builder's nonce-checked POST (Preview::is_authorized_preview_request()).
 *
 * @since 3.3.9
 * @method static PreviewPages get_instance($args = null)
 */
class PreviewPages {
    use GetInstance;

    /**
     * Results per post type.
     */
    const PER_TYPE = 8;

    /**
     * Statuses offered. Each non-public one is checked with `read_post`.
     */
    const STATUSES = array( 'publish', 'private', 'draft', 'pending', 'future' );

    public $namespace = 'notificationx/v1';
    public $rest_base = 'preview/pages';

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_pages' ),
                'permission_callback' => array( $this, 'can_search' ),
                'args'                => array(
                    'search' => array(
                        'description'       => __( 'Search words.', 'notificationx' ),
                        'type'              => 'string',
                        'default'           => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'url'    => array(
                        'description' => __( 'A page URL to name: the Preview\'s default page.', 'notificationx' ),
                        'type'        => 'string',
                        'default'     => '',
                    ),
                ),
            )
        );
    }

    public function can_search() {
        return current_user_can( 'edit_notificationx' );
    }

    /**
     * Post types offered: public, searchable, and not attachments.
     *
     * @return string[]
     */
    public function post_types() {
        $types = get_post_types(
            array(
                'public'              => true,
                'exclude_from_search' => false,
            )
        );
        unset( $types['attachment'] );
        $types = apply_filters( 'nx_preview_page_post_types', array_values( $types ) );
        return array_values( array_filter( (array) $types, 'post_type_exists' ) );
    }

    /**
     * @param WP_REST_Request $request Request.
     * @return array
     */
    public function get_pages( $request ) {
        $search = trim( (string) $request->get_param( 'search' ) );
        $items  = array();

        if ( '' === $search ) {
            $front = (int) get_option( 'page_on_front' );
            if ( 'page' === get_option( 'show_on_front' ) && $front ) {
                $post = get_post( $front );
                if ( $post && $this->can_read( $post ) ) {
                    $items[] = $this->item( $post );
                }
            }
        }

        foreach ( $this->post_types() as $type ) {
            $args = array(
                'post_type'           => $type,
                'post_status'         => self::STATUSES,
                'posts_per_page'      => self::PER_TYPE,
                'orderby'             => '' === $search ? 'modified' : 'relevance',
                'order'               => 'DESC',
                'no_found_rows'       => true,
                'ignore_sticky_posts' => true,
                'perm'                => 'readable',
            );
            if ( '' !== $search ) {
                $args['s'] = $search;
            }
            $query = new WP_Query( $args );
            foreach ( $query->posts as $post ) {
                if ( ! $this->can_read( $post ) ) {
                    continue;
                }
                $item = $this->item( $post );
                if ( ! in_array( $item['id'], wp_list_pluck( $items, 'id' ), true ) ) {
                    $items[] = $item;
                }
            }
        }

        return array(
            'items'   => $items,
            'current' => $this->name_url( (string) $request->get_param( 'url' ) ),
        );
    }

    /**
     * @param WP_Post $post Post.
     * @return bool
     */
    protected function can_read( $post ) {
        return in_array( $post->post_status, self::STATUSES, true ) && current_user_can( 'read_post', $post->ID );
    }

    /**
     * One picker entry.
     *
     * @param WP_Post $post Post.
     * @return array
     */
    public function item( $post ) {
        $type     = get_post_type_object( $post->post_type );
        $status   = $post->post_status;
        $password = '' !== (string) $post->post_password;
        $viewable = in_array( $status, array( 'publish', 'private' ), true );

        return array(
            'id'         => (int) $post->ID,
            'title'      => $this->title( $post ),
            'type'       => $post->post_type,
            'type_label' => $type ? $type->labels->name : $post->post_type,
            'status'     => $status,
            'password'   => $password,
            // Anyone can open it: published, no password, a viewable type.
            'public'     => 'publish' === $status && ! $password && is_post_type_viewable( $post->post_type ),
            'front'      => (int) get_option( 'page_on_front' ) === (int) $post->ID && 'page' === get_option( 'show_on_front' ),
            'url'        => $viewable ? get_permalink( $post ) : get_preview_post_link( $post ),
        );
    }

    /**
     * @param WP_Post $post Post.
     * @return string
     */
    protected function title( $post ) {
        // The raw title: get_the_title() adds "Private:" / "Protected:", and
        // the picker already shows those as badges. Tags are stripped; the
        // admin renders it as text, so React escapes it.
        $title = html_entity_decode( wp_strip_all_tags( (string) $post->post_title ), ENT_QUOTES, get_bloginfo( 'charset' ) );
        return '' !== trim( $title ) ? $title : __( '(no title)', 'notificationx' );
    }

    /**
     * Names the page behind a URL on this site, for the picker button.
     *
     * @param string $url URL.
     * @return array|null `title` and `id` (0 for the posts home page), or null.
     */
    public function name_url( $url ) {
        $url = esc_url_raw( $url );
        if ( '' === $url ) {
            return null;
        }
        if ( untrailingslashit( $url ) === untrailingslashit( home_url() ) ) {
            $front = (int) get_option( 'page_on_front' );
            if ( 'page' === get_option( 'show_on_front' ) && $front && current_user_can( 'read_post', $front ) ) {
                return array( 'id' => $front, 'title' => $this->title( get_post( $front ) ) );
            }
            return array( 'id' => 0, 'title' => __( 'Home', 'notificationx' ) );
        }
        $id   = url_to_postid( $url );
        $post = $id ? get_post( $id ) : null;
        if ( ! $post || ! $this->can_read( $post ) ) {
            return null;
        }
        return array( 'id' => (int) $post->ID, 'title' => $this->title( $post ) );
    }
}
