<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wpdeveloper.net
 * @since      1.0.0
 *
 * @package    FomoPress
 * @subpackage FomoPress/admin
 * @author     WPDeveloper <support@wpdeveloper.net>
 */

class FomoPress_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;
	/**
	 * All builder args
	 *
	 * @var array
	 */
	private $builder_args;
	/**
	 * Builder Metabox ID
	 *
	 * @var string
	 */
	private $metabox_id;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The type.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var string the post type of fomopress.
	 */
	public $type = 'notificationx';

	public $metabox;

	public static $prefix = 'nx_meta_';

	public static $settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		self::$settings = FomoPress_DB::get_settings();
	}
	/**
	 * Get all active items.
	 *
	 * @return void
	 */
	public static function get_active_items() {
		// WP Query arguments.
		$args = array(
			'post_type'         => 'notificationx',
			'posts_per_page'    => '-1',
			'post_status'		=> 'publish',
		);
		$active = [];
		// Get the notification posts.
		$posts = get_posts( $args );

		if ( count( $posts ) ) {
			foreach ( $posts as $post ) {
				$settings = FomoPress_MetaBox::get_metabox_settings( $post->ID );
				$type = ( $settings->display_type != 'conversions' ) ? $settings->display_type : $settings->conversion_from;

				$active[ $type ][] = $post->ID;
			}
		}

		return $active;
	}
	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles( $hook ) {
		global $post_type;
		$page_status = false;
		wp_enqueue_style( 
			$this->plugin_name . '-admin-global', 
			NOTIFICATIONX_ADMIN_URL . 'assets/css/fomopress-admin-global.css', 
			array(), $this->version, 'all' 
		);
		if( $hook == 'notificationx_page_nx-builder' || $hook == 'notificationx_page_nx-settings' ) {
			$page_status = true;
		}

		if( $post_type != $this->type && ! $page_status ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 
			$this->plugin_name . '-select2', 
			NOTIFICATIONX_ADMIN_URL . 'assets/css/select2.min.css', 
			array(), $this->version, 'all' 
		);
		wp_enqueue_style( 
			$this->plugin_name, 
			NOTIFICATIONX_ADMIN_URL . 'assets/css/fomopress-admin.css', 
			array(), $this->version, 'all' 
		);
	}
	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts( $hook ) {
		global $post_type;
		$page_status = false;
		if( $hook == 'notificationx_page_nx-builder' || $hook == 'notificationx_page_nx-settings' ) {
			$page_status = true;
		}

		if( $post_type != $this->type && ! $page_status ) {
			return;
		}

		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_media();
		wp_enqueue_script( 
			$this->plugin_name . '-sweetalert', 
			NOTIFICATIONX_ADMIN_URL . 'assets/js/sweetalert.min.js', 
			array( 'jquery' ), $this->version, true 
		);
		wp_enqueue_script( 
			$this->plugin_name . '-select2', 
			NOTIFICATIONX_ADMIN_URL . 'assets/js/select2.min.js', 
			array( 'jquery' ), $this->version, true 
		);
		wp_enqueue_script( 
			$this->plugin_name, 
			NOTIFICATIONX_ADMIN_URL . 'assets/js/fomopress-admin.js', 
			array( 'jquery' ), $this->version, true 
		);
	}

	public function custom_columns( $columns ) {
		$title_column = $columns['title'];
		$date_column = $columns['date'];

		unset( $columns['title'] );
		unset( $columns['date'] );

		$columns['notification_status'] = __('Enable / Disable', 'notificationx');
		$columns['title'] = $title_column;

		$columns['notification_type']   = __('Type', 'notificationx');

		$columns['date'] = $date_column;

		return apply_filters( 'fomopress_post_columns', $columns );
	}

	public function manage_custom_columns( $column, $post_id ){
		switch ( $column ) {
			case 'notification_type':
				$type = get_post_meta( $post_id, '_fomopress_display_type', true );
				if ( $type ) {
					$type = FomoPress_Helper::notification_types( $type );
					if( $type !== 'Conversions' ) {
						echo $type;
					} else {
						$from = get_post_meta( $post_id, '_fomopress_conversion_from', true );
						echo $type . ' - ' . FomoPress_Helper::conversion_from( $from );
					}
				}
				break;
			case 'notification_status':
				$status = get_post_meta( $post_id, '_nx_meta_active_check', true );
				self::notification_toggle( $status, $post_id );
				break;
		}

		do_action( 'fomopress_post_columns_content', $column, $post_id );
	}

	public static function notification_toggle( $status = '1', $post_id ){
		$text           = __('Active', 'notificationx');
		$img_active     = NOTIFICATIONX_ADMIN_URL . 'assets/img/active1.png';
		$img_inactive   = NOTIFICATIONX_ADMIN_URL . 'assets/img/active0.png';
		$active         = 'true';
		$img            = $img_active;

		if ( ! $status ) {
			$text   = __('Inactive', 'notificationx');
			$img    = $img_inactive;
			$active = 'false';
		}
		?>
		<img 
			src="<?php echo $img; ?>" 
			style="cursor: pointer; height: 16px; vertical-align: middle;" 
			alt="<?php echo $text; ?>" title="<?php echo $text; ?>" 
			data-nonce="<?php echo wp_create_nonce('fomopress_notification_toggle_status'); ?>" 
			data-post="<?php echo $post_id; ?>" />
		<?php
	}

	public function notification_status(){
		$error = false;

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'fomopress_notification_toggle_status' ) ) {
			$error = true;
		}

		if ( ! isset( $_POST['post_id'] ) || empty( $_POST['post_id'] ) || ! absint( $_POST['post_id'] ) ) {
			$error = true;
		}

		if ( $error ) {
			echo __('There is an error updating status.', 'notificationx');
			die();
		}

		$post_id = absint( $_POST['post_id'] );
		$status = $_POST['status'] == 'active' ? '1' : '0';

		update_post_meta( $post_id, '_nx_meta_active_check', $status );

		echo 'success';
		die();
	}
	/**
	 * Register the FomoPress custom post type.
	 *
	 * @since	1.0.0
	 */
	public function fomopress_type_register(){

		$labels = array(
			'name'                => 'NotificationX',
			'singular_name'       => 'NotificationX',
			'add_new'             => esc_html__( 'Add New', 'notificationx' ) ,
			'add_new_item'        => esc_html__( 'Add New', 'notificationx' ),
			'edit_item'           => esc_html__( 'Edit', 'notificationx' ),
			'new_item'            => esc_html__( 'New', 'notificationx' ),
			'view_item'           => esc_html__( 'View', 'notificationx' ),
			'search_items'        => esc_html__( 'Search', 'notificationx' ),
			'not_found'           => esc_html__( 'No notification x is found', 'notificationx' ),
			'not_found_in_trash'  => esc_html__( 'No notification x is found in Trash', 'notificationx' ),
			'menu_name'           => 'NotificationX',
		);

		$args = array(
			'labels'              => $labels,
			'hierarchical'        => false,
			'description'         => '',
			'taxonomies'          => array( '' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'notificationx',
			'show_in_admin_bar'   => true,
			'show_in_rest'        => false,
			'menu_position'       => 80,
			'menu_icon'           => NOTIFICATIONX_ADMIN_URL . 'assets/img/fomopress-menu-icon.png',
			'show_in_nav_menus'   => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => '',
			'capability_type'     => 'post',
			'supports'            => array( 'title' ),
		);

		register_post_type( $this->type, $args );
		add_image_size( "_nx_notification_thumb", 100, 100, true );
	}

	/**
	 * Admin Menu Page
	 *
	 * @return void
	 */
	public function fomopress_admin_menu_page(){

		$settings_class = new FomoPress_Settings();

		$settings = apply_filters( 'notificationx_admin_menu', array(
			'nx-settings'   => array(
				'title'      => __('Settings', 'notificationx'),
				'capability' => 'delete_users',
				'callback'   => array( $settings_class, 'settings_page' )
			),
			'nx-builder'   => array(
				'title'      => __('Quick Builder', 'notificationx'),
				'capability' => 'delete_users',
				'callback'   => array( $this, 'quick_builder' )
			),
		) );

		$this->builder_args = FomoPress_MetaBox::get_builder_args();
		$this->metabox_id   = $this->builder_args['id'];
		$flag         = true;
		/**
		 * Add Submit
		 */
		if( isset( $_POST[ 'fomopress_builder_add_submit' ] ) ) :
			if ( ! isset( $_POST[$this->metabox_id . '_nonce'] ) || ! wp_verify_nonce( $_POST[$this->metabox_id . '_nonce'], $this->metabox_id ) ) {
				$flag = false;
			}

			if( $flag ) {

				if( $_POST['nx_display_type'] == 'press_bar' )  {
					$title = __('Fomo - Notification Bar', 'notificationx');
				} elseif( $_POST['nx_display_type'] == 'comments' )  {
					$title = __('Fomo - WP Comments', 'notificationx');
				} elseif( $_POST['nx_display_type'] == 'conversions' )  {
					$conversions = FomoPress_Helper::conversion_from();
					$title = 'Fomo - ' . $conversions[$_POST['nx_conversion_from']];
				}
				$_POST['post_type'] = 'notificationx';
				$postdata = array(
					'post_type'   => 'notificationx',
					'post_title'  => $title . ' - ' . date( get_option( 'date_format' ), current_time( 'timestamp' ) ),
					'post_status' => 'publish',
					'post_author' => get_current_user_id()
				);
	
				$p_id = wp_insert_post($postdata);
				if( $p_id || ! is_wp_error( $p_id ) ) {
					do_action( 'fomopress_before_builder_submit', $_POST );
					// saving builder meta data with post
					FomoPress_MetaBox::save_data( $this->builder_data( $_POST ), $p_id );
					/**
					 * Safely Redirect to FomoPress Page
					 */
					wp_safe_redirect( add_query_arg( array(
						'post_type' => 'notificationx',
					), admin_url( 'edit.php' ) ) );
				}
			}
		endif;
		add_menu_page( 'NotificationX', 'NotificationX', 'delete_users', 'notificationx', '', NOTIFICATIONX_ADMIN_URL . 'assets/img/fomopress-menu-icon.png', 80 );
		foreach( $settings as $slug => $setting ) {
			$cap  = isset( $setting['capability'] ) ? $setting['capability'] : 'delete_users';
			$hook = add_submenu_page( 'notificationx', $setting['title'], $setting['title'], $cap, $slug, $setting['callback'] );
		}
	}

	public function quick_builder(){
		$builder_args = $this->builder_args;
		$tabs         = $this->builder_args['tabs'];
		$prefix       = self::$prefix;
		$metabox_id   = $this->metabox_id;
		/**
		 * This lines of code is for editing a notification in simple|quick builder
		 *
		 * @var  [type]
		 */
		$idd = null;
		if( isset( $_GET['post_id'] ) && ! empty( $_GET['post_id'] )) {
			$idd = intval( $_GET['post_id'] );
		}
		include_once NOTIFICATIONX_ADMIN_DIR_PATH . 'partials/fomopress-quick-builder-display.php';
	}
	/**
	 * Generate the builder data acording to default meta data
	 *
	 * @param array $data
	 * @return array
	 */
	protected function builder_data( $data ) {
		$post_data   = [];
		$prefix      = self::$prefix;
		$meta_fields = FomoPress_MetaBox::get_metabox_fields( $prefix );
		foreach( $meta_fields as $meta_key => $meta_field ) {
			if( in_array( $meta_key, array_keys($data) ) ) {
				$post_data[ $meta_key ] = $data[ $meta_key ];
			} else {
				$post_data[ $meta_key ] = '';

				if( isset( $meta_field['defaults'] ) ) {
					$post_data[ $meta_key ] = $meta_field['defaults'];
				}
				if( isset( $meta_field['default'] ) ) {
					$post_data[ $meta_key ] = $meta_field['default'];
				}
			}
		}

		return array_merge( $post_data, $data );
	}
	
	public static function get_form_action( $query_var = '', $builder_form = false ) {
		$page = '/admin.php?page=nx-settings';
		if( $builder_form ) {
			$page = '/admin.php?page=nx-builder';
		}

		if ( is_network_admin() ) {
			return network_admin_url( $page . $query_var );
		} else {
			return admin_url( $page . $query_var );
		}
	}

	public function notification_preview(){
		global $pagenow, $post_type, $post;
		if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) {
			return false;
		}
		if ( $this->type != $post_type ) {
			return false;
		}
		$display_type = get_post_meta( $post->ID, '_nx_meta_display_type', true );

		include NOTIFICATIONX_ADMIN_DIR_PATH . 'partials/fomopress-admin-preview.php';
	}

	public static function get_post_meta( $post_id, $key, $single = true ) {
		return get_post_meta( $post_id, '_nx_meta_' . $key, $single );
	}
	public static function update_post_meta( $post_id, $key, $value ) {
		update_post_meta( $post_id, '_nx_meta_' . $key, $value );
	}
}