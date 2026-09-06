<?php
/**
 * Ability registry for the NotificationX MCP server.
 *
 * Collects every {@see AbilityBase} the plugin exposes and hands them to the
 * MCP tools bridge. The registry is deliberately API-compatible with the
 * WordPress Abilities API: if that API is present it registers the abilities
 * there as well, so a future WordPress that ships the Abilities API in core
 * gets the same tools with zero changes. Pro (or any add-on) can inject its
 * own abilities through the `nx_register_abilities` filter.
 *
 * @package NotificationX\Abilities
 */

namespace NotificationX\Abilities;

use NotificationX\GetInstance;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @method static Registrar get_instance( $args = null )
 */
class Registrar {

    use GetInstance;

    /**
     * The ability category id used both here and (when present) in the
     * WordPress Abilities API.
     */
    const CATEGORY = 'notificationx';

    /**
     * Registered abilities keyed by ability id.
     *
     * @var AbilityBase[]
     */
    protected $abilities = array();

    /**
     * Whether {@see boot()} has already run.
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * Instantiate and register the core abilities. Called once when the MCP
     * module is enabled. Registration is intentionally decoupled from the
     * transport: abilities are always permission-checked individually, so it
     * is safe to register them whenever the module boots.
     *
     * @return void
     */
    public function boot() {
        if ( $this->booted ) {
            return;
        }
        $this->booted = true;

        $abilities = array(
            // Read.
            new Read\ListNotifications(),
            new Read\GetNotification(),
            new Read\ListTypes(),
            new Read\ListSources(),
            new Read\DescribeType(),
            new Read\GetStatus(),
            new Read\GetAnalytics(),
            new Read\GetSettings(),
            new Read\ListEntries(),
            new Read\ExportEntries(),
            // Manage (write).
            new Manage\CreateNotification(),
            new Manage\ToggleNotification(),
            new Manage\UpdateNotification(),
            new Manage\DuplicateNotification(),
            new Manage\DeleteNotification(),
        );

        /**
         * Filter the abilities exposed over MCP.
         *
         * Pro and third-party add-ons append their own AbilityBase instances
         * here (e.g. "notificationx-pro/...").
         *
         * @param AbilityBase[] $abilities
         */
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Prefixed with nx_ per NotificationX convention.
        $abilities = apply_filters( 'nx_register_abilities', $abilities );

        foreach ( $abilities as $ability ) {
            $this->register( $ability );
        }

        // Mirror into the WordPress Abilities API when it exists.
        if ( function_exists( 'wp_register_ability' ) ) {
            add_action( 'wp_abilities_api_init', array( $this, 'register_with_wp_abilities' ) );
        }
    }

    /**
     * Add a single ability to the registry.
     *
     * @param AbilityBase $ability Ability instance.
     * @return void
     */
    public function register( $ability ) {
        if ( $ability instanceof AbilityBase && $ability->get_id() ) {
            $this->abilities[ $ability->get_id() ] = $ability;
        }
    }

    /**
     * Get one ability by id.
     *
     * @param string $id Ability id (with category prefix).
     * @return AbilityBase|null
     */
    public function get( $id ) {
        return isset( $this->abilities[ $id ] ) ? $this->abilities[ $id ] : null;
    }

    /**
     * Look an ability up by its bare MCP tool name (no category prefix).
     *
     * @param string $tool_name Tool name, e.g. "list-notifications".
     * @return AbilityBase|null
     */
    public function get_by_tool_name( $tool_name ) {
        foreach ( $this->abilities as $ability ) {
            if ( $ability->tool_name() === $tool_name ) {
                return $ability;
            }
        }
        return null;
    }

    /**
     * All registered abilities.
     *
     * @return AbilityBase[]
     */
    public function get_all() {
        return $this->abilities;
    }

    /**
     * Bridge our abilities into the WordPress Abilities API when available.
     *
     * @return void
     */
    public function register_with_wp_abilities() {
        foreach ( $this->abilities as $ability ) {
            if ( function_exists( 'wp_has_ability' ) && wp_has_ability( $ability->get_id() ) ) {
                continue;
            }
            wp_register_ability(
                $ability->get_id(),
                array(
                    'label'               => $ability->get_label(),
                    'description'         => $ability->get_description(),
                    'category'            => self::CATEGORY,
                    'input_schema'        => $ability->input_schema(),
                    'output_schema'       => $ability->output_schema(),
                    'permission_callback' => array( $ability, 'permission_callback' ),
                    'execute_callback'    => array( $ability, 'run' ),
                    'meta'                => array(
                        'annotations' => $ability->annotations(),
                    ),
                )
            );
        }
    }
}
