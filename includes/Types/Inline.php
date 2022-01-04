<?php

/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Types;

use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;

/**
 * Extension Abstract for all Extension.
 */
class Inline extends Types {
    /**
     * Instance of Admin
     *
     * @var Admin
     */
    use GetInstance;
    public $priority       = 60;
    public $is_pro         = true;
    public $module         = ['modules_woocommerce'];
    public $id             = 'inline';
    public $default_source = 'woo_inline';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        $this->title = __('Inline Notification', 'notificationx');

        // nx_comment_colored_themes
        parent::__construct();

    }

    /**
     * Hooked to nx_before_metabox_load action.
     *
     * @return void
     */
    public function init_fields() {
        parent::init_fields();


    }


}
