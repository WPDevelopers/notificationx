<?php

namespace NotificationX\Admin\Rating;

use NotificationX\GetInstance;
use WP_REST_Server;

/**
 * This class is responsible for sending weekly email with reports
 * @method static RatingEmail get_instance($args = null)
 *
 * @since 1.4.4
 */
class RatingEmail {
    private static $_namespace   = 'notificationx';
    private static $_version     = 1;
    private static $_mail_sendto = 'support@wpdeveloper.com';
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
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'send_rating'),
                'permission_callback' => '__return_true',
                'args'                => [],
            ),
        ));
    }


    /**
     * Set Email Subject
     * By Default, subject will be "Weekly Reporting for NotificationX"
     * Admin can set Custom Subject from NotificationX Advanced Settings Panel
     * @return subject||String
     */
    public function email_subject() {
        $subject = __( "[IMPORTANT] New feedback received from a NotificationX user", 'notificationx' );
        return $subject;
    }

    public function send_rating($request)
    {
        update_option('nx_feedback_shared', true);
        $params = $request->get_params();
        $rating = isset($params['rating']) ? intval($params['rating']) : null;
        $review = isset($params['review']) ? sanitize_text_field($params['review']) : '';
    
        if (!$rating) {
            return new \WP_REST_Response(['message' => 'Invalid rating'], 400);
        }
    
        $to = self::$_mail_sendto; // Email recipient
        $subject = $this->email_subject();
        
        $data = [
            'rating' => $rating,
            'review' => $review
        ];
        
        $template = new EmailTemplate();
        $message = $template->template_body($data, 'weekly'); // Pass data
        
        $headers = ['Content-Type: text/html; charset=UTF-8', "From: NotificationX <support@wpdeveloper.com>"];
    
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