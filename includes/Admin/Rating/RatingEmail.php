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
                'permission_callback' => array($this, 'send_rating_permission'),
                'args'                => array(
                    'rating' => array(
                        'required'    => true,
                        'type'        => 'integer',
                        'description' => __( 'Star rating between 1 and 5.', 'notificationx' ),
                    ),
                    'review' => array(
                        'required'    => false,
                        'type'        => 'string',
                        'description' => __( 'Optional free-text feedback.', 'notificationx' ),
                    ),
                ),
            ),
        ));
    }

    /**
     * Submitting the rating prompt is a plugin-administration action: it mails
     * the vendor and writes a site-wide option. The route used to be open, which
     * let anyone drive `wp_mail()` in a loop from the outside. Gate it on the
     * capability that already guards the plugin's global settings -- it defaults
     * to administrator only, and the admin app that shows the prompt already
     * carries the `wp_rest` nonce every other NotificationX route relies on.
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return bool
     */
    public function send_rating_permission( $request ) {
        return current_user_can( 'edit_notificationx_settings' );
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
        $params = $request->get_params();
        $rating = isset($params['rating']) ? intval($params['rating']) : null;
        $review = isset($params['review']) ? sanitize_text_field($params['review']) : '';

        // The prompt is a five-star widget, so anything outside that range did
        // not come from it.
        if ($rating < 1 || $rating > 5) {
            return new \WP_REST_Response(['message' => 'Invalid rating'], 400);
        }

        // Throttle rather than lock permanently. A one-shot flag would be the
        // tighter guard, but `nx_feedback_shared` was previously written on every
        // request -- including failed ones -- so on existing sites it is already
        // true for people who never actually submitted, and keying on it would
        // lock them out for good.
        $throttle_key = 'nx_rating_sent_' . get_current_user_id();
        if (get_transient($throttle_key)) {
            return new \WP_REST_Response(['message' => 'Feedback already submitted'], 429);
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
            // Only record the feedback as shared once it actually went out --
            // writing it first meant a failed send still suppressed the prompt.
            update_option('nx_feedback_shared', true);
            set_transient($throttle_key, time(), HOUR_IN_SECONDS);
            return new \WP_REST_Response(['message' => 'Email sent successfully'], 200);
        } else {
            return new \WP_REST_Response(['message' => 'Failed to send email'], 500);
        }
    }
    


    public static function _namespace(){
        return  self::$_namespace . '/v' . self::$_version;
    }
}