<?php
/**
 * Ability: get a single NotificationX notification with its full configuration.
 *
 * @package NotificationX\Abilities\Read
 */

namespace NotificationX\Abilities\Read;

use NotificationX\Abilities\AbilityBase;
use NotificationX\Abilities\BuilderInfo;
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
                'enabled'      => array( 'type' => 'boolean' ),
                'theme_valid'  => array( 'type' => 'boolean' ),
                'notification' => array( 'type' => 'object' ),
            ),
        );
    }

    public function execute( $input ) {
        $nx_id     = (int) $input['nx_id'];
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

        // Authoritative active state (from the enabled-source map, not the blob).
        $enabled = (bool) $post_type->is_enabled( $nx_id );
        // Whether the stored theme actually exists for this source (a false here
        // is the usual reason a notification saves but renders nothing).
        $source      = isset( $post['source'] ) ? $post['source'] : '';
        $theme       = isset( $post['themes'] ) ? $post['themes'] : ( isset( $post['theme'] ) ? $post['theme'] : '' );
        $theme_valid = $source && $theme ? BuilderInfo::is_valid_theme( $source, $theme ) : false;

        return array(
            'enabled'      => $enabled,
            'theme_valid'  => $theme_valid,
            'notification' => $post,
        );
    }
}
