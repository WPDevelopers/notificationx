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
use NotificationX\NotificationX;

/**
 * Extension Abstract for all Extension.
 * @method static WooCommerce get_instance($args = null)
 */
class WooCommerceSales extends Types {
    /**
     * Instance of Admin
     *
     * @var Admin
     */
	use GetInstance;

    // colored_themes
    public $priority = 5;
    public $themes = [];
    public $module = [
        'modules_woocommerce',
        'modules_woocommerce_sales_reviews',
        'modules_woocommerce_sales_inline',
    ];

    public $woocommerce_count = array('woocommerce_conv-theme-seven', 'woocommerce_conv-theme-eight', 'woocommerce_conv-theme-nine');
    public $map_dependency = [];


    public $default_source    = 'woocommerce_sales';
    public $default_theme = 'woocommerce_theme-one';
    public $link_type = 'product_page';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->id = 'woocommerce_sales';
        $this->title = __('WooCommerce', 'notificationx');

        $is_pro = ! NotificationX::is_pro();
        // nx_colored_themes
        $common_fields = [
            'first_param'         => 'tag_name',
            'custom_first_param'  => __('Someone' , 'notificationx'),
            'second_param'        => __('just purchased', 'notificationx'),
            'third_param'         => 'tag_product_title',
            'custom_third_param'  => __('Anonymous Product', 'notificationx'),
            'fourth_param'        => 'tag_time',
            'custom_fourth_param' => __( 'Some time ago', 'notificationx' ),
        ];
        $this->themes = [
            'theme-one'   => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/nx-conv-theme-2.jpg',
                'image_shape' => 'square',
                'template'  => $common_fields,
            ],
            'theme-two'   => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/nx-conv-theme-1.jpg',
                'image_shape' => 'square',
                'template'  => $common_fields,
            ],
            'theme-three' => [
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/nx-conv-theme-3.jpg',
                'image_shape' => 'square',
                'template'  => $common_fields,
            ],
            'theme-four' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/nx-conv-theme-four.png',
                'image_shape' => 'circle',
                'template'  => $common_fields,
            ),
            'theme-five' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/nx-conv-theme-five.png',
                'image_shape' => 'circle',
                'template'  => $common_fields,
            ),
            // @todo pro map theme
            'conv-theme-six' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/nx-conv-theme-6.jpg',
                'image_shape' => 'circle',
            ),
            // @todo pro map theme
            'maps_theme' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/maps-theme.png',
                'image_shape' => 'square',
                'show_notification_image' => 'maps_image',
            ),
            'conv-theme-ten' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/nx-conv-theme-4.png',
                'image_shape' => 'rounded',
                'defaults'     => [
                    'link_button'   => true,
                ],
                'template'  => $common_fields,
            ),
            'conv-theme-eleven' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/nx-conv-theme-5.png',
                'image_shape' => 'rounded',
                'defaults'     => [
                    'link_button'   => true,
                ],
                'template'  => $common_fields,
            ),
            'conv-theme-seven' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/nx-conv-theme-7.png',
                'image_shape' => 'rounded',
            ),
            'conv-theme-eight' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/nx-conv-theme-8.png',
                'image_shape' => 'circle',

            ),
            'conv-theme-nine' => array(
                'is_pro' => true,
                'source' => NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/nx-conv-theme-9.png',
                'image_shape' => 'rounded',
            ),
           
        ];
        $this->templates = [
            'woo_template_new' => [
                'first_param' => GlobalFields::get_instance()->common_name_fields(),
                'third_param' => [
                    'tag_product_title' => __('Product Title', 'notificationx'),
                ],
                'fourth_param' => [
                    'tag_time' => __('Definite Time', 'notificationx'),
                ],
                '_themes' => [
                    'woocommerce_sales_theme-one',
                    'woocommerce_sales_theme-two',
                    'woocommerce_sales_theme-three',
                    'woocommerce_sales_theme-four',
                    'woocommerce_sales_theme-five',
                    'woocommerce_sales_conv-theme-ten',
                    'woocommerce_sales_conv-theme-eleven',
                ]
            ],
            'woo_template_sales_count' => [
                'first_param' => GlobalFields::get_instance()->common_name_fields(),
                'third_param' => [
                    'tag_product_title' => __('Product Title', 'notificationx'),
                ],
                'fourth_param' => [
                    // 'tag_time' => __('Definite Time', 'notificationx'),
                ],
                '_themes' => [
                    'woocommerce_sales_conv-theme-six',
                    'woocommerce_sales_conv-theme-seven',
                    'woocommerce_sales_conv-theme-eight',
                    'woocommerce_sales_conv-theme-nine',
                ]
            ],
        ];
        parent::__construct();
    }

    /**
     * Hooked to nx_before_metabox_load action.
     *
     * @return void
     */
    public function init_fields() {
        parent::init_fields();
        add_filter('nx_content_fields', [$this, 'content_fields'], 9);
        add_filter('nx_notification_template', [$this, 'review_templates'], 7);
        add_filter('nx_content_trim_length_dependency', [$this, 'content_trim_length_dependency']);
    }

    /**
     * Adding fields in the metabox.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function content_fields($fields) {
        $content_fields = &$fields['content']['fields'];
        $content_fields['combine_multiorder'] = [
            'label'       => __('Combine Multi Order', 'notificationx'),
            'name'        => 'combine_multiorder',
            'type'        => 'checkbox',
            'priority'    => 100,
            'default'     => true,
            'description' => __('Combine order like, 2 more products.', 'notificationx'),
            'rules' => Rules::logicalRule([
                Rules::is('type', $this->id),
                Rules::is('notification-template.first_param', 'tag_sales_count', true),
                Rules::includes('source', [ 'woocommerce_sales' ]),
            ]),
        ];
        return $fields;
    }

    public function excludes_product( $data, $settings ){
        if( empty( $settings['product_exclude_by'] ) || $settings['product_exclude_by'] === 'none' ) {
            return $data;
        }

        $product_category_list = $new_data = [];


        if( ! empty( $data ) ) {
            foreach( $data as $key => $product ) {
                $product_id = $product['product_id'];
                if( $settings['product_exclude_by'] == 'product_category' ) {
                    $product_categories = get_the_terms( $product_id, 'product_cat' );
                    if( ! is_wp_error( $product_categories ) ) {
                        foreach( $product_categories as $category ) {
                            $product_category_list[] = $category->slug;
                        }
                    }

                    $product_category_count = count( $product_category_list );
                    $array_diff = array_diff( $product_category_list, $settings['exclude_categories'] );
                    $array_diff_count = count( $array_diff );

                    if( ! ( $array_diff_count < $product_category_count ) ) {
                        $new_data[ $key ] = $product;
                    }
                    $product_category_list = [];
                }
                if( $settings['product_exclude_by'] == 'manual_selection' ) {
                    if( ! in_array( $product_id, $settings['exclude_products'] ) ) {
                        $new_data[ $key ] = $product;
                    }
                }
            }
        }

        return $new_data;

    }

    public function show_purchaseof( $data, $settings ){
        if( empty( $settings['product_control'] ) || $settings['product_control'] === 'none' ) {
            return $data;
        }

        $product_category_list = $new_data = [];

        if( ! empty( $data ) ) {
            foreach( $data as $key => $product ) {
                $product_id = $product['product_id'];
                if( $settings['product_control'] == 'product_category' ) {
                    $product_categories = get_the_terms( $product_id, 'product_cat' );
                    if( ! is_wp_error( $product_categories ) ) {
                        foreach( $product_categories as $category ) {
                            $product_category_list[] = $category->slug;
                        }
                    }

                    $product_category_count = count( $product_category_list );
                    $array_diff = array_diff( $settings['category_list'], $product_category_list );
                    $array_diff_count = count( $array_diff );

                    $cute_logic = ( count( $settings['category_list'] ) - ( $product_category_count +  $array_diff_count) );

                    if( ! $cute_logic ) {
                        $new_data[ $key ] = $product;
                    }
                    $product_category_list = [];
                }
                if( $settings['product_control'] == 'manual_selection' ) {
                    if( in_array( $product_id, $settings['product_list'] ) ) {
                        $new_data[ $key ] = $product;
                    }
                }
            }
        }
        return $new_data;
    }

    /**
     * Adds option to Link Type field in Content tab.
     *
     * @param array $options
     * @return array
     */
    public function link_types($options) {
        $_options = GlobalFields::get_instance()->normalize_fields([
            'review_page' => __('Product Page', 'notificationx'),
        ], 'type', $this->id);

        return array_merge($options, $_options);
    }

    /**
     * This method is an implementable method for All Extension coming forward.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function review_templates($template) {
        $template["review_fourth_param"] = [
            // 'label'     => __("Review Fourth Parameter", 'notificationx'),
            'name'      => "review_fourth_param",                            // changed name from "conversion_size"
            'type'      => "text",
            'priority'  => 27,
            'default'   => __('About', 'notificationx'),
            'rules' => Rules::includes('themes', 'reviews_review_saying'),
        ];
        return $template;
    }

    /**
     * This method is an implementable method for All Extension coming forward.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function content_trim_length_dependency($dependency) {
        $dependency[] = 'woocommerce_sales_reviews_review-comment';
        $dependency[] = 'woocommerce_sales_reviews_review-comment-2';
        $dependency[] = 'woocommerce_sales_reviews_review-comment-3';
        $dependency[] = 'woocommerce_sales_reviews_review-comment';
        $dependency[] = 'woocommerce_sales_reviews_review-comment-2';
        $dependency[] = 'woocommerce_sales_reviews_review-comment-3';
        $dependency[] = 'woocommerce_sales_reviews_review-comment';
        $dependency[] = 'woocommerce_sales_reviews_review-comment-2';
        $dependency[] = 'woocommerce_sales_reviews_review-comment-3';
        return $dependency;
    }

    // @todo frontend
    public function conversion_data($saved_data, $settings) {
        if(empty($saved_data['content']) && !empty($saved_data['plugin_review'])){
            $saved_data['content'] = $saved_data['plugin_review'];
        }
        if (!empty($saved_data['content'])) {
            $trim_length = 100;
            if ($settings['themes'] == 'woocommerce_sales_reviews_review-comment-3' || $settings['themes'] == 'woocommerce_sales_reviews_review-comment-3') {
                $trim_length = 80;
            }
            $nx_trimmed_length = apply_filters('nx_text_trim_length', $trim_length, $settings);
            $review_content = $saved_data['content'];
            if (strlen($review_content) > $nx_trimmed_length) {
                $review_content = substr($review_content, 0, $nx_trimmed_length) . '...';
            }
            if ($settings['themes'] == 'woocommerce_sales_reviews_review-comment-2') { // || $settings['theme'] == 'comments_theme-six-free'
                $review_content = '" ' . $review_content . ' "';
            }
            $saved_data['plugin_review'] = $review_content;
        }
        if(empty($saved_data['title']))
            $saved_data['title'] = isset($saved_data['post_title']) ? $saved_data['post_title'] : '';

        return $saved_data;
    }
    public function preview_entry($entry, $settings){
        $entry = array_merge($entry, [
            "title"             => _x("NotificationX", 'nx_preview', 'notificationx'),
        ]);
        return $entry;
    }

}
