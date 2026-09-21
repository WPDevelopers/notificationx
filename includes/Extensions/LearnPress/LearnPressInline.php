<?php

/**
 * LearnPress Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\LearnPress;

/**
 * LearnPress Extension
 */
class LearnPressInline extends LearnPress {
    protected static $instance = null;

    public $priority        = 22;
    public $id              = 'learnpress_inline';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/_learnpress.png';
    public $doc_link        = 'https://notificationx.com/docs/tutor-lms/';
    public $types           = 'inline';
    public $module_priority = 20;
    public $function        = 'LP';
    public $is_pro          = true;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        add_filter( 'nx_show_on_exclude', array( $this, 'show_on_exclude' ), 10, 4 );
    }

    public function init_extension()
    {
        $this->themes = [
            'conv-theme-seven' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/tutor-inline.png',
                'image_shape' => 'rounded',
                'inline_location' => ['learn-press/after-course-buttons'],
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
                'inline_location' => ['learn-press/list-courses/layout/item/section/bottom'],
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
            'learnpress_inline_template_sales_count' => [
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
                    'learnpress_inline_conv-theme-seven',
                    'learnpress_inline_conv-theme-eight',
                ]
            ],
        ];
    }

    /**
     * Keep this source's notifications out of the popup loop in
     * FrontEnd::get_notifications_ids(). They render inline at the hooks
     * chosen in `inline_location`, never as a floating popup.
     *
     * `inline_location` is not read here on purpose: it can be saved as ''
     * (MCP or Quick Builder create without the field), and passing that to
     * array_diff() is a TypeError on PHP 8 that white-screens every page.
     *
     * @param bool  $exclude  Whether an earlier callback already excluded it.
     * @param array $settings Notification settings.
     * @return bool
     */
    public function show_on_exclude( $exclude, $settings ) {
        if ( isset( $settings['type'], $settings['source'] ) && 'inline' === $settings['type'] && $this->id === $settings['source'] ) {
            return true;
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
