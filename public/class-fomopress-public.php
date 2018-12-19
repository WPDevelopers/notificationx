<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://wpdeveloper.net
 * @since      1.0.0
 *
 * @package    FomoPress
 * @subpackage FomoPress/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    FomoPress
 * @subpackage FomoPress/public
 * @author     WPDeveloper <support@wpdeveloper.net>
 */
class FomoPress_Public {

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

	public static $active;

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
		
		$this->notifications = get_option('fomopress_notifications');
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
		 * defined in FomoPress_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The FomoPress_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, NOTIFICATIONX_PUBLIC_URL . 'assets/css/fomopress-public.css', array(), $this->version, 'all' );
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
		 * defined in FomoPress_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The FomoPress_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name . '-cookie', NOTIFICATIONX_PUBLIC_URL . 'assets/js/Cookies.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( $this->plugin_name, NOTIFICATIONX_PUBLIC_URL . 'assets/js/fomopress-public.js', array( 'jquery' ), $this->version, true );
	}

	/**
	 * Get all active notifications.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function get_active_items() {
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
	}
	
	public function generate_active_fomo(){

		if( empty( self::$active ) ) {
			return;
		}
		$activeItems = self::$active;
		$conversion_ids = $comments_id = array();

		foreach( self::$active as $id ) {
			
			$settings = FomoPress_MetaBox::get_metabox_settings( $id );

			$logged_in = is_user_logged_in();
			$show_on_display = $settings->show_on_display;

			if( ( $logged_in && 'logged_out_user' == $show_on_display ) || ( ! $logged_in && 'logged_in_user' == $show_on_display ) ) {
				continue;
			}

			$locations = $settings->all_locations;

			$check_location = false;

			if( ! empty( $locations ) ) {
				$check_location = FomoPress_Locations::check_location( array( $locations ) );
			}

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

			switch ( $settings->display_type ) {
				case "press_bar":
					FomoPress_PressBar_Extension::display( $settings );
					break;
				case "conversions":
					$conversion_ids[] = $id;
					break;
				case "comments":
					$comments_id[] = $id;
					break;
			}
			
			self::generate_css( $settings );
			unset( $activeItems[ $id ] );
		}
		function pro_extension_ids() {
			return apply_filters('fomopress_pro_extetion_ids', array());
		}
		do_action( 'fomopress_active_fomo', $activeItems );
		$pro_ext = pro_extension_ids();
		/**
		 * Filtered Active IDs
		 */
		$conversion_ids = apply_filters( 'fomopress_conversions_id', $conversion_ids );
		$comments_id = apply_filters( 'fomopress_comments_id', $comments_id );

		if( ! empty( $conversion_ids ) || ! empty( $comments_id ) || ! empty( $pro_ext ) ) :
		?>
			<script type="text/javascript">
				var fomopress = {
					nonce      : '<?php echo wp_create_nonce('fomopress_frontend_nonce'); ?>',
					ajaxurl    : '<?php echo admin_url('admin-ajax.php'); ?>',
					conversions: <?php echo json_encode( $conversion_ids ); ?>,
					comments   : <?php echo json_encode( $comments_id ); ?>,
					pro_ext   : <?php echo json_encode( $pro_ext ); ?>,
				};
			</script>
		<?php	
		endif;
	}

	public function generate_conversions() {

		if( ! isset( $_POST['nonce'] ) && ! wp_verify_nonce( $_POST['nonce'], 'fomopress_frontend_nonce' ) ) {
			return;
		}

		$ids = $_POST['ids'];

		$echo = $data = [];
		if( ! empty( $this->notifications ) ) {
			$data = $this->notifications;
		}

		$settings = FomoPress_MetaBox::get_metabox_settings( $ids );

		$echo['config'] = array(
			'delay_before'  => ( ! empty( $settings->delay_before ) ) ? intval( $settings->delay_before ) * 1000 : 0,
			'display_for'   => ( ! empty( $settings->display_for ) ) ? intval( $settings->display_for ) * 1000 : 0,
			'delay_between' => ( ! empty( $settings->delay_between ) ) ? intval( $settings->delay_between ) * 1000 : 0,
			'loop'          => ( ! empty( $settings->loop ) ) ? $settings->loop : 0,
			'id'            => $ids,
		);

		ob_start();
		include NOTIFICATIONX_PUBLIC_PATH . 'partials/fomopress-public-display.php';
		$content = ob_get_clean();
		$echo['content'] = $content;

		echo json_encode( $echo );
		wp_die();
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
		if( empty( $settings ) ) return;
		$style = $image_style = $content_style = $first_row_font = $second_row_font = $third_row_font = [];
		$css_string = $css = '';

		switch( $settings->display_type ){
			case 'conversions' : 
				if( $settings->advance_edit ) {
					$style[] = 'background-color: ' . $settings->bg_color;
					$style[] = 'color: ' . $settings->text_color;
					
					if( $settings->border ){
						$style[] = 'border-width: ' . $settings->border_size . 'px';
						$style[] = 'border-style: ' . $settings->border_style;
						$style[] = 'border-color: ' . $settings->border_color;
					}
		
					if( ! empty( $settings->first_font_size ) ) {
						$first_row_font[] = 'font-size: ' . $settings->first_font_size . 'px';
					}
					if( ! empty( $settings->second_font_size ) ) {
						$second_row_font[] = 'font-size: ' . $settings->second_font_size . 'px';
					}
					if( ! empty( $settings->third_font_size ) ) {
						$third_row_font[] = 'font-size: ' . $settings->third_font_size . 'px';
					}
		
					if( $settings->image_position == 'right' ) {
						$style[] = 'flex-direction: row-reverse';
					}
				}
				break;
			case 'comments' : 
				if( $settings->comment_advance_edit ) {
					$style[] = 'background-color: ' . $settings->comment_bg_color;
					$style[] = 'color: ' . $settings->comment_text_color;
					
					if( $settings->comment_border ){
						$style[] = 'border-width: ' . $settings->comment_border_size . 'px';
						$style[] = 'border-style: ' . $settings->comment_border_style;
						$style[] = 'border-color: ' . $settings->comment_border_color;
					}
		
					if( ! empty( $settings->comment_first_font_size ) ) {
						$first_row_font[] = 'font-size: ' . $settings->comment_first_font_size . 'px';
					}
					if( ! empty( $settings->comment_second_font_size ) ) {
						$second_row_font[] = 'font-size: ' . $settings->comment_second_font_size . 'px';
					}
					if( ! empty( $settings->comment_third_font_size ) ) {
						$third_row_font[] = 'font-size: ' . $settings->comment_third_font_size . 'px';
					}
		
					if( $settings->comment_image_position == 'right' ) {
						$style[] = 'flex-direction: row-reverse';
					}
				}
				break;
			case 'press_bar' : 
				if( $settings->bar_advance_edit ) {
					$style[] = 'background-color: ' . $settings->bar_bg_color;
					$style[] = 'color: ' . $settings->bar_text_color;
					$style[] = 'font-size: ' . $settings->bar_font_size . 'px';
				}
				break;
		}

		

		$style = apply_filters( 'fomopress_style', $style );
		do_action( 'fomopress_style_generation' );

		if( ! empty( $style ) ) {
			$css_string .= '.fomopress-customize-style-' . $settings->id . '{' . implode( ';', $style ) . '}';
		}

		if( ! empty( $content_style ) ) {
			$css_string .= '.fomopress-customize-style-' . $settings->id . ' .fomopress-notification-content {' . implode( ';', $content_style ) . '}';
		}

		if( ! empty( $first_row_font ) ) {
			$css_string .= '.fomopress-customize-style-' . $settings->id . ' .fp-first-row {' . implode( ';', $first_row_font ) . '}';
		}
		if( ! empty( $second_row_font ) ) {
			$css_string .= '.fomopress-customize-style-' . $settings->id . ' .fp-second-row {' . implode( ';', $second_row_font ) . '}';
		}
		if( ! empty( $third_row_font ) ) {
			$css_string .= '.fomopress-customize-style-' . $settings->id . ' .fp-third-row {' . implode( ';', $third_row_font ) . '}';
		}

		if( ! empty( $css_string ) ) {
			$css .= '<style type="text/css">';
				$css .= $css_string;
			$css .= '</style>';
		}

		echo ! empty( $css ) ? $css : '';
	}

	public static function generate_preview_css( $settings, $key = 'wrapper' ){
		if( empty( $settings ) ) return;
		$style = $image_style = $content_style = $first_row_font = $second_row_font = $third_row_font = [];
		$css_string = $css = '';


		switch( $settings->display_type ) {
			case 'conversions' : 
				if( $settings->advance_edit ) {
					$style[ 'wrapper' ][] = 'background-color: ' . $settings->bg_color;
					$style[ 'wrapper' ][] = 'color: ' . $settings->text_color;
					
					if( $settings->border ){
						$style[ 'wrapper' ][] = 'border-width: ' . $settings->border_size . 'px';
						$style[ 'wrapper' ][] = 'border-style: ' . $settings->border_style;
						$style[ 'wrapper' ][] = 'border-color: ' . $settings->border_color;
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
		
					if( $settings->image_position == 'right' ) {
						$style[ 'wrapper' ][] = 'flex-direction: row-reverse';
					}
					echo 'style="'. implode( '; ', $style[ $key ] ) .'"';
				}
				break;

			case 'comments' : 
				if( $settings->comment_advance_edit ) {
					$style[ 'wrapper' ][] = 'background-color: ' . $settings->comment_bg_color;
					$style[ 'wrapper' ][] = 'color: ' . $settings->comment_text_color;
					
					if( $settings->comment_border ){
						$style[ 'wrapper' ][] = 'border-width: ' . $settings->comment_border_size . 'px';
						$style[ 'wrapper' ][] = 'border-style: ' . $settings->comment_border_style;
						$style[ 'wrapper' ][] = 'border-color: ' . $settings->comment_border_color;
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
		
					if( $settings->comment_image_position == 'right' ) {
						$style[ 'wrapper' ][] = 'flex-direction: row-reverse';
					}
					echo 'style="'. implode( '; ', $style[ $key ] ) .'"';
				}
				break;
			default : 
				echo '';
				break;
		}
	}
}
