<?php
/**
 * Loopback self-test for the NotificationX MCP server.
 *
 * Calls the site's own MCP endpoint the way an AI client would and reports the
 * first thing that is wrong, so an admin gets an actionable answer ("HTTPS
 * mismatch", "0 tools") instead of a silent "connected but useless" state.
 *
 * @package NotificationX\MCP
 */

namespace NotificationX\MCP;

use NotificationX\GetInstance;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @method static SelfTest get_instance( $args = null )
 */
class SelfTest {

    use GetInstance;

    /**
     * Run the staged diagnostic.
     *
     * @return array { ok:bool, step:string, message:string, tools:int }
     */
    public function run() {
        if ( ! Manager::get_instance()->is_enabled() ) {
            return $this->fail( 'disabled', __( 'MCP is turned off.', 'notificationx' ) );
        }

        $pairing = Pairing::get_instance();
        if ( ! $pairing->is_connected() ) {
            return $this->fail( 'not_connected', __( 'No connection token has been generated yet.', 'notificationx' ) );
        }

        $endpoint = home_url( '/notificationx/mcp' );
        $token    = $pairing->site_token();

        // 1) Authenticated tools/list must succeed and return at least one tool.
        $response = wp_remote_post( $endpoint, array(
            'timeout'   => 15,
            'sslverify' => apply_filters( 'https_local_ssl_verify', false ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core filter name.
            'headers'   => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ),
            'body'      => wp_json_encode( array(
                'jsonrpc' => '2.0',
                'id'      => 1,
                'method'  => 'tools/list',
                'params'  => (object) array(),
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $this->fail( 'unreachable', sprintf(
                /* translators: %s: error detail. */
                __( 'The MCP endpoint could not be reached from the server itself: %s', 'notificationx' ),
                $response->get_error_message()
            ) );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 401 === (int) $code || 403 === (int) $code ) {
            return $this->fail( 'auth', __( 'The endpoint rejected the connection token.', 'notificationx' ) );
        }

        $body  = json_decode( wp_remote_retrieve_body( $response ), true );
        $tools = isset( $body['result']['tools'] ) && is_array( $body['result']['tools'] ) ? count( $body['result']['tools'] ) : 0;

        if ( $tools < 1 ) {
            return $this->fail( 'no_tools', __( 'The server responded but exposed no tools.', 'notificationx' ) );
        }

        // 2) An unauthenticated call must be challenged (401 + WWW-Authenticate).
        $probe = wp_remote_post( $endpoint, array(
            'timeout'   => 15,
            'sslverify' => apply_filters( 'https_local_ssl_verify', false ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core filter name.
            'headers'   => array( 'Content-Type' => 'application/json' ),
            'body'      => wp_json_encode( array( 'jsonrpc' => '2.0', 'id' => 2, 'method' => 'ping' ) ),
        ) );

        if ( ! is_wp_error( $probe ) ) {
            $probe_code = (int) wp_remote_retrieve_response_code( $probe );
            $challenge  = wp_remote_retrieve_header( $probe, 'www-authenticate' );
            if ( 401 !== $probe_code || empty( $challenge ) ) {
                return $this->fail( 'challenge', __( 'The endpoint did not correctly challenge an unauthenticated request; OAuth discovery may fail for some clients.', 'notificationx' ) );
            }
        }

        return array(
            'ok'      => true,
            'step'    => 'ok',
            'message' => sprintf(
                /* translators: %d: number of tools. */
                _n( 'MCP is working. %d tool is available.', 'MCP is working. %d tools are available.', $tools, 'notificationx' ),
                $tools
            ),
            'tools'   => $tools,
        );
    }

    /**
     * Build a failure result.
     *
     * @param string $step    Failing step id.
     * @param string $message Human message.
     * @return array
     */
    protected function fail( $step, $message ) {
        return array(
            'ok'      => false,
            'step'    => $step,
            'message' => $message,
            'tools'   => 0,
        );
    }
}
