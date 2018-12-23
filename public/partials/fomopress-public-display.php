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
 * @subpackage FomoPress/public/partials
 */
$type = $key = $settings->display_type;
$data = apply_filters( 'fomopress_fields_data', $data, $settings->id );
/**
 * Set the key
 * which is use to get data out of it!
 */
if( 'conversions' === $type ) {
    $key = $settings->conversion_from;
}
$from = strtotime( '-' . intval( $settings->display_from ) . ' days');

if( $settings->display_type == 'conversions' && $settings->conversion_from == 'custom' ) {
    $data[ $key ] = $settings->custom_contents;
}
if( ! empty( $data[ $key ] ) ) {
    $new_data = NotificationX_Helper::sortBy( $data[ $key ], $key );
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
        echo get_extension_frontend( $key, $value, $settings );
    }
}

