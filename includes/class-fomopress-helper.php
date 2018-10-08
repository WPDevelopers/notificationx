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
                if( ! is_array( $value ) ) continue;
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
            case 'woocommerce' : // || 'custom' : 
                return self::sorter( $value, 'timestamp', 'DESC' );
                break;
            default: 
                return apply_filters('fomopress_sorted_data', $value);
                break;
        }
    }

    /**
     * Human Readable Time Diff
     *
     * @param boolean $time
     * @return void
     */
    public static function get_timeago_html( $time = false ) {
        if ( ! $time ) {
            return;
		}
		
        $offset = get_option('gmt_offset') * 60 * 60; // Time offset in seconds
		$timestamp = $time;
		$local_time = $timestamp + $offset;

        $time = human_time_diff( $local_time, current_time('timestamp') );
    
        ob_start();
        ?>
            <small><?php echo esc_html__( 'About', 'fomopress' ) . ' ' . esc_html( $time ) . ' ' . esc_html__( 'ago', 'fomopress' ) ?></small>
        <?php
        $time_ago = ob_get_clean();
        return $time_ago;
    }


    public static function post_types( $exclude = array() ) {
		$post_types = get_post_types(array(
			'public'	=> true,
			'show_ui'	=> true
		), 'objects');

		unset( $post_types['attachment'] );

        if ( count( $exclude ) ) {
            foreach ( $exclude as $type ) {
                if ( isset( $post_types[$type] ) ) {
                    unset( $post_types[$type] );
                }
            }
        }

		return $post_types;
    }
    
	public static function taxonomies( $post_type = '', $exclude = array() ) {
        if ( empty( $post_type ) ) {
            $taxonomies = get_taxonomies(
				array(
					'public'       => true,
					'_builtin'     => false
				),
				'objects'
			);
        } else {
		    $taxonomies = get_object_taxonomies( $post_type, 'objects' );
        }

		$data		= array();

		foreach ( $taxonomies as $tax_slug => $tax ) {
			if ( ! $tax->public || ! $tax->show_ui ) {
				continue;
			}

            if ( in_array( $tax_slug, $exclude ) ) {
                continue;
            }
			$data[$tax_slug] = $tax;
        }

		return apply_filters( 'fomopress_loop_taxonomies', $data, $taxonomies, $post_type );
    }
    

    public static function conversion_from( $from = '' ) {
        $froms = [
            'woocommerce'    => __('WooCommerce' , 'fomopress'),
        ];
        if( $from ){
            return $froms[ $from ];
        }
        return apply_filters( 'fomopress_conversions_from', $froms );
    }

    public static function press_bar_toggle_data(){
        return apply_filters('fomopress_press_bar_toggle_data', array(
            'sections' => [
                'countdown_timer'
            ],
            'fields'   => [
                'press_content',
                'button_text',
                'button_url',
                'pressbar_position',
                'sticky_bar',
                'initial_delay',
                'auto_hide',
                'hide_after',
            ],
        ));
    }

    public static function comments_toggle_data(){
        return apply_filters('fomopress_comments_toggle_data', array(
            'sections' => [
                'image'
            ],
            'fields'   => [
                'conversion_position',
                'comments_template',
                'show_avatar',
                'display_last',
                'display_from',
                'delay_before',
                'display_for',
                'delay_between',
                'loop',
                'notification_preview',
            ],
        ));
    }

    public static function conversions_toggle_data(){
        return apply_filters('fomopress_conversions_toggle_data', array(
            'sections' => [
                'image'
            ],
            'fields'   => [
                'conversion_from',
                'conversion_position',
                'delay_before',
                'display_last',
                'display_from',
                'display_for',
                'delay_between',
                'loop',
                'notification_preview'
            ],
        ));
    }

    public static function not_in_builder( $type = 'fields' ){
        $not_in_builder = apply_filters('fomopress_not_in_builder', array(
            'sections' => [
                'timing',
            ],
            'fields' => [
                'sticky_bar',
                'close_button',
                'hide_on_mobile',
                'loop',
            ],
        ));
    
        return $not_in_builder[ $type ];
    }    

    public static function notification_types( $type = '' ) {

        $types = [
            'press_bar'   => __('Notification Bar' , 'fomopress'),
            'comments'    => __('WP Comments' , 'fomopress'),
            'conversions' => __('Conversions' , 'fomopress'),
        ];
    
        if( $type ){
            return $types[ $type ];
        }
    
        return apply_filters( 'fomopress_notification_types', $types );
    }
}
