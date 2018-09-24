<?php
/**
 * This class will provide all kind of helper methods.
 */
class FomoPress_Helper {
    public static function sanitize_field( $field, $value ) {
        if ( isset( $field['sanitize'] ) && ! empty( $field['sanitize'] ) ) {

            if ( function_exists( $field['sanitize'] ) ) {
                $value = call_user_func( $field['sanitize'], $value );
            }

            return $value;
        }

        switch ( $field['type'] ) {
            case 'text':
                $value = sanitize_text_field( $value );
                break;
            case 'textarea':
                $value = sanitize_textarea_field( $value );
                break;
            case 'email':
                $value = sanitize_email( $value );
                break;
            default:
                break;
        }

        return $value;
    }

    public static function sorter( $data, $using = 'time_date',  $way = 'DESC' ){
        if( ! is_array( $data ) ) {
            return $data;
        }
        $new_array = [];

        if( $using === 'key' ) {
            if( $way !== 'ASC' ) {
                krsort( $data );
            } else {
                ksort( $data );
            }
        } else {
            foreach( $data as $key => $value ) {
                foreach( $value as $inner_key => $single ) {
                    if( $inner_key == $using ) {
                        $value[ 'tempid' ] = $key;
                        $new_array[ $single ] = $value;
                    }
                }
            }

            if( $way !== 'ASC' ) {
                krsort( $new_array );
            } else {
                ksort( $new_array );
            }

            if( ! empty( $new_array ) ) {
                foreach( $new_array as $array ) {
                    $index = $array['tempid'];
                    unset( $array['tempid'] );
                    $new_data[ $index ] = $array;
                }
                $data = $new_data;
            }
        }

        return $data;
    }

    public static function sortBy( &$value, $key = 'comments' ) {
        switch( $key ){
            case 'comments' : 
                return self::sorter( $value, 'key', 'DESC' );
                break;
            case 'conversions' : 
                return self::sorter( $value, 'timestamp', 'DESC' );
                break;
            default: 
                return apply_filters('fomopress_sorted_data', $value);
                break;
        }
    }
}