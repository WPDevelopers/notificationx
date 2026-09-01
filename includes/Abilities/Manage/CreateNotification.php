<?php
/**
 * Ability: create a notification from a configuration object.
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
 * Creates a new notification from a config object. The config is the same shape
 * that get-notification returns (and that export-notification produces in Pro),
 * so this is both a "create from scratch" tool and the second half of a
 * cross-site copy (export on the origin, create on the destination).
 *
 * The notification is created disabled by default so it never surprises the
 * site the moment it lands. Passing enabled:true activates it immediately; on
 * the free plan the single-active limit still applies, so the stored record may
 * come back disabled if another notification is already active — the returned
 * "enabled" reflects the real state after save.
 */
class CreateNotification extends AbilityBase {

    protected $id            = 'notificationx/create-notification';
    protected $label         = 'Create a notification';
    protected $description   = 'Create a new notification from a configuration object (the shape returned by get-notification). Use it to build a notification or to recreate an exported one on another site. Requires config.type, config.source and config.themes. Created disabled unless enabled:true (the free-plan single-active limit still applies).';
    protected $is_write      = true;
    protected $is_idempotent = false;

    /**
     * Keys stripped from an incoming config so the save always inserts a new
     * record rather than colliding with identity from another site.
     *
     * @var string[]
     */
    protected $strip_keys = array( 'nx_id', 'created_at', 'updated_at', 'update_status', '_nx_export' );

    public function input_schema() {
        return array(
            'type'       => 'object',
            'required'   => array( 'config' ),
            'properties' => array(
                'config'  => array(
                    'type'        => 'object',
                    'description' => 'The notification configuration. Must include at least "type", "source" and "themes". Get a valid shape from get-notification.',
                ),
                'title'   => array(
                    'type'        => 'string',
                    'description' => 'Optional title override for the new notification.',
                ),
                'enabled' => array(
                    'type'        => 'boolean',
                    'description' => 'Activate the notification immediately. Defaults to false (created disabled).',
                ),
            ),
        );
    }

    public function output_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'nx_id'        => array( 'type' => 'integer' ),
                'enabled'      => array( 'type' => 'boolean' ),
                'notification' => array( 'type' => 'object' ),
            ),
        );
    }

    public function execute( $input ) {
        $config = isset( $input['config'] ) && is_array( $input['config'] ) ? $input['config'] : array();

        foreach ( $this->strip_keys as $key ) {
            unset( $config[ $key ] );
        }

        // save_post() reads type/source/themes directly; without them it would
        // emit notices and store an unusable record. Fail loudly instead.
        foreach ( array( 'type', 'source', 'themes' ) as $required ) {
            if ( empty( $config[ $required ] ) || ! is_string( $config[ $required ] ) ) {
                return new \WP_Error(
                    'nx_mcp_invalid_config',
                    /* translators: %s: config field name. */
                    sprintf( __( 'The config is missing a valid "%s". A notification needs at least type, source and themes.', 'notificationx' ), $required ),
                    array( 'status' => 400 )
                );
            }
        }

        if ( ! empty( $input['title'] ) ) {
            $config['title'] = sanitize_text_field( $input['title'] );
        } elseif ( ! isset( $config['title'] ) ) {
            $config['title'] = __( 'Untitled notification', 'notificationx' );
        }

        $want_enabled = ! empty( $input['enabled'] );

        // Always insert disabled, so the new record can never bypass the
        // free-plan single-active cap on the way in. Activation (if asked for)
        // then goes through the same gated path ToggleNotification uses.
        $config['enabled'] = false;

        $post_type = PostType::get_instance();
        $saved     = $post_type->save_post( $config );
        $new_id    = isset( $saved['nx_id'] ) ? (int) $saved['nx_id'] : 0;

        if ( empty( $new_id ) ) {
            return new \WP_Error(
                'nx_mcp_create_failed',
                __( 'Could not create the notification from the provided config.', 'notificationx' ),
                array( 'status' => 500 )
            );
        }

        // Activate only if requested AND allowed: can_enable() honours the free
        // single-active limit (Pro lifts it via the nx_can_enable filter), and
        // the update_status path keeps the enabled-source bookkeeping correct.
        if ( $want_enabled && $post_type->can_enable( $config['source'] ) ) {
            $post_type->save_post(
                array(
                    'update_status' => true,
                    'nx_id'         => $new_id,
                    'enabled'       => true,
                    'source'        => $config['source'],
                )
            );
        }

        // Re-read so the caller sees the stored, normalised record and the real
        // enabled state (may be false if the single-active cap blocked it).
        $fresh = $post_type->get_post( $new_id );

        return array(
            'nx_id'        => $new_id,
            'enabled'      => ! empty( $fresh['enabled'] ),
            'notification' => $fresh,
        );
    }
}
