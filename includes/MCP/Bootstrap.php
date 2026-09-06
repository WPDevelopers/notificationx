<?php
/**
 * Conditional bootstrap for the NotificationX MCP module.
 *
 * NotificationX still supports PHP 5.6, but the MCP server uses modern PHP
 * (random_bytes, hash_equals, JSON everywhere). Rather than raise the plugin's
 * floor for everyone, the MCP module is a progressive enhancement: it loads
 * only when the runtime can support it. On older PHP the plugin behaves exactly
 * as before and MCP simply does not appear.
 *
 * @package NotificationX\MCP
 */

namespace NotificationX\MCP;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Static entry point invoked from the main NotificationX engine.
 */
class Bootstrap {

    /**
     * Minimum PHP version the MCP module requires.
     */
    const MIN_PHP = '7.0';

    /**
     * Load the MCP module when the runtime supports it.
     *
     * @return void
     */
    public static function init() {
        if ( ! self::is_supported() ) {
            return;
        }
        Manager::get_instance()->init();
    }

    /**
     * Whether the current runtime can run the MCP module.
     *
     * @return bool
     */
    public static function is_supported() {
        $supported = version_compare( PHP_VERSION, self::MIN_PHP, '>=' )
            && function_exists( 'random_bytes' )
            && function_exists( 'hash_equals' );

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Prefixed with nx_ per NotificationX convention.
        return (bool) apply_filters( 'nx_mcp_is_supported', $supported );
    }
}
