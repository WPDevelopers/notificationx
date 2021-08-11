<?php
/**
 * LearnDash Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\LearnDash;

use NotificationX\Core\Rules;
use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * LearnDash Extension
 */
class LearnDash extends Extension {
    /**
     * Instance of LearnDash
     *
     * @var LearnDash
     */
    use GetInstance;

    public $priority        = 10;
    public $id              = 'learndash';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/learndash.png';
    public $doc_link        = 'https://notificationx.com/docs/how-to-display-learndash-course-enrollment-alert-using-notificationx/';
    public $types           = 'elearning';
    public $module          = 'modules_learndash';
    public $module_priority = 18;
    public $is_pro          = true;
    public $version         = '1.2.0';
    public $class           = '\LDLMS_Post_Types';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('LearnDash', 'notificationx');
        $this->module_title = __('LearnDash', 'notificationx');
        parent::__construct();
    }

    public function source_error_message($messages) {
        if (!$this->class_exists()) {
            $messages[$this->id] = [
                'message' => __('You have to install <a target="_blank" rel="nofollow" href="https://www.learndash.com">LearnDash</a> plugin first.' , 'notificationx'),
                'html' => true,
                'type' => 'error',
                'rules' => Rules::is('source', $this->id),
            ];
        }
        return $messages;
    }

    public function doc(){
        return '<p>Make sure that you have <a target="_blank" href="https://www.learndash.com/">LearnDash installed & configured</a> to use its campaign & course selling data.  For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/how-to-display-learndash-course-enrollment-alert-using-notificationx">documentation</a>.</p>
		<p>🎦 <a target="_blank" href="https://www.youtube.com/watch?v=sTbBt2DVsIA">Watch video tutorial</a> to learn quickly</p>
		<p>👉 NotificationX <a target="_blank" href="https://notificationx.com/integrations/learndash/">Integration with LearnDash</a> </p>
		<p><strong>Recommended Blog:</strong></p>
		<p>🔥 How to Increase Your <a target="_blank" href="https://wpdeveloper.net/learndash-course-enrollment-rate-notificationx/">LearnDash Course Enrollment Rates</a> With NotificationX</p>';
    }
}
