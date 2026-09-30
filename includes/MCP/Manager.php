<?php
/**
 * Orchestrates the NotificationX MCP module.
 *
 * Wires up the transport (REST route + pretty `/notificationx/mcp` endpoint),
 * OAuth discovery documents and the `/notificationx/authorize` consent page,
 * the admin-only management endpoints (connect / rotate / disconnect /
 * self-test), and the "MCP" tab in NotificationX settings. The whole feature
 * is gated behind a single `enable_mcp` setting that defaults to off.
 *
 * @package NotificationX\MCP
 */

namespace NotificationX\MCP;

use NotificationX\GetInstance;
use NotificationX\Admin\Settings;
use NotificationX\Core\Rules;
use NotificationX\Abilities\Registrar;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @method static Manager get_instance( $args = null )
 */
class Manager {

    /**
     * Path of the pretty MCP endpoint, relative to home.
     *
     * Also the suffix of the RFC 9728 discovery URLs we answer for.
     *
     * @var string
     */
    const ENDPOINT_PATH = 'notificationx/mcp';

    use GetInstance;

    /**
     * Boot the module. Called from the MCP bootstrap only when the runtime is
     * capable (PHP version check) — see Bootstrap.
     *
     * @return void
     */
    public function init() {
        // Abilities are always registered when the module boots; each is
        // permission-checked individually and the transport is separately gated.
        Registrar::get_instance()->boot();

        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        add_action( 'parse_request', array( $this, 'handle_front_requests' ), 0 );

        // Admin settings tab (pure PHP field schema; no JS rebuild needed).
        add_filter( 'nx_settings_tab', array( $this, 'register_settings_tab' ), 20 );
        add_filter( 'nx_protected_settings', array( $this, 'protect_enable_setting' ) );

        // CSS + JS for the MCP panel (copy / reveal / revoke controls).
        add_action( 'admin_print_footer_scripts', array( $this, 'print_panel_assets' ) );
        add_action( 'admin_init', array( $this, 'redirect_hidden_tab' ) );
    }

    /**
     * Send a user who cannot see the MCP tab (see register_settings_tab()) from
     * a `?tab=tab-mcp` link to the settings screen's first tab, rather than to
     * an empty screen.
     *
     * @return void
     */
    public function redirect_hidden_tab() {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only navigation check.
        if ( wp_doing_ajax() || ! isset( $_GET['page'], $_GET['tab'] ) || 'nx-settings' !== $_GET['page'] || 'tab-mcp' !== $_GET['tab'] ) {
            return;
        }
        // phpcs:enable
        if ( current_user_can( 'manage_options' ) ) {
            return;
        }
        wp_safe_redirect( remove_query_arg( 'tab' ) );
        exit;
    }

    /**
     * Whether MCP access is switched on.
     *
     * @return bool
     */
    public function is_enabled() {
        return (bool) Settings::get_instance()->get( 'settings.enable_mcp' );
    }

    /**
     * The site's MCP connector URL.
     *
     * @return string
     */
    public function connector_url() {
        return home_url( '/notificationx/mcp' );
    }

    /* --------------------------------------------------------------------- */
    /* REST routes                                                           */
    /* --------------------------------------------------------------------- */

    /**
     * Register the transport, OAuth and management routes.
     *
     * @return void
     */
    public function register_routes() {
        $ns = 'notificationx/v1';

        // MCP transport — auth happens inside the handler.
        register_rest_route( $ns, '/mcp', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'rest_mcp' ),
            'permission_callback' => '__return_true',
        ) );

        // OAuth: dynamic client registration + token endpoint (public).
        // Discovery over REST as well as `/.well-known/`. The well-known path is
        // a single namespace the whole site shares: another plugin that hooks
        // `parse_request` earlier, or a host that answers `/.well-known/` itself
        // (ACME), takes it and our clients then read someone else's metadata.
        // A route inside our own REST namespace cannot be taken, so that is what
        // Server::with_challenge() advertises. Public, like the documents
        // themselves.
        register_rest_route( $ns, '/mcp/oauth/protected-resource', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'rest_protected_resource' ),
            'permission_callback' => '__return_true',
        ) );
        register_rest_route( $ns, '/mcp/oauth/authorization-server', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'rest_authorization_server' ),
            'permission_callback' => '__return_true',
        ) );
        register_rest_route( $ns, '/mcp/oauth/register', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'rest_oauth_register' ),
            'permission_callback' => '__return_true',
        ) );
        register_rest_route( $ns, '/mcp/oauth/token', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'rest_oauth_token' ),
            'permission_callback' => '__return_true',
        ) );

        // Management (admin only).
        $admin = array( $this, 'admin_permission' );
        register_rest_route( $ns, '/mcp/connection', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'rest_connection' ),
            'permission_callback' => $admin,
        ) );
        register_rest_route( $ns, '/mcp/connect', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'rest_connect' ),
            'permission_callback' => $admin,
        ) );
        register_rest_route( $ns, '/mcp/rotate', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'rest_rotate' ),
            'permission_callback' => $admin,
        ) );
        register_rest_route( $ns, '/mcp/disconnect', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'rest_disconnect' ),
            'permission_callback' => $admin,
        ) );
        register_rest_route( $ns, '/mcp/self-test', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'rest_self_test' ),
            'permission_callback' => $admin,
        ) );
        // The enable toggle persists through here rather than the settings form.
        // The settings endpoint replaces the whole settings blob with whatever the
        // admin app posts (see Admin\Settings::save_settings()), so a request
        // carrying only `enable_mcp` would wipe every other setting. This writes
        // the one key and leaves the rest alone.
        register_rest_route( $ns, '/mcp/enable', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'rest_set_enabled' ),
            'permission_callback' => $admin,
            'args'                => array(
                'enabled' => array(
                    'required' => true,
                    'type'     => 'boolean',
                ),
            ),
        ) );
        register_rest_route( $ns, '/mcp/apps/revoke', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'rest_revoke_app' ),
            'permission_callback' => $admin,
        ) );
        register_rest_route( $ns, '/mcp/apps', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'rest_list_apps' ),
            'permission_callback' => $admin,
        ) );
    }

    /**
     * List the currently connected apps as JSON, so the Connected apps panel can
     * refresh itself without a full page reload (an app may have been approved or
     * detached since the page was rendered).
     *
     * @return \WP_REST_Response
     */
    public function rest_list_apps() {
        $apps = array();
        foreach ( $this->get_connected_apps() as $app ) {
            $apps[] = array(
                'type'        => $app['type'],
                'client_id'   => $app['client_id'],
                'name'        => $app['name'],
                'read_only'   => (bool) $app['read_only'],
                'scope_label' => $app['read_only'] ? __( 'Read-only', 'notificationx' ) : __( 'Read & write', 'notificationx' ),
            );
        }

        return new \WP_REST_Response(
            array(
                'status' => 'success',
                'count'  => count( $apps ),
                'apps'   => $apps,
            ),
            200
        );
    }

    /**
     * Revoke a single connected app (pairing token or one OAuth client).
     *
     * @param \WP_REST_Request $request Request.
     * @return \WP_REST_Response
     */
    public function rest_revoke_app( $request ) {
        $params = $request->get_json_params() ?: $request->get_body_params();
        $type   = isset( $params['type'] ) ? sanitize_text_field( $params['type'] ) : '';

        if ( 'pairing' === $type ) {
            Pairing::get_instance()->disconnect();
        } elseif ( 'oauth' === $type && ! empty( $params['client_id'] ) ) {
            OAuth::get_instance()->revoke_client( sanitize_text_field( $params['client_id'] ) );
        } else {
            return new \WP_REST_Response( array( 'status' => 'error', 'message' => __( 'Nothing to revoke.', 'notificationx' ) ), 400 );
        }

        return new \WP_REST_Response( array( 'status' => 'success' ), 200 );
    }

    /**
     * Management permission: administrators only.
     *
     * @return bool
     */
    public function admin_permission() {
        return current_user_can( 'manage_options' );
    }

    /**
     * MCP transport handler (REST).
     *
     * @param \WP_REST_Request $request Request.
     * @return \WP_REST_Response
     */
    public function rest_mcp( $request ) {
        return Server::get_instance()->handle( $request );
    }

    /**
     * OAuth dynamic client registration handler.
     *
     * @param \WP_REST_Request $request Request.
     * @return \WP_REST_Response|\WP_Error
     */
    public function rest_oauth_register( $request ) {
        if ( ! $this->is_enabled() ) {
            return new \WP_REST_Response( array( 'error' => 'mcp_disabled' ), 403 );
        }
        $result = OAuth::get_instance()->register_client( $request->get_json_params() ?: array() );
        if ( is_wp_error( $result ) ) {
            return new \WP_REST_Response( array( 'error' => $result->get_error_code(), 'error_description' => $result->get_error_message() ), 400 );
        }
        return new \WP_REST_Response( $result, 201 );
    }

    /**
     * OAuth token handler.
     *
     * @param \WP_REST_Request $request Request.
     * @return \WP_REST_Response
     */
    public function rest_oauth_token( $request ) {
        if ( ! $this->is_enabled() ) {
            return new \WP_REST_Response( array( 'error' => 'mcp_disabled' ), 403 );
        }
        // Token requests are form-encoded per OAuth; fall back to JSON.
        $params = $request->get_body_params();
        if ( empty( $params ) ) {
            $params = $request->get_json_params() ?: array();
        }
        $result = OAuth::get_instance()->handle_token_request( $params );
        if ( is_wp_error( $result ) ) {
            $resp = new \WP_REST_Response( array( 'error' => $result->get_error_code(), 'error_description' => $result->get_error_message() ), 400 );
        } else {
            $resp = new \WP_REST_Response( $result, 200 );
        }
        $resp->header( 'Cache-Control', 'no-store' );
        $resp->header( 'Pragma', 'no-cache' );
        return $resp;
    }

    /**
     * Connection status for the admin UI.
     *
     * @return \WP_REST_Response
     */
    public function rest_connection() {
        // Whatever switched MCP on -- the toggle, or the settings form's Save --
        // the panel asks here for the state to display, so make sure there is a
        // token to hand back rather than reporting an empty one until a reload.
        $this->ensure_paired();

        return new \WP_REST_Response( $this->connection_state(), 200 );
    }

    /**
     * Enable a pairing connection.
     *
     * @return \WP_REST_Response
     */
    public function rest_connect() {
        Pairing::get_instance()->connect();
        return new \WP_REST_Response( array( 'status' => 'success' ) + $this->connection_state(), 200 );
    }

    /**
     * Rotate the pairing token.
     *
     * @return \WP_REST_Response
     */
    public function rest_rotate() {
        Pairing::get_instance()->rotate();
        return new \WP_REST_Response( array( 'status' => 'success' ) + $this->connection_state(), 200 );
    }

    /**
     * Disconnect: drop the pairing token and revoke all OAuth grants.
     *
     * @return \WP_REST_Response
     */
    public function rest_disconnect() {
        Pairing::get_instance()->disconnect();
        OAuth::get_instance()->revoke_all();
        return new \WP_REST_Response( array( 'status' => 'success' ), 200 );
    }

    /**
     * Run the loopback self-test.
     *
     * @return \WP_REST_Response
     */
    public function rest_self_test() {
        // A token is normally minted while the settings tab is built, which only
        // happens on a server-rendered request. Saving from the admin app never
        // rebuilds it, so a test run straight after switching MCP on used to
        // report "no connection token has been generated yet" until the page was
        // reloaded. Mint here too, so the test reflects the saved state.
        $this->ensure_paired();

        $result = SelfTest::get_instance()->run();
        return new \WP_REST_Response( array( 'status' => $result['ok'] ? 'success' : 'error', 'message' => $result['message'] ) + $result, 200 );
    }

    /**
     * Turn MCP access on or off.
     *
     * Writes only `settings.enable_mcp`: the settings endpoint replaces the whole
     * blob with the posted one, so persisting the toggle through there would
     * require the admin app to post every other setting alongside it.
     *
     * @param \WP_REST_Request $request Incoming request.
     * @return \WP_REST_Response
     */
    public function rest_set_enabled( $request ) {
        $enabled = (bool) $request->get_param( 'enabled' );

        $settings = Settings::get_instance()->get( 'settings' );
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }
        $settings['enable_mcp'] = $enabled;
        Settings::get_instance()->set( 'settings', $settings );

        // Same courtesy the settings tab does: switching on should leave the UI
        // with a token to show and a connection that can actually be tested.
        $this->ensure_paired();

        return new \WP_REST_Response(
            array( 'status' => 'success' ) + $this->connection_state(),
            200
        );
    }

    /**
     * Mint a pairing token if MCP is on and none exists yet. Idempotent, and a
     * no-op while MCP is off so turning it off never creates credentials.
     *
     * @return void
     */
    protected function ensure_paired() {
        if ( ! $this->is_enabled() ) {
            return;
        }
        $pairing = Pairing::get_instance();
        if ( ! $pairing->is_connected() ) {
            $pairing->connect();
        }
    }

    /**
     * Summarise the connection for the admin UI.
     *
     * @return array
     */
    protected function connection_state() {
        $pairing = Pairing::get_instance();
        return array(
            'enabled'       => $this->is_enabled(),
            'connected'     => $pairing->is_connected(),
            'connector_url' => $this->connector_url(),
            'token'         => $pairing->site_token(),
        );
    }

    /* --------------------------------------------------------------------- */
    /* Front-end requests: pretty endpoint, discovery, authorize page        */
    /* --------------------------------------------------------------------- */

    /**
     * Intercept the MCP pretty endpoint, OAuth discovery docs and the
     * authorize page from the front controller. Path-based so it works under
     * any permalink structure without rewrite flushes.
     *
     * @param \WP $wp WordPress environment.
     * @return void
     */
    public function handle_front_requests( $wp ) {
        $path = $this->request_path();
        if ( '' === $path ) {
            return;
        }

        // OAuth discovery (also accept the path-suffixed RFC form). Only our
        // own documents are served, and only while MCP is switched on: another
        // MCP plugin on the same site owns `.well-known/...`/<its-endpoint>,
        // and answering that with our metadata would point its clients at our
        // authorization server. With MCP off we own no resource to describe, so
        // the request falls through to WordPress instead.
        if ( $this->is_enabled() ) {
            if ( $this->owns_discovery_path( $path, 'oauth-authorization-server' ) ) {
                $this->emit_json( OAuth::get_instance()->authorization_server_metadata() );
            }
            if ( $this->owns_discovery_path( $path, 'oauth-protected-resource' ) ) {
                $this->emit_json( OAuth::get_instance()->protected_resource_metadata() );
            }
        }

        // Pretty MCP endpoint.
        if ( self::ENDPOINT_PATH === $path ) {
            $this->handle_pretty_mcp();
        }

        // OAuth authorize consent page.
        if ( 'notificationx/authorize' === $path ) {
            $this->handle_authorize();
        }
    }

    /**
     * Handle the pretty MCP endpoint by delegating to the JSON-RPC server.
     *
     * @return void
     */
    protected function handle_pretty_mcp() {
        // Only POST carries a JSON-RPC body; a GET is treated as a probe so
        // clients discovering the endpoint still get a challenge.
        $request = new \WP_REST_Request( 'POST', '/notificationx/v1/mcp' );
        $auth    = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- header validated downstream.
        if ( $auth ) {
            $request->set_header( 'authorization', $auth );
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- raw JSON-RPC body, parsed/validated by the server.
        $request->set_body( file_get_contents( 'php://input' ) );

        $response = Server::get_instance()->handle( $request );
        $this->emit_rest_response( $response );
    }

    /**
     * Render / process the OAuth authorize consent page.
     *
     * @return void
     */
    protected function handle_authorize() {
        if ( ! $this->is_enabled() ) {
            status_header( 404 );
            exit;
        }

        // Require a logged-in administrator; bounce through wp-login if needed.
        if ( ! is_user_logged_in() ) {
            $current = ( is_ssl() ? 'https://' : 'http://' ) . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) ) . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
            wp_safe_redirect( wp_login_url( $current ) );
            exit;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to authorize an MCP connection.', 'notificationx' ) );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- these are OAuth request params echoed back into a nonce-protected consent form; no state change on GET.
        $params  = wp_unslash( $_GET );
        $request = OAuth::get_instance()->validate_authorize_request( $params );
        if ( is_wp_error( $request ) ) {
            wp_die( esc_html( $request->get_error_message() ) );
        }

        $is_post = ( 'POST' === strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) );

        // Deny on POST (nonce-checked): bounce back to the client with the
        // standard OAuth error so it can end the flow cleanly instead of the
        // user landing on a dead browser tab.
        if ( $is_post && isset( $_POST['nx_mcp_deny'] ) ) {
            check_admin_referer( 'nx_mcp_authorize' );
            $redirect = add_query_arg(
                array(
                    'error'             => 'access_denied',
                    'error_description' => rawurlencode( 'The user denied the authorization request.' ),
                    'state'             => rawurlencode( $request['state'] ),
                ),
                $request['redirect_uri']
            );
            wp_redirect( $redirect ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- redirect_uri is validated against the registered client allow-list.
            exit;
        }

        // Approve on POST (nonce-checked).
        if ( $is_post && isset( $_POST['nx_mcp_authorize'] ) ) {
            check_admin_referer( 'nx_mcp_authorize' );
            $code     = OAuth::get_instance()->issue_code( $request, get_current_user_id() );
            $redirect = add_query_arg(
                array(
                    'code'  => rawurlencode( $code ),
                    'state' => rawurlencode( $request['state'] ),
                ),
                $request['redirect_uri']
            );
            wp_redirect( $redirect ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- redirect_uri is validated against the registered client allow-list.
            exit;
        }

        $this->render_authorize_page( $request );
    }

    /**
     * The brand mark for a connecting client.
     *
     * Clients arrive through open dynamic registration, so the name is whatever
     * the app sent and anyone can call themselves "Claude". A vendor mark is
     * therefore only shown when the name matches AND the code is being sent
     * back to a host that vendor controls; everything else (including loopback
     * redirects used by desktop apps) falls back to the initial. The files are
     * the same ones the Connect a client panel uses, so the consent screen and
     * the admin panel can never show different marks for the same app.
     *
     * @param string $name         Registered client name.
     * @param string $redirect_uri Validated redirect URI of this authorize request.
     * @return array{file:string,tint:string}|array Empty when unrecognised.
     */
    protected static function client_brand( $name, $redirect_uri ) {
        $brands = array(
            'claude'  => array( 'file' => 'claude.svg',  'tint' => '#fdf1ec', 'hosts' => array( 'claude.ai', 'claude.com', 'anthropic.com' ) ),
            'chatgpt' => array( 'file' => 'chatgpt.svg', 'tint' => '#eaf6f2', 'hosts' => array( 'chatgpt.com', 'openai.com' ) ),
            'openai'  => array( 'file' => 'chatgpt.svg', 'tint' => '#eaf6f2', 'hosts' => array( 'chatgpt.com', 'openai.com' ) ),
            'cursor'  => array( 'file' => 'cursor.svg',  'tint' => '#eceaf6', 'hosts' => array( 'cursor.com', 'cursor.sh' ) ),
        );
        $host   = strtolower( (string) wp_parse_url( (string) $redirect_uri, PHP_URL_HOST ) );
        $scheme = strtolower( (string) wp_parse_url( (string) $redirect_uri, PHP_URL_SCHEME ) );
        if ( '' === $host || 'https' !== $scheme ) {
            return array();
        }
        foreach ( $brands as $needle => $brand ) {
            if ( false === stripos( (string) $name, $needle ) ) {
                continue;
            }
            foreach ( $brand['hosts'] as $vendor_host ) {
                if ( $host === $vendor_host || substr( $host, -strlen( '.' . $vendor_host ) ) === '.' . $vendor_host ) {
                    return array(
                        'file' => $brand['file'],
                        'tint' => $brand['tint'],
                    );
                }
            }
        }
        return array();
    }

    /**
     * Output the consent form.
     *
     * @param array $request Validated authorize request.
     * @return void
     */
    protected function render_authorize_page( $request ) {
        $store  = get_option( OAuth::OPTION, array() );
        $client = isset( $store['clients'][ $request['client_id'] ] ) ? $store['clients'][ $request['client_id'] ] : array();
        $name   = ! empty( $client['client_name'] ) ? $client['client_name'] : $request['client_id'];
        $scope  = $request['scope'];

        // What the granted scope actually permits, in plain language.
        $read_only = OAuth::get_instance()->scope_is_read_only( $scope );

        // The two ends of the connection: the client app and this site.
        $client_host = (string) wp_parse_url( $request['redirect_uri'], PHP_URL_HOST );
        $site_name   = get_bloginfo( 'name' );
        $site_host   = (string) wp_parse_url( home_url(), PHP_URL_HOST );

        // Who is about to approve — everything the connection does is recorded
        // as this user.
        $user      = wp_get_current_user();
        $who_name  = $user->display_name ? $user->display_name : $user->user_login;
        $roles     = (array) $user->roles;
        $role_key  = $roles ? (string) reset( $roles ) : '';
        $role_lbl  = '';
        if ( $role_key ) {
            $wp_roles = wp_roles();
            if ( isset( $wp_roles->roles[ $role_key ]['name'] ) ) {
                $role_lbl = translate_user_role( $wp_roles->roles[ $role_key ]['name'] );
            }
        }
        $substr         = function_exists( 'mb_substr' ) ? 'mb_substr' : 'substr';
        $who_initial    = strtoupper( $substr( $who_name, 0, 1 ) );
        $client_initial = strtoupper( $substr( $name, 0, 1 ) );
        // Show the connecting app's own mark only when we can vouch for it; otherwise the initial.
        $client_brand = self::client_brand( $name, $request['redirect_uri'] );

        // The exact tools this grant unlocks, straight from the ability
        // registry so the list can never drift from what the server exposes.
        Registrar::get_instance()->boot();
        $granted = array();
        foreach ( Registrar::get_instance()->get_all() as $ability ) {
            if ( $read_only && $ability->is_write() ) {
                continue;
            }
            $granted[] = $ability;
        }

        $cap_label = $read_only ? __( 'Read only', 'notificationx' ) : __( 'Read & write', 'notificationx' );
        $cap_text  = $read_only
            ? __( 'It can read your notifications, entries and analytics. It cannot create, change or delete anything.', 'notificationx' )
            : __( 'It acts as you: anything it creates, edits or deletes is recorded under your account.', 'notificationx' );

        // NotificationX brand mark (assets/admin/images/nx-icon.svg), inlined so
        // the consent page never depends on a second asset request.
        $nx_mark = '<svg viewBox="0 0 387 392" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><g fill="none" fill-rule="evenodd"><g fill-rule="nonzero"><path d="m135.45 358.68h113.62c-2.05 13.15-27.83 29.91-49.81 32.3-25.34 2.75-56.03-12.6-63.81-32.3z" fill="#5614d5"/><path d="m372.31 305.79c-2.34-.2-4.71-.08-7.07-.08-5.61-.01-11.22 0-18.16 0 0-4.28 0-7.29 0-10.3-.01-46.66.17-93.32-.17-139.98-.08-10.54-1.03-21.24-3.12-31.56-17.4-85.97-103.85-140.06-188.98-118.65-67.97 17.09-116.9 79.04-116.62 149.48.17 42.42.02 84.84.01 127.26 0 3.84-.02 15.83-.04 23.74-5.18-.04-20.09-.13-25.3.18-7.73.45-12.92 6.43-12.82 14.09.1 7.46 5.04 12.77 12.63 13.45 2.11.19 4.24.15 6.36.15 115.71.04 231.43.07 347.14.09 2.12 0 4.25.03 6.36-.18 7.48-.75 12.61-6.25 12.75-13.53.13-7.37-5.42-13.51-12.97-14.16z" fill="#5614d5"/><g fill="#836eff"><circle cx="281.55" cy="255.92" r="15.49"/><path d="m295.67 140.1.24-.16c-.21-1.31-.39-2.65-.64-3.92-9.4-46.45-49.44-80.68-96.48-83.49-.06 0-.12-.01-.18-.01-2.02-.12-4.04-.2-6.08-.2-.05 0-.09 0-.14 0s-.09 0-.14 0c-2.04 0-4.07.08-6.08.2-.06 0-.12.01-.18.01-47.04 2.81-87.08 37.04-96.48 83.49-.26 1.27-.44 2.61-.64 3.92l.24.16c-.91 5.5-1.39 11.12-1.37 16.8.02 4.52.03 99.87.04 112.84l32.13 34.68c0-24.28-.01-133.85-.06-147.64-.13-32.6 22.96-62.09 54.91-70.12 2.65-.67 5.33-1.16 8.02-1.53.45-.06.89-.13 1.35-.18 1.02-.12 2.04-.21 3.05-.29 1.46-.1 2.92-.18 4.4-.19.27 0 .54-.02.81-.03.27 0 .54.02.81.03 1.48.01 2.94.09 4.4.19 1.02.08 2.04.17 3.05.29.45.05.9.12 1.35.18 2.69.37 5.37.86 8.02 1.53 31.94 8.03 55.04 37.53 54.91 70.12-.02 5.17-.03 50.29-.04 71.4l32.14-21.45c0-12.23.01-48.45.01-49.82.02-5.7-.45-11.31-1.37-16.81z"/></g></g><path d="m31.94 305.72c-6.36.13-12.74-.21-19.08.16-7.73.45-12.92 6.43-12.82 14.09.1 7.46 5.04 12.77 12.63 13.45 2.11.19 4.24.15 6.36.15 115.71.04 231.42.06 347.14.09 2.12 0 4.25.03 6.36-.18 7.48-.75 12.61-6.25 12.75-13.53.14-7.37-5.41-13.5-12.96-14.16-2.34-.2-4.71-.08-7.07-.08-5.61-.01-11.22 0-18.16 0 0-4.28 0-7.29 0-10.3-.01-40.67.11-81.34-.08-122l-215.39 143.62-78.04-84.22 33.47-30.79 51.67 55.6 204.48-136.36c-18.61-84.45-104.12-137.24-188.38-116.05-67.97 17.09-116.9 79.04-116.62 149.48.17 42.42.02 84.84.01 127.26 0 5.89.09 11.79-.05 17.67"/><path d="m346.91 155.42c.04 5.99.06 11.99.09 17.98l39.14-25.99-25.24-37.84-17.7 11.69c.19.87.42 1.72.6 2.59 2.08 10.33 3.04 21.04 3.11 31.57z" fill="#00f9ac" fill-rule="nonzero"/><path d="m87.05 202.03-33.47 30.79 78.04 84.22 215.38-143.63c-.03-5.99-.04-11.99-.09-17.98-.08-10.54-1.03-21.24-3.12-31.56-.18-.88-.4-1.73-.6-2.59l-204.47 136.35z"/><path d="m87.05 202.03-33.47 30.79 78.04 84.22 215.38-143.63c-.03-5.99-.04-11.99-.09-17.98-.08-10.54-1.03-21.24-3.12-31.56-.18-.88-.4-1.73-.6-2.59l-204.47 136.35z" fill="#21d8a3" fill-rule="nonzero" opacity=".9"/></g></svg>';

        nocache_headers();
        header( 'Content-Type: text/html; charset=utf-8' );
        ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php esc_html_e( 'Authorize MCP connection', 'notificationx' ); ?></title>
    <style>
        :root{--nx:#6a4bff;--nx-dark:#5614d5;--ink:#1a1a2e;--muted:#5b6072;--line:#e7e7ef}
        *{box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;color:var(--ink);background:#f4f3fb;background:radial-gradient(1200px 600px at 50% -10%,#efe9ff 0%,#f4f3fb 45%,#f4f3fb 100%)}
        .card{background:#fff;max-width:480px;width:100%;padding:32px 32px 28px;border-radius:20px;border:1px solid var(--line);box-shadow:0 18px 50px rgba(38,20,120,.10)}
        .apps{display:flex;align-items:flex-start;justify-content:center;gap:8px;margin:4px 0 22px}
        .app{width:132px;text-align:center}
        .tile{width:64px;height:64px;margin:0 auto 10px;border-radius:16px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(30,20,80,.10)}
        .tile.client{background:#eef0f6;color:#3a4056;font-size:26px;font-weight:700}
        .tile.client svg,.tile.client img{width:38px;height:38px;display:block}
        .tile.nx{background:#fff;border:1px solid var(--line)}
        .tile.nx svg{width:42px;height:42px;display:block}
        .app-name{font-size:14px;font-weight:600;line-height:1.3}
        .app-host{font-size:12px;color:var(--muted);word-break:break-word;margin-top:2px}
        .conn{flex:0 0 auto;align-self:center;margin-top:8px;display:flex;align-items:center;gap:6px;color:#b7b9c9}
        .conn i{display:block;width:14px;height:0;border-top:2px dotted currentColor}
        .conn .dot{width:26px;height:26px;border-radius:50%;border:1px solid var(--line);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:13px;background:#fff}
        h1{font-size:19px;line-height:1.45;margin:0 0 20px;text-align:center;font-weight:600}
        h1 strong{font-weight:700}
        .cap{border-radius:14px;padding:16px 16px 14px;border:1px solid #e4defb;background:#f6f3ff}
        .cap.ro{border-color:#dfe6f2;background:#f2f6fc}
        .pill{display:inline-block;font-size:12px;font-weight:700;padding:5px 12px;border-radius:999px;background:var(--nx);color:#fff}
        .cap.ro .pill{background:#3f6fd6}
        .cap p{margin:11px 0 0;font-size:13px;line-height:1.55;color:#403c5c}
        .who{display:flex;align-items:center;gap:10px;margin:16px 2px 0;font-size:13px;color:var(--muted)}
        .avatar{width:30px;height:30px;border-radius:50%;background:#eef0f6;color:#3a4056;font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center;flex:0 0 auto}
        .who b{color:var(--ink)}
        details{margin-top:14px;border:1px solid var(--line);border-radius:12px;overflow:hidden}
        summary{list-style:none;cursor:pointer;padding:13px 15px;font-size:14px;font-weight:600;display:flex;align-items:center;justify-content:space-between}
        summary::-webkit-details-marker{display:none}
        summary .chev{transition:transform .15s ease;color:var(--muted)}
        details[open] summary .chev{transform:rotate(180deg)}
        .abilities{margin:0;padding:2px 6px 8px;list-style:none}
        .abilities li{padding:9px 9px;border-top:1px solid var(--line)}
        .abilities .a-name{font-size:13px;font-weight:600}
        .abilities .a-desc{font-size:12px;color:var(--muted);margin-top:2px;line-height:1.45}
        .secured{display:flex;align-items:flex-start;gap:8px;margin:16px 2px 0;font-size:12px;color:var(--muted);line-height:1.5}
        .secured svg{flex:0 0 auto;margin-top:1px}
        .actions{display:flex;gap:12px;margin-top:22px}
        button{flex:1;padding:13px;border-radius:11px;font-size:14px;font-weight:700;cursor:pointer;border:1px solid transparent}
        .approve{background:var(--nx);color:#fff}
        .approve:hover{background:var(--nx-dark)}
        .deny{background:#fff;color:var(--ink);border-color:var(--line)}
        .deny:hover{background:#f6f6fa}
    </style>
</head>
<body>
    <div class="card">
        <div class="apps">
            <div class="app">
                <div class="tile client<?php echo $client_brand ? ' has-mark' : ''; ?>"<?php echo $client_brand ? ' style="background:' . esc_attr( $client_brand['tint'] ) . '"' : ''; ?>>
                    <?php if ( $client_brand ) : ?>
                        <img src="<?php echo esc_url( NOTIFICATIONX_ADMIN_URL . 'images/mcp/' . $client_brand['file'] ); ?>" alt="" width="38" height="38" />
                    <?php else : ?>
                        <?php echo esc_html( $client_initial ); ?>
                    <?php endif; ?>
                </div>
                <div class="app-name"><?php echo esc_html( $name ); ?></div>
                <?php if ( $client_host ) : ?><div class="app-host"><?php echo esc_html( $client_host ); ?></div><?php endif; ?>
            </div>
            <div class="conn" aria-hidden="true"><i></i><span class="dot">&rarr;</span><i></i></div>
            <div class="app">
                <div class="tile nx"><?php echo $nx_mark; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline brand SVG, no dynamic data. ?></div>
                <div class="app-name">NotificationX</div>
                <?php if ( $site_host ) : ?><div class="app-host"><?php echo esc_html( $site_host ); ?></div><?php endif; ?>
            </div>
        </div>

        <h1>
            <?php
            printf(
                /* translators: %1$s: client app name, %2$s: site name. */
                esc_html__( '%1$s wants to work with your notifications on %2$s.', 'notificationx' ),
                '<strong>' . esc_html( $name ) . '</strong>',
                '<strong>' . esc_html( $site_name ? $site_name : $site_host ) . '</strong>'
            );
            ?>
        </h1>

        <div class="cap <?php echo $read_only ? 'ro' : ''; ?>">
            <span class="pill"><?php echo esc_html( $cap_label ); ?></span>
            <p><?php echo esc_html( $cap_text ); ?></p>
        </div>

        <div class="who">
            <span class="avatar"><?php echo esc_html( $who_initial ); ?></span>
            <span>
                <?php
                printf(
                    /* translators: %1$s: user display name, %2$s: user role. */
                    esc_html__( 'Signed in as %1$s%2$s', 'notificationx' ),
                    '<b>' . esc_html( $who_name ) . '</b>',
                    $role_lbl ? ' &middot; ' . esc_html( $role_lbl ) : ''
                );
                ?>
            </span>
        </div>

        <?php if ( $granted ) : ?>
        <details>
            <summary>
                <span>
                    <?php
                    printf(
                        /* translators: %1$s: client app name, %2$d: number of tools. */
                        esc_html__( 'What %1$s will be able to do (%2$d)', 'notificationx' ),
                        esc_html( $name ),
                        count( $granted )
                    );
                    ?>
                </span>
                <span class="chev">&#9662;</span>
            </summary>
            <ul class="abilities">
                <?php foreach ( $granted as $ability ) : ?>
                <li>
                    <div class="a-name"><?php echo esc_html( $ability->get_label() ); ?></div>
                    <div class="a-desc"><?php echo esc_html( $ability->get_description() ); ?></div>
                </li>
                <?php endforeach; ?>
            </ul>
        </details>
        <?php endif; ?>

        <div class="secured">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <span>
                <?php esc_html_e( 'Secured with OAuth. You can revoke this app at any time under NotificationX → MCP.', 'notificationx' ); ?>
            </span>
        </div>

        <form method="post">
            <?php wp_nonce_field( 'nx_mcp_authorize' ); ?>
            <div class="actions">
                <button type="submit" class="deny" name="nx_mcp_deny" value="1"><?php esc_html_e( 'Deny', 'notificationx' ); ?></button>
                <button type="submit" class="approve" name="nx_mcp_authorize" value="1"><?php esc_html_e( 'Approve', 'notificationx' ); ?></button>
            </div>
        </form>
    </div>
</body>
</html>
        <?php
        exit;
    }

    /* --------------------------------------------------------------------- */
    /* Settings tab (NotificationX admin flow)                               */
    /* --------------------------------------------------------------------- */

    /**
     * Add the "MCP" tab to NotificationX settings.
     *
     * @param array $tabs Existing tabs.
     * @return array
     */
    public function register_settings_tab( $tabs ) {
        // No token is minted here. This runs for every user who can open the
        // NotificationX admin (and for GET /builder), so minting here bound the
        // token to whoever loaded a page first, including users who cannot use
        // it. The manage_options routes (enable, connection, self-test) mint it.

        // The panel prints the connection token, which acts as the administrator
        // who paired it. Settings access can be delegated to other roles (Role
        // Management), and every MCP route already requires manage_options, so
        // the tab is not shown to anyone who could not use those routes.
        if ( ! current_user_can( 'manage_options' ) ) {
            return $tabs;
        }

        $tabs['tab-mcp'] = array(
            'id'       => 'tab-mcp',
            'label'    => __( 'MCP', 'notificationx' ),
            'priority' => 45,
            'fields'   => $this->settings_fields(),
        );

        return $tabs;
    }

    /**
     * Keep `enable_mcp` out of reach of users who cannot manage MCP.
     *
     * The settings form posts the whole blob, so without this a user with
     * settings access but without manage_options could switch MCP on or off,
     * although every MCP management route requires manage_options.
     *
     * @param array $keys Protected settings keys.
     * @return array
     */
    public function protect_enable_setting( $keys ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            $keys   = (array) $keys;
            $keys[] = 'enable_mcp';
        }
        return $keys;
    }

    /**
     * Build the MCP settings field schema. The rich panels are server-rendered
     * HTML delivered through quickbuilder `message` fields (html => true); the
     * action buttons are plain buttons wired to the globals printed by
     * {@see print_panel_assets()}.
     *
     * Section order follows the reading order of someone who has never used the
     * feature: what it is (hero), what it would give them (capabilities), and
     * only then the plumbing. The capability section deliberately carries no
     * `rules`, so the one screen that answers "why would I turn this on?" is
     * also visible while the feature is still off — the rest stays hidden until
     * it has something real to show.
     *
     * @return array
     */
    protected function settings_fields() {
        $enabled_rule = Rules::is( 'enable_mcp', true );

        $fields = array(
            'mcp_main_section' => array(
                'name'   => 'mcp_main_section',
                'type'   => 'section',
                'label'  => __( 'MCP Server', 'notificationx' ),
                'fields' => array(
                    'mcp_hero' => array(
                        'name'    => 'mcp_hero',
                        'type'    => 'message',
                        'html'    => true,
                        'classes' => 'nx-mcp-field nx-mcp-field-flush',
                        'message' => $this->hero_html(),
                    ),
                    'enable_mcp' => array(
                        'name'    => 'enable_mcp',
                        'type'    => 'toggle',
                        'default' => false,
                        'label'   => __( 'Enable MCP access', 'notificationx' ),
                        'help'    => __( 'When enabled, approved AI assistants can connect to this site to manage notifications and read analytics.', 'notificationx' ),
                    ),
                    'mcp_stats' => array(
                        'name'    => 'mcp_stats',
                        'type'    => 'message',
                        'html'    => true,
                        'classes' => 'nx-mcp-field',
                        'rules'   => $enabled_rule,
                        'message' => $this->stats_html(),
                    ),
                ),
            ),

            'mcp_connection_section' => array(
                'name'   => 'mcp_connection_section',
                'type'   => 'section',
                'label'  => __( 'Connection', 'notificationx' ),
                'rules'  => $enabled_rule,
                'fields' => array(
                    'mcp_connection_html' => array(
                        'name'    => 'mcp_connection_html',
                        'type'    => 'message',
                        'html'    => true,
                        'classes' => 'nx-mcp-field',
                        'message' => $this->connection_html(),
                    ),
                ),
            ),

            'mcp_clients_section' => array(
                'name'   => 'mcp_clients_section',
                'type'   => 'section',
                'label'  => __( 'Connect a client', 'notificationx' ),
                'rules'  => $enabled_rule,
                'fields' => array(
                    'mcp_clients_html' => array(
                        'name'    => 'mcp_clients_html',
                        'type'    => 'message',
                        'html'    => true,
                        'classes' => 'nx-mcp-field',
                        'message' => $this->clients_html(),
                    ),
                ),
            ),

            'mcp_apps_section' => array(
                'name'   => 'mcp_apps_section',
                'type'   => 'section',
                'label'  => __( 'Connected apps', 'notificationx' ),
                'rules'  => $enabled_rule,
                'fields' => array(
                    'mcp_apps_html' => array(
                        'name'    => 'mcp_apps_html',
                        'type'    => 'message',
                        'html'    => true,
                        'classes' => 'nx-mcp-field',
                        'message' => $this->connected_apps_html(),
                    ),
                ),
            ),

            'mcp_health_section' => array(
                'name'   => 'mcp_health_section',
                'type'   => 'section',
                'label'  => __( 'Connection health', 'notificationx' ),
                'rules'  => $enabled_rule,
                'fields' => array(
                    'mcp_health_html' => array(
                        'name'    => 'mcp_health_html',
                        'type'    => 'message',
                        'html'    => true,
                        'classes' => 'nx-mcp-field',
                        'message' => $this->health_html(),
                    ),
                ),
            ),
        );

        return $fields;
    }

    /**
     * Current status: off | setup | active.
     *
     * @return array [ state, label ]
     */
    protected function status() {
        if ( ! $this->is_enabled() ) {
            return array( 'off', __( 'Off', 'notificationx' ) );
        }
        if ( Pairing::get_instance()->is_connected() ) {
            return array( 'active', __( 'Active', 'notificationx' ) );
        }
        return array( 'setup', __( 'Setup needed', 'notificationx' ) );
    }

    /**
     * The abilities currently registered, split the way the panel reads them.
     *
     * Read straight from the registry rather than a hand-kept list, so the tab
     * can never claim a tool the server does not actually expose — and so Pro's
     * abilities appear the moment Pro adds them through `nx_register_abilities`
     * with no change here. `boot()` is idempotent, and calling it is what makes
     * this safe to render on a request where nothing else has touched the
     * registry yet.
     *
     * @return array { read: array[], write: array[] } each row: label, tool, pro.
     */
    protected function ability_rows() {
        $registrar = Registrar::get_instance();
        $registrar->boot();

        $rows = array(
            'read'  => array(),
            'write' => array(),
        );

        foreach ( $registrar->get_all() as $id => $ability ) {
            $row = array(
                'label' => $ability->get_label(),
                'tool'  => $ability->tool_name(),
                'pro'   => ( 0 === strpos( (string) $id, 'notificationx-pro/' ) ),
            );

            $rows[ $ability->is_write() ? 'write' : 'read' ][] = $row;
        }

        return $rows;
    }

    /**
     * The three setup steps shown as a static how-to in the hero.
     *
     * @return array[] Each: icon, label, hint.
     */
    protected function setup_steps() {
        return array(
            array(
                'icon'  => 'icon-step-power',
                'label' => __( 'Turn MCP on', 'notificationx' ),
                'hint'  => __( 'Flip the switch below.', 'notificationx' ),
            ),
            array(
                'icon'  => 'icon-step-copy',
                'label' => __( 'Copy your connector', 'notificationx' ),
                'hint'  => __( 'One URL, and a token for clients that need one.', 'notificationx' ),
            ),
            array(
                'icon'  => 'icon-step-approve',
                'label' => __( 'Approve the client', 'notificationx' ),
                'hint'  => __( 'Add it in Claude, ChatGPT or Cursor and confirm.', 'notificationx' ),
            ),
        );
    }

    /**
     * Path to one of the tab's own icon files.
     *
     * The panel HTML is rendered into the settings app through a `message`
     * field, where an inline `<svg>` does not survive: icons are therefore real
     * files referenced with `<img>`, never markup and never a `data:` URI.
     *
     * @param string $name File name, without extension.
     * @return string
     */
    protected function icon_url( $name ) {
        return NOTIFICATIONX_ADMIN_URL . 'images/mcp/' . $name . '.svg';
    }

    /**
     * Hero header: what the feature is, where the site currently stands, and
     * the three steps between here and a working connection.
     *
     * @return string
     */
    protected function hero_html() {
        list( $state, $label ) = $this->status();
        $steps = $this->setup_steps();
        ob_start();
        ?>
        <div class="nx-mcp-hero nx-mcp-hero-<?php echo esc_attr( $state ); ?>">
            <div class="nx-mcp-hero-main">
                <span class="nx-mcp-hero-tile">
                    <img class="nx-mcp-hero-tile-ic" width="24" height="24" alt="" src="<?php echo esc_url( $this->icon_url( 'icon-mcp' ) ); ?>" />
                </span>
                <div class="nx-mcp-hero-body">
                    <h3 class="nx-mcp-hero-title">
                        <?php esc_html_e( 'Run NotificationX from your AI assistant', 'notificationx' ); ?>
                        <span class="nx-mcp-badge nx-mcp-badge-<?php echo esc_attr( $state ); ?>">
                            <span class="nx-mcp-badge-dot" aria-hidden="true"></span>
                            <span class="nx-mcp-badge-text"><?php echo esc_html( $label ); ?></span>
                        </span>
                    </h3>
                    <p class="nx-mcp-hero-text">
                        <?php esc_html_e( 'A built-in MCP server lets Claude, ChatGPT, Cursor and other assistants build campaigns, flip notifications on or off and read your analytics — in plain language, without leaving the chat. It stays off until you switch it on, and only administrators can connect.', 'notificationx' ); ?>
                    </p>
                    <a class="nx-mcp-learn" href="<?php echo esc_url( 'https://notificationx.com/docs/mcp-in-notificationx' ); ?>" target="_blank" rel="noopener noreferrer">
                        <span class="nx-mcp-learn-text"><?php esc_html_e( 'Learn how it works', 'notificationx' ); ?></span>
                        <span class="nx-mcp-learn-arrow" aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </div>
            <ol class="nx-mcp-rail">
                <?php foreach ( $steps as $step ) : ?>
                    <li class="nx-mcp-rail-step">
                        <span class="nx-mcp-rail-mark" aria-hidden="true">
                            <img class="nx-mcp-rail-ic" width="16" height="16" alt="" src="<?php echo esc_url( $this->icon_url( $step['icon'] ) ); ?>" />
                        </span>
                        <span class="nx-mcp-rail-body">
                            <strong class="nx-mcp-rail-label"><?php echo esc_html( $step['label'] ); ?></strong>
                            <span class="nx-mcp-rail-hint"><?php echo esc_html( $step['hint'] ); ?></span>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * The four headline numbers, each one read from state the page already has.
     *
     * There is deliberately no trend line or delta anywhere on this row: the
     * server keeps a single `last_used` stamp and no history at all, so a trend
     * here could only be invented.
     *
     * @return string
     */
    protected function stats_html() {
        list( $state, $status_label ) = $this->status();

        $pairing      = Pairing::get_instance();
        $pstate       = $pairing->state();
        $connected_at = ! empty( $pstate['connected_at'] ) ? (int) $pstate['connected_at'] : 0;
        $last_used    = ! empty( $pstate['last_used'] ) ? (int) $pstate['last_used'] : 0;

        $rows       = $this->ability_rows();
        $tool_count = count( $rows['read'] ) + count( $rows['write'] );
        $pro_count  = 0;
        foreach ( array_merge( $rows['read'], $rows['write'] ) as $row ) {
            if ( $row['pro'] ) {
                ++$pro_count;
            }
        }

        $apps = count( $this->get_connected_apps() );

        $tiles = array(
            array(
                'key'   => 'status',
                'icon'  => 'icon-status',
                'label' => __( 'Server status', 'notificationx' ),
                'value' => $status_label,
                'small' => true,
                'note'  => $connected_at
                    /* translators: %s: the date the connection was established. */
                    ? sprintf( __( 'since %s', 'notificationx' ), date_i18n( get_option( 'date_format' ), $connected_at ) )
                    : '',
            ),
            array(
                'icon'  => 'icon-tools',
                'label' => __( 'Tools exposed', 'notificationx' ),
                'value' => number_format_i18n( $tool_count ),
                'small' => false,
                'note'  => $pro_count
                    /* translators: %s: number of Pro-only tools. */
                    ? sprintf( _n( '%s from Pro', '%s from Pro', $pro_count, 'notificationx' ), number_format_i18n( $pro_count ) )
                    : __( 'more with Pro', 'notificationx' ),
            ),
            array(
                'icon'  => 'icon-apps',
                'label' => __( 'Connected apps', 'notificationx' ),
                'value' => number_format_i18n( $apps ),
                'small' => false,
                'note'  => $apps ? '' : __( 'none yet', 'notificationx' ),
            ),
            array(
                'icon'  => 'icon-activity',
                'label' => __( 'Last activity', 'notificationx' ),
                'value' => $last_used
                    /* translators: %s: human-readable time difference, e.g. "5 mins". */
                    ? sprintf( __( '%s ago', 'notificationx' ), human_time_diff( $last_used ) )
                    : __( 'Never', 'notificationx' ),
                'small' => true,
                'note'  => '',
            ),
        );

        ob_start();
        ?>
        <div class="nx-mcp-stats nx-mcp-stats-<?php echo esc_attr( $state ); ?>">
            <?php foreach ( $tiles as $tile ) : ?>
                <div class="nx-mcp-stat">
                    <div class="nx-mcp-stat-top">
                        <span class="nx-mcp-stat-label"><?php echo esc_html( $tile['label'] ); ?></span>
                        <span class="nx-mcp-stat-ic">
                            <img width="16" height="16" alt="" src="<?php echo esc_url( $this->icon_url( $tile['icon'] ) ); ?>" />
                        </span>
                    </div>
                    <div class="nx-mcp-stat-row">
                        <span class="nx-mcp-stat-value<?php echo $tile['small'] ? ' is-sm' : ''; ?><?php echo isset( $tile['key'] ) ? ' nx-mcp-stat-' . esc_attr( $tile['key'] ) : ''; ?>"><?php echo esc_html( $tile['value'] ); ?></span>
                        <?php if ( $tile['note'] ) : ?>
                            <span class="nx-mcp-stat-note"><?php echo esc_html( $tile['note'] ); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Connector URL + token cards with copy/reveal controls.
     *
     * Always rendered, even while MCP is off: the enable toggle saves itself and
     * hands back the token, which nxMcpSetToken() writes into these cards, so
     * the panel works without a reload.
     *
     * @return string
     */
    protected function connection_html() {
        // The token is deliberately not printed here. This markup is part of the
        // settings schema, which reaches every user who can open the
        // NotificationX admin (and GET /builder), not only administrators.
        // Show/Copy fetch it from GET /mcp/connection, which requires manage_options.
        $url = $this->connector_url();
        ob_start();
        ?>
        <div class="nx-mcp-grid">
            <div class="nx-mcp-card">
                <span class="nx-mcp-card-label"><?php esc_html_e( 'Connector URL', 'notificationx' ); ?></span>
                <div class="nx-mcp-copyrow">
                    <code class="nx-mcp-value"><?php echo esc_html( $url ); ?></code>
                    <button type="button" class="nx-mcp-copy" onclick="nxMcpCopy(this,'<?php echo esc_js( $url ); ?>')"><?php esc_html_e( 'Copy', 'notificationx' ); ?></button>
                </div>
                <p class="nx-mcp-hint"><?php esc_html_e( 'Add this URL as a custom connector in your AI client.', 'notificationx' ); ?></p>
            </div>
            <div class="nx-mcp-card">
                <span class="nx-mcp-card-label"><?php esc_html_e( 'Connection token', 'notificationx' ); ?></span>
                <div class="nx-mcp-copyrow">
                    <code class="nx-mcp-value nx-mcp-token" data-token="">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</code>
                    <button type="button" class="nx-mcp-copy" onclick="nxMcpReveal(this)"><?php esc_html_e( 'Show', 'notificationx' ); ?></button>
                    <?php // Reads the token from the element rather than a value baked in at render time: the panel is built before MCP is switched on, so a literal here would stay empty until a reload. ?>
                    <button type="button" class="nx-mcp-copy" onclick="nxMcpCopyToken(this)"><?php esc_html_e( 'Copy', 'notificationx' ); ?></button>
                </div>
                <p class="nx-mcp-hint"><?php esc_html_e( 'For token-based clients (ChatGPT, Cursor): send it as an Authorization: Bearer header. Keep it secret.', 'notificationx' ); ?></p>
            </div>
        </div>
        <div class="nx-mcp-actions">
            <button type="button" class="nx-mcp-btn nx-mcp-btn-primary" onclick="nxMcpAction(this,'test',{result:'nx-mcp-testresult',success:'<?php echo esc_js( __( 'Connection test passed — the MCP server is reachable and exposing its tools.', 'notificationx' ) ); ?>'})"><?php esc_html_e( 'Test connection', 'notificationx' ); ?></button>
            <button type="button" class="nx-mcp-btn nx-mcp-btn-ghost" onclick="nxMcpAction(this,'rotate',{confirm:'<?php echo esc_js( __( 'Reset the connection token? Existing clients will need the new token to reconnect.', 'notificationx' ) ); ?>',reload:true,success:'<?php echo esc_js( __( 'A new connection token was generated.', 'notificationx' ) ); ?>'})"><?php esc_html_e( 'Reset token', 'notificationx' ); ?></button>
        </div>
        <div class="nx-mcp-result" id="nx-mcp-testresult"></div>
        <?php
        return ob_get_clean();
    }

    /**
     * Per-client setup cards.
     *
     * Each card ends in a copy control that hands over exactly what that client
     * asks for — a URL for the two that take one, and a ready-made server block
     * for the config-file clients. The token is never baked into these strings:
     * the handler reads it from the token field already on the page, so this
     * panel adds no second copy of the secret to the document.
     *
     * @return string
     */
    protected function clients_html() {
        $url = $this->connector_url();
        ob_start();
        ?>
        <div class="nx-mcp-clients">
            <div class="nx-mcp-client">
                <div class="nx-mcp-client-name">
                    <img class="nx-mcp-client-ic" width="20" height="20" alt="" src="<?php echo esc_url( NOTIFICATIONX_ADMIN_URL . 'images/mcp/claude.svg' ); ?>" />
                    <span class="nx-mcp-client-title"><?php esc_html_e( 'Claude', 'notificationx' ); ?></span>
                    <span class="nx-mcp-pill nx-mcp-pill-oauth"><?php esc_html_e( 'OAuth', 'notificationx' ); ?></span>
                </div>
                <ol class="nx-mcp-steps">
                    <li><?php esc_html_e( 'In Claude, add a custom connector.', 'notificationx' ); ?></li>
                    <li><?php esc_html_e( 'Paste the Connector URL above.', 'notificationx' ); ?></li>
                    <li><?php esc_html_e( 'Approve the connection when prompted — you sign in here, no token needed.', 'notificationx' ); ?></li>
                </ol>
                <button type="button" class="nx-mcp-copy nx-mcp-copy-wide" onclick="nxMcpCopy(this,'<?php echo esc_js( $url ); ?>')"><?php esc_html_e( 'Copy connector URL', 'notificationx' ); ?></button>
            </div>
            <div class="nx-mcp-client">
                <div class="nx-mcp-client-name">
                    <img class="nx-mcp-client-ic" width="20" height="20" alt="" src="<?php echo esc_url( NOTIFICATIONX_ADMIN_URL . 'images/mcp/chatgpt.svg' ); ?>" />
                    <span class="nx-mcp-client-title"><?php esc_html_e( 'ChatGPT', 'notificationx' ); ?></span>
                    <span class="nx-mcp-pill nx-mcp-pill-token"><?php esc_html_e( 'Token', 'notificationx' ); ?></span>
                </div>
                <ol class="nx-mcp-steps">
                    <li><?php esc_html_e( 'Settings → Connectors → Add a custom connector.', 'notificationx' ); ?></li>
                    <li><?php esc_html_e( 'Use the Connector URL above.', 'notificationx' ); ?></li>
                    <li><?php esc_html_e( 'Provide the connection token as a Bearer credential.', 'notificationx' ); ?></li>
                </ol>
                <button type="button" class="nx-mcp-copy nx-mcp-copy-wide" onclick="nxMcpCopy(this,'<?php echo esc_js( $url ); ?>')"><?php esc_html_e( 'Copy connector URL', 'notificationx' ); ?></button>
            </div>
            <div class="nx-mcp-client">
                <div class="nx-mcp-client-name">
                    <img class="nx-mcp-client-ic" width="20" height="20" alt="" src="<?php echo esc_url( NOTIFICATIONX_ADMIN_URL . 'images/mcp/cursor.svg' ); ?>" />
                    <span class="nx-mcp-client-title"><?php esc_html_e( 'Cursor &amp; others', 'notificationx' ); ?></span>
                    <span class="nx-mcp-pill nx-mcp-pill-token"><?php esc_html_e( 'Token', 'notificationx' ); ?></span>
                </div>
                <ol class="nx-mcp-steps">
                    <li><?php esc_html_e( 'Open the client’s MCP configuration file.', 'notificationx' ); ?></li>
                    <li><?php esc_html_e( 'Paste the server block below into mcpServers.', 'notificationx' ); ?></li>
                    <li><?php esc_html_e( 'Confirm the install when the client asks.', 'notificationx' ); ?></li>
                </ol>
                <button type="button" class="nx-mcp-copy nx-mcp-copy-wide nx-mcp-copy-config" data-url="<?php echo esc_attr( $url ); ?>"><?php esc_html_e( 'Copy JSON config', 'notificationx' ); ?></button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * The currently connected apps (pairing token + active OAuth clients). Shared
     * by the rendered panel and the /mcp/apps endpoint so the two cannot drift.
     *
     * @return array[] Each: type, client_id, name, read_only.
     */
    protected function get_connected_apps() {
        $apps = array();

        // Only list the token connection once a client has actually used it —
        // the token existing on its own is not a "connected app".
        $pairing = Pairing::get_instance();
        $pstate  = $pairing->state();
        if ( $pairing->is_connected() && ! empty( $pstate['last_used'] ) ) {
            $apps[] = array(
                'type'      => 'pairing',
                'client_id' => '',
                'name'      => __( 'Token connection (ChatGPT / Cursor / manual)', 'notificationx' ),
                'read_only' => $pairing->is_read_only(),
            );
        }
        foreach ( OAuth::get_instance()->list_active_clients() as $client ) {
            $apps[] = array(
                'type'      => 'oauth',
                'client_id' => $client['client_id'],
                'name'      => $client['name'],
                'read_only' => ! empty( $client['read_only'] ),
            );
        }

        return $apps;
    }

    protected function connected_apps_html() {
        $apps = $this->get_connected_apps();

        ob_start();
        ?>
        <div class="nx-mcp-apps-head">
            <span class="nx-mcp-apps-hint"><?php esc_html_e( 'Apps you have approved. Refresh to pick up a new or detached connection.', 'notificationx' ); ?></span>
            <button type="button" class="nx-mcp-btn nx-mcp-btn-ghost nx-mcp-btn-sm nx-mcp-refresh-apps"><?php esc_html_e( 'Refresh', 'notificationx' ); ?></button>
        </div>
        <div id="nx-mcp-apps-wrap">
        <?php
        if ( empty( $apps ) ) {
            echo '<p class="nx-mcp-empty">' . esc_html__( 'No AI clients are connected yet.', 'notificationx' ) . '</p>';
        } else {
            echo '<div class="nx-mcp-apps">';
            foreach ( $apps as $app ) {
                $scope_class = $app['read_only'] ? 'nx-mcp-scope-ro' : 'nx-mcp-scope-rw';
                $scope_label = $app['read_only'] ? __( 'Read-only', 'notificationx' ) : __( 'Read & write', 'notificationx' );
                ?>
                <div class="nx-mcp-app" data-nx-key="<?php echo esc_attr( $app['type'] . ':' . $app['client_id'] ); ?>">
                    <div class="nx-mcp-app-info">
                        <strong><?php echo esc_html( $app['name'] ); ?></strong>
                        <span class="nx-mcp-scope <?php echo esc_attr( $scope_class ); ?>"><?php echo esc_html( $scope_label ); ?></span>
                    </div>
                    <button type="button" class="nx-mcp-revoke" onclick="nxMcpRevoke(this,'<?php echo esc_js( $app['type'] ); ?>','<?php echo esc_js( $app['client_id'] ); ?>')"><?php esc_html_e( 'Revoke', 'notificationx' ); ?></button>
                </div>
                <?php
            }
            echo '</div>';
        }
        ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Connection health panel.
     *
     * @return string
     */
    protected function health_html() {
        $secure = is_ssl();
        ob_start();
        ?>
        <div class="nx-mcp-health">
            <div class="nx-mcp-health-row">
                <span class="nx-mcp-dot <?php echo $secure ? 'nx-mcp-dot-good' : 'nx-mcp-dot-warn'; ?>"></span>
                <?php if ( $secure ) : ?>
                    <?php esc_html_e( 'Secure connection (HTTPS) is on.', 'notificationx' ); ?>
                <?php else : ?>
                    <?php esc_html_e( 'This site is not served over HTTPS. Token clients work, but hosted clients like Claude require an HTTPS site to connect.', 'notificationx' ); ?>
                <?php endif; ?>
            </div>
            <div class="nx-mcp-health-row"><span class="nx-mcp-dot nx-mcp-dot-good"></span><?php /* translators: %s: protocol version */ printf( esc_html__( 'MCP protocol version %s.', 'notificationx' ), esc_html( Server::PROTOCOL_VERSION ) ); ?></div>
            <div class="nx-mcp-health-row"><span class="nx-mcp-dot nx-mcp-dot-good"></span><?php esc_html_e( 'Endpoint:', 'notificationx' ); ?> <code><?php echo esc_html( $this->connector_url() ); ?></code></div>
            <p class="nx-mcp-hint"><?php esc_html_e( 'Use “Test connection” above to verify the server end-to-end.', 'notificationx' ); ?></p>
        </div>
        <div class="nx-mcp-danger">
            <div class="nx-mcp-danger-text">
                <strong><?php esc_html_e( 'Disconnect all', 'notificationx' ); ?></strong>
                <span><?php esc_html_e( 'Revoke every connection and OAuth grant. All clients will need to reconnect.', 'notificationx' ); ?></span>
            </div>
            <button type="button" class="nx-mcp-btn nx-mcp-btn-danger" onclick="nxMcpAction(this,'disconnect',{confirm:'<?php echo esc_js( __( 'Disconnect all clients? Every connection will be revoked.', 'notificationx' ) ); ?>',reload:true,success:'<?php echo esc_js( __( 'All MCP connections have been revoked.', 'notificationx' ) ); ?>'})"><?php esc_html_e( 'Disconnect all clients', 'notificationx' ); ?></button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Print the MCP panel CSS + JS on the NotificationX settings page.
     * (The onclick handlers in the rendered HTML reference these globals.)
     *
     * @return void
     */
    public function print_panel_assets() {
        // The NotificationX admin is a single-page app (BrowserRouter): moving
        // between its screens — including into Settings → MCP — is client-side, so
        // admin_print_footer_scripts fires only on the first full page load,
        // whatever NX screen that happened to be. Print the panel CSS/JS on every
        // NotificationX admin page (slug prefixed "nx-"), not just nx-settings, so
        // the styles/handlers are already on the document when the MCP tab renders
        // after a client-side navigation. Otherwise the panel shows unstyled until
        // a manual reload.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page check.
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( ! is_admin() || 0 !== strpos( $page, 'nx-' ) || ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $nonce = wp_create_nonce( 'wp_rest' );
        $urls  = array(
            'test'       => esc_url_raw( rest_url( 'notificationx/v1/mcp/self-test' ) ),
            'enable'     => esc_url_raw( rest_url( 'notificationx/v1/mcp/enable' ) ),
            'connection' => esc_url_raw( rest_url( 'notificationx/v1/mcp/connection' ) ),
            'rotate'     => esc_url_raw( rest_url( 'notificationx/v1/mcp/rotate' ) ),
            'disconnect' => esc_url_raw( rest_url( 'notificationx/v1/mcp/disconnect' ) ),
            'revoke'     => esc_url_raw( rest_url( 'notificationx/v1/mcp/apps/revoke' ) ),
            'apps'       => esc_url_raw( rest_url( 'notificationx/v1/mcp/apps' ) ),
        );
        $i18n = array(
            'revoke'        => __( 'Revoke', 'notificationx' ),
            'empty'         => __( 'No AI clients are connected yet.', 'notificationx' ),
            'refreshFailed' => __( 'Could not refresh the connected apps.', 'notificationx' ),
            'revokeConfirm' => __( 'Revoke this connection? The client will need to reconnect.', 'notificationx' ),
            // Enable toggle outcomes.
            'enabled'       => __( 'MCP access enabled.', 'notificationx' ),
            'disabled'      => __( 'MCP access disabled.', 'notificationx' ),
            'enableFailed'  => __( 'Could not save the MCP setting.', 'notificationx' ),
            // Generic action outcomes.
            'genericError'  => __( 'Something went wrong.', 'notificationx' ),
            'requestFailed' => __( 'Request failed.', 'notificationx' ),
            'done'          => __( 'Done.', 'notificationx' ),
            'revoked'       => __( 'Connection revoked.', 'notificationx' ),
            // Refresh outcomes: say what actually changed, not just a count.
            'noneStill'     => __( 'No apps connected yet.', 'notificationx' ),
            'upToDate'      => __( 'Up to date — nothing changed.', 'notificationx' ),
            'addedOne'      => __( '1 new app connected.', 'notificationx' ),
            /* translators: %d: number of newly connected apps. */
            'addedMany'     => __( '%d new apps connected.', 'notificationx' ),
            'removedOne'    => __( '1 app disconnected.', 'notificationx' ),
            /* translators: %d: number of disconnected apps. */
            'removedMany'   => __( '%d apps disconnected.', 'notificationx' ),
            'changed'       => __( 'Connected apps updated.', 'notificationx' ),
            'statusActive'  => __( 'Active', 'notificationx' ),
            'statusOff'     => __( 'Off', 'notificationx' ),
            'copied'        => __( 'Copied', 'notificationx' ),
            'tokenMissing'  => __( 'The token is not on screen yet. Reload the page and try again.', 'notificationx' ),
            'configCopied'  => __( 'Server block copied. Paste it into your client’s MCP config.', 'notificationx' ),
        );
        ?>
        <style id="nx-mcp-panel-css">
            /* The settings form renders a message field's HTML inside a <p>, which
               carries the form's own paragraph spacing: reset it on our own fields
               so the panels control their own rhythm. */
            .nx-mcp-field p{margin:0}
            .nx-mcp-field-flush > p{margin:0}

            /* NotificationX's own `#notificationx .wprf-message p {font-size:16px}`
               outranks a bare class, so every paragraph and list item inside a panel
               would silently come back at the form's body size — which is what made
               the old hint text read as body copy. These carry the same id plus the
               class, so the panel keeps the type scale it was designed at without
               reaching for !important. The unprefixed rules further down stay as the
               fallback for anywhere the `#notificationx` root is absent. */
            #notificationx .wprf-message p.nx-mcp-hero-text{font-size:13.5px;line-height:1.65}
            #notificationx .wprf-message p.nx-mcp-hint{font-size:12px;line-height:1.55}
            #notificationx .wprf-message p.nx-mcp-empty{font-size:13px}
            #notificationx .wprf-message ol.nx-mcp-steps,#notificationx .wprf-message ol.nx-mcp-steps li{font-size:12.5px;line-height:1.75}
            #notificationx .wprf-message ol.nx-mcp-rail,#notificationx .wprf-message ol.nx-mcp-rail li{font-size:12.5px}

            /* ---- Hero -------------------------------------------------------- */
            .nx-mcp-hero{border-radius:14px;overflow:hidden;background:linear-gradient(135deg,#f6f3ff 0%,#fbfaff 55%,#ffffff 100%);color:#1d2327;border:1px solid #e4ddff;box-shadow:0 6px 20px rgba(106,75,255,.08)}
            .nx-mcp-hero-main{display:flex;gap:16px;align-items:flex-start;padding:22px 24px 20px}
            .nx-mcp-hero-tile{width:44px;height:44px;flex:none;border-radius:12px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#6a4bff,#8b6bff);border:0;box-shadow:0 4px 12px rgba(106,75,255,.28)}
            .nx-mcp-hero-tile-ic{width:24px;height:24px;display:block}
            .nx-mcp-hero-body{min-width:0}
            .nx-mcp-hero-title{margin:0 0 8px;font-size:19px;line-height:1.3;font-weight:700;color:#1d2327;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
            .nx-mcp-hero-text{margin:0;color:#50575e;font-size:13.5px;line-height:1.65;max-width:720px}
            .nx-mcp-learn{display:inline-flex;align-items:center;gap:5px;margin-top:12px;color:#5a3ee6;font-size:13px;font-weight:600}
            /* The message-field CSS (#notificationx .wprf-message p a) underlines the
               whole anchor at rest, which draws a line under the arrow too. Override
               it in every state (!important beats that #id rule) and underline only
               the text span on hover. */
            .nx-mcp-learn,.nx-mcp-learn:link,.nx-mcp-learn:visited,.nx-mcp-learn:hover,.nx-mcp-learn:focus,.nx-mcp-learn:active{text-decoration:none!important;color:#5a3ee6!important}
            .nx-mcp-learn .nx-mcp-learn-text{text-decoration:none}
            .nx-mcp-learn:hover .nx-mcp-learn-text{text-decoration:underline}
            .nx-mcp-learn-arrow{display:inline-block;transition:transform .2s}
            .nx-mcp-learn:hover .nx-mcp-learn-arrow{transform:translateX(3px)}
            /* The glyph is a literal right arrow: mirror it, and the nudge, in RTL. */
            [dir="rtl"] .nx-mcp-learn-arrow{transform:scaleX(-1)}
            [dir="rtl"] .nx-mcp-learn:hover .nx-mcp-learn-arrow{transform:scaleX(-1) translateX(3px)}

            /* ---- Status badge ------------------------------------------------ */
            .nx-mcp-badge{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:700;padding:3px 11px;border-radius:999px;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}
            .nx-mcp-badge-dot{width:7px;height:7px;border-radius:50%;flex:none;background:currentColor}
            .nx-mcp-badge-off{background:#eef0f3;color:#50575e}
            .nx-mcp-badge-active{background:#d8f7e2;color:#127a35}
            .nx-mcp-badge-setup{background:#ffeccc;color:#8a5a00}
            .nx-mcp-badge-active .nx-mcp-badge-dot{animation:nx-mcp-pulse 1.8s ease-in-out infinite}
            @keyframes nx-mcp-pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.35;transform:scale(.72)}}
            @media(prefers-reduced-motion:reduce){.nx-mcp-badge-active .nx-mcp-badge-dot{animation:none}}

            /* ---- Setup rail -------------------------------------------------- */
            .nx-mcp-rail{display:grid;grid-template-columns:repeat(3,1fr);gap:0;margin:0;padding:0;list-style:none;background:transparent;border-top:1px solid #ece8ff}
            .nx-mcp-rail-step{display:flex;gap:12px;align-items:center;padding:14px 20px;margin:0;position:relative}
            .nx-mcp-rail-step + .nx-mcp-rail-step{border-inline-start:1px solid #ece8ff}
            .nx-mcp-rail-mark{width:32px;height:32px;flex:none;border-radius:9px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#6a4bff 0%,#8b6bff 100%);box-shadow:0 3px 8px rgba(106,75,255,.25)}
            .nx-mcp-rail-ic{width:16px;height:16px;display:block}
            .nx-mcp-rail-body{display:flex;flex-direction:column;gap:2px;min-width:0}
            .nx-mcp-rail-label{font-size:12.5px;font-weight:700;color:#1d2327}
            .nx-mcp-rail-hint{font-size:11.5px;line-height:1.5;color:#646970}
            @media(max-width:782px){.nx-mcp-rail{grid-template-columns:1fr}.nx-mcp-rail-step + .nx-mcp-rail-step{border-inline-start:0;border-top:1px solid #ece8ff}}

            /* ---- Enable toggle row ------------------------------------------- */
            /* Keep label + switch on one row (no fixed 200px label column gap) and
               let the help text span full-width, left-aligned. */
            .wprf-name-enable_mcp{display:flex;flex-wrap:wrap;align-items:center}
            .wprf-name-enable_mcp .wprf-control-label{width:auto!important;flex:0 0 auto!important;margin:0 12px 0 0!important}
            .wprf-name-enable_mcp .wprf-control-field{display:contents}
            .wprf-name-enable_mcp .wprf-toggle-wrap{order:2}
            .wprf-name-enable_mcp .wprf-help{order:3;flex-basis:100%;width:100%;margin:8px 0 0!important}

            /* ---- Stat tiles -------------------------------------------------- */
            .nx-mcp-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
            @media(max-width:960px){.nx-mcp-stats{grid-template-columns:repeat(2,1fr)}}
            @media(max-width:600px){.nx-mcp-stats{grid-template-columns:1fr}}
            .nx-mcp-stat{border:1px solid #e6e6ec;border-radius:12px;padding:13px 15px;background:#fff;position:relative;overflow:hidden}
            .nx-mcp-stat:before{content:"";position:absolute;top:0;bottom:0;inset-inline-start:0;width:3px;background:#6a4bff;opacity:.85}
            .nx-mcp-stat-top{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px}
            .nx-mcp-stat-label{font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#6b7280}
            .nx-mcp-stat-ic{width:26px;height:26px;border-radius:8px;background:#f4f2ff;display:flex;align-items:center;justify-content:center;flex:none}
            .nx-mcp-stat-ic img{width:16px;height:16px;display:block}
            .nx-mcp-stat-row{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap}
            .nx-mcp-stat-value{font-size:26px;line-height:1.1;font-weight:700;color:#1f2330}
            .nx-mcp-stat-value.is-sm{font-size:16px;line-height:1.4}
            .nx-mcp-stat-note{font-size:11.5px;color:#8a8f9c}


            /* ---- Pills ------------------------------------------------------- */
            .nx-mcp-pill{font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;text-transform:uppercase;letter-spacing:.03em;white-space:nowrap}
            .nx-mcp-pill-pro{background:#fff1d6;color:#9a6400}
            .nx-mcp-pill-oauth{background:#f0eefe;color:#6a4bff}
            .nx-mcp-pill-token{background:#e7f1ff;color:#1d4ed8}


            /* ---- Connection cards -------------------------------------------- */
            .nx-mcp-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
            @media(max-width:782px){.nx-mcp-grid{grid-template-columns:1fr}}
            .nx-mcp-card{border:1px solid #e6e6ec;border-radius:12px;padding:14px 16px;background:#fff}
            .nx-mcp-card-label{display:block;font-weight:700;font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px}
            .nx-mcp-copyrow{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
            .nx-mcp-value{background:#f6f7f9;border:1px solid #e6e6ec;border-radius:8px;padding:7px 10px;font-size:12px;flex:1;min-width:0;overflow:auto;white-space:nowrap}
            .nx-mcp-copy{cursor:pointer;border:1px solid #d3d4da;background:#fff;border-radius:8px;padding:7px 13px;font-size:12px;font-weight:600;color:#2c3338;transition:background .15s,border-color .15s}
            .nx-mcp-copy:hover{background:#f4f2ff;border-color:#c3b8ff;color:#4c31d6}
            .nx-mcp-copy-wide{display:block;width:100%;margin-top:12px;text-align:center}
            .nx-mcp-hint{margin:8px 0 0;color:#8a8f9c;font-size:12px}

            /* ---- Pending (enabled but unsaved) ------------------------------- */

            /* ---- Client cards ------------------------------------------------ */
            .nx-mcp-clients{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
            @media(max-width:960px){.nx-mcp-clients{grid-template-columns:1fr}}
            .nx-mcp-client{border:1px solid #e6e6ec;border-radius:12px;padding:14px 16px;background:#fff;display:flex;flex-direction:column;transition:border-color .15s,box-shadow .15s}
            .nx-mcp-client:hover{border-color:#c3b8ff;box-shadow:0 6px 18px rgba(106,75,255,.08)}
            .nx-mcp-client-name{display:flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap}
            .nx-mcp-client-title{font-weight:700;font-size:13.5px;color:#1f2330}
            /* Nothing on this tab is submitted by the settings form: the enable
               toggle saves itself and everything else is an ajax button or
               read-only text, so a Save button here only invites a click that
               does nothing. The control is a single shared quickbuilder
               component every other tab still needs, so it is hidden for this
               tab rather than removed. Selector mirrors the Entries tab, which
               already hides it the same way, and has to out-specify
               `#notificationx .wp-react-form... .wprf-submit{display:flex}`. */
            #notificationx .nx-admin-wrapper .nx-settings-form-wrapper.tab-mcp .wprf-submit.wprf-control{display:none}
            /* Client icons are <img> tags pointing at real SVG files: the card HTML is
               kses-filtered, which strips <svg> and rejects data: URIs in src/style. */
            .nx-mcp-client-ic{width:20px;height:20px;flex:none;display:inline-block;vertical-align:middle}
            .nx-mcp-steps{margin:0;padding-inline-start:18px;color:#50575e;font-size:12.5px;line-height:1.75;flex:1}

            /* ---- Connected apps ---------------------------------------------- */
            .nx-mcp-apps-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px;flex-wrap:wrap}
            .nx-mcp-apps-hint{color:#8a8f9c;font-size:12px}
            .nx-mcp-btn-sm{padding:5px 12px;font-size:12px}
            /* Once moved into the section heading bar, sit flush right on that row. */
            .wprf-section-title .nx-mcp-refresh-apps{margin-inline-start:auto}
            .nx-mcp-apps-head:empty{display:none;margin:0}
            .nx-mcp-apps{display:flex;flex-direction:column;gap:10px}
            .nx-mcp-app{display:flex;justify-content:space-between;align-items:center;gap:12px;border:1px solid #e6e6ec;border-radius:10px;padding:11px 14px;background:#fff}
            .nx-mcp-app-info{display:flex;align-items:center;gap:10px;flex-wrap:wrap;min-width:0}
            .nx-mcp-scope{font-size:11px;font-weight:700;padding:2px 9px;border-radius:999px}
            .nx-mcp-scope-ro{background:#eef0f3;color:#50575e}
            .nx-mcp-scope-rw{background:#d8f7e2;color:#127a35}
            .nx-mcp-revoke{cursor:pointer;border:1px solid #e2b5b6;background:#fff;color:#d63638;border-radius:8px;padding:6px 13px;font-size:12px;font-weight:600;flex:none;transition:background .15s,color .15s,border-color .15s}
            .nx-mcp-revoke:hover{background:#d63638;color:#fff;border-color:#d63638}
            .nx-mcp-empty{color:#8a8f9c;font-style:italic}

            /* ---- Health + danger --------------------------------------------- */
            .nx-mcp-health{display:flex;flex-direction:column;gap:9px}
            .nx-mcp-health-row{display:flex;align-items:center;gap:9px;color:#2c3338;font-size:13px}
            .nx-mcp-dot{width:9px;height:9px;border-radius:50%;display:inline-block;flex:none}
            .nx-mcp-dot-good{background:#16a34a}
            .nx-mcp-dot-warn{background:#dba617}
            .nx-mcp-danger{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-top:16px;padding:14px 16px;border:1px solid #f0c4c4;background:#fdf4f4;border-radius:12px;flex-wrap:wrap}
            .nx-mcp-danger-text{display:flex;flex-direction:column;gap:2px}
            .nx-mcp-danger-text strong{color:#8a1f21}
            .nx-mcp-danger-text span{color:#a15b5b;font-size:12px}

            /* ---- Buttons ----------------------------------------------------- */
            .nx-mcp-actions{display:flex;gap:10px;margin-top:16px;flex-wrap:wrap;align-items:center}
            .nx-mcp-btn{cursor:pointer;border-radius:8px;padding:9px 17px;font-size:13px;font-weight:600;border:1px solid transparent;line-height:1.2;text-decoration:none!important;display:inline-flex;align-items:center;justify-content:center;transition:background .15s,border-color .15s,box-shadow .15s}
            .nx-mcp-btn[disabled]{opacity:.6;cursor:default}
            .nx-mcp-btn-primary{background:#6a4bff;color:#fff!important;box-shadow:0 4px 12px rgba(106,75,255,.25)}
            .nx-mcp-btn-primary:hover{background:#583fd6}
            /* Kept as an alias: earlier markup used -secondary for the same control. */
            .nx-mcp-btn-secondary{background:#6a4bff;color:#fff}
            .nx-mcp-btn-secondary:hover{background:#583fd6}
            .nx-mcp-btn-ghost{background:#fff;color:#2c3338;border-color:#d3d4da}
            .nx-mcp-btn-ghost:hover{background:#f6f7f9;border-color:#c3c4c7}
            .nx-mcp-btn-danger{background:#d63638;color:#fff;border-color:#d63638}
            .nx-mcp-btn-danger:hover{background:#b32d2e}

            /* ---- Focus ------------------------------------------------------- */
            /* WP admin sets `a:focus{outline:2px solid transparent}` and leans on a
               box-shadow that never lands here, so the hero link had no visible focus
               state at all. Every control in the panel gets an explicit brand-colour
               ring. Keyboard only —
               :focus-visible keeps mouse clicks from drawing it. */
            .nx-mcp-copy:focus-visible,.nx-mcp-btn:focus-visible,.nx-mcp-revoke:focus-visible{outline:2px solid #4c31d6;outline-offset:2px;border-radius:8px}
            .nx-mcp-learn:focus-visible{outline:2px solid #4c31d6;outline-offset:3px;border-radius:4px}
            /* The controls that are anchors, not buttons — the hero link — is additionally zeroed by NotificationX's own
               `#notificationx a:focus{outline:0}`, which carries an id and outranks a
               class. Same id here so the ring survives; everything else in the panel
               is a <button> and never meets that rule. */
            #notificationx a.nx-mcp-learn:focus-visible{outline:2px solid #4c31d6;outline-offset:3px;border-radius:4px}

            /* ---- Inline result + toast --------------------------------------- */
            .nx-mcp-result{display:none;margin-top:12px;padding:11px 14px;border-radius:10px;font-size:12.5px;line-height:1.6;border:1px solid transparent}
            .nx-mcp-result.is-shown{display:block}
            .nx-mcp-result.is-ok{background:#eefaf1;border-color:#bfe6cb;color:#12652c}
            .nx-mcp-result.is-err{background:#fdf1f1;border-color:#f0c4c4;color:#8a1f21}
            .nx-mcp-toast{position:fixed;bottom:28px;inset-inline-end:28px;z-index:100001;padding:12px 18px;border-radius:10px;color:#fff;font-size:13px;font-weight:600;box-shadow:0 8px 28px rgba(0,0,0,.2);opacity:0;transform:translateY(12px);transition:opacity .28s,transform .28s;max-width:380px}
            .nx-mcp-toast-in{opacity:1;transform:translateY(0)}
            .nx-mcp-toast-success{background:#16a34a}
            .nx-mcp-toast-error{background:#d63638}
        </style>
        <script id="nx-mcp-panel-js">
            window.nxMcpData = { urls: <?php echo wp_json_encode( $urls ); ?>, nonce: <?php echo wp_json_encode( $nonce ); ?>, i18n: <?php echo wp_json_encode( $i18n ); ?> };
            window.nxMcpToast = function(type, msg){
                var t = document.createElement('div');
                t.className = 'nx-mcp-toast nx-mcp-toast-' + (type === 'error' ? 'error' : 'success');
                t.textContent = msg;
                document.body.appendChild(t);
                requestAnimationFrame(function(){ t.classList.add('nx-mcp-toast-in'); });
                setTimeout(function(){ t.classList.remove('nx-mcp-toast-in'); setTimeout(function(){ t.remove(); }, 320); }, 3600);
            };
            window.nxMcpCopy = function(btn, text){
                var done = function(){ var o = btn.textContent; btn.textContent = '✓ ' + window.nxMcpData.i18n.copied; setTimeout(function(){ btn.textContent = o; }, 1400); };
                if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(text).then(done, done); }
                else { var t=document.createElement('textarea'); t.value=text; document.body.appendChild(t); t.select(); try{document.execCommand('copy');}catch(e){} document.body.removeChild(t); done(); }
            };
            window.nxMcpReveal = function(btn){
                var code = btn.parentNode.querySelector('.nx-mcp-token'); if(!code) return;
                if (code.dataset.shown === '1'){ code.textContent = '••••••••••••'; code.dataset.shown='0'; btn.textContent='<?php echo esc_js( __( 'Show', 'notificationx' ) ); ?>'; return; }
                // The panel may have been rendered before MCP was switched on, in
                // which case there was no token to print into it. Fetch it rather
                // than revealing an empty box.
                nxMcpWithToken(function(token){
                    code.textContent = token || ''; code.dataset.shown='1';
                    btn.textContent='<?php echo esc_js( __( 'Hide', 'notificationx' ) ); ?>';
                });
            };
            window.nxMcpCopyToken = function(btn){
                var code = btn.parentNode.querySelector('.nx-mcp-token'); if(!code) return;
                nxMcpWithToken(function(token){ nxMcpCopy(btn, token || ''); });
            };
            // Hand the caller the token, fetching it once if the panel does not
            // have one yet.
            window.nxMcpWithToken = function(done){
                var code = document.querySelector('.nx-mcp-token');
                var have = code && code.dataset.token;
                if (have) { done(code.dataset.token); return; }
                nxMcpSyncConnection(function(state){ done(state && state.token ? state.token : ''); });
            };
            // The config-file clients want a server block, not a bare URL. It is
            // assembled here from the token already rendered into the connection
            // card, so the page never carries a second copy of the secret.
            window.nxMcpCopyConfig = function(btn){
                // Same source as the token card's Copy: fetched once if the panel
                // was rendered before MCP was switched on.
                nxMcpWithToken(function(token){
                    if (!token){ nxMcpToast('error', window.nxMcpData.i18n.tokenMissing); return; }
                    var config = {
                        mcpServers: {
                            notificationx: {
                                url: btn.getAttribute('data-url') || '',
                                headers: { Authorization: 'Bearer ' + token }
                            }
                        }
                    };
                    nxMcpCopy(btn, JSON.stringify(config, null, 2));
                    nxMcpToast('success', window.nxMcpData.i18n.configCopied);
                });
            };
            // Write an action's outcome into the panel next to the button that ran
            // it. The toast still fires: it covers the case where the button has
            // been scrolled out of view, and this covers the case where the reader
            // looks back at the panel after the toast has gone.
            window.nxMcpShowResult = function(id, ok, message){
                var box = document.getElementById(id);
                if (!box) return;
                box.textContent = message || '';
                box.className = 'nx-mcp-result is-shown ' + (ok ? 'is-ok' : 'is-err');
            };
            window.nxMcpAction = function(btn, action, opts){
                opts = opts || {};
                if (opts.confirm && !window.confirm(opts.confirm)) return;
                var old = btn.textContent; btn.disabled = true; btn.textContent = '…';
                fetch(window.nxMcpData.urls[action], {
                    method:'POST',
                    headers:{'Content-Type':'application/json','X-WP-Nonce':window.nxMcpData.nonce},
                    body: JSON.stringify(opts.body || {})
                }).then(function(r){ return r.json().catch(function(){ return {}; }); }).then(function(res){
                    btn.disabled = false; btn.textContent = old;
                    if (res && res.status === 'error'){
                        if (opts.result) nxMcpShowResult(opts.result, false, res.message || window.nxMcpData.i18n.genericError);
                        nxMcpToast('error', res.message || window.nxMcpData.i18n.genericError); return;
                    }
                    var msg = opts.success || (res && res.message) || window.nxMcpData.i18n.done;
                    if (opts.result) nxMcpShowResult(opts.result, true, (res && res.message) || msg);
                    nxMcpToast('success', msg);
                    if (opts.reload){ setTimeout(function(){ window.location.reload(); }, 900); }
                }).catch(function(){
                    btn.disabled = false; btn.textContent = old;
                    if (opts.result) nxMcpShowResult(opts.result, false, window.nxMcpData.i18n.requestFailed);
                    nxMcpToast('error', window.nxMcpData.i18n.requestFailed);
                });
            };
            window.nxMcpRevoke = function(btn, type, clientId){
                nxMcpAction(btn, 'revoke', {
                    confirm: window.nxMcpData.i18n.revokeConfirm,
                    body: { type: type, client_id: clientId },
                    reload: true,
                    success: window.nxMcpData.i18n.revoked
                });
            };
            // Re-read the connected apps without a full page reload, so a newly
            // approved or detached client shows up immediately. Rows are built with
            // textContent because a client's name comes from dynamic registration.
            window.nxMcpRefreshApps = function(btn){
                var wrap = document.getElementById('nx-mcp-apps-wrap');
                if (!wrap) return;
                var old = btn ? btn.textContent : '';
                if (btn){ btn.disabled = true; btn.textContent = '…'; }
                fetch(window.nxMcpData.urls.apps, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { 'X-WP-Nonce': window.nxMcpData.nonce }
                }).then(function(r){ return r.json(); }).then(function(res){
                    if (btn){ btn.disabled = false; btn.textContent = old; }
                    if (!res || res.status !== 'success' || !Array.isArray(res.apps)){
                        nxMcpToast('error', window.nxMcpData.i18n.refreshFailed); return;
                    }
                    // What was on screen before this refresh, so the toast can report
                    // the actual delta rather than just restating a count.
                    var prev = [];
                    wrap.querySelectorAll('.nx-mcp-app').forEach(function(el){
                        prev.push(el.getAttribute('data-nx-key') || '');
                    });
                    var next = res.apps.map(function(a){ return a.type + ':' + (a.client_id || ''); });
                    var added = next.filter(function(k){ return prev.indexOf(k) === -1; }).length;
                    var removed = prev.filter(function(k){ return next.indexOf(k) === -1; }).length;
                    var i18n = window.nxMcpData.i18n;
                    var toast;
                    if (!added && !removed) {
                        toast = next.length ? i18n.upToDate : i18n.noneStill;
                    } else if (added && !removed) {
                        toast = added === 1 ? i18n.addedOne : i18n.addedMany.replace('%d', added);
                    } else if (removed && !added) {
                        toast = removed === 1 ? i18n.removedOne : i18n.removedMany.replace('%d', removed);
                    } else {
                        toast = i18n.changed;
                    }

                    while (wrap.firstChild) { wrap.removeChild(wrap.firstChild); }
                    if (!res.apps.length){
                        var p = document.createElement('p');
                        p.className = 'nx-mcp-empty';
                        p.textContent = i18n.empty;
                        wrap.appendChild(p);
                        nxMcpToast('success', toast);
                        return;
                    }
                    var list = document.createElement('div');
                    list.className = 'nx-mcp-apps';
                    res.apps.forEach(function(app){
                        var row = document.createElement('div'); row.className = 'nx-mcp-app';
                        row.setAttribute('data-nx-key', app.type + ':' + (app.client_id || ''));
                        var info = document.createElement('div'); info.className = 'nx-mcp-app-info';
                        var name = document.createElement('strong'); name.textContent = app.name || '';
                        var scope = document.createElement('span');
                        scope.className = 'nx-mcp-scope ' + (app.read_only ? 'nx-mcp-scope-ro' : 'nx-mcp-scope-rw');
                        scope.textContent = app.scope_label || '';
                        info.appendChild(name); info.appendChild(scope);
                        var rev = document.createElement('button');
                        rev.type = 'button'; rev.className = 'nx-mcp-revoke';
                        rev.textContent = window.nxMcpData.i18n.revoke;
                        rev.addEventListener('click', function(){ nxMcpRevoke(rev, app.type, app.client_id || ''); });
                        row.appendChild(info); row.appendChild(rev);
                        list.appendChild(row);
                    });
                    wrap.appendChild(list);
                    nxMcpToast('success', toast);
                }).catch(function(){
                    if (btn){ btn.disabled = false; btn.textContent = old; }
                    nxMcpToast('error', window.nxMcpData.i18n.refreshFailed);
                });
            };
            // The section heading ("Connected apps") is rendered by the settings form,
            // outside this message field, so the button starts inside the content box.
            // Move it onto that heading row once it exists — flex handles the exact
            // alignment, so no hard-coded offsets that break at the responsive padding
            // change. If this never runs the button simply stays in the box and works.
            window.nxMcpPlaceRefresh = function(){
                var btns = document.querySelectorAll('.nx-mcp-refresh-apps');
                if (!btns.length) return;
                var fresh = null, section = null, i;
                for (i = 0; i < btns.length; i++){
                    var sec = btns[i].closest ? btns[i].closest('.wprf-control-section') : null;
                    if (!sec) continue;
                    var t = sec.querySelector('.wprf-section-title');
                    if (!t) continue;
                    section = sec;
                    // A button still sitting in the content box is a freshly rendered one.
                    if (!t.contains(btns[i])) { fresh = btns[i]; break; }
                }
                if (!section || !fresh) return;
                var title = section.querySelector('.wprf-section-title');
                if (!title) return;
                // Drop any previously moved button first, so a re-render cannot leave two.
                var stale = title.querySelectorAll('.nx-mcp-refresh-apps');
                for (i = 0; i < stale.length; i++){ stale[i].parentNode.removeChild(stale[i]); }
                title.appendChild(fresh);
            };
            if (window.MutationObserver){
                new MutationObserver(function(){ nxMcpPlaceRefresh(); })
                    .observe(document.body, { childList: true, subtree: true });
            }
            document.addEventListener('DOMContentLoaded', function(){ nxMcpPlaceRefresh(); });
            nxMcpPlaceRefresh();

            // Bound by delegation rather than an inline onclick, so the buttons keep
            // working even if the panel markup is passed through a sanitiser.
            document.addEventListener('click', function(e){
                if (!e.target || !e.target.closest) return;
                var refresh = e.target.closest('.nx-mcp-refresh-apps');
                if (refresh){ e.preventDefault(); nxMcpRefreshApps(refresh); return; }
                var config = e.target.closest('.nx-mcp-copy-config');
                if (config){ e.preventDefault(); nxMcpCopyConfig(config); }
            });

            // Paint the badge for a given state.
            // Only the label changes, so the status dot inside the badge survives.
            window.nxMcpPaintBadge = function(on){
                var badge = document.querySelector('.nx-mcp-badge');
                if (!badge) return;
                var text  = badge.querySelector('.nx-mcp-badge-text');
                var label = on ? window.nxMcpData.i18n.statusActive : window.nxMcpData.i18n.statusOff;
                if (text) { text.textContent = label; } else { badge.textContent = label; }
                badge.className = 'nx-mcp-badge nx-mcp-badge-' + (on ? 'active' : 'off');
                // The stats row is revealed by the toggle too; keep its status tile in step.
                var tile = document.querySelector('.nx-mcp-stat-status');
                if (tile) { tile.textContent = label; }
            };

            // The enable toggle saves itself. The settings form's own Save still
            // works, but the toggle gates every panel below it, so leaving it
            // unsaved meant the connector URL, token and connection test all
            // described a state the server was not in.
            var nxMcpEnableInFlight = false;
            var nxMcpToggleSync = false;
            // The toggle is a controlled React input: setting `checked` on the DOM
            // node leaves the settings form holding the old value, and the form's
            // Save would later write it back. Click it instead, so React's own
            // onChange updates the form state, and skip our handler for that click.
            var nxMcpSetToggle = function(input, value){
                if (!!input.checked === value) return;
                nxMcpToggleSync = true;
                try { input.click(); } finally { nxMcpToggleSync = false; }
            };
            document.addEventListener('change', function(e){
                if (!e.target || e.target.name !== 'enable_mcp') return;
                if (nxMcpToggleSync) return;
                var input = e.target;
                var on    = !!input.checked;

                // Show the intent straight away, then reconcile with the server.
                nxMcpPaintBadge(on);

                if (nxMcpEnableInFlight) return;
                nxMcpEnableInFlight = true;
                input.disabled = true;

                fetch(window.nxMcpData.urls.enable, {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'X-WP-Nonce': window.nxMcpData.nonce },
                    body: JSON.stringify({ enabled: on })
                }).then(function(r){
                    return r.json().catch(function(){ return {}; }).then(function(j){
                        if (!r.ok) { throw new Error((j && j.message) || 'http'); }
                        return j;
                    });
                }).then(function(res){
                    nxMcpEnableInFlight = false; input.disabled = false;
                    // Server is the truth: repaint from what it reports.
                    var saved = !!res.enabled;
                    nxMcpSetToggle(input, saved);
                    nxMcpPaintBadge(saved);
                    if (res.token) { nxMcpSetToken(res.token); }
                    nxMcpToast('success', saved ? window.nxMcpData.i18n.enabled : window.nxMcpData.i18n.disabled);
                }).catch(function(){
                    nxMcpEnableInFlight = false; input.disabled = false;
                    // Put the control back where it was so it cannot claim a
                    // state that was never stored.
                    nxMcpSetToggle(input, !on);
                    nxMcpPaintBadge(!on);
                    nxMcpToast('error', window.nxMcpData.i18n.enableFailed);
                });
            });

            // Fill in the token the panel was rendered without, so Show/Copy and
            // the connection test work without a reload.
            window.nxMcpSetToken = function(token){
                var code = document.querySelector('.nx-mcp-token');
                if (!code) return;
                code.dataset.token = token;
                if (code.dataset.shown === '1') { code.textContent = token; }
            };

            // Re-read the connection from the server and repaint the panel.
            // The panel is server-rendered once; anything that switches MCP on
            // afterwards -- the toggle, or the settings form's own Save -- leaves
            // the markup describing the old state until this runs.
            window.nxMcpSyncConnection = function(done){
                fetch(window.nxMcpData.urls.connection, {
                    headers: { 'X-WP-Nonce': window.nxMcpData.nonce }
                }).then(function(r){ return r.json(); }).then(function(state){
                    if (state && typeof state.enabled !== 'undefined') { nxMcpPaintBadge(!!state.enabled); }
                    if (state && state.token) { nxMcpSetToken(state.token); }
                    if (done) { done(state); }
                }).catch(function(){ if (done) { done(null); } });
            };

            // The settings form's Save can switch MCP on without going through
            // the toggle handler (a value restored by the browser, or a save
            // triggered from another tab). Pick the new state up either way.
            document.addEventListener('click', function(e){
                var btn = e.target && e.target.closest ? e.target.closest('.wprf-submit-button') : null;
                if (!btn) return;
                if (!document.querySelector('.nx-mcp-token')) return;
                setTimeout(function(){ nxMcpSyncConnection(); }, 1200);
            });
        </script>
        <?php
    }

    /* --------------------------------------------------------------------- */
    /* Helpers                                                               */
    /* --------------------------------------------------------------------- */

    /**
     * RFC 9728 protected-resource metadata, served from our own REST namespace.
     *
     * @return \WP_REST_Response
     */
    public function rest_protected_resource() {
        if ( ! $this->is_enabled() ) {
            return $this->discovery_disabled();
        }

        return new \WP_REST_Response( OAuth::get_instance()->protected_resource_metadata(), 200 );
    }

    /**
     * RFC 8414 authorization-server metadata, served from our own REST namespace.
     *
     * @return \WP_REST_Response
     */
    public function rest_authorization_server() {
        if ( ! $this->is_enabled() ) {
            return $this->discovery_disabled();
        }

        return new \WP_REST_Response( OAuth::get_instance()->authorization_server_metadata(), 200 );
    }

    /**
     * The response for a discovery request made while MCP is switched off.
     * A 404 keeps us indistinguishable from a site that never shipped MCP, so
     * a client cannot read our settings state from the discovery surface.
     *
     * @return \WP_Error
     */
    protected function discovery_disabled() {
        return new \WP_Error(
            'rest_no_route',
            __( 'No route was found matching the URL and request method.', 'notificationx' ),
            array( 'status' => 404 )
        );
    }

    /**
     * Whether an OAuth discovery path belongs to this plugin.
     *
     * The handler runs on `parse_request` at priority 0 and `emit_json()`
     * exits, so whatever it answers is final -- nothing later in the request
     * gets a say. A prefix match would therefore serve our metadata for
     * *any* suffix, including another MCP plugin's
     * `.well-known/oauth-protected-resource/<their-plugin>/mcp`, sending
     * their clients to our authorization server (RFC 9728 requires the
     * resource to match exactly, so their handshake then fails).
     *
     * Two forms are ours, and only those two:
     *
     * - the bare document, which our own `WWW-Authenticate` challenge
     *   advertises (see Server::with_challenge());
     * - the RFC 9728 path-suffixed form for our endpoint.
     *
     * Anything else is declined by returning false, so the request falls
     * through to whichever plugin does own it -- deliberately not a 404,
     * which would break that neighbour just as effectively.
     *
     * @param string $path Request path, relative to home and unslashed.
     * @param string $doc  Discovery document name.
     * @return bool
     */
    protected function owns_discovery_path( $path, $doc ) {
        $base = '.well-known/' . $doc;

        return $path === $base || $path === $base . '/' . self::ENDPOINT_PATH;
    }

    /**
     * The request path relative to the WordPress home path, without query string.
     *
     * @return string
     */
    protected function request_path() {
        $uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- parsed below.
        $uri = esc_url_raw( $uri );
        $path = wp_parse_url( $uri, PHP_URL_PATH );
        if ( ! $path ) {
            return '';
        }

        $home_path = wp_parse_url( home_url(), PHP_URL_PATH );
        if ( $home_path && 0 === strpos( $path, $home_path ) ) {
            $path = substr( $path, strlen( $home_path ) );
        }

        return trim( $path, '/' );
    }

    /**
     * Emit an array as a JSON document and stop.
     *
     * @param array $data Payload.
     * @return void
     */
    protected function emit_json( $data ) {
        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Access-Control-Allow-Origin: *' );
        header( 'Cache-Control: public, max-age=3600' );
        echo wp_json_encode( $data );
        exit;
    }

    /**
     * Emit a WP_REST_Response (status + headers + JSON body) and stop.
     *
     * @param \WP_REST_Response $response Response.
     * @return void
     */
    protected function emit_rest_response( $response ) {
        $status  = $response->get_status();
        $headers = $response->get_headers();
        $data    = $response->get_data();

        if ( ! isset( $headers['Content-Type'] ) ) {
            header( 'Content-Type: application/json; charset=utf-8' );
        }
        foreach ( $headers as $key => $value ) {
            header( $key . ': ' . $value );
        }
        // Set the status LAST. Emitting an auth header such as WWW-Authenticate
        // after the status resets the code to 401 in this SAPI, so the status
        // must be asserted after every other header() call.
        status_header( $status );
        if ( function_exists( 'http_response_code' ) ) {
            http_response_code( $status );
        }
        if ( 202 === $status || null === $data ) {
            exit;
        }
        echo wp_json_encode( $data );
        exit;
    }
}
