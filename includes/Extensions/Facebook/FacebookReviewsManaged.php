<?php
/**
 * Managed (hosted) connection for the Facebook Reviews source.
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Facebook;

use NotificationX\NotificationX;

/**
 * Talks to the NotificationX API proxy (facebook-reviews/v1) so a site can
 * collect Facebook recommendations with zero setup. The proxy holds the Apify
 * token; this site only ever holds a per-site Bearer token bound to its URL
 * and an install fingerprint.
 *
 * Connection flow:
 *   connect()           POST connect.php {site_url, fingerprint, license_key, …} → token, stored in OPT_AUTH.
 *                       Only ever triggered by an admin action (the Connect button); this is
 *                       the consent step, recorded in OPT_CONSENT.
 *   ensure_connected()  re-runs the handshake WITHOUT user action only after consent was
 *                       given once and the binding went stale (URL moved, salts rotated,
 *                       proxy forgot us). Never phones home on a fresh install.
 *   headers()           Bearer + site + fingerprint (+ licence key on Pro) for every call
 *   request()           thin wrapper that maps 401/403/429 to readable errors and self-heals
 *                       a stale binding by dropping the local token
 *
 * Tier: the proxy grants `pro` only when the licence key verifies against the
 * WPDeveloper store; otherwise it connects the site as `free` and reports why in
 * `license_status`. Whatever it decides is what we store and display.
 */
class FacebookReviewsManaged {
    const DEFAULT_ENDPOINT = 'https://api.notificationx.com/facebook-reviews/v1';

    /** Option holding [token, site_id, home_url, fingerprint, tier, connected_at]; autoload off. */
    const OPT_AUTH = 'nx_facebook_reviews_managed_auth';
    /** Set the first time an admin clicks Connect; cleared on Disconnect. Gates every automatic (re)connect. */
    const OPT_CONSENT = 'nx_facebook_reviews_managed_consent';
    /** Transient that throttles reconnect attempts while the proxy is unreachable. */
    const BACKOFF = 'nx_facebook_reviews_managed_backoff';

    public static function endpoint() {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
        $endpoint = untrailingslashit((string) apply_filters('nx_facebook_reviews_managed_endpoint', self::DEFAULT_ENDPOINT));
        // The Bearer token rides every request, so refuse to send it in clear
        // text — a plain-http override is only honoured for loopback / dev hosts.
        if (0 !== stripos($endpoint, 'https://') && !self::is_local_endpoint($endpoint)) {
            return self::DEFAULT_ENDPOINT;
        }
        return $endpoint;
    }

    protected static function is_local_endpoint($endpoint) {
        $host = strtolower((string) wp_parse_url($endpoint, PHP_URL_HOST));
        if ('' === $host) {
            return false;
        }
        if (in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true) || '.test' === substr($host, -5) || '.local' === substr($host, -6)) {
            return true;
        }
        return function_exists('wp_get_environment_type') && in_array(wp_get_environment_type(), ['local', 'development'], true);
    }

    /**
     * NotificationX Pro licence key, when Pro is active. Read straight from the
     * option the WPDeveloper licensing SDK stores it in (free plugin can't call
     * Pro's manager class), so the filter is there for a different store setup.
     */
    public static function license_key() {
        if (!NotificationX::get_instance()->is_pro()) {
            return '';
        }
        $prefix = defined('NOTIFICATIONX_PRO_SL_DB_PREFIX') ? NOTIFICATIONX_PRO_SL_DB_PREFIX : 'notificationx_pro_software_';
        $key = (string) get_option($prefix . '_license', '');
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
        return trim((string) apply_filters('nx_facebook_reviews_managed_license_key', $key));
    }

    /**
     * Stable per-install identifier the proxy binds the token to. Derived from
     * AUTH_KEY so a copied database on another host cannot reuse the token.
     */
    public static function compute_fingerprint() {
        $secret = defined('AUTH_KEY') && AUTH_KEY !== '' ? AUTH_KEY : '';
        if ('' === $secret) {
            $secret = get_option('nx_install_uuid');
            if (!$secret) {
                $secret = wp_generate_uuid4();
                update_option('nx_install_uuid', $secret, false);
            }
        }
        return hash('sha256', home_url() . '|' . $secret . '|notificationx-facebook-reviews');
    }

    public static function get_auth() {
        $auth = get_option(self::OPT_AUTH);
        return is_array($auth) && !empty($auth['token']) ? $auth : [];
    }

    public static function is_connected() {
        return [] !== self::get_auth();
    }

    /**
     * Metadata safe to show in the admin UI. Never includes the token.
     */
    public static function status() {
        $auth = self::get_auth();
        return [
            'connected'    => !empty($auth),
            'site_id'      => isset($auth['site_id']) ? $auth['site_id'] : '',
            'home_url'     => isset($auth['home_url']) ? $auth['home_url'] : home_url(),
            'tier'           => isset($auth['tier']) ? $auth['tier'] : '',
            'license_status' => isset($auth['license_status']) ? $auth['license_status'] : '',
            'connected_at'   => isset($auth['connected_at']) ? (int) $auth['connected_at'] : 0,
            'consented'      => (bool) get_option(self::OPT_CONSENT),
            'endpoint'       => self::endpoint(),
        ];
    }

    /**
     * Returns true when a usable token exists. Reconnects on demand ONLY if an
     * admin connected this site before (consent) and the binding has since gone
     * stale — never on a fresh install. Backs off while the proxy is down.
     */
    public static function ensure_connected() {
        if (self::is_connected()) {
            return true;
        }
        if (!get_option(self::OPT_CONSENT) || get_transient(self::BACKOFF)) {
            return false;
        }
        $result = self::connect();
        if (empty($result['ok'])) {
            set_transient(self::BACKOFF, 1, MINUTE_IN_SECONDS);
            return false;
        }
        return true;
    }

    /**
     * Open handshake with the proxy. Calling again rotates the token.
     *
     * @return array ['ok' => bool, 'message' => string] (+ auth fields on success)
     */
    public static function connect() {
        $payload = [
            'site_url'       => home_url(),
            'fingerprint'    => self::compute_fingerprint(),
            'admin_email'    => get_option('admin_email'),
            'plugin_version' => defined('NOTIFICATIONX_VERSION') ? NOTIFICATIONX_VERSION : '',
            'wp_version'     => get_bloginfo('version'),
            'tier'           => NotificationX::get_instance()->is_pro() ? 'pro' : 'free',
            'license_key'    => self::license_key(),
        ];

        $response = wp_remote_post(self::endpoint() . '/connect.php', [
            'timeout' => 15,
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
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

        $auth = [
            'token'          => (string) $body['token'],
            'site_id'        => (string) (isset($body['site_id']) ? $body['site_id'] : ''),
            'home_url'       => (string) (isset($body['home_url']) ? $body['home_url'] : home_url()),
            'fingerprint'    => $payload['fingerprint'],
            'tier'           => 'pro' === (isset($body['tier']) ? $body['tier'] : '') ? 'pro' : 'free',
            'license_status' => sanitize_key((string) (isset($body['license_status']) ? $body['license_status'] : '')),
            'connected_at'   => (int) (isset($body['issued_at']) ? $body['issued_at'] : time()),
        ];
        update_option(self::OPT_AUTH, $auth, false);
        update_option(self::OPT_CONSENT, 1, false);
        delete_transient(self::BACKOFF);

        return ['ok' => true] + $auth;
    }

    /**
     * Revokes the token on the proxy (best effort) and forgets it locally.
     */
    public static function disconnect() {
        if (self::is_connected()) {
            wp_remote_post(self::endpoint() . '/disconnect.php', [
                'timeout' => 10,
                'headers' => self::headers(),
                'body'    => '{}',
            ]);
        }
        delete_option(self::OPT_AUTH);
        delete_option(self::OPT_CONSENT);
        delete_transient(self::BACKOFF);
    }

    public static function forget() {
        self::disconnect();
    }

    public static function headers() {
        $headers = [
            'Content-Type'        => 'application/json',
            'Accept'              => 'application/json',
            'X-NotificationX-Site' => home_url(),
        ];
        $auth = self::get_auth();
        if (!empty($auth['token'])) {
            $headers['Authorization']               = 'Bearer ' . $auth['token'];
            // Some hosts strip Authorization before PHP sees it; the proxy accepts this too.
            $headers['X-NotificationX-Token']       = $auth['token'];
            $headers['X-NotificationX-Fingerprint'] = !empty($auth['fingerprint']) ? $auth['fingerprint'] : self::compute_fingerprint();
            // Lets the proxy re-verify the Pro licence periodically without storing the key.
            $license = self::license_key();
            if ('' !== $license) {
                $headers['X-NotificationX-License'] = $license;
            }
        }
        return $headers;
    }

    /**
     * Authenticated JSON request to the proxy.
     *
     * @param string     $method GET|POST
     * @param string     $path   e.g. '/enqueue.php' or '/status.php?job_id=…'
     * @param array|null $body
     * @return array ['ok' => bool, 'code' => int, 'body' => array, 'message' => string, 'retry' => bool]
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
            return ['ok' => false, 'code' => 0, 'body' => [], 'message' => $response->get_error_message()];
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        $data = is_array($data) ? $data : [];

        if (401 === $code || 403 === $code) {
            $error = isset($data['error']) ? (string) $data['error'] : '';
            // A stale binding (URL moved, salts rotated) is fixed by reconnecting,
            // which ensure_connected() does on the next run — so drop the token now.
            if (in_array($error, ['invalid_token', 'site_mismatch', 'fingerprint_mismatch'], true)) {
                delete_option(self::OPT_AUTH);
            }
            return [
                'ok'      => false,
                'code'    => $code,
                'body'    => $data,
                'message' => __('Facebook Reviews is not connected to the NotificationX API. Open NotificationX → Settings → API Integrations and click Connect.', 'notificationx'),
                'retry'   => false,
            ];
        }

        if (429 === $code || 503 === $code) {
            $error = isset($data['error']) ? (string) $data['error'] : '';
            $message = 'site_budget_exceeded' === $error
                ? __('This site has reached its daily Facebook Reviews collection limit. It will resume automatically.', 'notificationx')
                : __('The NotificationX API is busy right now. The collection will be retried automatically.', 'notificationx');
            return ['ok' => false, 'code' => $code, 'body' => $data, 'message' => $message, 'retry' => true];
        }

        if ($code < 200 || $code >= 300) {
            $message = !empty($data['message'])
                ? (string) $data['message']
                /* translators: %d: HTTP status code */
                : sprintf(__('The NotificationX API returned HTTP %d.', 'notificationx'), $code);
            return ['ok' => false, 'code' => $code, 'body' => $data, 'message' => $message, 'retry' => $code >= 500];
        }

        return ['ok' => true, 'code' => $code, 'body' => $data, 'message' => ''];
    }
}
