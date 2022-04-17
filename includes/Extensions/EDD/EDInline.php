<?php

/**
 * EDD Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\EDD;

/**
 * EDD Extension
 */
class EDDInline extends EDD {
    protected static $instance = null;

    public $priority        = 10;
    public $id              = 'edd_inline';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/edd.png';
    public $doc_link        = 'https://notificationx.com/docs/notificationx-easy-digital-downloads/';
    public $types           = 'inline';
    public $module          = 'modules_edd';
    public $module_priority = 5;
    public $class           = 'Easy_Digital_Downloads';
    public $is_pro          = true;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        $this->themes = [
            'conv-theme-seven' => array(
                'is_pro'      => true,
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/woo-inline.jpg',
                'image_shape' => 'rounded',
                'inline_location' => ['edd_single'],
                'template'    => [
                    'first_param'         => 'tag_sales_count',
                    'custom_first_param'  => __( 'Someone', 'notificationx' ),
                    'second_param'        => __( 'people purchased', 'notificationx' ),
                    'third_param'         => 'tag_custom',
                    'custom_third_param'  => ' ',
                    'fourth_param'        => 'tag_7days',
                    'custom_fourth_param' => __( 'in last {{day:7}}', 'notificationx' ),
                ],
            ),
        ];
        $this->templates = [
            'woo_template_sales_count' => [
                'first_param'  => [
                    'tag_sales_count' => __( 'Sales Count', 'notificationx' ),
                ],
                'third_param'  => [
                    'tag_product_title' => __( 'Product Title', 'notificationx' ),
                ],
                'fourth_param' => [
                    'tag_1day'   => __( 'In last 1 day', 'notificationx' ),
                    'tag_7days'  => __( 'In last 7 days', 'notificationx' ),
                    'tag_30days' => __( 'In last 30 days', 'notificationx' ),
                ],
                '_themes'      => [
                    "{$this->id}_conv-theme-seven",
                ],
            ],
        ];
        add_filter( 'nx_show_on_exclude', array( $this, 'show_on_exclude' ), 10, 4 );
        parent::__construct();
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
            $hooks        = [ 'edd_archive', 'edd_single' ];
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
