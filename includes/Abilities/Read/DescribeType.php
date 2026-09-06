<?php
/**
 * Ability: describe a notification type — its sources, valid themes and content
 * template — so a client can build/edit a notification with correct values
 * instead of guessing theme ids and field keys.
 *
 * @package NotificationX\Abilities\Read
 */

namespace NotificationX\Abilities\Read;

use NotificationX\Abilities\AbilityBase;
use NotificationX\Abilities\BuilderInfo;
use NotificationX\Types\TypeFactory;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns the builder-accurate blueprint for a notification type: which data
 * sources power it, and for each source the valid theme ids (with labels,
 * preview images and the default), responsive themes, and the content-template
 * placeholders. This is the source of truth the admin wizard uses, so nothing
 * here is guessed.
 *
 * Intended flow: call describe-type first, pick a source + a theme id from the
 * returned list, then call create-notification / update-notification with those
 * exact values.
 */
class DescribeType extends AbilityBase {

    protected $id          = 'notificationx/describe-type';
    protected $label       = 'Describe a notification type';
    protected $description  = 'Get everything needed to build or edit a notification of a given type: its data sources, and for each source the valid theme ids (with labels, preview images, pro-flag and the default), responsive theme ids, and the content-template placeholders. Always call this before create-notification / update-notification so the theme and source are valid instead of guessed. Omit "type" for a summary of every type; pass "type" (and optionally "source") for full detail.';
    protected $is_write    = false;

    public function input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'type'   => array(
                    'type'        => 'string',
                    'description' => 'A notification type id (from list-types), e.g. "notification_bar", "offer_announcement", "exit_intent". Omit to get a summary of all types.',
                ),
                'source' => array(
                    'type'        => 'string',
                    'description' => 'Optional data source id (from list-sources) to scope the themes/content to a single source. Defaults to the type\'s default source.',
                ),
            ),
        );
    }

    public function output_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'types' => array( 'type' => 'array' ),
                'type'  => array( 'type' => 'object' ),
            ),
        );
    }

    public function execute( $input ) {
        BuilderInfo::boot();

        $type_id = isset( $input['type'] ) ? (string) $input['type'] : '';
        if ( '' === $type_id ) {
            return array( 'types' => $this->summarize_all() );
        }

        $type = $this->find_type( $type_id );
        if ( ! $type ) {
            return new \WP_Error(
                'nx_mcp_unknown_type',
                /* translators: %s: type id. */
                sprintf( __( 'Unknown notification type: %s. Call list-types (or describe-type with no argument) for valid ids.', 'notificationx' ), $type_id ),
                array( 'status' => 404 )
            );
        }
        $type_id = isset( $type->id ) ? $type->id : $type_id;

        $sources    = $this->sources_for_type( $type_id );
        $source_ids = wp_list_pluck( $sources, 'id' );

        // Which source to detail: requested (if valid), else the default, else first.
        $requested = isset( $input['source'] ) ? (string) $input['source'] : '';
        $source_id = '';
        if ( $requested && in_array( $requested, $source_ids, true ) ) {
            $source_id = $requested;
        } elseif ( ! empty( $type->default_source ) && in_array( $type->default_source, $source_ids, true ) ) {
            $source_id = $type->default_source;
        } elseif ( ! empty( $source_ids ) ) {
            $source_id = $source_ids[0];
        }

        $detail = array(
            'id'             => $type_id,
            'title'          => isset( $type->dashboard_title ) && $type->dashboard_title ? $type->dashboard_title : ( isset( $type->title ) ? $type->title : '' ),
            'is_pro'         => ! empty( $type->is_pro ),
            'default_source' => isset( $type->default_source ) ? $type->default_source : '',
            'default_theme'  => isset( $type->default_theme ) ? $type->default_theme : '',
            'link_type'      => isset( $type->link_type ) ? $type->link_type : 'none',
            'sources'        => $sources,
        );

        if ( $source_id ) {
            $themes    = $this->rich_themes( BuilderInfo::theme_ids_for_source( $source_id ), BuilderInfo::all_themes() );
            $effective = BuilderInfo::effective_default_theme( $source_id );

            $detail['detailed_source']   = $source_id;
            $detail['themes']            = $this->mark_default( $themes, $effective );
            $detail['responsive_themes'] = $this->rich_themes( BuilderInfo::res_theme_ids_for_source( $source_id ), BuilderInfo::all_res_themes() );
            $detail['content_template']  = $this->content_template_for_source( $source_id );
            $detail['effective_default_theme'] = $effective;

            // Flag a declared default that does not actually exist for this source
            // (some types ship a stale default, e.g. "announcements_theme-one").
            $declared = $detail['default_theme'];
            if ( $declared && $declared !== $effective && ! in_array( $declared, wp_list_pluck( $themes, 'id' ), true ) ) {
                $detail['default_theme_invalid'] = $declared;
            }

            $detail['required_to_create'] = array(
                'type'   => $type_id,
                'source' => $source_id,
                'themes' => $effective,
            );
        }

        return array( 'type' => $detail );
    }

    /**
     * Find a type by array key or by its ->id.
     *
     * @param string $type_id Type id.
     * @return object|null
     */
    protected function find_type( $type_id ) {
        $types = TypeFactory::get_instance()->get_all();
        if ( isset( $types[ $type_id ] ) ) {
            return $types[ $type_id ];
        }
        foreach ( (array) $types as $t ) {
            if ( is_object( $t ) && isset( $t->id ) && $t->id === $type_id ) {
                return $t;
            }
        }
        return null;
    }

    /**
     * Summary line for every registered type.
     *
     * @return array
     */
    protected function summarize_all() {
        $out   = array();
        $types = TypeFactory::get_instance()->get_all();
        if ( ! is_array( $types ) ) {
            return $out;
        }
        foreach ( $types as $key => $type ) {
            if ( ! is_object( $type ) ) {
                continue;
            }
            $tid   = isset( $type->id ) ? $type->id : (string) $key;
            $out[] = array(
                'id'             => $tid,
                'title'          => isset( $type->dashboard_title ) && $type->dashboard_title ? $type->dashboard_title : ( isset( $type->title ) ? $type->title : '' ),
                'is_pro'         => ! empty( $type->is_pro ),
                'default_source' => isset( $type->default_source ) ? $type->default_source : '',
                'sources'        => wp_list_pluck( $this->sources_for_type( $tid ), 'id' ),
            );
        }
        return $out;
    }

    /**
     * All data sources (extensions) that belong to a given type.
     *
     * @param string $type_id Type id.
     * @return array[]
     */
    protected function sources_for_type( $type_id ) {
        $result = array();
        foreach ( BuilderInfo::sources() as $key => $ext ) {
            if ( ! is_object( $ext ) || ( isset( $ext->types ) ? $ext->types : '' ) !== $type_id ) {
                continue;
            }
            $sid      = isset( $ext->id ) ? $ext->id : (string) $key;
            $result[] = array(
                'id'             => $sid,
                'title'          => isset( $ext->title ) ? $ext->title : '',
                'is_pro'         => ! empty( $ext->is_pro ),
                'module'         => isset( $ext->module ) ? $ext->module : '',
                'module_enabled' => BuilderInfo::source_enabled( $sid ),
                'default_theme'  => BuilderInfo::effective_default_theme( $sid ),
            );
        }
        return $result;
    }

    /**
     * Build rich theme objects from a list of ids + the raw theme map.
     *
     * @param string[] $ids   Valid theme ids for the source.
     * @param array    $raw   Raw nx_themes / nx_res_themes map.
     * @return array[]
     */
    protected function rich_themes( $ids, $raw ) {
        // Index raw entries by their value for lookup.
        $by_value = array();
        foreach ( $raw as $key => $entry ) {
            $value              = is_array( $entry ) && isset( $entry['value'] ) ? (string) $entry['value'] : (string) $key;
            $by_value[ $value ] = $entry;
        }
        $out = array();
        foreach ( $ids as $id ) {
            $entry = isset( $by_value[ $id ] ) ? $by_value[ $id ] : array();
            $out[] = array(
                'id'          => $id,
                'label'       => is_array( $entry ) && isset( $entry['label'] ) ? $entry['label'] : $id,
                'preview_url' => is_array( $entry ) && isset( $entry['icon'] ) ? $entry['icon'] : '',
                'is_pro'      => is_array( $entry ) ? ! empty( $entry['is_pro'] ) : false,
                'is_default'  => false,
            );
        }
        return $out;
    }

    /**
     * Mark the effective-default theme in a rich theme list.
     *
     * @param array[] $themes    Rich theme list.
     * @param string  $effective Effective default id.
     * @return array[]
     */
    protected function mark_default( $themes, $effective ) {
        foreach ( $themes as &$t ) {
            $t['is_default'] = ( $t['id'] === $effective );
        }
        unset( $t );
        return $themes;
    }

    /**
     * The content-template placeholders for a source, best-effort (the builder
     * filter is meant to be seeded by the metabox assembler and can throw when
     * called bare, so a failure must never crash discovery).
     *
     * @param string $source_id Source id.
     * @return array
     */
    protected function content_template_for_source( $source_id ) {
        $out = array();
        try {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- consuming a core NotificationX (nx_) hook.
            $templates = apply_filters( 'nx_notification_template', array() );
            if ( is_array( $templates ) && isset( $templates[ $source_id ] ) ) {
                $out['template'] = $templates[ $source_id ];
            }
        } catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- intentional: degrade gracefully.
            unset( $e );
        }
        return $out;
    }
}
