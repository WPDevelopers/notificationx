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
 * Modules Class
 * @method static Modules get_instance($args = null)
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
     * Adds a Module
     *
     * @param array $module
     * @return array
     */
    public function update($module, $key, $value){
        if( !empty( $this->modules[$module] ) ) {
            $this->modules[$module][$key] = $value;
            return true;
        }
        return false;
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

    public function modules_defaults($settings) {
        if (!isset($settings) || !is_array($settings)) {
            $settings = [];
        }
        if( !isset( $settings['modules'] ) || !is_array( $settings['modules'] ) ) {
            $settings['modules'] = [];
        }
        $modules = $this->get_all();
        foreach ($modules as $key => $value) {
            if (!isset($settings['modules'][$key])) {
                $settings['modules'][$key] = true;
            }
        }
        return $settings;
    }
}
