<?php
/**
 * Client for the managed Facebook Reviews service on the NotificationX API.
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Facebook;

use NotificationX\NotificationX;

/**
 * Talks to api.notificationx.com/facebook-reviews/v1.
 *
 * The API owns the Meta app and every Facebook credential. This site only
 * holds a per-site Bearer token (issued by connect.php) which authenticates
 * the site on every call and is the HMAC key for the reviews webhook the API
 * sends back to /wp-json/notificationx/v1/social-review.
 *
 * Site handshake:   connect()  → POST connect.php → token (stored in OPT_AUTH)
 * Facebook flow:    oauth_start() → pages() → pages_connect() → status()
 * Teardown:         disconnect_page() / disconnect()
 */
class FacebookReviewsManaged {
    const DEFAULT_ENDPOINT = 'https://api.notificationx.com/facebook-reviews/v1';

    /** Option holding [token, site_id, home_url, fingerprint, tier, license_status, connected_at]; autoload off. */
    const OPT_AUTH = 'nx_facebook_reviews_managed_auth';
    /** Cached connection list from status.php (transient). */
    const CACHE_STATUS = 'nx_facebook_reviews_managed_status';
    /** Cached review page + its ETag, per connection (transient prefix). */
    const CACHE_REVIEWS = 'nx_facebook_reviews_page_';
    /** How long a fetched review page is reused before we ask the API again. */
    const REVIEWS_CACHE_TTL = 300;
    /** Accepted clock skew for webhook timestamps, seconds. */
    const WEBHOOK_SKEW = 300;

    public static function endpoint() {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
        $endpoint = untrailingslashit((string) apply_filters('nx_facebook_reviews_managed_endpoint', self::DEFAULT_ENDPOINT));
        // The Bearer token rides every request: plain http is only honoured for local/dev hosts.
        if (0 !== stripos($endpoint, 'https://') && !self::is_local_host((string) wp_parse_url($endpoint, PHP_URL_HOST))) {
            return self::DEFAULT_ENDPOINT;
        }
        return $endpoint;
    }

    public static function is_local_host($host) {
        $host = strtolower((string) $host);
        return in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true) || '.test' === substr($host, -5) || '.local' === substr($host, -6);
    }

    /**
     * Install fingerprint: stable for this install, meaningless elsewhere.
     */
    public static function compute_fingerprint() {
        $salt = defined('AUTH_SALT') ? AUTH_SALT : '';
        return 'fp_' . substr(hash('sha256', home_url() . '|' . $salt . '|' . get_option('admin_email')), 0, 40);
    }

    public static function get_auth() {
        $auth = get_option(self::OPT_AUTH);
        return is_array($auth) && !empty($auth['token']) ? $auth : [];
    }

    public static function is_connected() {
        return [] !== self::get_auth();
    }

    /**
     * Metadata safe for the admin UI. Never includes the token.
     */
    public static function site_status() {
        $auth = self::get_auth();
        return [
            'connected'      => !empty($auth),
            'site_id'        => isset($auth['site_id']) ? $auth['site_id'] : '',
            'tier'           => isset($auth['tier']) ? $auth['tier'] : '',
            'license_status' => isset($auth['license_status']) ? $auth['license_status'] : '',
            'connected_at'   => isset($auth['connected_at']) ? (int) $auth['connected_at'] : 0,
            'endpoint'       => self::endpoint(),
        ];
    }

    /**
     * Registers (or re-registers) this site with the API and stores the token.
     * Only ever triggered by an admin action.
     *
     * @return array ['ok' => bool, 'message' => string]
     */
    public static function connect() {
        $payload = [
            'site_url'       => home_url(),
            'fingerprint'    => self::compute_fingerprint(),
            'plugin_version' => defined('NOTIFICATIONX_VERSION') ? NOTIFICATIONX_VERSION : '',
            'wp_version'     => get_bloginfo('version'),
            'tier'           => NotificationX::get_instance()->is_pro() ? 'pro' : 'free',
            'license_key'    => self::license_key(),
        ];
        $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];
        $auth    = self::get_auth();
        if (!empty($auth['token'])) {
            // Lets the API keep our tenant when the fingerprint changed (salts rotated).
            $headers['Authorization'] = 'Bearer ' . $auth['token'];
        }

        $response = wp_remote_post(self::endpoint() . '/connect.php', [
            'timeout' => 15,
            'headers' => $headers,
            'body'    => wp_json_encode($payload),
        ]);
        if (is_wp_error($response)) {
            return ['ok' => false, 'message' => $response->get_error_message()];
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        if (200 !== $code || !is_array($body) || empty($body['token'])) {
            $message = is_array($body) && !empty($body['message'])
                ? (string) $body['message']
                /* translators: %d: HTTP status code */
                : sprintf(__('Could not connect to the NotificationX API (HTTP %d).', 'notificationx'), $code);
            return ['ok' => false, 'message' => $message];
        }

        update_option(self::OPT_AUTH, [
            'token'          => (string) $body['token'],
            'site_id'        => (string) (isset($body['site_id']) ? $body['site_id'] : ''),
            'home_url'       => home_url(),
            'fingerprint'    => $payload['fingerprint'],
            'tier'           => 'pro' === (isset($body['tier']) ? $body['tier'] : '') ? 'pro' : 'free',
            'license_status' => sanitize_key((string) (isset($body['license_status']) ? $body['license_status'] : '')),
            'connected_at'   => (int) (isset($body['issued_at']) ? $body['issued_at'] : time()),
        ], false);
        delete_transient(self::CACHE_STATUS);

        return ['ok' => true];
    }

    /**
     * Makes sure a site token exists, registering on demand. Used right before
     * an admin-initiated action (Connect Facebook), so it is consent-backed.
     */
    public static function ensure_connected() {
        if (self::is_connected()) {
            return ['ok' => true];
        }
        return self::connect();
    }

    /**
     * Revokes the site token (and every Facebook connection) on the API, then forgets it.
     */
    public static function disconnect() {
        if (self::is_connected()) {
            self::request('POST', '/disconnect.php', []);
        }
        delete_option(self::OPT_AUTH);
        delete_transient(self::CACHE_STATUS);
    }

    public static function headers() {
        $headers = [
            'Content-Type'         => 'application/json',
            'Accept'               => 'application/json',
            'X-NotificationX-Site' => home_url(),
        ];
        $auth = self::get_auth();
        if (!empty($auth['token'])) {
            $headers['Authorization']               = 'Bearer ' . $auth['token'];
            // Some hosts strip Authorization before PHP sees it; the API accepts this too.
            $headers['X-NotificationX-Token']       = $auth['token'];
            $headers['X-NotificationX-Fingerprint'] = !empty($auth['fingerprint']) ? $auth['fingerprint'] : self::compute_fingerprint();
        }
        return $headers;
    }

    /**
     * Authenticated JSON request to the API.
     *
     * @param array $extra_headers merged over the standard auth headers
     * @return array ['ok' => bool, 'code' => int, 'body' => array, 'headers' => array, 'error' => string, 'message' => string]
     */
    public static function request($method, $path, $body = null, $extra_headers = []) {
        $args = [
            'method'  => $method,
            'timeout' => 'POST' === $method ? 25 : 15,
            'headers' => array_merge(self::headers(), is_array($extra_headers) ? $extra_headers : []),
        ];
        if (null !== $body) {
            $args['body'] = wp_json_encode($body);
        }
        $response = wp_remote_request(self::endpoint() . $path, $args);
        if (is_wp_error($response)) {
            return ['ok' => false, 'code' => 0, 'body' => [], 'headers' => [], 'error' => 'network', 'message' => $response->get_error_message()];
        }
        $code    = (int) wp_remote_retrieve_response_code($response);
        $data    = json_decode((string) wp_remote_retrieve_body($response), true);
        $data    = is_array($data) ? $data : [];
        $error   = isset($data['error']) ? sanitize_key((string) $data['error']) : '';
        $headers = [
            'etag' => (string) wp_remote_retrieve_header($response, 'etag'),
        ];

        // 304 is a success with no payload: the caller keeps what it already had.
        if (304 === $code) {
            return ['ok' => true, 'code' => 304, 'body' => [], 'headers' => $headers, 'error' => '', 'message' => ''];
        }

        if (401 === $code || 403 === $code) {
            // A stale binding (URL moved, salts rotated, API forgot us) is fixed by reconnecting.
            if (in_array($error, ['invalid_token', 'site_mismatch', 'fingerprint_mismatch'], true)) {
                delete_option(self::OPT_AUTH);
                delete_transient(self::CACHE_STATUS);
            }
            return ['ok' => false, 'code' => $code, 'body' => $data, 'headers' => $headers, 'error' => $error ?: 'unauthorized', 'message' => __('This site is no longer connected to the NotificationX API. Please connect again.', 'notificationx')];
        }
        if ($code < 200 || $code >= 300) {
            if (!empty($data['message'])) {
                // The API answered in its own shape — it has a real reason, so say it.
                $message = (string) $data['message'];
            } elseif (404 === $code) {
                // A 404 with no JSON body is the web server's, not the API's:
                // there is no service at this address. Saying "HTTP 404" sends
                // the admin looking for a missing Page; the endpoint is what is
                // missing. This is what a mistyped or not-yet-deployed
                // `nx_facebook_reviews_managed_endpoint` looks like.
                /* translators: %s: API endpoint URL */
                $message = sprintf(
                    __('The NotificationX API is not available at %s. If this site points at a custom endpoint, check the nx_facebook_reviews_managed_endpoint filter.', 'notificationx'),
                    self::endpoint()
                );
                $error = $error ?: 'endpoint_not_found';
            } else {
                /* translators: %d: HTTP status code */
                $message = sprintf(__('The NotificationX API returned HTTP %d.', 'notificationx'), $code);
            }
            return ['ok' => false, 'code' => $code, 'body' => $data, 'headers' => $headers, 'error' => $error ?: 'http_' . $code, 'message' => $message];
        }
        return ['ok' => true, 'code' => $code, 'body' => $data, 'headers' => $headers, 'error' => '', 'message' => ''];
    }

    /**
     * Pull already-collected reviews for a connection.
     *
     * The API pushes each new review to /wp-json/notificationx/v1/social-review
     * as it is collected, and that remains the fast path. This is the pull
     * complement, and it is not merely a fallback: a large share of installs are
     * simply not reachable from the public internet — local and staging sites,
     * anything behind basic auth, an IP allowlist or a firewall — and for those
     * the webhook can never arrive. They get their reviews here instead, on the
     * same cron that refreshes the page rating.
     *
     * It also repairs the cases push cannot: a restored backup, a migrated
     * install, or a campaign created after the reviews were already collected.
     *
     * Responses are ETagged, so the common "nothing new" answer costs one
     * conditional request and no payload.
     *
     * @param string $connection_id
     * @param array  $args  ['limit' => int, 'after' => int, 'fresh' => bool]
     * @return array request() shape; body has reviews, total, next_cursor
     */
    public static function reviews($connection_id, $args = []) {
        $connection_id = sanitize_text_field((string) $connection_id);
        if ('' === $connection_id) {
            return ['ok' => false, 'code' => 0, 'body' => [], 'headers' => [], 'error' => 'missing_connection', 'message' => __('No Facebook Page is connected.', 'notificationx')];
        }
        $query = [
            'connection_id' => $connection_id,
            'limit'         => max(1, min(200, (int) (isset($args['limit']) ? $args['limit'] : 50))),
        ];
        if (!empty($args['after'])) {
            $query['after'] = (int) $args['after'];
        }

        // Only the first page is worth caching: it is the one the cron re-reads
        // every time, and deeper pages are walked once during a backfill.
        $cacheable = empty($query['after']);
        $cache_key = self::CACHE_REVIEWS . md5($connection_id . '|' . $query['limit']);
        $cached    = $cacheable && empty($args['fresh']) ? get_transient($cache_key) : false;
        $headers   = [];
        if (is_array($cached) && !empty($cached['etag'])) {
            $headers['If-None-Match'] = $cached['etag'];
        }

        $result = self::request('GET', '/reviews.php?' . http_build_query($query), null, $headers);

        if (!empty($result['ok']) && 304 === $result['code'] && is_array($cached)) {
            return ['ok' => true, 'code' => 200, 'body' => $cached['body'], 'headers' => $result['headers'], 'error' => '', 'message' => ''];
        }
        if (!empty($result['ok']) && $cacheable) {
            set_transient($cache_key, ['etag' => (string) $result['headers']['etag'], 'body' => $result['body']], self::REVIEWS_CACHE_TTL);
        }
        return $result;
    }

    /**
     * Ask the API to collect this Page again now, ahead of its schedule.
     *
     * Rate limited on the API side per connection, so a user leaning on a
     * Refresh button costs a 429 rather than the deployment's whole collection
     * budget. Collection is asynchronous: a successful call means the work was
     * accepted, not that new reviews already exist.
     */
    public static function refresh($connection_id) {
        $result = self::request('POST', '/refresh.php', ['connection_id' => sanitize_text_field((string) $connection_id)]);
        delete_transient(self::CACHE_STATUS);
        self::forget_review_cache($connection_id);
        return $result;
    }

    /** Drop the cached first page so the next pull is served fresh. */
    public static function forget_review_cache($connection_id) {
        foreach ([25, 50, 100, 200] as $limit) {
            delete_transient(self::CACHE_REVIEWS . md5(sanitize_text_field((string) $connection_id) . '|' . $limit));
        }
    }

    /** Starts the Facebook login; returns the URL to send the admin's browser to. */
    public static function oauth_start($return_url) {
        return self::request('POST', '/oauth-start.php', ['provider' => 'facebook', 'return_url' => $return_url]);
    }

    /**
     * Begin owner-attested connect: ask the API for this Page's challenge.
     *
     * The path for deployments whose API has no Meta app — App Review and
     * Business Verification take weeks and can be refused, and a product cannot
     * wait on that. Instead of Meta asserting that this person manages the Page,
     * the customer demonstrates control of it directly.
     *
     * Issuing a challenge connects nothing. attest_verify() does that, and only
     * after the API has actually found the proof on the Page.
     */
    public static function attest_start($page_url) {
        return self::request('POST', '/attest-start.php', ['page_url' => (string) $page_url]);
    }

    /** Check the Page for the proof and, if it is there, connect it. */
    public static function attest_verify($page_url) {
        $result = self::request('POST', '/attest-verify.php', ['page_url' => (string) $page_url]);
        if (!empty($result['ok'])) {
            delete_transient(self::CACHE_STATUS);
        }
        return $result;
    }

    /**
     * Look up a Page by address without connecting it.
     *
     * A pasted URL is opaque. Showing the admin "Anna's Bakery ⭐4.8 · 212"
     * before they commit is what stops a personal profile, a partner's Page or a
     * typo becoming a live connection that only reveals itself when the wrong
     * reviews appear on the site.
     */
    public static function page_preview($page_url) {
        return self::request('POST', '/page-connect.php', ['page_url' => (string) $page_url]);
    }

    /** Connect the Page at this address. */
    public static function page_connect($page_url) {
        $result = self::request('POST', '/page-connect.php', ['page_url' => (string) $page_url, 'confirm' => '1']);
        if (!empty($result['ok'])) {
            delete_transient(self::CACHE_STATUS);
        }
        return $result;
    }

    /**
     * Which ways this API lets a site connect a Page — `oauth`, `attested`, or
     * both. Reported by the API rather than assumed, so the UI offers what the
     * deployment can actually do instead of advertising a login that 404s.
     *
     * Falls back to OAuth for an API too old to report at all.
     *
     * @return string[]
     */
    public static function connect_modes() {
        $status = self::connections();
        $modes  = !empty($status['ok']) && !empty($status['body']['connect_modes'])
            ? (array) $status['body']['connect_modes']
            : [];
        $modes = array_values(array_intersect(array_map('sanitize_key', $modes), ['oauth', 'attested', 'open']));
        return $modes ?: ['oauth'];
    }

    /** Pages the logged-in Facebook user can connect (no tokens). */
    public static function pages($session_id) {
        return self::request('GET', '/pages.php?' . http_build_query(['session_id' => $session_id]));
    }

    public static function pages_connect($session_id, $page_id) {
        $result = self::request('POST', '/pages-connect.php', ['session_id' => $session_id, 'page_id' => $page_id]);
        delete_transient(self::CACHE_STATUS);
        return $result;
    }

    /**
     * All Facebook connections of this site (cached briefly), or one when $connection_id is given.
     */
    public static function connections($connection_id = '', $fresh = false) {
        if ('' !== $connection_id) {
            return self::request('GET', '/status.php?' . http_build_query(['connection_id' => $connection_id]));
        }
        $cached = $fresh ? false : get_transient(self::CACHE_STATUS);
        if (is_array($cached)) {
            return ['ok' => true, 'code' => 200, 'body' => $cached, 'error' => '', 'message' => ''];
        }
        $result = self::request('GET', '/status.php');
        if (!empty($result['ok'])) {
            set_transient(self::CACHE_STATUS, $result['body'], 5 * MINUTE_IN_SECONDS);
        }
        return $result;
    }

    public static function disconnect_page($connection_id) {
        $result = self::request('POST', '/facebook-disconnect.php', ['connection_id' => $connection_id]);
        delete_transient(self::CACHE_STATUS);
        return $result;
    }

    /**
     * Verifies a webhook from the API.
     *
     *   X-NX-Signature: sha256=HEX(HMAC_SHA256(site_token, "{timestamp}.{delivery_id}.{raw_body}"))
     *
     * @return true|string true when valid, otherwise a short reason.
     */
    public static function verify_webhook($raw_body, $timestamp, $delivery_id, $signature, $now = null) {
        $auth = self::get_auth();
        if (empty($auth['token'])) {
            return 'not_connected';
        }
        $now = null === $now ? time() : (int) $now;
        if (!ctype_digit((string) $timestamp) || abs($now - (int) $timestamp) > self::WEBHOOK_SKEW) {
            return 'expired_timestamp';
        }
        if ('' === $delivery_id || 0 !== strpos((string) $signature, 'sha256=')) {
            return 'invalid_signature';
        }
        $expected = hash_hmac('sha256', $timestamp . '.' . $delivery_id . '.' . $raw_body, $auth['token']);
        return hash_equals($expected, substr((string) $signature, 7)) ? true : 'invalid_signature';
    }

    protected static function license_key() {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
        return (string) apply_filters('nx_facebook_reviews_license_key', '');
    }
}
