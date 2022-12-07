<?php
/**
 * Extension Factory
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Core;

use NotificationX\Admin\Settings;
use NotificationX\GetInstance;

/**
 * ExtensionFactory Class
 */
class Modules {
    /**
     * Instance of Modules
     *
     * @var Modules
     */
    use GetInstance;

    protected $modules = [];

	/**
	 * Initially Invoked when initialized.
	 */
	public function __construct(){
		// echo 'ExtensionFactory';
        add_filter('nx_settings_page_settings', [$this, 'modules_defaults']);
    }

    /**
     * Adds a Module
     *
     * @param array $module
     * @return array
     */
    public function add($module){
        $this->modules[$module['value']] = $module;
        return $module['value'];
    }

    /**
     * Checks whether a module is enabled.
     *
     * @param string $module name of module
     * @return boolean
     */
    public function is_enabled($module) {
        $enabled_types = (array) Settings::get_instance()->get('settings.modules');

        if( isset( $enabled_types[ $module ] ) && $enabled_types[$module] ) {
            return $enabled_types[$module];
        } elseif( ! isset( $enabled_types[ $module ] ) ) {
            return true;
        }

        return false;
    }

    /**
     * Returns all the registered modules.
     *
     * @return array
     */
    public function get_all(){
        return $this->modules;
    }

    public function modules_defaults($settings){
        $modules = $this->get_all();
        foreach ($modules as $key => $value) {
            if(!isset($settings['modules'][$key])){
                $settings['modules'][$key] = true;
            }
        }
        return $settings;
    }
}
