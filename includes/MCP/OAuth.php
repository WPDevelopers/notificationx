<?php
/**
 * Minimal OAuth 2.1 authorization server for the NotificationX MCP endpoint.
 *
 * Implements exactly what MCP clients such as Claude need for a one-click
 * connection:
 *   - RFC 8414 authorization-server metadata + RFC 9728 protected-resource
 *     metadata (served by {@see Manager}),
 *   - RFC 7591 dynamic client registration (public clients, no secret),
 *   - the authorization-code grant with mandatory PKCE (S256),
 *   - refresh-token rotation.
 *
 * Access/refresh tokens are stored only as SHA-256 hashes; the raw value is
 * returned to the client once and never persisted. Every issued grant is bound
 * to the WordPress admin who approved it.
 *
 * @package NotificationX\MCP
 */

namespace NotificationX\MCP;

use NotificationX\GetInstance;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @method static OAuth get_instance( $args = null )
 */
class OAuth {

    use GetInstance;

    const OPTION            = 'notificationx_mcp_oauth';
    const ACCESS_TTL        = 3600;          // 1 hour.
    const REFRESH_TTL       = 2592000;       // 30 days.
    const CODE_TTL          = 60;            // 1 minute, single use.
    const MAX_CLIENTS       = 50;
    const CLIENT_TTL        = 86400;         // 24 hours for unused clients.

    /**
     * The OAuth issuer/authorization identifier. Path-based so it stays
     * specific to this plugin and can coexist with another plugin's MCP OAuth.
     *
     * @return string
     */
    public function issuer() {
        return home_url( '/notificationx/mcp' );
    }

    /**
     * The protected resource identifier (the MCP endpoint URL).
     *
     * @return string
     */
    public function resource() {
        return home_url( '/notificationx/mcp' );
    }

    /**
     * RFC 8414 authorization-server metadata document.
     *
     * @return array
     */
    public function authorization_server_metadata() {
        $base = home_url( '/notificationx/mcp' );
        return array(
            'issuer'                                => $this->issuer(),
            'authorization_endpoint'                => home_url( '/notificationx/authorize' ),
            'token_endpoint'                        => rest_url( 'notificationx/v1/mcp/oauth/token' ),
            'registration_endpoint'                 => rest_url( 'notificationx/v1/mcp/oauth/register' ),
            'scopes_supported'                      => array( 'read', 'write', 'mcp' ),
            'response_types_supported'              => array( 'code' ),
            'grant_types_supported'                 => array( 'authorization_code', 'refresh_token' ),
            'code_challenge_methods_supported'      => array( 'S256' ),
            'token_endpoint_auth_methods_supported' => array( 'none' ),
        );
    }

    /**
     * RFC 9728 protected-resource metadata document.
     *
     * @return array
     */
    public function protected_resource_metadata() {
        return array(
            'resource'                => $this->resource(),
            'authorization_servers'   => array( $this->issuer() ),
            'scopes_supported'        => array( 'read', 'write', 'mcp' ),
            'bearer_methods_supported' => array( 'header' ),
        );
    }

    /* --------------------------------------------------------------------- */
    /* Dynamic client registration                                           */
    /* --------------------------------------------------------------------- */

    /**
     * Register a public client (RFC 7591).
     *
     * @param array $params Registration request body.
     * @return array|\WP_Error Registration response or error.
     */
    public function register_client( $params ) {
        $redirect_uris = isset( $params['redirect_uris'] ) ? (array) $params['redirect_uris'] : array();
        $redirect_uris = array_values( array_filter( array_map( array( $this, 'clean_redirect_uri' ), $redirect_uris ) ) );

        if ( empty( $redirect_uris ) ) {
            return new \WP_Error( 'invalid_redirect_uri', __( 'At least one valid redirect_uri is required.', 'notificationx' ), array( 'status' => 400 ) );
        }

        $state = $this->store();
        $this->prune_clients( $state );

        if ( count( $state['clients'] ) >= self::MAX_CLIENTS ) {
            return new \WP_Error( 'too_many_clients', __( 'Client registration limit reached.', 'notificationx' ), array( 'status' => 400 ) );
        }

        $client_id = 'nx_' . bin2hex( random_bytes( 16 ) );

        $state['clients'][ $client_id ] = array(
            'redirect_uris' => $redirect_uris,
            'client_name'   => isset( $params['client_name'] ) ? sanitize_text_field( $params['client_name'] ) : '',
            'created_at'    => time(),
        );
        $this->save( $state );

        return array(
            'client_id'                => $client_id,
            'redirect_uris'            => $redirect_uris,
            'token_endpoint_auth_method' => 'none',
            'grant_types'              => array( 'authorization_code', 'refresh_token' ),
            'response_types'           => array( 'code' ),
        );
    }

    /* --------------------------------------------------------------------- */
    /* Authorization code                                                    */
    /* --------------------------------------------------------------------- */

    /**
     * Validate an incoming /authorize request.
     *
     * @param array $params Query params.
     * @return array|\WP_Error Cleaned request or error.
     */
    public function validate_authorize_request( $params ) {
        $client_id     = isset( $params['client_id'] ) ? sanitize_text_field( $params['client_id'] ) : '';
        $redirect_uri  = isset( $params['redirect_uri'] ) ? $this->clean_redirect_uri( $params['redirect_uri'] ) : '';
        $response_type = isset( $params['response_type'] ) ? sanitize_text_field( $params['response_type'] ) : '';
        $challenge     = isset( $params['code_challenge'] ) ? sanitize_text_field( $params['code_challenge'] ) : '';
        $method        = isset( $params['code_challenge_method'] ) ? sanitize_text_field( $params['code_challenge_method'] ) : '';
        $scope         = isset( $params['scope'] ) ? sanitize_text_field( $params['scope'] ) : 'read write';
        $state         = isset( $params['state'] ) ? sanitize_text_field( $params['state'] ) : '';

        $store  = $this->store();
        $client = isset( $store['clients'][ $client_id ] ) ? $store['clients'][ $client_id ] : null;

        if ( ! $client ) {
            return new \WP_Error( 'invalid_client', __( 'Unknown client.', 'notificationx' ), array( 'status' => 400 ) );
        }
        if ( ! $redirect_uri || ! in_array( $redirect_uri, $client['redirect_uris'], true ) ) {
            return new \WP_Error( 'invalid_redirect_uri', __( 'redirect_uri mismatch.', 'notificationx' ), array( 'status' => 400 ) );
        }
        if ( 'code' !== $response_type ) {
            return new \WP_Error( 'unsupported_response_type', __( 'Only the authorization code flow is supported.', 'notificationx' ), array( 'status' => 400 ) );
        }
        if ( '' === $challenge || 'S256' !== $method ) {
            return new \WP_Error( 'invalid_request', __( 'PKCE with S256 is required.', 'notificationx' ), array( 'status' => 400 ) );
        }

        return array(
            'client_id'      => $client_id,
            'redirect_uri'   => $redirect_uri,
            'code_challenge' => $challenge,
            'scope'          => $scope,
            'state'          => $state,
        );
    }

    /**
     * Issue a single-use authorization code bound to the approving user.
     *
     * @param array $request Validated authorize request.
     * @param int   $user_id Approving admin user id.
     * @return string The authorization code.
     */
    public function issue_code( $request, $user_id ) {
        $code  = bin2hex( random_bytes( 24 ) );
        $store = $this->store();

        $store['codes'][ $this->hash( $code ) ] = array(
            'client_id'      => $request['client_id'],
            'redirect_uri'   => $request['redirect_uri'],
            'code_challenge' => $request['code_challenge'],
            'scope'          => $request['scope'],
            'user_id'        => (int) $user_id,
            'expires'        => time() + self::CODE_TTL,
        );
        $this->save( $store );

        return $code;
    }

    /* --------------------------------------------------------------------- */
    /* Token endpoint                                                        */
    /* --------------------------------------------------------------------- */

    /**
     * Handle a token request (authorization_code or refresh_token grant).
     *
     * @param array $params Token request body.
     * @return array|\WP_Error Token response or error.
     */
    public function handle_token_request( $params ) {
        $grant_type = isset( $params['grant_type'] ) ? sanitize_text_field( $params['grant_type'] ) : '';

        if ( 'authorization_code' === $grant_type ) {
            return $this->grant_authorization_code( $params );
        }
        if ( 'refresh_token' === $grant_type ) {
            return $this->grant_refresh_token( $params );
        }
        return new \WP_Error( 'unsupported_grant_type', __( 'Unsupported grant type.', 'notificationx' ), array( 'status' => 400 ) );
    }

    /**
     * Exchange an authorization code (+ PKCE verifier) for tokens.
     *
     * @param array $params Token request body.
     * @return array|\WP_Error
     */
    protected function grant_authorization_code( $params ) {
        $code          = isset( $params['code'] ) ? (string) $params['code'] : '';
        $client_id     = isset( $params['client_id'] ) ? sanitize_text_field( $params['client_id'] ) : '';
        $redirect_uri  = isset( $params['redirect_uri'] ) ? $this->clean_redirect_uri( $params['redirect_uri'] ) : '';
        $verifier      = isset( $params['code_verifier'] ) ? (string) $params['code_verifier'] : '';

        $store    = $this->store();
        $code_key = $this->hash( $code );
        $record   = isset( $store['codes'][ $code_key ] ) ? $store['codes'][ $code_key ] : null;

        // Single use: delete on lookup regardless of outcome.
        if ( $record ) {
            unset( $store['codes'][ $code_key ] );
            $this->save( $store );
        }

        if ( ! $record || $record['expires'] < time() ) {
            return new \WP_Error( 'invalid_grant', __( 'Authorization code is invalid or expired.', 'notificationx' ), array( 'status' => 400 ) );
        }
        if ( $record['client_id'] !== $client_id || $record['redirect_uri'] !== $redirect_uri ) {
            return new \WP_Error( 'invalid_grant', __( 'Authorization code does not match this client.', 'notificationx' ), array( 'status' => 400 ) );
        }

        // PKCE: BASE64URL(SHA256(verifier)) === stored challenge.
        $computed = $this->base64url( hash( 'sha256', $verifier, true ) );
        if ( '' === $verifier || ! hash_equals( $record['code_challenge'], $computed ) ) {
            return new \WP_Error( 'invalid_grant', __( 'PKCE verification failed.', 'notificationx' ), array( 'status' => 400 ) );
        }

        return $this->issue_tokens( $record['user_id'], $client_id, $record['scope'] );
    }

    /**
     * Rotate a refresh token for a new access/refresh pair.
     *
     * @param array $params Token request body.
     * @return array|\WP_Error
     */
    protected function grant_refresh_token( $params ) {
        $refresh   = isset( $params['refresh_token'] ) ? (string) $params['refresh_token'] : '';
        $client_id = isset( $params['client_id'] ) ? sanitize_text_field( $params['client_id'] ) : '';

        $store   = $this->store();
        $key     = $this->hash( $refresh );
        $record  = isset( $store['refresh'][ $key ] ) ? $store['refresh'][ $key ] : null;

        if ( $record ) {
            unset( $store['refresh'][ $key ] );
            $this->save( $store );
        }

        if ( ! $record || $record['expires'] < time() || $record['client_id'] !== $client_id ) {
            return new \WP_Error( 'invalid_grant', __( 'Refresh token is invalid or expired.', 'notificationx' ), array( 'status' => 400 ) );
        }

        return $this->issue_tokens( $record['user_id'], $client_id, $record['scope'] );
    }

    /**
     * Mint and store a new access + refresh token pair (hashed at rest).
     *
     * @param int    $user_id   Bound admin user id.
     * @param string $client_id Client id.
     * @param string $scope     Space-separated scope string.
     * @return array Token response.
     */
    protected function issue_tokens( $user_id, $client_id, $scope ) {
        $access  = bin2hex( random_bytes( 32 ) );
        $refresh = bin2hex( random_bytes( 32 ) );

        $store = $this->store();
        $this->prune_tokens( $store );

        $store['tokens'][ $this->hash( $access ) ] = array(
            'user_id'   => (int) $user_id,
            'client_id' => $client_id,
            'scope'     => $scope,
            'expires'   => time() + self::ACCESS_TTL,
        );
        $store['refresh'][ $this->hash( $refresh ) ] = array(
            'user_id'   => (int) $user_id,
            'client_id' => $client_id,
            'scope'     => $scope,
            'expires'   => time() + self::REFRESH_TTL,
        );
        $this->save( $store );

        return array(
            'access_token'  => $access,
            'token_type'    => 'Bearer',
            'expires_in'    => self::ACCESS_TTL,
            'refresh_token' => $refresh,
            'scope'         => $scope,
        );
    }

    /**
     * Validate a bearer access token.
     *
     * @param string $token Presented access token.
     * @return array|false Grant record on success, false otherwise.
     */
    public function validate_token( $token ) {
        if ( '' === (string) $token ) {
            return false;
        }
        $store  = $this->store();
        $record = isset( $store['tokens'][ $this->hash( $token ) ] ) ? $store['tokens'][ $this->hash( $token ) ] : null;
        if ( ! $record || $record['expires'] < time() ) {
            return false;
        }
        return $record;
    }

    /**
     * Whether a scope string grants only read access.
     *
     * @param string $scope Space-separated scope string.
     * @return bool
     */
    public function scope_is_read_only( $scope ) {
        $scopes = preg_split( '/\s+/', trim( (string) $scope ) );
        return ! in_array( 'write', $scopes, true ) && ! in_array( 'mcp', $scopes, true );
    }

    /**
     * Revoke every issued token, refresh token and code. Used by disconnect.
     *
     * @return void
     */
    public function revoke_all() {
        $store            = $this->store();
        $store['tokens']  = array();
        $store['refresh'] = array();
        $store['codes']   = array();
        $this->save( $store );
    }

    /**
     * List OAuth clients that currently hold a live access or refresh token,
     * for the "Connected apps" admin list.
     *
     * @return array[] Each: { client_id, name, scope, read_only, last_seen }.
     */
    public function list_active_clients() {
        $store = $this->store();
        $this->prune_tokens( $store );

        $by_client = array();
        foreach ( array( 'tokens', 'refresh' ) as $bucket ) {
            foreach ( $store[ $bucket ] as $record ) {
                $cid = isset( $record['client_id'] ) ? $record['client_id'] : '';
                if ( '' === $cid ) {
                    continue;
                }
                if ( ! isset( $by_client[ $cid ] ) ) {
                    $name = isset( $store['clients'][ $cid ]['client_name'] ) && $store['clients'][ $cid ]['client_name']
                        ? $store['clients'][ $cid ]['client_name']
                        : $cid;
                    $by_client[ $cid ] = array(
                        'client_id' => $cid,
                        'name'      => $name,
                        'scope'     => isset( $record['scope'] ) ? $record['scope'] : 'read',
                        'read_only' => $this->scope_is_read_only( isset( $record['scope'] ) ? $record['scope'] : '' ),
                    );
                }
            }
        }

        return array_values( $by_client );
    }

    /**
     * Revoke all tokens issued to a single OAuth client.
     *
     * @param string $client_id Client id.
     * @return void
     */
    public function revoke_client( $client_id ) {
        $store = $this->store();
        foreach ( array( 'tokens', 'refresh', 'codes' ) as $bucket ) {
            foreach ( $store[ $bucket ] as $key => $record ) {
                if ( isset( $record['client_id'] ) && $record['client_id'] === $client_id ) {
                    unset( $store[ $bucket ][ $key ] );
                }
            }
        }
        $this->save( $store );
    }

    /* --------------------------------------------------------------------- */
    /* Storage helpers                                                       */
    /* --------------------------------------------------------------------- */

    /**
     * Load the OAuth store, pruning expired entries lazily.
     *
     * @return array
     */
    protected function store() {
        $store = get_option( self::OPTION, array() );
        if ( ! is_array( $store ) ) {
            $store = array();
        }
        $store += array(
            'clients' => array(),
            'codes'   => array(),
            'tokens'  => array(),
            'refresh' => array(),
        );
        return $store;
    }

    /**
     * Persist the OAuth store.
     *
     * @param array $store Store array.
     * @return void
     */
    protected function save( $store ) {
        update_option( self::OPTION, $store, false );
    }

    /**
     * Drop expired tokens/refresh/codes.
     *
     * @param array $store Store array (by reference).
     * @return void
     */
    protected function prune_tokens( &$store ) {
        $now = time();
        foreach ( array( 'tokens', 'refresh', 'codes' ) as $bucket ) {
            foreach ( $store[ $bucket ] as $key => $record ) {
                if ( empty( $record['expires'] ) || $record['expires'] < $now ) {
                    unset( $store[ $bucket ][ $key ] );
                }
            }
        }
    }

    /**
     * Evict old, unused registered clients.
     *
     * @param array $store Store array (by reference).
     * @return void
     */
    protected function prune_clients( &$store ) {
        $now = time();
        foreach ( $store['clients'] as $client_id => $client ) {
            if ( ! empty( $client['created_at'] ) && ( $now - $client['created_at'] ) > self::CLIENT_TTL ) {
                // Keep clients that still have live tokens.
                $has_token = false;
                foreach ( array( 'tokens', 'refresh' ) as $bucket ) {
                    foreach ( $store[ $bucket ] as $record ) {
                        if ( isset( $record['client_id'] ) && $record['client_id'] === $client_id ) {
                            $has_token = true;
                            break 2;
                        }
                    }
                }
                if ( ! $has_token ) {
                    unset( $store['clients'][ $client_id ] );
                }
            }
        }
    }

    /**
     * SHA-256 hash used for token storage.
     *
     * @param string $value Raw value.
     * @return string
     */
    protected function hash( $value ) {
        return hash( 'sha256', (string) $value );
    }

    /**
     * Base64url encoding (no padding).
     *
     * @param string $data Raw bytes.
     * @return string
     */
    protected function base64url( $data ) {
        return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
    }

    /**
     * Validate and normalise a redirect URI.
     *
     * @param string $uri Candidate redirect URI.
     * @return string Cleaned URI or empty string if invalid.
     */
    protected function clean_redirect_uri( $uri ) {
        $uri = esc_url_raw( trim( (string) $uri ), array( 'https', 'http' ) );
        return $uri ? $uri : '';
    }
}
