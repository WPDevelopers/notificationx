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
        // $local_time = $time + ($offset * 60 * 60 ); // added offset in seconds
        $time = human_time_diff( $time, current_time('timestamp', $offset) );
        ob_start();
        ?>
            <small><?php echo esc_html__( 'About', 'notificationx' ) . ' ' . esc_html__( $time, 'notificationx' ) . ' ' . esc_html__( 'ago', 'notificationx' ); ?></small>
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
            'woocommerce' => array(
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/woocommerce.jpg',
                'title' => 'WooCommerce'
            ),
            'edd'         => array(
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/edd.jpg',
                'title' => 'Easy Digital Downloads'
            ),
            'freemius'    => array(
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/freemius.jpg',
                'is_pro' => $is_pro,
                'title' => 'Freemius'
            ),
            'zapier' => array(
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/zapier.png',
                'is_pro' => $is_pro,
                'title' => 'Zapier'
            ),
            'envato' => array(
                'source'  => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/envato.png',
                'is_pro'  => $is_pro,
                'version' => '1.2.0',
                'title' => 'Envato'
            ),
            'custom_notification' => array(
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/custom.jpg',
                'is_pro' => $is_pro,
                'title' => __( 'Custom Notification', 'notificationx' )
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
            'wp_comments' => array(
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/wordpress.jpg',
                'title' => 'WP Comments'
            ),
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
            'wp_reviews' => array(
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/wordpress.jpg',
                'title' => 'WP.Org Reviews'
            ),
            'woo_reviews' => array(
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/woocommerce.jpg',
                'title' => 'WooCommerce Reviews'
            ),
            'reviewx' => array(
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/reviewx.png',
                'title' => 'ReviewX'
            ),
            'freemius' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/freemius.jpg',
                'title' => 'Freemius'
            ),
            'zapier'    => array(
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/zapier.png',
                'is_pro' => $is_pro,
                'title' => 'Zapier'
            ),
        ];
        $forms = apply_filters('nx_reviews_source_options', $froms );
        $forms = self::active_modules( $forms );
        if( $from ){
            return $froms[ $from ];
        }
        return $forms;
    }
    public static function form_source( $from = '' ) {
        $is_pro = ! NX_CONSTANTS::is_pro();
        $froms = [
            'cf7' => array(
                // 'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/wordpress.jpg',
                'title' => 'CF7'
            ),
            'wpf' => array(
                // 'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/wordpress.jpg',
                'title' => 'WPForms'
            ),
            'njf' => array(
                // 'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/wordpress.jpg',
                'title' => 'Ninja Forms'
            ),
            'grvf' => array(
                'is_pro' => $is_pro,
                // 'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/wordpress.jpg',
                'title' => 'Gravity Forms'
            ),
        ];
        $forms = apply_filters('nx_form_source_options', $froms );
        $forms = self::active_modules( $forms );
        if( $from ){
            return $froms[ $from ];
        }
        return $forms;
    }
    public static function stats_source( $from = '' ) {
        $is_pro = ! NX_CONSTANTS::is_pro();
        $froms = [
            'wp_stats' => array(
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/wordpress.jpg',
                'title' => 'WP.Org Stats'
            ),
            'freemius' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/freemius.jpg',
                'title' => 'Freemius'
            )
        ];
        $forms = apply_filters('nx_stats_source_options', $froms );
        $forms = self::active_modules( $forms );
        if( $from ){
            return $froms[ $from ];
        }
        return $forms;
    }

    public static function elearning_source( $from = '' ) {
        $is_pro = ! NX_CONSTANTS::is_pro();
        $froms = [
            'tutor' => array(
                'source'  => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/tutor.png',
                'title' => 'Tutor'
            ),
            'learndash' => array(
                'is_pro' => $is_pro,
                'source'  => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/learndash.png',
                'title' => 'LearnDash'
            ),
        ];
        $forms = apply_filters('nx_elarning_source_options', $froms );
        $forms = self::active_modules( $forms );
        if( $from ){
            return $froms[ $from ];
        }
        return $forms;
    }
    public static function donation_source( $from = '' ) {
        $is_pro = ! NX_CONSTANTS::is_pro();
        $froms = [
            'give' => array(
                'source'  => NOTIFICATIONX_ADMIN_URL . 'assets/img/sources/give.png',
                'version' => '1.2.5',
                'title' => 'Give'
            ),
        ];
        $forms = apply_filters('nx_elarning_source_options', $froms );
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
                'pressbar_body'
            ],
        ));
    }

    public static function hide_data( $types = 'display_types' ){
        if( $types == 'display_types' ) {
            return apply_filters("nx_display_types_hide_data", array(
                'comments' => array(
                    'sections' => [ 'bar_themes', 'conversion_link_options', 'bar_design', 'bar_typography', 'themes', 'design', 'image_design', 'typography', 'rs_link_options', 'donation_themes', 'elearning_themes' ],
                    'fields' => [ 'custom_contents', 'show_custom_image', 'show_notification_image', 'wp_reviews_template', 'has_no_cron' ]
                ),
                'press_bar' => array(
                    'sections' => [ 'image', 'link_options', 'conversion_link_options', 'comment_themes', 'comment_design', 'comment_image_design', 'comment_typography', 'themes', 'design', 'image_design', 'typography', 'rs_link_options', 'donation_themes', 'elearning_themes', 'queue_management' ],
                    'fields' => [ 'comments_template', 'custom_contents', 'notification_preview', 'image_url', 'conversion_size', 'conversion_position', 'comments_template_new', 'comments_template_adv', 'wp_stats_template_new', 'has_no_cron' ]
                ),
                'conversions' => array(
                    'sections' => [ 'bar_themes', 'link_options', 'bar_design', 'bar_typography', 'comment_themes', 'comment_design', 'comment_image_design', 'comment_typography', 'rs_link_options', 'donation_themes', 'elearning_themes' ],
                    'fields' => [ 'wp_reviews_template', 'has_no_cron' ]
                ),
                'reviews' => array(
                    'fields' => [ 'comments_source', 'conversion_from', 'has_no_cron' ],
                    'sections' => [ 'comment_themes', 'comment_design', 'comment_image_design', 'comment_typography', 'themes', 'design', 'image_design', 'typography', 'bar_themes', 'link_options', 'bar_design', 'bar_typography', 'donation_themes', 'elearning_themes' ],
                ),
                'elearning' => array(
                    'sections' => ['donation_themes'],
                    'fields' => ['has_no_cron']
                ),
                'donation' => array(
                    'sections' => ['elearning_themes'],
                    'fields' => ['has_no_cron']
                ),
                'form' => array(
                    'fields' => ['has_no_cron']
                ),
                'download_stats' => array(
                    'fields' => [ 'comments_source', 'conversion_from', 'reviews_source', 'show_notification_image', 'wp_reviews_template_new', 'wp_reviews_template', 'has_no_cron' ],
                    'sections' => [ 'image', 'comment_themes', 'comment_design', 'comment_image_design', 'comment_typography', 'themes', 'design', 'image_design', 'typography', 'bar_themes', 'link_options', 'bar_design', 'bar_typography', 'donation_themes', 'elearning_themes' ],
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
            'conversions'        => __( 'Sales Notification', 'notificationx' ),
            'comments'           => __( 'Comments', 'notificationx' ),
            'reviews'            => __( 'Reviews', 'notificationx' ),
            'download_stats'     => __( 'Download Stats', 'notificationx' ),
            'elearning'          => __( 'eLearning', 'notificationx' ),
            'donation'           => __( 'Donations', 'notificationx' ),
            'press_bar'          => __( 'Notification Bar', 'notificationx' ),
            'form'               => __( 'Contact Form', 'notificationx' ),
            'email_subscription' => array(
                'source' => __( 'Email Subscription', 'notificationx' ),
                'is_pro' => $is_pro
            ),
            'page_analytics' => array(
                'source' => __( 'Page Analytics', 'notificationx' ),
                'is_pro' => $is_pro
            ),
            'custom' => array(
                'source' => __( 'Custom Notification', 'notificationx' ),
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
    /**
     * Check Active Modules or Not
     *
     * @param array $types
     * @return array
     * @since 1.2.2
     */
    public static function active_modules( $types ) {
        $active_modules = NotificationX_DB::get_settings('nx_modules');
        if( empty( $active_modules ) ) {
            return $types;
        }
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
                        if( ! isset( $active_modules[ $single_module ] ) ) {
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
                    if( ! isset( $active_modules[ $module ] ) ) {
                        if( isset( $types[ $parent_type ] ) ) {
                            unset( $types[ $parent_type ] );
                        }
                    }
                }
            }
        }

        return $types;
    }

    public static function modules_in_action( $modules ){
        $module_source = self::modules();
        $modules_we_have = [];
        foreach( $module_source as $module ){
            if( is_array( $module ) ) {
                foreach( $module as $i_module ){
                    $modules_we_have[ $i_module ] = '';
                }
            } else {
                $modules_we_have[ $module ] = '';
            }
        }
        if( ! empty( $modules ) ) {
            $modules_we_can_run = null;
            foreach( $modules as $key => $s_module ) {
                if( $s_module ) {
                    if( isset( $modules_we_have[ $key ] ) ) {
                        $modules_we_can_run[ $key ] = true;
                    }
                } else {
                    if( isset( $modules_we_have[ $key ] ) ) {
                        $modules_we_can_run[ $key ] = false;
                    }
                }
            }
            return $modules_we_can_run;
        }
        return null;
    }

    public static function modules(){
        return apply_filters( 'nx_modules_source', array(
            'press_bar' => 'modules_bar',
            'comments' => array(
                'modules_wordpress'
            ),
            'wp_comments' => 'modules_wordpress',
            'wp_stats'    => 'modules_wordpress',
            'wp_reviews'  => 'modules_wordpress',
            'woo_reviews' => 'modules_woocommerce',
            'reviewx'     => 'modules_reviewx',
            'conversions' => array(
                'modules_woocommerce',
                'modules_edd',
            ),
            'download_stats' => array(
                'modules_wordpress',
            ),
            'reviews' => array(
                'modules_wordpress',
                'modules_woocommerce',
                'modules_reviewx'
            ),
            'elearning' => array(
                'modules_tutor',
            ),
            'donation' => array(
                'modules_give',
            ),
            'form' => array(
                'modules_cf7',
                'modules_wpf',
                'modules_njf'
            ),
            'woocommerce' => 'modules_woocommerce',
            'edd'         => 'modules_edd',
            'give'        => 'modules_give',
            'tutor'       => 'modules_tutor',
            'cf7'         => 'modules_cf7',
            'wpf'         => 'modules_wpf',
            'njf'         => 'modules_njf',
        ));
    }

    public static function colored_themes(){
        $is_pro = ! NX_CONSTANTS::is_pro();

        return apply_filters('nx_colored_themes', array(
            'theme-one'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-conv-theme-2.jpg',
            'theme-two'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-conv-theme-1.jpg',
            'theme-three' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-conv-theme-3.jpg',
            'theme-four' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/pro/nx-conv-theme-four.png'
            ),
            'theme-five' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/pro/nx-conv-theme-five.png'
            ),
            'conv-theme-six' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/pro/nx-conv-theme-6.jpg'
            ),
            'maps_theme' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/pro/maps-theme.png'
            ),
            'conv-theme-seven' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/pro/nx-conv-theme-7.png'
            ),
            'conv-theme-eight' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/pro/nx-conv-theme-8.png'
            ),
            'conv-theme-nine' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/pro/nx-conv-theme-9.png'
            ),
        ));

    }

    public static function elearning_themes(){
        $is_pro = ! NX_CONSTANTS::is_pro();

        return apply_filters('nx_elearning_themes', array(
            'theme-one'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/elearning/elearning-theme-1.jpg',
            'theme-two'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/elearning/elearning-theme-2.jpg',
            'theme-three' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/elearning/elearning-theme-3.jpg',
            'theme-four' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/elearning/elearning-theme-4.png'
            ),
            'theme-five' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/elearning/elearning-theme-5.png'
            ),
            'conv-theme-six' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/elearning/elearning-theme-6.png'
            ),
            'maps_theme' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/elearning/maps-theme.png'
            ),
            'conv-theme-seven' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/elearning/elearning-theme-7.png'
            ),
            'conv-theme-eight' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/elearning/elearning-theme-8.png'
            ),
            'conv-theme-nine' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/elearning/elearning-theme-9.png'
            ),
        ));

    }

    public static function donation_themes(){
        $is_pro = ! NX_CONSTANTS::is_pro();

        return apply_filters('nx_donation_themes', array(
            'theme-one'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/donation/donation-theme-1.jpg',
            'theme-two'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/donation/donation-theme-2.jpg',
            'theme-three' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/donation/donation-theme-3.jpg',
            'theme-four' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/donation/donation-theme-4.png'
            ),
            'theme-five' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/donation/donation-theme-5.png'
            ),
            'conv-theme-six' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/donation/donation-theme-6.jpg'
            ),
            'maps_theme' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/donation/maps-theme.png'
            ),
            'conv-theme-seven' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/donation/donation-theme-7.png'
            ),
            'conv-theme-eight' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/donation/donation-theme-8.png'
            ),
            'conv-theme-nine' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/donation/donation-theme-9.png'
            ),
        ));

    }

    public static function comment_colored_themes(){
        $is_pro = ! NX_CONSTANTS::is_pro();

        return apply_filters('nx_comment_colored_themes', array(
            'theme-one'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-comment-theme-2.jpg',
            'theme-two'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-comment-theme-1.jpg',
            'theme-three' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-comment-theme-3.jpg',
            'theme-six-free' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-comment-theme-4.jpg',
            'theme-seven-free' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-comment-theme-5.jpg',
            'theme-eight-free' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-comment-theme-6.jpg',
            'theme-four' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/pro/nx-comment-theme-four.png'
            ),
            'theme-five' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/pro/nx-comment-theme-five.png'
            ),
            'maps_theme' => array(
                'is_pro' => $is_pro,
                'source' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/pro/maps-theme-comments.png'
            ),
        ));

    }

    public static function bar_colored_themes(){
        return apply_filters('nx_bar_colored_themes', array(
            'theme-one'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-bar-theme-one.jpg',
            'theme-two'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-bar-theme-two.jpg',
            'theme-three' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/nx-bar-theme-three.jpg',
        ));
    }

    public static function form_themes(){
        return apply_filters('nx_form_colored_themes', array(
            'theme-one'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/form/cf7-theme-two.jpg',
            'theme-two'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/form/cf7-theme-one.jpg',
            'theme-three' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/form/cf7-theme-three.jpg',
        ));
    }

    public static function designs_for_review(){
        return apply_filters('nxpro_wporg_themes', array(
            'total-rated'     => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/total-rated.png',
            'reviewed'     => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/reviewed.png',
            'review_saying' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/saying-review.png',
            'review-comment' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/review-with-comment.jpg',
            'review-comment-2' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/review-with-comment-2.jpg',
            'review-comment-3' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/review-with-comment-3.jpg',
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
            'elearning_template_new',
            'donation_template_new',
            'form_template_new',
            'wpf_template_new',
            'njf_template_new',
            'grvf_template_new'
        );
        return $data;
    }

    public static function regenerate_the_theme( $template_data, $desire_data ){
        $template_string = [];
        $temp_val = '';
        if( ! empty( $template_data ) ) {
            $i = $j = 0;
            foreach( $template_data as $key => $value ) {
                if( strpos( $value, 'tag_' ) === 0 ) {
                    $tag = str_replace( 'tag_', '', $value );
                    if( $tag === 'none' ) {
                        continue;
                    }
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

        $custom_tag = array(
            '{{custom}}',
            '{{sometime}}',
            '{{custom_stats}}',
            '{{this_page}}',
            '{{custom_form_title}}'
        );
        foreach( $template_string as $s_key => $s_value ) {
            if( in_array( $s_key, $desire_data['br_before'] ) ) {
                $j++;
            }
            if( in_array( trim( $s_value ), $custom_tag ) ) {
                $hasCustomAsValue = true;
                continue;
            }
            // if( in_array( trim( $s_value ), $custom_tag ) ) {
            //     $hasCustomAsValue = true;
            // }
            if( strpos( $s_key, 'custom_' ) === 0 && ! $hasCustomAsValue ) {
                // $hasCustomAsKey = true;
                continue;
            }

            // if( $hasCustomAsValue === true ) {
            //     $hasCustomAsValue = false;
            //     continue;
            // }

            // if( $hasCustomAsKey === true && $hasCustomAsValueinPrev === false ) {
            //     $hasCustomAsKey = false;
            //     continue;
            // }

            if( isset( $new_template_str[ $j ] ) ) {
                $new_template_str[ $j ] .= $s_value;
            } else {
                $new_template_str[ $j ] = $s_value;
            }
            $hasCustomAsValue = false;
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
            'elearning'      => 'elearning_source',
            'donation'       => 'donation_source',
            'form'           => 'form_source',
        ));
    }
    public static function types_title(){
        return apply_filters( 'nx_source_types_title', array(
            'press_bar'      => __('Notification Bar', 'notificationx'),
            'comments'       => __('Comments', 'notificationx'),
            'conversions'    => __('Sales Notification', 'notificationx'),
            'reviews'        => __('Reviews', 'notificationx'),
            'download_stats' => __('Download Stats', 'notificationx'),
            'elearning'      => __('eLearning', 'notificationx'),
            'donation'       => __('Donation', 'notificationx'),
            'form'           => __('Contact Form', 'notificationx'),
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
        return apply_filters( 'nx_get_theme', $theme, $settings );
    }

    public static function theme_sources(){
        return apply_filters( 'nx_themes_types', array(
            'press_bar'   => 'bar_theme',
            'wp_comments' => 'comment_theme',
            'woocommerce' => 'theme',
            'edd'         => 'theme',
            'wp_reviews'  => 'wporg_theme',
            'woo_reviews' => 'wporg_theme',
            'reviewx'     => 'wporg_theme',
            'wp_stats'    => 'wpstats_theme',
            'give'        => 'donation_theme',
            'tutor'       => 'elearning_theme',
            'cf7'         => 'form_theme',
            'wpf'         => 'form_theme',
            'njf'         => 'form_theme',
            'grvf'        => 'form_theme',
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
            'woo_reviews' => 'wp_reviews_template_new',
            'reviewx'     => 'wp_reviews_template_new',
            'wp_stats'    => 'wp_stats_template_new',
            'give'        => 'donation_template_new',
            'tutor'       => 'elearning_template_new',
            'cf7'         => 'form_template_new',
            'wpf'         => 'wpf_template_new',
            'njf'         => 'njf_template_new',
            'grvf'        => 'grvf_template_new',
        ));
    }
    /**
     * Formating Number in a Nice way
     * @since 1.2.1
     * @param int|string $n
     * @return string
     */
    public static function nice_number( $n ) {
        $temp_number = str_replace(",", "", $n );
        if( ! empty( $temp_number ) ) {
            $n = ( 0 + $temp_number );
        } else {
            $n = $n;
        }
        if( ! is_numeric( $n ) ) return 0;
        $is_neg = false;
        if( $n < 0 ) {
            $is_neg = true;
            $n = abs( $n );
        }
        $number = 0;
        $suffix = '';
        switch( true ) {
            case $n >= 1000000000000 :
                $number = ( $n / 1000000000000 );
                $suffix = $n > 1000000000000 ? 'T+' : 'T';
                break;
            case $n >= 1000000000 :
                $number = ( $n / 1000000000 );
                $suffix = $n > 1000000000 ? 'B+' : 'B';
                break;
            case $n >= 1000000 :
                $number = ( $n / 1000000 );
                $suffix = $n > 1000000 ? 'M+' : 'M';
                break;
            case $n >= 1000 :
                $number = ( $n / 1000 );
                $suffix = $n > 1000 ? 'K+' : 'K';
                break;
            default:
                $number = $n;
                break;
        }
        if( strpos( $number, '.') !== false && strpos( $number, '.') >= 0 ) {
            $number = number_format($number, 1 );
        }
        return ( $is_neg ? '-' : '' ) . $number . $suffix;
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
                <div id="nx-meta-section-sound_checkbox_wrap" class="nx-sound-appearance nx-flex nx-align-items-center">
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
    /**
     * Settings By Themes
     */
    public static function settings_by_themes( $data, $post = null ) {
        if( $post === null || $post->post_type !== 'notificationx' ) {
            return [];
        }
        /**
         * Donation Template Settins
         */

        $sales_field = get_post_meta( $post->ID, '_nx_meta_donation_template_new', true );
        $data['nx_meta_donation_template_new'] = array(
            'theme-one' => array(
                'first_param' => isset( $sales_field['first_param'] ) ? $sales_field['first_param'] : 'tag_name',
                'second_param' => isset( $sales_field['second_param'] ) ? $sales_field['second_param'] : 'just donated for',
                'third_param' => isset( $sales_field['third_param'] ) ? $sales_field['third_param'] : 'tag_title',
                'fourth_param' => isset( $sales_field['fourth_param'] ) ? $sales_field['fourth_param'] : 'tag_time',
            ),
        );
        /**
         * eLearning Template Settins
         */

        $sales_field = get_post_meta( $post->ID, '_nx_meta_elearning_template_new', true );
        $data['nx_meta_elearning_template_new'] = array(
            'theme-one' => array(
                'first_param' => isset( $sales_field['first_param'] ) ? $sales_field['first_param'] : 'tag_name',
                'second_param' => isset( $sales_field['second_param'] ) ? $sales_field['second_param'] : 'just enrolled',
                'third_param' => isset( $sales_field['third_param'] ) ? $sales_field['third_param'] : 'tag_title',
                'fourth_param' => isset( $sales_field['fourth_param'] ) ? $sales_field['fourth_param'] : 'tag_time',
            ),
        );

        /**
         * Sales Template Settins
         */

        $sales_field = get_post_meta( $post->ID, '_nx_meta_woo_template_new', true );
        $data['nx_meta_woo_template_new'] = array(
            'theme-one' => array(
                'first_param' => isset( $sales_field['first_param'] ) ? $sales_field['first_param'] : 'tag_name',
                'second_param' => isset( $sales_field['second_param'] ) ? $sales_field['second_param'] : 'just purchased',
                'third_param' => isset( $sales_field['third_param'] ) ? $sales_field['third_param'] : 'tag_title',
                'fourth_param' => isset( $sales_field['fourth_param'] ) ? $sales_field['fourth_param'] : 'tag_time',
            ),
        );

        // Commnets Template Settings
        $comments_fields = get_post_meta( $post->ID, '_nx_meta_comments_template_new', true );
        $data['nx_meta_comments_template_new'] = array(
            'theme-one' => array(
                'third_param'  => isset( $comments_fields['third_param'] ) ? $comments_fields['third_param'] : 'tag_post_title',
            ),
            'theme-two' => array(
                'third_param'  => isset( $comments_fields['third_param'] ) ? $comments_fields['third_param'] : 'tag_post_title',
            ),
            'theme-three' => array(
                'third_param'  => isset( $comments_fields['third_param'] ) ? $comments_fields['third_param'] : 'tag_post_title',
            ),
            'theme-four' => array(
                'third_param'  => isset( $comments_fields['third_param'] ) ? $comments_fields['third_param'] : 'tag_post_title',
            ),
            'theme-five' => array(
                'third_param'  => isset( $comments_fields['third_param'] ) ? $comments_fields['third_param'] : 'tag_post_title',
            ),
            'theme-six-free' => array(
                'third_param'  => isset( $comments_fields['third_param'] ) ? $comments_fields['third_param'] : 'tag_post_comment',
            ),
            'theme-seven-free' => array(
                'third_param'  => isset( $comments_fields['third_param'] ) ? $comments_fields['third_param'] : 'tag_post_comment',
            ),
            'theme-eight-free' => array(
                'third_param'  => isset( $comments_fields['third_param'] ) ? $comments_fields['third_param'] : 'tag_post_comment',
            ),
        );

        /**
         * Reviews Template Settings
         */
        $reviews_field = get_post_meta( $post->ID, '_nx_meta_wp_reviews_template_new', true );
        $data['nx_meta_wp_reviews_template_new'] = array(
            'reviewed' => array(
                'first_param' => isset( $reviews_field['first_param'] ) ? $reviews_field['first_param'] : 'tag_username',
                'second_param' => isset( $reviews_field['second_param'] ) ? $reviews_field['second_param'] : 'just reviewed',
                'third_param' => isset( $reviews_field['third_param'] ) ? $reviews_field['third_param'] : 'tag_plugin_name',
                'fourth_param' => isset( $reviews_field['fourth_param'] ) ? $reviews_field['fourth_param'] : 'tag_rating',
            ),
            'total-rated' => array(
                'first_param' => isset( $reviews_field['first_param'] ) ? $reviews_field['first_param'] : 'tag_rated',
                'second_param' => isset( $reviews_field['second_param'] ) ? $reviews_field['second_param'] : 'people rated',
                'third_param' => isset( $reviews_field['third_param'] ) ? $reviews_field['third_param'] : 'tag_plugin_name',
                'fourth_param' => isset( $reviews_field['fourth_param'] ) ? $reviews_field['fourth_param'] : 'tag_rating',
            ),
            'review-comment' => array(
                'first_param' => isset( $reviews_field['first_param'] ) ? $reviews_field['first_param'] : 'tag_username',
                'second_param' => isset( $reviews_field['second_param'] ) ? $reviews_field['second_param'] : 'just reviewed',
                'third_param' => isset( $reviews_field['third_param'] ) ? $reviews_field['third_param'] : 'tag_plugin_review',
                'fourth_param' => isset( $reviews_field['fourth_param'] ) ? $reviews_field['fourth_param'] : 'tag_rating',
            ),
            'review-comment-2' => array(
                'first_param' => isset( $reviews_field['first_param'] ) ? $reviews_field['first_param'] : 'tag_username',
                'second_param' => isset( $reviews_field['second_param'] ) ? $reviews_field['second_param'] : 'just reviewed',
                'third_param' => isset( $reviews_field['third_param'] ) ? $reviews_field['third_param'] : 'tag_plugin_review',
                'fourth_param' => isset( $reviews_field['fourth_param'] ) ? $reviews_field['fourth_param'] : 'tag_rating',
            ),
            'review-comment-3' => array(
                'first_param' => isset( $reviews_field['first_param'] ) ? $reviews_field['first_param'] : 'tag_username',
                'second_param' => isset( $reviews_field['second_param'] ) ? $reviews_field['second_param'] : 'just reviewed',
                'third_param' => isset( $reviews_field['third_param'] ) ? $reviews_field['third_param'] : 'tag_plugin_review',
                'fourth_param' => isset( $reviews_field['fourth_param'] ) ? $reviews_field['fourth_param'] : 'tag_time',
            )
        );

        /**
         * Stats Template Settings
         */
        $stats_field = get_post_meta( $post->ID, '_nx_meta_wp_stats_template_new', true );
        $data['nx_meta_wp_stats_template_new'] = array(
            'today-download' => array(
                'first_param'  => isset( $stats_field['first_param'] ) ? $stats_field['first_param'] : 'tag_name',
                'third_param'  => isset( $stats_field['third_param'] ) ? $stats_field['third_param'] : 'tag_today',
                'fourth_param' => isset( $stats_field['fourth_param'] ) ? $stats_field['fourth_param'] : 'tag_today_text',
            ),
            '7day-download' => array(
                'first_param'  => isset( $stats_field['first_param'] ) ? $stats_field['first_param'] : 'tag_name',
                'third_param'  => isset( $stats_field['third_param'] ) ? $stats_field['third_param'] : 'tag_last_week',
                'fourth_param' => isset( $stats_field['fourth_param'] ) ? $stats_field['fourth_param'] : 'tag_last_week_text',
            ),
            'total-download' => array(
                'first_param'  => isset( $stats_field['first_param'] ) ? $stats_field['first_param'] : 'tag_name',
                'third_param'  => isset( $stats_field['third_param'] ) ? $stats_field['third_param'] : 'tag_all_time',
                'fourth_param' => isset( $stats_field['fourth_param'] ) ? $stats_field['fourth_param'] : 'tag_all_time_text',
            )
        );

        return $data;
    }

    /**
     * Contact Forms Key Name filter for Name Selectbox
     * @since 1.4.*
     * @param string
     * @return boolean
     */
    public static function filter_contactform_key_names( $name ) {
        $validKey = true;
        $filterWords = array(
            "checkbox",
            "color",
            "date",
            "datetime-local",
            "file",
            "image",
            "month",
            "number",
            "password",
            "radio",
            "range",
            "reset",
            "submit",
            "tel",
            "time",
            "week",
            "Comment",
            "message",
            "address",
            "phone",
        );
        foreach ( $filterWords as $word ) {
            if ( !empty($name) && stripos($name, $word) === false) {
                $validKey = true;
            }
            else {
                $validKey = false;
                break;
            }
        }
        return $validKey;
    }

    /**
     * Contact Forms Key Name remove special characters and meaningless words for Name Selectbox
     * @since 1.4.*
     * @param string
     * @return string
     */
    public static function rename_contactform_key_names( $name ) {
        $result = preg_split("/[_,\-]+/", $name);
        $returnName = ucfirst($result[0]);
        return $returnName;
    }
}
