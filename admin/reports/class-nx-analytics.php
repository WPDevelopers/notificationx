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
        
        if( NotificationX_DB::get_settings( 'enable_analytics' ) != 1 && NotificationX_DB::get_settings( 'enable_analytics' ) !== '' ) {
            return;
        }
        if( defined( 'NOTIFICATIONX_PRO_VERSION' ) ) {
            if( version_compare( NOTIFICATIONX_PRO_VERSION, '1.4.6', '<' ) ) {
                return;
            }
        }
        add_action( 'notificationx_admin_menu', array( $this, 'add_analytics_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );
        add_action( 'notificationx_settings_header', array( $this, 'stats_counter' ), 11 );
        add_action( 'notificationx_admin_header', array( $this, 'stats_counter' ), 11 );
        add_action( 'notificationx_after_analytics_header', array( $this, 'stats_counter' ), 11 );
        add_filter( 'nx_frontend_after_html', array( $this, 'add_nonce' ), 11 , 2 );
        add_action( 'wp_ajax_notificationx_pro_analytics', array( $this, 'analytics_data' ) );
        add_action( 'wp_ajax_nopriv_notificationx_pro_analytics', array( $this, 'analytics_data' ) );
        add_filter( 'nx_admin_table_stats', array( $this, 'stats_output' ), 11 , 2 ); 
    }

    /**
     * This method is responsible for output the stats value in admin table
     * @return void
     */
    public function stats_output( $output, $idd ){
        if( empty( $idd ) ) {
            return 0;
        }
        $output = get_post_meta( $idd, '_nx_meta_views', true );
        $analytics_url = admin_url( 'admin.php?page=nx-analytics&notificationx=' . $idd . '&comparison_factor=views,clicks,ctr' );
        $format = '<a href="'. esc_url( $analytics_url ) .'">%s</a>';

        if( empty( $output ) ) {
            return sprintf( $format, '0 views');
        }
        return sprintf( $format, $output . __(' views', 'notificationx') );
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

    public function add_nonce( $output, $settings ){
        $nonce = wp_create_nonce( '_notificationx_pro_analytics_nonce' );
        $output .= '<input class="notificationx-analytics" name="_notificationx_pro_analytics_nonce" type="hidden" value="' . $nonce . '"/>';
        return $output;
    }

    public function analytics_data(){
        /**
         * Verify the Nonce
         */
        $nonce_key = isset( $_POST['nonce_key'] ) && $_POST['nonce_key'] !== 'false' ? $_POST['nonce_key'] : '_notificationx_pro_analytics_nonce';
        
        if ( ! isset( $_POST['nonce'] ) || ! isset( $_POST['id'] ) || ! wp_verify_nonce( $_POST['nonce'], $nonce_key ) ) {
            return;
        }

        global $user_ID;

        $analytics_from =  NotificationX_DB::get_settings( 'analytics_from' );
        $analytics_from = empty( $analytics_from ) ? 'everyone' : $analytics_from;

        $should_count = false;
        /**
         * Inspired from WP-Postviews for 
         * this pece of code. 
         */
        switch( $analytics_from ) {
            case 'everyone':
                $should_count = true;
                break;
            case 'guests':
                if( empty( $_COOKIE[ USER_COOKIE ] ) && (int) $user_ID === 0 ) {
                    $should_count = true;
                }
                break;
            case 'registered_users':
                if( (int) $user_ID > 0 ) {
                    $should_count = true;
                }
                break;
        }

        if( $should_count === false ) {
            wp_die();
        }

        $exclude_bot_analytics =  NotificationX_DB::get_settings( 'exclude_bot_analytics' );

        if ( $exclude_bot_analytics == 1 ) {
            /**
             * Inspired from WP-Postviews for 
             * this piece of code. 
             */
            $bots = array(
                'Google Bot' => 'google',
                'MSN' => 'msnbot',
                'Alex' => 'ia_archiver',
                'Lycos' => 'lycos',
                'Ask Jeeves' => 'jeeves',
                'Altavista' => 'scooter',
                'AllTheWeb' => 'fast-webcrawler',
                'Inktomi' => 'slurp@inktomi',
                'Turnitin.com' => 'turnitinbot',
                'Technorati' => 'technorati',
                'Yahoo' => 'yahoo',
                'Findexa' => 'findexa',
                'NextLinks' => 'findlinks',
                'Gais' => 'gaisbo',
                'WiseNut' => 'zyborg',
                'WhoisSource' => 'surveybot',
                'Bloglines' => 'bloglines',
                'BlogSearch' => 'blogsearch',
                'PubSub' => 'pubsub',
                'Syndic8' => 'syndic8',
                'RadioUserland' => 'userland',
                'Gigabot' => 'gigabot',
                'Become.com' => 'become.com',
                'Baidu' => 'baiduspider',
                'so.com' => '360spider',
                'Sogou' => 'spider',
                'soso.com' => 'sosospider',
                'Yandex' => 'yandex'
            );
            $useragent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
            foreach ( $bots as $name => $lookfor ) {
                if ( ! empty( $useragent ) && ( false !== stripos( $useragent, $lookfor ) ) ) {
                    $should_count = false;
                    break;
                }
            }
        }

        if( $should_count === false ) {
            wp_die();
        }
        /**
         * Save Impressions
         */
        $post_id = intval( $_POST['id'] );
        /**
         * For Per Click Data
         */
        $todays_date = date( 'd-m-Y', time() );
        if( isset( $_POST['clicked'] ) && $_POST['clicked'] == 'true' ) {
            $clicks = get_post_meta( $post_id, '_nx_meta_clicks', true );
            if( $clicks === null ) {
                add_post_meta( $post_id, '_nx_meta_clicks', 1 );
            } else {
                update_post_meta( $post_id, '_nx_meta_clicks', ++$clicks );
            }
            /**
             * For Per Pop Up Click
             */
            $idd = intval( $_POST['id'] );
            $impressions = get_post_meta( $idd, '_nx_meta_impression_per_day', true );
            if( empty( $impressions ) ) {
                $impressions = [];
                $impressions[ $todays_date ][ 'clicks' ] = 1;
                add_post_meta( $idd, '_nx_meta_impression_per_day', $impressions );
            } else {
                if( isset( $impressions[ $todays_date ] ) ) {
                    $clicks_data = isset( $impressions[ $todays_date ]['clicks'] ) ? ++$impressions[ $todays_date ]['clicks'] : 1;
                    $impressions[ $todays_date ][ 'clicks' ] = $clicks_data;
                } else {
                    $impressions[ $todays_date ][ 'clicks' ] = 1;
                }
                update_post_meta( $idd, '_nx_meta_impression_per_day', $impressions );
                echo 'Success';
            }
            wp_die(); // die here
        }

        $views = get_post_meta( $post_id, '_nx_meta_views', true );
        if( $views === null ) {
            add_post_meta( $post_id, '_nx_meta_views', 1 );
        } else {
            update_post_meta( $post_id, '_nx_meta_views', ++$views );
        }

        /**
         * For Per Pop Up
         */
        $impressions = get_post_meta( $post_id, '_nx_meta_impression_per_day', true );
        if( empty( $impressions )  ) {
            $impressions = [];
            $impressions[ $todays_date ]['impressions'] = 1;
            add_post_meta( $post_id, '_nx_meta_impression_per_day', $impressions );
        } else {
            if( isset( $impressions[ $todays_date ] ) ) {
                $impressions_data = isset( $impressions[ $todays_date ]['impressions'] ) ? ++$impressions[ $todays_date ]['impressions'] : 1;
                $impressions[ $todays_date ]['impressions'] = $impressions_data;
            } else {
                $impressions[ $todays_date ]['impressions'] = 1;
            }
            update_post_meta( $post_id, '_nx_meta_impression_per_day', $impressions );
            echo 'Success';
        }
        wp_die();
    }
}

NotificationX_Analytics::get_instance();