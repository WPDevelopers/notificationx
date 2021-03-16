<?php

/**
* The public-facing functionality of the plugin.
*
* @link       https://wpdeveloper.net
* @since      1.0.0
*
* @package    NotificationX
* @subpackage NotificationX/public
*/

/**
* The public-facing functionality of the plugin.
*
* Defines the plugin name, version, and two examples hooks for how to
* enqueue the public-facing stylesheet and JavaScript.
*
* @package    NotificationX
* @subpackage NotificationX/public
* @author     WPDeveloper <support@wpdeveloper.net>
*/
class NotificationX_Public {

	/**
	* The ID of this plugin.
	*
	* @since    1.0.0
	* @access   private
	* @var      string    $plugin_name    The ID of this plugin.
	*/
	private $plugin_name;

	/**
	* The version of this plugin.
	*
	* @since    1.0.0
	* @access   private
	* @var      string    $version    The current version of this plugin.
	*/
	private $version;

	public static $active = [];

	public $notifications = [];

	/**
	* Initialize the class and set its properties.
	*
	* @since    1.0.0
	* @param      string    $plugin_name       The name of the plugin.
	* @param      string    $version    The version of this plugin.
	*/
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name   = $plugin_name;
		$this->version       = $version;

		$this->notifications = get_option('notificationx_data');

		add_filter('body_class', array($this,'body_class'), 10, 1);
		add_action( 'nx_notification_image_action', array( $this, 'image_action' ), 999 ); // Image Action for gravatar
	}
    /**
     * Some process take long time to execute
     * for that need to raise the limit.
     */
    public static function raise_limits() {
		wp_raise_memory_limit( 'admin' );
		if ( wp_is_ini_value_changeable( 'max_execution_time' ) ) {
			ini_set( 'max_execution_time', 0 );
		}
		@ set_time_limit( 0 );
	}
	/**
	* Register the stylesheets for the public-facing side of the site.
	*
	* @since    1.0.0
	*/
	public function enqueue_styles() {

		/**
		* This function is provided for demonstration purposes only.
		*
		* An instance of this class should be passed to the run() function
		* defined in NotificationX_Loader as all of the hooks are defined
		* in that particular class.
		*
		* The NotificationX_Loader will then create the relationship
		* between the defined hooks and the functions defined in this
		* class.
		*/

		wp_enqueue_style( $this->plugin_name, NOTIFICATIONX_PUBLIC_URL . 'assets/css/notificationx-public.min.css', array(), $this->version, 'all' );
	}

	/**
	* Register the JavaScript for the public-facing side of the site.
	*
	* @since    1.0.0
	*/
	public function enqueue_scripts() {

		/**
		* This function is provided for demonstration purposes only.
		*
		* An instance of this class should be passed to the run() function
		* defined in NotificationX_Loader as all of the hooks are defined
		* in that particular class.
		*
		* The NotificationX_Loader will then create the relationship
		* between the defined hooks and the functions defined in this
		* class.
		*/

		wp_enqueue_script( $this->plugin_name . '-cookie', NOTIFICATIONX_PUBLIC_URL . 'assets/js/Cookies.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( $this->plugin_name, NOTIFICATIONX_PUBLIC_URL . 'assets/js/notificationx-public.min.js', array( 'jquery' ), $this->version, true );
		wp_localize_script( $this->plugin_name, 'NotificationX', array(
			'ajaxurl' => admin_url('admin-ajax.php')
		) );
	}

	/**
	* Get all active notifications.
	*
	* @since 1.0.0
	* @return void
	*/
	public static function get_active_items() {
		$args = array(
			'post_type'         => 'notificationx',
			'posts_per_page'    => '-1',
			'post_status'		=> 'publish',
			'meta_query'        => array(
				array(
					'key'           => '_nx_meta_active_check',
					'value'         => '1',
					'compare'       => '='
				)
			)
		);
		$posts = get_posts( $args );
		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post ) {
				self::$active[] = $post->ID;
			}
		}

		return self::$active;
	}

	public function pro_extension_ids() {
		return apply_filters('nx_pro_extetion_ids', array());
	}

	public function generate_active_notificationx(){
		if( empty( self::$active ) ) {
			self::$active = self::get_active_items();
		}
		$activeItems = self::$active;
		$conversion_ids = $comments_id = $reviews_id = $download_stats_id = $form_id = $active_notifications = $global_notifications = array();

		foreach( self::$active as $id ) {

			$settings = NotificationX_MetaBox::get_metabox_settings( $id );
			self::generate_css( $settings );

			$logged_in = is_user_logged_in();
			$show_on_display = $settings->show_on_display;

			if( $settings->show_on === 'only_shortcode' ) {
				continue;
			}

			if( ( $logged_in && 'logged_out_user' == $show_on_display ) || ( ! $logged_in && 'logged_in_user' == $show_on_display ) ) {
				continue;
			}

			$locations = $settings->all_locations;

			$check_location = false;

			if( ! empty( $locations ) ) {
				$check_location = NotificationX_Locations::check_location( array( $locations ) );
			}

			$check_location = apply_filters( 'nx_check_location', $check_location, $settings );

			if( $settings->show_on == 'on_selected' ) {
				// show if the page is on selected
				if ( ! $check_location ) {
					continue;
				}
			} elseif( $settings->show_on == 'hide_on_selected' ) {
				// hide if the page is on selected
				if ( $check_location ) {
					continue;
				}
			}
			/**
			* Check for hiding in mobile device
			*/
			if( wp_is_mobile() && $settings->hide_on_mobile ) {
				continue;
			}

			$add_in_list = apply_filters( 'nx_add_in_queue', $settings->display_type, $settings );

			$active_global_queue = boolval( $settings->global_queue_active );

			switch ( $add_in_list  ) {
				case "press_bar":
					NotificationX_PressBar_Extension::display( $settings );
					break;
				// case "conversions" :
				// 	$conversion_ids[] = $id;
				// 	break;
				// case 'elearning':
				// 	$conversion_ids[] = $id;
				// 	break;
				// case 'donation':
				// 	$conversion_ids[] = $id;
				// 	break;
				// case "comments":
				// 	$comments_id[] = $id;
				// 	break;
				// case "reviews":
				// 	$reviews_id[] = $id;
				// 	break;
				// case "download_stats":
				// 	$download_stats_id[] = $id;
				// 	break;
				// case "form":
				// 	$form_id[] = $id;
				// 	break;
				default:
					if( $active_global_queue && NX_CONSTANTS::is_pro() ) {
						$global_notifications[] = $id;
					} else {
						$active_notifications[] = $id;
					}
					break;
			}
			unset( $activeItems[ $id ] );
		}
		do_action( 'nx_active_notificationx', $activeItems );
		$pro_ext = $this->pro_extension_ids();
		/**
		* Filtered Active IDs
		*/
		$conversion_ids    = apply_filters('nx_conversions_id', $conversion_ids );
		$comments_id       = apply_filters('nx_comments_id', $comments_id );
		$reviews_id        = apply_filters('nx_reviews_id', $reviews_id );
		$download_stats_id = apply_filters('nx_download_stats_id', $download_stats_id );
		$form_id 		   = apply_filters('nx_form_id', $form_id );

		// if( ! empty( $form_id ) || ! empty( $conversion_ids ) || ! empty( $comments_id ) || ! empty( $pro_ext ) || ! empty( $reviews_id ) || ! empty( $download_stats_id ) ) :
			?>
			<script type="text/javascript">
			var notificationx = {
				nonce               : '<?php echo wp_create_nonce('nx_frontend_nonce'); ?>',
				ajaxurl             : '<?php echo admin_url('admin-ajax.php'); ?>',
				notificatons        : <?php echo json_encode( $active_notifications ); ?>,
				global_notifications: <?php echo json_encode( $global_notifications ); ?>,
				conversions         : <?php echo json_encode( $conversion_ids ); ?>,
				comments            : <?php echo json_encode( $comments_id ); ?>,
				reviews             : <?php echo json_encode( $reviews_id ); ?>,
				stats               : <?php echo json_encode( $download_stats_id ); ?>,
				form                : <?php echo json_encode( $form_id ); ?>,
				pro_ext             : <?php echo json_encode( $pro_ext ); ?>,
			};
			</script>
			<?php
		// endif;
	}

	public function generate_conversions() {
		if( ! isset( $_POST['nonce'] ) || ( isset( $_POST['nonce'] ) && ! wp_verify_nonce( $_POST['nonce'], 'nx_frontend_nonce' ) ) ) {
			return;
		}

		$ids = $_POST['ids'];

		$echo = $data = [];
		if( ! empty( $this->notifications ) ) {
			$data = $this->notifications;
		}

		$global = ( isset( $_POST['global'] ) && $_POST['global'] === "true" ) ? true && NX_CONSTANTS::is_pro() : false;
		self::raise_limits();

		if( ! $global ) {
			$settings = NotificationX_MetaBox::get_metabox_settings( $ids );
			$echo['config'] = apply_filters('nx_frontend_config', array(
				'delay_before'  => ( ! empty( $settings->delay_before ) ) ? intval( $settings->delay_before ) * 1000 : 0,
				'display_for'   => ( ! empty( $settings->display_for ) ) ? intval( $settings->display_for ) * 1000 : 0,
				'delay_between' => ( ! empty( $settings->delay_between ) ) ? intval( $settings->delay_between ) * 1000 : 0,
				'loop'          => ( ! empty( $settings->loop ) ) ? $settings->loop : 0,
				'id'            => $ids,
				'analytics'     => boolval( NotificationX_DB::get_settings( 'enable_analytics' ) )
			), $settings);

			ob_start();
			include NOTIFICATIONX_PUBLIC_PATH . 'partials/nx-public-display.php';
			$content = ob_get_clean();
			$echo['content'] = $content;
			echo json_encode( $echo );
			wp_die();
		} else {
			$ids = explode( ',',  $ids);
			if( ! empty( $ids ) ) {

				$all_data = $this->single_data = $new_data = [];

				foreach( $ids as $id ) {
					$settings = $this->settings = NotificationX_MetaBox::get_metabox_settings( $id );
					$type = $extension_name = $key = '';
					$type = $extension_name = $key = NotificationX_Helper::get_type( $settings );
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
					}
					if( is_array( $new_data ) && ! empty( $new_data ) ) {
						array_walk( $new_data, function( $item, $key ) {
							$this->single_data[ $key ] = $item;
							$this->single_data[ $key ]['settings'] = $this->settings;
						});
					}

					// $this->single_data = $new_data;

					if( empty( $all_data ) ) {
						$all_data = $this->single_data;
					} else {
						$all_data = array_merge( $all_data, $this->single_data );
					}
				}

				// sorting
				$this->limiter = new NotificationX_Array();
				$limit = intval( NotificationX_DB::get_settings( 'cache_limit' ) );
				if( $limit <= 0 ) {
					$limit = 100;
				}
				$this->limiter->setLimit( $limit );
				$this->limiter->sortBy = 'timestamp';
				$this->limiter->setValues( $all_data );
				$all_data = $this->limiter->values();

				ob_start();
				include NOTIFICATIONX_PUBLIC_PATH . 'partials/nx-public-global-display.php';
				$content = ob_get_clean();
				$echo['content'] = $content;
			}

			$saved_settings = NotificationX_DB::get_settings();

			$echo['config'] = apply_filters('nx_frontend_config', array(
				'delay_before'  => isset( $saved_settings['delay_before'] ) ? intval( $saved_settings['delay_before'] ) * 1000 : 0,
				'display_for'   => isset( $saved_settings['display_for'] ) ? intval( $saved_settings['display_for'] ) * 1000 : 0,
				'delay_between' => isset( $saved_settings['delay_between'] ) ? intval( $saved_settings['delay_between'] ) * 1000 : 0,
				'loop'          => 1,
				'id'            => null,
				'analytics'     => boolval( NotificationX_DB::get_settings( 'enable_analytics' ) )
			), []);

			echo json_encode( $echo );
			wp_die();
		}
	}

	public function get_client_ip() {
		$ip = '';

		if( getenv( 'HTTP_CLIENT_IP' ) ) {
			$ip = getenv( 'HTTP_CLIENT_IP' );
		} elseif( getenv( 'HTTP_X_FORWARDED_FOR' ) ) {
			$ip = getenv( 'HTTP_X_FORWARDED_FOR' );
		} elseif( getenv( 'HTTP_X_FORWARDED' ) ) {
			$ip = getenv( 'HTTP_X_FORWARDED' );
		} elseif( getenv( 'HTTP_FORWARDED_FOR' ) ) {
			$ip = getenv( 'HTTP_FORWARDED_FOR' );
		} elseif( getenv( 'HTTP_FORWARDED' ) ) {
			$ip = getenv( 'HTTP_FORWARDED' );
		} elseif( getenv( 'REMOTE_ADDR' ) ) {
			$ip = getenv( 'REMOTE_ADDR' );
		} else {
			$ip = "UNKNOWN";
		}

		return $ip;
	}

	public static function generate_css( $settings ){
		echo NotificationX_Advanced_Style::generate_css( $settings );
	}
	/**
	* This function is responsible for generate css for preview
	*
	* @param stdClass $settings
	* @param string $key
	* @return string
	*/
	public static function generate_preview_css( $settings, $key = 'wrapper' ){
		if( empty( $settings ) ) return;
		$style = $image_style = $content_style = $first_row_font = $second_row_font = $third_row_font = [];
		$css_string = $css = '';

		switch( $settings->display_type ) {
			case 'conversions' :
				if( $settings->advance_edit ) {
					$style[ 'wrapper' ][] = ! empty( $settings->bg_color ) ? 'background-color: ' . $settings->bg_color : '';
					$style[ 'wrapper' ][] = ! empty( $settings->text_color ) ? 'color: ' . $settings->text_color : '';

					if( $settings->border ){
						$style[ 'wrapper' ][] = ! empty( $settings->border_size ) ? 'border-width: ' . $settings->border_size . 'px !important' : '';
						$style[ 'wrapper' ][] = ! empty( $settings->border_style ) ? 'border-style: ' . $settings->border_style . ' !important': '';
						$style[ 'wrapper' ][] = ! empty( $settings->border_color ) ? 'border-color: ' . $settings->border_color  . ' !important': '';
					}

					if( ! empty( $settings->first_font_size ) ) {
						$style['first-row'][] = 'font-size: ' . $settings->first_font_size . 'px';
					}
					if( ! empty( $settings->second_font_size ) ) {
						$style['second-row'][] = 'font-size: ' . $settings->second_font_size . 'px';
					}
					if( ! empty( $settings->third_font_size ) ) {
						$style['third-row'][] = 'font-size: ' . $settings->third_font_size . 'px';
					}

					return 'style="'. implode( '; ', $style[ $key ] ) .'"';
				}
				break;
			case 'comments' :
				if( $settings->comment_advance_edit ) {
					$style[ 'wrapper' ][] = ! empty( $settings->comment_bg_color ) ? 'background-color: ' . $settings->comment_bg_color : '';
					$style[ 'wrapper' ][] = ! empty( $settings->comment_text_color ) ? 'color: ' . $settings->comment_text_color : '';

					if( $settings->comment_border ){
						$style[ 'wrapper' ][] = ! empty( $settings->comment_border_size ) ? 'border-width: ' . $settings->comment_border_size . 'px !important' : '';
						$style[ 'wrapper' ][] = ! empty( $settings->comment_border_style ) ? 'border-style: ' . $settings->comment_border_style . ' !important' : '';
						$style[ 'wrapper' ][] = ! empty( $settings->comment_border_color ) ? 'border-color: ' . $settings->comment_border_color . ' !important' : '';
					}

					if( ! empty( $settings->comment_first_font_size ) ) {
						$style['first-row'][] = 'font-size: ' . $settings->comment_first_font_size . 'px';
					}
					if( ! empty( $settings->comment_second_font_size ) ) {
						$style['second-row'][] = 'font-size: ' . $settings->comment_second_font_size . 'px';
					}
					if( ! empty( $settings->comment_third_font_size ) ) {
						$style['third-row'][] = 'font-size: ' . $settings->comment_third_font_size . 'px';
					}
					return 'style="'. implode( '; ', $style[ $key ] ) .'"';
				}
				break;
			default :
				return '';
				break;
		}
	}
	/**
	* This function is responsible for generate css for preview
	* its use when advance edit button is clicked.
	* @param stdClass $settings
	* @param string $key
	* @return string
	*/
	public static function generate_css_for_preview( $settings, $key = 'wrapper' ){
		if( empty( $settings ) ) return;
		$style = $image_style = $content_style = $first_row_font = $second_row_font = $third_row_font = [];
		$css_string = $css = '';

		switch( $settings->display_type ) {
			case 'conversions' :
				$style[ 'wrapper' ][] = ! empty( $settings->bg_color ) ? 'background-color: ' . $settings->bg_color : '';
				$style[ 'wrapper' ][] = ! empty( $settings->text_color ) ? 'color: ' . $settings->text_color : '';

				if( $settings->border ){
					$style[ 'wrapper' ][] = ! empty( $settings->border_size ) ? 'border-width: ' . $settings->border_size . 'px' : '';
					$style[ 'wrapper' ][] = ! empty( $settings->border_style ) ? 'border-style: ' . $settings->border_style : '';
					$style[ 'wrapper' ][] = ! empty( $settings->border_color ) ? 'border-color: ' . $settings->border_color : '';
				}

				if( ! empty( $settings->first_font_size ) ) {
					$style['first-row'][] = 'font-size: ' . $settings->first_font_size . 'px';
				}
				if( ! empty( $settings->second_font_size ) ) {
					$style['second-row'][] = 'font-size: ' . $settings->second_font_size . 'px';
				}
				if( ! empty( $settings->third_font_size ) ) {
					$style['third-row'][] = 'font-size: ' . $settings->third_font_size . 'px';
				}
				return implode( ';', $style[ $key ] );
				break;
			case 'comments' :
				$style[ 'wrapper' ][] = ! empty( $settings->comment_bg_color ) ? 'background-color: ' . $settings->comment_bg_color : '';
				$style[ 'wrapper' ][] = ! empty( $settings->comment_text_color ) ? 'color: ' . $settings->comment_text_color : '';

				if( $settings->comment_border ){
					$style[ 'wrapper' ][] = ! empty( $settings->comment_border_size ) ? 'border-width: ' . $settings->comment_border_size . 'px' : '';
					$style[ 'wrapper' ][] = ! empty( $settings->comment_border_style ) ? 'border-style: ' . $settings->comment_border_style : '';
					$style[ 'wrapper' ][] = ! empty( $settings->comment_border_color ) ? 'border-color: ' . $settings->comment_border_color : '';
				}

				if( ! empty( $settings->comment_first_font_size ) ) {
					$style['first-row'][] = 'font-size: ' . $settings->comment_first_font_size . 'px';
				}
				if( ! empty( $settings->comment_second_font_size ) ) {
					$style['second-row'][] = 'font-size: ' . $settings->comment_second_font_size . 'px';
				}
				if( ! empty( $settings->comment_third_font_size ) ) {
					$style['third-row'][] = 'font-size: ' . $settings->comment_third_font_size . 'px';
				}
				return implode( ';', $style[ $key ] );
				break;
			default :
				return '';
				break;
		}
	}
    /**
     * This function added css class in body for bar theme
     * @hooked body_class
     * @param array $classes
     * @return array
     */
    public function body_class($classes){

        if(!empty(self::$active)){
            foreach (self::$active as $active_nx){
                $type = get_post_meta($active_nx, '_nx_meta_display_type', true);
                if($type == 'press_bar'){
                    $classes[] = 'nx-'. $type . '-active';
                    $classes[] = 'nx-'. $type . '-active-' . $active_nx;
                }
            }
        }

		return $classes;
	}


    /**
     * Image Action
     */
    public function image_action(){
        add_filter( 'nx_notification_image', array( $this, 'notification_image' ), 999, 3 );
    }

    public function notification_image( $image_data, $data, $settings ){
        if( $settings->display_type  === 'press_bar' ) {
            return [];
		}

		$alt_title = '';

		$is_default_enabled = true;
		if( $settings->show_notification_image != 'none' ) {
			if( isset( $image_data['url'] ) && ! empty( $image_data['url'] ) ) {
				$is_default_enabled = false;
			}
		}

		if( $settings->show_default_image && $is_default_enabled ) {
			$default_avatar = $settings->default_avatar;
			if( $default_avatar === 'none' ) {
				if( ! empty( $settings->image_url['url'] ) ) {
					$image = wp_get_attachment_image_src( $settings->image_url['id'], 'medium', true );
					$image_data['url'] = $image[0];
				}
			} else {
				$image_data['url'] = NOTIFICATIONX_PUBLIC_URL . 'assets/img/icons/' . $default_avatar;
			}
		}
		// Fallback for uploaded Image.
		if( $settings->show_default_image && isset( $settings->image_url ) && ! empty( $settings->image_url['url'] ) ) {
			$image = wp_get_attachment_image_src( $settings->image_url['id'], 'medium', true );
			$image_data['url'] = $image[0];
		}

		if( ! $settings->show_default_image ) {
			if( $settings->show_notification_image === 'gravatar' ) {
				if( isset( $data['email'] ) ) {
					$avatar = get_avatar( $data['email'], 100, '', $alt_title, array( 'extra_attr' => 'title="'. $alt_title .'"' ) );
				}
				$image_data['gravatar'] = true;
				$image_data['url'] = $avatar;
			}
		}

		$image_data['alt'] = '';
        return $image_data;
    }
}
