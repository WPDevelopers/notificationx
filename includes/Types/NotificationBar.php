<?php

/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Types;

use NotificationX\Core\Helper;
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
        add_action('nx_show_on_exclude', [$this, 'show_on_exclude'], 10, 4);
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
                strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false) {
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

                $from_time = strtotime(date('Y-m-d ') . date('H:i:s', strtotime($settings['daily_from_time'])));
                $to_time = strtotime(date('Y-m-d ') . date('H:i:s', strtotime($settings['daily_to_time'])));

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

                $current_day = strtolower(date('l', $current_time));
                $from_time = strtotime(date('Y-m-d ') . date('H:i:s', strtotime($settings['weekly_from_time'])));
                $to_time = strtotime(date('Y-m-d ') . date('H:i:s', strtotime($settings['weekly_to_time'])));

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

            // Custom schedule check (placeholder for future implementation)
            if ($settings['schedule_type'] === 'custom') {
                // Custom schedule logic will be implemented later
                // For now, we'll just show the notification
            }
        }

        // 3. Country Targeting
        if (!empty($settings['country_targeting']) &&
            !in_array('all', $settings['country_targeting'])) {

            $visitor_country = Helper::nx_get_visitor_country_code();
            if (empty($visitor_country)) {
                return true;
            }
            // If we couldn't determine the country or it's not in the target list
            if ($visitor_country !== 'all' && !in_array($visitor_country, $settings['country_targeting'])) {
                return true;
            }
        }

        // 4. User Role Targeting
        if (!empty($settings['targeting_user_roles']) &&
            !in_array('all_users', $settings['targeting_user_roles'])) {

            // For logged out users
            if (!is_user_logged_in() && !in_array('guest', $settings['targeting_user_roles'])) {
                return true;
            }

            // For logged in users
            if (is_user_logged_in()) {
                $user = wp_get_current_user();
                $user_roles = (array) $user->roles;

                // Check if any of the user's roles match the targeted roles
                $has_targeted_role = false;
                foreach ($user_roles as $role) {
                    if (in_array($role, $settings['targeting_user_roles'])) {
                        $has_targeted_role = true;
                        break;
                    }
                }

                if (!$has_targeted_role) {
                    return true;
                }
            }
        }

        // If we've made it here, don't exclude the notification
        return $exclude;
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
