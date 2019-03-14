<?php
/**
* The admin-specific functionality of the plugin.
*
* @link       https://wpdeveloper.net
* @since      1.0.0
*
* @package    NotificationX
* @subpackage NotificationX/admin
* @author     WPDeveloper <support@wpdeveloper.net>
*/

class NotificationX_Admin {
	
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
	* @var string the post type of notificationx.
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
		self::$settings = NotificationX_DB::get_settings();
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
				$settings = NotificationX_MetaBox::get_metabox_settings( $post->ID );
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
			NOTIFICATIONX_ADMIN_URL . 'assets/css/nx-admin-global.min.css', 
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
			NOTIFICATIONX_ADMIN_URL . 'assets/css/nx-admin.min.css', 
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
			NOTIFICATIONX_ADMIN_URL . 'assets/js/nx-admin.min.js', 
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
		
		return apply_filters('nx_post_columns', $columns );
	}
	
	public function manage_custom_columns( $column, $post_id ){
		switch ( $column ) {
			case 'notification_type':
				$type = get_post_meta( $post_id, '_nx_meta_display_type', true );
				if ( $type ) {
					$type = NotificationX_Helper::notification_types( $type );
					if( $type !== 'Conversions' ) {
						echo $type;
					} else {
						$from = get_post_meta( $post_id, '_nx_meta_conversion_from', true );
						echo $type . ' - ' . NotificationX_Helper::conversion_from( $from );
					}
				}
				break;
				case 'notification_status':
					$status = get_post_meta( $post_id, '_nx_meta_active_check', true );
					self::notification_toggle( $status, $post_id );
					break;
				}
				
				do_action( 'nx_post_columns_content', $column, $post_id );
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
				data-nonce="<?php echo wp_create_nonce('notificationx_status_nonce'); ?>" 
				data-post="<?php echo $post_id; ?>" />
				<?php
			}
			
			public function notification_status(){
				$error = false;
				
				if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'notificationx_status_nonce' ) ) {
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
			* Register the NotificationX custom post type.
			*
			* @since	1.0.0
			*/
			public function register(){
				
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
					'menu_icon'           => NOTIFICATIONX_ADMIN_URL . 'assets/img/nx-menu-icon.png',
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
			public function menu_page(){
				
				$settings_class = new NotificationX_Settings();
				
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
					
					$this->builder_args = NotificationX_MetaBox::get_builder_args();
					$this->metabox_id   = $this->builder_args['id'];
					$flag         = true;
					/**
					* Add Submit
					*/
					if( isset( $_POST[ 'nx_builder_add_submit' ] ) ) :
						if ( ! isset( $_POST[$this->metabox_id . '_nonce'] ) || ! wp_verify_nonce( $_POST[$this->metabox_id . '_nonce'], $this->metabox_id ) ) {
							$flag = false;
						}
						
						if( $flag ) {
							
							if( $_POST['nx_meta_display_type'] == 'press_bar' )  {
								$title = __('NotificationX - Notification Bar', 'notificationx');
							} elseif( $_POST['nx_meta_display_type'] == 'comments' )  {
								$title = __('NotificationX - WP Comments', 'notificationx');
							} elseif( $_POST['nx_meta_display_type'] == 'conversions' )  {
								$conversions = NotificationX_Helper::conversion_from();
								$title = 'NotificationX - ' . $conversions[$_POST['nx_meta_conversion_from']];
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
								do_action( 'nx_before_builder_submit', $_POST );
								// saving builder meta data with post
								NotificationX_MetaBox::save_data( $this->builder_data( $_POST ), $p_id );
								/**
								* Safely Redirect to NotificationX Page
								*/
								wp_safe_redirect( add_query_arg( array(
									'post_type' => 'notificationx',
								), admin_url( 'edit.php' ) ) );
							}
						}
					endif;
					add_menu_page( 'NotificationX', 'NotificationX', 'delete_users', 'notificationx', '', NOTIFICATIONX_ADMIN_URL . 'assets/img/nx-menu-icon.png', 80 );
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
					include_once NOTIFICATIONX_ADMIN_DIR_PATH . 'partials/nx-quick-builder-display.php';
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
					$meta_fields = NotificationX_MetaBox::get_metabox_fields( $prefix );
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
					
					include NOTIFICATIONX_ADMIN_DIR_PATH . 'partials/nx-admin-preview.php';
				}
				
				public function preview_html( $settings, $type = 'conversion' ){
					
					$data = array(
						'comment' => array(
							'link' => '#',
							'post_title' => 'Hello world!',
							'post_link' => '#',
							'timestamp' => '1550986787',
							'user_id' => get_current_user_id(),
							'name' => 'John D',
						),
						'conversion' => array(
							'link' => '#',
							'title' => 'Hello world!',
							'timestamp' => '1550986787',
							'user_id' => get_current_user_id(),
							'name' => 'John D',
							)
						);
						
						$unique_id = uniqid( 'notificationx-' ); 
						$output = '<div id="'. esc_attr( $unique_id ) .'" class="nx-notification '. NotificationX_Extension::get_classes( $settings ) .'">';
						$output .= '<div '. NotificationX_Public::generate_preview_css( $settings ) .' class="notificationx-inner '. NotificationX_Extension::get_classes( $settings, 'inner' ) .'">';
						$output .= '<div class="notificationx-image nx-preview-image">';
						$output .= '<img class="'. NotificationX_Extension::get_classes( $settings, 'img' ) .'" src="'. NOTIFICATIONX_ADMIN_URL . 'assets/img/placeholder-300x300.png" alt="">';
						$output .= '</div>';
						$output .= '<div class="notificationx-content">';
						
						if( $type === 'conversion' ) :
							$output .= NotificationX_Template::get_template_ready( $settings->woo_template, NotificationX_Extension::newData( $data[ 'conversion' ] ), $settings );
						endif;
						if( $type === 'comment' ) :
							$output .= NotificationX_Template::get_template_ready( $settings->comments_template, NotificationX_Extension::newData( $data[ 'comment' ] ), $settings );
						endif;
						
						if( $settings->close_button ) :
							$output .= '<span class="notificationx-close nx-preview-close"><svg width="8px" height="8px" viewBox="0 0 48 48" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="Page-1" stroke="none" stroke-width="1" fill-rule="evenodd"><g id="close" fill-rule="nonzero"><path d="M28.228,23.986 L47.092,5.122 C48.264,3.951 48.264,2.051 47.092,0.88 C45.92,-0.292 44.022,-0.292 42.85,0.88 L23.986,19.744 L5.121,0.88 C3.949,-0.292 2.051,-0.292 0.879,0.88 C-0.293,2.051 -0.293,3.951 0.879,5.122 L19.744,23.986 L0.879,42.85 C-0.293,44.021 -0.293,45.921 0.879,47.092 C1.465,47.677 2.233,47.97 3,47.97 C3.767,47.97 4.535,47.677 5.121,47.091 L23.986,28.227 L42.85,47.091 C43.436,47.677 44.204,47.97 44.971,47.97 C45.738,47.97 46.506,47.677 47.092,47.091 C48.264,45.92 48.264,44.02 47.092,42.849 L28.228,23.986 Z" id="Shape"></path></g></g></svg></span>';
							endif;
							if( is_null( NotificationX_Extension::$powered_by ) ) :
								$output .= '<small class="nx-branding">';
								$output .= '<svg width="7" height="13" viewBox="0 0 7 13" xmlns="http://www.w3.org/2000/svg" title="Powered by NotificationX"><g fill-rule="evenodd" fill="none"><path fill="#F6A623" d="M4.127.496C4.51-.12 5.37.356 5.16 1.07L3.89 5.14H6.22c.483 0 .757.616.464 1.044l-4.338 6.34c-.407.595-1.244.082-1.01-.618L2.72 7.656H.778c-.47 0-.748-.59-.48-1.02L4.13.495z"></path><path fill="#FEF79E" d="M4.606.867L.778 7.007h2.807l-1.7 5.126 4.337-6.34H3.16"></path></g></svg>';
								$output .= ' by <a href="'. NOTIFICATIONX_PLUGIN_URL .'?utm_source='. urlencode( home_url() ) .'&utm_medium=notificationx_referrer" target="_blank" class="nx-powered-by">NotificationX</a>';
								$output .= '</small>';
							endif;
							$output .= '</div>';
							$output .= '</div>';
							$output .= '</div>';
							
							return $output;
						}
						
						public static function get_post_meta( $post_id, $key, $single = true ) {
							return get_post_meta( $post_id, '_nx_meta_' . $key, $single );
						}
						public static function update_post_meta( $post_id, $key, $value ) {
							update_post_meta( $post_id, '_nx_meta_' . $key, $value );
						}
					}