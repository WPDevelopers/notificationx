<?php
/**
 * Per-IP rate limiter for the NotificationX MCP endpoint.
 *
 * Only *failed* credential attempts count toward the limit — a request that
 * presents no credential (the normal OAuth opening probe) is never penalised.
 * A fixed window is used so a stranded client can simply wait it out rather
 * than being locked out indefinitely by its own retries.
 *
 * @package NotificationX\MCP
 */

namespace NotificationX\MCP;

use NotificationX\GetInstance;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @method static RateLimiter get_instance( $args = null )
 */
class RateLimiter {

    use GetInstance;

    const PREFIX = 'nx_mcp_rl_';

    /**
     * Max failed attempts allowed inside the window.
     *
     * @return int
     */
    protected function max_fails() {
        $max = defined( 'NOTIFICATIONX_MCP_MAX_FAILS' ) ? (int) NOTIFICATIONX_MCP_MAX_FAILS : 10;
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Prefixed with nx_ per NotificationX convention.
        return (int) apply_filters( 'nx_mcp_rate_limit_max', $max );
    }

    /**
     * Lockout window length in seconds.
     *
     * @return int
     */
    protected function window() {
        $window = defined( 'NOTIFICATIONX_MCP_LOCKOUT_SECONDS' ) ? (int) NOTIFICATIONX_MCP_LOCKOUT_SECONDS : 900;
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Prefixed with nx_ per NotificationX convention.
        return (int) apply_filters( 'nx_mcp_rate_limit_window', $window );
    }

    /**
     * Client IP. REMOTE_ADDR only by default — forwarded headers are spoofable
     * and only trusted if a site opts in via the filter.
     *
     * @return string
     */
    protected function client_ip() {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Prefixed with nx_ per NotificationX convention.
        return (string) apply_filters( 'nx_mcp_client_ip', $ip );
    }

    /**
     * Transient key for the current client.
     *
     * @return string
     */
    protected function key() {
        return self::PREFIX . md5( $this->client_ip() );
    }

    /**
     * Whether the client is currently locked out.
     *
     * @return bool
     */
    public function is_locked() {
        $state = get_transient( $this->key() );
        return is_array( $state ) && ! empty( $state['count'] ) && $state['count'] >= $this->max_fails();
    }

    /**
     * Seconds until the current lockout ends (0 if not locked).
     *
     * @return int
     */
    public function retry_after() {
        $state = get_transient( $this->key() );
        if ( is_array( $state ) && ! empty( $state['until'] ) ) {
            return max( 0, (int) $state['until'] - time() );
        }
        return 0;
    }

    /**
     * Record a failed authentication attempt. Fixed window: the expiry is set
     * once when the window opens and not extended by later failures.
     *
     * @return void
     */
    public function record_failure() {
        $key    = $this->key();
        $window = $this->window();
        $state  = get_transient( $key );

        if ( ! is_array( $state ) || empty( $state['until'] ) || $state['until'] <= time() ) {
            $state = array(
                'count' => 0,
                'until' => time() + $window,
            );
        }

        $state['count']++;
        $ttl = max( 1, $state['until'] - time() );
        set_transient( $key, $state, $ttl );
    }

    /**
     * Clear the lockout state (called after a successful authentication).
     *
     * @return void
     */
    public function clear() {
        delete_transient( $this->key() );
    }
}
