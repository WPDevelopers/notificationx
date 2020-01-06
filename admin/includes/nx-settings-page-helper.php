<?php

function notificationx_settings_args(){
    return apply_filters('notificationx_settings_tab', array(
        'general' => array(
            'title' => __( 'General', 'notificationx' ),
            'priority' => 10,
            'button_text' => __( 'Save Settings' ),
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
                            'link'  => 'https://notificationx.com/docs/contact-form/'
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
            'priority' => 10,
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
                ))
            ))
        ),
        'cache_settings_tab' => array(
            'title' => __( 'Cache Settings', 'notificationx' ),
            'priority' => 11,
            'button_text' => __( 'Save Settings' ),
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