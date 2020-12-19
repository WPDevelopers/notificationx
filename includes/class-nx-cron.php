<?php
/**
 * This class is responsible for Cron Jobs
 * for NotificationX & NotificationX Pro
 */
class NotificationX_Cron {
    /**
	 * Cron hook.
	 * @var string $hook
	 */
	public static $hook = 'nx_cron_update_data';

	/**
	 * Schedule cron jobs.
	 * @param int $post_id
	 * @param string $cache_key
	 */
	public static function set_cron( $post_id, $cache_key = 'nx_cache_interval' ) {
		if ( ! $post_id || empty( $post_id ) ) {
			return;
		}
		// First clear previously scheduled cron hook.
		self::clear_schedule( array( 'post_id' => $post_id ) );

        // If there is no next event, start cron now.
        if ( ! wp_next_scheduled( self::$hook, array( 'post_id' => $post_id ) ) ) {
			wp_schedule_event( current_time('timestamp'), $cache_key, self::$hook, array( 'post_id' => $post_id ) );
        }
	}
	/**
	 * Clearing Schedule
	 * @param array $args
	 * @since 1.1.3
	 */
	public static function clear_schedule( $args = array() ){
		if( empty( $args ) ) {
			return false;
		}
		return wp_clear_scheduled_hook( self::$hook, $args );
	}
	/**
     * This method is responsible for cron schedules
     *
     * @param array $schedules
     * @return array
	 * @since 1.1.3
     */
    public static function cron_schedule( $schedules ){
        $download_stats_cache_duration = NotificationX_DB::get_settings( 'download_stats_cache_duration' );
		$reviews_cache_duration = NotificationX_DB::get_settings( 'reviews_cache_duration' );

        if ( ! $download_stats_cache_duration || empty( $download_stats_cache_duration ) ) {
            $download_stats_cache_duration = 3;
        }
        if ( ! $reviews_cache_duration || empty( $reviews_cache_duration ) ) {
            $reviews_cache_duration = 3;
        }

        $schedules['nx_wp_stats_interval'] = array(
            'interval'	=> MINUTE_IN_SECONDS * $download_stats_cache_duration,
            'display'	=> sprintf( __('Every %s minutes', 'notificationx'), $download_stats_cache_duration )
		);

        $schedules['nx_wp_review_interval'] = array(
            'interval'	=> MINUTE_IN_SECONDS * $reviews_cache_duration,
            'display'	=> sprintf( __('Every %s minutes', 'notificationx'), $reviews_cache_duration )
		);

		$schedules = apply_filters('nx_cron_schedules', $schedules);

        return $schedules;
    }
}