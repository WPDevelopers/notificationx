<?php
/**
 * Ability: list NotificationX notifications.
 *
 * @package NotificationX\Abilities\Read
 */

namespace NotificationX\Abilities\Read;

use NotificationX\Abilities\AbilityBase;
use NotificationX\Core\PostType;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns a compact list of the site's notifications with their status.
 */
class ListNotifications extends AbilityBase {

    protected $id          = 'notificationx/list-notifications';
    protected $label       = 'List notifications';
    protected $description = 'List NotificationX campaigns on this site with their type, data source, theme and active status. Supports filtering by source, type and enabled state.';

    public function input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'enabled' => array(
                    'type'        => 'boolean',
                    'description' => 'Only return notifications that are active (true) or inactive (false). Omit for all.',
                ),
                'source'  => array(
                    'type'        => 'string',
                    'description' => 'Filter by data source id (e.g. "woocommerce", "comments"). Omit for all.',
                ),
                'type'    => array(
                    'type'        => 'string',
                    'description' => 'Filter by notification type id (e.g. "conversions", "reviews"). Omit for all.',
                ),
                'limit'   => array(
                    'type'        => 'integer',
                    'description' => 'Maximum number of notifications to return (default 50, max 200).',
                ),
            ),
        );
    }

    public function output_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'count'         => array( 'type' => 'integer' ),
                'notifications' => array( 'type' => 'array' ),
            ),
        );
    }

    public function execute( $input ) {
        $wheres = array();
        if ( array_key_exists( 'enabled', $input ) ) {
            $wheres['enabled'] = (bool) $input['enabled'];
        }
        if ( ! empty( $input['source'] ) ) {
            $wheres['source'] = $input['source'];
        }
        if ( ! empty( $input['type'] ) ) {
            $wheres['type'] = $input['type'];
        }

        $limit = isset( $input['limit'] ) ? min( 200, max( 1, (int) $input['limit'] ) ) : 50;

        $posts = PostType::get_instance()->get_posts( $wheres );
        if ( ! is_array( $posts ) ) {
            $posts = array();
        }

        $posts = array_slice( $posts, 0, $limit );

        $notifications = array();
        foreach ( $posts as $post ) {
            $notifications[] = array(
                'nx_id'        => isset( $post['nx_id'] ) ? (int) $post['nx_id'] : 0,
                'title'        => isset( $post['title'] ) ? $post['title'] : '',
                'type'         => isset( $post['type'] ) ? $post['type'] : '',
                'type_label'   => isset( $post['type_label'] ) ? $post['type_label'] : '',
                'source'       => isset( $post['source'] ) ? $post['source'] : '',
                'source_label' => isset( $post['source_label'] ) ? $post['source_label'] : '',
                'theme'        => isset( $post['themes'] ) ? $post['themes'] : ( isset( $post['theme'] ) ? $post['theme'] : '' ),
                'enabled'      => ! empty( $post['enabled'] ),
                'created_at'   => isset( $post['created_at'] ) ? $post['created_at'] : '',
                'updated_at'   => isset( $post['updated_at'] ) ? $post['updated_at'] : '',
            );
        }

        return array(
            'count'         => count( $notifications ),
            'notifications' => $notifications,
        );
    }
}
