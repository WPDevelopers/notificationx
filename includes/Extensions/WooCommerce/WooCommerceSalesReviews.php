<?php
  /**
 * WooCommerce Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\WooCommerce;

  /**
 * WooCommerce Extension Class
 * @method static WooCommerce get_instance($args = null)
 */
class WooCommerceSalesReviews extends WooReviews {
      /**
     * Instance of WooInline
     *
     * @var WooCommerceSalesReviews
     */
    protected static $instance       = null;
    public    $priority              = 10;
    public    $id                    = 'woocommerce_sales_reviews';
    public    $img                   = '';
    public    $doc_link              = 'https://notificationx.com/docs/woocommerce-reviews-notificationx/';
    public    $types                 = 'woocommerce_sales';
    public    $module                = 'modules_woocommerce';
    public    $module_priority       = 3;
    public    $class                 = '\WooCommerce';
    public    $default_theme         = 'woocommerce_sales_reviews_total-rated';
    public    $exclude_custom_themes = true;

      /**
     * Get the instance of called class.
     *
     * @return WooCommerce
    */
    public static function get_instance($args = null){
        if ( is_null( static::$instance ) || ! static::$instance instanceof self ) {
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
      /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
    }

    public function init_extension()
    {
        $this->title        = __('Reviews', 'notificationx');
        $this->module_title = __('Reviews', 'notificationx');
        $this->themes       = [
            'total-rated'     => [
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/total-rated.png',
                'image_shape' => 'square',
                'template'    => [
                    'first_param'         => 'tag_rated',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('people rated', 'notificationx'),
                    'third_param'         => 'tag_product_title',
                    'fourth_param'        => 'tag_rating',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ]
            ],
            'reviewed'     => [
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/reviewed.png',
                'image_shape' => 'circle',
                'template'    => [
                    'first_param'         => 'tag_username',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('just reviewed', 'notificationx'),
                    'third_param'         => 'tag_product_title',
                    'fourth_param'        => 'tag_rating',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ]
            ],
            'review_saying' => [
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/saying-review.png',
                'image_shape' => 'circle',
                'template'    => [
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
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/review-with-comment.jpg',
                'image_shape' => 'rounded',
                'template'    => [
                    'first_param'         => 'tag_username',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('just reviewed', 'notificationx'),
                    'third_param'         => 'tag_plugin_review',
                    'fourth_param'        => 'tag_rating',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ]
            ],
            'review-comment-2' => [
                'source'   => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/review-with-comment-2.jpg',
                'template' => [
                    'first_param'         => 'tag_username',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('just reviewed', 'notificationx'),
                    'third_param'         => 'tag_plugin_review',
                    'fourth_param'        => 'tag_rating',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ]
            ],
            'review-comment-3' => [
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/review-with-comment-3.jpg',
                'image_shape' => 'circle',
                'template'    => [
                    'first_param'         => 'tag_username',
                    'custom_first_param'  => __('Someone', 'notificationx'),
                    'second_param'        => __('just reviewed', 'notificationx'),
                    'third_param'         => 'tag_plugin_review',
                    'fourth_param'        => 'tag_time',
                    'custom_fourth_param' => __('Some time ago', 'notificationx'),
                ]
            ],
        ];

        $this->templates = [
            'wp_reviews_template_new'  => [
                'first_param' => [
                    'tag_username' => __('Username', 'notificationx'),
                    'tag_rated'    => __('Rated', 'notificationx'),
                ],
                'third_param' => [
                    'tag_product_title'   => __('Product Title', 'notificationx'),
                    'tag_plugin_review'   => __('Review', 'notificationx'),
                    'tag_anonymous_title' => __('Anonymous Title', 'notificationx'),
                ],
                'fourth_param' => [
                    'tag_rating' => __('Rating', 'notificationx'),
                    'tag_time'   => __('Definite Time', 'notificationx'),
                ],
                '_themes' => [
                    'woocommerce_sales_reviews_total-rated',
                    'woocommerce_sales_reviews_reviewed',
                    'woocommerce_sales_reviews_review-comment',
                    'woocommerce_sales_reviews_review-comment-2',
                    'woocommerce_sales_reviews_review-comment-3',
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
                    'woocommerce_sales_reviews_review_saying',
                ],
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
                'is_pro'    => true,
            ],
            'res-theme-two'     => [
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_reviews/nx-review-res-theme-2.png',
                'image_shape' => 'square',
                'template'    => [
                    'res_first_param'  => 'tag_username',
                    'res_second_param' => __('just reviewed', 'notificationx'),
                    'res_third_param'  => 'tag_plugin_name',
                ],
                'is_pro'    => true,
            ],
            'res-theme-three'     => [
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_reviews/nx-review-res-theme-3.png',
                'image_shape' => 'square',
                'template'    => [
                    'res_first_param'  => 'tag_username',
                    'res_second_param' => __('just reviewed', 'notificationx'),
                    'res_third_param'  => 'tag_plugin_name',
                ],
                'is_pro'    => true,
            ],
            'rating-res-theme-four'     => [
                'source'      => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/res_reviews/nx-review-res-theme-4.png',
                'image_shape' => 'square',
                'template'    => [
                    'res_first_param'  => 'tag_username',
                    'res_second_param' => __('just reviewed', 'notificationx'),
                    'res_third_param'  => 'tag_rating',
                ],
                'is_pro'    => true,
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
    }

    public function doc() {
        return sprintf(__('<p>Make sure that you have <a target="_blank" href="%1$s">WooCommerce installed & activated</a> to use this campaign. For further assistance, check out our step by step <a target="_blank" href="%2$s">documentation</a>.</p>
		<p>🎦 Watch <a target="_blank" href="%3$s">video tutorial</a> to learn quickly</p>
		<p><strong>Recommended Blog:</strong></p>
		<p>🚀 How to <a target="_blank" href="%4$s">boost WooCommerce Sales</a> Using NotificationX</p>', 'notificationx'),
        'https://wordpress.org/plugins/woocommerce/',
        'https://notificationx.com/docs/woocommerce-reviews-notificationx/',
        'https://www.youtube.com/watch?v=bHuaOs9JWvI',
        'https://wpdeveloper.com/ecommerce-sales-social-proof/'
        );
    }

}
