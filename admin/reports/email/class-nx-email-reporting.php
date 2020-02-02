<?php 
/**
 * This class is responsible for sending weekly email with reports
 * 
 * @since 1.4.4
 */

include plugin_dir_path( __FILE__ ) . 'templates/nxm-header.php';

class NotificationX_Report_Email {
    /**
     * Get a single Instance of Analytics
     * @var NotificationX_Report_Email
     */
    private static $_instance = null;

    public function __construct() {
        add_filter( 'cron_schedules', array( $this, 'custom_cron_schedule_weekly' ) );
        // register_activation_hook(__FILE__, array( $this, 'mail_report_activation' ));
        add_action('admin_init', array( $this, 'mail_report_activation' ));
        add_action('weekly_email_reporting', array( $this, 'send_email_weekly' ));
        register_deactivation_hook(__FILE__, array( $this, 'mail_report_deactivation' ));


        add_action('admin_init', array( $this, 'test_function' ));
    }

    public function test_function() {
        // $html = email_template();
        // echo $html;
        // error_log(print_r($html, TRUE)); 
        // die;
    }

    /**
     * Calculate Total NotificationX Views
     * @return int
     */
    public function get_total_views() {
        $totalviews = 0;
        global $wpdb;
        $viewsForEachPost = $wpdb->get_results( 'SELECT post_id, meta_value FROM ' . $wpdb->prefix . 'postmeta WHERE meta_key = "_nx_meta_views"'  );
        if ( !empty($viewsForEachPost) && is_array($viewsForEachPost) ) {
            foreach ( $viewsForEachPost as $view ) {
                $totalviews = $totalviews + $view->meta_value;
            }
        }
        return $totalviews;
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
            'display'  => __( 'Once Weekly', 'NotificationX' )
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
        $email = get_option( 'admin_email' );
        return $email;
    }
    
    /**
     * Set Email Subject
     * By Default, subject will be "Weekly Reporting for NotificationX"
     * Admin can set Custom Subject from NotificationX Advanced Settings Panel
     * @return subject||String
     */
    public function email_subject() {
        $subject = "Weekly Reporting for NotificationX";
        return $subject;
    }
    
    /**
     * Generate Email HTML
     * @return String
     */
    public function email_body() {
        $totalviews = self::get_total_views();
        $totalclicks = self::get_total_clicks();
        $click_through_rate = $totalviews/$totalclicks;
        $impression_data = self::get_impression_data();

        $html = email_template();
        // $html = "<h1>email_template</h1>";

        return (string)$html;
    }

    /**
     * Enable Cron Function
     * Hook: admin_init
     */
    function mail_report_activation() {
        $datetime = strtotime("+7 days"); //Set Initial Date to 7 Days on setup
        if (! wp_next_scheduled ( 'weekly_email_reporting' )) {
            wp_schedule_event( $datetime, 'nx_weekly', 'weekly_email_reporting' );
        }
    }

    /**
     * Execute Cron Function
     * Hook: admin_init
     */
    function send_email_weekly() {
        $to = $this->receiver_email_address();
        $subject = $this->email_subject();
        $message = $this->email_body();
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail( $to, $subject, $message, $headers );          
    }    

    /**
     * Disable Cron Function
     * Hook: plugin_deactivation
     */
    function mail_report_deactivation() {
        wp_clear_scheduled_hook('weekly_email_reporting');
    }

}

NotificationX_Report_Email::get_instance();