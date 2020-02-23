<?php 
/**
 * This class is responsible for sending weekly email with reports
 * 
 * @since 1.4.4
 */
class NotificationX_Report_Email {
    /**
     * Get a single Instance of Analytics
     * @var NotificationX_Report_Email
     */
    private static $_instance = null;
    protected $settings = null;
    /**
     * Initially Invoked by Default.
     */
    public function __construct() {
        $this->settings = NotificationX_DB::get_settings();
        add_filter( 'cron_schedules', array( $this, 'custom_cron_schedule_weekly' ) );
        add_action('admin_init', array( $this, 'mail_report_activation' ));
        add_action('weekly_email_reporting', array( $this, 'send_email_weekly' ));
        add_action('wp_ajax_nx_email_report_test', array( $this, 'email_test_report' ));
        // register_activation_hook(__FILE__, array( $this, 'mail_report_activation' ));
        // register_deactivation_hook(__FILE__, array( $this, 'mail_report_deactivation' ));
        // add_action('admin_init', array( $this, 'test_function' ));
    }

    public function test_function() {
    
        // dump( $this->settings );

        // die;
    }
    /**
     * Calculate Total NotificationX Views
     * @return int
     */
    public function get_weekly_data( $frequency = 'nx_weekly') {
        $totalviews = 0;
        global $wpdb;
        $viewsForEachPost = $wpdb->get_results( 'SELECT DISTINCT post_id, meta_value FROM ' . $wpdb->prefix . 'postmeta WHERE meta_key = "_nx_meta_impression_per_day"'  );

        return $this->collect_last_week_data( $viewsForEachPost, $frequency );
    }

    public function collect_last_week_data( $data = array(), $frequency ){
        if( empty( $data ) ) {
            return [];
        }
        global $wpdb;
        $new_data = [];
        $meta_index = 0;
        $last_meta_index = 1;

        if( $frequency == 'nx_weekly' ) {
            $frequency_days = 7;
        }
        if( $frequency == 'nx_daily' ) {
            $frequency_days = 1;
            $meta_index = 1;
            $last_meta_index = 2;
        }
        if( $frequency == 'nx_monthly' ) {
            $frequency_days = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));;
        }

        array_walk( $data, function( $value, $key ) use ( &$new_data, $wpdb, $frequency_days, $meta_index, $last_meta_index ) {
            if( isset( $value->meta_value ) ) {
                $nx_id = $value->post_id;
                $meta_value = unserialize( $value->meta_value );
                $meta_value = array_reverse( $meta_value );
                $i = 1;

                $wk_wise_meta = array_chunk( $meta_value, $frequency_days, true );
                $metaSettings = NotificationX_MetaBox::get_metabox_settings( $nx_id );
                $type = NotificationX_Helper::get_type( $metaSettings );
                $active = get_post_meta( $nx_id, '_nx_meta_active_check', true );

                if( $active !== '0' ) {
                    if( ! empty( $wk_wise_meta ) && isset( $wk_wise_meta[ $meta_index ] ) && is_array( $wk_wise_meta[ $meta_index ] ) ) {
                        $views = $clicks = $ctr = $last_wk_clicks = $last_wk_views = 0;

                        $from_date = $to_date = '';
                        
                        array_walk( $wk_wise_meta[ $meta_index ], function( $wk_value, $wk_key ) use( $nx_id, &$i, &$views, &$clicks, &$from_date, &$to_date, $last_meta_index ) {
                            if( $i === 1 ) {
                                $from_date = $wk_key;
                            }
                            if( $i === 7 ) {
                                $to_date = $wk_key;
                            }
                            $i++;
                            if( ! empty( $wk_value ) ) {
                                $views = isset( $wk_value['impressions'] ) ? $views + $wk_value['impressions'] : 0;
                                $clicks = isset( $wk_value['clicks'] ) ? $views + $wk_value['clicks'] : 0;
                            }
                        } );
                        if( isset( $wk_wise_meta[ $last_meta_index ] ) && is_array( $wk_wise_meta[ $last_meta_index ] ) ){
                            array_walk( $wk_wise_meta[ $last_meta_index ], function( $wk_value, $wk_key ) use( &$last_wk_views, &$last_wk_clicks ) {
                                if( ! empty( $wk_value ) ) {
                                    $last_wk_views = isset( $wk_value['impressions'] ) ? $last_wk_views + $wk_value['impressions'] : 0;
                                    $last_wk_clicks = isset( $wk_value['clicks'] ) ? $last_wk_clicks + $wk_value['clicks'] : 0;
                                }
                            } );
                        }
                        $ctr = $views > 0 ? number_format( ( intval( $clicks ) / intval( $views ) ) * 100, 2) : 0;
                        $last_wk_ctr = $last_wk_views > 0 ? number_format( ( intval( $last_wk_clicks ) / intval( $last_wk_views ) ) * 100, 2) : 0;
                        $new_data[ $nx_id ] = array(
                            'views' => $views,
                            'last_wk_views' => $last_wk_views,
                            'clicks' => $clicks,
                            'last_wk_clicks' => $last_wk_clicks,
                            'percentage_views' => $last_wk_views > 0 ? number_format( ( ( $views - $last_wk_views ) / $last_wk_views ) * 100, 2 ) : 0,
                            'percentage_clicks' => $last_wk_clicks > 0 ? number_format( ( ( $clicks - $last_wk_clicks ) / $last_wk_clicks ) * 100, 2 ) : 0,
                            'ctr' => $ctr,
                            'percentage_ctr' => $last_wk_ctr > 0 ? number_format( ( ( $ctr - $last_wk_ctr ) / $last_wk_ctr ) * 100, 2 ) : 0,
                            'source' => $type,
                            'type' => NotificationX_Helper::notification_types( $metaSettings->display_type ),
                            'from_date' => $from_date,
                            'to_date' => $to_date,
                            'title' => get_the_title( $nx_id )
                        );
                    }
                }
            }
        } );

        return $new_data;
    }

    /**
     * Calculate Total NotificationX Clicks
     * @return int
     */
    public function get_total_clicks() {
        $totalclicks = 0;
        global $wpdb;
        $viewsForEachPost = $wpdb->get_results( 'SELECT post_id, meta_value FROM ' . $wpdb->prefix . 'postmeta WHERE meta_key = "_nx_meta_clicks"'  );
        if ( !empty($viewsForEachPost) && is_array($viewsForEachPost) ) {
            foreach ( $viewsForEachPost as $view ) {
                $totalclicks = $totalclicks + $view->meta_value;
            }
        }
        return $totalclicks;
    }

    /**
     * Impressions Per Day
     * @return int
     */
    public function get_impression_data() {
        $day = 7; //will be taken from settings
        date_default_timezone_get();
        $targeted_date = strtotime("-".$day." days");
        $targeted_date_format = date('m/d/y', $targeted_date);
        $targeted_date_final = strtotime($targeted_date_format);

        $totalviews = 0;
        $totalclicks = 0;

        global $wpdb;
        $query = $wpdb->get_results( 'SELECT post_id, meta_value FROM ' . $wpdb->prefix . 'postmeta WHERE meta_key = "_nx_meta_impression_per_day"' );

        if ( !empty($query) && is_array($query) ) {
            foreach ( $query as $postdata ) {
                $impressiondata = $postdata->meta_value;
                $impressiondata_array = unserialize( $impressiondata );
                foreach ($impressiondata_array as $key=>$value) {
                    $thisDate = strtotime($key);
                    if ($thisDate >= $targeted_date) {
                        if ( isset($value['impressions']) ) {
                            $totalviews = $totalviews + $value['impressions'];
                        }
                        if ( isset($value['clicks']) ) {
                            $totalclicks = $totalclicks + $value['clicks'];
                        }                        
                    }
                }
            }
        }
        $impression_data = array();
        $impression_data['totalviews'] = $totalviews;
        $impression_data['totalclicks'] = $totalclicks;

        return $impression_data;
    }
    /**
     * Adds a custom cron schedule for Weekly.
     *
     * @param array $schedules An array of non-default cron schedules.
     * @return array Filtered array of non-default cron schedules.
     */
    function custom_cron_schedule_weekly( $schedules = array() ) {
        $schedules['nx_weekly'] = array(
            'interval' => 604800,
            'display'  => __( 'Once Weekly', 'notificationx' )
        );
        $schedules['nx_daily'] = array(
            'interval' => 86400,
            'display'  => __( 'Once Daily', 'notificationx' )
        );

        $days_in_month = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));
        $schedules['nx_monthly'] = array(
            'interval' => 86400 * $days_in_month,
            'display'  => __( 'Once Monthly', 'notificationx' )
        );
        return $schedules;
    }
    /**
     * Get || Making a Single Instance of Analytics
     * @return self
     */
    public static function get_instance() {
        if( self::$_instance === null ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    /**
     * Set Email Receiver mail address
     * By Default, Admin Email Address
     * Admin can set Custom email from NotificationX Advanced Settings Panel
     * @return email||String
     */
    public function receiver_email_address() {
        $email = NotificationX_DB::get_settings( 'reporting_email' );
        if( empty( $email ) ) {
            $email = get_option( 'admin_email' );
        } else {
            if( strpos( $email, ',' ) !== false ) {
                $email = str_replace( ' ', '', $email );
                $email = explode(',', $email );
            }
        }
        return $email;
    }
    
    /**
     * Set Email Subject
     * By Default, subject will be "Weekly Reporting for NotificationX"
     * Admin can set Custom Subject from NotificationX Advanced Settings Panel
     * @return subject||String
     */
    public function email_subject() {
        $subject = __( "Your Weekly Engagement Summary from NotificationX", 'notificationx' );
        if( isset( $this->settings['reporting_subject'] ) && ! empty( $this->settings['reporting_subject'] ) ) {
            $subject = $this->settings['reporting_subject'];
        }
        return $subject;
    }
    
    protected function reporting_frequency(){
        $frequency = 'nx_weekly';
        if( isset( $this->settings['reporting_frequency'] ) && ! empty( $this->settings['reporting_frequency'] ) && is_string( $this->settings['reporting_frequency'] ) ) {
            $frequency = $this->settings['reporting_frequency'];
        }
        return $frequency;
    }
    /**
     * Enable Cron Function
     * Hook: admin_init
     */
    function mail_report_activation() {
        if( isset( $this->settings['enable_analytics'] ) && ! $this->settings['enable_analytics'] ) {
            return;
        }
        $day = "monday";
        if( isset( $this->settings['reporting_day'] ) ) {
            $day = $this->settings['reporting_day'];
        }

        if( $this->reporting_frequency() === 'nx_weekly' ) {
            $datetime = strtotime( "+7days next $day 9AM" );
            $this->mail_report_deactivation( 'daily_email_reporting' );
            $this->mail_report_deactivation( 'monthly_email_reporting' );
            if ( ! wp_next_scheduled ( 'weekly_email_reporting' ) ) {
                wp_schedule_event( $datetime, $this->reporting_frequency(), 'weekly_email_reporting' );
            }
        }
        if( $this->reporting_frequency() === 'nx_daily' ) {
            $datetime = strtotime( "+1days 9AM" );
            $this->mail_report_deactivation( 'weekly_email_reporting' );
            $this->mail_report_deactivation( 'monthly_email_reporting' );
            if ( ! wp_next_scheduled ( 'daily_email_reporting' ) ) {
                wp_schedule_event( $datetime, $this->reporting_frequency(), 'daily_email_reporting' );
            }
        }
        if( $this->reporting_frequency() === 'nx_monthly' ) {
            $days_in_month = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));
            $datetime = strtotime( "+{$days_in_month}days 9AM" );
            $this->mail_report_deactivation( 'daily_email_reporting' );
            $this->mail_report_deactivation( 'weekly_email_reporting' );
            if ( ! wp_next_scheduled ( 'monthly_email_reporting' ) ) {
                wp_schedule_event( $datetime, $this->reporting_frequency(), 'monthly_email_reporting' );
            }
        }
    }

    /**
     * Execute Cron Function
     * Hook: admin_init
     */
    public function send_email_weekly( $frequency = 'nx_weekly' ) {
        $data = $this->get_weekly_data( $frequency );
        if( empty( $data ) ) {
            return false;
        }
        if( isset( $this->settings['enable_analytics'] ) && ! $this->settings['enable_analytics'] ) {
            return false;
        }
        $to = $this->receiver_email_address();
        $subject = $this->email_subject();
        if( ! class_exists( 'NotificationX_Email_Template' ) ) {
            require_once NOTIFICATIONX_ROOT_DIR_PATH . 'admin/reports/email/class-nx-email-template.php';
        }
        $template = new NotificationX_Email_Template();
        $message = $template->template_body( $data, $frequency );
        $headers = array( 'Content-Type: text/html; charset=UTF-8', "From: NotificationX <support@wpdeveloper.net>" );
        return wp_mail( $to, $subject, $message, $headers );
    }

    public function send_email_monthly(){
        return $this->send_email_weekly( 'nx_monthly' );
    }
    public function send_email_daily(){
        return $this->send_email_weekly( 'nx_daily' );
    }

    /**
     * Disable Cron Function
     * Hook: plugin_deactivation
     */
    public function mail_report_deactivation( $clear_hook = 'weekly_email_reporting' ) {
        wp_clear_scheduled_hook( $clear_hook );
    }

    public static function test_report(){
        echo '<button class="nx-email-test">'. __( 'Test Report' ) .'</button>';
    }
    public function email_test_report(){
        // dump( $this->reporting_frequency() );
        // die;
        if( $this->send_email_weekly( $this->reporting_frequency() ) ) {
            wp_send_json_success( __( 'Successfully Sent an Email', 'notificationx' ) );
        }
        wp_send_json_error( __( 'Something went wrong.', 'notificationx' ) );
    }
}

NotificationX_Report_Email::get_instance();