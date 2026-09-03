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
     * @var array Raw nx_themes_trigger map (theme id => trigger list).
     */
    protected static $triggers = array();

    /**
     * @var bool Whether the theme-trigger map has been primed this request.
     */
    protected static $triggers_booted = false;

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
     * The default notification-template (content-slot → data-tag map) the admin
     * builder applies when a theme is selected.
     *
     * Data-driven types (comments, download stats, reviews, sales, forms) render
     * their text entirely through this template; static-content types (bar,
     * cookie notice, announcement, exit intent) carry their own literal content
     * and have no template here. The builder writes it from the selected theme's
     * `template` array via the nx_themes_trigger system (@notification-template.
     * <param>:<tag> entries). A headless create/update (MCP, REST, WP-CLI) never
     * fires those UI triggers, so without backfilling this the record saves and
     * lists fine but renders blank for data-driven types. Reconstructing it from
     * the exact same trigger data guarantees it can never diverge from the admin.
     *
     * @param string $theme Theme id (stored value, e.g. download_stats_today-download).
     * @return array Param => tag/value map (first_param, custom_first_param, ...); empty when the theme has no template.
     */
    public static function default_template_for_theme( $theme ) {
        if ( ! $theme ) {
            return array();
        }
        if ( ! self::$triggers_booted ) {
            self::boot(); // Ensures nx_before_metabox_load fired so extensions registered nx_themes_trigger.
            self::$triggers_booted = true;
            // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- consuming core NotificationX (nx_) hooks.
            $triggers = apply_filters( 'nx_themes_trigger', array() );
            // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            self::$triggers = is_array( $triggers ) ? $triggers : array();
        }

        $list = isset( self::$triggers[ $theme ] ) && is_array( self::$triggers[ $theme ] ) ? self::$triggers[ $theme ] : array();

        $template = array();
        $prefix   = '@notification-template.';
        $len      = strlen( $prefix );
        foreach ( $list as $entry ) {
            if ( ! is_string( $entry ) || 0 !== strpos( $entry, $prefix ) ) {
                continue;
            }
            // Entry shape: "@notification-template.<param>:<value>". Split on the
            // FIRST colon only — a value may itself contain colons (e.g. custom
            // text like "in last {{day:7}}").
            $rest = substr( $entry, $len );
            $pos  = strpos( $rest, ':' );
            if ( false === $pos ) {
                continue;
            }
            $param = substr( $rest, 0, $pos );
            $value = substr( $rest, $pos + 1 );
            if ( '' !== $param ) {
                $template[ $param ] = $value;
            }
        }
        return $template;
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

            // The entry's `rules` can be a NotificationX\Core\Rule object, a plain
            // array, or a nested mix (e.g. GDPR uses ["and", <Rule>, <Rule>] where
            // the source rule is nested). JSON round-trip the WHOLE tree so every
            // nested Rule object becomes an array and the recursive source lookup
            // below can reach it.
            $rules = null;
            if ( is_array( $theme ) && isset( $theme['rules'] ) ) {
                $decoded = json_decode( wp_json_encode( $theme['rules'] ), true );
                $rules   = is_array( $decoded ) ? $decoded : null;
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
