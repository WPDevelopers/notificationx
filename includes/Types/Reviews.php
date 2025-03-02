<?php

/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Types;

use NotificationX\Core\Rules;
use NotificationX\Types\Traits\Reviews as TraitsReviews;
use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;
use NotificationX\Modules;

/**
 * Extension Abstract for all Extension.
 * @method static Reviews get_instance($args = null)
 */
class Reviews extends Types {
    /**
     * Instance of Admin
     *
     * @var Admin
     */
    use GetInstance;
    use TraitsReviews;

    public $priority = 20;
    public $themes = [];
    public $res_themes = [];
    public $module = [
        'modules_wordpress',
        'modules_woocommerce',
        'modules_reviewx',
        'modules_zapier',
        'modules_freemius',
    ];
    public $default_source = 'wp_reviews';
    public $default_theme  = 'reviews_total-rated';
    public $link_type      = 'review_page';


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        add_filter('nx_link_types', [$this, 'link_types']);
        parent::__construct();
        $this->id    = 'reviews';
    }

    public function init() {
        parent::init();
        $this->title = __('Reviews', 'notificationx');
        $this->themes = [
            'total-rated'     => [
                'source'                => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/total-rated.png',
                'image_shape' => 'square',
                'template'  => [
                    'first_param'         => 'tag_rated',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('people rated', 'notificationx'),
                    'third_param'         => 'tag_plugin_name',
                    'fourth_param'        => 'tag_rating',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ]
            ],
            'reviewed'     => [
                'source'                => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/reviewed.png',
                'image_shape' => 'circle',
                'template'  => [
                    'first_param'         =>  'tag_username',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('just reviewed', 'notificationx'),
                    'third_param'         => 'tag_plugin_name',
                    'fourth_param'        => 'tag_rating',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ]
            ],
            'review_saying' => [
                'source'               => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/saying-review.png',
                'image_shape' => 'circle',
                'template'  => [
                    'first_param'         => 'tag_username',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('saying', 'notificationx'),
                    'third_param'         => 'tag_title',
                    'custom_third_param'  => __('Excellent', 'notificationx'),
                    'review_fourth_param' => __('about', 'notificationx'),
                    'fifth_param'         => 'tag_plugin_name',
                    'sixth_param'         => 'tag_custom',
                    'custom_sixth_param'  => __('Try it now', 'notificationx'),
                ]
            ],
            'review-comment' => [
                'source'                => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/review-with-comment.jpg',
                'image_shape' => 'rounded',
                'template'  => [
                    'first_param'         => 'tag_username',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('just reviewed', 'notificationx'),
                    'third_param'         => 'tag_plugin_review',
                    'fourth_param'        => 'tag_rating',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ]
            ],
            'review-comment-2' => [
                'source'                => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/review-with-comment-2.jpg',
                'template'  => [
                    'first_param'         => 'tag_username',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('just reviewed', 'notificationx'),
                    'third_param'         => 'tag_plugin_review',
                    'fourth_param'        => 'tag_rating',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ]
            ],
            'review-comment-3' => [
                'source'                => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/review-with-comment-3.jpg',
                'image_shape' => 'circle',
                'template'  => [
                    'first_param'         => 'tag_username',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('just reviewed', 'notificationx'),
                    'third_param'         => 'tag_plugin_review',
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ]
            ],
        ];
        $this->res_themes = [
            'res-theme-one'     => [
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_reviews/nx-review-res-theme-1.png',
                'image_shape' => 'square',
                'template'    => [
                    'res_first_param'  => 'tag_rated',
                    'res_second_param' => __('people rated', 'notificationx'),
                    'res_third_param'  => 'tag_plugin_name',
                ],
                'is_pro' => true,
            ],
            'res-theme-two'     => [
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_reviews/nx-review-res-theme-2.png',
                'image_shape' => 'square',
                'template'    => [
                    'res_first_param'  => 'tag_username',
                    'res_second_param' => __('just reviewed', 'notificationx'),
                    'res_third_param'  => 'tag_plugin_name',
                ],
                'is_pro' => true,
            ],
            'res-theme-three'     => [
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_reviews/nx-review-res-theme-3.png',
                'image_shape' => 'square',
                'template'    => [
                    'res_first_param'  => 'tag_username',
                    'res_second_param' => __('just reviewed', 'notificationx'),
                    'res_third_param'  => 'tag_plugin_name',
                ],
                'is_pro' => true,
            ],
            'rating-res-theme-four'     => [
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_reviews/nx-review-res-theme-4.png',
                'image_shape' => 'square',
                'template'    => [
                    'res_first_param'  => 'tag_username',
                    'res_second_param' => __('just reviewed', 'notificationx'),
                    'res_third_param'  => 'tag_rating',
                ],
                'is_pro' => true,
            ],
            'rating-res-theme-five'     => [
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_reviews/nx-review-res-theme-5.png',
                'image_shape' => 'square',
                'template'    => [
                    'res_first_param'  => 'tag_username',
                    'res_second_param' => __('just reviewed', 'notificationx'),
                    'res_third_param'  => 'tag_rating',
                ],
                'is_pro' => true,
            ],
            'rating-res-theme-six'     => [
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_reviews/nx-review-res-theme-6.png',
                'image_shape' => 'square',
                'template'    => [
                    'res_first_param'  => 'tag_username',
                    'res_second_param' => __('just reviewed', 'notificationx'),
                    'res_third_param'  => 'tag_rating',
                ],
                'is_pro' => true,
            ],
        ];

        $this->templates = [
            'wp_reviews_template_new'  => [
                'first_param' => [
                    'tag_username' => __('Username', 'notificationx'),
                    'tag_rated'    => __('Rated', 'notificationx'),
                ],
                'third_param' => [
                    'tag_plugin_name'     => __('Plugin Name', 'notificationx'),
                    'tag_plugin_review'   => __('Review', 'notificationx'),
                    'tag_anonymous_title' => __('Anonymous Title', 'notificationx'),
                ],
                'fourth_param' => [
                    'tag_rating'   => __('Rating', 'notificationx'),
                    'tag_time'     => __('Definite Time', 'notificationx'),
                ],
                '_themes' => [
                    'reviews_total-rated',
                    'reviews_reviewed',
                    'reviews_review-comment',
                    'reviews_review-comment-2',
                    'reviews_review-comment-3',
                ],
            ],
            'review_saying_template_new' => [
                'first_param' => [
                    'tag_username' => __('Username', 'notificationx'),
                ],
                'third_param' => [
                    'tag_title'           => __('Review Title', 'notificationx'),
                    'tag_anonymous_title' => __('Anonymous Title', 'notificationx'),
                ],
                'fifth_param' => [
                    'tag_plugin_name' => __('Plugin Name', 'notificationx'),
                ],
                'sixth_param' => [
                    // @todo maybe add some predefined texts.
                ],
                '_themes' => [
                    'reviews_review_saying',
                ],
            ],
        ];
        add_filter("nx_filtered_entry_{$this->id}", array($this, 'conversion_data'), 11, 2);
    }

    /**
     * Hooked to nx_before_metabox_load action.
     *
     * @return void
     */
    public function init_fields() {
        parent::init_fields();
        add_filter('nx_notification_template', [$this, 'review_templates'], 7);
        add_filter('nx_content_trim_length_dependency', [$this, 'content_trim_length_dependency']);
    }

}
