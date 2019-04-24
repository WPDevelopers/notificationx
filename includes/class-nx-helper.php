<?php
/**
 * This class will provide all kind of helper methods.
 */
class NotificationX_Helper {
    /**
     * This function is responsible for the data sanitization
     *
     * @param array $field
     * @param string|array $value
     * @return string|array
     */
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
    /**
     * This function is responsible for making an array sort by their key
     * @param array $data
     * @param string $using
     * @param string $way
     * @return array
     */
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
                        $single = self::numeric_key_gen( $new_array, $single );
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
    /**
     * This function is responsible for generate unique numeric key for a given array.
     *
     * @param array $data
     * @param integer $index
     * @return integer
     */
    private static function numeric_key_gen( $data, $index = 0 ){
        if( isset( $data[ $index ] ) ) {
            $index+=1;
            return self::numeric_key_gen( $data, $index );
        }
        return $index;
    }
    /**
     * Sorting Data 
     * by their type
     *
     * @param array $value
     * @param string $key
     * @return void
     */
    public static function sortBy( &$value, $key = 'comments' ) {
        switch( $key ){
            case 'comments' : 
                return self::sorter( $value, 'key', 'DESC' );
                break;
            default: 
                return self::sorter( $value, 'timestamp', 'DESC' );
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
		
        $offset = get_option('gmt_offset'); // Time offset in hours
        $local_time = $time + ($offset * 60 * 60 ); // added offset in seconds
        $time = human_time_diff( $local_time, current_time('timestamp') );
        ob_start();
        ?>
            <small><?php echo esc_html__( 'About', 'notificationx' ) . ' ' . esc_html( $time ) . ' ' . esc_html__( 'ago', 'notificationx' ) ?></small>
        <?php
        $time_ago = ob_get_clean();
        return $time_ago;
    }
    /**
     * Get all post types
     *
     * @param array $exclude
     * @return void
     */
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

		return apply_filters('nx_post_types', $post_types );
    }
    /**
     * Get all taxonomies
     *
     * @param string $post_type
     * @param array $exclude
     * @return void
     */
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
        
        $data = array();
        if( is_array( $taxonomies ) ) {
            foreach ( $taxonomies as $tax_slug => $tax ) {
                if( ! $tax->public || ! $tax->show_ui ) {
                    continue;
                }
                if( in_array( $tax_slug, $exclude ) ) {
                    continue;
                }
                $data[$tax_slug] = $tax;
            }
        }
		return apply_filters('nx_loop_taxonomies', $data, $taxonomies, $post_type );
    }
    /**
     * This function is responsible for all conversion from data
     * @param string $from
     * @return array|string
     */
    public static function conversion_from( $from = '' ) {
        $froms = [
            'woocommerce' => __('WooCommerce' , 'notificationx'),
            'edd'         => __('Easy Digital Downloads' , 'notificationx'),
        ];
        $forms = apply_filters('nx_conversions_from', $froms );
        if( $from ){
            return $froms[ $from ];
        }
        return $forms;
    }
    /**
     * This function is responsible for press_bar toggle data
     * @return array
     */
    public static function press_bar_toggle_data(){
        return apply_filters('nx_press_bar_toggle_data', array(
            'sections' => [
                'countdown_timer',
                'bar_themes'
            ],
            'fields'   => [
                'press_content',
                'button_text',
                'button_url',
                'pressbar_position',
                'sticky_bar',
                'initial_delay',
                'auto_hide',
            ],
        ));
    }
    /**
     * This function is responsible for comments toggle data
     * @return array
     */
    public static function comments_toggle_data(){
        return apply_filters('nx_comments_toggle_data', array(
            'sections' => [
                'image',
                'comment_themes',
                'link_options'
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
                'conversion_size'
            ],
        ));
    }
    /**
     * This function is responsible for conversion toggle data
     * @return array
     */
    public static function conversions_toggle_data(){
        return apply_filters('nx_conversions_toggle_data', array(
            'sections' => [
                'image',
                'themes',
                'conversion_link_options'
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
                'notification_preview',
                'conversion_size'
            ],
        ));
    }

    public static function hide_data( $types = 'display_types' ){
        if( $types == 'display_types' ) {
            return apply_filters("nx_display_types_hide_data", array(
                'comments' => array(
                    'sections' => [ 'bar_themes', 'conversion_link_options', 'bar_design', 'bar_typography', 'themes', 'design', 'image_design', 'typography' ],
                    'fields' => [ 'custom_template', 'custom_contents', 'show_custom_image', 'show_notification_image', 'image_url' ]
                ),
                'press_bar' => array(
                    'sections' => [ 'image', 'link_options', 'conversion_link_options', 'comment_themes', 'comment_design', 'comment_image_design', 'comment_typography', 'themes', 'design', 'image_design', 'typography' ],
                    'fields' => [ 'custom_template', 'comments_template', 'custom_contents', 'notification_preview', 'image_url', 'conversion_size', 'conversion_position' ]
                ),
                'conversions' => array(
                    'sections' => [ 'bar_themes', 'link_options', 'bar_design', 'bar_typography', 'comment_themes', 'comment_design', 'comment_image_design', 'comment_typography' ], 
                ),
            ));
        }
        if( $types == 'conversion_from' ) {
            return apply_filters("nx_conversion_from_hide_data", array() );
        }

        return [];
    }
    /**
     * This function is responsible for all Notification types
     * @param string $type
     * @return array|string
     */
    public static function notification_types( $type = '' ) {
        $types = [
            'press_bar'   => __('Notification Bar' , 'notificationx'),
            'comments'    => __('WP Comments' , 'notificationx'),
            'conversions' => __('Sales Notification' , 'notificationx'),
        ];

        $types = apply_filters('nx_notification_types', $types );

        if( $type ){
            return $types[ $type ];
        }
        return $types;
    }

    public static function colored_themes(){

        return apply_filters('nx_colored_themes', array(
            'theme-one'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-conv-theme-2.jpg',
            'theme-two'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-conv-theme-1.jpg',
            'theme-three' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-conv-theme-3.jpg'
        ));

    }

    public static function comment_colored_themes(){

        return apply_filters('nx_comment_colored_themes', array(
            'theme-one'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-comment-theme-2.jpg',
            'theme-two'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-comment-theme-1.jpg',
            'theme-three' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-comment-theme-3.jpg'
        ));

    }

    public static function bar_colored_themes(){

        return apply_filters('nx_bar_colored_themes', array(
            'theme-one'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-bar-theme-3.jpg',
            'theme-two'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-bar-theme-1.jpg',
            'theme-three' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-bar-theme-2.jpg',
        ));

    }

    public static function conversion_toggle(){
        return apply_filters('nx_conversion_toggle' , array(
            'custom_notification'        => array(
                'sections' => [ 'image' ],
                'fields' => [ 'custom_template', 'custom_contents' ]
            ),
        ));
    }
}
