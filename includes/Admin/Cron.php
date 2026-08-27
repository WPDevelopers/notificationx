<?php
namespace NotificationX\Admin;

use NotificationX\Core\PostType;
use NotificationX\GetInstance;

/**
 * This class is responsible for Cron Jobs
 * for NotificationX & NotificationX Pro
 * @method static Cron get_instance($args = null)
 */
class Cron {
    /**
     * Instance of Cron
     *
     * @var Cron
     */
    use GetInstance;

    /**
     * Cron hook.
     * @var string $hook
     */
    public $hook = 'nx_cron_update_data';

    public function __construct(){
        add_filter('cron_schedules', [$this, 'cron_schedule'], 10, 1);
        add_action($this->hook, array($this, 'update_data'), 10, 1);
        add_action('nx_delete_post', array($this, 'delete_post'), 10, 1);

    }

    /**
     * Schedule cron jobs.
     *
     * Callers that run before `init` (migrations and upgrade routines hooked to
     * `plugins_loaded`, for instance) are deferred rather than refused:
     * wp_schedule_event() -> wp_get_schedules() fires the `cron_schedules`
     * filter, and its callbacks -- ours, NX Pro's, WooCommerce's -- translate
     * their interval labels, which before `init` trips WordPress'
     * _load_textdomain_just_in_time "called too early" notice (WP 6.7+).
     * Guarding here rather than at each call site keeps the whole class of bug
     * closed, whatever hooks a future caller happens to run on.
     *
     * @param int $post_id
     * @param string $cache_key
     */
    public function set_cron($post_id, $cache_key = 'nx_cache_interval') {
        if (!$post_id || empty($post_id)) {
            return;
        }

        if (!did_action('init')) {
            add_action('init', function () use ($post_id, $cache_key) {
                $this->set_cron($post_id, $cache_key);
            }, 20);
            return;
        }

        // First clear previously scheduled cron hook.
        $this->clear_schedule((int) $post_id);

        // If there is no next event, start cron now.
        if (!wp_next_scheduled($this->hook, array((int) $post_id))) {
            wp_schedule_event(time(), $cache_key, $this->hook, array((int) $post_id));
        }
    }

    /**
     * Schedule cron jobs.
     * @param int $post_id
     * @param string $cache_key
     */
    public function set_cron_single($post_id) {
        if (!$post_id || empty($post_id)) {
            return;
        }

        // If there is no next event, start cron now.
        if (!wp_next_scheduled($this->hook, array((int) $post_id))) {
            wp_schedule_single_event(time() + 10, $this->hook, array((int) $post_id));
        }
    }

    /**
     * Clearing Schedule
     * @param array $args
     * @since 1.1.3
     */
    public function clear_schedule($post_id) {
        if (empty($post_id)) {
            return false;
        }
        return wp_clear_scheduled_hook($this->hook, array((int) $post_id));
    }

    /**
     * This method is responsible for cron schedules
     *
     * @param array $schedules
     * @return array
     * @since 1.1.3
     */
    public function cron_schedule($schedules) {
        $download_stats_cache_duration = Settings::get_instance()->get('settings.download_stats_cache_duration', 3);
        $reviews_cache_duration = Settings::get_instance()->get('settings.reviews_cache_duration', 3);

        $schedules['nx_wp_stats_interval'] = array(
            'interval'    => MINUTE_IN_SECONDS * $download_stats_cache_duration,
            // translators: %s: no of minutes
            'display'    => sprintf(__('Every %s minutes', 'notificationx'), $download_stats_cache_duration)
        );

        $schedules['nx_wp_review_interval'] = array(
            'interval'    => MINUTE_IN_SECONDS * $reviews_cache_duration,
            // translators: %s: no of minutes
            'display'    => sprintf(__('Every %s minutes', 'notificationx'), $reviews_cache_duration)
        );

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
        $schedules = apply_filters('nx_cron_schedules', $schedules);

        return $schedules;
    }

    public function update_data($post_id) {
        if (empty($post_id)) {
            return;
        }
        $post = PostType::get_instance()->get_post($post_id);

        if(!empty($post['source']) && !empty($post['enabled'])){
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
            do_action("{$this->hook}_{$post['source']}", $post_id, $post);
        }
        else{
            $this->clear_schedule($post_id);
        }
    }

    public function delete_post($post_id) {
        $this->clear_schedule($post_id);
    }
}
