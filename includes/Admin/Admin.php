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

/**
 * Admin Class, this class is responsible for all Admin Actions
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
        if (!empty($extension) && method_exists($extension, 'get_notification_ready') && $extension->is_active(false)) {
            Entries::get_instance()->delete_entries($post_id);
            $result = $extension->get_notification_ready($post, $post_id);
            return true;
        }
        return false;
    }

    public function admin_notices(){
        $notices = new Notices([
            'id'          => 'notificationx',
            'store'       => 'options',
            'storage_key' => 'notices',
            'version'     => '1.0.0',
            'lifetime'    => 3,
            'styles'      => self::ASSET_URL . 'css/wpdeveloper-review-notice.css',
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
            ]
        );

        $notices->add(
            'opt_in',
            [ $this->insights, 'notice' ],
            [
                'classes'     => 'updated put-dismiss-notice',
                'start'       => $notices->time(),
                'dismissible' => true,
                'do_action'   => 'wpdeveloper_notice_clicked_for_notificationx',
                'display_if'  => ! is_array( $notices->is_installed( 'notificationx-pro/notificationx-pro.php' ) )
            ]
        );

        $notices->init();
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
