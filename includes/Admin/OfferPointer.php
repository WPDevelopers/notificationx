<?php
/**
 * The seasonal offer pointer beside the NotificationX admin menu item.
 *
 * @package NotificationX\Admin
 */

namespace NotificationX\Admin;

use NotificationX\NotificationX;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * The seasonal offer pointer: a WordPress admin pointer beside NotificationX's menu item.
 *
 * Free upsell UI for the running campaign (Black Friday / Cyber Monday 2026), shown beside the
 * dashboard offer notice (`Admin::notices()`, `nx_bfcm_2026`) and inside the same dates. The
 * notice reads its dates and its link from the constants below, so the two cannot drift apart.
 * It uses WordPress's own `wp-pointer`, so it looks like every other admin pointer.
 *
 * It shows only when all of these hold:
 * - NotificationX Pro is not active;
 * - the date is inside the campaign window;
 * - the screen is one of NotificationX's own pages (not the WordPress Dashboard);
 * - the user can `manage_options`;
 * - this user has not dismissed it (user meta, kept for good);
 * - no other WPDeveloper plugin holds the pointer slot with a smaller number.
 *
 * The pointer slot is shared: every WPDeveloper plugin that shows a pointer reads and writes the
 * option `_wpdeveloper_plugin_pointer_priority`, the smallest number wins, and a plugin releases
 * the slot when its pointer is dismissed. NotificationX's number is 5, the same priority its
 * notices use. It releases the slot only while it holds it, and again once the campaign ends
 * or Pro is activated, so a claim never outlives the pointer.
 *
 * Each campaign replaces the one before it: change the constants and the copy in `content()`.
 * A new campaign takes a new `ID`, or it inherits the previous one's dismissals.
 *
 * See docs/features/offer-campaign.md.
 */
class OfferPointer {

    /** The campaign. Part of the dismissal's user meta key. */
    const ID = 'bfcm2026';

    /** The campaign window (`strtotime()`, so UTC). The dashboard offer notice uses the same two. */
    const START = '12:00:00am 15th November, 2026';

    const EXPIRE = '11:59:59pm 14th December, 2026';

    /** Where the pointer's button and the notice's button lead. */
    const URL = 'https://notificationx.com/bfcm2026/admin-notice';

    /** NotificationX's place in the shared pointer queue (smaller shows first). */
    const PRIORITY = 5;

    /** Shared by every WPDeveloper plugin that shows a pointer. */
    const PRIORITY_OPTION = '_wpdeveloper_plugin_pointer_priority';

    /**
     * The menu item's own link (not the whole item: on NotificationX's pages the item holds the
     * open submenu, and the arrow would point at its middle).
     */
    const TARGET = '#toplevel_page_nx-admin > a.menu-top';

    const CAPABILITY = 'manage_options';

    const AJAX_ACTION = 'nx_dismiss_offer_pointer';

    const NONCE_ACTION = 'nx_offer_pointer';

    const SCRIPT_HANDLE = 'nx-offer-pointer';

    /** The script, relative to `assets/`. Plain JavaScript, no build step. */
    const SCRIPT = 'admin/js/offer-pointer.js';

    /** The campaign blue: WordPress's default admin blue, whatever colour scheme the user picked. */
    const COLOR = '#2271b1';

    const COLOR_HOVER = '#135e96';

    /**
     * Hook in. Called on every admin request, with or without Pro.
     *
     * @return void
     */
    public static function register() {
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'on_enqueue_scripts' ) );
        add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'dismiss' ) );
        add_action( 'admin_init', array( __CLASS__, 'on_admin_init' ) );
    }

    /**
     * `admin_enqueue_scripts`.
     *
     * @return void
     */
    public static function on_enqueue_scripts() {
        self::enqueue();
    }

    /**
     * `admin_init`.
     *
     * @return void
     */
    public static function on_admin_init() {
        self::release_when_over();
    }

    /**
     * The user meta key that records a dismissal.
     *
     * @return string
     */
    public static function meta_key() {
        return '_nx_offer_pointer_' . self::ID . '_dismissed';
    }

    /**
     * Whether NotificationX Pro is active. The pointer is for free sites only.
     *
     * @return bool
     */
    public static function is_pro_active() {
        /**
         * Filters whether the offer pointer treats the site as a Pro site.
         *
         * Return true to hide the pointer (and give back its shared slot) on a free site.
         *
         * @param bool $is_pro Whether NotificationX Pro is active.
         */
        return (bool) apply_filters( 'nx_offer_pointer_pro_active', NotificationX::is_pro() );
    }

    /**
     * Whether a moment is inside the campaign window (both ends included).
     *
     * @param int $now Unix timestamp.
     * @return bool
     */
    public static function in_window( $now ) {
        $now = (int) $now;

        return $now >= (int) strtotime( self::START ) && $now <= (int) strtotime( self::EXPIRE );
    }

    /**
     * Whether a screen shows the pointer: NotificationX's own pages only (`toplevel_page_nx-admin`
     * and every `notificationx_page_nx-*` submenu page). Not the WordPress Dashboard, which has
     * the offer notice.
     *
     * @param string $screen_id `get_current_screen()->id`.
     * @return bool
     */
    public static function is_screen( $screen_id ) {
        $screen_id = (string) $screen_id;

        return 'toplevel_page_nx-admin' === $screen_id
            || 0 === strpos( $screen_id, 'notificationx_page_nx-' );
    }

    /**
     * Whether NotificationX may show its pointer, given who holds the shared slot.
     *
     * Nobody holds it (the option is missing, null, empty or 0), or the holder's number is not
     * smaller than ours. A smaller number is another plugin's pointer waiting to be dismissed.
     *
     * @param mixed $current The shared option's value.
     * @return bool
     */
    public static function may_take_slot( $current ) {
        $holder = is_numeric( $current ) ? (int) $current : 0;

        return $holder <= 0 || $holder >= self::PRIORITY;
    }

    /**
     * Whether a user dismissed this campaign's pointer.
     *
     * @param int $user_id User id.
     * @return bool
     */
    public static function is_dismissed( $user_id ) {
        $user_id = (int) $user_id;

        return $user_id > 0 && '' !== (string) get_user_meta( $user_id, self::meta_key(), true );
    }

    /**
     * Every condition but the shared slot: Pro, dates, screen, capability, dismissal.
     *
     * @param string $screen_id Screen id.
     * @param int    $now       Unix timestamp.
     * @return bool
     */
    public static function is_due( $screen_id, $now ) {
        if ( self::is_pro_active() ) {
            return false;
        }

        return self::in_window( $now )
            && self::is_screen( $screen_id )
            && current_user_can( self::CAPABILITY )
            && ! self::is_dismissed( get_current_user_id() );
    }

    /**
     * What the pointer says. Plain text: the script builds the markup with DOM methods.
     *
     * @return array title, text, button, dismiss.
     */
    public static function content() {
        return array(
            'title'   => __( 'Black Friday Is On!', 'notificationx' ),
            'text'    => __( 'Up to 40% off NotificationX Pro. Unlock real-time sales and conversion notifications that turn visitors into customers.', 'notificationx' ),
            'button'  => __( 'View Deals', 'notificationx' ),
            'dismiss' => __( 'Dismiss', 'notificationx' ),
        );
    }

    /**
     * Load the pointer when it is due and the slot is free.
     *
     * @param int|null $now Unix timestamp; null reads the clock.
     * @return bool Whether the pointer was loaded.
     */
    public static function enqueue( $now = null ) {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

        if ( ! $screen || ! self::is_due( $screen->id, null === $now ? time() : $now ) ) {
            return false;
        }

        $holder = get_option( self::PRIORITY_OPTION );
        if ( ! self::may_take_slot( $holder ) ) {
            return false;
        }

        // Take the slot (or keep it), so a plugin later in the queue waits for this pointer.
        if ( ! is_numeric( $holder ) || (int) $holder !== self::PRIORITY ) {
            update_option( self::PRIORITY_OPTION, self::PRIORITY );
        }

        $path = NOTIFICATIONX_ASSETS_PATH . self::SCRIPT;

        wp_enqueue_style( 'wp-pointer' );
        wp_enqueue_script(
            self::SCRIPT_HANDLE,
            NOTIFICATIONX_ASSETS . self::SCRIPT,
            array( 'jquery', 'wp-pointer' ),
            file_exists( $path ) ? (string) filemtime( $path ) : NOTIFICATIONX_VERSION,
            true
        );

        // Not wp_localize_script(): it casts every scalar to a string.
        wp_add_inline_script(
            self::SCRIPT_HANDLE,
            'window.nxOfferPointer = ' . wp_json_encode(
                array_merge(
                    self::content(),
                    array(
                        'id'      => self::ID,
                        'target'  => self::TARGET,
                        'url'     => self::URL,
                        'edge'    => is_rtl() ? 'right' : 'left',
                        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                        'action'  => self::AJAX_ACTION,
                        'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
                    )
                )
            ) . ';',
            'before'
        );

        wp_add_inline_style( 'wp-pointer', self::css() );

        return true;
    }

    /**
     * A 14px circle with a cross, as a CSS `url()`.
     *
     * @param string $color Stroke colour.
     * @return string
     */
    private static function dismiss_icon( $color ) {
        return 'url("data:image/svg+xml,' . rawurlencode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="' . $color . '" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="8" r="6.75"/><path d="M5.6 5.6l4.8 4.8M10.4 5.6l-4.8 4.8"/></svg>' ) . '")';
    }

    /**
     * The pointer's own rules, on top of WordPress's `wp-pointer` stylesheet.
     *
     * The look is fixed: the header, the button and Dismiss are the default admin blue on white.
     * A user's admin colour scheme recolours every core pointer and primary button, so each of
     * those rules is answered here with one class more than the scheme's selector. The action row
     * puts the button on the left and Dismiss on the right (core floats both to the right), and
     * Dismiss carries an outlined circle in place of core's filled grey one.
     *
     * Every selector starts with the pointer's own class, so nothing here restyles another
     * plugin's pointer.
     *
     * @return string
     */
    public static function css() {
        $scope = '.wp-pointer.nx-offer-pointer';
        $blue  = self::COLOR;
        $hover = self::COLOR_HOVER;

        $rules = array(
            // Core sets the width inline; NotificationX's own pages make boxes border-box.
            "$scope{box-sizing:content-box;}",
            "$scope .wp-pointer-content{padding:0;background:#fff;border:1px solid #c3c4c7;border-radius:4px;box-shadow:0 3px 6px rgba(0,0,0,.08);}",
            "$scope .wp-pointer-content h3{margin:-1px -1px 0;padding:9px 18px 9px 61px;border:1px solid $blue;border-bottom:none;border-radius:4px 4px 0 0;background:$blue;color:#fff;font-size:14px;font-weight:600;line-height:1.4;}",
            "$scope .wp-pointer-content h3:before{top:6px;left:13px;width:26px;height:26px;background:#fff;color:$blue;font:normal 16px/26px dashicons;}",
            "$scope .wp-pointer-content p{margin:12px 0 13px;padding:0 21px;color:#3c434a;font-size:13px;line-height:1.42;}",
            "$scope .wp-pointer-buttons{margin:0;padding:0 17px 22px 21px;overflow:visible;}",
            "$scope .nx-offer-pointer__actions{display:flex;align-items:center;justify-content:space-between;gap:12px;}",
            "$scope .wp-pointer-buttons a{float:none;}",
            "$scope .wp-pointer-buttons a.button-primary{margin:0;min-height:28px;padding:0 11px;border:1px solid $blue;border-radius:3px;background:$blue;color:#fff;font-size:13px;font-weight:600;line-height:26px;box-shadow:none;text-shadow:none;}",
            "$scope .wp-pointer-buttons a.button-primary:hover,$scope .wp-pointer-buttons a.button-primary:focus{border-color:$hover;background:$hover;color:#fff;}",
            "$scope .wp-pointer-buttons a.button-primary:focus{box-shadow:0 0 0 1px #fff,0 0 0 3px $blue;outline:0;}",
            "$scope .wp-pointer-buttons a.close{padding-left:19px;color:$blue;font-size:13px;line-height:1.4;}",
            "$scope .wp-pointer-buttons a.close:hover,$scope .wp-pointer-buttons a.close:focus{color:$hover;}",
            "$scope .wp-pointer-buttons a.close:before{content:'';left:0;top:50%;width:14px;height:14px;margin:-7px 0 0;background:" . self::dismiss_icon( $blue ) . ' no-repeat center / 14px 14px;}',
            "$scope .wp-pointer-buttons a.close:hover:before,$scope .wp-pointer-buttons a.close:focus:before{background-image:" . self::dismiss_icon( $hover ) . ';}',
            // The arrow keeps core's white fill and grey edge under every colour scheme.
            "$scope.wp-pointer-left .wp-pointer-arrow{border-right-color:#c3c4c7;}",
            "$scope.wp-pointer-left .wp-pointer-arrow-inner{border-right-color:#fff;}",
            "$scope.wp-pointer-right .wp-pointer-arrow{border-left-color:#c3c4c7;}",
            "$scope.wp-pointer-right .wp-pointer-arrow-inner{border-left-color:#fff;}",
        );

        return implode( '', $rules );
    }

    /**
     * AJAX: record this user's dismissal and release the shared slot.
     *
     * @return void
     */
    public static function dismiss() {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'notificationx' ) ), 403 );
        }

        self::record_dismissal( get_current_user_id() );

        wp_send_json_success();
    }

    /**
     * Record a user's dismissal (kept for good) and release the shared slot.
     *
     * @param int $user_id User id.
     * @return void
     */
    public static function record_dismissal( $user_id ) {
        $user_id = (int) $user_id;

        if ( $user_id > 0 ) {
            update_user_meta( $user_id, self::meta_key(), time() );
        }

        self::release_slot();
    }

    /**
     * Give the shared slot back, only while NotificationX holds it.
     *
     * @return bool Whether the slot was released.
     */
    public static function release_slot() {
        $holder = get_option( self::PRIORITY_OPTION );

        if ( ! is_numeric( $holder ) || (int) $holder !== self::PRIORITY ) {
            return false;
        }

        return (bool) update_option( self::PRIORITY_OPTION, null );
    }

    /**
     * Release a slot nobody dismissed once the pointer can no longer show (the campaign is over,
     * or Pro was activated), so a plugin later in the queue is not kept waiting for a pointer
     * that will never come back.
     *
     * @param int|null $now Unix timestamp; null reads the clock.
     * @return bool Whether the slot was released.
     */
    public static function release_when_over( $now = null ) {
        $now = null === $now ? time() : (int) $now;

        if ( $now > (int) strtotime( self::EXPIRE ) || self::is_pro_active() ) {
            return self::release_slot();
        }

        return false;
    }
}
