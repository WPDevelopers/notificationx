<?php

class NotificationX_Cron {
    /**
	 * Holds cron hook.
	 * 
	 * @since 1.1.2
	 * @var string $hook
	 */
	public static $hook = 'nx_cron_update_data';

	/**
	 * Schedule cron jobs.
	 * 
	 * @since 1.1.2
	 * @param int $post_id
	 */
	public static function set_cron( $post_id, $cache_key = 'nx_cache_interval' ) {
		if ( ! $post_id || empty( $post_id ) ) {
			return;
		}
		// First clear previously scheduled cron hook.
        wp_clear_scheduled_hook( self::$hook, array( 'post_id' => $post_id ) );

        // If there is no next event, start cron now.
        if ( ! wp_next_scheduled( self::$hook, array( 'post_id' => $post_id ) ) ) {
			wp_schedule_event( time(), $cache_key, self::$hook, array( 'post_id' => $post_id ) );
        }
	}
}