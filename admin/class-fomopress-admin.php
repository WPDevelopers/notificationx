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
	public $type = 'fomopress';

	public $metabox;

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
		/**
		 * Load FomoPress_MetaBox
		 */
		if( ! class_exists( 'FomoPress_MetaBox' ) ) {
			require_once FOMOPRESS_ADMIN_DIR_PATH . 'includes/class-fomopress-metabox.php';
			$this->metabox = new FomoPress_MetaBox;
		}

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_style( 
			$this->plugin_name, 
			FOMOPRESS_ADMIN_URL . 'assets/css/fomopress-admin.css', 
			array(), $this->version, 'all' 
		);

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_script( 
			$this->plugin_name, 
			FOMOPRESS_ADMIN_URL . 'assets/js/fomopress-admin.js', 
			array( 'jquery' ), $this->version, true 
		);

	}

	/**
	 * Register the FomoPress custom post type.
	 *
	 * @since	1.0.0
	 */
	public function fomopress_type_register(){

		$labels = array(
			'name'                => 'FomoPress',
			'singular_name'       => 'FomoPress',
			'add_new'             => esc_html__( 'Add New', 'fomopress' ) ,
			'add_new_item'        => esc_html__( 'Add New', 'fomopress' ),
			'edit_item'           => esc_html__( 'Edit', 'fomopress' ),
			'new_item'            => esc_html__( 'New', 'fomopress' ),
			'view_item'           => esc_html__( 'View', 'fomopress' ),
			'search_items'        => esc_html__( 'Search', 'fomopress' ),
			'not_found'           => esc_html__( 'No fomo found', 'fomopress' ),
			'not_found_in_trash'  => esc_html__( 'No fomo found in Trash', 'fomopress' ),
			'menu_name'           => 'FomoPress',
		);
		$args = array(
			'labels'              => $labels,
			'hierarchical'        => false,
			'description'         => '',
			'taxonomies' 		  => array( '' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 80,
			'menu_icon'           => FOMOPRESS_ADMIN_URL . 'assets/img/fomopress-icon.png',
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

		// $this->metabox_tabs();

	}

	/**
	 * Admin Menu Page
	 *
	 * @return void
	 */
	public function fomopress_admin_menu_page(){

		$settings = apply_filters( 'fomopress_admin_menu', array(
			'fomopress-settings'   => array(
				'title'      => __('Settings', 'fomopress'),
				'capability' => 'delete_users',
				'callback'   => array( $this, 'fomopress_settings_page' )
			)
		) );

		foreach( $settings as $slug => $setting ) {
			$cap  = isset( $setting['capability'] ) ? $setting['capability'] : 'delete_users';
			$hook = add_submenu_page( 'edit.php?post_type=fomopress', $setting['title'], $setting['title'], $cap, $slug, $setting['callback'] );
		}
		
	}

	public static function settings_args(){
		return require FOMOPRESS_ADMIN_DIR_PATH . 'includes/fomopress-settings-page-helper.php';
	}

	public function fomopress_settings_page(){
		$settings_args = self::settings_args();

		if( isset( $_POST[ 'fomopress_settings_submit' ] ) ) : 
			$this->save_settings( $_POST );
		endif;

		include_once FOMOPRESS_ADMIN_DIR_PATH . 'partials/fomopress-settings-display.php';
	}

	private function get_settings_fields( $settings ){
        $new_fields = [];

        foreach( $settings as $setting ) {
            $sections = $setting['sections'];
            foreach( $sections as $section ) {
                $fields = $section['fields'];
                foreach( $fields as $id => $field ) {
                    $new_fields[ $id ] = $field;
                }    
            }
        }

        return apply_filters( 'fomopress_settings_fields', $new_fields );
	}
	

	public static function render_field( $name, $field ){
		if( empty( $name ) || empty( $field ) ) {
			return;
		}
		$file_name = isset( $field['type'] ) ? $field['type'] : 'text';
		$value = FomoPress_DB::get_settings( $name );

		if( ! $value ) {
			$value = isset( $field['default'] ) ? $field['default'] : '';
		}

        include FOMOPRESS_ADMIN_DIR_PATH . 'includes/fields/fomopress-'. $file_name .'.php';
	}

	public function save_settings( $values = [] ){
		// Verify the nonce.
        if ( ! isset( $values['fomopress_settings_nonce'] ) || ! wp_verify_nonce( $values['fomopress_settings_nonce'], 'fomopress_settings' ) ) {
            return;
		}

		if( ! isset( $values['fomopress_settings_submit'] ) || ! is_array( $values ) ) {
			return;
		}

		$settings_args = self::settings_args();
		$fields = $this->get_settings_fields( $settings_args );

		foreach( $values as $key => $value ) {

			if( array_key_exists( $key, $fields ) ) {
				if( empty( $value ) ) {
					$value = $fields[ $key ]['default'];
				}
				$value = FomoPress_Helper::sanitize_field( $fields[ $key ], $value );
				$data[ $key ] = $value;
			}

		}

		FomoPress_DB::update_settings( $data );
	}

	static public function get_form_action( $query_var = '' ) {
		$page = '/edit.php?post_type=fomopress&page=fomopress-settings';

		if ( is_network_admin() ) {
			return network_admin_url( $page . $query_var );
		} else {
			return admin_url( $page . $query_var );
		}
	}

}
