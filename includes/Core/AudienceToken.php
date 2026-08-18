<?php

namespace NotificationX\Core;

/**
 * A signed list of the notifications one page render decided a visitor may see.
 *
 * The frontend does not ask the server "what should I show?" -- it is told at
 * page render, and then posts those ids back to /notice, /analytics and
 * /popup-submit. That round trip is the problem: the ids arrive as plain request
 * data, so anyone could name any id and be served, counted, or written against
 * a notification the targeting rules exclude them from.
 *
 * The obvious repair -- recompute the allowed set when the request arrives --
 * does not work here. FrontEnd::get_notifications_ids() decides using
 * Locations::check_location(), which reads is_front_page(), is_singular(),
 * is_page(), is_archive() and friends. During a request to
 * /wp-json/notificationx/v1/notice none of those describe the page the visitor
 * is actually on, so every "show on selected pages" notification would evaluate
 * to excluded and silently disappear. (FrontEnd has carried a `@todo need to
 * pass url` note at that spot for exactly this reason.) The same applies to the
 * session: the frontend posts with `credentials: 'omit'` by default, so a
 * logged-in visitor looks logged out to the REST request and any re-check of
 * audience or user-role targeting would come out wrong.
 *
 * So the decision is not recomputed -- it is carried. Page render already
 * evaluates the full policy with the right context; this signs the result so it
 * survives the trip back intact. The token holds the id list and an HMAC over
 * it, which makes it self-contained: the endpoint learns the authoritative set
 * from the token itself rather than trusting an echoed copy.
 *
 * What it deliberately does NOT do:
 *
 * - It is not a secret and not a nonce. It authenticates a list, not a user or
 *   a session, and two visitors on the same page legitimately hold the same
 *   token. Sending it in the query string is fine: every id in it is already
 *   present in that page's HTML.
 * - It does not expire. Expiry would buy very little -- the ids were shown to
 *   this context already -- and would cost a silent failure on any full-page
 *   cache older than the window, which is the kind of breakage nobody
 *   diagnoses. State that genuinely changes after minting is re-checked at use
 *   instead: /notice still drops notifications that have since been disabled.
 *
 * @since 3.2.15
 */
class AudienceToken {

    /**
     * The id groups FrontEnd::get_notifications_ids() returns. Everything else
     * in that array (`total`, and whatever filters bolt on) is not an id list.
     */
    const GROUPS = [ 'global', 'active', 'pressbar', 'gdpr', 'popup', 'exit_intent', 'shortcode' ];

    /**
     * Mint a token for a set of notification ids.
     *
     * @param array $groups Either get_notifications_ids() output, or any array
     *                      carrying those group keys.
     * @return string Token, or '' when there is nothing to sign.
     */
    public static function create( $groups ) {
        $ids = self::collect_ids( $groups );
        if ( empty( $ids ) ) {
            return '';
        }

        $payload = self::base64url_encode( wp_json_encode( $ids ) );

        return $payload . '.' . hash_hmac( 'sha256', $payload, self::key() );
    }

    /**
     * The ids a token vouches for.
     *
     * @param string $token Token as minted by create().
     * @return array|null Ids, or null when the token is absent, malformed or
     *                    not signed by this site. Null and [] mean different
     *                    things -- callers decide what to do about "no usable
     *                    token", which is not the same as "allowed nothing".
     */
    public static function allowed_ids( $token ) {
        if ( ! is_string( $token ) || '' === $token || substr_count( $token, '.' ) !== 1 ) {
            return null;
        }

        list( $payload, $signature ) = explode( '.', $token );
        if ( '' === $payload || '' === $signature ) {
            return null;
        }

        $expected = hash_hmac( 'sha256', $payload, self::key() );
        if ( ! hash_equals( $expected, $signature ) ) {
            return null;
        }

        $ids = json_decode( self::base64url_decode( $payload ), true );
        if ( ! is_array( $ids ) ) {
            return null;
        }

        return array_map( 'absint', $ids );
    }

    /**
     * Whether a token vouches for one specific notification.
     *
     * @param string $token Token from the request.
     * @param int    $nx_id Notification id.
     * @return bool
     */
    public static function permits( $token, $nx_id ) {
        $allowed = self::allowed_ids( $token );

        return is_array( $allowed ) && in_array( absint( $nx_id ), $allowed, true );
    }

    /**
     * Flatten group arrays into one sorted, de-duplicated id list.
     *
     * Sorting and de-duplicating are what make the payload canonical, so the
     * same set of notifications always mints the same token and full-page
     * caches keep working.
     *
     * @param array $groups
     * @return array
     */
    protected static function collect_ids( $groups ) {
        if ( ! is_array( $groups ) ) {
            return [];
        }

        $ids = [];
        foreach ( self::GROUPS as $group ) {
            if ( empty( $groups[ $group ] ) || ! is_array( $groups[ $group ] ) ) {
                continue;
            }
            foreach ( $groups[ $group ] as $id ) {
                if ( is_scalar( $id ) && absint( $id ) ) {
                    $ids[] = absint( $id );
                }
            }
        }

        $ids = array_values( array_unique( $ids ) );
        sort( $ids, SORT_NUMERIC );

        return $ids;
    }

    /**
     * Signing key, derived from the site's nonce salt rather than used directly
     * so this cannot collide with anything else signing under that salt.
     *
     * Rotating the salts invalidates outstanding tokens; the next page load
     * mints a fresh one, and cached pages recover on purge.
     *
     * @return string
     */
    protected static function key() {
        return hash_hmac( 'sha256', 'notificationx-audience-token-v1', wp_salt( 'nonce' ) );
    }

    /**
     * @param string $data
     * @return string
     */
    protected static function base64url_encode( $data ) {
        return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
    }

    /**
     * @param string $data
     * @return string
     */
    protected static function base64url_decode( $data ) {
        return (string) base64_decode( strtr( $data, '-_', '+/' ), true );
    }
}
