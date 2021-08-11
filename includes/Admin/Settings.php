<?php

/**
 * Extension Factory
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Admin;

use NotificationX\Core\Database;
use NotificationX\Core\Modules;
use NotificationX\Core\REST;
use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;
use NotificationX\HooksLoader;
use NotificationX\NotificationX;
use UsabilityDynamics\Settings as UsabilityDynamicsSettings;

/**
 * ExtensionFactory Class
 */
class Settings extends UsabilityDynamicsSettings {
    /**
     * Instance of Settings
     *
     * @var Settings
     */
    use GetInstance;

    protected $wpdb;
    protected $defaults = [];

    /**
     * Initially Invoked when initialized.
     * @hook init
     */
    public function __construct($args) {
        global $wpdb;
        $this->wpdb = $wpdb;
        parent::__construct($args);
        add_action('init', [$this, 'init']);
    }

    /**
     * Called from Admin::init();
     * NotificationX
     *
     * @return void
     */
    public function init(){
        // add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        add_action('admin_menu', [$this, 'menu'], 25);
        add_filter('nx_branding_url', array($this, 'nx_branding_url'), 12);
    }

    /**
     * This method is reponsible for Admin Menu of
     * NotificationX
     *
     * @return void
     */
    public function menu() {
		$nx_settings_caps = apply_filters( 'nx_settings_caps', 'delete_users', 'settings_roles' );
        add_submenu_page('nx-admin', 'Settings', 'Settings', $nx_settings_caps, 'nx-admin#/settings', '__return_null', 3);
    }

    /**
     * Register scripts and styles.
     *
     * @param string $hook
     * @return void
     */
    function get_form_data() {
		$nx_settings_caps = apply_filters( 'nx_settings_caps', 'delete_users', 'settings_roles' );
        $data = NotificationX::get_instance()->normalize($this->settings_form());
        // $data['redirect']     = !current_user_can( $nx_settings_caps );
        $data['current_page'] = 'settings';
        $data['rest']         = REST::get_instance()->rest_data();
        $data['savedValues']  = Settings::get_instance()->get('settings', false);
        $data['values']       = Settings::get_instance()->get('settings', false);
        $data['assets']       = [
            'admin' => NOTIFICATIONX_ADMIN_URL,
            'public' => NOTIFICATIONX_PUBLIC_URL,
        ];
        return $data;
    }

    /**
     * Admin Views
     *
     * @return void
     */
    public function settings_page() {
        include_once NOTIFICATIONX_INCLUDES . 'Admin/views/settings.views.php';
    }


    public function settings_form($nx_id = 0) {
        do_action('nx_before_settings_fields');
        $wp_roles = GlobalFields::get_instance()->normalize_fields($this->get_roles());
        $site_name = get_bloginfo( 'name');
        $settings = [
            'id'           => 'notificationx_metabox_wrapper',
            'title'        => __('NotificationX', 'notificationx'),
            'object_types' => array('notificationx'),
            'context'      => 'normal',
            'priority'     => 'high',
            'show_header'  => false,
            'tabnumber'    => true,
            'layout'       => 'horizontal',
            'is_pro_active' => NotificationX::get_instance()->is_pro(),
            'config'    => [
                'active'  => "tab-general",
                'sidebar' => false,
                'title' => false
            ],
            'submit' => [
                'show' => true,
                'label' => 'Save Settings',
                'class' => 'save-settings'
            ],
            'tabs'         => apply_filters('nx_settings_tab', [
                "tab-general" => apply_filters('nx_settings_tab_general', [
                    'id'      => "tab-general",
                    'label'   => "General",
                    // 'icon'    => NOTIFICATIONX_ADMIN_URL . 'images/icons/pencil.svg',
                    'classes' => "tab-general",
                    'fields'  => [
                        'section-modules' => [
                            'label'   => "Modules",
                            'name'    => "section-modules",
                            'type'    => "section",
                            'fields'  => [
                                'modules' => [
                                    // 'label'   => "Modules",
                                    'name'       => "modules",
                                    'type'       => "toggle",
                                    "multiple"   => true,
                                    'default'    => true,
                                    'style'      => [
                                        'type'   => 'card',
                                        'column' => 3,
                                    ],
                                    'options'    => array_values(Modules::get_instance()->get_all()),
                                ],
                            ],
                        ],
                    ],
                ]),
                "advanced-settings-tab" => apply_filters('nx_settings_tab_advanced', [
                    'id'      => "tab-advanced-settings",
                    'label'   => "Advanced Settings",
                    // 'icon'    => NOTIFICATIONX_ADMIN_URL . 'images/icons/pencil.svg',
                    'classes' => "tab-advanced-settings",
                    'fields'  => [
                        'powered_by' => [
                            'name' => 'powered_by',
                            'label'   => "Powered By",
                            'type'    => "section",
                            'priority' => 15,
                            'fields'  => [
                                'disable_powered_by' => [
                                    'type'        => 'checkbox',
                                    'label'       => __('Disable Powered By', 'notificationx'),
                                    'name'        => "disable_powered_by",
                                    'default'     => 0,
                                    'priority'    => 10,
                                    'description' => __('Click, if you want to disable powered by text from notification', 'notificationx'),
                                ],
                            ],
                        ],
                        'role_management' => array(
                            'name' => 'role_management',
                            'type'    => "section",
                            'label' => __('Role Management', 'notificationx'),
                            'priority'    => 30,
                            'fields' => array(
                                'notification_roles' => array(
                                    'name'     => 'notification_roles',
                                    'type'     => 'select',
                                    'label'    => __('Who Can Create Notification?', 'notificationx'),
                                    'priority' => 1,
                                    'multiple' => true,
                                    'is_pro'   => true,
                                    'disable'  => true,
                                    'default'  => 'administrator',
                                    'options'  => $wp_roles
                                ),
                                'settings_roles' => array(
                                    'name'        => 'settings_roles',
                                    'type'        => 'select',
                                    'label'       => __('Who Can Edit Settings?', 'notificationx'),
                                    'priority'    => 2,
                                    'multiple' => true,
                                    'is_pro' => true,
                                    'disable' => true,
                                    'default' => 'administrator',
                                    'options' => $wp_roles
                                ),
                                'analytics_roles' => array(
                                    'name'        => 'analytics_roles',
                                    'type'        => 'select',
                                    'label'       => __('Who Can Check Analytics?', 'notificationx'),
                                    'priority'    => 3,
                                    'multiple' => true,
                                    'is_pro' => true,
                                    'disable' => true,
                                    'default' => 'administrator',
                                    'options' => $wp_roles
                                ),
                            )
                        )
                    ],
                ]),
                'email-analytics-reporting' => apply_filters('nx_settings_tab_email_analytics', [
                    'label' => __('Analytics & Reporting', 'notificationx'),
                    'id'      => "email-analytics-reporting",
                    // 'icon'    => NOTIFICATIONX_ADMIN_URL . 'images/icons/pencil.svg',
                    'classes' => "tab-advanced-settings",
                    'fields' => [
                        'analytics' => array(
                            'name'        => 'analytics',
                            'priority' => 10,
                            'type'    => "section",
                            'label'    => __('Analytics', 'notificationx'),
                            'fields'   => array(
                                'disable_dashboard_widget' => array(
                                    'name'        => 'disable_dashboard_widget',
                                    'type'        => 'checkbox',
                                    'label'       => __('Disable Dashboard Widget', 'notificationx'),
                                    'default'     => false,
                                    'priority'    => 0,
                                    'description' => __('Click, if you want to disable dashboard widget of analytics only.', 'notificationx'),
                                ),
                                'enable_analytics' => array(
                                    'name'        => 'enable_analytics',
                                    'type'    => 'checkbox',
                                    'label'   => __('Enable Analytics', 'notificationx'),
                                    'default'  => true,
                                    'priority' => 5,
                                ),
                                'analytics_from' => array(
                                    'name'        => 'analytics_from',
                                    'type'    => 'select',
                                    'label'   => __('Analytics From', 'notificationx'),
                                    'options' => GlobalFields::get_instance()->normalize_fields(array(
                                        'everyone'         => __('Everyone', 'notificationx'),
                                        'guests'           => __('Guests Only', 'notificationx'),
                                        'registered_users' => __('Registered Users Only', 'notificationx'),
                                    )),
                                    'default'  => 'everyone',
                                    'priority' => 10,
                                    'rules' => Rules::is( 'enable_analytics', true ),
                                ),
                                'exclude_bot_analytics' => array(
                                    'name'        => 'exclude_bot_analytics',
                                    'type'        => 'checkbox',
                                    'label'       => __('Exclude Bot Analytics', 'notificationx'),
                                    'default'     => true,
                                    'priority'    => 15,
                                    'description' => __('Select if you want to exclude bot analytics.', 'notificationx'),
                                    'rules' => Rules::is( 'enable_analytics', true ),
                                ),
                            ),
                        ),
                        'email_reporting' => array(
                            'name'        => 'email_reporting',
                            'priority' => 20,
                            'type'    => "section",
                            'label'    => __('Reporting', 'notificationx'),
                            'rules' => Rules::is( 'enable_analytics', true ),
                            'fields'   => array(
                                'disable_reporting' => array(
                                    'name'        => 'disable_reporting',
                                    'label' => __('Disable Reporting', 'notificationx'),
                                    'type'        => 'checkbox',
                                    'priority' => 0,
                                    'default' => 0,
                                ),
                                'reporting_frequency' => array(
                                    'name'        => 'reporting_frequency',
                                    'type'        => 'select',
                                    'label'       => __('Reporting Frequency', 'notificationx'),
                                    'default'     => 'nx_weekly',
                                    'is_pro' => true,
                                    'priority'    => 1,
                                    'disable'     => true,
                                    'options' => GlobalFields::get_instance()->normalize_fields(array(
                                        'nx_daily'         => __('Once Daily', 'notificationx'),
                                        'nx_weekly'         => __('Once Weekly', 'notificationx'),
                                        'nx_monthly'         => __('Once Monthly', 'notificationx'),
                                    )),
                                    'rules' => Rules::is( 'disable_reporting', false ),
                                ),
                                'reporting_monthly_help_text' => array(
                                    'name'        => 'reporting_monthly_help_text',
                                    'type' => 'message',
                                    'class' => 'nx-warning',
                                    'priority'    => 1.5,
                                    'message' => __('It will be triggered on the first day of next month.', 'notificationx'),
                                    'rules' => Rules::is( 'reporting_frequency', "nx_monthly" ),
                                ),
                                'reporting_day' => array(
                                    'name'        => 'reporting_day',
                                    'type'        => 'select',
                                    'label'       => __('Select Reporting Day', 'notificationx'),
                                    'default'     => 'monday',
                                    'priority'    => 2,
                                    'options' => GlobalFields::get_instance()->normalize_fields(array(
                                        'sunday'         => __('Sunday', 'notificationx'),
                                        'monday'         => __('Monday', 'notificationx'),
                                        'tuesday'        => __('Tuesday', 'notificationx'),
                                        'wednesday'      => __('Wednesday', 'notificationx'),
                                        'thursday'       => __('Thursday', 'notificationx'),
                                        'friday'         => __('Friday', 'notificationx'),
                                    )),
                                    'description' => __('Select a Day for Email Report.', 'notificationx'),
                                    'rules'       => Rules::logicalRule([
                                        Rules::is( 'reporting_frequency', "nx_weekly" ),
                                        Rules::is( 'disable_reporting', false )
                                    ]),
                                ),
                                'reporting_email' => array(
                                    'name'        => 'reporting_email',
                                    'type'        => 'text',
                                    'label'       => __('Reporting Email', 'notificationx'),
                                    'default'     => get_option('admin_email'),
                                    'priority'    => 3,
                                    'rules' => Rules::is( 'disable_reporting', false ),
                                ),
                                'reporting_subject' => array(
                                    'name'        => 'reporting_subject',
                                    'type'        => 'text',
                                    'label'       => __('Reporting Email Subject', 'notificationx'),
                                    'default'     => __("Weekly Engagement Summary of ‘{$site_name}’", 'notificationx'),
                                    'priority'    => 4,
                                    'disable'     => true,
                                    'rules' => Rules::is( 'disable_reporting', false ),
                                ),
                                'test_report' => array(
                                    'name'     => 'test_report',
                                    'label'    => __('Reporting Test', 'notificationx'),
                                    'text'     => __('Test Report', 'notificationx'),
                                    'type'     => 'button',
                                    'priority' => 5,
                                    'rules'    => Rules::is( 'disable_reporting', false ),
                                    'ajax'     => [
                                        'on'   => 'click',
                                        'api'  => '/notificationx/v1/reporting-test',
                                        'data' => [
                                            'disable_reporting'   => '@disable_reporting',
                                            'reporting_subject'   => '@reporting_subject',
                                            'reporting_email'     => '@reporting_email',
                                            'reporting_day'       => '@reporting_day',
                                            'reporting_frequency' => '@reporting_frequency',
                                        ],
                                        'swal' => [
                                            'title' => 'Successful',
                                            'text'  => 'Successfully Sent a Test Report in Your Email.',
                                            'icon'  => 'success',
                                        ],
                                    ],
                                ),
                            ),
                        ),
                    ],
                ]),
                'cache_settings_tab' => apply_filters('nx_settings_tab_cache', [
                    'id' => 'tab-cache-settings',
                    'label' => __('Cache Settings', 'notificationx'),
                    'priority' => 30,
                    // 'name' => 'cache_settings_tab',
                    'fields' => [
                        'cache_settings' => array(
                            'name'        => 'cache_settings',
                            'type'    => "section",
                            'label' => __('Cache Settings', 'notificationx'),
                            'priority' => 5,
                            'fields' => array(
                                'cache_limit' => array(
                                    'name' => 'cache_limit',
                                    'type'      => 'text',
                                    'label'     => __('Cache Limit', 'notificationx'),
                                    'description' => __('Number of Notification Data to be saved in Database.', 'notificationx'),
                                    'default'   => '100',
                                    'priority'    => 1
                                ),
                                'download_stats_cache_duration' => array(
                                    'name' => 'download_stats_cache_duration',
                                    'type'        => 'text',
                                    'label'       => __('Download Stats Cache Duration', 'notificationx'),
                                    'description' => __('Minutes (Schedule Duration to fetch new data).', 'notificationx'),
                                    'default'     => 3,
                                    'priority'    => 2
                                ),
                                'reviews_cache_duration' => array(
                                    'name' => 'reviews_cache_duration',
                                    'type'        => 'text',
                                    'label'       => __('Reviews Cache Duration', 'notificationx'),
                                    'description' => __('Minutes (Schedule Duration to fetch new data).', 'notificationx'),
                                    'default'     => '3',
                                    'priority'    => 3
                                )
                            ),
                        ),
                    ],
                ]),
            ]),
        ];

        $settings = apply_filters('nx_settings_configs', $settings);
        return $settings;
    }

    /**
     * Get All Roles
     * dynamically
     * @return array
     */
    public function get_roles() {
        $roles = wp_roles()->role_names;
        unset($roles['subscriber']);
        return $roles;
    }

    public function save_settings( $settings ) {
        $nx_settings_caps = apply_filters( 'nx_settings_caps', 'delete_users', 'settings_roles' );
        if(!current_user_can( $nx_settings_caps )){
            return false;
        }

        $this->set('settings', $settings);
        delete_transient( 'nx_get_field_names' );
        do_action('nx_settings_saved', $settings);
        return true;
    }

    /**
     * Getter for options
     *
     * @param bool|\UsabilityDynamics\type $key
     *
     * @param bool                         $default
     *
     * @return type
     */
    public function Xget($key = false, $default = false) {
        if(empty($default) && strpos($key, 'settings.') === 0){
            // getting default value from settings form.
            $_key = str_replace('settings.', '', $key);
            if(empty($default)){
                $defaults = $this->get_defaults();
                $default = !empty($defaults[$_key]) ? $defaults[$_key]['default'] : $default;
            }
        }
        return parent::get($key, $default);
    }

    /**
     * Setter for options
     *
     * @param string|\UsabilityDynamics\type $key
     * @param bool|\UsabilityDynamics\type   $value
     * @param bool                           $bypass_validation
     *
     * @internal param bool|\UsabilityDynamics\type $force_save
     *
     * @return \UsabilityDynamics\Settings
     */
    public function set( $key = '', $value = false, $bypass_validation = false ) {
        if(strpos( $key, '.' ) === false){
            parent::flush(false, $key);
        }
        return parent::set($key, $value, $bypass_validation);
    }

    public function nx_branding_url($link) {
        $affiliate_link = $this->get('settings.affiliate_link');
        if (!empty($affiliate_link)) {
            $link = $affiliate_link;
        }
        return $link;
    }

    // @todo maybe remove
    public function get_defaults($key = null) {
        if(empty($this->defaults)){
            $tabs = $this->settings_form();
            $this->defaults = NotificationX::get_instance()->get_field_names($tabs['tabs']);
        }
        return $this->defaults;
    }
}