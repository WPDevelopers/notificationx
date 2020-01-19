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
        add_action( 'admin_init', array( $this, 'notificationx' ) );
        add_action( 'notificationx_admin_menu', array( $this, 'add_analytics_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );
        add_action( 'wp_ajax_notificationx_pro_analytics', array( $this, 'analytics_data' ) );
        add_action( 'wp_ajax_nopriv_notificationx_pro_analytics', array( $this, 'analytics_data' ) );
        add_action( 'wp_ajax_nx_analytics_calc', array( $this, 'analytics_calc' ) );
        add_filter( 'nx_frontend_after_html', array( $this, 'add_nonce' ), 11 , 2 );
        add_filter( 'nx_admin_table_stats', array( $this, 'stats_output' ), 11 , 2 );
        add_action( 'notificationx_settings_header', array( $this, 'stats_counter' ), 11 );
        add_action( 'notificationx_admin_header', array( $this, 'stats_counter' ), 11 );
        add_action( 'notificationx_after_analytics_header', array( $this, 'stats_counter' ), 11 );
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
        $comparison_factor_list = array(
            'views' => 'Views',
            'clicks' => 'Clicks',
            'ctr' => 'CTR',
        );

        if( file_exists( NOTIFICATIONX_ADMIN_DIR_PATH . 'partials/nx-admin-analytics-display.php' ) ) {
            return include_once NOTIFICATIONX_ADMIN_DIR_PATH . 'partials/nx-admin-analytics-display.php';
        }
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
        
        if( file_exists( NOTIFICATIONX_ADMIN_URL . 'partials/nx-admin-analytics-counter.php' ) ) {
            return include_once NOTIFICATIONX_ADMIN_URL . 'partials/nx-admin-analytics-counter.php';
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
			'notificationx-select2', 
			NOTIFICATIONX_ADMIN_URL . 'assets/css/select2.min.css', 
			array(), '1.0.1', 'all' 
		);
        wp_enqueue_style( 
			'notificationx-chart', 
			NOTIFICATIONX_ADMIN_URL . 'assets/css/Chart.css', 
			array(), '1.0.1', 'all' 
        );
		wp_enqueue_style( 
			'notificationx-analytics', 
			NOTIFICATIONX_ADMIN_URL . 'assets/css/nx-analytics.css', 
			array(), '1.0.1', 'all' 
        );
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 
			'notificationx-select2', 
			NOTIFICATIONX_ADMIN_URL . 'assets/js/select2.min.js', 
			array( 'jquery' ), '1.0.1', true 
		);
		wp_enqueue_script( 
			'chartjs', 
			NOTIFICATIONX_ADMIN_URL . 'assets/js/Chart.min.js', 
			array( 'jquery' ), '1.0.1', true 
		);
		wp_enqueue_script( 
			'notificationx-analytics', 
			NOTIFICATIONX_ADMIN_URL . 'assets/js/nx-analytics.js', 
			array( 'jquery', 'jquery-ui-datepicker', 'chartjs' ), '1.0.1', true 
        );
    }
    protected function labels( $query_vars = array() ){
        $current_date = date('d-m-Y', current_time('timestamp'));
        $start_date = date('d-m-Y', strtotime( $current_date . ' -7days' ));
        if( isset( $query_vars['start_date'] ) && ! empty( $query_vars['start_date'] ) ) {
            $start_date = $query_vars['start_date'];
        }

        if( isset( $query_vars['end_date'] ) && ! empty( $query_vars['end_date'] ) ) {
            $current_date = $query_vars['end_date'];
        }

        $dates = array();
        $start_date_diff = new DateTime( $start_date );
        $current_date_diff = new DateTime( $current_date );
        $diff = $current_date_diff->diff($start_date_diff);
        $counter = isset( $diff->days ) ? $diff->days : 0;
        for( $i = 0; $i <= $counter; $i++ ) {
            $date = $i === 0 ? $start_date : $start_date . " +$i days";
            $dates[] = date( 'M d', strtotime( $date ) );
        }

        $this->dates = $dates;

        return $dates;
    }
    protected function datasets( $query_vars = array() ){
        global $wpdb;

        $ids = false;
        $extra_sql_input = $extra_sql = '';
        if( ! isset( $query_vars['notificationx'] ) ) {
            $ids = true;
        }


        if( isset( $query_vars['notificationx'] ) ) {
            $notificationx = trim($query_vars['notificationx']);
            if( strpos( $notificationx, 'all' ) === false ) {
                $ids = false;
            } else {
                $ids = true;
            }
        }


        if( ! $ids ) {
            $extra_sql_input = $notificationx;
            $extra_sql = "AND POSTS.ID IN ( $extra_sql_input )";
        }

        $inner_sql = "SELECT DISTINCT INNER_POSTS.ID, INNER_POSTS.post_title FROM $wpdb->posts AS INNER_POSTS INNER JOIN $wpdb->postmeta AS INNER_META ON INNER_POSTS.ID = INNER_META.post_id WHERE INNER_POSTS.post_type = '%s'";

        $query = $wpdb->prepare(
            "SELECT POSTS.ID, POSTS.post_title, META.meta_value FROM ( $inner_sql ) as POSTS INNER JOIN $wpdb->postmeta as META ON POSTS.ID = META.post_id WHERE META.meta_key = %s $extra_sql", 
            array(
                'notificationx',
                '_nx_meta_impression_per_day'
            )
        );
        $results = $wpdb->get_results( $query );

        $default_value = array(
            "fill" => false,
        );

        $datasets = $views = $data = $impressions = $comaprison_factor = $available_data = array();
        $impressions = $clicks = $ctr = array_fill_keys( $this->dates, 0 );

        if( isset( $query_vars['comparison_factor'] ) && ! empty( $query_vars['comparison_factor'] ) && $query_vars['comparison_factor'] != null ) {
            if( strpos( $query_vars['comparison_factor'], ',' ) !== false && strpos( $query_vars['comparison_factor'], ',' ) >= 0 ) {
                $comaprison_factor = explode( ',', $query_vars['comparison_factor'] );
            } else {
                if( $query_vars['comparison_factor'] != 'undefined' ) {
                    $comaprison_factor = [ $query_vars['comparison_factor'] ];
                }
            }
        }

        if( empty( $comaprison_factor ) ) {
            $comaprison_factor = array( 'views' );
        }
        $number_of_impressions = $number_of_clicks = $max_stepped_size = 0;

        if( ! empty( $results ) ) {
            $index = 0;

            foreach( $results as $value ) {
                $unserialize = unserialize( $value->meta_value );
                if( ! empty( $unserialize ) ) {
                    foreach( $unserialize as $date => $single ) {
                        $temp_date = date('M d', strtotime( $date ));
                        if( isset( $impressions[ $temp_date ] ) ) {
                            $impressions[ $temp_date ] = $number_of_impressions = isset( $single['impressions'] ) ? $single['impressions'] : 0;
                        }
                        if( in_array( 'views', $comaprison_factor ) ) {
                            $available_data[ 'views' ] = $impressions;
                            if( $max_stepped_size < $number_of_impressions ) {
                                $max_stepped_size = $number_of_impressions;
                            }
                        }
                        if( isset( $clicks[ $temp_date ] ) ) {
                            $clicks[ $temp_date ] = $number_of_clicks = isset( $single['clicks'] ) ? $single['clicks'] : 0;
                        }
                        if( in_array( 'clicks', $comaprison_factor ) ) { 
                            $available_data[ 'clicks' ] = $clicks;
                            if( $max_stepped_size < $number_of_clicks ) {
                                $max_stepped_size = $number_of_clicks;
                            }
                        }
                        if( in_array( 'ctr', $comaprison_factor ) ) { 
                            $ctr[ $temp_date ] = $number_of_ctr = $number_of_impressions > 0 ? number_format( ( intval( $number_of_clicks ) / intval( $number_of_impressions ) ) * 100, 2) : 0;
                            $available_data[ 'ctr' ] = $ctr;
                            if( $max_stepped_size < $number_of_ctr ) {
                                $max_stepped_size = $number_of_ctr;
                            }
                        }

                        $number_of_impressions = $number_of_clicks = 0;
                    }
                    //TODO: has to check again and again.
                    if( $available_data ) {
                        foreach( $available_data as $factor => $factor_data ){
                            $data['data'] = array_values( $factor_data );
                            $data = array_merge( $default_value, $data );
                            $color = $this->random_color( ++$index );
                            $data['backgroundColor'] = $color;
                            $data['borderColor'] = $color;
                            $factor_label = $factor == 'ctr' ? 'CTR' : ucwords( $factor );
                            $data['label'] = $value->post_title . ' - ' . $factor_label;
                            $data['labelString'] = 'Impressions';

                            $views[ $value->ID . '_' . $factor ] = $data;
                            $views[ 'stepped_size' ] = $max_stepped_size;
                        }
                    }
                }
            }

            return $views;
        }
        return array();
    }

    public function analytics_calc(){
        if ( empty( $_POST ) || ! check_admin_referer( '_nx_analytics_nonce', 'nonce' ) ) {
            return;
        }
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], '_nx_analytics_nonce' ) ) {
            return;
        }

        $dates = $this->labels( $_POST['query_vars'] );
        $datasets = $this->datasets( $_POST['query_vars'] );

        echo json_encode( array(
            'labels'   => $dates,
            'datasets' => $datasets,
        ));

        wp_die();
    }

    private function random_color_part() {
        return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
    }
    
    private function random_color( $index = '' ) {
        if( ! empty( $index ) ) {
            if( isset( $this->colors[ $index ] ) ) {
                return $this->colors[ $index ];
            } else {
                return '#' . $this->random_color_part() . $this->random_color_part() . $this->random_color_part();
            }
        }
    }

    protected function clicks(){

    }
    /**
     * Add Settings Options
     * @return void
     */
    public function add_settings(){
        add_filter( 'notificationx_settings_tab', array( $this, 'add_settings_tab' ) );
    }

    public function add_settings_tab( $options ){
        $general = $options['advanced_settings_tab'];

        $general['sections']['analytics'] = array(
            'priority' => 20,
            'title'    => __('Analytics', 'notificationx'),
            'fields'   => array(
                'enable_analytics' => array(
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable Analytics', 'notificationx' ),
                    'default'  => 1,
                    'priority' => 0,
                    'dependency' => array(
                        1 => array( 
                            'fields' => array( 'analytics_from', 'exclude_bot_analytics' )
                        )
                    ),
                    'hide' => array(
                        0 => array( 
                            'fields' => array( 'analytics_from', 'exclude_bot_analytics' )
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
                    'priority' => 1,
                ),
                'exclude_bot_analytics' => array(
                    'type'        => 'checkbox',
                    'label'       => __( 'Exclude Bot Analytics', 'notificationx' ),
                    'default'     => 1,
                    'priority'    => 1,
                    'description' => __( 'Select if you want to exclude bot analytics.', 'notificationx' ),
                ),
            ),
        );

        $options['advanced_settings_tab'] = $general;
        return $options;
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