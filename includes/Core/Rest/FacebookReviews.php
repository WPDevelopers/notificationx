<?php
/**
 * REST routes for the managed Facebook Reviews integration.
 *
 * @package NotificationX\Core\Rest
 */

namespace NotificationX\Core\Rest;

use NotificationX\Extensions\Facebook\FacebookReviews as FacebookReviewsExtension;
use NotificationX\Extensions\Facebook\FacebookPageFinder;
use NotificationX\Extensions\Facebook\FacebookReviewsManaged;
use NotificationX\GetInstance;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Admin routes (used by the builder/settings field) proxy to the NotificationX
 * API with the site token, so the token never reaches the browser:
 *
 *   POST facebook-reviews/oauth-start     {return_url}            → {authorize_url}
 *   GET  facebook-reviews/pages           ?session_id             → {pages:[{id,name}]}
 *   POST facebook-reviews/pages-connect   {session_id, page_id}   → {connection}
 *   GET  facebook-reviews/connections     [?fresh=1]              → {site, connections, providers}
 *   POST facebook-reviews/disconnect-page {connection_id}         → {ok}
 *   POST facebook-reviews/sync            {connection_id, nx_id?}  → {queued, stored}
 *   POST facebook-reviews/attest-start    {page_url}               → {page, token, methods}
 *   POST facebook-reviews/attest-verify   {page_url}               → {connection, method}
 *   GET  facebook-reviews/discover        [?fresh=1]               → {pages:[{url,handle,source}]}
 *   POST facebook-reviews/page-preview    {page_url}               → {preview}
 *   POST facebook-reviews/page-connect    {page_url}               → {connection}
 *
 * Inbound webhook from the API (HMAC-SHA256 signed with the site token):
 *
 *   POST social-review  → 200 stored | 409 duplicate | 401 bad signature | 422 malformed
 *
 * @method static FacebookReviews get_instance($args = null)
 */
class FacebookReviews {
    use GetInstance;

    const WEBHOOK_ROUTE = 'social-review';
    /** Replay guard: delivery ids seen in the last 24h. */
    const DELIVERY_TTL = DAY_IN_SECONDS;

    public $namespace = 'notificationx/v1';

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        $admin = ['permission_callback' => [$this, 'admin_permission']];

        register_rest_route($this->namespace, '/facebook-reviews/oauth-start', $admin + ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'oauth_start']]);
        register_rest_route($this->namespace, '/facebook-reviews/pages', $admin + ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'pages']]);
        register_rest_route($this->namespace, '/facebook-reviews/pages-connect', $admin + ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'pages_connect']]);
        register_rest_route($this->namespace, '/facebook-reviews/connections', $admin + ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'connections']]);
        register_rest_route($this->namespace, '/facebook-reviews/disconnect-page', $admin + ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'disconnect_page']]);
        register_rest_route($this->namespace, '/facebook-reviews/sync', $admin + ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'sync']]);
        register_rest_route($this->namespace, '/facebook-reviews/attest-start', $admin + ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'attest_start']]);
        register_rest_route($this->namespace, '/facebook-reviews/attest-verify', $admin + ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'attest_verify']]);
        register_rest_route($this->namespace, '/facebook-reviews/discover', $admin + ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'discover']]);
        register_rest_route($this->namespace, '/facebook-reviews/page-preview', $admin + ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'page_preview']]);
        register_rest_route($this->namespace, '/facebook-reviews/page-connect', $admin + ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'page_connect']]);

        register_rest_route($this->namespace, '/' . self::WEBHOOK_ROUTE, [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'webhook'],
            // Authentication is the HMAC signature, checked in webhook().
            'permission_callback' => '__return_true',
        ]);
    }

    public function admin_permission() {
        return current_user_can('edit_notificationx');
    }

    /**
     * Begins the Facebook login. Registers the site with the API on first use
     * (this click is the consent) and returns the Meta authorize URL.
     */
    public function oauth_start(WP_REST_Request $request) {
        $ensured = FacebookReviewsManaged::ensure_connected();
        if (empty($ensured['ok'])) {
            return $this->error('api_unreachable', $ensured['message'], 502);
        }
        $return_url = esc_url_raw((string) $request->get_param('return_url'));
        if ('' === $return_url || wp_parse_url($return_url, PHP_URL_HOST) !== wp_parse_url(home_url(), PHP_URL_HOST)) {
            $return_url = admin_url('admin.php?page=nx-settings&tab=tab-api-integrations');
        }
        $result = FacebookReviewsManaged::oauth_start($return_url);
        if (empty($result['ok']) || empty($result['body']['authorize_url'])) {
            return $this->api_error($result);
        }
        $authorize_url = (string) $result['body']['authorize_url'];
        if (0 !== strpos($authorize_url, 'https://www.facebook.com/') && 0 !== strpos($authorize_url, 'https://facebook.com/')) {
            return $this->error('unexpected_response', __('The NotificationX API returned an unexpected login URL.', 'notificationx'), 502);
        }
        return rest_ensure_response(['authorize_url' => $authorize_url, 'session_id' => (string) $result['body']['session_id']]);
    }

    public function pages(WP_REST_Request $request) {
        $session_id = sanitize_text_field((string) $request->get_param('session_id'));
        if ('' === $session_id) {
            return $this->error('missing_session', __('Missing login session.', 'notificationx'), 400);
        }
        $result = FacebookReviewsManaged::pages($session_id);
        if (empty($result['ok'])) {
            return $this->api_error($result);
        }
        $pages = [];
        foreach ((array) ($result['body']['pages'] ?? []) as $page) {
            if (is_array($page) && !empty($page['id'])) {
                $pages[] = ['id' => sanitize_text_field((string) $page['id']), 'name' => sanitize_text_field((string) ($page['name'] ?? ''))];
            }
        }
        return rest_ensure_response(['pages' => $pages]);
    }

    public function pages_connect(WP_REST_Request $request) {
        $session_id = sanitize_text_field((string) $request->get_param('session_id'));
        $page_id    = sanitize_text_field((string) $request->get_param('page_id'));
        if ('' === $session_id || '' === $page_id) {
            return $this->error('missing_params', __('Missing login session or page.', 'notificationx'), 400);
        }
        $result = FacebookReviewsManaged::pages_connect($session_id, $page_id);
        if (empty($result['ok']) || empty($result['body']['connection'])) {
            return $this->api_error($result);
        }
        return rest_ensure_response(['connection' => $this->public_connection($result['body']['connection'])]);
    }

    public function connections(WP_REST_Request $request) {
        $site = FacebookReviewsManaged::site_status();
        if (empty($site['connected'])) {
            // Not registered yet, so we cannot ask the API what it offers.
            // Advertise both and let the chosen one report if it is unavailable —
            // hiding a working option would be worse than showing a dead one.
            return rest_ensure_response(['site' => $site, 'connections' => [], 'providers' => [], 'connect_modes' => ['oauth', 'attested', 'open']]);
        }
        $result = FacebookReviewsManaged::connections('', (bool) $request->get_param('fresh'));
        if (empty($result['ok'])) {
            return $this->api_error($result);
        }
        $connections = array_map([$this, 'public_connection'], array_values(array_filter((array) ($result['body']['connections'] ?? []), 'is_array')));
        $modes = array_values(array_intersect(
            array_map('sanitize_key', (array) ($result['body']['connect_modes'] ?? [])),
            ['oauth', 'attested', 'open']
        ));
        return rest_ensure_response([
            'site'          => FacebookReviewsManaged::site_status(),
            'connections'   => $connections,
            'providers'     => (array) ($result['body']['providers'] ?? []),
            // An API too old to report this only ever supported the login.
            'connect_modes' => $modes ?: ['oauth'],
        ]);
    }

    public function disconnect_page(WP_REST_Request $request) {
        $connection_id = sanitize_text_field((string) $request->get_param('connection_id'));
        if ('' === $connection_id) {
            return $this->error('missing_params', __('Missing connection.', 'notificationx'), 400);
        }
        $result = FacebookReviewsManaged::disconnect_page($connection_id);
        if (empty($result['ok'])) {
            return $this->api_error($result);
        }
        return rest_ensure_response(['ok' => true]);
    }

    /**
     * Facebook Pages this site already advertises, so the admin can press a
     * button instead of filling in a field. Local only — option reads plus at
     * most one request to the site's own homepage.
     */
    public function discover(WP_REST_Request $request) {
        return rest_ensure_response(['pages' => FacebookPageFinder::discover((bool) $request->get_param('fresh'))]);
    }

    /** Look up a pasted address without connecting it. */
    public function page_preview(WP_REST_Request $request) {
        $ensured = FacebookReviewsManaged::ensure_connected();
        if (empty($ensured['ok'])) {
            return $this->error('api_unreachable', $ensured['message'], 502);
        }
        $page_url = trim((string) $request->get_param('page_url'));
        if ('' === $page_url) {
            return $this->error('missing_page_url', __('Enter the address of your Facebook Page.', 'notificationx'), 400);
        }
        $result = FacebookReviewsManaged::page_preview($page_url);
        if (empty($result['ok']) || empty($result['body']['preview'])) {
            return $this->api_error($result);
        }
        $preview = $result['body']['preview'];
        return rest_ensure_response(['preview' => [
            'handle'         => sanitize_text_field((string) ($preview['handle'] ?? '')),
            'url'            => esc_url_raw((string) ($preview['url'] ?? ''), ['https']),
            'name'           => sanitize_text_field((string) ($preview['name'] ?? '')),
            'rating_overall' => isset($preview['rating_overall']) && null !== $preview['rating_overall'] ? (float) $preview['rating_overall'] : null,
            'rating_count'   => isset($preview['rating_count']) && null !== $preview['rating_count'] ? (int) $preview['rating_count'] : null,
        ]]);
    }

    /** Connect the Page at this address. */
    public function page_connect(WP_REST_Request $request) {
        $page_url = trim((string) $request->get_param('page_url'));
        if ('' === $page_url) {
            return $this->error('missing_page_url', __('Enter the address of your Facebook Page.', 'notificationx'), 400);
        }
        $result = FacebookReviewsManaged::page_connect($page_url);
        if (empty($result['ok']) || empty($result['body']['connection'])) {
            return $this->api_error($result);
        }
        FacebookPageFinder::forget();
        return rest_ensure_response(['connection' => $this->public_connection($result['body']['connection'])]);
    }

    /**
     * Begin owner-attested connect for a pasted Page address.
     *
     * Registers the site with the API on first use, exactly as the Facebook
     * login does — this click is the consent.
     */
    public function attest_start(WP_REST_Request $request) {
        $ensured = FacebookReviewsManaged::ensure_connected();
        if (empty($ensured['ok'])) {
            return $this->error('api_unreachable', $ensured['message'], 502);
        }
        $page_url = trim((string) $request->get_param('page_url'));
        if ('' === $page_url) {
            return $this->error('missing_page_url', __('Enter the address of your Facebook Page.', 'notificationx'), 400);
        }

        $result = FacebookReviewsManaged::attest_start($page_url);
        if (empty($result['ok'])) {
            return $this->api_error($result);
        }
        $body = $result['body'];
        return rest_ensure_response([
            'page'       => [
                'handle' => sanitize_text_field((string) ($body['page']['handle'] ?? '')),
                'url'    => esc_url_raw((string) ($body['page']['url'] ?? ''), ['https']),
            ],
            'token'      => sanitize_text_field((string) ($body['token'] ?? '')),
            'expires_at' => (int) ($body['expires_at'] ?? 0),
            'methods'    => array_map(static function ($method) {
                return [
                    'id'          => sanitize_key((string) ($method['id'] ?? '')),
                    'label'       => sanitize_text_field((string) ($method['label'] ?? '')),
                    'description' => sanitize_text_field((string) ($method['description'] ?? '')),
                    'value'       => sanitize_text_field((string) ($method['value'] ?? '')),
                ];
            }, array_values(array_filter((array) ($body['methods'] ?? []), 'is_array'))),
        ]);
    }

    /** Ask the API to look for the proof and connect the Page if it is there. */
    public function attest_verify(WP_REST_Request $request) {
        $page_url = trim((string) $request->get_param('page_url'));
        if ('' === $page_url) {
            return $this->error('missing_page_url', __('Enter the address of your Facebook Page.', 'notificationx'), 400);
        }

        $result = FacebookReviewsManaged::attest_verify($page_url);
        if (empty($result['ok']) || empty($result['body']['connection'])) {
            // The API's message here is the instruction the customer needs —
            // which code to add, or which domain to set — so it is passed
            // through verbatim rather than replaced with something generic.
            return $this->api_error($result);
        }
        return rest_ensure_response([
            'connection' => $this->public_connection($result['body']['connection']),
            'method'     => sanitize_key((string) ($result['body']['method'] ?? '')),
        ]);
    }

    /**
     * "Sync now": ask the API to collect this Page again, and pull whatever it
     * already holds into the campaign right away.
     *
     * The two halves are deliberately separate. Collection is asynchronous —
     * asking for it returns before any new review exists — so pulling in the
     * same call is what makes the button do something visible immediately:
     * anything already collected (by an earlier run, or for another site
     * showing the same Page) lands now, and the fresh collection follows on its
     * own. Reporting both counts keeps that honest rather than implying the
     * refresh finished.
     */
    public function sync(WP_REST_Request $request) {
        $connection_id = sanitize_text_field((string) $request->get_param('connection_id'));
        if ('' === $connection_id) {
            return $this->error('missing_params', __('Missing connection.', 'notificationx'), 400);
        }

        $refresh = FacebookReviewsManaged::refresh($connection_id);
        // A 429 here is expected and harmless — the API is protecting its
        // collection budget. The pull below still runs, so the user is not left
        // with nothing.
        $queued = !empty($refresh['ok']);
        if (!$queued && !in_array((string) ($refresh['error'] ?? ''), ['rate_limited', 'network'], true)) {
            return $this->api_error($refresh);
        }

        $extension = FacebookReviewsExtension::get_instance();
        $stored    = 0;
        $nx_id     = absint($request->get_param('nx_id'));
        $campaigns = $nx_id > 0 ? [['nx_id' => $nx_id]] : $extension->campaigns_for_connection($connection_id);
        foreach ($campaigns as $campaign) {
            $stored += (int) $extension->sync_reviews($campaign['nx_id'], $connection_id, true);
        }

        return rest_ensure_response([
            'queued'     => $queued,
            'stored'     => $stored,
            'campaigns'  => count($campaigns),
            'message'    => $queued
                ? __('Refresh requested. New reviews appear as the NotificationX API collects them.', 'notificationx')
                : __('A refresh was requested recently. Any reviews already collected have been imported.', 'notificationx'),
        ]);
    }

    /**
     * Inbound review from the NotificationX API.
     */
    public function webhook(WP_REST_Request $request) {
        $raw       = (string) $request->get_body();
        $timestamp = (string) $request->get_header('x_nx_timestamp');
        $delivery  = sanitize_text_field((string) $request->get_header('x_nx_delivery_id'));
        $signature = (string) $request->get_header('x_nx_signature');

        $verified = FacebookReviewsManaged::verify_webhook($raw, $timestamp, $delivery, $signature);
        if (true !== $verified) {
            return $this->error($verified, __('Webhook signature could not be verified.', 'notificationx'), 401);
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload) || empty($payload['review_id']) || empty($payload['connection_id']) || empty($payload['page_id'])) {
            return $this->error('malformed_payload', __('Malformed review payload.', 'notificationx'), 422);
        }
        if (isset($payload['event']) && 'social_review.created' !== $payload['event']) {
            return $this->error('unsupported_event', __('Unsupported event.', 'notificationx'), 422);
        }

        $replay_key = 'nx_fbr_dlv_' . md5($delivery);
        if (get_transient($replay_key)) {
            return $this->error('duplicate_delivery', __('Already processed.', 'notificationx'), 409);
        }
        set_transient($replay_key, 1, self::DELIVERY_TTL);

        $stored = FacebookReviewsExtension::get_instance()->ingest_review($payload);
        return new WP_REST_Response(['ok' => true, 'campaigns' => $stored], 200);
    }

    /** Strips anything the browser does not need. */
    protected function public_connection($connection) {
        return [
            'connection_id'    => sanitize_text_field((string) ($connection['connection_id'] ?? '')),
            'provider'         => sanitize_key((string) ($connection['provider'] ?? 'facebook')),
            'page_id'          => sanitize_text_field((string) ($connection['page_id'] ?? '')),
            'page_name'        => sanitize_text_field((string) ($connection['page_name'] ?? '')),
            'status'           => sanitize_key((string) ($connection['status'] ?? '')),
            'rating_overall'   => isset($connection['rating_overall']) && null !== $connection['rating_overall'] ? (float) $connection['rating_overall'] : null,
            'rating_count'     => isset($connection['rating_count']) && null !== $connection['rating_count'] ? (int) $connection['rating_count'] : null,
            'individual_reviews' => !empty($connection['capabilities']['individual_reviews']),
            'connect_mode'     => sanitize_key((string) ($connection['connect_mode'] ?? 'oauth')),
            'last_synced_at'   => isset($connection['last_synced_at']) ? sanitize_text_field((string) $connection['last_synced_at']) : null,
            'last_sync_error'  => isset($connection['last_sync_error']) ? sanitize_key((string) $connection['last_sync_error']) : null,
        ];
    }

    protected function api_error($result) {
        $code    = !empty($result['error']) ? $result['error'] : 'api_error';
        $status  = !empty($result['code']) && $result['code'] >= 400 && $result['code'] < 600 ? (int) $result['code'] : 502;
        $message = !empty($result['message']) ? $result['message'] : __('The NotificationX API could not complete the request.', 'notificationx');
        return $this->error($code, $message, $status);
    }

    protected function error($code, $message, $status) {
        return new WP_Error('nx_facebook_reviews_' . sanitize_key($code), $message, ['status' => $status, 'code' => sanitize_key($code)]);
    }
}
