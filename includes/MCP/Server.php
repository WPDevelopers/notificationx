<?php
/**
 * The NotificationX MCP server — JSON-RPC 2.0 over HTTP.
 *
 * Handles the MCP method set (initialize, ping, tools/list, tools/call,
 * notifications) and authenticates every call via a static pairing token or an
 * OAuth 2.1 access token. On success it impersonates the granting admin so each
 * ability's own capability checks run as that user; on failure it returns an
 * RFC 9728 Bearer challenge so a client can discover how to authorize.
 *
 * @package NotificationX\MCP
 */

namespace NotificationX\MCP;

use NotificationX\GetInstance;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @method static Server get_instance( $args = null )
 */
class Server {

    use GetInstance;

    const PROTOCOL_VERSION = '2025-06-18';

    // JSON-RPC error codes.
    const PARSE_ERROR      = -32700;
    const INVALID_REQUEST  = -32600;
    const METHOD_NOT_FOUND = -32601;
    const INVALID_PARAMS   = -32602;
    const INTERNAL_ERROR   = -32603;
    const UNAUTHORIZED     = -32001;

    /**
     * Handle an MCP request. Returns a WP_REST_Response so both the REST route
     * and the pretty endpoint can emit it consistently.
     *
     * @param \WP_REST_Request $request Incoming request.
     * @return \WP_REST_Response
     */
    public function handle( $request ) {
        if ( ! Manager::get_instance()->is_enabled() ) {
            return $this->response( array(), 403 );
        }

        $limiter = RateLimiter::get_instance();
        if ( $limiter->is_locked() ) {
            $resp = $this->response(
                $this->error_body( null, self::UNAUTHORIZED, __( 'Too many failed attempts. Try again later.', 'notificationx' ) ),
                429
            );
            $resp->header( 'Retry-After', (string) $limiter->retry_after() );
            return $this->with_challenge( $resp );
        }

        $token = $this->bearer_token( $request );

        // No credential: normal OAuth opening probe. Challenge, do not penalise.
        if ( '' === $token ) {
            return $this->with_challenge(
                $this->response(
                    $this->error_body( null, self::UNAUTHORIZED, __( 'Authentication required.', 'notificationx' ) ),
                    401
                )
            );
        }

        $auth = $this->authenticate( $token );
        if ( is_wp_error( $auth ) ) {
            $limiter->record_failure();
            return $this->with_challenge(
                $this->response(
                    $this->error_body( null, self::UNAUTHORIZED, $auth->get_error_message() ),
                    401
                )
            );
        }
        $limiter->clear();

        // Scope → read/write gating for this request.
        Tools::get_instance()->set_read_only( ! empty( $auth['read_only'] ) );

        $raw     = $request->get_body();
        $decoded = json_decode( $raw, true );

        if ( null === $decoded && JSON_ERROR_NONE !== json_last_error() ) {
            return $this->response( $this->error_body( null, self::PARSE_ERROR, __( 'Parse error.', 'notificationx' ) ), 400 );
        }

        // Batch vs single.
        if ( $this->is_batch( $decoded ) ) {
            $out = array();
            foreach ( $decoded as $message ) {
                $result = $this->dispatch( $message );
                if ( null !== $result ) {
                    $out[] = $result;
                }
            }
            return empty( $out ) ? $this->response( null, 202 ) : $this->response( $out, 200 );
        }

        $result = $this->dispatch( is_array( $decoded ) ? $decoded : array() );
        return null === $result ? $this->response( null, 202 ) : $this->response( $result, 200 );
    }

    /**
     * Dispatch a single JSON-RPC message.
     *
     * @param array $message Decoded message.
     * @return array|null JSON-RPC response, or null for notifications.
     */
    protected function dispatch( $message ) {
        $method = isset( $message['method'] ) ? $message['method'] : '';
        $id     = isset( $message['id'] ) ? $message['id'] : null;
        $params = isset( $message['params'] ) && is_array( $message['params'] ) ? $message['params'] : array();

        // Notifications (no id) are acknowledged without a body.
        if ( null === $id && 0 === strpos( (string) $method, 'notifications/' ) ) {
            return null;
        }

        switch ( $method ) {
            case 'initialize':
                return $this->result( $id, array(
                    'protocolVersion' => self::PROTOCOL_VERSION,
                    'capabilities'    => array(
                        'tools' => array( 'listChanged' => false ),
                    ),
                    'serverInfo'      => array(
                        'name'    => 'notificationx',
                        'version' => defined( 'NOTIFICATIONX_VERSION' ) ? NOTIFICATIONX_VERSION : '1.0.0',
                    ),
                ) );

            case 'ping':
                return $this->result( $id, (object) array() );

            case 'tools/list':
                return $this->result( $id, array( 'tools' => Tools::get_instance()->list_tools() ) );

            case 'tools/call':
                return $this->call_tool( $id, $params );

            default:
                if ( null === $id ) {
                    return null;
                }
                return $this->error_body( $id, self::METHOD_NOT_FOUND, __( 'Method not found.', 'notificationx' ) );
        }
    }

    /**
     * Handle tools/call.
     *
     * @param mixed $id     JSON-RPC id.
     * @param array $params Params (name + arguments).
     * @return array
     */
    protected function call_tool( $id, $params ) {
        $name = isset( $params['name'] ) ? $params['name'] : '';
        $args = isset( $params['arguments'] ) && is_array( $params['arguments'] ) ? $params['arguments'] : array();

        if ( '' === $name ) {
            return $this->error_body( $id, self::INVALID_PARAMS, __( 'Missing tool name.', 'notificationx' ) );
        }

        $result = Tools::get_instance()->invoke( $name, $args );

        // A tool-level error is returned as a *successful* JSON-RPC result with
        // isError=true, so the assistant can read and react to the message.
        if ( is_wp_error( $result ) ) {
            return $this->result( $id, array(
                'content' => array(
                    array( 'type' => 'text', 'text' => $result->get_error_message() ),
                ),
                'isError' => true,
            ) );
        }

        return $this->result( $id, array(
            'content'           => array(
                array( 'type' => 'text', 'text' => wp_json_encode( $result ) ),
            ),
            'structuredContent' => $result,
            'isError'           => false,
        ) );
    }

    /* --------------------------------------------------------------------- */
    /* Authentication                                                        */
    /* --------------------------------------------------------------------- */

    /**
     * Authenticate a bearer token via pairing token or OAuth, then impersonate
     * the granting admin.
     *
     * @param string $token Bearer token.
     * @return array|\WP_Error { user_id, read_only } or error.
     */
    protected function authenticate( $token ) {
        $pairing = Pairing::get_instance();
        if ( $pairing->is_connected() && $pairing->verify( $token ) ) {
            $user = $this->impersonate( $pairing->user_id() );
            if ( is_wp_error( $user ) ) {
                return $user;
            }
            $pairing->touch_last_used();
            return array(
                'user_id'   => $pairing->user_id(),
                'read_only' => $pairing->is_read_only(),
            );
        }

        $grant = OAuth::get_instance()->validate_token( $token );
        if ( is_array( $grant ) ) {
            $user = $this->impersonate( $grant['user_id'] );
            if ( is_wp_error( $user ) ) {
                return $user;
            }
            return array(
                'user_id'   => $grant['user_id'],
                'read_only' => OAuth::get_instance()->scope_is_read_only( isset( $grant['scope'] ) ? $grant['scope'] : '' ),
            );
        }

        return new \WP_Error( 'nx_mcp_unauthorized', __( 'Invalid or expired credentials.', 'notificationx' ) );
    }

    /**
     * Become the granting user for the rest of the request. Refuses anyone who
     * is not an administrator (a demoted/deleted admin's grants stop working).
     *
     * @param int $user_id User id.
     * @return \WP_User|\WP_Error
     */
    protected function impersonate( $user_id ) {
        $user = get_user_by( 'id', (int) $user_id );
        if ( ! $user || ! user_can( $user, 'manage_options' ) ) {
            return new \WP_Error( 'nx_mcp_unauthorized', __( 'The account behind this connection can no longer manage NotificationX.', 'notificationx' ) );
        }
        wp_set_current_user( $user->ID );
        return $user;
    }

    /**
     * Extract the bearer token from the Authorization header.
     *
     * @param \WP_REST_Request $request Request.
     * @return string
     */
    protected function bearer_token( $request ) {
        $header = $request->get_header( 'authorization' );
        if ( ! $header ) {
            $header = $request->get_header( 'Authorization' );
        }
        if ( $header && preg_match( '/Bearer\s+(.+)/i', $header, $m ) ) {
            return trim( $m[1] );
        }
        return '';
    }

    /* --------------------------------------------------------------------- */
    /* Response helpers                                                      */
    /* --------------------------------------------------------------------- */

    /**
     * Build a JSON-RPC success result.
     *
     * @param mixed $id     Request id.
     * @param mixed $result Result payload.
     * @return array
     */
    protected function result( $id, $result ) {
        return array(
            'jsonrpc' => '2.0',
            'id'      => $id,
            'result'  => $result,
        );
    }

    /**
     * Build a JSON-RPC error object.
     *
     * @param mixed  $id      Request id.
     * @param int    $code    Error code.
     * @param string $message Error message.
     * @return array
     */
    protected function error_body( $id, $code, $message ) {
        return array(
            'jsonrpc' => '2.0',
            'id'      => $id,
            'error'   => array(
                'code'    => $code,
                'message' => $message,
            ),
        );
    }

    /**
     * Wrap a body in a WP_REST_Response and stamp the protocol header.
     *
     * @param mixed $body   Response body.
     * @param int   $status HTTP status.
     * @return \WP_REST_Response
     */
    protected function response( $body, $status = 200 ) {
        $resp = new \WP_REST_Response( $body, $status );
        $resp->header( 'MCP-Protocol-Version', self::PROTOCOL_VERSION );
        $resp->header( 'Cache-Control', 'no-store' );
        return $resp;
    }

    /**
     * Add the RFC 9728 Bearer challenge header pointing at resource metadata.
     *
     * @param \WP_REST_Response $resp Response.
     * @return \WP_REST_Response
     */
    protected function with_challenge( $resp ) {
        $metadata_url = home_url( '/.well-known/oauth-protected-resource' );
        $resp->header( 'WWW-Authenticate', sprintf( 'Bearer resource_metadata="%s"', $metadata_url ) );
        return $resp;
    }

    /**
     * Whether a decoded payload is a JSON-RPC batch (a list of messages).
     *
     * @param mixed $decoded Decoded payload.
     * @return bool
     */
    protected function is_batch( $decoded ) {
        return is_array( $decoded ) && array() !== $decoded && array_keys( $decoded ) === range( 0, count( $decoded ) - 1 );
    }
}
