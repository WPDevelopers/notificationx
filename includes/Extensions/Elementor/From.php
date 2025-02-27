<?php
/**
 * Envato Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Elementor;

use NotificationX\Core\Rules;
use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * Envato Extension
 * @method static From get_instance($args = null)
 */
class From extends Extension {
    /**
     * Instance of Envato
     *
     * @var Envato
     */
    use GetInstance;

    public $priority        = 25;
    public $id              = 'elementor_form';
    // public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/envato.png';
    public $doc_link        = 'https://notificationx.com/docs/elementor-form-with-notificationx/';
    public $types           = 'form';
    public $module          = 'elementor_form';
    public $module_priority = 17;
    public $is_pro          = true;
    public $class           = 'ElementorPro\Modules\Forms\Submissions\Database\Repositories\Form_Snapshot_Repository';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
    }

    public function init_extension()
    {
        $this->title = __('Elementor Form', 'notificationx');
        $this->module_title = __('Elementor', 'notificationx');
        $this->popup = [
            "denyButtonText" => __("<a href='https://notificationx.com/docs/elementor-form-with-notificationx/' target='_blank'>More Info</a>", "notificationx"),
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            "html"=> __('
                <span>Elementor forms that can help you keep important leads and stay in touch with your customers.</span>
            ', 'notificationx')
        ];
    }

    public function source_error_message($messages) {
        if (!$this->class_exists()) {
            $url = "https://elementor.com/";
            $messages[$this->id] = [
                'message' => sprintf( '%s <a href="%s" target="_blank">%s</a> %s',
                    __( 'You have to install', 'notificationx' ),
                    $url,
                    __( 'Elementor Pro', 'notificationx' ),
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
        // translators: links
        return sprintf(__('<p>Make sure that you have <a target="_blank" href="%1$s">Elementor Pro installed & configured</a> to use its form submission data. For further assistance, check out our step by step <a target="_blank" href="%2$s">documentation</a>.</p>
		<p><strong>Recommended Blog:</strong></p>
		<p>🔥 Hacks to Increase Your <a target="_blank" href="%3$s">WordPress Contact Forms Submission Rate</a> Using NotificationX</p>', 'notificationx'),
        'https://elementor.com/',
        'https://notificationx.com/docs/elementor-form-with-notificationx/',
        'https://notificationx.com/blog/wordpress-contact-forms/'
        );
    }
}
