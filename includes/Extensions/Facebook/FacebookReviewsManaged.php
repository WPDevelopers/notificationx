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
     * @return array ['ok' => bool, 'code' => int, 'body' => array, 'error' => string, 'message' => string]
     */
    public static function request($method, $path, $body = null) {
        $args = [
            'method'  => $method,
            'timeout' => 'POST' === $method ? 25 : 15,
            'headers' => self::headers(),
        ];
        if (null !== $body) {
            $args['body'] = wp_json_encode($body);
        }
        $response = wp_remote_request(self::endpoint() . $path, $args);
        if (is_wp_error($response)) {
            return ['ok' => false, 'code' => 0, 'body' => [], 'error' => 'network', 'message' => $response->get_error_message()];
        }
        $code  = (int) wp_remote_retrieve_response_code($response);
        $data  = json_decode((string) wp_remote_retrieve_body($response), true);
        $data  = is_array($data) ? $data : [];
        $error = isset($data['error']) ? sanitize_key((string) $data['error']) : '';

        if (401 === $code || 403 === $code) {
            // A stale binding (URL moved, salts rotated, API forgot us) is fixed by reconnecting.
            if (in_array($error, ['invalid_token', 'site_mismatch', 'fingerprint_mismatch'], true)) {
                delete_option(self::OPT_AUTH);
                delete_transient(self::CACHE_STATUS);
            }
            return ['ok' => false, 'code' => $code, 'body' => $data, 'error' => $error ?: 'unauthorized', 'message' => __('This site is no longer connected to the NotificationX API. Please connect again.', 'notificationx')];
        }
        if ($code < 200 || $code >= 300) {
            $message = !empty($data['message'])
                ? (string) $data['message']
                /* translators: %d: HTTP status code */
                : sprintf(__('The NotificationX API returned HTTP %d.', 'notificationx'), $code);
            return ['ok' => false, 'code' => $code, 'body' => $data, 'error' => $error ?: 'http_' . $code, 'message' => $message];
        }
        return ['ok' => true, 'code' => $code, 'body' => $data, 'error' => '', 'message' => ''];
    }

    /** Starts the Facebook login; returns the URL to send the admin's browser to. */
    public static function oauth_start($return_url) {
        return self::request('POST', '/oauth-start.php', ['provider' => 'facebook', 'return_url' => $return_url]);
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
