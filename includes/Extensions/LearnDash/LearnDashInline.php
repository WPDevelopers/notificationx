<?php

/**
 * EDD Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\LearnDash;

/**
 * EDD Extension
 */
class LearnDashInline extends LearnDash {
    protected static $instance = null;

    public $priority        = 20;
    public $id              = 'learndash_inline';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/learndash.png';
    public $doc_link        = 'https://notificationx.com/docs/how-to-display-learndash-course-enrollment-alert-using-notificationx/';
    public $types           = 'inline';
    public $module          = 'modules_learndash';
    public $module_priority = 18;
    public $is_pro          = true;
    public $version         = '1.2.0';
    public $class           = '\LDLMS_Post_Types';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        $this->themes = [
            'conv-theme-seven' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/learndash-inline.png',
                'image_shape' => 'rounded',
                'inline_location' => ['learndash_content'],
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
            'learndash_inline_template_sales_count' => [
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
                    'learndash_inline_conv-theme-seven',
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
            $hooks        = [ 'tutor_course/loop/after_title' ];
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
