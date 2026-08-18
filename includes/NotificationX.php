<?php

/**
 * NotificationX File
 *
 * @package NotificationX
 */

namespace NotificationX;

use NotificationX\Admin\Admin;
use NotificationX\Admin\Cron;
use NotificationX\Admin\EntriesMailReceiver;
use NotificationX\Admin\Settings;
use NotificationX\Blocks\Blocks;
use NotificationX\Core\Database;
use NotificationX\Core\PostType;
use NotificationX\Core\QuickBuild;
use NotificationX\Core\REST;
use NotificationX\Core\ShortcodeInline;
use NotificationX\Core\Targeting;
use NotificationX\Core\Upgrader;
use NotificationX\Extensions\GlobalFields;
use NotificationX\FrontEnd\FrontEnd;
use NotificationX\Types\TypeFactory;
use NotificationX\Extensions\ExtensionFactory;
use NotificationX\ThirdParty\WPML;
use NotificationX\Core\WPDRoleManagement;
use NotificationX\ThirdParty\VisualPortfolio;
use NotificationX\Extensions\Elementor\ElementorManager;

/**
 * Plugin Engine.
 * @method static NotificationX get_instance($args = null)
 */
class NotificationX {
    /**
     * Instance of NotificationX
     * @var NotificationX
     */
    use GetInstance;
    /**
     * Option flag recording that NotificationX has already gone through its
     * initial (first-ever) activation on this site.
     *
     * Set on the first activation and seeded for pre-existing installs by
     * Upgrader, so a re-activation is never mistaken for a first run.
     *
     * @since 3.3.0
     */
    const FIRST_ACTIVATION_OPTION = 'nx_first_activation';
    /**
     * Settings
     * @var Settings
     */
    public $settings;
    /**
     * WP_CLI
     * @var boolean
     */
    public static $WP_CLI = false;
    /**
     * Invoked initially.
     */
    public function __construct() {
        static::$WP_CLI = defined('WP_CLI') && WP_CLI;
        $this->settings = Settings::get_instance([
            'key'         => 'notificationx',
            'auto_commit' => true,
            'debug'       => false,
            'store'       => 'options',
        ]);

        $args = Settings::get_instance()->get_role_map();
        new WPDRoleManagement($args);

        Upgrader::get_instance();
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reviewed for the NotificationX codebase: acceptable in this context.
        if (is_admin() || empty($_GET['frontend']) || $_GET['frontend'] != true) {
            Admin::get_instance();
        }
        FrontEnd::get_instance();
        add_action('admin_init', [$this, 'maybe_redirect'], 10);
        add_action('init', [$this, 'init'], 10);
        add_action('plugins_loaded', array($this, 'init_extension'));
        add_filter('nx_pro_alert_popup', array($this, 'pro_alert_popup'));
        add_action( 'init', [ $this, 'register_custom_image_size' ] );
        /**
         * Register all REST Endpoint
         */
        REST::get_instance();
        // Country/user-role targeting for ALL notification types (decoupled from the bar module).
        Targeting::get_instance();
        Cron::get_instance();
        QuickBuild::get_instance();
        ShortcodeInline::get_instance();
        Blocks::get_instance();

        CoreInstaller::get_instance(basename(NOTIFICATIONX_FILE, '.php'));

        // 3rd Party features.
        WPML::get_instance();
        VisualPortfolio::get_instance();
        if ( did_action( 'elementor/loaded' ) ) {
            ElementorManager::get_instance();
        } else {
            add_action( 'elementor/loaded', [ ElementorManager::class, 'get_instance' ] );
        }
        EntriesMailReceiver::get_instance();
    }
    /**
     * The Plugin Activator
     * @return void
     */
    public function activator(){
        // nx_activated
        Database::get_instance()->Create_DB();

        // A first-ever activation is the only moment the onboarding wizard may
        // be auto-launched; an abandoned wizard still counts as "already
        // activated", so re-activations never force it again.
        $is_first_activation = ! self::has_activated_before();
        if ( $is_first_activation ) {
            update_option( self::FIRST_ACTIVATION_OPTION, time(), 'no' );
        }

		if( ! static::$WP_CLI && current_user_can( 'delete_users' ) ) {
			set_transient( 'nx_activated', $is_first_activation ? 'first' : 'again', 30 );
		}

        // Usage insights are normally wired up on `init`, which WordPress has
        // already fired by the time it activates a plugin — so the
        // register_activation_hook() callback registered inside the insights
        // constructor never runs and the tracking event is left unscheduled.
        // Build the instance and run its activation routine explicitly here so
        // data collection is live from activation onwards.
        Admin::get_instance()->plugin_usage_insights()->activate_this_plugin();

        Upgrader::get_instance()->clear_transient();
    }

    /**
     * Whether NotificationX has already gone through its initial activation on
     * this site — i.e. it has been activated before, regardless of whether the
     * Setup Wizard was ever opened or completed.
     *
     * @since 3.3.0
     * @return bool
     */
    public static function has_activated_before() {
        return (bool) get_option( self::FIRST_ACTIVATION_OPTION, false );
    }

    public function maybe_redirect(){
        if( static::$WP_CLI || wp_doing_ajax() ) {
            return;
        }
        // Bail if no activation transient is set.
        $activation = get_transient( 'nx_activated' );
        if ( ! $activation ) {
            return;
        }
        // Delete the activation transient.
        delete_transient( 'nx_activated' );

        if ( ! is_multisite() ) {
            // Only a genuine first-ever activation may launch the onboarding
            // Setup Wizard (and only while it has not been completed/skipped).
            // Every returning activation lands on the dashboard, as before.
            // Transients written by an older version hold `true`, which is
            // treated as a returning activation.
            $is_first_activation = ( 'first' === $activation );
            $page                = ( $is_first_activation && ! \NotificationX\Core\SetupWizard::is_completed() ) ? 'nx-setup-wizard' : 'nx-dashboard';
            wp_safe_redirect( add_query_arg( array(
                'page'		=> $page
            ), admin_url( 'admin.php' ) ) );
        }
    }

    public function init_extension() {
        // TypeFactory::get_instance();
        ExtensionFactory::get_instance();
    }

    public function init() {
        /**
         * Run All Actions and Filters
         * for GOOD.
         */
        // load_plugin_textdomain( 'notificationx', false, dirname( plugin_basename( NOTIFICATIONX_FILE ) ) . '/languages' );

        //  @todo remove
        if(defined('NX_DEBUG') && NX_DEBUG){
            add_action('wp_ajax_nx', [$this, 'get_tabs']); // executed when logged in
        }

        add_action( 'plugin_action_links_' . NOTIFICATIONX_BASENAME, array($this, 'nx_action_links'), 10, 1);
    }

    public function pro_alert_popup($args) {
        if ( !empty($args)){
            $args = wp_parse_args($args, [
                "showConfirmButton"=> true,
                "showCloseButton"=>true,
                "title" => __('Opps! This is PRO Feature.', 'notificationx'),
                "customClass"=> [
                    "container"=> 'pro-video-popup',
                    "closeButton"=> 'pro-video-close-button',
                    "icon"=> 'pro-video-icon',
                    "title"=> 'pro-video-title',
                    "content"=> 'pro-video-content',
                    "actions"=> 'nx-pro-alert-actions',
                    "confirmButton"=> 'pro-video-confirm-button',
                    "denyButton"=> 'pro-video-deny-button',
                ],
            ]);
        }
        return $args;
    }

    /**
     * This function is hooked
     * @hooked plugin_action_links_
     * @param array $links
     * @return array
     * @since 1.2.4
     */
    public function nx_action_links( $links ) {
        $deactivate_link = isset( $links['deactivate'] ) ? $links['deactivate'] : '';
        unset($links['deactivate']);
		$links['settings'] = '<a href="' . admin_url('admin.php?page=nx-settings') . '">' . __('Settings','notificationx') .'</a>';
		if( ! empty( $deactivate_link ) ) {
			$links['deactivate'] = $deactivate_link;
		}
        if( ! is_plugin_active('notificationx-pro/notificationx-pro.php' ) ) {
            $links['pro'] = '<a href="' . esc_url('https://notificationx.com/#pricing') . '" target="_blank" style="color: #349e34;"><b>' . __('Go Pro','notificationx') .'</b></a>';
        }
        return $links;
    }

    /**
     * Checks whether pro plugin is active.
     *
     * @return boolean
     */
    public static function is_pro() {
        return class_exists('\NotificationXPro\NotificationX');
    }
    /**
     * Get Tabs array in json.
     *
     * @return json
     */
    public function get_tabs() {
        $tabs = GlobalFields::get_instance()->tabs();
        // $tabs = Settings::get_instance()->get_form_data();
        $fields = $this->get_field_names($tabs['tabs']);
        wp_send_json($tabs);
    }
    /**
     * Convert `fields` associative array to numeric array recursively.
     * @todo improve implementation.
     *
     * @param array $arr
     * @return array
     */
    public function normalize($arr) {

        if (!empty($arr['fields'])) {
            $arr['fields'] = array_values($arr['fields']);
        }

        if (!empty($arr['options'])) {
            $arr['options'] = array_values($arr['options']);
        }

        if (!empty($arr['tabs'])) {
            $arr['tabs'] = array_values($arr['tabs']);
        }

        if (is_array($arr)) {
            foreach ($arr as $key => $value) {
                if (is_array($value)) {
                    $arr[$key] = $this->normalize($value);
                }
            }
        }
        return $arr;
    }

    public function normalize_post($settings) {
        $fields = $this->get_field_names();
        foreach ($fields as $key => $value) {
            if (!isset($settings[$key]) && isset($value['default'])) {
                $settings[$key] = $value['default'];
            }
            if (isset( $value['type'] ) && ($value['type'] == 'checkbox' || $value['type'] == 'toggle')) {
                $settings[$key] = (bool) (isset($settings[$key]) ? $settings[$key] : false);
            }
            if (isset( $value['type'] ) && $value['type'] == 'number') {
                $settings[$key] = isset($settings[$key]) ? $settings[$key] : 0;
                $settings[$key] = is_numeric($settings[$key]) ? $settings[$key] + 0 : 0;
            }
        }
        return $settings;
    }

    public function get_tab(){
        $tabs = get_transient('nx_builder_fields');
        if(empty($tabs) || (defined('NX_DEBUG') && NX_DEBUG)){
            $tabs = GlobalFields::get_instance()->tabs();
            set_transient( 'nx_builder_fields', $tabs, DAY_IN_SECONDS );
        }
        return $tabs;
    }

    public function get_field_names($tabs = null){
        $fields = [];
        if(empty($tabs)){
            $tabs = $this->get_tab();
        }
        if(!empty($tabs['tabs'])){
            $fields = $this->_get_field_names($tabs['tabs']);
        }
        return $fields;
    }

    public function get_field($field_name){
        $fields = $this->get_field_names();
        if(!empty($fields[$field_name])){
            return $fields[$field_name];
        }
    }

    // @todo maybe remove if not used in future.
    public function _get_field_names($fields, $names = []) {
        foreach ($fields as $key => $field) {
            if (empty($field['type'])) {
                $names = $this->_get_field_names($field['fields'], $names);
            } else if ($field['type'] == 'section' || $field['type'] == 'group') { //
                $_names = $this->_get_field_names($field['fields'], []);
                if ($field['type'] == 'section')
                    $names = array_merge($names, $_names);
                else
                foreach ($_names as $key => $value) {
                    $names[$field['name']][$key] = [
                        'type'         => $value['type'],
                        'default'      => isset($value['default']) ? $value['default'] : '',
                        'help'         => isset($value['help']) ? $value['help'] : '',
                        'label'        => isset($value['label']) ? $value['label'] : '',
                        'parent_label' => isset($field['label']) ? $field['label'] : '',
                    ];
                }
            } elseif (!empty($field['name'])) {
                $names[$field['name']] = [
                    'type'     => $field['type'],
                    'default'  => isset($field['default']) ? $field['default'] : '',
                    'help'     => isset($field['help']) ? $field['help'] : '',
                    'label'    => isset($field['label']) ? $field['label'] : '',
                    'multiple' => isset($field['multiple']) ? $field['multiple'] : '',
                ];
            }
        }

        return $names;
    }

    function my_upgrade_function( $upgrader_object, $options ) {
        $current_plugin_path_name = plugin_basename( __FILE__ );

        if ($options['action'] == 'update' && $options['type'] == 'plugin' ) {
           foreach($options['plugins'] as $each_plugin) {
              if ($each_plugin==$current_plugin_path_name) {
                 // .......................... YOUR CODES .............

              }
           }
        }
    }

    
    public function register_custom_image_size() {
        add_image_size('_nx_notification_thumb_100_100', 100, 100, true);
        add_image_size('_nx_notification_thumb_200_200', 200, 200, true);
        add_image_size('_nx_notification_thumb_300_300', 300, 300, true);
        add_image_size('_nx_notification_thumb_400_400', 400, 400, true);
        add_image_size('_nx_notification_thumb_500_500', 500, 500, true);
    }

}
