<?php
/**
 * Ability: update fields of an existing notification.
 *
 * @package NotificationX\Abilities\Manage
 */

namespace NotificationX\Abilities\Manage;

use NotificationX\Abilities\AbilityBase;
use NotificationX\Abilities\BuilderInfo;
use NotificationX\Core\PostType;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Updates the title, theme, data source and/or configuration fields of a
 * notification.
 *
 * The notification is loaded, the requested changes are merged over its stored
 * data, and it is saved back through the normal PostType::save_post() pipeline
 * so all NotificationX filters/hooks still run. The data source *can* be changed
 * (as in the admin); when it is, the notification type is realigned to the new
 * source automatically and the theme is validated/defaulted for that source.
 */
class UpdateNotification extends AbilityBase {

    protected $id            = 'notificationx/update-notification';
    protected $label         = 'Update a notification';
    protected $description   = 'Update an existing notification: change its title, theme, data source, or other configuration fields. Provide nx_id plus what to change. Changing "source" realigns the type automatically and, if you do not also pass a valid "themes", resets the theme to that source\'s default — call describe-type for the new source to get valid theme ids and content fields. Invalid theme/source values are rejected (not silently ignored).';
    protected $is_write      = true;
    protected $is_idempotent = true;

    /**
     * Fields that must never be overwritten via the free-form `fields` map.
     * (source/type are handled explicitly as first-class inputs.)
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
                'source' => array(
                    'type'        => 'string',
                    'description' => 'New data source id (from list-sources / describe-type). Changing it realigns the type and, unless a valid "themes" is also given, resets the theme to the new source default.',
                ),
                'themes' => array(
                    'type'        => 'string',
                    'description' => 'New theme id (must be valid for the notification\'s source; see describe-type). Rejected if invalid.',
                ),
                'fields' => array(
                    'type'        => 'object',
                    'description' => 'Advanced: a map of additional configuration fields to merge (e.g. content/design settings). Use get-notification / describe-type first to see the available fields.',
                ),
            ),
        );
    }

    public function output_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'nx_id'        => array( 'type' => 'integer' ),
                'applied'      => array( 'type' => 'array' ),
                'ignored'      => array( 'type' => 'array' ),
                'warnings'     => array( 'type' => 'array' ),
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

        $applied  = array();
        $ignored  = array();
        $warnings = array();

        // Start from the stored config so save_post() has everything it needs.
        $data          = $existing;
        $old_source    = isset( $existing['source'] ) ? $existing['source'] : '';
        $source        = $old_source;
        $source_changed = false;

        // --- Data source change (validated) ---------------------------------
        if ( ! empty( $input['source'] ) && (string) $input['source'] !== $old_source ) {
            $new_source = sanitize_text_field( $input['source'] );
            if ( ! BuilderInfo::source_exists( $new_source ) ) {
                return new \WP_Error(
                    'nx_mcp_invalid_source',
                    /* translators: %s: source id. */
                    sprintf( __( 'Unknown data source "%s". Call list-sources for valid ids.', 'notificationx' ), $new_source ),
                    array( 'status' => 400 )
                );
            }
            if ( ! BuilderInfo::source_enabled( $new_source ) ) {
                return new \WP_Error(
                    'nx_mcp_source_disabled',
                    /* translators: %s: source id. */
                    sprintf( __( 'The module for data source "%s" is disabled; enable it before using it.', 'notificationx' ), $new_source ),
                    array( 'status' => 409 )
                );
            }
            $source         = $new_source;
            $source_changed = true;
            $applied[]      = 'source';
        }

        // Type always follows the source (authoritative).
        $type = BuilderInfo::type_for_source( $source );
        if ( '' === $type ) {
            $type = isset( $existing['type'] ) ? $existing['type'] : $type;
        }
        if ( $type !== ( isset( $existing['type'] ) ? $existing['type'] : '' ) ) {
            $applied[] = 'type';
        }

        // --- Theme (validated against the effective source) -----------------
        // Only reject when the source's themes can actually be enumerated; a
        // source whose themes cannot be introspected accepts the given theme.
        $valid_themes  = BuilderInfo::theme_ids_for_source( $source );
        $theme_changed = false;
        $old_theme     = isset( $existing['themes'] ) ? $existing['themes'] : '';
        if ( ! empty( $input['themes'] ) ) {
            $theme = sanitize_text_field( $input['themes'] );
            if ( ! empty( $valid_themes ) && ! in_array( $theme, $valid_themes, true ) ) {
                return new \WP_Error(
                    'nx_mcp_invalid_theme',
                    sprintf(
                        /* translators: 1: theme id, 2: source id, 3: comma-separated valid ids. */
                        __( 'Theme "%1$s" is not valid for source "%2$s". Valid themes: %3$s', 'notificationx' ),
                        $theme,
                        $source,
                        implode( ', ', $valid_themes )
                    ),
                    array( 'status' => 400 )
                );
            }
            $data['themes'] = $theme;
            $applied[]      = 'themes';
            $theme_changed  = ( $theme !== $old_theme );
        } elseif ( $source_changed && ! empty( $valid_themes ) && ! in_array( isset( $data['themes'] ) ? $data['themes'] : '', $valid_themes, true ) ) {
            // Old theme belongs to the old source; reset to the new source default.
            $data['themes'] = BuilderInfo::effective_default_theme( $source );
            $applied[]      = 'themes';
            $theme_changed  = true;
            $warnings[]     = sprintf(
                /* translators: 1: theme id, 2: source id. */
                __( 'Theme reset to "%1$s" (the default for the new source "%2$s"). Pass a valid "themes" to choose another; see describe-type.', 'notificationx' ),
                $data['themes'],
                $source
            );
        }

        // When the theme changed and the caller didn't explicitly set a content
        // template, re-apply the new theme's default notification-template — same
        // as selecting a theme in the admin builder — so data-driven types don't
        // render blank after a theme/source switch. Restricted to the cases that
        // actually break (no existing template, or a source change that
        // invalidates the old tags) so a same-type theme swap never clobbers a
        // user's customised content. Static-content themes have no template here.
        $template_supplied = ! empty( $input['fields']['notification-template'] );
        $existing_template = isset( $existing['notification-template'] ) ? $existing['notification-template'] : array();
        if ( $theme_changed && ! $template_supplied && ( empty( $existing_template ) || $source_changed ) ) {
            $default_template = BuilderInfo::default_template_for_theme( $data['themes'] );
            if ( ! empty( $default_template ) ) {
                $data['notification-template'] = $default_template;
                $applied[]                     = 'notification-template';
            }
        }

        if ( $source_changed ) {
            $warnings[] = __( 'Data source changed: content fields from the previous source were kept. Set the new source\'s content via "fields" (see describe-type) so the notification renders as intended.', 'notificationx' );
        }

        // --- Title ----------------------------------------------------------
        if ( isset( $input['title'] ) ) {
            $data['title'] = sanitize_text_field( $input['title'] );
            $applied[]     = 'title';
        }

        // --- Free-form fields (protected keys reported, not silently dropped) -
        if ( ! empty( $input['fields'] ) && is_array( $input['fields'] ) ) {
            foreach ( $input['fields'] as $key => $value ) {
                if ( in_array( $key, $this->protected_keys, true ) ) {
                    $ignored[] = $key;
                    continue;
                }
                $data[ $key ] = $value;
                $applied[]    = $key;
            }
        }

        // Apply the resolved identity + source/type and mark updated.
        $data['nx_id']      = $nx_id;
        $data['type']       = $type;
        $data['source']     = $source;
        $data['updated_at'] = current_time( 'mysql' );
        unset( $data['update_status'] );

        $post_type->save_post( $data );

        return array(
            'nx_id'        => $nx_id,
            'applied'      => array_values( array_unique( $applied ) ),
            'ignored'      => array_values( array_unique( $ignored ) ),
            'warnings'     => $warnings,
            'notification' => $post_type->get_post( $nx_id ),
        );
    }
}
