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

    public function __construct() {
        // add_action( 'admin_init', array( $this, 'send_email' ) );
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

    public function receiver_email_address(){
        $email = get_option( 'admin_email' );

        return $email;
    }
    
    public static function send_email(){
        $to = self::receiver_email_address();
        $subject = "Test Email";
        $message = "Hello Message";
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $attachments = "";

        wp_mail( $to, $subject, $message, $headers );
    }



}

NotificationX_Report_Email::get_instance();