<?php
/**
 * Reviews Types
 *
 * @package NotificationX\Types
 */

namespace NotificationX\Types\Traits;
use NotificationX\Core\Rules;

trait Conversions {

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

}