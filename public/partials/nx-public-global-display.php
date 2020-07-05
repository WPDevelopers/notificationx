<?php
if( ! is_array( $all_data ) ) {
    return;
}

foreach( $all_data as $value ) {
    $settings = $value['settings'];
    unset( $value['settings'] );
    $type = $extension_name = NotificationX_Helper::get_type( $settings );
    if( 'conversions' === $type ) {
        $key = $settings->conversion_from;
        $extension_name = $key;
    }
    $last_count = intval( $settings->display_last );
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

