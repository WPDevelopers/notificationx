<?php
/**
 * Ability: duplicate an existing notification.
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
 * Clones a notification into a new, inactive copy.
 */
class DuplicateNotification extends AbilityBase {

    protected $id            = 'notificationx/duplicate-notification';
    protected $label         = 'Duplicate a notification';
    protected $description   = 'Create a copy of an existing notification. The copy is created inactive (disabled) so it never conflicts with the free-plan single-active limit; enable it explicitly when ready.';
    protected $is_write      = true;
    protected $is_idempotent = false;

    public function input_schema() {
        return array(
            'type'       => 'object',
            'required'   => array( 'nx_id' ),
            'properties' => array(
                'nx_id' => array(
                    'type'        => 'integer',
                    'description' => 'The notification id to duplicate.',
                ),
                'title' => array(
                    'type'        => 'string',
                    'description' => 'Optional title for the copy. Defaults to the original title with " (copy)" appended.',
                ),
            ),
        );
    }

    public function output_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'nx_id' => array( 'type' => 'integer' ),
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

        $data = $existing;
        unset( $data['nx_id'], $data['created_at'], $data['updated_at'] );

        $original_title = isset( $existing['title'] ) ? $existing['title'] : '';
        if ( ! empty( $input['title'] ) ) {
            $data['title'] = sanitize_text_field( $input['title'] );
        } else {
            /* translators: %s: original notification title. */
            $data['title'] = trim( sprintf( __( '%s (copy)', 'notificationx' ), $original_title ) );
        }

        // Always create the copy disabled.
        $data['enabled'] = false;

        $saved    = $post_type->save_post( $data );
        $new_id   = isset( $saved['nx_id'] ) ? (int) $saved['nx_id'] : 0;

        if ( empty( $new_id ) ) {
            return new \WP_Error(
                'nx_mcp_duplicate_failed',
                __( 'Could not duplicate the notification.', 'notificationx' ),
                array( 'status' => 500 )
            );
        }

        return array(
            'nx_id'   => $new_id,
            'enabled' => false,
        );
    }
}
