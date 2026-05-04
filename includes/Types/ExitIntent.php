<?php
/**
 * Exit Intent Popup Notification Type
 *
 * @package NotificationX\Types
 */

namespace NotificationX\Types;

use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;

/**
 * Exit Intent Popup notification type.
 * @method static ExitIntent get_instance($args = null)
 */
class ExitIntent extends Types {
    use GetInstance;

    public $priority       = 17;
    public $is_pro         = false;
    public $module         = ['modules_exit_intent'];
    public $default_source = 'exit_intent_custom';
    public $default_theme  = 'exit_intent_theme-one';
    public $link_type      = 'none';

    public function __construct() {
        parent::__construct();
        $this->id = 'exit_intent';
    }

    public function init() {
        parent::init();
        $this->title           = __( 'Exit Intent Popup', 'notificationx' );
        $this->dashboard_title = __( 'Exit Intent Popup', 'notificationx' );

        $this->themes = [
            'theme-one' => [
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/themes/theme-blank.jpg',
                'image_shape' => 'circle',
                'template'    => [
                    'first_param'         => 'tag_name',
                    'custom_first_param'  => __( 'Someone', 'notificationx' ),
                    'second_param'        => __( 'is about to leave', 'notificationx' ),
                    'third_param'         => 'tag_title',
                    'custom_third_param'  => __( 'Check this out', 'notificationx' ),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __( 'Just now', 'notificationx' ),
                ],
            ],
        ];

        $this->templates = [
            'exit_intent_template_default' => [
                'first_param'  => GlobalFields::get_instance()->common_name_fields(),
                'third_param'  => [
                    'tag_title' => __( 'Item Title', 'notificationx' ),
                ],
                'fourth_param' => [
                    'tag_time' => __( 'Time', 'notificationx' ),
                ],
                '_themes' => [
                    'exit_intent_theme-one',
                ],
            ],
        ];
    }
}
