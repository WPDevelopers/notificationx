<?php
/**
 * Vimeo Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Vimeo;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * Vimeo Extension
 * @method static Vimeo get_instance($args = null)
 */
class Vimeo extends Extension {
    /**
     * Instance of Vimeo
     *
     * @var Vimeo
     */
    use GetInstance;

    public $priority        = 10;
    public $id              = 'vimeo';
    public $doc_link        = 'https://notificationx.com/docs/google-reviews-with-notificationx/';
    public $types           = 'video';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/vimeo.png';
    public $show_on_module  = false;
    public $show_on_type     = false;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
    }
    
    public function init_extension()
    {
        $this->title = __('Vimeo', 'notificationx');
    }

}
