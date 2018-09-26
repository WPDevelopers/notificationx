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

    public static function get_template_ready( $template, $tags ){
        $html = $template;

		// If template is in array format, lets break it down and
		// make HTML markup.
		if ( is_array( $template ) ) {
			$html = '';
			for ( $i = 0; $i < count( $template ); $i++ ) {
				if ( $i == 0 ) { // Line 1
					$html .= '<span class="ibx-notification-row-first">' . $template[$i] . '</span>';
				}
				if ( $i == 1 ) { // Line 2
					$html .= '<span class="ibx-notification-popup-title ibx-notification-row-second">' . $template[$i] . '</span>';	
				}
				if ( $i == 2 ) { // Line 3
					$html .= '<span class="ibx-notification-row-third"><small>' . $template[$i] . '</small></span>';	
				}
			}
		}

		// Get all merge tags from the template html.
		preg_match_all( '/{{([^}]*)}}/', $html, $tags_in_html, PREG_PATTERN_ORDER );

		// Holds the original tags without formatting parameteres.
		$actual_tags = array();

		// Holds the tags with formatting parameteres.
		$formatted_tags = array();

		if ( ! empty( $tags_in_html ) ) {
			for ( $i = 0; $i < count( $tags_in_html[1] ); $i++ ) {
				
				$x = explode( '|', $tags_in_html[1][$i] );
				$tag_in_template = '{{' . trim( $tags_in_html[1][$i] ) . '}}';
				if ( is_array( $x ) ) {
					$actual_tag = '{{' . trim( $x[0] ) . '}}';
					if ( ! isset( $x[1] ) ) {
						$x[1] = ' ';
					}
					$actual_tags[ $actual_tag ] = trim( $x[1] );
					$formatted_tags[ $actual_tag ] = $tag_in_template;
				} else {
					$actual_tags[ $tag_in_template ] = '';
					$formatted_tags[ $tag_in_template ] = $tag_in_template;
				}
			}
		}


		// Loop through tags and convert the values in their relevant HTML.
        foreach ( $tags as $tag => $value ) {
			
			if ( isset( $actual_tags[ $tag ] ) ) {
				
				
				$variable = explode( ':', $actual_tags[ $tag ] );
				$formatted_value = $value;
				
				switch ( trim( $variable[0] ) ) {
					case 'bold':
						$formatted_value = '<strong>' . $value . '</strong>';
						break;
					case 'italic':
						$formatted_value = '<em>' . $value . '</em>';
						break;
					case 'color':
						$formatted_value = '<span style="color: ' . trim( $variable[1] ) . ';">' . $value . '</span>';
						break;
					case 'bold+color':
						$formatted_value = '<strong style="color: ' . trim( $variable[1] ) . ';">' . $value . '</strong>';
						break;
					case 'italic+color':
						$formatted_value = '<em style="color: ' . trim( $variable[1] ) . ';">' . $value . '</em>';
						break;
					case 'propercase':
						$formatted_value = '<span style="text-transform: capitalize;">' . $value . '</span>';
						break;
					case 'upcase':
						$formatted_value = '<span style="text-transform: uppercase;">' . $value . '</span>';
						break;
					case 'downcase':
						$formatted_value = '<span style="text-transform: lowercase;">' . $value . '</span>';
						break;
					case 'fallback':
						$tmp_val = trim( $variable[1] );
						$tmp_val = str_replace( '[', '', $tmp_val );
						$tmp_val = str_replace( ']', '', $tmp_val );
						$formatted_value = empty( $value ) ? $tmp_val : $value;
						break;
					default:
						break;
				}
				$html = str_replace( $formatted_tags[ $tag ], $formatted_value, $html );
			} else {
				if ( ! is_array( $html ) && ! is_array( $value ) ) {
					$html = str_replace( $tag, $value, $html );
				}
			}
        }

        $html = str_replace( '\\', '', $html );

        return $html;
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

}

function fomopress_press_bar_toggle_data(){
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

function fomopress_conversions_toggle_data(){
    return apply_filters('fomopress_conversions_toggle_data', array(
        'sections' => [],
        'fields'   => [
            'conversion_from',
            'conversion_position',
            'delay_before',
            'display_last',
            'display_from',
            'display_for',
            'delay_between',
            'loop'
        ],
    ));
}

function fomopress_comments_toggle_data(){
    return apply_filters('fomopress_comments_toggle_data', array(
        'sections' => [],
        'fields'   => [
            'conversion_position',
            'comments_template',
            'display_last',
            'display_from',
            'delay_before',
            'display_for',
            'delay_between',
            'loop'
        ],
    ));
}