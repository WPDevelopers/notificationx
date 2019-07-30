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
                            'disable'     => true,
                            'priority'    => 10,
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
        'api_integrations_tab' => array(
            'title' => __( 'API Integrations', 'notificationx' ),
            'priority' => 12,
            'is_pro' => ! NX_CONSTANTS::is_pro(),
            'sections' => apply_filters('nx_api_integration_sections', array(
                'mailchimp_settings_section' => array(
                    'modules' => 'modules_mailchimp',
                    'title' => __( 'MailChimp Settings', 'notificationx' ),
                    'fields' => array(
                        'mailchimp_api_key' => array(
                            'type'        => 'text',
                            'label'       => __('MailChimp API Key' , 'notificationx-pro'),
                            'default'     => __('Connect', 'notificationx'),
                            'priority'    => 5,
                            'description' => '<a href="https://mailchimp.com/help/about-api-keys/">Click Here</a> to get your API KEY',
                        ),
                        'mailchimp_cache_duration' => array(
                            'type'        => 'text',
                            'label'       => __('Cache Duration' , 'notificationx-pro'),
                            'default'     => 5,
                            'priority'    => 5,
                            'description' => 'minutes, scheduled duration for collect new data',
                        )
                    )
                ),
                'convertkit_settings_section' => array(
                    'title' => __( 'ConvertKit Settings', 'notificationx' ),
                    'modules' => 'modules_convertkit',
                    'fields' => array(
                        'convertkit_api_key' => array(
                            'type'        => 'text',
                            'label'       => __('API Key' , 'notificationx-pro'),
                            'default'     => '',
                            'priority'    => 5,
                            'description' => '<a href="https://developers.convertkit.com">Click Here</a> to get API KEY.',
                        ),
                        'convertkit_api_secret' => array(
                            'type'        => 'text',
                            'label'       => __('API Secret' , 'notificationx-pro'),
                            'default'     => '',
                            'priority'    => 5,
                            'description' => '<a href="https://developers.convertkit.com">Click Here</a> to get API Secret.',
                        ),
                        'convertkit_cache_duration' => array(
                            'type'        => 'text',
                            'label'       => __('Cache Duration' , 'notificationx-pro'),
                            'default'     => 3,
                            'priority'    => 5,
                            'description' => 'Minutes',
                        )
                    )
                ),
                'freemius_settings_section' => array(
                    'title' => __( 'Freemius Settings', 'notificationx' ),
                    'modules' => 'modules_freemius',
                    'fields' => array(
                        'freemius_dev_id' => array(
                            'type'        => 'text',
                            'label'       => __('Developer ID' , 'notificationx-pro'),
                            'priority'    => 5,
                            'default'     => '',
                            'description' => '<a href="https://dashboard.freemius.com">Click Here</a> to get Developer ID.',
                        ),
                        'freemius_dev_pk' => array(
                            'type'      => 'text',
                            'label'     => __('Developer Public Key' , 'notificationx-pro'),
                            'priority'	=> 6,
                            'default'	=> '',
                            'description' => '<a href="https://dashboard.freemius.com">Click Here</a> to get Developer Public KEY.',
                        ),
                        'freemius_dev_sk' => array(
                            'type'      => 'text',
                            'label'     => __('Developer Secret Key' , 'notificationx-pro'),
                            'priority'	=> 7,
                            'default'	=> '',
                            'description' => '<a href="https://dashboard.freemius.com">Click Here</a> to get Developer Secret KEY.',
                        ),
                        'freemius_cache_duration' => array(
                            'type'      => 'text',
                            'label'     => __('Cache Duration' , 'notificationx-pro'),
                            'default'	=> 5,
                            'priority'	=> 5,
                            'description'	=> 'Minutes',
                        )
                    )
                ),
                'zapier_settings_section' => array(
                    'title' => __( 'Zapier Settings', 'notificationx' ),
                    'modules' => 'modules_zapier',
                    'fields' => array(
                        'zapier_api_key' => array(
                            'type'      => 'text',
                            'label'     => __('API Key' , 'notificationx-pro'),
                            'default'	=> md5( home_url() ),
                            'priority'	=> 5,
                            'readonly' => true
                        )
                    )
                ),
    
            )),
            'views' => 'NotificationX_Settings::integrations'
        ),
        // 'go_premium_tab' => array(
        //     'title' => __( 'Go Premium', 'notificationx' ),
        //     'priority' => 13,
        //     'views' => 'NotificationX_Settings::integrations'
        // )
    ));
}