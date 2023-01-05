<?php
/**
 * GravityForms Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\GRVF;

use NotificationX\Core\Rules;
use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * GravityForms Extension
 * @method static GravityForms get_instance($args = null)
 */
class GravityForms extends Extension {
    /**
     * Instance of GravityForms
     *
     * @var GravityForms
     */
    use GetInstance;

    public $priority        = 20;
    public $id              = 'grvf';
    public $img             = '';
    public $doc_link        = 'https://notificationx.com/docs/contact-form-submission-alert/';
    public $types           = 'form';
    public $module          = 'modules_grvf';
    public $module_priority = 11;
    public $is_pro          = true;
    public $version         = '1.4.4';
    public $class           = '\GFForms';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('Gravity Forms', 'notificationx');
        $this->module_title = __('Gravity Forms', 'notificationx');
        $this->popup = [
            "denyButtonText" => __("<a href='https://notificationx.com/docs/gravity-forms/' target='_blank'>More Info</a>", "notificationx"),
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            "html"=> __('
                <span>A WordPress contact forms plugin that can help you keep important leads and stay in touch with your customers.</span>
                <iframe id="email_subscription_video" type="text/html" allowfullscreen width="450" height="235"
                src="https://www.youtube.com/embed/1Gl3XRd1TxY">
                </iframe>
            ', 'notificationx')
        ];
        parent::__construct();
    }

    public function source_error_message($messages) {
        if (!$this->class_exists()) {
            $url = "https://www.gravityforms.com/";
            $messages[$this->id] = [
                'message' => sprintf( '%s <a href="%s" target="_blank">%s</a> %s',
                    __( 'You have to install', 'notificationx' ),
                    $url,
                    __( 'Gravity Forms', 'notificationx' ),
                    __( 'plugin first.', 'notificationx' )
                ),
                'html' => true,
                'type' => 'error',
                'rules' => Rules::is('source', $this->id),
            ];
        }
        return $messages;
    }

    public function doc(){
        return sprintf(__('<p>Make sure that you have <a target="_blank" href="%1$s">Gravity Forms installed & configured</a>, to use its campaign & form subscriptions data. For further assistance, check out our step by step <a target="_blank" href="%2$s">documentation</a>.</p>
		<p>🎦 <a target="_blank" href="%3$s">Watch video tutorial</a> to learn quickly</p>
		<p>👉NotificationX <a target="_blank" href="%4$s">Integration with Ninja Forms</a></p>
		<p><strong>Recommended Blog:</strong></p>
		<p>🔥Hacks to Increase Your <a target="_blank" href="%5$s">WordPress Contact Forms Submission Rate</a> Using NotificationX</p>', 'notificationx'),
        'https://www.gravityforms.com/',
        'https://notificationx.com/docs/gravity-forms/',
        'https://www.youtube.com/watch?v=1Gl3XRd1TxY',
        'https://notificationx.com/integrations/gravity-forms/',
        'https://notificationx.com/blog/wordpress-contact-forms/'
        );
    }

}
