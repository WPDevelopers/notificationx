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

		// add_action( 'fomopress_after_public_action', array( $this, 'fomopress_get_conversions' ) );

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

		wp_enqueue_style( $this->plugin_name, FOMOPRESS_PUBLIC_URL . 'assets/css/fomopress-public.css', array(), $this->version, 'all' );

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

		wp_enqueue_script( $this->plugin_name, FOMOPRESS_PUBLIC_URL . 'assets/js/fomopress-public.js', array( 'jquery' ), $this->version, true );

	}

        /**
    	 * Get all active notifications.
    	 *
    	 * @since 1.0.0
    	 * @return void
    	 */
        public function get_active_items() {
            // WP Query arguments.
            $args = array(
                'post_type'         => 'fomopress',
				'posts_per_page'    => '-1',
				'post_status'		=> 'publish',
            );

            // Get the notification posts.
            $posts = get_posts( $args );

            if ( count( $posts ) ) {
                foreach ( $posts as $post ) {
                    self::$active[] = $post->ID;
                }
			}
		}
		
		public function display(){

			if( empty( self::$active ) ) {
				return;
			}

			$conversion_ids = $comments_id = array();
			
			foreach( self::$active as $id ) {
				
				$settings = FomoPress_MetaBox::get_metabox_settings( $id );

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
                    default:
                        break;
                }

			}

			if( ! empty( $conversion_ids ) || ! empty( $comments_id ) ) :
		?>
			<script type="text/javascript">
				var fomopress = {
					nonce      : '<?php echo wp_create_nonce('fomopress_frontend_nonce'); ?>',
					ajaxurl    : '<?php echo admin_url('admin-ajax.php'); ?>',
					conversions: <?php echo json_encode( $conversion_ids ); ?>,
					comments   : <?php echo json_encode( $comments_id ); ?>
				};
			</script>
		<?php	
			endif;
		}

		public function fomopress_get_conversions() {

			if( ! isset( $_POST['nonce'] ) && ! wp_verify_nonce( $_POST['nonce'], 'fomopress_frontend_nonce' ) ) {
				return;
			}

			$ids = $_POST['ids'];

			$echo = $data = [];
			if( ! empty( $this->notifications ) ) {
				$data = $this->notifications;
			}

			foreach( $ids as $id ) {

				$settings = FomoPress_MetaBox::get_metabox_settings( $id );

				// dump( $settings );

				// // dump( FomoPress_Template::template( $settings->notification_template ) );

				// die();

				$echo['config'] = array(
					'delay_before' => $settings->delay_before,
					'display_for' => $settings->display_for,
					'delay_between' => $settings->delay_between,
					'loop' => $settings->loop,
					'id' => $id,
				);

				ob_start();
				include FOMOPRESS_PUBLIC_PATH . 'partials/fomopress-public-display.php';
				$content = ob_get_clean();

				$echo['content'] = $content;
			}

			// echo json_encode( $echo );

			// die();
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

}
