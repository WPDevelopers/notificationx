<?php

function notificationx_settings_args(){
    return apply_filters('notificationx_settings_tab', array(
        'general' => array(
            'title' => __( 'General', 'notificationx' ),
            'priority' => 10,
            'form' => true,
            'button_text' => __( 'Save Settings' ),
            'sections' => apply_filters('nx_general_settings_sections', array(
                'modules_sections' => array(
                    'title'    => __('Modules' , 'notificationx'),
                    'priority' => 1,
                    'modules' => true,
                    'fields'   => apply_filters('nx_modules', array(
                        'modules_bar' => __('Notification Bar', 'notificationx'),
                        'modules_wordpress' => __('WordPress', 'notificationx'),
                        'modules_woocommerce' => __('WooCommerce', 'notificationx'),
                        'modules_edd' => __('Easy Digital Downloads', 'notificationx'),
                        'modules_freemius' => array(
                            'is_pro' => true,
                            'title' => __('Freemius', 'notificationx'),
                        ),
                        'modules_custom_notification' => array(
                            'is_pro' => true,
                            'title' => __('Custom Notification', 'notificationx'),
                        ),
                        'modules_mailchimp' => array(
                            'is_pro' => true,
                            'title' => __('MailChimp', 'notificationx'),
                        ),
                        'modules_convertkit' => array(
                            'is_pro' => true,
                            'title' => __('ConvertKit', 'notificationx'),
                        ),
                        'modules_zapier' => array(
                            'is_pro' => true,
                            'title' => __('Zapier', 'notificationx'),
                        ),
                    )),
                    'views' => 'NotificationX_Settings::modules'
                ),
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
                        'affiliate_link' => array(
                            'type'        => 'text',
                            'label'       => __('Affiliate Link' , 'notificationx'),
                            'priority'    => 11,
                            'disable'    => true,
                            'description' => __('Click, if you want to disable powered by text from notification' , 'notificationx'),
                        )
                    ),
                ))
            )),
        ),
        'cache_settings_tab' => array(
            'title' => __( 'Cache Settings', 'notificationx' ),
            'priority' => 11,
            'form' => true,
            'button_text' => __( 'Save Settings' ),
            'sections' => apply_filters('nx_cache_settings_sections', array(
                'cache_settings' => apply_filters('nx_cache_settings_tab', array(
                    'priority' => 5,
                    'fields' => array(
                        'cache_limit' => array(
                            'type'      => 'text',
                            'label'     => __('Cache Limit' , 'notificationx'),
                            'default'   => '100',
                            'priority'	=> 1
                        ),
                        'download_stats_cache_duration' => array(
                            'type'        => 'text',
                            'label'       => __('Download Stats Cache Duration' , 'notificationx'),
                            'description' => __(' minutes (Schedule Duration to fetch new data).' , 'notificationx'),
                            'default'     => '3',
                            'priority'    => 2
                        ),
                        'reviews_cache_duration' => array(
                            'type'        => 'text',
                            'label'       => __('Reviews Cache Duration' , 'notificationx'),
                            'description' => __(' minutes (Schedule Duration to fetch new data).' , 'notificationx'),
                            'default'     => '3',
                            'priority'    => 3
                        )
                    ),
                )),
            )),
        ),
    ));
}