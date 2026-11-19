<?php
/**
 * Live Visitors Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\LiveVisitors;

use NotificationX\Core\Modules;
use NotificationX\Core\Rules;
use NotificationX\Extensions\Extension;
use NotificationX\GetInstance;
use NotificationX\NotificationX;

/**
 * Built-in live visitor count for Page Analytics, without Google Analytics.
 *
 * This is only the source card and its module: NotificationX Pro counts the
 * visitors (its mirror class `NotificationXPro\Extensions\LiveVisitors\LiveVisitors`)
 * and says so through the `nx_live_visitors_supported` filter. Until a
 * notification uses this source, nothing runs on the site.
 *
 * @since x.x.x
 * @method static LiveVisitors get_instance($args = null)
 */
class LiveVisitors extends Extension {
    /**
     * Instance of LiveVisitors
     *
     * @var LiveVisitors
     */
    use GetInstance;

    const ID     = 'live_visitors';
    const MODULE = 'modules_live_visitors';

    /**
     * The Page Analytics designs that show a live count. The others show
     * totals over days, which a live count cannot fill.
     *
     * @var string[]
     */
    const SUPPORTED_THEMES     = [ 'pa-theme-three' ];
    const SUPPORTED_RES_THEMES = [ 'res-pa-theme-three' ];

    public $priority        = 6;
    public $id              = self::ID;
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/live-visitors.png';
    public $doc_link        = 'https://notificationx.com/docs/';
    public $types           = 'page_analytics';
    public $module          = self::MODULE;
    public $module_priority = 20;
    public $is_pro          = true;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
    }

    public function init_extension() {
        $this->title        = __( 'Built-in (no Google Analytics)', 'notificationx' );
        $this->module_title = __( 'Live Visitors', 'notificationx' );
        $this->popup        = [
            'denyButtonText'    => __( "<a href='https://notificationx.com/docs/' target='_blank'>More Info</a>", 'notificationx' ),
            'confirmButtonText' => __( "<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", 'notificationx' ),
            // phpcs:disable PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- False positive for this context: remote documentation/pricing links in admin help text, not offloaded plugin assets.
            'html'              => '<span>' . __( 'Show how many people are on your site right now, counted by NotificationX itself. No Google Analytics account needed.', 'notificationx' ) . '</span>',
            // phpcs:enable PluginCheck.CodeAnalysis.Offloading.OffloadedContent
        ];
    }

    public function public_actions() {
        parent::public_actions();
        // An outdated Pro registers no counter, so never print this source's
        // notifications for it: they would show without a number.
        add_filter( 'nx_show_on_exclude', [ $this, 'exclude_unsupported' ], 10, 2 );
    }

    /**
     * Whether the active NotificationX Pro counts live visitors.
     *
     * @return bool
     */
    public static function is_supported() {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- nx_ is this plugin's hook prefix.
        return NotificationX::is_pro() && (bool) apply_filters( 'nx_live_visitors_supported', false );
    }

    /**
     * Whether the Growth Alert "live viewers" design can get a count: from
     * Google Analytics, or from this built-in counter.
     *
     * @return bool
     */
    public static function live_viewers_available() {
        $modules   = Modules::get_instance();
        $available = $modules->is_enabled( 'modules_google_analytics' )
            || ( $modules->is_enabled( self::MODULE ) && self::is_supported() );
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- nx_ is this plugin's hook prefix.
        return (bool) apply_filters( 'nx_live_viewers_available', $available );
    }

    /**
     * Locks the source card with an update prompt when the active Pro does
     * not count live visitors yet. Without Pro, the usual upsell stays.
     *
     * @param array $sources
     * @return array
     */
    public function __nx_sources( $sources ) {
        $sources = parent::__nx_sources( $sources );
        if ( NotificationX::is_pro() && ! self::is_supported() ) {
            $last                       = count( $sources ) - 1;
            $sources[ $last ]['is_pro'] = true;
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- nx_ is this plugin's hook prefix.
            $sources[ $last ]['popup'] = apply_filters( 'nx_pro_alert_popup', [
                'title'             => __( 'NotificationX Pro needs an update.', 'notificationx' ),
                'html'              => esc_html__( 'Update NotificationX Pro to count live visitors without Google Analytics.', 'notificationx' ),
                'confirmButtonText' => sprintf( '<a href="%s">%s</a>', esc_url( self_admin_url( 'plugins.php' ) ), esc_html__( 'Update', 'notificationx' ) ),
                // The Pro alert buttons take their padding from an inner link.
                // No href: the button itself closes the popup.
                'denyButtonText'    => sprintf( '<a style="cursor: pointer;">%s</a>', esc_html__( 'Close', 'notificationx' ) ),
                // Shown even though Pro is active (the builder skips Pro alerts then).
                'forced'            => true,
            ] );
        }
        return $sources;
    }

    /**
     * Page Analytics designs, limited to the ones that show a live count.
     *
     * @return array
     */
    public function get_themes() {
        return $this->only_themes( parent::get_themes(), self::SUPPORTED_THEMES );
    }

    /**
     * Responsive Page Analytics designs, limited like get_themes().
     *
     * @return array
     */
    public function get_res_themes() {
        return $this->only_themes( parent::get_res_themes(), self::SUPPORTED_RES_THEMES );
    }

    /**
     * Keeps only the given type-level themes. Extension::get_themes() prefixes
     * the type's theme names with "{$this->types}_" when the extension declares
     * none of its own, so the kept keys carry that prefix. Add-ons can add their
     * own live designs (e.g. a Verified Cards theme) with `nx_live_visitors_themes`.
     *
     * @param array    $themes
     * @param string[] $supported
     * @return array
     */
    protected function only_themes( $themes, $supported ) {
        if ( ! is_array( $themes ) ) {
            return $themes;
        }
        $keep = [];
        foreach ( $supported as $name ) {
            $keep[] = $this->types . '_' . $name;
        }
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- nx_ is this plugin's hook prefix.
        $keep = (array) apply_filters( 'nx_live_visitors_themes', $keep, $themes );
        return array_intersect_key( $themes, array_flip( $keep ) );
    }

    /**
     * Notification Template options: a live count has no "Total Site View",
     * so that option is hidden for this source.
     *
     * @param array $templates
     * @return array
     */
    public function __notification_template( $templates ) {
        $templates = parent::__notification_template( $templates );
        if ( ! empty( $templates['first_param']['options']['tag_siteview'] ) ) {
            $templates['first_param']['options']['tag_siteview'] = Rules::includes(
                'source',
                [ $this->id ],
                true,
                $templates['first_param']['options']['tag_siteview']
            );
        }
        return $templates;
    }

    /**
     * Keeps this source's notifications off the page when the active Pro
     * cannot count visitors (see public_actions()).
     *
     * @param bool  $exclude
     * @param array $settings
     * @return bool
     */
    public function exclude_unsupported( $exclude, $settings ) {
        if ( ! $exclude && isset( $settings['source'] ) && self::ID === $settings['source'] && ! self::is_supported() ) {
            return true;
        }
        return $exclude;
    }

    /**
     * Get data for the Live Visitors Extension.
     *
     * @param array $args Settings arguments.
     * @return array
     */
    public function get_data( $args = array() ){
        return 'Hello From Live Visitors';
    }

    public function doc(){
        return sprintf(
            '<p>%s</p><p>%s</p>',
            esc_html__( 'NotificationX counts the people on your site itself: no Google Analytics account is needed. Visitors are counted without cookies, and no IP address is stored.', 'notificationx' ),
            esc_html__( 'If a GDPR notice is active, only visitors who allowed analytics are counted.', 'notificationx' )
        );
    }
}
