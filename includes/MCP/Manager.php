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

        // CSS + JS for the MCP panel (copy / reveal / revoke controls).
        add_action( 'admin_print_footer_scripts', array( $this, 'print_panel_assets' ) );
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
        register_rest_route( $ns, '/mcp/apps/revoke', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'rest_revoke_app' ),
            'permission_callback' => $admin,
        ) );
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
        $result = SelfTest::get_instance()->run();
        return new \WP_REST_Response( array( 'status' => $result['ok'] ? 'success' : 'error', 'message' => $result['message'] ) + $result, 200 );
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

        // OAuth discovery (also accept the path-suffixed RFC form).
        if ( 0 === strpos( $path, '.well-known/oauth-authorization-server' ) ) {
            $this->emit_json( OAuth::get_instance()->authorization_server_metadata() );
        }
        if ( 0 === strpos( $path, '.well-known/oauth-protected-resource' ) ) {
            $this->emit_json( OAuth::get_instance()->protected_resource_metadata() );
        }

        // Pretty MCP endpoint.
        if ( 'notificationx/mcp' === $path ) {
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

        // Approve on POST (nonce-checked).
        if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['nx_mcp_authorize'] ) ) {
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
     * Output the consent form.
     *
     * @param array $request Validated authorize request.
     * @return void
     */
    protected function render_authorize_page( $request ) {
        $store   = get_option( OAuth::OPTION, array() );
        $client  = isset( $store['clients'][ $request['client_id'] ] ) ? $store['clients'][ $request['client_id'] ] : array();
        $name    = ! empty( $client['client_name'] ) ? $client['client_name'] : $request['client_id'];
        $scope   = $request['scope'];

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
        body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;background:#f0f0f1;margin:0;display:flex;min-height:100vh;align-items:center;justify-content:center;color:#1e1e1e}
        .card{background:#fff;max-width:440px;width:92%;padding:32px;border-radius:12px;box-shadow:0 6px 30px rgba(0,0,0,.08)}
        h1{font-size:20px;margin:0 0 8px}
        p{font-size:14px;line-height:1.6;color:#3c434a}
        .app{font-weight:600}
        .scope{display:inline-block;background:#f0f0f1;border-radius:4px;padding:2px 8px;font-size:12px;margin:2px 4px 2px 0}
        .actions{display:flex;gap:12px;margin-top:24px}
        button{flex:1;padding:12px;border-radius:8px;border:0;font-size:14px;font-weight:600;cursor:pointer}
        .approve{background:#6a4bff;color:#fff}
        .deny{background:#f0f0f1;color:#1e1e1e}
    </style>
</head>
<body>
    <div class="card">
        <h1><?php esc_html_e( 'Authorize connection', 'notificationx' ); ?></h1>
        <p>
            <?php
            printf(
                /* translators: %1$s: client app name, %2$s: site name. */
                esc_html__( '%1$s wants to connect to NotificationX on %2$s and act on your behalf.', 'notificationx' ),
                '<span class="app">' . esc_html( $name ) . '</span>',
                esc_html( get_bloginfo( 'name' ) )
            );
            ?>
        </p>
        <p>
            <?php esc_html_e( 'Requested access:', 'notificationx' ); ?><br>
            <?php foreach ( preg_split( '/\s+/', trim( $scope ) ) as $s ) : ?>
                <span class="scope"><?php echo esc_html( $s ); ?></span>
            <?php endforeach; ?>
        </p>
        <form method="post">
            <?php wp_nonce_field( 'nx_mcp_authorize' ); ?>
            <input type="hidden" name="nx_mcp_authorize" value="1">
            <div class="actions">
                <button type="button" class="deny" onclick="window.close();history.back();"><?php esc_html_e( 'Cancel', 'notificationx' ); ?></button>
                <button type="submit" class="approve"><?php esc_html_e( 'Approve', 'notificationx' ); ?></button>
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
        // If MCP is on, make sure a pairing token exists so the UI has one to show.
        if ( $this->is_enabled() && ! Pairing::get_instance()->is_connected() ) {
            Pairing::get_instance()->connect();
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
     * Build the MCP settings field schema. The rich panels are server-rendered
     * HTML delivered through quickbuilder `message` fields (html => true); the
     * action buttons use quickbuilder `button` fields for the ajax + toast.
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
                        'message' => $this->hero_html(),
                    ),
                    'enable_mcp' => array(
                        'name'    => 'enable_mcp',
                        'type'    => 'toggle',
                        'default' => false,
                        'label'   => __( 'Enable MCP access', 'notificationx' ),
                        'help'    => __( 'When enabled, approved AI assistants can connect to this site to manage notifications and read analytics.', 'notificationx' ),
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
     * Hero header with the status badge.
     *
     * @return string
     */
    protected function hero_html() {
        list( $state, $label ) = $this->status();
        ob_start();
        ?>
        <div class="nx-mcp-hero">
            <div class="nx-mcp-hero-icon">&#128268;</div>
            <div class="nx-mcp-hero-body">
                <h3 class="nx-mcp-hero-title">
                    <?php esc_html_e( 'MCP Server', 'notificationx' ); ?>
                    <span class="nx-mcp-badge nx-mcp-badge-<?php echo esc_attr( $state ); ?>"><?php echo esc_html( $label ); ?></span>
                </h3>
                <p class="nx-mcp-hero-text">
                    <?php esc_html_e( 'Connect NotificationX to Claude, ChatGPT, Cursor and other AI assistants through a built-in MCP server, so you can manage notifications and read analytics in plain language. It is off by default and only administrators can use it.', 'notificationx' ); ?>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Connector URL + token cards with copy/reveal controls.
     *
     * @return string
     */
    protected function connection_html() {
        $url   = $this->connector_url();
        $token = Pairing::get_instance()->site_token();
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
                    <code class="nx-mcp-value nx-mcp-token" data-token="<?php echo esc_attr( $token ); ?>">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</code>
                    <button type="button" class="nx-mcp-copy" onclick="nxMcpReveal(this)"><?php esc_html_e( 'Show', 'notificationx' ); ?></button>
                    <button type="button" class="nx-mcp-copy" onclick="nxMcpCopy(this,'<?php echo esc_js( $token ); ?>')"><?php esc_html_e( 'Copy', 'notificationx' ); ?></button>
                </div>
                <p class="nx-mcp-hint"><?php esc_html_e( 'For token-based clients (ChatGPT, Cursor): send it as an Authorization: Bearer header. Keep it secret.', 'notificationx' ); ?></p>
            </div>
        </div>
        <div class="nx-mcp-actions">
            <button type="button" class="nx-mcp-btn nx-mcp-btn-secondary" onclick="nxMcpAction(this,'test',{success:'<?php echo esc_js( __( 'Connection test passed — the MCP server is reachable and exposing its tools.', 'notificationx' ) ); ?>'})"><?php esc_html_e( 'Test connection', 'notificationx' ); ?></button>
            <button type="button" class="nx-mcp-btn nx-mcp-btn-ghost" onclick="nxMcpAction(this,'rotate',{confirm:'<?php echo esc_js( __( 'Reset the connection token? Existing clients will need the new token to reconnect.', 'notificationx' ) ); ?>',reload:true,success:'<?php echo esc_js( __( 'A new connection token was generated.', 'notificationx' ) ); ?>'})"><?php esc_html_e( 'Reset token', 'notificationx' ); ?></button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Per-client setup cards.
     *
     * @return string
     */
    protected function clients_html() {
        $url = esc_html( $this->connector_url() );
        ob_start();
        ?>
        <div class="nx-mcp-clients">
            <div class="nx-mcp-client">
                <div class="nx-mcp-client-name"><span class="nx-mcp-client-ic">&#129302;</span> <?php esc_html_e( 'Claude', 'notificationx' ); ?><span class="nx-mcp-tag"><?php esc_html_e( 'OAuth', 'notificationx' ); ?></span></div>
                <ol class="nx-mcp-steps">
                    <li><?php esc_html_e( 'In Claude, add a custom connector.', 'notificationx' ); ?></li>
                    <li><?php esc_html_e( 'Paste the Connector URL above.', 'notificationx' ); ?></li>
                    <li><?php esc_html_e( 'Approve the connection when prompted — you sign in here, no token needed.', 'notificationx' ); ?></li>
                </ol>
            </div>
            <div class="nx-mcp-client">
                <div class="nx-mcp-client-name"><span class="nx-mcp-client-ic">&#128172;</span> <?php esc_html_e( 'ChatGPT', 'notificationx' ); ?><span class="nx-mcp-tag"><?php esc_html_e( 'Token', 'notificationx' ); ?></span></div>
                <ol class="nx-mcp-steps">
                    <li><?php esc_html_e( 'Settings → Connectors → Add a custom connector.', 'notificationx' ); ?></li>
                    <li><?php /* translators: %s: connector URL */ printf( esc_html__( 'Use the URL %s.', 'notificationx' ), '<code>' . $url . '</code>' ); ?></li>
                    <li><?php esc_html_e( 'Provide the connection token as a Bearer credential.', 'notificationx' ); ?></li>
                </ol>
            </div>
            <div class="nx-mcp-client">
                <div class="nx-mcp-client-name"><span class="nx-mcp-client-ic">&#128421;</span> <?php esc_html_e( 'Cursor &amp; others', 'notificationx' ); ?><span class="nx-mcp-tag"><?php esc_html_e( 'Token', 'notificationx' ); ?></span></div>
                <ol class="nx-mcp-steps">
                    <li><?php esc_html_e( 'Add an MCP server with the Connector URL above.', 'notificationx' ); ?></li>
                    <li><?php esc_html_e( 'Set the Authorization header to: Bearer <token>.', 'notificationx' ); ?></li>
                    <li><?php esc_html_e( 'Confirm the install when the client asks.', 'notificationx' ); ?></li>
                </ol>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * The list of currently connected AI apps (pairing token + OAuth clients).
     *
     * @return string
     */
    protected function connected_apps_html() {
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

        ob_start();
        if ( empty( $apps ) ) {
            echo '<p class="nx-mcp-empty">' . esc_html__( 'No AI clients are connected yet.', 'notificationx' ) . '</p>';
        } else {
            echo '<div class="nx-mcp-apps">';
            foreach ( $apps as $app ) {
                $scope_class = $app['read_only'] ? 'nx-mcp-scope-ro' : 'nx-mcp-scope-rw';
                $scope_label = $app['read_only'] ? __( 'Read-only', 'notificationx' ) : __( 'Read & write', 'notificationx' );
                ?>
                <div class="nx-mcp-app">
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
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page check.
        if ( ! is_admin() || empty( $_GET['page'] ) || 'nx-settings' !== $_GET['page'] ) {
            return;
        }
        $nonce = wp_create_nonce( 'wp_rest' );
        $urls  = array(
            'test'       => esc_url_raw( rest_url( 'notificationx/v1/mcp/self-test' ) ),
            'rotate'     => esc_url_raw( rest_url( 'notificationx/v1/mcp/rotate' ) ),
            'disconnect' => esc_url_raw( rest_url( 'notificationx/v1/mcp/disconnect' ) ),
            'revoke'     => esc_url_raw( rest_url( 'notificationx/v1/mcp/apps/revoke' ) ),
        );
        ?>
        <style id="nx-mcp-panel-css">
            .nx-mcp-hero{display:flex;gap:14px;align-items:flex-start}
            .nx-mcp-hero-icon{font-size:26px;line-height:1}
            .nx-mcp-hero-title{margin:0 0 6px;font-size:18px;display:flex;align-items:center;gap:10px}
            .nx-mcp-hero-text{margin:0;color:#50575e;max-width:640px}
            .nx-mcp-badge{font-size:11px;font-weight:600;padding:2px 10px;border-radius:999px;text-transform:uppercase;letter-spacing:.02em}
            .nx-mcp-badge-off{background:#e2e4e7;color:#50575e}
            .nx-mcp-badge-active{background:#e5f6ea;color:#1a7f37}
            .nx-mcp-badge-setup{background:#fcf3e3;color:#996800}
            .nx-mcp-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
            @media(max-width:782px){.nx-mcp-grid{grid-template-columns:1fr}}
            .nx-mcp-card{border:1px solid #e0e0e0;border-radius:10px;padding:14px 16px;background:#fff}
            .nx-mcp-card-label{display:block;font-weight:600;font-size:12px;color:#50575e;text-transform:uppercase;letter-spacing:.03em;margin-bottom:8px}
            .nx-mcp-copyrow{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
            .nx-mcp-value{background:#f6f7f7;border:1px solid #e0e0e0;border-radius:6px;padding:6px 10px;font-size:12px;flex:1;min-width:0;overflow:auto;white-space:nowrap}
            .nx-mcp-copy{cursor:pointer;border:1px solid #c3c4c7;background:#f6f7f7;border-radius:6px;padding:6px 12px;font-size:12px;font-weight:600;color:#2c3338}
            .nx-mcp-copy:hover{background:#eef0f1}
            .nx-mcp-hint{margin:8px 0 0;color:#787c82;font-size:12px}
            .nx-mcp-clients{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
            @media(max-width:960px){.nx-mcp-clients{grid-template-columns:1fr}}
            .nx-mcp-client{border:1px solid #e0e0e0;border-radius:10px;padding:14px 16px;background:#fff}
            .nx-mcp-client-name{font-weight:600;display:flex;align-items:center;gap:8px;margin-bottom:8px}
            .nx-mcp-tag{font-size:10px;font-weight:600;background:#f0eefe;color:#6a4bff;padding:2px 8px;border-radius:999px;text-transform:uppercase}
            .nx-mcp-steps{margin:0;padding-left:18px;color:#50575e;font-size:13px;line-height:1.7}
            .nx-mcp-apps{display:flex;flex-direction:column;gap:10px}
            .nx-mcp-app{display:flex;justify-content:space-between;align-items:center;border:1px solid #e0e0e0;border-radius:8px;padding:10px 14px;background:#fff}
            .nx-mcp-app-info{display:flex;align-items:center;gap:10px}
            .nx-mcp-scope{font-size:11px;font-weight:600;padding:2px 8px;border-radius:999px}
            .nx-mcp-scope-ro{background:#eef0f1;color:#50575e}
            .nx-mcp-scope-rw{background:#e5f6ea;color:#1a7f37}
            .nx-mcp-revoke{cursor:pointer;border:1px solid #d63638;background:#fff;color:#d63638;border-radius:6px;padding:5px 12px;font-size:12px;font-weight:600}
            .nx-mcp-revoke:hover{background:#d63638;color:#fff}
            .nx-mcp-empty{color:#787c82;font-style:italic}
            .nx-mcp-health{display:flex;flex-direction:column;gap:8px}
            .nx-mcp-health-row{display:flex;align-items:center;gap:8px;color:#2c3338;font-size:13px}
            .nx-mcp-dot{width:9px;height:9px;border-radius:50%;display:inline-block;flex:none}
            .nx-mcp-dot-good{background:#1a7f37}
            .nx-mcp-dot-warn{background:#dba617}
            .nx-mcp-client-ic{font-size:18px;line-height:1}
            .nx-mcp-actions{display:flex;gap:10px;margin-top:16px;flex-wrap:wrap}
            .nx-mcp-btn{cursor:pointer;border-radius:6px;padding:8px 16px;font-size:13px;font-weight:600;border:1px solid transparent;line-height:1.2}
            .nx-mcp-btn[disabled]{opacity:.6;cursor:default}
            .nx-mcp-btn-secondary{background:#6a4bff;color:#fff}
            .nx-mcp-btn-secondary:hover{background:#583fd6}
            .nx-mcp-btn-ghost{background:#fff;color:#2c3338;border-color:#c3c4c7}
            .nx-mcp-btn-ghost:hover{background:#f6f7f7}
            .nx-mcp-btn-danger{background:#d63638;color:#fff;border-color:#d63638}
            .nx-mcp-btn-danger:hover{background:#b32d2e}
            .nx-mcp-danger{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-top:16px;padding:14px 16px;border:1px solid #f0c4c4;background:#fcf0f0;border-radius:10px;flex-wrap:wrap}
            .nx-mcp-danger-text{display:flex;flex-direction:column;gap:2px}
            .nx-mcp-danger-text strong{color:#8a1f21}
            .nx-mcp-danger-text span{color:#a15b5b;font-size:12px}
            .nx-mcp-toast{position:fixed;bottom:28px;right:28px;z-index:100001;padding:12px 18px;border-radius:8px;color:#fff;font-size:13px;font-weight:600;box-shadow:0 8px 28px rgba(0,0,0,.2);opacity:0;transform:translateY(12px);transition:opacity .28s,transform .28s;max-width:380px}
            .nx-mcp-toast-in{opacity:1;transform:translateY(0)}
            .nx-mcp-toast-success{background:#1a7f37}
            .nx-mcp-toast-error{background:#d63638}
        </style>
        <script id="nx-mcp-panel-js">
            window.nxMcpData = { urls: <?php echo wp_json_encode( $urls ); ?>, nonce: <?php echo wp_json_encode( $nonce ); ?> };
            window.nxMcpToast = function(type, msg){
                var t = document.createElement('div');
                t.className = 'nx-mcp-toast nx-mcp-toast-' + (type === 'error' ? 'error' : 'success');
                t.textContent = msg;
                document.body.appendChild(t);
                requestAnimationFrame(function(){ t.classList.add('nx-mcp-toast-in'); });
                setTimeout(function(){ t.classList.remove('nx-mcp-toast-in'); setTimeout(function(){ t.remove(); }, 320); }, 3600);
            };
            window.nxMcpCopy = function(btn, text){
                var done = function(){ var o = btn.textContent; btn.textContent = '✓'; setTimeout(function(){ btn.textContent = o; }, 1200); };
                if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(text).then(done, done); }
                else { var t=document.createElement('textarea'); t.value=text; document.body.appendChild(t); t.select(); try{document.execCommand('copy');}catch(e){} document.body.removeChild(t); done(); }
            };
            window.nxMcpReveal = function(btn){
                var code = btn.parentNode.querySelector('.nx-mcp-token'); if(!code) return;
                if (code.dataset.shown === '1'){ code.textContent = '••••••••••••'; code.dataset.shown='0'; btn.textContent='Show'; }
                else { code.textContent = code.dataset.token || ''; code.dataset.shown='1'; btn.textContent='Hide'; }
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
                    if (res && res.status === 'error'){ nxMcpToast('error', res.message || 'Something went wrong.'); return; }
                    nxMcpToast('success', opts.success || (res && res.message) || 'Done.');
                    if (opts.reload){ setTimeout(function(){ window.location.reload(); }, 900); }
                }).catch(function(){ btn.disabled = false; btn.textContent = old; nxMcpToast('error', 'Request failed.'); });
            };
            window.nxMcpRevoke = function(btn, type, clientId){
                nxMcpAction(btn, 'revoke', {
                    confirm: 'Revoke this connection? The client will need to reconnect.',
                    body: { type: type, client_id: clientId },
                    reload: true,
                    success: 'Connection revoked.'
                });
            };
            // Keep the status badge in sync with the enable toggle, live.
            document.addEventListener('change', function(e){
                if (!e.target || e.target.name !== 'enable_mcp') return;
                var badge = document.querySelector('.nx-mcp-badge');
                if (!badge) return;
                var on = !!e.target.checked;
                badge.textContent = on ? '<?php echo esc_js( __( 'Active', 'notificationx' ) ); ?>' : '<?php echo esc_js( __( 'Off', 'notificationx' ) ); ?>';
                badge.className = 'nx-mcp-badge nx-mcp-badge-' + (on ? 'active' : 'off');
            });
        </script>
        <?php
    }

    /* --------------------------------------------------------------------- */
    /* Helpers                                                               */
    /* --------------------------------------------------------------------- */

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
