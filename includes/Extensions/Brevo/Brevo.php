<?php
/**
 * Brevo Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Brevo;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * Brevo Extension
 * @method static Brevo get_instance($args = null)
 */
class Brevo extends Extension {
    /**
     * Instance of Brevo
     *
     * @var Brevo
     */
    use GetInstance;

    public $priority        = 16;
    public $id              = 'brevo';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/brevo.png';
    public $doc_link        = 'https://notificationx.com/docs/brevo-email-subscription-alert/';
    public $types           = 'email_subscription';
    public $module          = 'modules_brevo';
    public $module_priority = 21;
    public $is_pro          = true;

    /**
     * Email Subscription themes Brevo cannot fill.
     *
     * A Brevo contact carries no geo data -- there is no `location` object the
     * way MailChimp returns one, and no lat/lon contact attribute -- so the
     * Maps theme (and its responsive counterpart) would render an empty map for
     * every entry. Excluding them in get_themes()/get_res_themes() below keeps
     * them out of the theme picker for this source only; every other Email
     * Subscription source keeps them.
     *
     * @var string[]
     */
    const UNSUPPORTED_THEMES = [ 'maps_theme' ];

    /**
     * @var string[]
     */
    const UNSUPPORTED_RES_THEMES = [ 'subscriptions-res-theme-four' ];

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
    }

    public function init_extension()
    {
        $this->title        = __('Brevo', 'notificationx');
        $this->module_title = __('Brevo', 'notificationx');
    }

    /**
     * Themes inherited from the Email Subscription type, minus the ones Brevo
     * has no data for.
     *
     * @return array
     */
    public function get_themes() {
        return $this->exclude_themes( parent::get_themes(), self::UNSUPPORTED_THEMES );
    }

    /**
     * Responsive themes inherited from the Email Subscription type, minus the
     * ones Brevo has no data for.
     *
     * @return array
     */
    public function get_res_themes() {
        return $this->exclude_themes( parent::get_res_themes(), self::UNSUPPORTED_RES_THEMES );
    }

    /**
     * Drops type-level themes this source cannot render.
     *
     * Extension::get_themes() prefixes the type's theme names with
     * "{$this->types}_" whenever the extension declares no themes of its own,
     * which is the case here, so the keys to unset carry that prefix.
     *
     * Dropping a theme here is what hides it: Extension::__nx_themes() appends
     * this source to each returned theme's `includes source` rule, so a theme
     * that never comes back from this method never lists `brevo` as a valid
     * source and stays hidden in the picker.
     *
     * @param array    $themes
     * @param string[] $unsupported
     * @return array
     */
    protected function exclude_themes( $themes, $unsupported ) {
        if ( ! is_array( $themes ) ) {
            return $themes;
        }
        foreach ( $unsupported as $name ) {
            unset( $themes[ $this->types . '_' . $name ] );
        }
        return $themes;
    }

    /**
     * Get data for Brevo Extension.
     *
     * @param array $args Settings arguments.
     * @return array
     */
    public function get_data( $args = array() ){
        return 'Hello From Brevo';
    }

    public function doc(){
        /* translators: %1$s: signed in & retrieved API key from Brevo account link URL, %2$s: documentation link URL, %3$s: Integration with Brevo link URL, %4$s: Email Marketing Strategy link URL, %5$s: Email Subscription List link URL */
        return sprintf(__('<p>Make sure that you have <a target="_blank" href="%1$s">signed in & retrieved an API key from your Brevo account</a> to use its contact list & email subscriptions data. For further assistance, check out our step by step <a target="_blank" href="%2$s">documentation</a>.</p>
		<p>👉 NotificationX <a target="_blank" href="%3$s">Integration with Brevo</a></p>
		<p><strong>Recommended Blogs:</strong></p>
		<p>🔥 How To Improve Your <a target="_blank" href="%4$s">Email Marketing Strategy</a> With Social Proof</p>
		<p>🚀 Hacks To Grow Your <a target="_blank" href="%5$s">Email Subscription List</a> On WordPress Website</p>', 'notificationx'),
        'https://help.brevo.com/hc/en-us/articles/209467485-Create-and-manage-your-API-keys',
        'https://notificationx.com/docs/brevo-email-subscription-alert/',
        'https://notificationx.com/integrations/brevo/',
        'https://wpdeveloper.com/email-marketing-social-proof/',
        'https://wpdeveloper.com/email-subscription-list-wordpress/'
        );
    }
}
