<?php
/**
 * Wistia Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Wistia;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * Wistia Extension
 * @method static Wistia get_instance($args = null)
 */
class Wistia extends Extension {
    /**
     * Instance of Wistia
     *
     * @var Wistia
     */
    use GetInstance;

    public $priority        = 15;
    public $id              = 'wistia';
    public $doc_link        = 'https://notificationx.com/docs/google-reviews-with-notificationx/';
    public $types           = 'video';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/wistia.png';
    public $show_on_module  = false;
    public $show_on_type     = false;
    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('Wistia', 'notificationx');
        parent::__construct();
    }

}
