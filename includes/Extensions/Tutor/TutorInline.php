<?php

/**
 * Tutor Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Tutor;

/**
 * Tutor Extension
 */
class TutorInline extends Tutor {
    protected static $instance = null;

    public $priority        = 15;
    public $id              = 'tutor_inline';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/tutor.png';
    public $doc_link        = 'https://notificationx.com/docs/tutor-lms/';
    public $types           = 'inline';
    public $module_priority = 7;
    public $function        = 'tutor_lms';
    public $is_pro          = true;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        $this->themes = [
            'conv-theme-seven' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/elearning/elearning-theme-7.png',
                'image_shape' => 'rounded',
                'inline_location' => ['tutor_course/loop/after_title'],
                'template'    => [
                    'first_param'         => 'tag_sales_count',
                    'second_param'        => __('people enrolled', 'notificationx-pro'),
                    'third_param'         => 'tag_course_title',
                    'fourth_param'        => 'tag_7days',
                    'custom_fourth_param' => __('in last {{day:7}}', 'notificationx'),
                ],
            ),
        ];
        $this->templates = [
            'tutor_inline_template_sales_count' => [
                'first_param'  => [
                    'tag_sales_count' => __( 'Sales Count', 'notificationx' ),
                ],
                'third_param' => [
                    'tag_course_title' => __('Course Title', 'notificationx'),
                ],
                'fourth_param' => [
                    'tag_1day'   => __( 'In last 1 day', 'notificationx' ),
                    'tag_7days'  => __( 'In last 7 days', 'notificationx' ),
                    'tag_30days' => __( 'In last 30 days', 'notificationx' ),
                ],
                '_themes' => [
                    'tutor_inline_conv-theme-seven',
                ]
            ],
        ];
        parent::__construct();
    }

    /**
     * Get the instance of called class.
     *
     * @return ReviewX
     */
    public static function get_instance($args = null){
        if ( is_null( static::$instance ) ) {
            $class = __CLASS__;
            if(strpos($class, "NotificationX\\") === 0){
                $pro_class = str_replace("NotificationX\\", "NotificationXPro\\", $class);
                if(class_exists($pro_class)){
                    $class = $pro_class;
                }
            }

            if(!empty($args)){
                static::$instance = new $class($args);
            }
            else{
                static::$instance = new $class;
            }
        }
        return static::$instance;
    }


}
