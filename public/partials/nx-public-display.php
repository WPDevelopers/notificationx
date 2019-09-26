<?php

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://wpdeveloper.net
 * @since      1.0.0
 *
 * @package    NotificationX
 * @subpackage NotificationX/public/partials
 */
$type = $extension_name = $key = '';
$type = $extension_name = $key = NotificationX_Helper::get_type( $settings );
$data = apply_filters('nx_fields_data', $data, $settings->id );


/**
 * Set the key
 * which is use to get data out of it!
 */
if( 'conversions' === $type ) {
    $key = $settings->conversion_from;
    $extension_name = $key;
}

$from = strtotime( '-' . intval( $settings->display_from ) . ' days');

$key = apply_filters( 'nx_data_key', $key, $settings );

if( $settings->display_type == 'conversions' && $settings->conversion_from == 'custom_notification' ) {
    $data[ $key ] = $settings->custom_contents;
}

if( ! empty( $data[ $key ] ) ) {
    $new_data = apply_filters( 'nx_filtered_data', NotificationX_Helper::sortBy( $data[ $key ], $key ), $settings );
    if( ! is_array( $new_data ) ) {
        return;
    }
    $last_count = intval( $settings->display_last );
    foreach( $new_data as $value ) {
        /**
         * Fallback Check 
         * Display Last
         */
        if( $last_count === 0 ) {
            break;
        }
        /**
         * It will break the loop when the 
         * display from the last value isset.
         */
        if( isset( $value['timestamp'] ) ) {
            if( $value['timestamp'] < $from ) {
                break;
            }
        }
        echo get_extension_frontend( $extension_name, $value, $settings );
        $last_count--;
    }
}

