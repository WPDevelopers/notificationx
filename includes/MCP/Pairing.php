<?php
/**
 * Static pairing-token connection for the NotificationX MCP server.
 *
 * This is the simplest way to connect a client that accepts a bearer token
 * (ChatGPT, Cursor, custom clients): the admin enables MCP, a long random
 * token is minted, and the client sends it as `Authorization: Bearer <token>`.
 * The token is bound to the admin who created it — every ability runs as that
 * user, and if they lose `manage_options` the connection stops working.
 *
 * OAuth 2.1 ({@see OAuth}) is the alternative for one-click clients like Claude.
 *
 * @package NotificationX\MCP
 */

namespace NotificationX\MCP;

use NotificationX\GetInstance;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @method static Pairing get_instance( $args = null )
 */
class Pairing {

    use GetInstance;

    const OPTION = 'notificationx_mcp_pairing';

    /**
     * The stored pairing state.
     *
     * @return array
     */
    public function state() {
        $state = get_option( self::OPTION, array() );
        return is_array( $state ) ? $state : array();
    }

    /**
     * Whether a pairing token currently exists.
     *
     * @return bool
     */
    public function is_connected() {
        $state = $this->state();
        return ! empty( $state['connected'] ) && ! empty( $state['site_token'] );
    }

    /**
     * The current pairing token (empty string if not connected).
     *
     * @return string
     */
    public function site_token() {
        $state = $this->state();
        return isset( $state['site_token'] ) ? (string) $state['site_token'] : '';
    }

    /**
     * The scopes granted to the pairing token.
     *
     * @return string[]
     */
    public function scopes() {
        $state = $this->state();
        return isset( $state['scopes'] ) && is_array( $state['scopes'] ) ? $state['scopes'] : array( 'read', 'write' );
    }

    /**
     * Whether the pairing token is read-only.
     *
     * @return bool
     */
    public function is_read_only() {
        $scopes = $this->scopes();
        return ! in_array( 'write', $scopes, true );
    }

    /**
     * Create the pairing token if it does not already exist. Idempotent.
     *
     * @param array $scopes Scopes to grant (defaults to read + write).
     * @return array The pairing state.
     */
    public function connect( $scopes = array( 'read', 'write' ) ) {
        $state = $this->state();
        if ( empty( $state['site_token'] ) ) {
            $state = array(
                'site_token'   => $this->generate_token(),
                'connected'    => true,
                'connected_at' => time(),
                'scopes'       => $scopes,
                'user_id'      => get_current_user_id(),
                'last_used'    => 0,
            );
            update_option( self::OPTION, $state, false );
        }
        return $state;
    }

    /**
     * Replace the pairing token with a fresh one (invalidates the old token).
     *
     * @return array The new pairing state.
     */
    public function rotate() {
        $state                 = $this->state();
        $state['site_token']   = $this->generate_token();
        $state['connected']    = true;
        $state['connected_at'] = time();
        $state['user_id']      = get_current_user_id();
        if ( empty( $state['scopes'] ) ) {
            $state['scopes'] = array( 'read', 'write' );
        }
        update_option( self::OPTION, $state, false );
        return $state;
    }

    /**
     * Remove the pairing token entirely.
     *
     * @return void
     */
    public function disconnect() {
        delete_option( self::OPTION );
    }

    /**
     * Note the last time the token was used (for the admin UI).
     *
     * @return void
     */
    public function touch_last_used() {
        $state = $this->state();
        if ( ! empty( $state ) ) {
            $state['last_used'] = time();
            update_option( self::OPTION, $state, false );
        }
    }

    /**
     * Constant-time comparison of a presented token against the stored one.
     *
     * @param string $token Presented bearer token.
     * @return bool
     */
    public function verify( $token ) {
        $stored = $this->site_token();
        if ( '' === $stored || '' === (string) $token ) {
            return false;
        }
        return hash_equals( $stored, (string) $token );
    }

    /**
     * The WordPress user id the token is bound to.
     *
     * @return int
     */
    public function user_id() {
        $state = $this->state();
        return isset( $state['user_id'] ) ? (int) $state['user_id'] : 0;
    }

    /**
     * Generate a 64-char random token.
     *
     * @return string
     */
    protected function generate_token() {
        return bin2hex( random_bytes( 32 ) );
    }
}
