<?php 
/**
 * This class is responsible for making stats for each NotificationX
 * 
 * @since 1.0.2
 */
class NotificationX_Analytics {
    /**
     * Get a single Instance of Analytics
     * @var NotificationX_Analytics
     */
    private static $_instance = null;
    /**
     * List of NotificationX
     * @var arrau
     */
    private static $notificationx = array();
    /**
     * Colors for Bar
     */
    private $colors = array(
        '#1abc9c',
        '#27ae60',
        '#3498db',
        '#8e44ad',
        '#e67e22',
        '#e74c3c',
        '#f39c12',
        '#34495e',
        '#9b59b6',
        '#16a085'
    );
    /**
     * View Options
     */
    private $views_options = array(
        'analytics_from' => 'everyone',
        'exclude_bot_analytics' => 1
    );

    public function __construct() {
        add_action( 'nx_before_settings_load', array( $this, 'add_settings' ) );
        if( NotificationX_DB::get_settings( 'enable_analytics' ) != 1 && NotificationX_DB::get_settings( 'enable_analytics' ) !== '' ) {
            return;
        }
        if( defined( 'NOTIFICATIONX_PRO_VERSION' ) ) {
            if( version_compare( NOTIFICATIONX_PRO_VERSION, '1.4.6', '<' ) ) {
                return;
            }
        }
        add_action( 'admin_init', array( $this, 'notificationx' ) );
        add_action( 'notificationx_admin_menu', array( $this, 'add_analytics_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );
        add_action( 'notificationx_settings_header', array( $this, 'stats_counter' ), 11 );
        add_action( 'notificationx_admin_header', array( $this, 'stats_counter' ), 11 );
        add_action( 'notificationx_after_analytics_header', array( $this, 'stats_counter' ), 11 );
        add_filter( 'nx_frontend_after_html', array( $this, 'add_nonce' ), 11 , 2 );
    }
    public static function notificationx(){
        $notificationx = new WP_Query(array(
            'post_type'      => 'notificationx',
            'posts_per_page' => -1,
        ));

        return self::$notificationx = $notificationx->posts;
    }

    /**
     * Get || Making a Single Instance of Analytics
     * @return self
     */
    public static function get_instance(){
        if( self::$_instance === null ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    /**
     * This method is responsible for adding analytics in Menu
     * @return void
     */
    public function add_analytics_menu( $pages ){

        $nx_analytics_caps = apply_filters( 'nx_analytics_caps', 'administrator', 'analytics_roles' );

        $pages['nx-analytics'] = array(
            'title'      => __('Analytics', 'notificationx'),
            'capability' => $nx_analytics_caps,
            'callback'   => array( $this, 'page_outputs' )
        );

        return $pages;
    }
    /**
     * This method is responsible for adding analytics page frontend.
     * @return void
     */
    public function page_outputs(){
        if( file_exists( NOTIFICATIONX_ADMIN_DIR_PATH . 'partials/nx-admin-analytics-display.php' ) ) {
            return include_once NOTIFICATIONX_ADMIN_DIR_PATH . 'partials/nx-admin-analytics-display.php';
        }
    }

    public function stats_counter(){
        global $pagenow, $current_user;
        $class = '';
        $nx_analytics_caps = apply_filters( 'nx_analytics_caps', 'administrator', 'analytics_roles' );
        if( is_null( $nx_analytics_caps ) || ! in_array( $nx_analytics_caps, $current_user->roles ) ) {
            return;
        }
        if( ! empty( $pagenow ) ) {
            $class = 'nx-header-for-' . str_replace('.php', '', $pagenow);
        }

        global $wpdb;
        $ids = false;

        $inner_sql = "SELECT DISTINCT INNER_POSTS.ID, INNER_POSTS.post_title FROM $wpdb->posts AS INNER_POSTS INNER JOIN $wpdb->postmeta AS INNER_META ON INNER_POSTS.ID = INNER_META.post_id WHERE INNER_POSTS.post_type = '%s'";

        $query = $wpdb->prepare(
            "SELECT META.meta_key as `key`, SUM( META.meta_value ) as `value` FROM ( $inner_sql ) as POSTS INNER JOIN $wpdb->postmeta as META ON POSTS.ID = META.post_id WHERE META.meta_key IN ( '_nx_meta_views', '_nx_meta_clicks' ) GROUP BY META.meta_key", 
            array(
                'notificationx',
            )
        );
        $results = $wpdb->get_results( $query );

        $views = $clicks = $ctr = 0;
        if( ! empty( $results ) ) { 
            foreach( $results as $result ) {
                if( isset( $result->key ) && $result->key === '_nx_meta_views' ) {
                    $views = $result->value;
                }
                if( isset( $result->key ) && $result->key === '_nx_meta_clicks' ) {
                    $clicks = $result->value;
                }
            }
        }

        $ctr = $views > 0 ? number_format( ( intval( $clicks ) / intval( $views ) ) * 100, 2) : 0;

        $views = NotificationX_Helper::nice_number( $views );
        $clicks = NotificationX_Helper::nice_number( $clicks );

        $views_link = admin_url( 'admin.php?page=nx-analytics&comparison_factor=views' );
        $clicks_link = admin_url( 'admin.php?page=nx-analytics&comparison_factor=clicks' );
        $ctr_link = admin_url( 'admin.php?page=nx-analytics&comparison_factor=ctr' );
        
        if( file_exists( NOTIFICATIONX_ADMIN_DIR_PATH . 'partials/nx-admin-analytics-counter.php' ) ) {
            return include_once NOTIFICATIONX_ADMIN_DIR_PATH . 'partials/nx-admin-analytics-counter.php';
        }
    }

	public function enqueues( $hook ) {
		if( $hook !== 'notificationx_page_nx-analytics' ) {
			return;
        }
        wp_enqueue_style( 
			'notificationx', 
			NOTIFICATIONX_ADMIN_URL . 'assets/css/nx-admin.min.css', 
			array(), '1.0.1', 'all' 
        );
		wp_enqueue_style( 
			'notificationx-analytics', 
			NOTIFICATIONX_ADMIN_URL . 'assets/css/nx-analytics.css', 
			array(), '1.0.1', 'all' 
        );
        wp_enqueue_script( 
			'notificationX-sweetalert', 
			NOTIFICATIONX_ADMIN_URL . 'assets/js/sweetalert.min.js', 
			array( 'jquery' ), '1.0.1', true 
		);
    }

    protected function clicks(){

    }
    /**
     * Add Settings Options
     * @return void
     */
    public function add_settings(){
        // add_filter( 'notificationx_settings_tab', array( $this, 'add_settings_tab' ) );
    }

    public function add_settings_tab( $options ){
        $options['email_analytics_reporting'] = [
            'title' => __( 'Email Reporting', 'notificationx' ),
            'button_text' => __( 'Save Settings' ),
        ];
        $general = $options['email_analytics_reporting'];
        $general['sections']['email_reporting'] = array(
            'priority' => 20,
            'title'    => __('Reporting', 'notificationx'),
            'fields'   => array(
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
                'reporting_frequency' => array(
                    'type'        => 'select',
                    'label'       => __( 'Reporting Frequency', 'notificationx' ),
                    'default'     => 'nx_weekly',
                    'priority'    => 4,
                    // 'disable'     => true,
                    'options' => array( 
                        'nx_weekly'         => __( 'Once Weekly', 'notificationx' ),
                        'hourly'         => __( 'Once Hourly', 'notificationx' ),
                    )
                )
            ),
        );

        $options['email_analytics_reporting'] = $general;
        return $options;
    }


    public function add_nonce( $output, $settings ){
        $nonce = wp_create_nonce( '_notificationx_pro_analytics_nonce' );
        $output .= '<input class="notificationx-analytics" name="_notificationx_pro_analytics_nonce" type="hidden" value="' . $nonce . '"/>';
        return $output;
    }

    
}

NotificationX_Analytics::get_instance();