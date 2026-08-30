<?php
/**
 * Finds the Facebook Page a site already advertises.
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Facebook;

/**
 * Most sites that want to show their Facebook reviews already link to the Page
 * somewhere — a footer icon, a social menu, an SEO plugin's social settings. The
 * plugin runs inside WordPress, so it can just look, and the admin gets a button
 * to press instead of a field to fill.
 *
 * This is a convenience, never a claim. What turns up may be a partner's Page, an
 * employee's profile, or a stale link, so the result is always offered for the
 * admin to confirm and never connected on its own.
 *
 * Everything here is local: option reads plus, at most, one request to the site's
 * own homepage.
 */
class FacebookPageFinder {

    /** Cached across the settings screen's requests; cheap, but not free. */
    const CACHE_KEY = 'nx_facebook_reviews_discovered_pages';
    const CACHE_TTL = 15 * MINUTE_IN_SECONDS;

    /**
     * Path segments that are Facebook's own plumbing rather than a Page. A site's
     * markup is full of these — share buttons, the pixel, embedded like boxes —
     * and without filtering, "your Page" would come back as `sharer.php`.
     */
    const NOT_PAGES = [
        'sharer', 'sharer.php', 'share.php', 'dialog', 'plugins', 'tr', 'v2.0', 'v1.0',
        'login', 'login.php', 'help', 'policies', 'privacy', 'terms', 'l.php', 'flx',
        'photo', 'photos', 'video', 'videos', 'watch', 'groups', 'events', 'marketplace',
        'story.php', 'permalink.php', 'profile.php', 'pages', 'people', 'reel', 'share', 'search',
    ];

    /**
     * Page addresses this site already advertises, best source first.
     *
     * @param bool $fresh skip the cache
     * @return array list of ['url' => string, 'handle' => string, 'source' => string]
     */
    public static function discover( $fresh = false ) {
        if ( ! $fresh ) {
            $cached = get_transient( self::CACHE_KEY );
            if ( is_array( $cached ) ) {
                return $cached;
            }
        }

        $found = [];
        // Ordered by how deliberate the link is. An SEO plugin's "Facebook Page
        // URL" field was typed by someone naming their own Page; a link in the
        // page body might be anything.
        foreach ( [ 'seo_plugins', 'theme_mods', 'nav_menus', 'homepage' ] as $source ) {
            foreach ( call_user_func( [ __CLASS__, 'from_' . $source ] ) as $url ) {
                $handle = self::handle_from( $url );
                if ( '' === $handle || isset( $found[ $handle ] ) ) {
                    continue;
                }
                $found[ $handle ] = [
                    'url'    => 'https://www.facebook.com/' . $handle,
                    'handle' => $handle,
                    'source' => $source,
                ];
            }
        }

        $found = array_values( $found );
        set_transient( self::CACHE_KEY, $found, self::CACHE_TTL );
        return $found;
    }

    public static function forget() {
        delete_transient( self::CACHE_KEY );
    }

    /**
     * The handle out of a URL, or '' when it does not name a Page.
     *
     * Mirrors the API's PageHandle rules deliberately — a suggestion the API
     * would then reject is worse than no suggestion.
     */
    public static function handle_from( $url ) {
        $url  = trim( (string) $url );
        $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
        if ( '' === $host || ! preg_match( '/(^|\.)(facebook\.com|fb\.com)$/', $host ) ) {
            return '';
        }
        $path    = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
        $segment = '' !== $path ? explode( '/', $path )[0] : '';
        if ( '' === $segment || in_array( strtolower( $segment ), self::NOT_PAGES, true ) ) {
            return '';
        }
        // "Annas-Bakery-100064861234567" — Facebook's form for a Page with no
        // vanity username. Identity is the trailing id; the slug changes on rename.
        if ( preg_match( '/^(?:[A-Za-z0-9.\-]+)-(\d{6,})$/', $segment, $m ) ) {
            return $m[1];
        }
        if ( ctype_digit( $segment ) ) {
            return $segment;
        }
        return preg_match( '/^[A-Za-z0-9.]{3,64}$/', $segment ) ? $segment : '';
    }

    /** Yoast and Rank Math both keep a dedicated "Facebook Page URL" setting. */
    protected static function from_seo_plugins() {
        $urls = [];
        foreach ( [ [ 'wpseo_social', 'facebook_site' ], [ 'rank-math-options-titles', 'social_url_facebook' ] ] as $pair ) {
            $option = get_option( $pair[0] );
            if ( is_array( $option ) && ! empty( $option[ $pair[1] ] ) ) {
                $urls[] = (string) $option[ $pair[1] ];
            }
        }
        $aioseo = get_option( 'aioseo_options' );
        if ( is_string( $aioseo ) && false !== stripos( $aioseo, 'facebook.com' ) ) {
            $urls = array_merge( $urls, self::scan( $aioseo ) );
        }
        return $urls;
    }

    /** Most themes (Astra, Kadence, Blocksy…) keep social links in theme mods. */
    protected static function from_theme_mods() {
        $urls = [];
        $mods = get_theme_mods();
        if ( is_array( $mods ) ) {
            array_walk_recursive(
                $mods,
                static function ( $value ) use ( &$urls ) {
                    if ( is_string( $value ) && false !== stripos( $value, 'facebook.com' ) ) {
                        $urls = array_merge( $urls, self::scan( $value ) );
                    }
                }
            );
        }
        return $urls;
    }

    protected static function from_nav_menus() {
        $urls = [];
        foreach ( wp_get_nav_menus() as $menu ) {
            foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $item ) {
                if ( isset( $item->url ) && false !== stripos( (string) $item->url, 'facebook.com' ) ) {
                    $urls[] = (string) $item->url;
                }
            }
        }
        return $urls;
    }

    /**
     * The site's own homepage — where a footer social icon lives, and the source
     * that works regardless of theme or plugins.
     */
    protected static function from_homepage() {
        $response = wp_remote_get(
            home_url( '/' ),
            [
                'timeout'    => 10,
                'redirection' => 2,
                // Requesting ourselves; a local certificate is not evidence of anything.
                'sslverify'  => false,
                'headers'    => [ 'Accept' => 'text/html' ],
            ]
        );
        if ( is_wp_error( $response ) ) {
            return [];
        }
        return self::scan( (string) wp_remote_retrieve_body( $response ) );
    }

    /** Every facebook.com URL in a blob of text. */
    protected static function scan( $text ) {
        preg_match_all( '#https?://(?:[a-z0-9-]+\.)?facebook\.com/[A-Za-z0-9._/?=&%-]+#i', (string) $text, $matches );
        return array_slice( array_unique( $matches[0] ), 0, 40 );
    }
}
