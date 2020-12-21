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
	public static $counts;

	public static $enabled_types = [];
	public static $active_items = [];

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		self::$settings = NotificationX_DB::get_settings();
        add_action( 'plugin_action_links_' . NOTIFICATIONX_BASENAME, array($this, 'nx_action_links'), 10, 1);
		add_filter( 'plugin_row_meta', array( $this, 'nx_row_meta' ), 10, 2 );
		add_filter( 'wp_untrash_post_status', array( $this, 'untrashed_post' ), 10, 3 );
		/**
		 * @since 1.2.6
		 */
		add_filter('set-screen-option', array( $this, 'save_screen_options' ), 10, 3);
		/**
		 * @since 1.3.5
		 */
		add_filter('nx_template_settings_by_theme', array( 'NotificationX_Helper', 'settings_by_themes' ), 10, 2);
		add_action('admin_notices', function(){
			if( get_post_type() !== 'notificationx' ) {{
				return false;
			}}
			do_action( 'notificationx_admin_new_header' );
		});
		add_action('notificationx_admin_header', array( $this, 'header_template' ));
		add_action('notificationx_admin_new_header', array( $this, 'header_template' ));
		add_action( 'add_meta_boxes',array( $this, 'add_metabox' ) );
	}

	public function untrashed_post( $new_status, $post_id, $old_status ){
		if( $new_status != 'publish' && get_post_type( $post_id ) === 'notificationx' ) {
			return 'publish';
		}
		return $new_status;
	}

	public function add_metabox(){
		add_meta_box( 'nx-instructions', __( 'NotificationX Instructions', 'notificationx' ), array( $this, 'metabox_content' ), 'notificationx', 'side' );
	}
	public function metabox_content(){
		ob_start();
        ?>
        <div class="nx-type-instructions-wrapper">
			<div class="conversions nxins-type">
				<div class="woocommerce nxins-type-source">
					<p>Make sure that you have <a target="_blank" href="https://wordpress.org/plugins/woocommerce/">WooCommerce installed & activated</a> to use this campaign. For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/woocommerce-sales-notifications/">documentation</a>.</p>
					<p>🎦 <a href="https://www.youtube.com/watch?v=dVthd36hJ-E&t=1s" target="_blank">Watch video tutorial</a> to learn quickly</p>
					<p>⭐ NotificationX Integration with WooCommerce</p>
					<p><strong>Recommended Blog:</strong></p>
					<p>🔥 Why NotificationX is The <a target="_blank" href="https://notificationx.com/integrations/woocommerce/">Best FOMO and Social Proof Plugin</a> for WooCommerce?</p>
					<p>🚀 How to <a target="_blank" href="https://notificationx.com/blog/best-fomo-and-social-proof-plugin-for-woocommerce/">boost WooCommerce Sales</a> Using NotificationX</p>
				</div>
				<div class="edd nxins-type-source">
					<p>Make sure that you have <a href="https://wordpress.org/plugins/easy-digital-downloads/" target="_blank">Easy Digital Downloads installed & activated</a> to use its campaign & product sales data. For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/notificationx-easy-digital-downloads/">documentation</a>.</p>
					<p>👉 NotificationX <a target="_blank" href="https://notificationx.com/integrations/easy-digital-downloads/">Integration with Easy Digital Downloads</a></p>
					<p><strong>Recommended Blog:</strong></p>
					<p>🔥 How Does <a target="_blank" href="https://wpdeveloper.net/notificationx-increase-sales-wordpress/">NotificationX Increase Sales on WordPress</a> Websites?</p>
				</div>
				<div class="nxins-type-source envato">
					<p>Make sure that you have <a target="_blank" href="https://account.envato.com/sign_in?to=envato-api">created & signed in to Envato account</a> to use its campaign & product sales data.  For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/envato-sales-notification/">documentation</a>.</p>
					<p>🎦 <a target="_blank" href="https://youtu.be/-df_6KHgr7I">Watch video tutorial</a> to learn quickly</p>
					<p>👉 NotificationX <a target="_blank" href="https://notificationx.com/integrations/envato/">Integration with Envato</a></p>
				</div>
				<div class="nxins-type-source custom_notification">
					<p>You can make custom notification for its all types of campaign. For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/custom-notification/">documentation</a>.</p>
					<p>🎦 Watch <a target="_blank" href="https://www.youtube.com/watch?v=OuTmDZ0_TEw">video tutorial</a> to learn quickly</p>
					<p><strong>Recommended Blog:</strong></p>
					<p>🔥 How to <a target="_blank" href="https://wpdeveloper.net/custom-notificationx-alert-fomo/">Display Custom Notification Alerts</a> On Your Website Using NotificationX</p>
				</div>
			</div>
			<div class="elearning nxins-type">
				<div class="nxins-type-source tutor">
					<p>Make sure that you have <a href="https://wordpress.org/plugins/tutor/" target="_blank">Tutor LMS installed & configured</a> to use its campaign & course selling data. For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/tutor-lms/">documentation</a>.</p>
					<p>🎦 Watch <a target="_blank" href="https://www.youtube.com/watch?v=EMrjLfL563Q">video tutorial</a> to learn quickly</p>
					<p>👉 NotificationX <a target="_blank" href="https://notificationx.com/integrations/tutor-lms/">Integration with Tutor LMS</a></p>
				</div>
				<div class="nxins-type-source learndash">
					<p>Make sure that you have <a target="_blank" href="https://www.learndash.com/">LearnDash installed & configured</a> to use its campaign & course selling data.  For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/how-to-display-learndash-course-enrollment-alert-using-notificationx">documentation</a>.</p>
					<p>🎦 <a target="_blank" href="https://www.youtube.com/watch?v=sTbBt2DVsIA">Watch video tutorial</a> to learn quickly</p>
					<p>👉 NotificationX <a target="_blank" href="https://notificationx.com/integrations/learndash/">Integration with LearnDash</a> </p>
					<p><strong>Recommended Blog:</strong></p>
					<p>🔥 How to Increase Your <a target="_blank" href="https://wpdeveloper.net/learndash-course-enrollment-rate-notificationx/">LearnDash Course Enrollment Rates</a> With NotificationX</p>
				</div>
			</div>
			<div class="donation nxins-type">
				<div class="nxins-type-source give">
					<p>Make sure that you have <a target="_blank" href="https://wordpress.org/plugins/give/">GiveWP installed & configured</a> to use its campaign & donars data. For further assistance, check out our step by step <a href="https://notificationx.com/docs/givewp-donation-alert/">documentation</a>.</p>
					<p>🎦 <a target="_blank" href="https://www.youtube.com/watch?v=8EFgHSA8mOg">Watch video tutorial</a> to learn quickly</p>
					<p>👉 NotificationX <a target="_blank" href="https://notificationx.com/integrations/givewp/">Integration with GiveWP</a></p>
					<p><strong>Recommended Blog:</strong></p>
					<p>🔥 How Does <a target="_blank" href="https://wpdeveloper.net/notificationx-increase-sales-wordpress/">NotificationX Increase Sales on WordPress</a> Websites?"</p>
				</div>
			</div>
			<div class="nxins-type form">
				<div class="nxins-type-source cf7">
					<p>Make sure that you have <a target="_blank" href="https://wordpress.org/plugins/contact-form-7/">Contact Form 7 installed & configured</a> to use its campaign & form subscriptions data. For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/contact-form-submission-alert/">documentation</a>.</p>
					<p>🎦 <a target="_blank" href="https://youtu.be/SP9NXMioIK8">Watch video tutorial</a> to learn quickly</p>
					<p>👉 NotificationX <a target="_blank" href="https://notificationx.com/integrations/contact-form-7/">Integration with Contact Form 7</a></p>
					<p><strong>Recommended Blog:</strong></p>
					<p>🔥 Hacks to Increase Your <a target="_blank" href="https://notificationx.com/blog/wordpress-contact-forms/">WordPress Contact Forms Submission Rate</a> Using NotificationX</p>
				</div>
				<div class="nxins-type-source njf">
					<p>Make sure that you have <a target="_blank" href="https://wordpress.org/plugins/ninja-forms/">Ninja Forms installed & configured</a> to use its campaign & form subscriptions data. For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/ninja-forms/">documentation</a>.</p>
					<p>🎦 <a target="_blank" href="https://www.youtube.com/watch?v=Ibv84iGcBHE">Watch video tutorial</a> to learn quickly</p>
					<p>⭐ Check how it looks in <a target="_blank" href="https://demo.notificationx.com/woocommerce/">LIVE Demo</a></p>
					<p>👉 NotificationX <a target="_blank" href="https://notificationx.com/integrations/ninja-forms/">Integration with Ninja Forms</a></p>
					<p><strong>Recommended Blog:</strong></p>
					<p>🔥 Hacks to Increase Your <a target="_blank" href="https://notificationx.com/blog/wordpress-contact-forms/">WordPress Contact Forms Submission Rate</a> Using NotificationX</p>
				</div>
				<div class="nxins-type-source wpf">
					<p>Make sure that you have <a target="_blank" href="https://wordpress.org/plugins/wpforms-lite/">WPForms installed & configured</a>  to use its campaign & form subscriptions data. For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/wpforms/">documentation</a>.</p>
					<p>🎦 <a target="_blank" href="https://www.youtube.com/watch?v=8tk7_ZawJN8">Watch video tutorial</a> to learn quickly</p>
					<p>👉 NotificationX <a target="_blank" href="https://notificationx.com/integrations/wpforms/">Integration with WPForms</a></p>
					<p><strong>Recommended Blogs:</strong></p>
					<p>🔥Hacks to Increase Your <a target="_blank" href="https://notificationx.com/blog/wordpress-contact-forms/">WordPress Contact Forms Submission Rate</a> Using NotificationX</p>
				</div>
				<div class="nxins-type-source grvf">
					<p>Make sure that you have <a target="_blank" href="https://www.gravityforms.com/">Gravity Forms installed & configured</a>, to use its campaign & form subscriptions data. For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/gravity-forms/">documentation</a>.</p>
					<p>🎦 <a target="_blank" href="https://www.youtube.com/watch?v=1Gl3XRd1TxY">Watch video tutorial</a> to learn quickly</p>
					<p>👉NotificationX <a target="_blank" href="https://notificationx.com/integrations/gravity-forms/">Integration with Ninja Forms</a></p>
					<p><strong>Recommended Blog:</strong></p>
					<p>🔥Hacks to Increase Your <a target="_blank" href="https://notificationx.com/blog/wordpress-contact-forms/">WordPress Contact Forms Submission Rate</a> Using NotificationX</p>
				</div>
			</div>
			<div class="nxins-type press_bar">
				<p>You can showcase the notification bar to do instant popup campaign on WordPress site. For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/notification-bar/">documentation</a>.</p>
				<p>🎦 Watch <a target="_blank" href="https://www.youtube.com/watch?v=l7s9FXgzbEM">video tutorial</a> to learn quickly</p>
				<p><strong>Recommended Blog:</strong></p>
				<p>🔥 Introducing NotificationX: <a target="_blank" href="https://wpdeveloper.net/notificationx-social-proof-fomo/">Social Proof & FOMO Marketing Solution</a> for WordPress</p>
				<p>🔥 How to <a href="https://notificationx.com/docs/notification-bar-with-elementor/" target="_blank">design Notification Bar with Elementor Page Builder</a>.</p>
			</div>
			<div class="nxins-type reviews download_stats comments">
				<div class="nxins-type-source wp_reviews wp_stats wp_comments">
					<p>Make sure that you have a <a target="_blank" href="https://wordpress.org/">wordpress.org</a> account to use its campaign on blog comments, reviews and download stats data. For further assistance, check out our step by step documentation on <a target="_blank" href="https://notificationx.com/docs/wordpress-comment-popup-alert/">comments popup</a>, <a target="_blank" href="https://notificationx.com/docs/wordpress-plugin-review-notificationx/">plugin reviews</a> & <a target="_blank" href="https://notificationx.com/docs/wordpress-plugin-download-stats/">downloads stats</a>.</p>
					<p>🎦 Watch video tutorial on <a target="_blank" href="https://www.youtube.com/watch?v=wZKAUKH9XQY">blog comments</a>, <a target="_blank" href="https://www.youtube.com/watch?v=wZKAUKH9XQY">reviews</a> & <a target="_blank" href="https://www.youtube.com/watch?v=wZKAUKH9XQY">downloads stats</a> to learn quickly</p>
					<p><strong>Recommended Blogs:</strong></p>
					<p>🔥 Proven Hacks To <a target="_blank" href="https://notificationx.com/blog/hacks-to-get-more-comments-wordpress/">Get More Comments on Your WordPress Blog</a> Posts</p>
					<p>🚀 How To Increase <a target="_blank" href="https://wpdeveloper.net/wordpress-plugin-download/">WordPress Plugin Download Rates & Increase Sales</a> in 2020</p>
				</div>
				<div class="nxins-type-source woo_reviews">
					<p>Make sure that you have <a target="_blank" href="https://wordpress.org/plugins/woocommerce/">WooCommerce installed & activated</a> to use this campaign. For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/woocommerce-product-reviews/">documentation</a>.</p>
					<p>🎦 Watch <a target="_blank" href="https://www.youtube.com/watch?v=bHuaOs9JWvI">video tutorial</a> to learn quickly</p>
					<p><strong>Recommended Blog:</strong></p>
					<p>🚀 How to <a target="_blank" href="https://wpdeveloper.net/ecommerce-sales-social-proof/">boost WooCommerce Sales</a> Using NotificationX</p>
				</div>
				<div class="nxins-type-source reviewx">
					<p>Make sure that you have <a target="_blank" href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a> & <a target="_blank" href="https://wordpress.org/plugins/reviewx/">ReviewX</a> installed & activated to use this campaign. For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/reviewx-notification-alerts">documentation</a>.</p>
					<p><strong>Recommended Blog:</strong></p>
					<p>🚀 How to <a target="_blank" href="https://wpdeveloper.net/ecommerce-sales-social-proof/">boost WooCommerce Sales</a> Using NotificationX</p>
				</div>
			</div>
			<div class="nxins-type reviews download_stats conversions">
				<div class="nxins-type-source freemius">
					<p>Make sure that you have <a target="_blank" href="https://dashboard.freemius.com/login/">created & signed in to Freemius account</a> to use its campaign & product sales data. For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/freemius-sales-notification/">documentation</a>.</p>
					<p>🎦 <a target="_blank" href="https://youtu.be/0uANsOSFmtw">Watch video tutorial</a> to learn quickly</p>
					<p>👉 NotificationX <a target="_blank" href="https://notificationx.com/integrations/freemius/">Integration with Freemius</a></p>
				</div>
			</div>
			<div class="nxins-type custom">
				<p>You can make custom notification for its all types of campaign. For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/custom-notification/">documentation</a>.</p>
				<p>🎦 Watch <a target="_blank" href="https://www.youtube.com/watch?v=OuTmDZ0_TEw">video tutorial</a> to learn quickly</p>
				<p><strong>Recommended Blog:</strong></p>
				<p>🔥 How to <a target="_blank" href="https://wpdeveloper.net/custom-notificationx-alert-fomo/">Display Custom Notification Alerts</a> On Your Website Using NotificationX</p>
			</div>
			<div class="nxins-type page_analytics">
				<div class="nxins-type-source google">
					<p>Make sure that you have <a target="_blank" href="https://analytics.google.com/analytics/web/">signed in to Google Analytics site</a>, to use its campaign & page analytics data. For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/google-analytics/">documentation</a>.</p>
					<p>🎦 <a target="_blank" href="https://www.youtube.com/watch?v=zZPF5nJD4mo">Watch video tutorial</a> to learn quickly</p>
					<p>👉NotificationX <a target="_blank" href="https://notificationx.com/docs/google-analytics/">Integration with Google Analytics</a></p>
				</div>
			</div>
			<div class="nxins-type email_subscription">
				<div class="nxins-type-source mailchimp">
					<p>Make sure that you have <a target="_blank" href="https://mailchimp.com/help/about-api-keys/">signed in & retrieved API key from MailChimp account</a> to use its campaign & email subscriptions data. For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/mailchimp-email-subscription-alert/">documentation</a>.</p>
					<p>🎦 <a target="_blank" href="https://youtu.be/WvX8feM5DBw">Watch video tutorial</a> to learn quickly</p>
					<p>👉 NotificationX <a target="_blank" href="https://notificationx.com/integrations/mailchimp/">Integration with MailChimp</a></p>
					<p><strong>Recommended Blogs:</strong></p>
					<p>🔥 How To Improve Your <a target="_blank" href="https://wpdeveloper.net/email-marketing-social-proof/">Email Marketing Strategy</a> With Social Proof</p>
					<p>🚀 Hacks To Grow Your <a target="_blank" href="https://wpdeveloper.net/email-subscription-list-wordpress/">Email Subscription List</a> On WordPress Website</p>
				</div>
				<div class="nxins-type-source convertkit">
					<p>Make sure that you have <a target="_blank" href="https://app.convertkit.com/users/login">signed in & retrieved your API key from ConvertKit account</a> to use its campaign & email subscriptions data. For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/convertkit-alert/">documentation</a>.</p>
					<p>🎦 <a target="_blank" href="https://youtu.be/lk_KMSBkEbY">Watch video tutorial</a> to learn quickly</p>
					<p>👉 NotificationX <a target="_blank" href="https://notificationx.com/integrations/convertkit/">Integration with ConvertKit</a></p>
					<p><strong>Recommended Blog:</strong></p>
					<p>🔥 Connect <a target="_blank" href="https://wpdeveloper.net/convertkit-social-proof/">NotificationX With ConvertKit</a>: Grow Your Audience By Leveraging Social Proof</p>
				</div>
			</div>
		</div>
		<?php
        return ob_get_flush();
	}
    public static function header_template(){
        global $pagenow;
        $class = '';
        if( ! empty( $pagenow ) ) {
            $class = 'nx-header-for-' . str_replace('.php', '', $pagenow);
        }
        ?>
            <div class="nx-settings-header <?php echo esc_attr( $class ); ?>">
                <div class="nx-header-left">
					<div class="nx-admin-header">
						<img src="<?php echo NOTIFICATIONX_URL; ?>/admin/assets/img/logo.svg" alt="NotificationX">
						<?php if( $pagenow === 'admin.php' ) : ?>
							<a class="nx-add-new-btn" href="post-new.php?post_type=notificationx"><?php echo _e('Add New', 'notificationx'); ?></a>
						<?php endif; ?>
					</div>
                </div>
                <div class="nx-header-right">
                    <span><?php _e( 'NotificationX', 'notificationx' ); ?>: <strong><?php echo NOTIFICATIONX_VERSION; ?></strong></span>
                    <?php if( defined('NOTIFICATIONX_PRO_VERSION') ) : ?>
                        <span><?php _e( 'NotificationX Pro', 'notificationx' ); ?>: <strong><?php echo NOTIFICATIONX_PRO_VERSION; ?></strong> </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php
    }
	/**
	* Get all active items.
	*
	* @return void
	*/
	public static function get_active_items() {
		// WP Query arguments.
		$source_types = NotificationX_Helper::source_types();
		$args = array(
			'post_type'         => 'notificationx',
			'posts_per_page'    => '-1',
			'post_status'		=> 'publish',
		);
		self::$active_items = [];
		// Get the notification posts.
		$posts = get_posts( $args );
		if ( count( $posts ) ) {
			foreach ( $posts as $post ) {
				$settings = NotificationX_MetaBox::get_metabox_settings( $post->ID );
				$type = '';
				if( array_key_exists( $settings->display_type, $source_types ) ) {
					$type = $settings->{ $source_types[ $settings->display_type ] };
				}
				self::$active_items[ $type ][] = $post->ID;
			}
		}

		wp_reset_postdata();

		return self::$active_items;
	}

	public function trashed_notificationx(){
		$screen = get_current_screen();
		if( $screen->id == 'edit-notificationx' ) {
			if( isset( $_GET['trashed'] ) ){
				$intval = intval($_GET['trashed']);
				if( $intval > 0 ) {
					$current_url = admin_url('admin.php?page=nx-admin');
					wp_safe_redirect( $current_url );
					exit;
				}
			}
		}
	}

	public function redirect_after_publish( $post_ID, $post, $update ){
		if( defined('NOTIFICATIONX_DEBUG') && NOTIFICATIONX_DEBUG ) {
			return;
		}
		if( ( isset( $_POST['is_quick_builder'] ) && $_POST['is_quick_builder'] == true ) || ( isset( $_GET['action'], $_GET['page'] ) && $_GET['action'] == 'nxduplicate' ) ) {
			return;
		}
		if( isset( $post->post_type ) && $post->post_type == 'notificationx' ) {
			if( isset( $post->post_status ) && $post->post_status == 'publish' ) {
				$current_url = admin_url('admin.php?page=nx-admin');
				wp_safe_redirect( $current_url );
				exit;
			}
		}
		return $post_ID;
	}

	public static function get_enabled_types() {
		// WP Query arguments.
		$source_types = NotificationX_Helper::source_types();
		$args = array(
			'post_type'      => 'notificationx',
			'posts_per_page' => '-1',
			'post_status'    => 'publish',
			'meta_key'       => '_nx_meta_active_check',
			'meta_value'     => 1
		);
		self::$enabled_types = [];
		// Get the notification posts.
		$posts = get_posts( $args );
		if ( count( $posts ) ) {
			foreach ( $posts as $post ) {
				$settings = NotificationX_MetaBox::get_metabox_settings( $post->ID );
				$type = '';
				if( array_key_exists( $settings->display_type, $source_types ) ) {
					$type = $settings->{ $source_types[ $settings->display_type ] };
				}
				self::$enabled_types[ $type ][] = $post->ID;
			}
		}

		return self::$enabled_types;
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
		if( $hook == 'notificationx_page_nx-builder' || $hook == 'notificationx_page_nx-settings' || $hook === 'toplevel_page_nx-admin' ) {
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
			$this->plugin_name . '-flatfickr',
			NOTIFICATIONX_ADMIN_URL . 'assets/css/flatfickr.min.css',
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

		if( $hook == 'notificationx_page_nx-builder' || $hook == 'notificationx_page_nx-settings' || $hook === 'toplevel_page_nx-admin' ) {
			$page_status = true;
		}

		if( $post_type != $this->type && ! $page_status ) {
			return;
		}

		if ( 'notificationx' == get_post_type() ) {
			wp_dequeue_script( 'autosave' );
		}

		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
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
			$this->plugin_name . '-flatfickr',
			NOTIFICATIONX_ADMIN_URL . 'assets/js/flatfickr.min.js',
			array( 'jquery' ), $this->version, true
		);
		wp_enqueue_script(
			$this->plugin_name,
			NOTIFICATIONX_ADMIN_URL . 'assets/js/nx-admin.min.js',
			array( 'jquery' ), $this->version, true
		);

		wp_localize_script( $this->plugin_name, 'notificationx', self::toggleFields( $hook ) );
	}

	public function toggleFields( $hook, $builder = false ){
		$args = NotificationX_MetaBox::get_args();
		if( $builder ) {
			$args = NotificationX_MetaBox::get_builder_args();
		}

		$toggleFields = $hideFields = $conditions = array();

		$tabs = $args[ 'tabs' ];

		if( $hook === 'notificationx_page_nx-settings' ) {
			$tabs = NotificationX_Settings::settings_args();
		}

		if( ! empty( $tabs ) ) {
			foreach( $tabs as $tab_id => $tab ) {
				$sections = isset( $tab['sections'] ) ? $tab[ 'sections' ] : [];
				if( ! empty( $sections ) ) {
					foreach( $sections as $section_id => $section ) {
						$fields = isset( $section['fields'] ) ? $section[ 'fields' ] : [];
						if( ! empty( $fields ) ) {
							foreach( $fields as $field_key => $field ) {
								$options = isset( $field['options'] ) ? $field['options'] : [];
								if( isset( $field['fields'] ) ) {
									foreach( $field['fields'] as $inner_field_key => $inner_field ) {
										$options = isset( $inner_field['options'] ) ? $inner_field['options'] : [];
										if( isset( $inner_field['hide'] ) && ! empty( $inner_field['hide'] ) && is_array( $inner_field['hide'] ) ) {
											foreach( $inner_field['hide'] as $key => $hide ) {
												if( strpos( $key, '!', 0 ) === 0 ) {
													if( ! empty( $options ) ) {
														$ignored_key = substr( $key, 1, strlen( $key ) );
														unset( $options[ $ignored_key ] );
														foreach( $options as $dkey => $value ) {
															if( empty( $hideFields[ $inner_field_key ][ $dkey ] ) ) {
																$hideFields[ $inner_field_key ][ $dkey ] = $hide;
															} else {
																$hideFields[ $inner_field_key ][ $dkey ] = array_merge_recursive( $hideFields[ $inner_field_key ][ $dkey ], $hide );
															}
														}
													}
													continue;
												}
												if( isset( $hideFields[ $inner_field_key ][ $key ] ) ) {
													$hideFields[ $inner_field_key ][ $key ] = array_merge_recursive( $hideFields[ $inner_field_key ][ $key ], $hide );
												} else {
													$hideFields[ $inner_field_key ][ $key ] = $hide;
												}
											}
										}
										if( isset( $inner_field['dependency'] ) && ! empty( $inner_field['dependency'] ) && is_array( $inner_field['dependency'] ) ) {
											foreach( $inner_field['dependency'] as $key => $dependency ) {
												$conditions[ $inner_field_key ][ $key ] = $dependency;
											}
										}
									}
								}
								if( isset( $field['hide'] ) && ! empty( $field['hide'] ) && is_array( $field['hide'] ) ) {
									foreach( $field['hide'] as $key => $hide ) {
										$hideFields[ $field_key ][ $key ] = $hide;
									}
								}
								if( isset( $field['dependency'] ) && ! empty( $field['dependency'] ) && is_array( $field['dependency'] ) ) {
									foreach( $field['dependency'] as $key => $dependency ) {
										if( strpos( $key, '!', 0 ) === 0 ) {
											if( ! empty( $options ) ) {
												$ignored_key = substr( $key, 1, strlen( $key ) );
												unset( $options[ $ignored_key ] );
												foreach( $options as $key => $value ) {
													if( empty( $conditions[ $field_key ][ $key ] ) ) {
														$conditions[ $field_key ][ $key ] = $dependency;
													} else {
														$conditions[ $field_key ][ $key ] = array_merge_recursive( $conditions[ $field_key ][ $key ], $dependency );
													}
												}
											}
											continue;
										}
										if( isset( $conditions[ $field_key ][ $key ] ) ) {
											$conditions[ $field_key ][ $key ] = array_merge_recursive( $conditions[ $field_key ][ $key ], $dependency );
										} else {
											$conditions[ $field_key ][ $key ] = $dependency;
										}
									}
								}
							}
						}
					}
				}
			}
		}

		$template = apply_filters( 'nx_template_name', array() );
		$template_settings = array();
		global $post;
		if( is_object( $post ) ) {
			$template_settings = apply_filters( 'nx_template_settings_by_theme', array(), $post );
		}

		return array(
			'toggleFields'      => $conditions, // TODO: toggling system has to be more optimized!
			'hideFields'        => $hideFields,
			'template'          => $template,
			'template_settings' => $template_settings,
			'title_of_types'    => NotificationX_Helper::types_title(),
			'source_types'      => NotificationX_Helper::source_types(),
			'theme_sources'     => NotificationX_Helper::theme_sources(),
			'template_keys'     => NotificationX_Helper::template_keys(),
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
					$type = is_array( $type ) ? $type['source'] : $type;
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

	public static function notification_toggle( $status = true, $post_id ){
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
		if( isset( $_POST['url'] ) ) {
			wp_safe_redirect( $_POST['url'] );
		}
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
		$nx_create_notification = apply_filters( 'nx_create_notification', 'edit_posts', 'notification_roles' );
		$args = array(
			'labels'              => $labels,
			'hierarchical'        => false,
			'description'         => '',
			'taxonomies'          => array( '' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'notificationx',
			'show_in_admin_bar'   => $nx_create_notification,
			'show_in_rest'        => false,
			'menu_position'       => 80,
			'menu_icon'           => NOTIFICATIONX_ADMIN_URL . 'assets/img/nx-menu-icon-colored.png',
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

		$this->builder_args = NotificationX_MetaBox::get_builder_args();
		$this->metabox_id   = $this->builder_args['id'];

		$settings_class = new NotificationX_Settings();

		$nx_create_notification = apply_filters( 'nx_create_notification', 'edit_posts', 'notification_roles' );
		$nx_settings_caps = apply_filters( 'nx_settings_caps', 'delete_users', 'settings_roles' );

		$settings = apply_filters( 'notificationx_admin_menu', array(
			'nx-settings'   => array(
				'title'      => __('Settings', 'notificationx'),
				'capability' => $nx_settings_caps,
				'callback'   => array( $settings_class, 'settings_page' )
			),
			'nx-builder'   => array(
				'title'      => __('Quick Builder', 'notificationx'),
				'capability' => $nx_create_notification,
				'callback'   => array( $this, 'quick_builder' )
			),
		) );

		$hook = add_menu_page( 'NotificationX', 'NotificationX', $nx_create_notification, 'nx-admin', array( $this, 'notificationx' ), NOTIFICATIONX_ADMIN_URL . 'assets/img/nx-menu-logo.svg', 80 );
		add_action('load-' . $hook, array( $this, 'screen_options' ) );
		/**
		 * @since 1.2.2
		 */
		add_submenu_page( 'nx-admin', 'All NotificationX', 'All NotificationX', $nx_create_notification, 'nx-admin' );
		/**
		 * @since 1.2.1
		 */
		add_submenu_page( 'nx-admin', __('Add New', 'notificationx'), __('Add New', 'notificationx'), $nx_create_notification, 'post-new.php?post_type=notificationx');
		foreach( $settings as $slug => $setting ) {
			$cap  = isset( $setting['capability'] ) ? $setting['capability'] : 'delete_users';
			$hook = add_submenu_page( 'nx-admin', $setting['title'], $setting['title'], $cap, $slug, $setting['callback'] );
		}
	}
	/**
	 * Render screen options
	 * @since 1.2.6
	 */
	public function screen_options(){
		$option = 'per_page';
		$args = array(
			'label' => __('Number of Notification Per Page', 'notificationx'),
			'default' => 10,
			'option' => 'notification_per_page'
		);

		add_screen_option( $option, $args );
	}
	/**
	 * Save screen option
	 * @since 1.2.6
	 */
	public function save_screen_options($status, $option, $value) {
		if ( 'notification_per_page' == $option ) return $value;
		return $status;
	}
	public function highlight_admin_menu( $parent_file ){
		if( $parent_file === 'notificationx' ) {
			return 'nx-admin';
		}
		return $parent_file;
	}
	public function highlight_admin_submenu( $submenu_file, $parent_file ){
		if( $parent_file == 'nx-admin' && $submenu_file == 'edit.php?post_type=notificationx' ) {
			return "post-new.php?post_type=notificationx";
		}
		return $submenu_file;
	}

	public static function count_posts( $type = 'notificationx', $perm = '' ) {
		global $wpdb;
		if ( ! post_type_exists( $type ) ) {
			return new stdClass;
		}
		$cache_key = 'nx_counts_cache';
		self::$counts = wp_cache_get( $cache_key, 'counts' );
		if ( false !== self::$counts ) {
			return self::$counts;
		}
		$query = "SELECT ID, post_status, meta_key, meta_value FROM {$wpdb->posts} INNER JOIN {$wpdb->postmeta} ON ID = post_id WHERE post_type = %s AND meta_key = '_nx_meta_active_check'";
		$results = (array) $wpdb->get_results( $wpdb->prepare( $query, $type ), ARRAY_A );
		$counts  = array_fill_keys( array( 'enabled', 'disabled', 'trash', 'publish' ), 0 );
		$disable = 0;
		$enable = 0;
		foreach ( $results as $row ) {
			$counts[ 'publish' ] = $counts['publish'] + ( $row['post_status'] === 'publish' ? 1 : 0 );
			$counts[ 'trash' ] = $counts['trash'] + ( $row['post_status'] === 'trash' ? 1 : 0 );

			if( $row[ 'meta_value' ] == 0 ) {
				$disable = 1;
				$enable = 0;
			}
			if( $row[ 'meta_value' ] == 1 ) {
				$disable = 0;
				$enable = 1;
			}

			if( $disable == 1 && $row['post_status'] == 'trash' ) {
				$disable = 0;
			}

			if( $enable == 1 && $row['post_status'] == 'trash' ) {
				$enable = 0;
			}

			$counts[ 'disabled' ] = $counts[ 'disabled' ] + $disable;
			$counts[ 'enabled' ] = $counts[ 'enabled' ] + $enable;
		}
		self::$counts = (object) $counts;
		wp_cache_set( $cache_key, self::$counts, 'counts' );
		return self::$counts;
	}

	public function notificationx(){
		$all_active_class = '';
		$enabled_active_class = '';
		$disabled_active_class = '';
		$trash_active_class = '';
		$pagenow = '';
		$paged = 1;

		$count_posts            = self::count_posts();
		$screen                 = get_current_screen();
		$user                   = get_current_user_id();
		$option                 = $screen->get_option('per_page', 'option');
		$per_page               = get_user_meta($user, $option, true);
		$per_page               = empty( $per_page ) ? 10 : $per_page;
		$total_page             = ceil( $count_posts->publish / $per_page );
		$pagination_current_url = admin_url('admin.php?page=nx-admin');

		$post_args = array(
			'post_type' => 'notificationx',
			'numberposts' => -1,
			'posts_per_page' => $per_page,
		);

		if( isset( $_GET['page'] ) && $_GET['page'] == 'nx-admin' ) {
			$all_active_class = 'class="active"';
			$pagenow = 'publish, draft';
			if( isset( $_GET['status'] ) && $_GET['status'] == 'enabled' ) {
				$pagination_current_url = add_query_arg('status', 'enabled', $pagination_current_url);
				$enabled_active_class   = 'class="active"';
				$all_active_class       = '';
				$pagenow                = 'publish';
				$total_page  = ceil( $count_posts->enabled / $per_page );
				$post_args = array_merge( $post_args, array( 'meta_query' => array(
					array(
						'key'     => '_nx_meta_active_check',
						'value'   => 1,
						'compare' => '=',
					),
				)));
			}
			if( isset( $_GET['status'] ) && $_GET['status'] == 'disabled' ) {
				$pagination_current_url = add_query_arg('status', 'disabled', $pagination_current_url);
				$disabled_active_class  = 'class="active"';
				$all_active_class       = '';
				$pagenow                = 'publish';
				$total_page  = ceil( $count_posts->disabled / $per_page );
				$post_args = array_merge( $post_args, array( 'meta_query' => array(
					array(
						'key'     => '_nx_meta_active_check',
						'value'   => 0,
						'compare' => '=',
					),
				)));
			}
			if( isset( $_GET['status'] ) && $_GET['status'] == 'trash' ) {
				$pagination_current_url = add_query_arg('status', 'trash', $pagination_current_url);
				$trash_active_class     = 'class="active"';
				$all_active_class       = '';
				$pagenow                = 'trash';
				$total_page  = ceil( $count_posts->trash / $per_page );
			}
			if( isset( $_GET['paged'] ) ) {
				if( intval( $_GET['paged'] ) > 0 ) {
					$paged = intval( $_GET['paged'] );
				}
			}
		}

		$post_args = array_merge( $post_args, array( 'post_status' => explode(', ', $pagenow), 'offset' => ( ( $paged - 1 ) * $per_page ) ));

		$notificationx = get_posts( $post_args );

		$table_header = apply_filters( 'nx_admin_table_header', array(
			'NotificationX Title',
			__('Preview', 'notificationx'),
			__('Status', 'notificationx'),
			__('Type', 'notificationx'),
			__('Stats', 'notificationx'),
			__('Date', 'notificationx'),
		));
		include_once NOTIFICATIONX_ADMIN_DIR_PATH . 'partials/nx-admin.php';
	}
	public function get_stats( $idd ){
		$from_pro = apply_filters('nx_admin_table_stats', '', $idd );
		if( $from_pro == '' ) {
			if( ! NX_CONSTANTS::is_pro() ) {
				echo '<img data-swal="true" class="nx-stats-tease" width="45" src="'. NOTIFICATIONX_ADMIN_URL .'/assets/img/pro.svg"/>';
			} else {
				echo sprintf('<a href="%s">%s</a>', admin_url('admin.php?page=nx-settings#email_analytics_reporting'), __('Disabled', 'notificationx'));
			}
		}
		echo $from_pro;
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
	//TODO: Notification Preview Not Visible for now.
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
		$output = '<div id="'. esc_attr( $unique_id ) .'" class="nx-notification '. implode( ' ', NotificationX_Extension::get_classes( $settings ) ) .'">';
		$output .= '<div '. NotificationX_Public::generate_preview_css( $settings ) .' class="notificationx-inner '. implode( ' ', NotificationX_Extension::get_classes( $settings, 'inner' ) ) .'">';
		$output .= '<div class="notificationx-image nx-preview-image">';
		$output .= '<img class="'. implode( ' ', NotificationX_Extension::get_classes( $settings, 'img' ) ) .'" src="'. NOTIFICATIONX_ADMIN_URL . 'assets/img/placeholder-300x300.png" alt="NotificationX">';
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
			$output .= '<svg width="12px" height="16px" viewBox="0 0 387 392" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><desc>Created with Sketch.</desc><defs></defs><g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g id="NotificationX_final" transform="translate(-1564.000000, -253.000000)"><g id="Group" transform="translate(1564.000000, 253.000000)"><path d="M135.45,358.68 C173.45,358.68 211.27,358.68 249.07,358.68 C247.02,371.83 221.24,388.59 199.26,390.98 C173.92,393.73 143.23,378.38 135.45,358.68 Z" id="Shape" fill="#5614D5" fill-rule="nonzero"></path><path d="M372.31,305.79 C369.97,305.59 367.6,305.71 365.24,305.71 C359.63,305.7 354.02,305.71 347.08,305.71 C347.08,301.43 347.08,298.42 347.08,295.41 C347.07,248.75 347.25,202.09 346.91,155.43 C346.83,144.89 345.88,134.19 343.79,123.87 C326.39,37.9 239.94,-16.19 154.81,5.22 C86.84,22.31 37.91,84.26 38.19,154.7 C38.36,197.12 38.21,239.54 38.2,281.96 C38.2,285.8 38.18,297.79 38.16,305.7 C32.98,305.66 18.07,305.57 12.86,305.88 C5.13,306.33 -0.06,312.31 0.04,319.97 C0.14,327.43 5.08,332.74 12.67,333.42 C14.78,333.61 16.91,333.57 19.03,333.57 C134.74,333.61 250.46,333.64 366.17,333.66 C368.29,333.66 370.42,333.69 372.53,333.48 C380.01,332.73 385.14,327.23 385.28,319.95 C385.41,312.58 379.86,306.44 372.31,305.79 Z" id="Shape" fill="#5614D5" fill-rule="nonzero"></path><circle id="Oval" fill="#836EFF" fill-rule="nonzero" cx="281.55" cy="255.92" r="15.49"></circle><path d="M295.67,140.1 L295.91,139.94 C295.7,138.63 295.52,137.29 295.27,136.02 C285.87,89.57 245.83,55.34 198.79,52.53 C198.73,52.53 198.67,52.52 198.61,52.52 C196.59,52.4 194.57,52.32 192.53,52.32 C192.48,52.32 192.44,52.32 192.39,52.32 C192.34,52.32 192.3,52.32 192.25,52.32 C190.21,52.32 188.18,52.4 186.17,52.52 C186.11,52.52 186.05,52.53 185.99,52.53 C138.95,55.34 98.91,89.57 89.51,136.02 C89.25,137.29 89.07,138.63 88.87,139.94 L89.11,140.1 C88.2,145.6 87.72,151.22 87.74,156.9 C87.76,161.42 87.77,256.77 87.78,269.74 L119.91,304.42 C119.91,280.14 119.9,170.57 119.85,156.78 C119.72,124.18 142.81,94.69 174.76,86.66 C177.41,85.99 180.09,85.5 182.78,85.13 C183.23,85.07 183.67,85 184.13,84.95 C185.15,84.83 186.17,84.74 187.18,84.66 C188.64,84.56 190.1,84.48 191.58,84.47 C191.85,84.47 192.12,84.45 192.39,84.44 C192.66,84.44 192.93,84.46 193.2,84.47 C194.68,84.48 196.14,84.56 197.6,84.66 C198.62,84.74 199.64,84.83 200.65,84.95 C201.1,85 201.55,85.07 202,85.13 C204.69,85.5 207.37,85.99 210.02,86.66 C241.96,94.69 265.06,124.19 264.93,156.78 C264.91,161.95 264.9,207.07 264.89,228.18 L297.03,206.73 C297.03,194.5 297.04,158.28 297.04,156.91 C297.06,151.21 296.59,145.6 295.67,140.1 Z" id="Shape" fill="#836EFF" fill-rule="nonzero"></path><path d="M31.94,305.72 C25.58,305.85 19.2,305.51 12.86,305.88 C5.13,306.33 -0.06,312.31 0.04,319.97 C0.14,327.43 5.08,332.74 12.67,333.42 C14.78,333.61 16.91,333.57 19.03,333.57 C134.74,333.61 250.45,333.63 366.17,333.66 C368.29,333.66 370.42,333.69 372.53,333.48 C380.01,332.73 385.14,327.23 385.28,319.95 C385.42,312.58 379.87,306.45 372.32,305.79 C369.98,305.59 367.61,305.71 365.25,305.71 C359.64,305.7 354.03,305.71 347.09,305.71 C347.09,301.43 347.09,298.42 347.09,295.41 C347.08,254.74 347.2,214.07 347.01,173.41 L131.62,317.03 L53.58,232.81 L87.05,202.02 L138.72,257.62 L343.2,121.26 C324.59,36.81 239.08,-15.98 154.82,5.21 C86.85,22.3 37.92,84.25 38.2,154.69 C38.37,197.11 38.22,239.53 38.21,281.95 C38.21,287.84 38.3,293.74 38.16,299.62" id="Shape"></path><path d="M346.91,155.42 C346.95,161.41 346.97,167.41 347,173.4 L386.14,147.41 L360.9,109.57 L343.2,121.26 C343.39,122.13 343.62,122.98 343.8,123.85 C345.88,134.18 346.84,144.89 346.91,155.42 Z" id="Shape" fill="#00F9AC" fill-rule="nonzero"></path><path d="M87.05,202.03 L53.58,232.82 L131.62,317.04 L347,173.41 C346.97,167.42 346.96,161.42 346.91,155.43 C346.83,144.89 345.88,134.19 343.79,123.87 C343.61,122.99 343.39,122.14 343.19,121.28 L138.72,257.63 L87.05,202.03 Z" id="Shape"></path><path d="M87.05,202.03 L53.58,232.82 L131.62,317.04 L347,173.41 C346.97,167.42 346.96,161.42 346.91,155.43 C346.83,144.89 345.88,134.19 343.79,123.87 C343.61,122.99 343.39,122.14 343.19,121.28 L138.72,257.63 L87.05,202.03 Z" id="Shape" fill="#21D8A3" fill-rule="nonzero" opacity="0.9"></path></g></g></g></svg>';
			$output .= ' by <a rel="nofollow" href="'. NOTIFICATIONX_PLUGIN_URL .'?utm_source='. urlencode( home_url() ) .'&utm_medium=notificationx" target="_blank" class="nx-powered-by">NotificationX</a>';
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
	/**
	 * Admin Init For User Interactions
	 * @return void
	 */
	public function admin_init( $hook ){
		/**
		 * NotificationX Admin URL
		 */
		$current_url = admin_url('admin.php?page=nx-admin');
		/**
		 * For Duplicate NotificationX
		 */
		$this->duplicate_notificationx( $current_url );
		/**
		 * For Re-generate Notifications for current notification type
		 * @since 1.4.0
		 */
		$this->regenerate_notifications( $current_url );
		/**
		 * For Empty Trash
		 */
		$this->empty_trash( $current_url );
		/**
		 * For Enable And Disable
		 */
		$this->enable_disable( $current_url );
		/**
		 * For Quick Builder Submit
		 */
		$this->quick_builder_submit( $current_url );

	}
	/**
	 * For Empty Trash
	 * @return void
	 */
	protected function empty_trash( $current_url = '' ) {
		if( empty( $current_url ) ) {
			return;
		}
		if( isset( $_GET['delete_all'], $_GET['page'] ) && $_GET['delete_all'] == true && $_GET['page'] == 'nx-admin' ) {
			$notificationx = new WP_Query(array(
				'post_type' => 'notificationx',
				'post_status' => array('trash'),
				'numberposts' => -1,
			));
			if( $notificationx->have_posts() ) {
				while( $notificationx->have_posts() ) : $notificationx->the_post();
					$iddd = get_the_ID();
					wp_delete_post( $iddd );
				endwhile;
				wp_safe_redirect( $current_url ); // TODO: after all remove trash redirect.
				die;
			}
		}
	}
	/**
	 * For Enable and Disable NotificationX.
	 * @param string $current_url
	 * @return void
	 */
	protected function enable_disable( $current_url = '' ){
		if( empty( $current_url ) ) {
			return;
		}
		// For Enable & Disable
		if( isset( $_GET['status'], $_GET['page'] ) && $_GET['page'] == 'nx-admin' ) {
			$post_status         = self::count_posts();
			$get_enabled_post    = $post_status->enabled;
			$get_disabled_post   = $post_status->disabled;
			$trash_notificationx = $post_status->trash;

			if( ( $_GET['status'] == 'disabled' && $get_disabled_post == 0 )
				|| ( $_GET['status'] == 'trash' && $trash_notificationx == 0 )
				|| ( $_GET['status'] == 'enabled' && $get_enabled_post == 0 )
			) {
				wp_safe_redirect( $current_url );
				die;
			}
		}
	}
	/**
	 * For Duplicate NotificationX
	 * @param string $current_url
	 * @return void
	 */
	protected function duplicate_notificationx( $current_url = '' ){
		if( empty( $current_url ) ) {
			return;
		}
		// Duplicating NotificationX
		if( isset( $_GET['action'], $_GET['page'], $_GET['post'], $_GET['nx_duplicate_nonce'] )
		&& $_GET['action'] === 'nxduplicate' && $_GET['page'] === 'nx-admin' ) {
			if( wp_verify_nonce( $_GET['nx_duplicate_nonce'], 'nx_duplicate_nonce' ) ) {
				$nx_post_id = intval( $_GET['post'] );
				$get_post = get_post( $nx_post_id );
				$post_data = json_decode( json_encode( $get_post ), true );
				unset( $post_data['ID'] );
				unset( $post_data['post_date'] );
				unset( $post_data['post_date_gmt'] );
				$post_data['post_title'] = $post_data['post_title'] . ' - Copy';
				$duplicate_post_id = wp_insert_post( $post_data );
				$duplicate_post_id = intval( $duplicate_post_id );
				$get_post_meta = get_metadata( 'post', $nx_post_id );
				if( ! empty( $get_post_meta ) ) {
					foreach( $get_post_meta as $key => $value ){
						if( in_array( $key, array( '_edit_lock', '_edit_last', '_nx_meta_views', '_nx_meta_active_check' ) ) ) {
							continue;
						}
						if( $key === '_nx_meta_impression_per_day' ) {
							add_post_meta( $duplicate_post_id, $key, array() );
						} else {
							add_post_meta( $duplicate_post_id, $key, $value[0] );
						}
					}
				}
				wp_safe_redirect( $current_url );
				exit;
			}
		}
	}
	/**
	 * This method is responsible for re generating notification for single type.
	 * @param string $current_url
	 * @since 1.4.0
	 */
	protected function regenerate_notifications( $current_url = '' ){
		if( empty( $current_url ) ) {
			return;
		}
		// Duplicating NotificationX
		if( isset( $_GET['action'], $_GET['page'], $_GET['nx_type'], $_GET['nx_regenerate_nonce'] )
		&& $_GET['action'] === 'nx_regenerate' && $_GET['page'] === 'nx-admin' ) {
			if( wp_verify_nonce( $_GET['nx_regenerate_nonce'], 'nx_regenerate_nonce' ) ) {
				$nx_type = $_GET['nx_type'];
				$post = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : null;
				$from = isset( $_GET['from'] ) ? intval( $_GET['from'] ) : 2;
				$last = isset( $_GET['last'] ) ? intval( $_GET['last'] ) : 20;
				global $nx_extension_factory;
				$extension_class = $nx_extension_factory->get_extension( $nx_type );
				if( ! empty( $extension_class ) ) {
					$extension = new $extension_class();
					if( method_exists( $extension, 'get_notification_ready' ) ) {
						$nx_notificationx = NotificationX_DB::get_notifications();
						if( isset( $nx_notificationx[ $nx_type ] ) ) {
							unset( $nx_notificationx[ $nx_type ] );
							NotificationX_DB::update_notifications( $nx_notificationx );
						}
						$extension->get_notification_ready( $nx_type, ['_nx_meta_display_from' => $from, '_nx_meta_display_last' => $last, 'post' => $post ] );
					}
				}
				wp_safe_redirect( $current_url );
				exit;
			}
		}
	}
	/**
	 * For Quick Builder Submit
	 * @return void
	 */
	protected function quick_builder_submit( $current_url = '' ){
		if( empty( $current_url ) ) {
			return;
		}
		if( isset( $_POST[ 'nx_builder_add_submit' ], $_POST['is_quick_builder'] ) && $_POST['is_quick_builder'] ) :
			$flag = true;
			if ( ! isset( $_POST[$this->metabox_id . '_nonce'] ) || ! wp_verify_nonce( $_POST[$this->metabox_id . '_nonce'], $this->metabox_id ) ) {
				$flag = false;
				return;
			}

			if( $flag ) {
				/**
				 * TODO: it has to be update in a new way! more dynamic way!
				 */
				if( $_POST['nx_meta_display_type'] == 'press_bar' )  {
					$title = __('NotificationX - Notification Bar', 'notificationx');
				} elseif( $_POST['nx_meta_display_type'] == 'comments' )  {
					$title = __('NotificationX - WP Comments', 'notificationx');
				} elseif( $_POST['nx_meta_display_type'] == 'conversions' )  {
					$conversions = NotificationX_Helper::conversion_from();
					$sub = isset( $conversions[$_POST['nx_meta_conversion_from']]['title'] ) ? $conversions[$_POST['nx_meta_conversion_from']]['title'] : '';
					$title = 'NotificationX - ' . $sub;
				} else {
					$title_temp = NotificationX_Helper::notification_types( $_POST['nx_meta_display_type'] );
					$title = 'NotificationX - ' . $title_temp;
				}
				$_POST['post_type'] = 'notificationx';
				$postdata = array(
					'post_type'   => 'notificationx',
					'post_title'  => $title . ' - ' . date( get_option( 'date_format' ), current_time( 'timestamp' ) ),
					'post_status' => 'publish',
					'post_author' => get_current_user_id(),
				);
				$p_id = null;
				$p_id = wp_insert_post($postdata);
				if( ( $p_id || ! is_wp_error( $p_id ) ) && ! is_null( $p_id ) ) {
					do_action( 'nx_before_builder_submit', $_POST );
					// saving builder meta data with post
					NotificationX_MetaBox::save_data( $this->builder_data( $_POST ), $p_id );
					/**
					* Safely Redirect to NotificationX Page
					*/
					wp_safe_redirect( $current_url );
					exit;
				}
			}
		endif;
	}

    /**
     * This function is hooked
     * @hooked plugin_action_links_
     * @param array $links
     * @return array
     * @since 1.2.4
     */
    public function nx_action_links( $links ) {
        $deactivate_link = isset( $links['deactivate'] ) ? $links['deactivate'] : '';
        unset($links['deactivate']);
		$links['settings'] = '<a href="' . admin_url('admin.php?page=nx-settings') . '">' . __('Settings','notificationx') .'</a>';
		if( ! empty( $deactivate_link ) ) {
			$links['deactivate'] = $deactivate_link;
		}
        if( ! is_plugin_active('notificationx-pro/notificationx-pro.php' ) ) {
            $links['pro'] = '<a href="' . esc_url('http://wpdeveloper.net/in/upgrade-notificationx') . '" target="_blank" style="color: #349e34;"><b>' . __('Go Pro','notificationx') .'</b></a>';
        }
        return $links;
    }

    /**
     * This function is hooked
     * @hooked plugin_row_meta
     * @param array $links
     * @param string $file
     * @return array
     * @since 1.2.4
     */
    public function nx_row_meta($links, $file) {
        if( NOTIFICATIONX_BASENAME == $file ){
            $links['docs'] = '<a href="' . esc_url('https://notificationx.com/docs/?utm_medium=admin&utm_source=wp.org&utm_term=nx') . '" target="_blank">' . __('Docs & FAQ','notificationx') .'</a>';
        }
        return $links;
    }
}
