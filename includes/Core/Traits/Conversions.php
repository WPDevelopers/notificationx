<?php
/**
 * Reviews Types
 *
 * @package NotificationX\Types
 */

namespace NotificationX\Core\Traits;
use NotificationX\Core\Rules;

trait Conversions {
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
            // 'rules'  => ["and", ['is', 'type', $this->id], ['includes', 'source', [ 'woocommerce', 'edd' ]]],
            'rules' => Rules::logicalRule([
                Rules::is('type', $this->id),
                Rules::is('notification-template.first_param', 'tag_sales_count', true),
                Rules::includes('source', [ 'woocommerce', 'edd' ]),
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

}