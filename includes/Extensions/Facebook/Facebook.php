<?php
/**
 * Facebook Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Facebook;

use NotificationX\Admin\Settings;
use NotificationX\Core\Rules;
use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * Facebook Extension
 * @method static Facebook get_instance($args = null)
 */
class Facebook extends Extension {
    /**
     * Instance of Facebook
     *
     * @var Facebook
     */
    use GetInstance;

    public $priority        = 25;
    public $id              = 'facebook';
    public $types           = 'reviews';
    public $module          = 'modules_facebook';
    public $module_priority = 33;
    public $is_pro          = true;
    public $version         = '1.2.0';
    public $client          = null;
    public $nx_client       = null;
    public $redirect_url       = null;
    // public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/facebook.png';
    // public $doc_link        = 'https://notificationx.com/docs/facebook-sales-notification';
    /**
     * token information of google client, includes refresh token and timestamp
     * @var array
     */
    public $token_info;
    /**
     * page analytics options
     * @var array
     */
    public $fb_options;
    /**
     * option key for saving google analytics data as option
     * @var string
     */
    public $option_key = 'fb_settings';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('Facebook', 'notificationx');
        $this->module_title = __('Facebook', 'notificationx');
        $this->popup = [
            "denyButtonText" => __("<a href='https://notificationx.com/docs/facebook-sales-notification/' target='_blank'>More Info</a>", "notificationx"),
            "confirmButtonText" => __("<a href='https://notificationx.com/#pricing' target='_blank'>Upgrade to PRO</a>", "notificationx"),
            "html"=> __('
                <span>A resourceful online marketplace for digital assets and services.</span>
                <iframe id="email_subscription_video" type="text/html" allowfullscreen width="450" height="235"
                src="https://www.youtube.com/embed/-df_6KHgr7I">
                </iframe>
            ', 'notificationx')
        ];
        parent::__construct();
        add_action('admin_init', array($this, 'init_google_client'));
        $this->client = new Client();
        $this->redirect_url = admin_url('admin.php?page=nx-settings&tab=tab-api-integrations#facebook_settings_section');
    }

    public function init_settings_fields() {
        parent::init_settings_fields();
        // settings page
        add_filter('nx_settings_tab_api_integration', [$this, 'api_integration_settings']);
    }

    /**
     * This method adds google analytics settings section in admin settings
     * @param array $sections
     * @return array
     */
    public function api_integration_settings($sections) {
        // $shop_page_url = get_permalink( wc_get_page_id( 'shop' ) );
        $state = [
            'redirect_url' => $this->redirect_url,
        ];

        $sections['facebook_settings_section'] = array(
            'name'     => 'facebook_settings_section',
            'type'     => 'section',
            'label'    => __('Facebook Settings', 'notificationx-pro'),
            'modules'  => 'modules_facebook',
            'priority' => 0,
            'rules'    => Rules::is('modules.modules_facebook', true),
            'fields'   => [
                'ga_connect' => array(
                    'name'      => 'ga_connect',
                    'type'      => 'button',
                    'text'      => __('Connect your account', 'notificationx-pro'),
                    'label'     => __('Connect with Google Analytics', 'notificationx-pro'),
                    'className' => 'ga-btn connect-analytics',
                    'href'      => $this->client->get_oauth_url($state),
                ),


                // 'is_ga_connected' => array(
                //     'name'      => 'is_ga_connected',
                //     'type'      => 'hidden',
                //     // 'default'   => $this->is_ga_connected(),
                // ),

            ],
        );
        return $sections;
    }

    /**
     * Init Google client with auth code
     * @return void
     */
    public function init_google_client() {
        if (isset($_GET['access_token']) && 'nx-settings' == $_GET['page']) {
            if (!empty($this->fb_options['auth_code'])) {
                if ($this->fb_options['auth_code'] === $_GET['code']) {
                    return;
                }
            }
            try {
                $this->authenticate();
            } catch (\Exception $e) {
                // self::$error_message = $e->getMessage();
                // Helper::write_log(['error' => $e->getMessage()]);
            }
        }

    }

    /**
     * Authenticate user with auth code
     * Set access token for get data
     * Save token information in database
     * @throws \Exception
     */
    private function authenticate() {
        $token_info = $_GET['access_token'];
        $code       = $_GET['code'];


        if (!array_key_exists('error', $token_info)) {
            $fb_options               = [];
            $fb_options['token_info'] = $token_info;
            $fb_options['auth_code']  = $code;
            if ($this->client->getRedirectUri() === $this->redirect_url) {
                $fb_options['ga_app_type'] = 'user_app';
            } else {
                $fb_options['ga_app_type'] = 'nx_app';
            }

            $this->set_option($fb_options);

            Settings::get_instance()->set("settings.is_fb_connected", true);

            wp_redirect(admin_url('admin.php?page=nx-settings&tab=tab-api-integrations#facebook_settings_section'));
        } else {
            throw new \Exception('Get token with auth code failed.' . $token_info['error']);
        }
    }

    /**
     * Set page analytics options
     * @return Google_Client
     */
    public function get_client() {
        if(empty($this->nx_client)){
            $this->nx_client = new Client();
        }
        return $this->nx_client;
    }

    /**
     * Set page analytics options
     * @return void
     */
    public function get_options() {
        if(empty($this->fb_options)){
            $options          = Settings::get_instance()->get("settings.{$this->option_key}");
            $this->fb_options = !empty($options) ? $options : [];
        }
        return $this->fb_options;
    }

    public function set_option($options){
        $this->fb_options = $options;
        Settings::get_instance()->set("settings.{$this->option_key}", $options);
    }

    /**
     * Set page analytics options
     * @return void
     */
    public function get_token_info() {
        if(empty($this->token_info)){
            $fb_options = $this->get_options();
            $this->token_info = !empty($fb_options['token_info']) ? $fb_options['token_info'] : [];
        }
        return $this->token_info;
    }

    public function doc(){
        return sprintf(__('<p>Make sure that you have <a target="_blank" href="%1$s">created & signed in to Facebook account</a> to use its campaign & product sales data.  For further assistance, check out our step by step <a target="_blank" href="%2$s">documentation</a>.</p>
		<p>🎦 <a target="_blank" href="%3$s">Watch video tutorial</a> to learn quickly</p>
		<p>👉 NotificationX <a target="_blank" href="%4$s">Integration with Facebook</a></p>', 'notificationx'),
        'https://account.facebook.com/sign_in?to=facebook-api',
        'https://notificationx.com/docs/facebook-sales-notification/',
        'https://youtu.be/-df_6KHgr7I',
        'https://notificationx.com/integrations/facebook/'
        );
    }
}
