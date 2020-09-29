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
    public $settings = null;
    /**
     * Initially Invoked by Default.
     */
    public function __construct() {
        $this->settings = NotificationX_DB::get_settings();
        if( ( isset( $this->settings['enable_analytics'] ) && ! $this->settings['enable_analytics'] ) || ( isset( $this->settings['disable_reporting'] ) && $this->settings['disable_reporting'] ) ) {
            $this->mail_report_deactivation( 'daily_email_reporting' );
            $this->mail_report_deactivation( 'weekly_email_reporting' );
            $this->mail_report_deactivation( 'monthly_email_reporting' );
            return;
        }
        $this->migration();
        add_filter( 'cron_schedules', array( $this, 'schedules_cron' ) );
        add_action('admin_init', array( $this, 'mail_report_activation' ));
        add_action('weekly_email_reporting', array( $this, 'send_email_weekly' ));
        add_action('wp_ajax_nx_email_report_test', array( $this, 'email_test_report' ));
        // add_action('admin_init', array( $this, 'test_function' ));
    }

    public function test_function() {
        // dump( $this->get_data( 'nx_monthly' ) );
        // die;
    }
    /**
     * Just for correct timing purpose.
     */
    public function migration(){
        $version_migration = get_option( 'nx_version_migration_161', false );
        if( $version_migration === false && version_compare( NOTIFICATIONX_VERSION, '1.6.1', '==') ) { 
            update_option( 'nx_version_migration_161', true );
            $this->mail_report_deactivation( 'daily_email_reporting' );
            $this->mail_report_deactivation( 'weekly_email_reporting' );
            $this->mail_report_deactivation( 'monthly_email_reporting' );
        }
    }
    /**
     * Calculate Total NotificationX Views
     * @return int
     */
    public function get_data( $frequency = 'nx_weekly') {
        global $wpdb;
        $viewsForEachPost = $wpdb->get_results( "SELECT main_meta.post_id, main_meta.meta_value, main_posts.post_title FROM {$wpdb->prefix}posts as main_posts LEFT JOIN {$wpdb->prefix}postmeta as main_meta ON main_posts.ID = main_meta.post_id  WHERE  main_meta.meta_key = '_nx_meta_impression_per_day' AND  main_meta.post_id IN ( SELECT posts.ID FROM {$wpdb->prefix}posts as posts LEFT JOIN {$wpdb->prefix}postmeta as meta ON posts.ID = meta.post_id WHERE posts.post_type='notificationx' AND meta.meta_key='_nx_meta_active_check' AND meta.meta_value=1 )" );
        return $this->reshape_data( $viewsForEachPost, $frequency );
    }

    public function reshape_data( $data = array(), $frequency ){
        if( empty( $data ) ) {
            return [];
        }
        global $wpdb;
        $new_data = [];
        $current_timestamp = current_time('timestamp');
        if( $frequency == 'nx_weekly' ) {
            $last_timestamp = strtotime('-14days', $current_timestamp);
            $initial_timestamp = strtotime('-7days', $current_timestamp);
            $days_in_month = 7;
            $days_in_last_month = 7;
        }
        if( $frequency == 'nx_daily' ) {
            $last_timestamp = strtotime('last day last day', $current_timestamp );
            $initial_timestamp = strtotime('last day', $current_timestamp);
            $days_in_month = 1;
            $days_in_last_month = 1;
        }
        if( $frequency == 'nx_monthly' ) {
            $last_timestamp = strtotime('first day of last month last month', $current_timestamp);
            $initial_timestamp = strtotime('first day of last month', $current_timestamp);
            $days_in_month = cal_days_in_month(CAL_GREGORIAN, date( 'm', $last_timestamp ), date( 'Y', $last_timestamp ));
            $days_in_last_month = cal_days_in_month(CAL_GREGORIAN, date( 'm', $initial_timestamp ), date( 'Y', $initial_timestamp ));
        }
        $start_date = new DateTime( date( 'd-m-Y', $last_timestamp ) );
        $end_date = new DateTime(  date( 'd-m-Y', $current_timestamp ) );
        $interval = $start_date->diff( $end_date, true );
        $frequency_days = $interval->days;
        $new_data = $this->generate_data( $data, $frequency, $frequency_days, $current_timestamp, $initial_timestamp, $last_timestamp, $days_in_month, $days_in_last_month );
        return $new_data;
    }

    public function generate_data( $data, $frequency, $frequency_days, $current_timestamp, $initial_timestamp, $last_timestamp, $days_in_month, $days_in_last_month ){
        $new_data = array();
        $from_date = date('d-m-Y', $initial_timestamp );
        $to_date = date('d-m-Y', strtotime( "-1day", $current_timestamp ) );
        if( $frequency === 'nx_monthly' ) {
            $to_date = date('d-m-Y', strtotime( "last day of last month", $current_timestamp ) );
        }
        $initial_timestamp = strtotime( date('d-m-Y', $initial_timestamp ) );
        $last_timestamp = strtotime( date('d-m-Y', $last_timestamp ) );
        $current_timestamp = strtotime( date('d-m-Y', $current_timestamp ) );

        array_walk( $data, function( $value, $key ) use ( &$new_data, $frequency_days, $days_in_month, $days_in_last_month, $current_timestamp, $initial_timestamp, $last_timestamp, $from_date, $to_date ) {
            if( isset( $value->meta_value ) ) {
                $nx_id = $value->post_id;
                $title = $value->post_title;
                $meta_value = unserialize( $value->meta_value );
                $meta_value = array_reverse( $meta_value );
                $wk_wise_meta = array_slice( $meta_value, 0, $frequency_days );
                $previous_month_data = array_slice( $wk_wise_meta, 1, $days_in_last_month );
                $last_month_data = array_slice( $wk_wise_meta, $days_in_last_month + 1, $days_in_month );

                $metaSettings = NotificationX_MetaBox::get_metabox_settings( $nx_id );
                $type = NotificationX_Helper::get_type( $metaSettings );
                if( ! empty( $previous_month_data ) && is_array( $previous_month_data ) ) {
                    $views = $clicks = $ctr = $last_clicks = $last_views = 0;
                    array_walk( $previous_month_data, function( $wk_value, $wk_key ) use( &$views, &$clicks ) {
                        if( ! empty( $wk_value ) ) {
                            $views = isset( $wk_value['impressions'] ) ? $views + $wk_value['impressions'] : 0;
                            $clicks = isset( $wk_value['clicks'] ) ? $clicks + $wk_value['clicks'] : 0;
                        }
                    } );
                    if( is_array( $last_month_data ) && ! empty( $last_month_data ) ){
                        array_walk( $last_month_data, function( $wk_value, $wk_key ) use( &$last_views, &$last_clicks ) {
                            if( ! empty( $wk_value ) ) {
                                $last_views = isset( $wk_value['impressions'] ) ? $last_views + $wk_value['impressions'] : 0;
                                $last_clicks = isset( $wk_value['clicks'] ) ? $last_clicks + $wk_value['clicks'] : 0;
                            }
                        } );
                    }
                    $ctr = $views > 0 ? number_format( ( intval( $clicks ) / intval( $views ) ) * 100, 2) : 0;
                    $last_ctr = $last_views > 0 ? number_format( ( intval( $last_clicks ) / intval( $last_views ) ) * 100, 2) : 0;
                    $new_data[ $nx_id ] = array(
                        'views' => $views,
                        'last_views' => $last_views,
                        'clicks' => $clicks,
                        'last_clicks' => $last_clicks,
                        'percentage_views' => $last_views > 0 ? number_format( ( ( $views - $last_views ) / $last_views ) * 100, 2 ) : 0,
                        'percentage_clicks' => $last_clicks > 0 ? number_format( ( ( $clicks - $last_clicks ) / $last_clicks ) * 100, 2 ) : 0,
                        'ctr' => $ctr,
                        'percentage_ctr' => $last_ctr > 0 ? number_format( ( ( $ctr - $last_ctr ) / $last_ctr ) * 100, 2 ) : 0,
                        'source' => $type,
                        'type' => NotificationX_Helper::notification_types( $metaSettings->display_type ),
                        'from_date' => $from_date,
                        'to_date' => $to_date,
                        'title' => $title
                    );
                }
            }
        } );
        return $new_data;
    }
    /**
     * Adds a custom cron schedule for Weekly.
     *
     * @param array $schedules An array of non-default cron schedules.
     * @return array Filtered array of non-default cron schedules.
     */
    function schedules_cron( $schedules = array() ) {
        $schedules['nx_weekly'] = array(
            'interval' => 604800,
            'display'  => __( 'Once Weekly', 'notificationx' )
        );
        $schedules['nx_daily'] = array(
            'interval' => 86400,
            'display'  => __( 'Once Daily', 'notificationx' )
        );
        $schedules['nx_monthly'] = array(
            'interval' => strtotime( 'first day of next month 9AM' ),
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
    public function receiver_email_address( $email = '' ) {
        if( empty( $email ) ) {
            $email = NotificationX_DB::get_settings( 'reporting_email' );
            if( empty( $email ) ) {
                $email = get_option( 'admin_email' );
            }
        }
        if( strpos( $email, ',' ) !== false ) {
            $email = str_replace( ' ', '', $email );
            $email = explode(',', $email );
        } else {
            $email = trim( $email );
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
        $site_name = get_bloginfo( 'name' );
        $subject = __( "Weekly Engagement Summary of ‘{$site_name}’", 'notificationx' );
        if( isset( $this->settings['reporting_subject'] ) && ! empty( $this->settings['reporting_subject'] ) ) {
            $subject = stripcslashes( $this->settings['reporting_subject'] );
        }
        return $subject;
    }
    
    public function reporting_frequency(){
        $frequency = 'nx_weekly';
        if( class_exists('NotificationXPro') && isset( $this->settings['reporting_frequency'] ) && ! empty( $this->settings['reporting_frequency'] ) && is_string( $this->settings['reporting_frequency'] ) ) {
            $frequency = $this->settings['reporting_frequency'];
        }
        return $frequency;
    }
    /**
     * Enable Cron Function
     * Hook: admin_init
     */
    function mail_report_activation() {
        $day = "monday";
        if( isset( $this->settings['reporting_day'] ) ) {
            $day = $this->settings['reporting_day'];
        }

        $frequency = $this->reporting_frequency();
        if( $frequency === 'nx_weekly' ) {
            $datetime = strtotime( "next $day 9AM", current_time('timestamp') );
            $triggered = NotificationX_DB::get_settings( '', "{$frequency}_mail_sent" );
            if ( $triggered == 1 ) {
                $this->mail_report_deactivation( 'weekly_email_reporting' );
            }
            if( ! $triggered ) {
                $datetime = strtotime( "+1hour", current_time('timestamp') );
            }
            $this->mail_report_deactivation( 'daily_email_reporting' );
            $this->mail_report_deactivation( 'monthly_email_reporting' );
            if ( ! wp_next_scheduled ( 'weekly_email_reporting' ) ) {
                wp_schedule_event( $datetime, $frequency, 'weekly_email_reporting' );
            }
        }
        
    }

    /**
     * Execute Cron Function
     * Hook: admin_init
     */
    public function send_email_weekly( $frequency = 'nx_weekly', $test = false, $email = null ) {
        $data = $this->get_data( $frequency );
        if( empty( $data ) ) {
            return false;
        }
        if( isset( $this->settings['enable_analytics'] ) && ! $this->settings['enable_analytics'] ) {
            return false;
        }
        $to = is_null( $email ) ? $this->receiver_email_address() : $email;
        if( empty( $to ) ) {
            return false;
        }
        if( ! class_exists( 'NotificationX_Email_Template' ) ) {
            require_once NOTIFICATIONX_ROOT_DIR_PATH . 'admin/reports/email/class-nx-email-template.php';
        }
        $subject = $this->email_subject();
        $template = new NotificationX_Email_Template();
        $message = $template->template_body( $data, $frequency );
        $headers = array( 'Content-Type: text/html; charset=UTF-8', "From: NotificationX <support@wpdeveloper.net>" );
        if( ! $test ) {
            $triggered = NotificationX_DB::get_settings( '', "{$frequency}_mail_sent" );
            $triggered = ! $triggered ? 0 : $triggered++;
            NotificationX_DB::update_settings( $triggered, "{$frequency}_mail_sent" );
        }
        return wp_mail( $to, $subject, $message, $headers );
    }

    /**
     * Disable Cron Function
     * Hook: plugin_deactivation
     */
    public function mail_report_deactivation( $clear_hook = 'weekly_email_reporting' ) {
        wp_clear_scheduled_hook( $clear_hook );
    }

    public static function test_report(){
        echo '<button class="nx-email-test">'. __( 'Test Report', 'notificationx' ) .'</button>';
    }
    public function email_test_report(){
        $email = isset( $_POST['email'] ) ? $this->receiver_email_address( sanitize_text_field( $_POST['email'] ) ) : '';

        if( ! empty( $email ) ) {
            if( $this->send_email_weekly( $this->reporting_frequency(), true, $email ) ) {
                wp_send_json_success( __( 'Successfully Sent an Email', 'notificationx' ) );
            }
        }

        wp_send_json_error( __( 'Something went wrong.', 'notificationx' ) );
    }
}

NotificationX_Report_Email::get_instance();