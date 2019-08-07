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

        if( is_array( $field ) && isset( $field['type'] ) ) {
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
                    return $value;
                    break;
            }
        } else {
            $value = sanitize_text_field( $value );
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
        $is_pro = ! NX_CONSTANTS::is_pro();
        $froms = [
            'woocommerce' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/woocommerce.jpg',
            'edd'         => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/edd.jpg',
            'freemius'    => array(
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/freemius.jpg',
                'is_pro' => $is_pro,
            ),
            'zapier'    => array(
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/zapier.jpg',
                'is_pro' => $is_pro,
            ),
            'custom_notification'    => array(
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/custom.jpg',
                'is_pro' => $is_pro,
            ),
        ];
        $forms = apply_filters('nx_conversions_from', $froms );
        $forms = self::active_modules( $forms );

        if( $from ){
            return $froms[ $from ];
        }
        return $forms;
    }
    /**
     * This function is responsible for all conversion from data
     * @param string $from
     * @return array|string
     */
    public static function comments_source( $from = '' ) {
        $froms = [
            'wp_comments' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/wordpress.jpg',
        ];
        $forms = apply_filters('nx_comments_source_options', $froms );
        $forms = self::active_modules( $forms );
        if( $from ){
            return $froms[ $from ];
        }
        return $forms;
    }
    /**
     * This function is responsible for all conversion from data
     * @param string $from
     * @return array|string
     */
    public static function reviews_source( $from = '' ) {
        $is_pro = ! NX_CONSTANTS::is_pro();
        $froms = [
            'wp_reviews' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/wordpress.jpg',
            'freemius' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/freemius.jpg'
            ),
            'zapier'    => array(
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/zapier.jpg',
                'is_pro' => $is_pro
            ),
        ];
        $forms = apply_filters('nx_reviews_source_options', $froms );
        $forms = self::active_modules( $forms );
        if( $from ){
            return $froms[ $from ];
        }
        return $forms;
    }
    public static function stats_source( $from = '' ) {
        $is_pro = ! NX_CONSTANTS::is_pro();
        $froms = [
            'wp_stats' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/wordpress.jpg',
            'freemius' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/freemius.jpg'
            ),
        ];
        $forms = apply_filters('nx_stats_source_options', $froms );
        $forms = self::active_modules( $forms );
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
                'conversion_size',
            ],
        ));
    }

    public static function hide_data( $types = 'display_types' ){
        if( $types == 'display_types' ) {
            return apply_filters("nx_display_types_hide_data", array(
                'comments' => array(
                    'sections' => [ 'bar_themes', 'conversion_link_options', 'bar_design', 'bar_typography', 'themes', 'design', 'image_design', 'typography', 'rs_link_options' ],
                    'fields' => [ 'custom_contents', 'show_custom_image', 'show_notification_image', 'image_url' ]
                ),
                'press_bar' => array(
                    'sections' => [ 'image', 'link_options', 'conversion_link_options', 'comment_themes', 'comment_design', 'comment_image_design', 'comment_typography', 'themes', 'design', 'image_design', 'typography', 'rs_link_options' ],
                    'fields' => [ 'comments_template', 'custom_contents', 'notification_preview', 'image_url', 'conversion_size', 'conversion_position', 'comments_template_new', 'comments_template_adv', 'wp_stats_template_new' ]
                ),
                'conversions' => array(
                    'sections' => [ 'bar_themes', 'link_options', 'bar_design', 'bar_typography', 'comment_themes', 'comment_design', 'comment_image_design', 'comment_typography', 'rs_link_options' ], 
                ),
                'reviews' => array(
                    'fields' => [ 'comments_source', 'conversion_from' ], 
                    'sections' => [ 'comment_themes', 'comment_design', 'comment_image_design', 'comment_typography', 'themes', 'design', 'image_design', 'typography', 'bar_themes', 'link_options', 'bar_design', 'bar_typography' ], 
                ),
                'download_stats' => array(
                    'fields' => [ 'comments_source', 'conversion_from', 'reviews_source', 'show_notification_image', 'wp_reviews_template_new' ], 
                    'sections' => [ 'image', 'comment_themes', 'comment_design', 'comment_image_design', 'comment_typography', 'themes', 'design', 'image_design', 'typography', 'bar_themes', 'link_options', 'bar_design', 'bar_typography' ], 
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
        $is_pro = ! NX_CONSTANTS::is_pro();
        $types = [
            'conversions'    => __( 'Sales Notification', 'notificationx' ),
            'comments'       => __( 'Comments', 'notificationx' ),
            'reviews'        => __( 'Reviews', 'notificationx' ),
            'download_stats' => __( 'Download Stats', 'notificationx' ),
            'press_bar'      => __( 'Notification Bar', 'notificationx' ),
            'email_subscription' => array(
                'source' => __( 'Email Subscription', 'notificationx' ),
                'is_pro' => $is_pro
            ),
        ];
        $types = apply_filters('nx_notification_types', $types );

        $types = self::active_modules( $types );

        if( $type ){
            return isset( $types[ $type ] ) ? $types[ $type ] : '';
        }
        return $types;
    }

    public static function active_modules( $types ) {
        $active_modules = NotificationX_DB::get_settings('nx_modules');
        if( isset( $active_modules['modules_bar'] ) && $active_modules['modules_bar'] == false ) {
            unset( $types['press_bar'] );
        }
        $module_source = self::modules();

        if( ! empty( $module_source ) ) {
            foreach( $module_source as $parent_type => $module ) {
                if( is_array( $module ) ) {
                    $module_counter = count( $module );
                    foreach( $module as $source_key => $single_module ) {
                        if( isset( $active_modules[ $single_module ] ) && $active_modules[ $single_module ] == false ) {
                            $module_counter--;
                        }
                    }
                    if( $module_counter === 0 ) {
                        if( isset( $types[ $parent_type ] ) ) {
                            unset( $types[ $parent_type ] );
                        } 
                    }
                } else {
                    if( isset( $active_modules[ $module ] ) && $active_modules[ $module ] == false ) {
                        if( isset( $types[ $parent_type ] ) ) {
                            unset( $types[ $parent_type ] );
                        } 
                    }
                }
            }
        }
        return $types;
    }

    public static function modules(){
        return apply_filters( 'nx_modules_source', array(
            'press_bar' => 'modules_bar',
            'comments' => array(
                'modules_wordpress'
            ),
            'wp_comments' => 'modules_wordpress',
            'wp_stats' => 'modules_wordpress',
            'wp_reviews' => 'modules_wordpress',
            'conversions' => array(
                'modules_woocommerce',
                'modules_edd'
            ),
            'download_stats' => array(
                'modules_wordpress',
            ),
            'reviews' => array(
                'modules_wordpress',
            ),
            'woocommerce' => 'modules_woocommerce',
            'edd' => 'modules_edd',
        ));
    }

    public static function colored_themes(){

        return apply_filters('nx_colored_themes', array(
            'theme-one'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-conv-theme-2.jpg',
            'theme-two'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-conv-theme-1.jpg',
            'theme-three' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-conv-theme-3.jpg'
        ));

    }

    public static function comment_colored_themes(){
        $is_pro = ! NX_CONSTANTS::is_pro();

        return apply_filters('nx_comment_colored_themes', array(
            'theme-one'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-comment-theme-2.jpg',
            'theme-two'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-comment-theme-1.jpg',
            'theme-three' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-comment-theme-3.jpg',
            'theme-four' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-comment-theme-3.jpg'
            ),
        ));

    }

    public static function bar_colored_themes(){
        return apply_filters('nx_bar_colored_themes', array(
            'theme-one'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-bar-theme-3.jpg',
            'theme-two'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-bar-theme-1.jpg',
            'theme-three' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-bar-theme-2.jpg',
        ));
    }

    public static function designs_for_review(){
        return apply_filters('nxpro_wporg_themes', array(
            'total-rated'     => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/total-rated.png',
            'reviewed'     => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/reviewed.png',
            'review_saying' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/saying-review.png',
        ));
    }

    public static function designs_for_stats(){
        return apply_filters('nxpro_stats_themes', array(
            'today-download' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/today-download.png',
            '7day-download'  => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/7day-download.png',
            'actively_using' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/actively-using.png',
            'total-download' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/total-download.png',
        ));
    }

    public static function new_template_name( $data ){
        $data = array(
            'comments_template_new',
            'wp_reviews_template_new',
            'wp_stats_template_new',
            'actively_using_template_new',
            'review_saying_template_new',
            'woo_template_new',
        );
        return $data;
    }

    public static function regenerate_the_theme( $template_data, $desire_data ){
        $template_string = [];
        if( ! empty( $template_data ) ) {
            $i = $j = 0;
            foreach( $template_data as $key => $value ) {
                if( strpos( $value, 'tag_' ) === 0 ) {
                    $tag = str_replace( 'tag_', '', $value );
                    $template_string[ $key ] = "{{{$tag}}} ";
                } else {
                    $trimed = isset( $template_string[ $key ] ) ? trim( $template_string[ $key ] ) : '';
                    if( ! empty( $trimed ) ) {
                        $temp_val = trim( $template_string[ $key ] ) . ' ';
                    }
                    if( ! empty( $temp_val ) && strpos( $key, 'custom_' ) === 0 ) {
                        $value = '';
                    }
                    $template_string[ $key ] =  $temp_val . $value . " ";
                }
                $temp_val = $value = '';
                $i++;
            }
        }

        $new_template_str = [];

        $hasCustomAsValue = $hasCustomAsKey = $hasCustomAsValueinPrev = false;
        $previous_key = $previous_value = '';

        $j = 0;

        foreach( $template_string as $s_key => $s_value ) {
            if( in_array( $s_key, $desire_data['br_before'] ) ) {
                $j++;
            }
            if( trim($previous_value) === '{{custom}}' ) { 
                $hasCustomAsValueinPrev = true;
            }
            if( trim($s_value) === '{{custom}}' ) { 
                $hasCustomAsValue = true;
            }
            if( strpos( $s_key, 'custom_' ) === 0 ) { 
                $hasCustomAsKey = true;
            }
            
            if( $hasCustomAsValue === true ) {
                $previous_value = $s_value;
                $hasCustomAsValue = false;
                continue;
            }

            if( $hasCustomAsKey === true && $hasCustomAsValueinPrev === false ) {
                $previous_value = $s_value;
                $hasCustomAsKey = false;
                continue;
            }

            $previous_value = $s_value;
            if( isset( $new_template_str[ $j ] ) ) {
                $new_template_str[ $j ] .= $s_value;
            } else {
                $new_template_str[ $j ] = $s_value;
            }
        }
        return $new_template_str;
    }

    public static function remove_prefix( $value, $key ){
        global $new_array;
        $key = str_replace( 'nx_meta_', '', $key );
        $new_array[ $key ] = $value;
    }

    public static function get_type( $settings ){
        if( empty( $settings ) ) {
            return 'press_bar';
        }
        $type = 'press_bar';
        if( is_array( $settings ) ) {
            $new_array = [];
            global $new_array;
            array_walk( $settings, 'NotificationX_Helper::remove_prefix'  );
            $settings = ( object ) $new_array;
        }

        $source_types = self::source_types();
        if( array_key_exists( $settings->display_type, $source_types ) ) {
            $type = $settings->{ $source_types[ $settings->display_type ] };
        }
        return $type;
    }

    public static function source_types(){
        return apply_filters( 'nx_source_types', array( 
            'press_bar'      => 'display_type',
            'comments'       => 'comments_source',
            'conversions'    => 'conversion_from',
            'reviews'        => 'reviews_source',
            'download_stats' => 'stats_source',
        ));
    }
    public static function types_title(){
        return apply_filters( 'nx_source_types_title', array( 
            'press_bar'      => __('Notification Bar', 'notificationx'),
            'comments'       => __('Comments', 'notificationx'),
            'conversions'    => __('Sales Notification', 'notificationx'),
            'reviews'        => __('Reviews', 'notificationx'),
            'download_stats' => __('Download Stats', 'notificationx'),
        ));
    }

    public static function get_theme( $settings ){
        if( empty( $settings ) ) {
            return 'theme-one';
        }
        $theme = '';
        if( is_array( $settings ) ) {
            $new_array = [];
            global $new_array;
            array_walk( $settings, 'NotificationX_Helper::remove_prefix'  );
            $settings = ( object ) $new_array;
        }
        $type = self::get_type( $settings );
        $theme_sources = self::theme_sources();
        if( array_key_exists( $type, $theme_sources ) ) {
            if( is_array( $theme_sources[ $type ] ) ) {
                $theme = $settings->{ $theme_sources[ $type ][ $settings->display_type ] };
            } else {
                $theme = $settings->{ $theme_sources[ $type ] };
            }
        }
        return $theme;
    }

    public static function theme_sources(){
        return apply_filters( 'nx_themes_types', array( 
            'press_bar'   => 'bar_theme',
            'wp_comments' => 'comment_theme',
            'woocommerce' => 'theme',
            'edd'         => 'theme',
            'wp_reviews'  => 'wporg_theme',
            'wp_stats'    => 'wpstats_theme',
        ));
    }

    public static function get_template_key( $settings ){
        if( empty( $settings ) ) {
            return '';
        }
        $template = '';
        if( is_array( $settings ) ) {
            $new_array = [];
            global $new_array;
            array_walk( $settings, 'NotificationX_Helper::remove_prefix'  );
            $settings = ( object ) $new_array;
        }
        $theme = self::get_theme( $settings );
        $template_types = self::template_keys();
        if( array_key_exists( $type, $theme_types ) ) {
            if( is_array( $theme_types[ $type ] ) ) {
                $template = $settings->{ $theme_types[ $type ][ $settings->display_type ] };
            } else {
                $template = $settings->{ $theme_types[ $type ] };
            }
        }
        return $template;
    }

    public static function template_keys(){
        return apply_filters( 'nx_template_keys', array( 
            'wp_comments' => 'comments_template_new',
            'woocommerce' => 'woo_template_new',
            'edd'         => 'woo_template_new',
            'wp_reviews'  => 'wp_reviews_template_new',
            'wp_stats'  => 'wp_stats_template_new',
        ));
    }

    public static function nice_number($n) {
        // first strip any formatting;
        $n = ( 0 + str_replace(",", "", $n ) );
        // is this a number?
        if( ! is_numeric( $n ) ) return 0;
        $number = 0;
        $suffix = '';
        switch( $n ) {
            case $n >= 1000000000000 : 
                $number = round( ( $n / 1000000000000 ), 1 );
                $suffix = 'T';
                if( $n > 1000000000000 ) {
                    $suffix = 'T+';
                }
                break;
            case $n >= 1000000000 : 
                $number = round( ( $n / 1000000000 ), 1 );
                $suffix = 'B';
                if( $n > 1000000000 ) {
                    $suffix = 'B+';
                }
                break;
            case $n >= 1000000 : 
                $number = round( ( $n / 1000000 ), 1 );
                $suffix = 'M';
                if( $n > 1000000 ) {
                    $suffix = 'M+';
                }
                break;
            case $n >= 1000 : 
                $number = round( ( $n / 1000 ), 1 );
                $suffix = 'K';
                if( $n > 1000 ) {
                    $suffix = 'K+';
                }
                break;
            default: 
                $number = $n;
                break;
        }
        $number = number_format($number);
        return $number . $suffix;
    }

    public static function sound_section( $sec_id, $id, $section ){
        if( $sec_id === 'appearance' ) {
            global $post;
            $checked = get_post_meta( $post->ID, '_nx_meta_sound_checkbox', true );
            $checked = $checked ? 'checked="true"' : '';
            $is_pro = class_exists( 'NotificationXPro' ) ? '' : 'data-swal="true"';
            if( $is_pro ) {
                $checked = '';
            }

            $active_class = '';
            $active_class = 'nx-sound-active';

            ?>
                <div id="nx-meta-section-sound" class="nx-sound-appearance nx-flex nx-align-items-center">
                    <div class="nx-left">
                        <span class="nx-sound-enable <?php echo ! $checked ? $active_class : ''; ?>"><?php _e( 'Enable Sound', 'notificationx' );?></span>
                        <span class="nx-sound-disable <?php echo $checked ? $active_class : ''; ?>"><?php _e( 'Disable Sound', 'notificationx' );?></span>
                    </div>
                    <div class="nx-right">
                        <div class="nx-styled-checkbox">
                            <input <?php echo $is_pro; ?> type="checkbox" name="nx_meta_sound_checkbox" id="nx_sound_checkbox" <?php echo $checked; ?>>
                            <label for="nx_sound_checkbox"></label>
                        </div>
                    </div>
                </div>
            <?php
        }
    }
}
