<?php
/**
 * Ability: delete a notification.
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
 * Permanently deletes a notification (and its entries/stats). Requires an
 * explicit confirm flag so an assistant cannot delete on a loose instruction.
 */
class DeleteNotification extends AbilityBase {

    protected $id            = 'notificationx/delete-notification';
    protected $label         = 'Delete a notification';
    protected $description   = 'Permanently delete a notification and its analytics. This cannot be undone. Requires confirm:true to proceed.';
    protected $is_write      = true;
    protected $is_idempotent = false;

    public function input_schema() {
        return array(
            'type'       => 'object',
            'required'   => array( 'nx_id', 'confirm' ),
            'properties' => array(
                'nx_id'   => array(
                    'type'        => 'integer',
                    'description' => 'The notification id to delete.',
                ),
                'confirm' => array(
                    'type'        => 'boolean',
                    'description' => 'Must be true to confirm permanent deletion.',
                ),
            ),
        );
    }

    public function output_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'nx_id'   => array( 'type' => 'integer' ),
                'deleted' => array( 'type' => 'boolean' ),
            ),
        );
    }

    public function execute( $input ) {
        $nx_id = (int) $input['nx_id'];

        if ( empty( $input['confirm'] ) ) {
            return new \WP_Error(
                'nx_mcp_confirm_required',
                __( 'Deletion not confirmed. Pass confirm:true to permanently delete this notification.', 'notificationx' ),
                array( 'status' => 400 )
            );
        }

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

        $post_type->delete_post( $nx_id );

        return array(
            'nx_id'   => $nx_id,
            'deleted' => true,
        );
    }
}
