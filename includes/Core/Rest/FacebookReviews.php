<?php
/**
 * REST routes for the managed Facebook Reviews integration.
 *
 * @package NotificationX\Core\Rest
 */

namespace NotificationX\Core\Rest;

use NotificationX\Extensions\Facebook\FacebookReviews as FacebookReviewsExtension;
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
            return rest_ensure_response(['site' => $site, 'connections' => [], 'providers' => []]);
        }
        $result = FacebookReviewsManaged::connections('', (bool) $request->get_param('fresh'));
        if (empty($result['ok'])) {
            return $this->api_error($result);
        }
        $connections = array_map([$this, 'public_connection'], array_values(array_filter((array) ($result['body']['connections'] ?? []), 'is_array')));
        return rest_ensure_response(['site' => FacebookReviewsManaged::site_status(), 'connections' => $connections, 'providers' => (array) ($result['body']['providers'] ?? [])]);
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
