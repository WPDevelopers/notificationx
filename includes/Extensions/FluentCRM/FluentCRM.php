<?php
/**
 * Envato Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\FluentCRM;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;

/**
 * FluentCRM Extension
 * @method static FluentCRM get_instance($args = null)
 */
class FluentCRM extends Extension {
    /**
     * Instance of FluentCRM
     *
     * @var FluentCRM
     */
    use GetInstance;

    public $priority        = 30;
    public $id              = 'FluentCRM';
    // public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/fluentcrm.png';
    // public $doc_link        = 'https://notificationx.com/docs/FluentCRM-sales-notification';
    public $types           = 'email_subscription';
    public $module          = 'modules_fluentcrm';
    public $module_priority = 18;
    public $is_pro          = true;
    public $class           = 'WPCF7_ContactForm';
    public $version         = '2.7.1';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('FluentCRM', 'notificationx');
        $this->module_title = __('FluentCRM', 'notificationx');
        parent::__construct();
    }

    /**
     * If you want to add fields in edit notice page.
     */
    public function init_fields() {
        parent::init_fields();
        // add_filter('nx_content_fields', [$this, 'content_fields']);
        // add_filter('nx_display_fields', [$this, 'display_fields']);
        // add_filter('nx_customize_fields', [$this, 'customize_fields']);
    }

    /**
     * If you want to add fields in Settings page.
     */
    public function init_settings_fields() {
        parent::init_settings_fields();
        // add_filter('nx_settings_tab_general', [$this, 'nx_settings_tab_general']);
        // add_filter('nx_settings_tab_advanced', [$this, 'nx_settings_tab_advanced']);
        // add_filter('nx_settings_tab_email_analytics', [$this, 'nx_settings_tab_email_analytics']);
        // add_filter('nx_settings_tab_cache', [$this, 'nx_settings_tab_cache']);
        // add_filter('nx_settings_tab_miscellaneous', [$this, 'nx_settings_tab_miscellaneous']);
        // add_filter('nx_settings_tab_api_integration', [$this, 'api_integration_settings']);
    }

    /**
     * If you want to modify data before sending it to frontend for view.
     *
     * See includes/FrontEnd/FrontEnd.php for available filters.
     *
     * @return void
     */
    public function public_actions() {
        parent::public_actions();

        // add_filter('nx_frontend_get_entries', [$this, 'nx_frontend_get_entries'], 10, 2);
    }

    /**
     * This is where you hook into third party plugins to get data.
     *
     * @return void
     */
    public function admin_actions() {
        parent::admin_actions();

        // New contact created
        add_action('fluentcrm_contact_created', [$this, 'contact_created'], 10, 1);

    }

    /**
     * Undocumented function
     *
     * @param Model $contact
     * @return void
     */
    public function contact_created($contact){
        $key = rand(); // unique key
        $data = [
            'name'     => '',
            'first_name'     => '',
            'last_name'     => '',
            'last_name'     => '',
            'title'     => '',
            'link'      => '',
            'timestamp' => time(),
            //.....
            // or specify your custom key which you can use in Advanced Template.
        ]; // convert $contact into data.
        $this->save([
            'source'    => $this->id,
            'entry_key' => $key,
            'data'      => $data,
        ]);
        // $this->update_notifications($entries);
    }

    public function doc(){
        return '';
    }
}
