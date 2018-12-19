<?php

/**
 * @link       https://wpdeveloper.net
 * @since      1.0.0
 *
 * @package    FomoPress
 * @subpackage FomoPress/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    FomoPress
 * @subpackage FomoPress/includes
 * @author     WPDeveloper <support@wpdeveloper.net>
 */
final class FomoPress {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      FomoPress_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'NOTIFICATIONX_VERSION' ) ) {
			$this->version = NOTIFICATIONX_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'notificationx';

		$this->load_dependencies();
		$this->set_locale();
		$this->fomopress_start_plugin_tracking();
		add_action( 'plugins_loaded', array( $this, 'load_extensions' ) );
		add_action( 'plugins_loaded', array( $this, 'define_admin_hooks' ) );
		add_action( 'plugins_loaded', array( $this, 'define_public_hooks' ) );
		add_action( 'admin_init', array( $this, 'redirect' ) );
	}

	public function redirect() {
		// Bail if no activation transient is set.
		if ( ! get_transient( '_fomopress_activation_notice' ) ) {
			return;
		}
		// Delete the activation transient.
		delete_transient( '_fomopress_activation_notice' );

		if ( ! is_multisite() ) {
			// Redirect to the welcome page.
			wp_safe_redirect( add_query_arg( array(
				'page'		=> 'fomopress-builder'
			), admin_url( 'admin.php' ) ) );
		}
	}
	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - FomoPress_Loader. Orchestrates the hooks of the plugin.
	 * - FomoPress_i18n. Defines internationalization functionality.
	 * - FomoPress_Admin. Defines all hooks for the admin area.
	 * - FomoPress_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * FomoPress DB
		 */
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-fomopress-db.php';
		/**
		 * FomoPress Helper
		 */
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-fomopress-helper.php';
		/**
		 * FomoPress Cron
		 */
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-fomopress-cron.php';
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 * 
		 * TODO: do something with loader
		 */
		// require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-fomopress-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-fomopress-i18n.php';
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-plugin-usage-tracker.php';
		
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'public/includes/class-fomopress-template.php';
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-fomopress-locations.php';
		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once NOTIFICATIONX_ADMIN_DIR_PATH . 'includes/class-fomopress-metabox.php';
		require_once NOTIFICATIONX_ADMIN_DIR_PATH . 'includes/class-fomopress-settings.php';
		require_once NOTIFICATIONX_ADMIN_DIR_PATH . 'class-fomopress-admin.php';
		
		/**
		 * The class responsible for defining extensions functionality
		 * of the plugin.
		 */
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-fomopress-extension-factory.php';
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-fomopress-extension.php';
		require_once NOTIFICATIONX_EXT_DIR_PATH . 'press-bar/class-press-bar.php';
		require_once NOTIFICATIONX_EXT_DIR_PATH . 'wp-comments/class-wp-comments.php';
		require_once NOTIFICATIONX_EXT_DIR_PATH . 'woocommerce/class-woocommerce.php';
		require_once NOTIFICATIONX_EXT_DIR_PATH . 'edd/class-edd.php';
		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'public/class-fomopress-public.php';
		/**
		 * TODO: do something with loader, or have to remove
		 */
		// $this->loader = new FomoPress_Loader();
		do_action('fomopress_load_depedencies');
	}

	/**
	 * Optional usage tracker
	 *
	 * @since v1.0.0
 	*/
	public function fomopress_start_plugin_tracking() {
		$wisdom = new FomoPress_Plugin_Usage_Tracker(
			NOTIFICATIONX_FILE,
			'https://wpdeveloper.net',
			array(),
			true,
			true,
			1
		);
	}

	/**
	 * This function is responsible for load all extensions
	 *
	 * @return void
	 */
	public function load_extensions(){
		global $fomopress_extension_factory;

		$extensions = [
			'FomoPress_EDD_Extension',
			'FomoPress_PressBar_Extension',
			'FomoPress_WP_Comments_Extension',
			'FomoPress_WooCommerce_Extension',
		];

		foreach( $extensions as $extension ) {
			/**
			 * Register the extension
			 */
			fomopress_register_extension( $extension );
		}
		/**
		 * Init all extensions here.
		 */
		do_action( 'fomopress_extensions_init' );
		/**
		 * Load all extension.
		 */
		$fomopress_extension_factory->load();
	}
	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the FomoPress_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new FomoPress_i18n();
		add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );
	}
	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	public function define_admin_hooks() {
		
		$plugin_admin          = new FomoPress_Admin( $this->get_plugin_name(), $this->get_version() );
		$plugin_admin->metabox = new FomoPress_MetaBox;
		
		add_action( 'init', array( $plugin_admin, 'fomopress_type_register') );
		add_action( 'init', array( $plugin_admin, 'get_active_items') );
		add_action( 'add_meta_boxes', array( $plugin_admin->metabox, 'add_meta_boxes') );
		add_action( 'admin_menu', array( $plugin_admin, 'fomopress_admin_menu_page') );
		add_action( 'admin_footer', array( $plugin_admin, 'notification_preview') );
		add_filter( 'manage_fomopress_posts_columns', array( $plugin_admin, 'custom_columns') );
		add_action( 'manage_fomopress_posts_custom_column', array( $plugin_admin, 'manage_custom_columns' ), 10, 2 );
		add_action( 'wp_ajax_notifications_toggle_status', array( $plugin_admin, 'notification_status') );

		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles') );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts') );
		
		add_action( 'save_post', array( $plugin_admin->metabox, 'save_metabox') );

		/**
		 * Initializing FomoPress_Settings
		 */
		FomoPress_Settings::init();

		do_action( 'fomopress_admin_action' );
	}
	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	public function define_public_hooks() {

		$plugin_public = new FomoPress_Public( $this->get_plugin_name(), $this->get_version() );

		do_action( 'fomopress_public_action' );

		add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_styles') );
		add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_scripts') );
		add_action( 'wp', array( $plugin_public, 'get_active_items') );
		add_action( 'wp_footer', array( $plugin_public, 'generate_active_fomo') );
		add_action( 'wp_ajax_fomopress_get_conversions', array( $plugin_public, 'generate_conversions') );
		add_action( 'wp_ajax_nopriv_fomopress_get_conversions', array( $plugin_public, 'generate_conversions') );
	}
	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		return $this;
	}
	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}
	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    FomoPress_Loader    Orchestrates the hooks of the plugin.
	 * TODO: remove this or do others 
	 */
	public function get_loader() {
		return $this->loader;
	}
	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}