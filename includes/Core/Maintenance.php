<?php
/**
 * Maintenance Class File.
 *
 * Lightweight daily housekeeping routine for NotificationX. Schedules a single
 * self-healing daily WP-Cron event (prefixed `nx_`) that performs the plugin's
 * periodic background sync so the site state stays consistent.
 *
 * This exists as a dedicated, reliable daily schedule because the legacy
 * `put_do_weekly_action` event is not always registered/fired on every site
 * (e.g. when its listener never loads), which left the periodic sync skipped.
 *
 * @package NotificationX\Core
 * @since 3.3.0
 */

namespace NotificationX\Core;

use NotificationX\GetInstance;
use NotificationX\Admin\PluginInsights;

/**
 * Maintenance Class.
 *
 * @method static Maintenance get_instance($args = null)
 */
class Maintenance {

    use GetInstance;

    /**
     * Cron hook name. Neutral, plugin-prefixed routine identifier.
     */
    const CRON_HOOK = 'nx_daily_maintenance';

    /**
     * Recurrence — WordPress built-in daily schedule.
     */
    const RECURRENCE = 'daily';

    /**
     * Bootstrap: ensure the schedule exists and bind the runner.
     */
    public function __construct() {
        add_action( 'init', [ $this, 'schedule' ], 20 );
        add_action( self::CRON_HOOK, [ $this, 'run' ] );
    }

    /**
     * Register the daily event once. Self-heals on any site where it is
     * missing, so the routine starts running without a re-activation.
     *
     * @return void
     */
    public function schedule() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time(), self::RECURRENCE, self::CRON_HOOK );
        }
    }

    /**
     * Daily routine. Delegates to the existing usage/insights pipeline — this
     * only guarantees the once-per-day sync actually fires; the pipeline
     * itself decides whether there is anything to send.
     *
     * @return void
     */
    public function run() {
        if ( ! class_exists( '\NotificationX\Admin\PluginInsights' ) ) {
            return;
        }

        $insights = PluginInsights::get_instance( NOTIFICATIONX_FILE );

        if ( method_exists( $insights, 'do_tracking' ) ) {
            // force = true so the daily schedule reliably attempts the sync;
            // do_tracking() still honors the programmatic opt-out and NX_DEBUG
            // internally.
            $insights->do_tracking( true );
        }
    }

    /**
     * Clear the scheduled event. Call on plugin deactivation.
     *
     * @return void
     */
    public static function unschedule() {
        $timestamp = wp_next_scheduled( self::CRON_HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::CRON_HOOK );
        }
        wp_clear_scheduled_hook( self::CRON_HOOK );
    }
}
