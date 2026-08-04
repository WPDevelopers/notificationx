<?php
/**
 * Targeting
 *
 * Country- and user-role-based targeting for ALL notification types.
 *
 * Historically these two fields lived inside the PressBar extension and were
 * enforced inside the NotificationBar type, so targeting only worked for the
 * Notification Bar and only while the bar module was enabled. This class
 * decouples the feature from the bar: it registers the fields on the shared
 * `nx_customize_fields` schema (so every type exposes them) and enforces them
 * on the shared `nx_show_on_exclude` filter (applied for every notification in
 * FrontEnd::get_notifications_ids()), regardless of which modules are active.
 *
 * @package NotificationX\Core
 */

namespace NotificationX\Core;

use NotificationX\Admin\InfoTooltipManager;
use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;

/**
 * @method static Targeting get_instance($args = null)
 */
class Targeting {
    /**
     * Instance of Targeting
     *
     * @var Targeting
     */
    use GetInstance;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        add_filter('nx_customize_fields', [$this, 'add_targeting_fields'], 20);
        add_filter('nx_show_on_exclude', [$this, 'show_on_exclude'], 10, 2);
    }

    /**
     * Get all WordPress roles dynamically.
     *
     * @return array
     */
    public function get_roles() {
        return wp_roles()->role_names;
    }

    /**
     * Inject the "Targeting" section (country + user role) into the shared
     * field schema so it appears for every notification type.
     *
     * @param array $fields Field schema.
     * @return array
     */
    public function add_targeting_fields($fields) {
        // Safety: if some extension already registered the section, don't duplicate.
        if (isset($fields['targeting'])) {
            return $fields;
        }

        $wp_roles = $this->get_roles();

        // Show the Targeting section for every notification type. The two
        // "special" sources (GDPR + Exit Intent) are excluded, mirroring the
        // core `appearance` section rule — a section without any rule is not
        // rendered by the QuickBuilder form engine.
        $targeting_rules = Rules::logicalRule([
            Rules::is('source', 'gdpr_notification', true),
            Rules::is('source', 'exit_intent_custom', true),
        ], 'and');

        $fields['targeting'] = [
            'label'    => __('Targeting', 'notificationx'),
            'name'     => 'targeting',
            'type'     => 'section',
            'id'       => 'targeting',
            'classes'  => 'nx-targeting',
            'priority' => 100,
            'info'     => InfoTooltipManager::get_instance()->render('targeting'),
            'rules'    => $targeting_rules,
            'fields'   => [],
        ];

        // Country Targeting
        $fields['targeting']['fields']['country_targeting'] = [
            'label'    => __('Country Targeting', 'notificationx'),
            'name'     => 'country_targeting',
            'type'     => 'better-select',
            'priority' => 10,
            'is_pro'   => true,
            'multiple' => true,
            'values'   => ['label' => "All Country", 'value' => 'all'],
            'option'   => GlobalFields::get_instance()->normalize_fields(Helper::nx_get_all_country()),
            'ajax'     => [
                'api'  => "/notificationx/v1/get-data",
                'data' => [
                    'type'   => "@type",
                    'source' => "@source",
                    'field'  => "country_targeting",
                ],
            ],
            'info'     => InfoTooltipManager::get_instance()->render('targeting'),
        ];

        // User Role Targeting. Includes a "guest" option so logged-out visitors
        // can be TARGETED (not just excluded) — the enforcement in
        // show_on_exclude() already honors 'guest', it was just never selectable.
        $wp_roles_with_default = array_merge(
            [
                'all_users' => __('Show for All Users', 'notificationx'),
                'guest'     => __('Logged-out Visitors (Guests)', 'notificationx'),
            ],
            is_array($wp_roles) ? $wp_roles : []
        );
        $fields['targeting']['fields']['targeting_user_roles'] = [
            'label'    => __('Set Target Audience', 'notificationx'),
            'name'     => 'targeting_user_roles',
            'type'     => 'select',
            'priority' => 20,
            'is_pro'   => true,
            'default'  => ['all_users'],
            'options'  => GlobalFields::get_instance()->normalize_fields($wp_roles_with_default),
            'multiple' => true,
            'info'     => InfoTooltipManager::get_instance()->render('targeting'),
        ];

        return $fields;
    }

    /**
     * Enforce country- and user-role-based targeting for every notification.
     *
     * Runs on `nx_show_on_exclude` (applied in FrontEnd::get_notifications_ids()
     * for all sources), so it works for any notification type that has these
     * settings, not just the Notification Bar.
     *
     * @param bool  $exclude  Current exclusion status.
     * @param array $settings Notification settings.
     * @return bool True if the notification should be excluded.
     */
    public function show_on_exclude($exclude, $settings) {
        // Already excluded by an earlier handler — nothing to add.
        if ($exclude) {
            return true;
        }

        // 1. Country Targeting
        if (!empty($settings['country_targeting']) && is_array($settings['country_targeting'])) {
            // The better-select stores each selection as a {value,label} object;
            // fall back to plain strings for legacy/imported settings.
            $countryValues = array_column($settings['country_targeting'], 'value');
            if (empty($countryValues)) {
                $countryValues = array_filter($settings['country_targeting'], 'is_string');
            }
            $normalizedCountries = array_map('strtoupper', $countryValues);

            // Only enforce when a specific country list is set — 'ALL' means every
            // country, so there is nothing to filter.
            if (!in_array('ALL', $normalizedCountries, true)) {
                $visitor_country = strtoupper((string) Helper::nx_get_visitor_country_code());

                // Fail open: if the visitor's country can't be resolved (geo lookup
                // failed/rate-limited, CLI, or local testing) do NOT hide the
                // notification. Silently hiding a country-targeted notification for
                // everyone during an API outage is worse than showing it slightly
                // wider, so an unknown country simply skips country filtering.
                if ('' !== $visitor_country && !in_array($visitor_country, $normalizedCountries, true)) {
                    return true;
                }
            }
        }

        // 2. User Role Targeting
        if (!empty($settings['targeting_user_roles']) &&
            !in_array('all_users', $settings['targeting_user_roles'])) {

            // For logged out users
            if (!is_user_logged_in() && !in_array('guest', $settings['targeting_user_roles'])) {
                return true;
            }

            // For logged in users
            if (is_user_logged_in()) {
                $user       = wp_get_current_user();
                $user_roles = (array) $user->roles;

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

        return $exclude;
    }

    /**
     * REST handler that powers the country-search AJAX for the
     * country_targeting field (routed by field, for every type).
     *
     * @param array $params Request params.
     * @return array
     */
    public static function restResponse($params) {
        if (empty($params['inputValue'])) {
            return [];
        }
        return array_values(GlobalFields::get_instance()->normalize_fields(Helper::nx_get_all_country($params['inputValue'])));
    }
}
