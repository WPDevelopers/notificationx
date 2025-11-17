<?php

/**
 * Milestone Notification for WordPress Admin Dashboard
 *
 * Displays a beautiful animated notification on the main WordPress dashboard (/wp-admin/)
 * showing milestone achievements and encouraging premium upgrades.
 *
 * Features:
 * - Automatically appears on dashboard after 2 seconds
 * - Slides in from bottom-right with smooth animation
 * - Fully responsive design
 * - Close on button click, overlay click, or Escape key
 * - Customizable milestone data and stats
 *
 * Usage:
 * - Automatically initialized in NotificationX main class
 * - Only loads on WordPress dashboard (index.php)
 * - Customize data in get_milestone_data() method
 * - Control display logic in should_show_milestone() method
 *
 * @package     NotificationX
 * @author      NotificationX <help@notificationx.com>
 * @copyright   Copyright (C) 2023 WPDeveloper. All rights reserved.
 * @license     GPLv3 or later
 * @since       1.0.0
 */

namespace NotificationX\Admin;

use NotificationX\GetInstance;
use NotificationX\Core\Analytics;
use NotificationX\Core\Helper;

defined('ABSPATH') or die("No direct script access allowed.");

class MilestoneNotification
{
    use GetInstance;

    /**
     * Constructor - Initialize the milestone notification
     */
    public function __construct()
    {
        // Hook into admin footer to inject the notification
        add_action('admin_footer', [$this, 'render_milestone_notification']);

        // Enqueue styles and scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // AJAX handler to mark milestone as seen
        add_action('wp_ajax_notificationx_mark_milestone_seen', [$this, 'ajax_mark_milestone_seen']);
    }

    /**
     * Enqueue CSS and JS for milestone notification
     */
    public function enqueue_assets($hook)
    {
        // Only load on main dashboard page
        if ($hook !== 'index.php') {
            return;
        }

        // Enqueue the milestone CSS - use existing admin CSS file
        wp_enqueue_style(
            'notificationx-milestone',
            Helper::file('admin/css/admin.css', true),
            [],
            NOTIFICATIONX_VERSION
        );

        // Enqueue inline script for milestone functionality
        wp_add_inline_script('jquery', $this->get_milestone_script());
    }

    /**
     * Get the JavaScript for milestone functionality
     */
    private function get_milestone_script()
    {
        return "
        jQuery(document).ready(function($) {
            // Auto-show milestone after 2 seconds
            setTimeout(function() {
                showNotificationXMilestone();
            }, 2000);
        });

        function showNotificationXMilestone() {
            var container = document.getElementById('notificationx-milestone-container');
            if (!container) return;

            container.style.display = 'block';

            // Trigger animation
            setTimeout(function() {
                var overlay = container.querySelector('.milestone-overlay');
                var notification = container.querySelector('.milestone-notification');

                if (overlay) overlay.classList.add('milestone-overlay--visible');
                if (notification) notification.classList.add('milestone-notification--visible');
            }, 100);
        }

        function hideNotificationXMilestone(event) {
            if (event) event.preventDefault();

            var container = document.getElementById('notificationx-milestone-container');
            if (!container) return;

            var overlay = container.querySelector('.milestone-overlay');
            var notification = container.querySelector('.milestone-notification');

            if (overlay) overlay.classList.remove('milestone-overlay--visible');
            if (notification) notification.classList.remove('milestone-notification--visible');

            // Remove from DOM after animation
            setTimeout(function() {
                container.style.display = 'none';
            }, 400);

            // Mark milestone as seen via AJAX
            jQuery.post(ajaxurl, {
                action: 'notificationx_mark_milestone_seen',
                nonce: '" . wp_create_nonce('notificationx_milestone_nonce') . "'
            });
        }

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideNotificationXMilestone();
            }
        });

        // Close on overlay click
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('milestone-overlay')) {
                hideNotificationXMilestone();
            }
        });
        ";
    }

    /**
     * Render the milestone notification HTML
     */
    public function render_milestone_notification()
    {
        // Only show on main dashboard
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'dashboard') {
            return;
        }

        // Check if milestone should be shown
        if (!$this->should_show_milestone()) {
            return;
        }

        // Get milestone data
        $data = $this->get_milestone_data();

?>
        <div id="notificationx-milestone-container" style="display: none;">
            <div class="milestone-overlay">
                <div class="milestone-notification" onclick="event.stopPropagation()">
                    <!-- Header -->
                    <div class="milestone-header">
                        <h2 class="milestone-title">
                            <?php echo esc_html__('Your Milestones', 'notificationx'); ?>
                        </h2>
                        <button class="milestone-close" onclick="hideNotificationXMilestone(event)" aria-label="Close">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14 1.41L12.59 0L7 5.59L1.41 0L0 1.41L5.59 7L0 12.59L1.41 14L7 8.41L12.59 14L14 12.59L8.41 7L14 1.41Z" fill="currentColor" />
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="milestone-content">
                        <!-- Achievement Banner -->
                        <div class="milestone-achievement">
                            <h3 class="milestone-achievement-title">
                                <?php echo $data['emoji']; ?> <?php echo wp_kses_post($data['title']); ?>
                            </h3>
                            <p class="milestone-achievement-subtitle">
                                <?php echo wp_kses_post($data['subtitle']); ?>
                            </p>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=nx-analytics')); ?>" class="milestone-link">
                                View Analytics
                            </a>
                        </div>

                        <!-- Stats Grid -->
                        <div class="milestone-stats">
                            <div class="milestone-stats-inner-wrapper">
                                <div class="milestone-stats-inner">
                                    <?php foreach ($data['stats'] as $stat) : ?>
                                        <div class="milestone-stat-card">
                                            <div class="milestone-stat-label"><?php echo esc_html($stat['label']); ?></div>
                                            <div class="milestone-stat-value"><?php echo esc_html($stat['value']); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- CTA Button -->
                                <a href="<?php echo esc_url('https://notificationx.com/pricing/'); ?>" target="_blank" class="milestone-cta">
                                     Unlock Pro Features
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php
    }

    /**
     * Get milestone data
     * Fetches real analytics data from the database to show user's notification performance
     */
    private function get_milestone_data()
    {
        // Get Analytics instance
        $analytics = Analytics::get_instance();

        // Get real analytics data
        $total_notifications = 0;
        $total_views = 0;
        $total_clicks = 0;
        $total_ctr = 0;

        try {
            // Get total count data
            $analytics_data = $analytics->get_total_count();

            if (is_array($analytics_data)) {
                $total_views = isset($analytics_data['totalViews']) ? $this->parse_number($analytics_data['totalViews']) : 0;
                $total_clicks = isset($analytics_data['totalClicks']) ? $this->parse_number($analytics_data['totalClicks']) : 0;
                $total_ctr = isset($analytics_data['totalCtr']) ? $analytics_data['totalCtr'] : 0;
            }

            // Get total notifications count
            global $wpdb;
            $table_name = $wpdb->prefix . 'nx_posts';
            $total_notifications = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE enabled = 1");
            $total_notifications = $total_notifications ? intval($total_notifications) : 0;

        } catch (\Exception $e) {
            // Fallback to default values if there's an error
            error_log('NotificationX Milestone: Error fetching analytics data - ' . $e->getMessage());
        }

        // Format numbers for display
        $notifications_formatted = $this->format_number($total_notifications);
        $views_formatted = $this->format_number($total_views);
        $clicks_formatted = $this->format_number($total_clicks);
        $ctr_formatted = number_format($total_ctr, 2) . '%';

        // Calculate total interactions (sum of all metrics)
        $total_interactions = $total_notifications + $total_views + $total_clicks;

        // Get milestone configuration based on total interactions
        $milestone_config = $this->get_milestone_config($total_interactions);

        return array_merge($milestone_config, [
            'stats' => [
                [
                    'label' => 'Total Notifications',
                    'value' => $notifications_formatted
                ],
                [
                    'label' => 'Total Views',
                    'value' => $views_formatted
                ],
                [
                    'label' => 'Total Clicks',
                    'value' => $clicks_formatted
                ],
                [
                    'label' => 'Click Rate',
                    'value' => $ctr_formatted
                ]
            ]
        ]);
    }

    /**
     * Parse number from formatted string (e.g., "1.2K" -> 1200)
     */
    private function parse_number($formatted_number)
    {
        if (is_numeric($formatted_number)) {
            return intval($formatted_number);
        }

        $formatted_number = strtoupper(trim($formatted_number));
        $multiplier = 1;

        if (strpos($formatted_number, 'K') !== false) {
            $multiplier = 1000;
            $formatted_number = str_replace('K', '', $formatted_number);
        } elseif (strpos($formatted_number, 'M') !== false) {
            $multiplier = 1000000;
            $formatted_number = str_replace('M', '', $formatted_number);
        }

        return intval(floatval($formatted_number) * $multiplier);
    }

    /**
     * Get milestone configuration based on total interactions
     * Returns unique title, subtitle, and emoji for each milestone level
     */
    private function get_milestone_config($total_interactions)
    {
        // Define milestone levels with unique messages
        $milestones = [
            1000000 => [
                'emoji' => '👑',
                'title' => 'Legendary! <strong>1M+ interactions achieved!</strong>',
                'subtitle' => 'You\'re an <strong>absolute legend</strong>! Your notifications are reaching millions. Pro features will help you scale to infinity.',
                'level' => '1m'
            ],
            500000 => [
                'emoji' => '💎',
                'title' => 'Diamond Status! <strong>500K+ interactions!</strong>',
                'subtitle' => 'You\'ve reached <strong>diamond tier</strong>! Your content is viral. Unlock Pro to maximize your massive reach.',
                'level' => '500k'
            ],
            250000 => [
                'emoji' => '🏆',
                'title' => 'Champion! <strong>250K+ interactions unlocked!</strong>',
                'subtitle' => 'You\'re a <strong>true champion</strong>! Your notifications are crushing it. Get Pro insights to dominate even more.',
                'level' => '250k'
            ],
            100000 => [
                'emoji' => '🌟',
                'title' => 'Superstar! <strong>100K+ interactions reached!</strong>',
                'subtitle' => 'You\'re a <strong>superstar</strong>! Your content is exploding. Upgrade to Pro for enterprise-level features.',
                'level' => '100k'
            ],
            50000 => [
                'emoji' => '🚀',
                'title' => 'Incredible! <strong>50K+ interactions achieved!</strong>',
                'subtitle' => 'You\'re a <strong>Pro</strong>! Unlock advanced features to scale even further and dominate your niche.',
                'level' => '50k'
            ],
            20000 => [
                'emoji' => '🔥',
                'title' => 'Amazing! <strong>20K+ interactions and counting!</strong>',
                'subtitle' => 'Your notifications are <strong>on fire</strong>! Get Pro to unlock powerful features and boost performance.',
                'level' => '20k'
            ],
            10000 => [
                'emoji' => '⭐',
                'title' => 'Fantastic! You\'ve reached <strong>10K</strong> interactions!',
                'subtitle' => 'You\'re doing <strong>great</strong>! Upgrade to Pro to see detailed analytics and grow even faster.',
                'level' => '10k'
            ],
            5000 => [
                'emoji' => '🎯',
                'title' => 'Awesome! <strong>5K interactions milestone unlocked!</strong>',
                'subtitle' => 'Your content is <strong>resonating</strong>! Unlock Pro to discover what\'s working best.',
                'level' => '5k'
            ],
            1000 => [
                'emoji' => '🎉',
                'title' => 'Congratulations! <strong>1K+ interactions reached!</strong>',
                'subtitle' => 'Your notifications are <strong>gaining traction</strong>! Upgrade to Pro to see advanced analytics and improve performance.',
                'level' => '1k'
            ]
        ];

        // Find the appropriate milestone level
        foreach ($milestones as $threshold => $config) {
            if ($total_interactions >= $threshold) {
                return $config;
            }
        }

        // Default for users below 1K interactions
        return [
            'emoji'    => '👋',
            'title'    => 'You\'ve reached 32K+ active installations',
            'subtitle' => 'Setup almost complete unlock full performance insights.',
            'level'    => 'starter'
        ];
    }

    /**
     * Format number for display (e.g., 1000 -> 1K, 1000000 -> 1M)
     *
     * @param int $number
     * @return string
     */
    private function format_number($number)
    {
        $number = (int) $number;

        if ($number === 0) {
            return '0';
        }

        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        }

        if ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }

        return (string) $number;
    }

    /**
     * Check if milestone should be shown
     * Shows milestone only when site reaches a new level (site-wide, not per-user)
     */
    private function should_show_milestone()
    {
        // Get Analytics instance
        $analytics = Analytics::get_instance();

        // Get real analytics data
        $total_notifications = 0;
        $total_views = 0;
        $total_clicks = 0;

        try {
            // Get total count data
            $analytics_data = $analytics->get_total_count();

            if (is_array($analytics_data)) {
                $total_views = isset($analytics_data['totalViews']) ? $this->parse_number($analytics_data['totalViews']) : 0;
                $total_clicks = isset($analytics_data['totalClicks']) ? $this->parse_number($analytics_data['totalClicks']) : 0;
            }

            // Get total notifications count
            global $wpdb;
            $table_name = $wpdb->prefix . 'nx_posts';
            $total_notifications = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE enabled = 1");
            $total_notifications = $total_notifications ? intval($total_notifications) : 0;

        } catch (\Exception $e) {
            error_log('NotificationX Milestone: Error fetching analytics data - ' . $e->getMessage());
            return false;
        }

        // Calculate total interactions
        $total_interactions = $total_notifications + $total_views + $total_clicks;

        // Determine current milestone level
        $current_level = 'starter';
        if ($total_interactions >= 1000000) {
            $current_level = '1m';
        } elseif ($total_interactions >= 500000) {
            $current_level = '500k';
        } elseif ($total_interactions >= 250000) {
            $current_level = '250k';
        } elseif ($total_interactions >= 100000) {
            $current_level = '100k';
        } elseif ($total_interactions >= 50000) {
            $current_level = '50k';
        } elseif ($total_interactions >= 20000) {
            $current_level = '20k';
        } elseif ($total_interactions >= 10000) {
            $current_level = '10k';
        } elseif ($total_interactions >= 5000) {
            $current_level = '5k';
        } elseif ($total_interactions >= 1000) {
            $current_level = '1k';
        }

        // Get the last seen milestone level (site-wide option)
        $last_seen_level = get_option('notificationx_milestone_level', '');

        // Show milestone if:
        // 1. Site has never seen any milestone, OR
        // 2. Site has reached a new milestone level
        if (empty($last_seen_level) || $last_seen_level !== $current_level) {
            // Don't update here - only update when user closes the notification
            // Store current level temporarily so we can update it later
            update_option('notificationx_milestone_current_level', $current_level);
            return true;
        }

        return false;
    }

    /**
     * AJAX handler to mark milestone as seen
     */
    public function ajax_mark_milestone_seen()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'notificationx_milestone_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Get the current milestone level that was shown
        $current_level = get_option('notificationx_milestone_current_level', '');

        if (!empty($current_level)) {
            // Mark this milestone level as seen (site-wide)
            update_option('notificationx_milestone_level', $current_level);

            // Clean up the temporary option
            delete_option('notificationx_milestone_current_level');
        }

        wp_send_json_success();
    }
}