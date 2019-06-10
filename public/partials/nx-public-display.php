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

switch( $settings->display_type ) {
    case 'press_bar' : 
        $type = $extension_name = $key = $settings->display_type;
        break;
    case 'comments' : 
        $type = $extension_name = $key = $settings->comments_source;
        break;
    case 'conversions' : 
        $type = $extension_name = $key = $settings->conversion_from;
        break;
    case 'reviews' : 
        $type = $extension_name = $key = $settings->reviews_source;
        break;
    case 'download_stats' : 
        $type = $extension_name = $key = $settings->stats_source;
        break;
}

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

    foreach( $new_data as $value ) {
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
    }
}

