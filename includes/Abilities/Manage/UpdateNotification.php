<?php
/**
 * Ability: update fields of an existing notification.
 *
 * @package NotificationX\Abilities\Manage
 */

namespace NotificationX\Abilities\Manage;

use NotificationX\Abilities\AbilityBase;
use NotificationX\Core\PostType;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Updates the title and/or selected configuration fields of a notification.
 *
 * The notification is loaded, the requested fields are merged over its stored
 * data, and it is saved back through the normal PostType::save_post() pipeline
 * so all NotificationX filters/hooks still run. The type and source cannot be
 * changed (that would be a different notification).
 */
class UpdateNotification extends AbilityBase {

    protected $id            = 'notificationx/update-notification';
    protected $label         = 'Update a notification';
    protected $description   = 'Update an existing notification: change its title, theme, or other configuration fields. Provide nx_id plus the fields to change. The notification type and data source cannot be changed.';
    protected $is_write      = true;
    protected $is_idempotent = true;

    /**
     * Fields that must never be overwritten via this ability.
     *
     * @var string[]
     */
    protected $protected_keys = array( 'nx_id', 'type', 'source', 'created_at', 'update_status' );

    public function input_schema() {
        return array(
            'type'       => 'object',
            'required'   => array( 'nx_id' ),
            'properties' => array(
                'nx_id'  => array(
                    'type'        => 'integer',
                    'description' => 'The notification id to update.',
                ),
                'title'  => array(
                    'type'        => 'string',
                    'description' => 'New title for the notification.',
                ),
                'themes' => array(
                    'type'        => 'string',
                    'description' => 'New theme id for the notification (must be a theme valid for its source).',
                ),
                'fields' => array(
                    'type'        => 'object',
                    'description' => 'Advanced: a map of additional configuration fields to merge into the notification (e.g. content/design settings). Use get-notification first to see the available fields.',
                ),
            ),
        );
    }

    public function output_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'nx_id'        => array( 'type' => 'integer' ),
                'notification' => array( 'type' => 'object' ),
            ),
        );
    }

    public function execute( $input ) {
        $nx_id     = (int) $input['nx_id'];
        $post_type = PostType::get_instance();
        $existing  = $post_type->get_post( $nx_id );

        if ( empty( $existing ) ) {
            return new \WP_Error(
                'nx_mcp_not_found',
                /* translators: %d: notification id. */
                sprintf( __( 'No notification found with id %d.', 'notificationx' ), $nx_id ),
                array( 'status' => 404 )
            );
        }

        // Start from the stored config so save_post() has everything it needs.
        $data = $existing;

        if ( isset( $input['title'] ) ) {
            $data['title'] = sanitize_text_field( $input['title'] );
        }
        if ( ! empty( $input['themes'] ) ) {
            $data['themes'] = sanitize_text_field( $input['themes'] );
        }
        if ( ! empty( $input['fields'] ) && is_array( $input['fields'] ) ) {
            foreach ( $input['fields'] as $key => $value ) {
                if ( in_array( $key, $this->protected_keys, true ) ) {
                    continue;
                }
                $data[ $key ] = $value;
            }
        }

        // Preserve identity and mark as updated.
        $data['nx_id']      = $nx_id;
        $data['type']       = $existing['type'];
        $data['source']     = $existing['source'];
        $data['updated_at'] = current_time( 'mysql' );
        unset( $data['update_status'] );

        $post_type->save_post( $data );

        return array(
            'nx_id'        => $nx_id,
            'notification' => $post_type->get_post( $nx_id ),
        );
    }
}
