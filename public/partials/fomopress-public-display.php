<?php

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://wpdeveloper.net
 * @since      1.0.0
 *
 * @package    FomoPress
 * @subpackage FomoPress/public/partials
 */
$type = $key = $settings->display_type;
$data = apply_filters( 'fomopress_fields_data', $data );

if( 'conversions' === $type ) {
    $key = $settings->conversion_from;
}
$from = date( 'j', strtotime( '-' . intval( $settings->display_from ) . ' days') );

if( ! empty( $data[ $key ] ) ) {
    $new_data = FomoPress_Helper::sortBy( $data[ $key ], $key );
    foreach( $new_data as $value ) {
        $unique_id = uniqid( 'fomopress-notification-' );
        /**
         * It will break the loop when the 
         * display from the last value isset.
         */
        $notification_date = date( 'j', $value['timestamp'] );
        if(  $notification_date < $from ) {
            break;
        }
        ?>
        <div id="<?php echo esc_attr( $unique_id ); ?>" class="fomopress-notification fomopress-notification-<?php echo $id; ?>">
            <div class="fomopress-notification-inner">
                <?php echo get_extention_frontend( $key, $value, $settings ); ?>
            </div>
            <!-- Link Code Will Be Here -->
        </div>
        <?php
    }
}

