<?php

/**
 * Popup Notification
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Popup;

use NotificationX\GetInstance;
use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;
use NotificationX\Extensions\Extension;

/**
 * Popup Extension
 * @method static Popup get_instance($args = null)
 */
class PopupNotification extends Extension {
    /**
     * Instance of Popup
     *
     * @var Popup
     */
    use GetInstance;

    public $priority        = 15;
    public $id              = 'popup_notification';
    public $doc_link        = 'https://notificationx.com/docs/google-reviews-with-notificationx/';
    public $types           = 'popup';
    public $module          = 'modules_popup';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
    }

    public function init_extension()
    {
        $this->title = __('Popup', 'notificationx');
        $this->module_title = __('Popup', 'notificationx');
    }
    
     public function init_fields() {
        parent::init_fields();
        add_filter('nx_customize_fields', [$this, 'customize_fields']);
    }

     /**
     * This method is an implementable method for All Extension coming forward.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function customize_fields($fields) {
        $fields['queue_management'] = Rules::is('source', $this->id, true, $fields['queue_management']);
        $_fields             = &$fields["appearance"]['fields'];
        $conversion_position = &$_fields['position']['options'];
        $conversion_position['bottom_left']  = Rules::is('source', $this->id, true, $conversion_position['bottom_left']);
        $conversion_position['bottom_right'] = Rules::is('source', $this->id, true, $conversion_position['bottom_right']);

        $conversion_position['center'] = [
            'label' => __('Center', 'notificationx'),
            'value' => 'center',
            'rules' => Rules::is('source', $this->id),
        ];

        $_fields['sticky_bar'] = [
            'label'       => __("Sticky Bar?", 'notificationx'),
            'name'        => "sticky_bar",
            'type'        => "checkbox",
            'default'     => 0,
            'priority'    => 60,
            'description' => __('If checked, this will fixed Notification Bar at top or bottom.', 'notificationx'),
            'rules'       => Rules::is('source', $this->id),
        ];

        return $fields;
    }

}
