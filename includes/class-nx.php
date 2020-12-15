<?php
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
 * @package    NotificationX
 * @subpackage NotificationX/includes
 * @author     WPDeveloper <support@wpdeveloper.net>
 */
final class NotificationX {
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
		$this->start_plugin_tracking();
		add_action( 'init', array( $this, 'load_extensions' ) );
		add_action( 'plugins_loaded', array( $this, 'define_admin_hooks' ) );
		add_action( 'init', array( $this, 'define_public_hooks' ) );
		add_action( 'init', array( $this, 'migration' ) );
		add_action( 'admin_init', array( $this, 'redirect' ) );
	}

	public function redirect() {
		// Bail if no activation transient is set.
		if ( ! get_transient( '_nx_meta_activation_notice' ) ) {
			return;
		}
		// Delete the activation transient.
		delete_transient( '_nx_meta_activation_notice' );

		if ( ! is_multisite() ) {
			// Redirect to the welcome page.
			wp_safe_redirect( add_query_arg( array(
				'page'		=> 'nx-builder'
			), admin_url( 'admin.php' ) ) );
		}
	}
	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - NotificationX_Loader. Orchestrates the hooks of the plugin.
	 * - NotificationX_i18n. Defines internationalization functionality.
	 * - NotificationX_Admin. Defines all hooks for the admin area.
	 * - NotificationX_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * The code adds wpdev-dashboard-widget.php
		 * It will show a wpdeveloper.net news feed in dashboard.
		 */
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-wpdev-dashboard-widget.php';
		/**
		 * NotificationX DB
		 */
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-nx-db.php';
		/**
		 * Analytics Dashboard Widgets
		 */
		require_once NOTIFICATIONX_ADMIN_DIR_PATH . 'includes/class-dashboard-widget.php';
		/**
		 * NotificationX Helper
		 */
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-nx-helper.php';
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-nx-const.php';
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-toggle-helper.php';

		/**
		 * NotificationX Analytics Report
		 */
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'admin/reports/class-nx-analytics.php';
		/**
		 * NotificationX Report Email
		 */
        require_once NOTIFICATIONX_ROOT_DIR_PATH . 'admin/reports/email/class-nx-email-template.php';
        require_once NOTIFICATIONX_ROOT_DIR_PATH . 'admin/reports/email/class-nx-email-reporting.php';

		/**
		 * NotificationX Messages
		 */
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-nx-message.php';
		/**
		 * NotificationX Cron
		 */
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-nx-cron.php';
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 *
		 * TODO: do something with loader
		 */
		// require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-nx-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-nx-i18n.php';
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-plugin-usage-tracker.php';

		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'public/includes/class-nx-template.php';
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-nx-locations.php';
		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once NOTIFICATIONX_ADMIN_DIR_PATH . 'includes/class-nx-metabox.php';
		require_once NOTIFICATIONX_ADMIN_DIR_PATH . 'includes/class-nx-settings.php';
		require_once NOTIFICATIONX_ADMIN_DIR_PATH . 'class-nx-admin.php';

		/**
		 * The class responsible for defining extensions functionality
		 * of the plugin.
		 */
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-nx-array.php';
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-nx-extension-factory.php';
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'includes/class-nx-extension.php';
		require_once NOTIFICATIONX_EXT_DIR_PATH . 'press-bar/class-press-bar.php';
		require_once NOTIFICATIONX_EXT_DIR_PATH . 'wp-comments/class-wp-comments.php';
		require_once NOTIFICATIONX_EXT_DIR_PATH . 'wporg/class-wporg-review.php';
		require_once NOTIFICATIONX_EXT_DIR_PATH . 'wporg/class-wporg-stats.php';
		require_once NOTIFICATIONX_EXT_DIR_PATH . 'woocommerce/class-woocommerce.php';
		require_once NOTIFICATIONX_EXT_DIR_PATH . 'woocommerce/class-woocommerce-reviews.php';
		require_once NOTIFICATIONX_EXT_DIR_PATH . 'woocommerce/class-reviewx.php';
		require_once NOTIFICATIONX_EXT_DIR_PATH . 'edd/class-edd.php';
		require_once NOTIFICATIONX_EXT_DIR_PATH . 'give/class-give.php';
		require_once NOTIFICATIONX_EXT_DIR_PATH . 'tutor/class-tutor.php'; // @since 1.3.9
		require_once NOTIFICATIONX_EXT_DIR_PATH . 'form/class-cf7.php'; // @since 1.3.9
		require_once NOTIFICATIONX_EXT_DIR_PATH . 'form/class-wpf.php'; // @since 1.4.*
		require_once NOTIFICATIONX_EXT_DIR_PATH . 'form/class-ninjaform.php'; // @since 1.4.*
		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'public/includes/class-nx-advanced-style.php';
		require_once NOTIFICATIONX_ROOT_DIR_PATH . 'public/class-nx-public.php';
		do_action('notificationx_load_depedencies');
	}
	/**
	 * Optional usage tracker
	 *
	 * @since v1.0.0
	*/
	public function start_plugin_tracking() {
		new NotificationX_Plugin_Usage_Tracker(
			NOTIFICATIONX_FILE,
			'http://app.wpdeveloper.net',
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
	public function load_extensions( $hook ){
		global $nx_extension_factory;

		$extensions = [
			'press_bar'   => 'NotificationX_PressBar_Extension',
			'wp_comments' => 'NotificationX_WP_Comments_Extension',
			'wp_reviews'  => 'NotificationXPro_WPOrgReview_Extension',
			'wp_stats'    => 'NotificationXPro_WPOrgStats_Extension',
			'woocommerce' => 'NotificationX_WooCommerce_Extension',
			'edd'         => 'NotificationX_EDD_Extension',
			'give'        => 'NotificationX_Give_Extension',
			'tutor'       => 'NotificationX_Tutor_Extension',
			'cf7'         => 'NotificationX_CF7_Extension',
			'wpf'         => 'NotificationXPro_WPForms_Extension',
			'njf'         => 'NotificationXPro_NinjaForms_Extension',
			'woo_reviews' => 'NotificationX_WooCommerceReview_Extension',
			'reviewx'     => 'NotificationX_ReviewX_Extension',
		];

		foreach( $extensions as $key => $extension ) {
			/**
			 * Register the extension
			 */
			nx_register_extension( $extension, $key );
		}
		/**
		 * Init all extensions here.
		 */
		do_action( 'nx_extensions_init' );
		/**
		 * Load all extension.
		 */
		$nx_extension_factory->load();
	}
	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the NotificationX_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new NotificationX_i18n();
		add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );
	}
	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	public function define_admin_hooks( $hook ) {
		$plugin_admin          = new NotificationX_Admin( $this->get_plugin_name(), $this->get_version() );
		$plugin_admin->metabox = new NotificationX_MetaBox;

		if( class_exists( 'WPDeveloper_Dashboard_Widget' ) ) {
			WPDeveloper_Dashboard_Widget::instance();
		}

		add_action( 'init', array( $plugin_admin, 'register') );
		// add_action( 'init', array( $plugin_admin, 'get_active_items') );
		add_filter( 'cron_schedules', 'NotificationX_Cron::cron_schedule', 10, 1 );
		add_action( 'admin_init', array( $plugin_admin, 'get_enabled_types') );
		add_action( 'admin_init', array( $plugin_admin, 'admin_init') );
		add_action( 'add_meta_boxes', array( $plugin_admin->metabox, 'add_meta_boxes') );
		add_action( 'nx_before_metabox_tab_section', 'NotificationX_Helper::sound_section', 10, 3 );
		add_action( 'nx_builder_before_tab', array( $plugin_admin->metabox, 'finalize_builder'), 10, 2 );
		add_action( 'admin_menu', array( $plugin_admin, 'menu_page') );
		add_filter( 'parent_file', array(&$plugin_admin, 'highlight_admin_menu'));
		add_filter( 'submenu_file', array(&$plugin_admin, 'highlight_admin_submenu'), 10, 2);
		// add_action( 'admin_footer', array( $plugin_admin, 'notification_preview') );

		add_filter( 'init', 'NotificationX_PressBar_Extension::register_post_type' );
		add_filter( 'nx_template_name', 'NotificationX_Helper::new_template_name', 10, 2 );
		add_filter( 'manage_notificationx_posts_columns', array( $plugin_admin, 'custom_columns') );
		add_action( 'manage_notificationx_posts_custom_column', array( $plugin_admin, 'manage_custom_columns' ), 10, 2 );
		add_action( 'wp_ajax_notifications_toggle_status', array( $plugin_admin, 'notification_status') );

		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles') );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts') );

		add_action( 'save_post_notificationx', array( $plugin_admin->metabox, 'save_metabox') );
		add_action( 'load-edit.php', array( $plugin_admin, 'trashed_notificationx') );
		add_action( 'wp_insert_post', array( $plugin_admin, 'redirect_after_publish'), 9999, 3 );

		/**
		 * Initializing NotificationX_Settings
		 */
		NotificationX_Settings::init();

		do_action( 'nx_admin_action' );
	}
	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	public function define_public_hooks() {

		$plugin_public = new NotificationX_Public( $this->get_plugin_name(), $this->get_version() );

		do_action( 'nx_public_action' );
		add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_styles') );
		add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_scripts') );
		add_action( 'wp_head', array( $plugin_public, 'generate_active_notificationx') );
		add_action( 'wp_ajax_nx_get_conversions', array( $plugin_public, 'generate_conversions') );
		add_action( 'wp_ajax_nopriv_nx_get_conversions', array( $plugin_public, 'generate_conversions') );
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
	 * @return    NotificationX_Loader    Orchestrates the hooks of the plugin.
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
	/**
	 * Version data migration if have something changed from previous version to current version
	 * @return void
	 */
	public function migration(){
		$version = get_option( 'nx_free_version', false );
		$version_migration = get_option( 'nx_version_migration_140', false );
		if( $version_migration === false && version_compare( NOTIFICATIONX_VERSION, '1.4.0', '==') ) {
			update_option( 'nx_free_version', NOTIFICATIONX_VERSION );
			update_option( 'nx_version_migration_140', true );

			global $wpdb;

			$inner_query_sql = "SELECT posts.ID, meta.meta_value as val FROM $wpdb->posts AS posts INNER JOIN $wpdb->postmeta AS meta ON meta.post_id = posts.ID WHERE post_type = '%s' AND post_status = '%s' AND meta.meta_value = '%s' AND meta.meta_key = '%s'";

			$query_sql = "SELECT nx_posts.ID, imeta.meta_value as source FROM ( $inner_query_sql ) AS nx_posts right JOIN $wpdb->postmeta AS imeta ON imeta.post_id = nx_posts.ID WHERE imeta.meta_key = '%s' AND imeta.meta_value IN ( 'tutor', 'learndash' )";

			$main_query = "SELECT * FROM $wpdb->postmeta as nx_meta INNER JOIN ( $query_sql ) as nx_mig on nx_meta.post_id = nx_mig.ID WHERE nx_meta.meta_key IN ( '_nx_meta_woo_template_new_string', '_nx_meta_woo_template_new' )";

			$query = $wpdb->prepare(
				$main_query,
				array(
					'notificationx',
					'publish',
					'conversions',
					'_nx_meta_display_type',
					'_nx_meta_conversion_from',
				)
			);

			$results = $wpdb->get_results( $query );

			$format = [ '%s' ];
			$where_format = [ '%d', '%s' ];

			if( ! empty( $results ) && is_array( $results ) ) {
				$temp_id = 0; $meta_key = '';
				foreach( $results  as $nx ) {
					if( in_array( $nx->source, array( 'tutor', 'learndash' ) ) ) {
						$data = [ 'meta_value' => 'elearning' ];
						if( $nx->meta_key == '_nx_meta_woo_template_new' ) {
							$meta_key = '_nx_meta_elearning_template_new';
						}
						if( $nx->meta_key == '_nx_meta_woo_template_new_string' ) {
							$meta_key = '_nx_meta_elearning_template_new_string';
						}
					}
					if( $nx->source === 'give' ) {
						$data = [ 'meta_value' => 'donation' ];
						if( $nx->meta_key == '_nx_meta_woo_template_new' ) {
							$meta_key = '_nx_meta_donation_template_new';
						}
						if( $nx->meta_key == '_nx_meta_woo_template_new_string' ) {
							$meta_key = '_nx_meta_donation_template_new_string';
						}
					}

					if( ! empty( $meta_key ) ) {
						$wpdb->insert( $wpdb->postmeta, array(
							'post_id' => $nx->ID,
							'meta_key' => $meta_key,
							'meta_value' => $nx->meta_value,
						), array( '%d', '%s', '%s' ) );
					}
					if( $temp_id != $nx->ID ) {
						$where = [ 'post_id' => $nx->ID, 'meta_key' => '_nx_meta_display_type' ];
						$wpdb->update( $wpdb->postmeta, $data, $where, $format, $where_format );
					}
					$temp_id = $nx->ID;
				}
			}
		}
		if(version_compare( NOTIFICATIONX_VERSION, '1.4.1', '==')){
            $version_migration = get_option( 'nx_version_migration_141', false );
            if( ! $version_migration ) {
                update_option('nx_version_migration_141', true);
				$settings = NotificationX_DB::get_settings();
                if( ! isset( $settings['nx_modules']['modules_google_analytics'] ) ){
                    $settings['nx_modules']['modules_google_analytics'] = true;
                    NotificationX_DB::update_settings( $settings );
                }
            }
		}
		if(version_compare( NOTIFICATIONX_VERSION, '1.4.3', '==')){
			$version_migration = get_option( 'nx_version_migration_143', false );
            if( ! $version_migration ) {
                update_option('nx_version_migration_143', true);
				$settings = NotificationX_DB::get_settings();
                if( ! isset( $settings['nx_modules']['modules_cf7'] ) ){
                    $settings['nx_modules']['modules_cf7'] = true;
                    NotificationX_DB::update_settings( $settings );
                }
            }
		}
		if(version_compare( NOTIFICATIONX_VERSION, '1.4.4', '==')){
			$version_migration = get_option( 'nx_version_migration_144', false );
            if( ! $version_migration ) {
                update_option('nx_version_migration_144', true);
				$settings = NotificationX_DB::get_settings();
				$settings['nx_modules']['modules_wpf'] = true;
				$settings['nx_modules']['modules_njf'] = true;
				NotificationX_DB::update_settings( $settings );
            }
		}

		if(version_compare( NOTIFICATIONX_VERSION, '1.8.1', '==')){
            $version_migration = get_option( 'nx_version_migration_181', false );
            if( ! $version_migration ) {
                update_option('nx_version_migration_181', true);
				$settings = NotificationX_DB::get_settings();
                if( ! isset( $settings['nx_modules']['modules_reviewx'] ) ){
                    $settings['nx_modules']['modules_reviewx'] = true;
                    NotificationX_DB::update_settings( $settings );
                }
            }
		}

	}
}