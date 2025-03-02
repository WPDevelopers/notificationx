<?php
/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Types;

use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;
use NotificationX\Modules;
use NotificationX\NotificationX;

/**
 * Extension Abstract for all Extension.
 * @method static ELearning get_instance($args = null)
 */
class ELearning extends Types {
    /**
     * Instance of Admin
     *
     * @var Admin
     */
    use GetInstance;

    public $priority = 10;
    public $themes = [];
    public $module = [
        'modules_tutor',
        'modules_learndash',
    ];
    public $default_source    = 'tutor';
    public $default_theme = 'elearning_theme-one';
    public $link_type = 'course_page';


    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
        $this->id = 'elearning';
        add_filter("nx_filtered_entry_{$this->id}", array($this, 'conversion_data'), 10, 2);
    }

    public function init()
    {
        parent::init();
        $this->title = __('eLearning', 'notificationx');
        $this->themes = [
            'theme-one'   => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/elearning/elearning-theme-1.jpg',
                'image_shape' => 'circle',
                'template'  => [
                    'first_param'         => 'tag_name',
                    'custom_first_param'  => __('Someone' , 'notificationx'),
                    'second_param'        => __('just enrolled', 'notificationx'),
                    'third_param'         => 'tag_course_title',
                    'custom_third_param'  => __('Anonymous Course' , 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __( 'Some time ago', 'notificationx' ),
                ],
            ],
            'theme-two'   => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/elearning/elearning-theme-2.jpg',
                'image_shape' => 'circle',
                'template'  => [
                    'first_param'         => 'tag_name',
                    'custom_first_param'  => __('Someone' , 'notificationx'),
                    'second_param'        => __('recently enrolled' , 'notificationx'),
                    'third_param'         => 'tag_course_title',
                    'custom_third_param'  => __('Anonymous Course' , 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __( 'Some time ago', 'notificationx' ),
                ],
            ],
            'theme-three' => [
                'source'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/elearning/elearning-theme-3.jpg',
                'image_shape' => 'square',
                'template' => [
                    'first_param'         => 'tag_name',
                    'custom_first_param'  => __('Someone' , 'notificationx'),
                    'second_param'        => __('recently enrolled' , 'notificationx'),
                    'third_param'         => 'tag_course_title',
                    'custom_third_param'  => __('Anonymous Course' , 'notificationx'),
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __( 'Some time ago', 'notificationx' ),
                ],
            ],
            'theme-four' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/elearning/elearning-theme-4.png',
                'image_shape' => 'circle',
            ),
            'theme-five' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/elearning/elearning-theme-5.png',
                'image_shape' => 'circle',
            ),
            'conv-theme-six' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/elearning/elearning-theme-6.png',
                'image_shape' => 'circle',
            ),
            'conv-theme-seven' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/elearning/elearning-theme-7.png',
                'image_shape' => 'rounded',
            ),
            'conv-theme-eight' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/elearning/elearning-theme-8.png',
                'image_shape' => 'circle',
            ),
            'conv-theme-nine' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/elearning/elearning-theme-9.png',
                'image_shape' => 'circle',
            ),
            'maps_theme' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/elearning/maps-theme.png',
                'image_shape' => 'square',
                'show_notification_image' => 'maps_image',
            ),
        ];
        $this->res_themes = [
            'res-theme-one' => array(
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_elearning/elearning-res-theme-1.png',
                '_template' => 'elearning_template_new',
                'is_pro'    => true,
            ),
            'res-theme-two' => array(
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_elearning/elearning-res-theme-2.png',
                '_template' => 'elearning_template_new',
                'is_pro'    => true,
            ),
            'res-theme-three' => array(
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_elearning/elearning-res-theme-3.png',
                '_template' => 'elearning_template_new',
                'is_pro'    => true,
            ),
            'res-theme-four' => array(
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_elearning/elearning-res-theme-4.png',
                '_template' => 'elearning_template_new',
                'is_pro'    => true,
            ),
            'elearning-res-theme-five' => array(
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_elearning/elearning-res-theme-5.png',
                '_template' => 'elearning_template_new',
                'is_pro'    => true,
            ),
            'elearning-res-theme-six' => array(
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_elearning/elearning-res-theme-6.png',
                '_template' => 'maps_template_new',
                'is_pro'    => true,
            ),
            'elearning-res-theme-seven' => array(
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_elearning/elearning-res-theme-7.png',
                '_template' => 'elearning_template_sales_count',
                'is_pro'    => true,
            ),
            'elearning-res-theme-eight' => array(
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_elearning/elearning-res-theme-8.png',
                '_template' => 'elearning_template_sales_count',
                'is_pro'    => true,
            ),
            'res-theme-nine' => array(
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_elearning/elearning-res-theme-9.png',
                '_template' => 'elearning_template_sales_count',
                'is_pro'    => true,
            ),
            'elearning-res-theme-ten' => array(
                'source'    => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_elearning/elearning-res-theme-10.png',
                '_template' => 'maps_template_new',
                'is_pro'    => true,
            ),
        ];
        $this->templates = [
            'elearning_template_new' => [
                'first_param' => GlobalFields::get_instance()->common_name_fields(),
                'third_param' => [
                    'tag_course_title'       => __('Course Title', 'notificationx'),
                ],
                'fourth_param' => [
                    'tag_time'       => __('Definite Time', 'notificationx'),
                ],
                '_themes' => [
                    'elearning_theme-one',
                    'elearning_theme-two',
                    'elearning_theme-three',
                    'elearning_theme-four',
                    'elearning_theme-five',
                ]
            ],
            'elearning_template_sales_count' => [
                'first_param' => GlobalFields::get_instance()->common_name_fields(),
                'third_param' => [
                    'tag_course_title' => __('Course Title', 'notificationx'),
                ],
                'fourth_param' => [
                    // 'tag_time' => __('Definite Time', 'notificationx'),
                ],
                '_themes' => [
                    'elearning_conv-theme-seven',
                    'elearning_conv-theme-eight',
                    'elearning_conv-theme-nine',
                ]
            ],
        ];
    }


    /**
     * Hooked to nx_before_metabox_load action.
     *
     * @return void
     */
    public function init_fields() {
        parent::init_fields();
        add_filter('nx_link_types', [$this, 'link_types']);
        add_filter('nx_content_fields', [$this, 'content_fields']);
    }

    /**
     * Needed content fields
     * @return array
     */
    public function content_fields($fields) {
        $content_fields = &$fields['content']['fields'];
        $content_fields['ld_product_control'] = array(
            'name'     => 'ld_product_control',
            'label'    => __('Show Notification Of', 'notificationx'),
            'type'     => 'select',
            'priority' => 200,
            'default'  => 'none',
            'options'  => GlobalFields::get_instance()->normalize_fields(array(
                'none'      => __('All', 'notificationx'),
                'ld_course' => __('By Course', 'notificationx'),
            )),
            'rules'       => Rules::is('type', $this->id),
        );

        $content_fields['ld_course_list'] = array(
            'name'     => 'ld_course_list',
            'label'    => __('Select Course', 'notificationx'),
            'type'     => 'select-async',
            'multiple' => true,
            'priority' => 201,
            'options'  => apply_filters('nx_elearning_course_list', [
                [
                    'label'    => "Type for more result...",
                    'value'    => null,
                    'disabled' => true,
                ],
            ]),
            'rules'       => Rules::logicalRule([
                Rules::is('type', $this->id),
                Rules::is('ld_product_control', 'ld_course'),
            ]),
            'ajax'   => [
                'api'  => "/notificationx/v1/get-data",
                'data' => [
                    'type'   => "@type",
                    'source' => "@source",
                    'field'  => "ld_course_list",
                ],
            ],
        );

        return $fields;
    }


    /**
     * Adds option to Link Type field in Content tab.
     *
     * @param array $options
     * @return array
     */
    public function link_types($options) {
        $_options = GlobalFields::get_instance()->normalize_fields([
            'course_page' => __('Course Page', 'notificationx'),
        ], 'type', $this->id);

        return array_merge($options, $_options);
    }

    public function conversion_data($saved_data, $settings) {
        if ( ! empty( $saved_data['course_title'] ) ) {
            $saved_data['course_title'] = strip_tags( html_entity_decode( $saved_data['course_title'] ) );
        }
        return $saved_data;
    }

    public function preview_entry($entry, $settings){
        $entry = array_merge($entry, [
            "title"             => "PHP Beginners – Become a PHP Master",

        ]);
        return $entry;
    }
}
