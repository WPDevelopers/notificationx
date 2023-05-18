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
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/tutor-inline.png',
                'image_shape' => 'rounded',
                'inline_location' => ['tutor/course/single/entry-box/free'],
                'template'    => [
                    'first_param'         => 'tag_sales_count',
                    'second_param'        => __('people enrolled', 'notificationx'),
                    'third_param'         => 'tag_custom',
                    'custom_third_param'  => ' ',
                    'fourth_param'        => 'tag_7days',
                    'custom_fourth_param' => __('in last {{day:7}}', 'notificationx'),
                ],
            ),
            'conv-theme-eight' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/tutor-inline-2.png',
                'image_shape' => 'rounded',
                'inline_location' => ['tutor_course/loop/after_title'],
                'template'    => [
                    'first_param'         => 'tag_sales_count',
                    'second_param'        => __('people enrolled', 'notificationx'),
                    'third_param'         => 'tag_custom',
                    'custom_third_param'  => ' ',
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
                    'tutor_inline_conv-theme-eight',
                ]
            ],
        ];
        parent::__construct();
        add_filter( 'nx_show_on_exclude', array( $this, 'show_on_exclude' ), 10, 4 );
    }

    /**
     * @todo Something
     *
     * @param [type] $exclude
     * @param [type] $settings
     * @return void
     */
    public function show_on_exclude( $exclude, $settings ) {
        if ( 'inline' === $settings['type'] && $settings['source'] === $this->id ) {
            $edd_location = $settings['inline_location'];
            $hooks        = [ 'tutor_course/loop/after_title', 'tutor/course/single/entry-box/free' ];
            $diff         = array_diff( $hooks, $edd_location );
            if ( count( $diff ) <= count( $hooks ) ) {
                return true;
            }
        }
        return $exclude;
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
