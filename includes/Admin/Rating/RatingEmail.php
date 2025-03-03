<?php

namespace NotificationX\Admin\Reports;

use NotificationX\GetInstance;
use WP_REST_Server;

/**
 * This class is responsible for sending weekly email with reports
 * @method static RatingEmail get_instance($args = null)
 *
 * @since 1.4.4
 */
class RatingEmail {
    private static $_namespace = 'notificationx';
    private static $_version = 1;
    private static $_mail_sendto = 'shakibul@wpdeveloper.com';
    /**
     * Instance of RatingEmail
     *
     * @var RatingEmail
     */
    use GetInstance;

    /**
     * Initially Invoked by Default.
     */
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        $namespace = self::_namespace();

        register_rest_route($namespace, '/send-rating', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'send_rating'),
                'permission_callback' => '__return_true',
                'args'                => [],
            ),
        ));
    }


    public function send_rating($request)
    {
        $to = self::$_mail_sendto; // Email recipient
        $subject = 'Weekly Rating Report';
        $message = 'Hello, this is your weekly rating report.';
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        // Send email
        $sent = wp_mail($to, $subject, $message, $headers);

        if ($sent) {
            return new \WP_REST_Response(['message' => 'Email sent successfully'], 200);
        } else {
            return new \WP_REST_Response(['message' => 'Failed to send email'], 500);
        }
        
    }


    public static function _namespace(){
        return  self::$_namespace . '/v' . self::$_version;
    }
}