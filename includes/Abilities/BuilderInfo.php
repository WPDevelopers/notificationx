<?php
/**
 * Shared builder metadata for MCP abilities: the authoritative theme ids and
 * source→type map the admin wizard uses. Centralised so describe-type,
 * create-notification and update-notification all agree on what is valid.
 *
 * @package NotificationX\Abilities
 */

namespace NotificationX\Abilities;

use NotificationX\Types\TypeFactory;
use NotificationX\Extensions\ExtensionFactory;
use NotificationX\Core\Modules;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Read-only lookups over the builder's theme/source registry.
 */
class BuilderInfo {

    /**
     * @var bool Whether the builder theme filters have been primed this request.
     */
    protected static $booted = false;

    /**
     * @var array Raw nx_themes map (id => entry).
     */
    protected static $themes = array();

    /**
     * @var array Raw nx_res_themes map (id => entry).
     */
    protected static $res_themes = array();

    /**
     * Prime the extension builder filters once (same trigger the admin metabox
     * uses), then cache the theme maps for the rest of the request.
     *
     * @return void
     */
    public static function boot() {
        if ( self::$booted ) {
            return;
        }
        self::$booted = true;
        // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- consuming core NotificationX (nx_) hooks.
        do_action( 'nx_before_metabox_load' );
        $themes = apply_filters( 'nx_themes', array() );
        $res    = apply_filters( 'nx_res_themes', array() );
        // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        self::$themes     = is_array( $themes ) ? $themes : array();
        self::$res_themes = is_array( $res ) ? $res : array();
    }

    /**
     * The raw nx_themes map.
     *
     * @return array
     */
    public static function all_themes() {
        self::boot();
        return self::$themes;
    }

    /**
     * The raw nx_res_themes map.
     *
     * @return array
     */
    public static function all_res_themes() {
        self::boot();
        return self::$res_themes;
    }

    /**
     * Registered source (extension) objects keyed by id.
     *
     * @return array
     */
    public static function sources() {
        $exts = ExtensionFactory::get_instance()->get_all();
        return is_array( $exts ) ? $exts : array();
    }

    /**
     * Whether a source id is registered.
     *
     * @param string $source Source id.
     * @return bool
     */
    public static function source_exists( $source ) {
        $exts = self::sources();
        return $source && isset( $exts[ $source ] );
    }

    /**
     * The notification type a source belongs to (e.g. press_bar → notification_bar).
     *
     * @param string $source Source id.
     * @return string
     */
    public static function type_for_source( $source ) {
        $exts = self::sources();
        return isset( $exts[ $source ]->types ) ? (string) $exts[ $source ]->types : '';
    }

    /**
     * Whether a source's module is enabled (sources with no module are always on).
     *
     * @param string $source Source id.
     * @return bool
     */
    public static function source_enabled( $source ) {
        $exts = self::sources();
        if ( ! isset( $exts[ $source ] ) ) {
            return false;
        }
        $module = isset( $exts[ $source ]->module ) ? $exts[ $source ]->module : '';
        return $module ? (bool) Modules::get_instance()->is_enabled( $module ) : true;
    }

    /**
     * Whether a source is Pro-only.
     *
     * @param string $source Source id.
     * @return bool
     */
    public static function source_is_pro( $source ) {
        $exts = self::sources();
        return isset( $exts[ $source ] ) && ! empty( $exts[ $source ]->is_pro );
    }

    /**
     * Valid theme ids for a source (desktop themes).
     *
     * @param string $source Source id.
     * @return string[]
     */
    public static function theme_ids_for_source( $source ) {
        return self::filter_ids( self::all_themes(), $source );
    }

    /**
     * Valid responsive theme ids for a source.
     *
     * @param string $source Source id.
     * @return string[]
     */
    public static function res_theme_ids_for_source( $source ) {
        return self::filter_ids( self::all_res_themes(), $source );
    }

    /**
     * Whether a theme id is valid for a source.
     *
     * @param string $source Source id.
     * @param string $theme  Theme id.
     * @return bool
     */
    public static function is_valid_theme( $source, $theme ) {
        return $theme && in_array( $theme, self::theme_ids_for_source( $source ), true );
    }

    /**
     * A theme id that is guaranteed to exist for the source: the type's declared
     * default if valid, otherwise the first available theme.
     *
     * @param string $source Source id.
     * @return string
     */
    public static function effective_default_theme( $source ) {
        $ids = self::theme_ids_for_source( $source );
        $exts = self::sources();
        $type_id  = self::type_for_source( $source );
        $types    = TypeFactory::get_instance()->get_all();
        $declared = '';
        if ( isset( $exts[ $source ]->default_theme ) && $exts[ $source ]->default_theme ) {
            $declared = $exts[ $source ]->default_theme;
        } elseif ( $type_id && isset( $types[ $type_id ]->default_theme ) ) {
            $declared = $types[ $type_id ]->default_theme;
        }
        if ( $declared && in_array( $declared, $ids, true ) ) {
            return $declared;
        }
        return isset( $ids[0] ) ? $ids[0] : '';
    }

    /**
     * Filter a raw theme map down to the ids belonging to a source. Prefers each
     * entry's own source rule (["includes","source",[...]]); falls back to the
     * "<source>_" id prefix.
     *
     * @param array  $themes Raw theme map.
     * @param string $source Source id.
     * @return string[]
     */
    protected static function filter_ids( $themes, $source ) {
        $out = array();
        foreach ( $themes as $id => $theme ) {
            $value = is_array( $theme ) && isset( $theme['value'] ) ? (string) $theme['value'] : (string) $id;
            $match = false;

            // The entry's `rules` can be a NotificationX\Core\Rule object (which
            // is NOT array-accessible, only JSON-serialisable) or a plain array.
            // Normalise to an array so the source rule can be read either way.
            $rules = null;
            if ( is_array( $theme ) && isset( $theme['rules'] ) ) {
                $rules = $theme['rules'];
                if ( is_object( $rules ) ) {
                    $decoded = json_decode( wp_json_encode( $rules ), true );
                    $rules   = is_array( $decoded ) ? $decoded : null;
                }
            }

            if ( is_array( $rules ) && ! empty( $rules ) ) {
                $sources = self::rule_sources( $rules );
                if ( ! empty( $sources ) ) {
                    $match = in_array( $source, $sources, true );
                } else {
                    $match = 0 === strpos( $value, $source . '_' );
                }
            } else {
                $match = 0 === strpos( $value, $source . '_' );
            }

            if ( $match ) {
                $out[] = $value;
            }
        }
        return $out;
    }

    /**
     * Extract source ids from a (possibly nested) rules array shaped like
     * ["includes","source",[...]].
     *
     * @param array $rules Rules array.
     * @return string[]
     */
    protected static function rule_sources( $rules ) {
        $found = array();
        if ( isset( $rules[0], $rules[1], $rules[2] ) && 'includes' === $rules[0] && 'source' === $rules[1] && is_array( $rules[2] ) ) {
            foreach ( $rules[2] as $s ) {
                $found[] = (string) $s;
            }
            return $found;
        }
        foreach ( $rules as $r ) {
            if ( is_array( $r ) ) {
                $found = array_merge( $found, self::rule_sources( $r ) );
            }
        }
        return $found;
    }
}
