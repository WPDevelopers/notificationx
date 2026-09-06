<?php
/**
 * Ability: enable or disable a notification.
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
 * Toggles a notification active/inactive. Honours the free-plan limit of one
 * active notification at a time (enforced by PostType::can_enable()).
 */
class ToggleNotification extends AbilityBase {

    protected $id            = 'notificationx/toggle-notification';
    protected $label         = 'Enable or disable a notification';
    protected $description   = 'Enable (activate) or disable (deactivate) a notification by its id. On the free plan only one notification can be active at a time, so enabling one may require disabling another first.';
    protected $is_write      = true;
    protected $is_idempotent = true;

    public function input_schema() {
        return array(
            'type'       => 'object',
            'required'   => array( 'nx_id', 'enabled' ),
            'properties' => array(
                'nx_id'   => array(
                    'type'        => 'integer',
                    'description' => 'The notification id to toggle.',
                ),
                'enabled' => array(
                    'type'        => 'boolean',
                    'description' => 'true to activate, false to deactivate.',
                ),
            ),
        );
    }

    public function output_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'nx_id'   => array( 'type' => 'integer' ),
                'enabled' => array( 'type' => 'boolean' ),
            ),
        );
    }

    public function execute( $input ) {
        $nx_id   = (int) $input['nx_id'];
        $enabled = (bool) $input['enabled'];

        $post_type = PostType::get_instance();
        $post      = $post_type->get_post( $nx_id );

        if ( empty( $post ) ) {
            return new \WP_Error(
                'nx_mcp_not_found',
                /* translators: %d: notification id. */
                sprintf( __( 'No notification found with id %d.', 'notificationx' ), $nx_id ),
                array( 'status' => 404 )
            );
        }

        $source           = isset( $post['source'] ) ? $post['source'] : '';
        $currently_enabled = $post_type->is_enabled( $nx_id );

        // No change requested.
        if ( $currently_enabled === $enabled ) {
            return array(
                'nx_id'   => $nx_id,
                'enabled' => $enabled,
                'message' => __( 'No change; notification already in the requested state.', 'notificationx' ),
            );
        }

        // Enabling: respect the free-plan single-active-notification cap.
        if ( $enabled && ! $currently_enabled && ! $post_type->can_enable( $source ) ) {
            return new \WP_Error(
                'nx_mcp_cap_reached',
                __( 'Another notification is already active. On the free plan only one notification can be active at a time — disable the active one first, or upgrade to Pro.', 'notificationx' ),
                array( 'status' => 409 )
            );
        }

        $post_type->save_post(
            array(
                'update_status' => true,
                'nx_id'         => $nx_id,
                'enabled'       => $enabled,
                'source'        => $source,
            )
        );

        // Re-read the stored record instead of PostType::is_enabled(), whose
        // enabled-source list is memoized before the toggle and would report
        // the pre-save state to the caller.
        $fresh = $post_type->get_post( $nx_id );

        return array(
            'nx_id'   => $nx_id,
            'enabled' => ! empty( $fresh['enabled'] ),
        );
    }
}
