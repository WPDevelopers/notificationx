<?php
/**
 * GoogleReviews Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Google;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * GoogleReviews Extension
 */
class GoogleReviews extends Extension {
    /**
     * Instance of GoogleReviews
     *
     * @var GoogleReviews
     */
    use GetInstance;

    public $priority        = 5;
    public $id              = 'google_reviews';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/google-rating.png';
    public $doc_link        = 'https://notificationx.com/docs/google-analytics/';
    public $types           = 'reviews';
    public $module          = 'modules_google_reviews';
    public $module_priority = 20;
    public $is_pro          = true;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('Google Reviews', 'notificationx');
        $this->module_title = __('Google Reviews', 'notificationx');
        parent::__construct();
    }


    public function doc(){
        return sprintf(__('<p>Make sure that you have <a target="_blank" href="%1$s">signed in to Google Analytics site</a>, to use its campaign & page analytics data. For further assistance, check out our step by step <a target="_blank" href="%2$s">documentation</a>.</p>
		<p>🎦 <a target="_blank" href="%3$s">Watch video tutorial</a> to learn quickly</p>
		<p>👉NotificationX <a target="_blank" href="%4$s">Integration with Google Analytics</a></p>', 'notificationx'),
        'https://analytics.google.com/analytics/web/',
        'https://notificationx.com/docs/google-analytics/',
        'https://www.youtube.com/watch?v=zZPF5nJD4mo',
        'https://notificationx.com/docs/google-analytics/'
        );
    }
}
