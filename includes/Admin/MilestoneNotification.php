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

use NotificationX\NotificationX;
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
        $data                = $this->get_milestone_data();
        $black_friday_notice = esc_url( NOTIFICATIONX_PUBLIC_URL . 'image/reports/black-friday-small.webp' );
        $nx_icon             = esc_url( NOTIFICATIONX_ADMIN_URL . 'images/nx-icon.svg' );

?>
        <div id="notificationx-milestone-container" style="display: none;">
            <div class="milestone-overlay">
                <div class="milestone-notification" onclick="event.stopPropagation()">
                    <!-- Header -->
                    <div class="milestone-header">
                        <h2 class="milestone-title">
                            <img width="24" src="<?php echo esc_url( $nx_icon ) ?>" alt="">
                            <?php echo esc_html__('NotificationX Milestones', 'notificationx'); ?>
                        </h2>
                        <button class="milestone-close" onclick="hideNotificationXMilestone(event)" aria-label="Close">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14 1.41L12.59 0L7 5.59L1.41 0L0 1.41L5.59 7L0 12.59L1.41 14L7 8.41L12.59 14L14 12.59L8.41 7L14 1.41Z" fill="currentColor" />
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="milestone-content">
                        <?php 
                            // Get current date in UTC
                            $today    = gmdate('Y-m-d');
                            $end_date = gmdate('Y-m-d', strtotime('December 4'));
                           if ( $today <= $end_date ) : ?>
                            <div class="black-friday-notice">
                                <a target="_blank" href="<?php echo esc_url( 'https://notificationx.com/bfcm2025-admin-notice' ) ?>">
                                    <img src="<?php echo esc_url( $black_friday_notice ) ?>" alt="<?php echo esc_attr__('Black Friday','notificationx') ?>">
                                </a>
                            </div>
                        <?php endif ?>
                        <!-- Achievement Banner -->
                        <div class="milestone-achievement">
                            <h3 class="milestone-achievement-title">
                                <?php echo esc_html( $data['emoji'] ); ?> <?php echo wp_kses_post($data['title']); ?>
                            </h3>
                            <p class="milestone-achievement-subtitle">
                                <?php echo wp_kses_post($data['subtitle']); ?>
                            </p>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=nx-analytics')); ?>" class="milestone-link <?php echo NotificationX::is_pro() ? 'pro-activated' : 'pro-deactivated' ?>">
                                <?php echo ! NotificationX::is_pro() ? esc_html__('View Analytics', 'notificationx') : esc_html__('View Detail Insights', 'notificationx'); ?>
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
                                    <?php echo esc_html__('Unlock Advanced Analytics Data', 'notificationx'); ?>
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
                    'label' => esc_html__('Total Views', 'notificationx'),
                    'value' => $views_formatted
                ],
                [
                    'label' => esc_html__('Total Clicks', 'notificationx'),
                    'value' => $clicks_formatted
                ],
                [
                    'label' => esc_html__('Click Rate', 'notificationx'),
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
            2000000 => [
                'emoji'    => '',
                'title'    => wp_kses_post( __('<strong>Mega Milestone Unlocked!</strong> 🚀', 'notificationx') ),
                'subtitle' => esc_html__( 'Your website notifications are on fire! Over 2M+ interactions have taken place across the globe.', 'notificationx' ),
                'level'    => '2m'
            ],

            1000000 => [
                'emoji'    => '',
                'title'    => wp_kses_post( __('<strong>Huge milestone achieved!</strong> 🎉', 'notificationx') ),
                'subtitle' => esc_html__( 'You have achieved something unbelievable. 1M+ interactions have already happened on your website globally.', 'notificationx' ),
                'level'    => '1m'
            ],

            700000 => [
                'emoji'    => '',
                'title'    => wp_kses_post( __('700,000 <strong>interaction milestone reached!</strong> 🎉', 'notificationx') ),
                'subtitle' => esc_html__( 'New interactions milestone achieved! Create more amazing notifications to reach out to a wider audience globally.', 'notificationx' ),
                'level'    => '700k'
            ],

            500000 => [
                'emoji'    => '',
                'title'    => wp_kses_post( __('<strong>You are doing amazing!</strong> 🎉', 'notificationx') ),
                'subtitle' => esc_html__( 'Your notification is doing great and getting global clicks and impressions. See the detailed analytics below.', 'notificationx' ),
                'level'    => '500k'
            ],

            300000 => [
                'emoji'    => '',
                'title'    => wp_kses_post( __('<strong>Legendary achievement!</strong> 🎉', 'notificationx') ),
                'subtitle' => esc_html__( 'New interactions milestone achieved! Create more amazing notifications to reach out to a wider audience globally.', 'notificationx' ),
                'level'    => '300k'
            ],

            250000 => [
                'emoji'    => '',
                'title'    => wp_kses_post( __('<strong>Diamond Status!!</strong> 🎉', 'notificationx') ),
                'subtitle' => esc_html__( 'Your notification is grabbing attention globally. Check the detailed analytics to learn more about the performance.', 'notificationx' ),
                'level'    => '250k'
            ],

            200000 => [
                'emoji'    => '',
                'title'    => wp_kses_post( __('200,000 <strong>interaction milestone reached!</strong> 🎉', 'notificationx') ),
                'subtitle' => esc_html__( 'Globally, 200,000 interactions are happening on your website. Check the detailed analytics to grow your audience further.', 'notificationx' ),
                'level'    => '200k'
            ],

            150000 => [
                'emoji'    => '',
                'title'    => wp_kses_post( __('Congratulations! <strong>Another milestone</strong> 🎉', 'notificationx') ),
                'subtitle' => esc_html__( 'Your notifications are performing incredibly well worldwide. Check out the detailed analytics below!', 'notificationx' ),
                'level'    => '150k'
            ],

            100000 => [
                'emoji'    => '',
                'title'    => wp_kses_post( __('100,000 <strong>interaction milestone reached!</strong> 🎉', 'notificationx') ),
                'subtitle' => esc_html__( 'New interactions milestone achieved! Create more amazing notifications to reach out to a wider audience globally.', 'notificationx' ),
                'level'    => '100k'
            ],

            75000 => [
                'emoji'    => '',
                'title'    => wp_kses_post( __('75,000+ <strong>interactions achieved today!</strong> 🎉', 'notificationx') ),
                'subtitle' => esc_html__( 'Your notification is doing great and getting global clicks and impressions. See the detailed analytics below.', 'notificationx' ),
                'level'    => '75k'
            ],

            50000 => [
                'emoji'    => '',
                'title'    => wp_kses_post( __('Incredible, <strong>50,000 interactions unlocked!</strong> 🎉', 'notificationx') ),
                'subtitle' => esc_html__( 'You are going great with your notification. Do not forget to check the detailed analytics to learn more.', 'notificationx' ),
                'level'    => '50k'
            ],

            25000 => [
                'emoji'    => '',
                'title'    => wp_kses_post( __('25,000 <strong>interaction milestone reached!</strong> 🎉', 'notificationx') ),
                'subtitle' => esc_html__( 'Globally, 25,000 interactions are happening on your website. Check the detailed analytics to grow your audience further.', 'notificationx' ),
                'level'    => '25k'
            ],

            10000 => [
                'emoji'    => '',
                'title'    => wp_kses_post( __('Fantastic! <strong>10,000 interactions reached!</strong> 🎉', 'notificationx') ),
                'subtitle' => esc_html__( 'New interactions milestone achieved! Create more amazing notifications to reach out to a wider audience globally.', 'notificationx' ),
                'level'    => '10k'
            ],

            5000 => [
                'emoji'    => '',
                'title'    => wp_kses_post( __('Awesome - <strong>you got 5,000 interactions!</strong> 🎉', 'notificationx') ),
                'subtitle' => esc_html__( 'Your amazing website reached 5,000 interactions from your notification. Check the detailed analytics and achieve more.', 'notificationx' ),
                'level'    => '5k'
            ],
            2000 => [
                'emoji'    => '',
                'title'    => wp_kses_post( __('2,000 <strong>interaction milestone achieved!</strong> 🎉', 'notificationx') ),
                'subtitle' => esc_html__( 'Your website has more than 2,000 interactions from your notification. Do not forget to check the detailed analytics and achieve more.', 'notificationx' ),
                'level'    => '2000'
            ],

            1000 => [
                'emoji'    => '',
                'title'    => wp_kses_post( __('Great - <strong>1,000 interactions achieved!</strong> 👏', 'notificationx') ),
                'subtitle' => esc_html__( 'Your notifications are performing incredibly well worldwide. Check out the detailed analytics below!', 'notificationx' ),
                'level'    => '1000'
            ],

            100 => [
                'emoji'    => '',
                'title'    => wp_kses_post( __('You\'ve got <strong>100 interactions overall!</strong> 🎉', 'notificationx') ),
                'subtitle' => esc_html__( 'Your notification is doing great and getting global clicks and impressions. See the detailed analytics below.', 'notificationx' ),
                'level'    => '100'
            ],
        ];


        // Find the appropriate milestone level
        foreach ($milestones as $threshold => $config) {
            if ($total_interactions >= $threshold) {
                return $config;
            }
        }

        // Default for users below 1K interactions
        return [];
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
        $total_views         = 0;
        $total_clicks        = 0;

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
        if ($total_interactions >= 2000000) {
            $current_level = '2m';
        } elseif ($total_interactions >= 1000000) {
            $current_level = '1m';
        } elseif ($total_interactions >= 700000) {
            $current_level = '700k';
        } elseif ($total_interactions >= 500000) {
            $current_level = '500k';
        } elseif ($total_interactions >= 300000) {
            $current_level = '300k';
        } elseif ($total_interactions >= 250000) {
            $current_level = '250k';
        } elseif ($total_interactions >= 200000) {
            $current_level = '200k';
        } elseif ($total_interactions >= 150000) {
            $current_level = '150k';
        } elseif ($total_interactions >= 100000) {
            $current_level = '100k';
        } elseif ($total_interactions >= 75000) {
            $current_level = '75k';
        } elseif ($total_interactions >= 50000) {
            $current_level = '50k';
        } elseif ($total_interactions >= 25000) {
            $current_level = '25k';
        } elseif ($total_interactions >= 10000) {
            $current_level = '10k';
        } elseif ($total_interactions >= 5000) {
            $current_level = '5k';
        } elseif ($total_interactions >= 2000) {
            $current_level = '2k';
        } elseif ($total_interactions >= 1000) {
            $current_level = '1k';
        } elseif ($total_interactions >= 100) {
            $current_level = '100';
        } elseif( $total_interactions < 100 ) {
            return false;
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