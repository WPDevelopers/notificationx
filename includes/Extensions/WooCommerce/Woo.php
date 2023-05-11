<?php
/**
 * WooCommerce Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\WooCommerce;

use NotificationX\Core\Helper;
use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;

trait Woo {
    public $post_type = 'product';


    public function _init_fields(){
        add_filter('nx_conversion_product_list', [$this, 'products']);
        add_filter('nx_conversion_category_list', [$this, 'categories']);
    }


    public function categories($options){

        $product_categories = get_terms(array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ));

        $category_list = [];

        if( ! is_wp_error( $product_categories ) ) {
            foreach( $product_categories as $product ) {
                $category_list[ $product->slug ] = $product->name;
            }
        }

        $options = GlobalFields::get_instance()->normalize_fields($category_list, 'source', $this->id, $options);
        return $options;
    }

    public function products($options){
        $product_list = Helper::get_post_titles_by_search($this->post_type);
        $options      = GlobalFields::get_instance()->normalize_fields($product_list, 'source', $this->id, $options);
        return $options;
    }

    /**
     * Lists available tags in the selected form.
     *
     * @param array $args An array of arguments, including inputValue.
     * @return array An indexed array of product IDs and titles.
     */
    public function restResponse($args) {
        // Check if inputValue is provided
        if ( empty( $args['search_empty']) && empty($args['inputValue'] ) ) {
            return [];
        }
        // Get the products that match the inputValue
        $products = Helper::get_post_titles_by_search($this->post_type, $args['inputValue']);
        // Normalize the fields and return as an indexed array
        return array_values(GlobalFields::get_instance()->normalize_fields($products, 'source', $this->id));
    }
}