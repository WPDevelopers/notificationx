<?php

/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Types;

use NotificationX\Core\Helper;
use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;
use NotificationX\Modules;

/**
 * Extension Abstract for all Extension.
 * @method static NotificationBar get_instance($args = null)
 */
class NotificationBar extends Types {
    /**
     * Instance of Admin
     *
     * @var Admin
     */
    use GetInstance;

    public $priority       = 15;
    public $themes         = [];
    public $module         = [];
    public $default_source = 'press_bar';
    public $link_type      = '-1';


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        $this->id = 'notification_bar';
        add_filter('nx_show_on_exclude', [$this, 'show_on_exclude'], 10, 2);
    }
    


    /**
     * Determines whether to exclude the notification bar from showing
     * based on various conditions like bar reappearance, schedule settings,
     * country targeting, and user role targeting.
     *
     * @param bool $exclude Current exclusion status
     * @param array $settings Notification settings
     * @return bool True if should be excluded, false otherwise
     */
    public function show_on_exclude($exclude, $settings) {
        // Only process if this is a PressBar notification
        // if (empty($settings['type']) || $settings['type'] !== $this->id) {
        //     return $exclude;
        // }

        // 1. Bar Reappearance settings
        if (!empty($settings['bar_reappearance'])) {
            $cookie_name = 'nx_bar_' . $settings['nx_id'];

            // Check if the bar should be permanently hidden for this user
            if ($settings['bar_reappearance'] === 'dont_show_welcomebar' && isset($_COOKIE[$cookie_name])) {
                return true;
            }

            // Check if the bar should only show on new visits (not refreshes/page changes)
            if ($settings['bar_reappearance'] === 'show_welcomebar_next_visit' &&
                isset($_COOKIE[$cookie_name]) &&
                $_COOKIE[$cookie_name] === 'shown' &&
                !empty($_SERVER['HTTP_REFERER']) &&
                strpos(
                    esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])),
                    isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : ''
                ) !== false) {
                return true;
            }
        }

        // 2. Schedule Settings
        if (!empty($settings['schedule_type'])) {
            $current_time = current_time('timestamp', true);

            // Daily schedule check
            if ($settings['schedule_type'] === 'daily' &&
                !empty($settings['daily_from_time']) &&
                !empty($settings['daily_to_time'])) {

                $from_time = strtotime(gmdate('Y-m-d ') . gmdate('H:i:s', strtotime($settings['daily_from_time'])));
                $to_time = strtotime(gmdate('Y-m-d ') . gmdate('H:i:s', strtotime($settings['daily_to_time'])));

                // Handle case where to_time is on the next day
                if ($to_time < $from_time) {
                    $to_time += 86400; // Add 24 hours
                }

                if ($current_time < $from_time || $current_time > $to_time) {
                    return true;
                }
            }

            // Weekly schedule check
            if ($settings['schedule_type'] === 'weekly' &&
                !empty($settings['weekly_days']) &&
                !empty($settings['weekly_from_time']) &&
                !empty($settings['weekly_to_time'])) {

                $current_day = strtolower(gmdate('l', $current_time));
                $from_time = strtotime(gmdate('Y-m-d ') . gmdate('H:i:s', strtotime($settings['weekly_from_time'])));
                $to_time = strtotime(gmdate('Y-m-d ') . gmdate('H:i:s', strtotime($settings['weekly_to_time'])));

                // Handle case where to_time is on the next day
                if ($to_time < $from_time) {
                    $to_time += 86400; // Add 24 hours
                }

                // Check if current day is in the selected days
                $show_today = false;
                foreach ($settings['weekly_days'] as $day) {
                    if (strtolower($day) === $current_day) {
                        $show_today = true;
                        break;
                    }
                }

                if (!$show_today || $current_time < $from_time || $current_time > $to_time) {
                    return true;
                }
            }
            // Custom schedule check
            if (
                $settings['schedule_type'] === 'custom' &&
                !empty($settings['custom_schedule']['startDate']) &&
                !empty($settings['custom_schedule']['endDate']) &&
                !empty($settings['custom_from_time']) &&
                !empty($settings['custom_to_time'])
            ) {
               // Parse date range (strip timezone)
                $start_date       = strtotime(substr($settings['custom_schedule']['startDate'], 0, 10));
                $end_date         = strtotime(substr($settings['custom_schedule']['endDate'], 0, 10));
                $current_date     = strtotime(gmdate('Y-m-d', $current_time));                            // Current day in GMT
                $custom_from_time = $settings['custom_from_time'];
                $custom_to_time   = $settings['custom_to_time'];
                // Check if current date is outside range
                if ($current_date < $start_date || $current_date > $end_date) {
                    return true;
                }

                // Combine current date with from/to times
                $from_time = strtotime(gmdate('Y-m-d ') . gmdate('H:i:s', strtotime($custom_from_time)));
                $to_time = strtotime(gmdate('Y-m-d ') . gmdate('H:i:s', strtotime($custom_to_time)));

                // Handle overnight time ranges (e.g., 10 PM - 6 AM)
                if ($to_time < $from_time) {
                    $to_time += 86400; // add 24 hours
                     if ($current_time < $from_time) {
                        $current_time += 86400;
                    }
                }

                // Check if current timestamp is outside the daily time range
                if ($current_time < $from_time || $current_time > $to_time) {
                    return true;
                }
            }
        }

        // Country & user-role targeting are enforced globally for every type in
        // NotificationX\Core\Targeting (also hooked on nx_show_on_exclude).

        // If we've made it here, don't exclude the notification
        return $exclude;
    }

    public static function restResponse($params) {
         if (empty($params['inputValue'])) {
            return [];
        }
        return array_values(GlobalFields::get_instance()->normalize_fields(Helper::nx_get_all_country($params['inputValue'])));
    }


    /**
     * Runs when modules is enabled.
     *
     * @return void
     */
    public function init() {
        parent::init();
        $this->title = __('Notification Bar', 'notificationx');
    }


}
