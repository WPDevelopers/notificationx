<?php
/**
 * Admin Class File.
 *
 * @package NotificationX\Admin
 */

namespace NotificationX\Admin;

use NotificationX\NotificationX;
use NotificationX\Admin\Reports\ReportEmail;
use NotificationX\Core\Analytics;
use NotificationX\Core\Database;
use NotificationX\Core\PostType;
use NotificationX\Core\Upgrader;
use NotificationX\GetInstance;
use NotificationX\Extensions\ExtensionFactory;

use PriyoMukul\WPNotice\Notices;
use PriyoMukul\WPNotice\Utils\CacheBank;
use PriyoMukul\WPNotice\Utils\NoticeRemover;

/**
 * Admin Class, this class is responsible for all Admin Actions
 * @method static Admin get_instance($args = null)
 */
class Admin {
    /**
     * Instance of Admin
     *
     * @var Admin
     */
    use GetInstance;
    /**
     * Assets Path and URL
     */
    const ASSET_URL  = NOTIFICATIONX_ASSETS . 'admin/';
    const ASSET_PATH = NOTIFICATIONX_ASSETS_PATH . 'admin/';
    const VIEWS_PATH = NOTIFICATIONX_INCLUDES . 'Admin/views/';

    private $insights = null;

    /**
	 * @var CacheBank
	 */
	private static $cache_bank;

    /**
     * Initially Invoked
     * when its initialized.
     */
    public function __construct(){
        /**
         * Admin Dashboard Widget
         * For Analytics
         */
        Analytics::get_instance();
        ReportEmail::get_instance();
        ImportExport::get_instance();
        XSS::get_instance();
        add_action('init', [$this, 'init'], 5);
    }

    /**
     * This method is reponsible for Admin Menu of
     * NotificationX
     *
     * @return void
     */
    public function init(){
        if( ! NotificationX::is_pro() ){
            $this->plugin_usage_insights();
            $this->admin_notices();
        }
        add_action('admin_init', [$this, 'admin_init']);
        add_action('admin_menu', [$this, 'menu'], 10);
        PostType::get_instance();
        Settings::get_instance()->init();
        Entries::get_instance();
    }

    /**
     * This method is responsible for Admin Menu of
     * NotificationX
     *
     * @return void
     */
    public function admin_init(){
        DashboardWidget::get_instance();
        add_action('in_admin_header', [ $this, 'hide_others_plugin_admin_notice' ], 99);
    }

    /**
     * This method is reponsible for Admin Menu of
     * NotificationX
     *
     * @return void
     */
    public function menu(){
        add_menu_page(
            'NotificationX',
            'NotificationX',
            'read_notificationx', // User Permision.
            'nx-admin',
            array( $this, 'views' ),
            self::ASSET_URL . 'images/logo-icon.svg',
            80
        );
		add_submenu_page( 'nx-admin', __('All NotificationX', 'notificationx'), __('All NotificationX', 'notificationx'), 'read_notificationx', 'nx-admin', null, 1 );
    }

    /**
     * Admin Views
     *
     * @return void
     */
    public function views() {
        // react script included in PostType::admin_enqueue_scripts();
        include_once Admin::VIEWS_PATH . 'main.views.php';
    }
    /**
     * Get File Modification Time or URL
     *
     * @param string $file  File relative path for Admin
     * @param boolean $url  true for URL return
     * @return void|string|integer
     */
    public function file( $file, $url = false ){
        if( $url ) {
            return self::ASSET_URL . $file;
        }
        return filemtime( self::ASSET_PATH . $file );
    }

    /**
     * This method is responsible for re generating notification for single type.
     * @param string $current_url
     * @since 1.4.0
     */
    public function regenerate_notifications($params) {
        $post_id = intval($params['nx_id']);
        // @todo should not query the settings here. source and other two should be passed in param.
        $post = PostType::get_instance()->get_post($post_id);
        $source = isset($post['source']) ? $post['source'] : false;
        $extension = ExtensionFactory::get_instance()->get($source);
        if (!empty($extension) && method_exists($extension, 'get_notification_ready') && $extension->is_active()) {
            Entries::get_instance()->delete_entries($post_id);
            $result = $extension->get_notification_ready($post, $post_id);
            return true;
        }
        return false;
    }

    /**
     * This method is responsible for re generating notification for single type.
     * @param string $current_url
     * @since 1.4.0
     */
    public function reset_notifications($params) {
        $nx_id = null;
        if (!empty($entry_key)) {
            $nx_id = $params['entry_key'];
        }
        if (!empty($params['nx_id'])) {
            $nx_id = $params['nx_id'];
        }
        if (!empty($nx_id)) {
            return Analytics::get_instance()->delete_analytics($nx_id);
        }
    }


    
    public function admin_notices(){
        self::$cache_bank = CacheBank::get_instance();
		try {
			$this->notices();
		} catch ( \Exception $e ) {
			unset( $e );
		}
        // Remove OLD notice from 1.0.0 (if other WPDeveloper plugin has notice)
		NoticeRemover::get_instance( '1.0.0' );
    }


    public function hide_others_plugin_admin_notice() 
    {
        $current_screen = get_current_screen();
        $hide_on = ['toplevel_page_nx-admin','notificationx_page_nx-edit','notificationx_page_nx-settings','notificationx_page_nx-analytics','notificationx_page_nx-builder'];
        if ( $current_screen && isset( $current_screen->base ) &&  in_array($current_screen->base, $hide_on) ) {
            remove_all_actions('user_admin_notices');
            remove_all_actions('admin_notices');
        }
    }

    public function notices() {
        $notices = new Notices([
			'id'             => 'notificationx',
			'store'          => 'options',
			'storage_key'    => 'notices',
			'version'        => '1.0.0',
			'lifetime'       => 3,
			'stylesheet_url' => '',
			'styles'         => self::ASSET_URL . 'css/wpdeveloper-review-notice.css',
			'priority'       => 5,
        ]);

        $_review_notice = [
            'thumbnail' => self::ASSET_URL . 'images/nx-icon.svg',
            'html' => '<p>'. __( 'We hope you\'re enjoying NotificationX! Could you please do us a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation?', 'notificationx' ) .'</p>',
            'links' => [
                'later' => array(
                    'link' => 'https://wpdeveloper.com/review-notificationx',
                    'target' => '_blank',
                    'label' => __( 'Ok, you deserve it!', 'notificationx' ),
                    'icon_class' => 'dashicons dashicons-external',
                ),
                'allready' => array(
                    'label' => __( 'I already did', 'notificationx' ),
                    'icon_class' => 'dashicons dashicons-smiley',
                    'attributes' => [
                        'data-dismiss' => true
                    ],
                ),
                'maybe_later' => array(
                    'label' => __( 'Maybe Later', 'notificationx' ),
                    'icon_class' => 'dashicons dashicons-calendar-alt',
                    'attributes' => [
                        'data-later' => true
                    ],
                ),
                'support' => array(
                    'link' => 'https://wpdeveloper.com/support',
                    'label' => __( 'I need help', 'notificationx' ),
                    'icon_class' => 'dashicons dashicons-sos',
                ),
                'never_show_again' => array(
                    'label' => __( 'Never show again', 'notificationx' ),
                    'icon_class' => 'dashicons dashicons-dismiss',
                    'attributes' => [
                        'data-dismiss' => true
                    ],
                )
            ]
        ];

        $notices->add(
            'review',
            $_review_notice,
            [
                'start'       => $notices->strtotime( '+7 day' ),
                'recurrence'  => 30,
                'dismissible' => true,
                'refresh'     => NOTIFICATIONX_VERSION,
            ]
        );

        $notices->add(
            'opt_in',
            [ $this->insights, 'notice' ],
            [
                'classes'     => 'updated put-dismiss-notice',
                'start'       => $notices->strtotime( '+30 days' ),
                'dismissible' => true,
                'refresh'     => NOTIFICATIONX_VERSION,
                'do_action'   => 'wpdeveloper_notice_clicked_for_notificationx',
                'display_if'  => ! is_array( $notices->is_installed( 'notificationx-pro/notificationx-pro.php' ) )
            ]
        );

        $notice_text = sprintf('<div style="display: flex; align-items: center;">%s <a class="button button-primary" style="margin-left: 10px; background: #5614d5; border-color: #5614d5;" target="_blank" href="%s">%s</a></div>', __( '<p><strong>Black Friday Exclusive:</strong> SAVE up to 40% & access to <strong>NotificationX Pro</strong> features.</p>', 'notificationx' ), esc_url( 'https://notificationx.com/#pricing' ), __('Grab The Offer', 'notificationx') );

        $_black_friday = [
            'thumbnail' => self::ASSET_URL . 'images/nx-icon.svg',
            'html' => $notice_text,
        ];

        $notices->add(
            'black_friday',
            $_black_friday,
            [
                'start'       => $notices->time(),
                'recurrence'  => false,
                'dismissible' => true,
                'expire'      => strtotime( 'Wed, 30 Nov 2022 23:59:59 GMT' ),
                'display_if'  => ! is_array( $notices->is_installed( 'notificationx-pro/notificationx-pro.php' ) )
            ]
        );
        $b_message            = '<p style="margin-top: 0; margin-bottom: 10px;">Black Friday Sale: Get up to 40% discounts & <strong>boost your sales</strong> with advanced marketing campaigns ⚡</p><a class="button button-primary" href="https://wpdeveloper.com/upgrade/notificationx-bfcm" target="_blank">Upgrade to pro</a> <button data-dismiss="true" class="dismiss-btn button button-link">I don’t want to save money</button>';
		$_black_friday_notice = [
			'thumbnail' => self::ASSET_URL . 'images/full-logo.svg',
			'html'      => $b_message,
		];

		$notices->add(
			'black_friday_notice',
			$_black_friday_notice,
			[
				'start'       => $notices->time(),
				'recurrence'  => false,
				'dismissible' => true,
				'refresh'     => NOTIFICATIONX_VERSION,
				"expire"      => strtotime( '11:59:59pm 2nd December, 2023' ),
				'display_if'  => ! is_plugin_active( 'notificationx-pro/notificationx-pro.php' )
			]
		);
        // $notices->init();
        self::$cache_bank->create_account( $notices );
		self::$cache_bank->calculate_deposits( $notices );
    }

    public function plugin_usage_insights(){
        $this->insights = PluginInsights::get_instance( NOTIFICATIONX_FILE, [
			'opt_in'       => true,
			'goodbye_form' => true,
			'item_id'      => '6ba8d30bc0beaddb2540'
		] );
		$this->insights->set_notice_options(array(
			'notice' => __( 'Want to help make <strong>NotificationX</strong> even more awesome? You can get a <strong>10% discount coupon</strong> for Premium extensions if you allow us to track the usage.', 'notificationx' ),
			'extra_notice' => __( 'We collect non-sensitive diagnostic data and plugin usage information.
			Your site URL, WordPress & PHP version, plugins & themes and email address to send you the
			discount coupon. This data lets us make sure this plugin always stays compatible with the most
			popular plugins and themes. No spam, I promise.', 'notificationx' ),
		));
		$this->insights->init();
    }
}
