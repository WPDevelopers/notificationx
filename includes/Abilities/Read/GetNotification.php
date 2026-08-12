<?php
/**
 * Ability: get a single NotificationX notification with its full configuration.
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
 * Returns the full stored configuration of one notification.
 */
class GetNotification extends AbilityBase {

    protected $id          = 'notificationx/get-notification';
    protected $label       = 'Get notification';
    protected $description = 'Get the full configuration of a single NotificationX notification by its id (nx_id), including type, source, theme, content and display settings.';

    public function input_schema() {
        return array(
            'type'       => 'object',
            'required'   => array( 'nx_id' ),
            'properties' => array(
                'nx_id' => array(
                    'type'        => 'integer',
                    'description' => 'The notification id to fetch.',
                ),
            ),
        );
    }

    public function output_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'notification' => array( 'type' => 'object' ),
            ),
        );
    }

    public function execute( $input ) {
        $nx_id = (int) $input['nx_id'];
        $post  = PostType::get_instance()->get_post( $nx_id );

        if ( empty( $post ) ) {
            return new \WP_Error(
                'nx_mcp_not_found',
                /* translators: %d: notification id. */
                sprintf( __( 'No notification found with id %d.', 'notificationx' ), $nx_id ),
                array( 'status' => 404 )
            );
        }

        return array(
            'notification' => $post,
        );
    }
}
