<?php

function notificationx_settings_args(){
    $wp_roles = NotificationX_Settings::get_roles();
    $site_name = get_bloginfo( 'name' );

    return apply_filters('notificationx_settings_tab', array(
        'general' => array(
            'title' => __( 'General', 'notificationx' ),
            'priority' => 10,
            'button_text' => __( 'Save Settings', 'notificationx' ),
            'sections' => apply_filters('nx_general_settings_sections', array(
                'modules_sections' => array(
                    'title'    => __('Modules' , 'notificationx'),
                    'priority' => 1,
                    'modules' => true,
                    'fields'   => apply_filters('nx_modules', array(
                        'modules_bar' => array(
                            'title' => __('Notification Bar', 'notificationx'),
                            'link' => 'https://notificationx.com/docs/notification-bar/'
                        ),
                        'modules_wordpress' =>  array (
                            'title' => __('WordPress', 'notificationx'),
                            'link' => 'https://notificationx.com/docs-category/configurations/'
                        ),
                        'modules_woocommerce' => array(
                            'title' => __('WooCommerce', 'notificationx'),
                            'link' => 'https://notificationx.com/docs/woocommerce-sales-notifications/'
                        ),
                        'modules_reviewx' => array(
                            'title'      => __('ReviewX', 'notificationx'),
                            'link'       => 'https://notificationx.com/docs/reviewx-notification-alerts'
                        ),
                        'modules_edd' => array(
                            'title' => __('Easy Digital Downloads', 'notificationx'),
                            'link' => 'https://notificationx.com/docs/notificationx-easy-digital-downloads/'
                        ),
                        'modules_give' => array(
                            'title'   => __('Give', 'notificationx'),
                            'version' => '1.2.5',
                            'link'    => 'https://notificationx.com/docs/givewp-donation-alert/'
                        ),
                        'modules_tutor' => array(
                            'title' => __('Tutor', 'notificationx'),
                            'link'  => 'https://notificationx.com/docs/tutor-lms/'
                        ),
                        'modules_cf7' => array(
                            'title' => __('Contact Form 7', 'notificationx'),
                            'link'  => 'https://notificationx.com/docs/contact-form-submission-alert/'
                        ),
                        'modules_wpf' => array(
                            'title' => __('WPForms', 'notificationx'),
                            'link'  => 'https://notificationx.com/docs/contact-form-submission-alert/'
                        ),
                        'modules_njf' => array(
                            'title' => __('Ninja Forms', 'notificationx'),
                            'link'  => 'https://notificationx.com/docs/contact-form-submission-alert/'
                        ),
                        'modules_grvf' => array(
                            'is_pro' => true,
                            'title' => __('Gravity Forms', 'notificationx'),
                            'version' => '1.4.4',
                            'link' => 'https://notificationx.com/docs/contact-form-submission-alert/'
                        ),
                        'modules_freemius' => array(
                            'is_pro' => true,
                            'title' => __('Freemius', 'notificationx'),
                            'link' => 'https://notificationx.com/docs/freemius-sales-notification/'
                        ),
                        'modules_custom_notification' => array(
                            'is_pro' => true,
                            'title' => __('Custom Notification', 'notificationx'),
                        ),
                        'modules_mailchimp' => array(
                            'is_pro' => true,
                            'title' => __('MailChimp', 'notificationx'),
                            'link' => 'https://notificationx.com/docs/mailchimp-email-subscription-alert/'
                        ),
                        'modules_convertkit' => array(
                            'is_pro' => true,
                            'title' => __('ConvertKit', 'notificationx'),
                            'link' => 'https://notificationx.com/docs/convertkit-alert/'
                        ),
                        'modules_zapier' => array(
                            'is_pro' => true,
                            'title' => __('Zapier', 'notificationx'),
                            'link' => 'https://notificationx.com/docs/zapier-notification-alert/'
                        ),
                        'modules_envato' => array(
                            'is_pro' => true,
                            'title' => __('Envato', 'notificationx'),
                            'version' => '1.2.0',
                            'link' => 'https://notificationx.com/docs/envato-sales-notification'
                        ),
                        'modules_learndash' => array(
                            'is_pro' => true,
                            'title' => __('LearnDash', 'notificationx'),
                            'version' => '1.2.0',
                            'link' => 'https://notificationx.com/docs/how-to-display-learndash-course-enrollment-alert-using-notificationx'
                        ),
                        'modules_google_analytics' => array(
                            'is_pro' => true,
                            'title' => __('Google Analytics', 'notificationx'),
                            'version' => '1.4.0',
                            'link' => 'https://notificationx.com/docs/google-analytics/'
                        ),
                    )),
                    'views' => 'NotificationX_Settings::modules'
                ),
            )),
        ),
        'advanced_settings_tab' => array(
            'title' => __('Advanced Settings', 'notificationx'),
            'button_text' => __('Save Settings', 'notificationx'),
            'priority' => 20,
            'sections' => apply_filters( 'nx_advanced_settings_sections', array(
                'powered_by' => apply_filters('nx_powered_by_settings', array(
                    'priority' => 15,
                    'fields' => array(
                        'disable_powered_by' => array(
                            'type'        => 'checkbox',
                            'label'       => __('Disable Powered By' , 'notificationx'),
                            'default'     => 0,
                            'priority'    => 10,
                            'description' => __('Click, if you want to disable powered by text from notification' , 'notificationx'),
                        ),
                    ),
                )),
                'role_management' => array(
                    'title' => __('Role Management', 'notificationx'),
                    'priority'    => 30,
                    'fields' => array(
                        'notification_roles' => array(
                            'type'        => 'select',
                            'label'       => __('Who Can Create Notification?', 'notificationx'),
                            'priority'    => 1,
                            'multiple' => true,
                            'disable' => true,
                            'default' => 'administrator',
                            'options' => $wp_roles
                        ),
                        'settings_roles' => array(
                            'type'        => 'select',
                            'label'       => __('Who Can Edit Settings?', 'notificationx'),
                            'priority'    => 1,
                            'multiple' => true,
                            'disable' => true,
                            'default' => 'administrator',
                            'options' => $wp_roles
                        ),
                        'analytics_roles' => array(
                            'type'        => 'select',
                            'label'       => __('Who Can Check Analytics?', 'notificationx'),
                            'priority'    => 1,
                            'multiple' => true,
                            'disable' => true,
                            'default' => 'administrator',
                            'options' => $wp_roles
                        ),
                    )
                )
            ))
        ),
        'email_analytics_reporting' => array(
            'title' => __('Analytics & Reporting', 'notificationx'),
            'button_text' => __('Save Settings', 'notificationx'),
            'priority' => 20,
            'sections' => apply_filters( 'nx_email_analytics_reporting_sections', array(
                'analytics' => array(
                    'priority' => 20,
                    'title'    => __('Analytics', 'notificationx'),
                    'fields'   => array(
                        'disable_dashboard_widget' => array(
                            'type'        => 'checkbox',
                            'label'       => __('Disable Dashboard Widget' , 'notificationx'),
                            'default'     => 0,
                            'priority'    => 0,
                            'description' => __('Click, if you want to disable dashboard widget of analytics only.' , 'notificationx'),
                        ),
                        'enable_analytics' => array(
                            'type'    => 'checkbox',
                            'label'   => __( 'Enable Analytics', 'notificationx' ),
                            'default'  => 1,
                            'priority' => 5,
                            'dependency' => array(
                                1 => array(
                                    'fields' => array( 'analytics_from', 'exclude_bot_analytics' ),
                                    'sections' => array( 'email_reporting' ),
                                )
                            ),
                            'hide' => array(
                                0 => array(
                                    'fields' => array( 'analytics_from', 'exclude_bot_analytics' ),
                                    'sections' => array( 'email_reporting' ),
                                )
                            )
                        ),
                        'analytics_from' => array(
                            'type'    => 'select',
                            'label'   => __( 'Analytics From', 'notificationx' ),
                            'options' => array(
                                'everyone'         => __( 'Everyone', 'notificationx' ),
                                'guests'           => __( 'Guests Only', 'notificationx' ),
                                'registered_users' => __( 'Registered Users Only', 'notificationx' ),
                            ),
                            'default'  => 'everyone',
                            'priority' => 10,
                        ),
                        'exclude_bot_analytics' => array(
                            'type'        => 'checkbox',
                            'label'       => __( 'Exclude Bot Analytics', 'notificationx' ),
                            'default'     => 1,
                            'priority'    => 15,
                            'description' => __( 'Select if you want to exclude bot analytics.', 'notificationx' ),
                        ),
                    ),
                ),
                'email_reporting' => array(
                    'priority' => 20,
                    'title'    => __('Reporting', 'notificationx'),
                    'fields'   => array(
                        'disable_reporting' => array(
                            'label' => __( 'Disable Reporting', 'notificationx' ),
                            'type'        => 'checkbox',
                            'priority' => 0,
                            'default' => 0,
                            'hide' => array(
                                1 => array(
                                    'fields' => array( 'reporting_day', 'reporting_frequency', 'reporting_monthly_help_text', 'reporting_email', 'reporting_subject', 'test_report' )
                                ),
                            ),
                            'dependency' => array(
                                0 => array(
                                    'fields' => array( 'reporting_frequency', 'reporting_email', 'reporting_subject', 'test_report' )
                                ),
                            ),
                        ),
                        'reporting_frequency' => array(
                            'type'        => 'select',
                            'label'       => __( 'Reporting Frequency', 'notificationx' ),
                            'default'     => 'nx_weekly',
                            'priority'    => 1,
                            'disable'     => true,
                            'options' => array(
                                'nx_daily'         => __( 'Once Daily', 'notificationx' ),
                                'nx_weekly'         => __( 'Once Weekly', 'notificationx' ),
                                'nx_monthly'         => __( 'Once Monthly', 'notificationx' ),
                            ),
                            'hide' => array(
                                'nx_daily' => array(
                                    'fields' => array( 'reporting_day', 'reporting_monthly_help_text' )
                                ),
                                'nx_weekly' => array(
                                    'fields' => array( 'reporting_monthly_help_text' )
                                ),
                                'nx_monthly' => array(
                                    'fields' => array( 'reporting_day' )
                                ),
                            ),
                            'dependency' => array(
                                'nx_weekly' => array(
                                    'fields' => array( 'reporting_day' )
                                ),
                                'nx_monthly' => array(
                                    'fields' => array( 'reporting_monthly_help_text' )
                                ),
                            )
                        ),
                        'reporting_monthly_help_text' => array(
                            'type' => 'message',
                            'class' => 'nx-warning',
                            'priority'    => 1.5,
                            'message' => __( 'It will be triggered on the first day of next month.', 'notificationx' )
                        ),
                        'reporting_day' => array(
                            'type'        => 'select',
                            'label'       => __( 'Select Reporting Day', 'notificationx' ),
                            'default'     => 'monday',
                            'priority'    => 2,
                            'options' => array(
                                'sunday'         => __( 'Sunday', 'notificationx' ),
                                'monday'         => __( 'Monday', 'notificationx' ),
                                'tuesday'        => __( 'Tuesday', 'notificationx' ),
                                'wednesday'      => __( 'Wednesday', 'notificationx' ),
                                'thursday'       => __( 'Thursday', 'notificationx' ),
                                'friday'         => __( 'Friday', 'notificationx' ),
                            ),
                            'description' => __( 'Select a Day for Email Report.', 'notificationx' ),
                        ),
                        'reporting_email' => array(
                            'type'        => 'text',
                            'label'       => __( 'Reporting Email', 'notificationx' ),
                            'default'     => get_option( 'admin_email' ),
                            'priority'    => 3,
                        ),
                        'reporting_subject' => array(
                            'type'        => 'text',
                            'label'       => __( 'Reporting Email Subject', 'notificationx' ),
                            'default'     => __( "Weekly Engagement Summary of ‘{$site_name}’", 'notificationx' ),
                            'priority'    => 4,
                            'disable'     => true,
                        ),
                        'test_report' => array(
                            'label' => __( 'Reporting Test', 'notificationx' ),
                            'view' => 'NotificationX_Report_Email::test_report',
                            'priority' => 5
                        ),
                    ),
                )
            ))
        ),
        'cache_settings_tab' => array(
            'title' => __( 'Cache Settings', 'notificationx' ),
            'priority' => 30,
            'button_text' => __( 'Save Settings', 'notificationx' ),
            'sections' => apply_filters('nx_cache_settings_sections', array(
                'cache_settings' => apply_filters('nx_cache_settings_tab', array(
                    'priority' => 5,
                    'fields' => array(
                        'cache_limit' => array(
                            'type'      => 'text',
                            'label'     => __('Cache Limit' , 'notificationx'),
                            'description' => __('Number of Notification Data to be saved in Database.' , 'notificationx'),
                            'default'   => '100',
                            'priority'	=> 1
                        ),
                        'download_stats_cache_duration' => array(
                            'type'        => 'text',
                            'label'       => __('Download Stats Cache Duration' , 'notificationx'),
                            'description' => __('Minutes (Schedule Duration to fetch new data).' , 'notificationx'),
                            'default'     => '3',
                            'priority'    => 2
                        ),
                        'reviews_cache_duration' => array(
                            'type'        => 'text',
                            'label'       => __('Reviews Cache Duration' , 'notificationx'),
                            'description' => __('Minutes (Schedule Duration to fetch new data).' , 'notificationx'),
                            'default'     => '3',
                            'priority'    => 3
                        )
                    ),
                )),
            )),
        ),
    ));
}