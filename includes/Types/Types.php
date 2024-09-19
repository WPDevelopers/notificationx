<?php

/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Types;

use NotificationX\Modules;
use NotificationX\Core\Themes;
use NotificationX\Extensions\GlobalFields;

/**
 * Extension Abstract for all Types.
 */
abstract class Types {

    public $id;
    public $title;
    public $dashboard_title;
    public $is_pro            = false;
    public $themes            = [];
    public $res_themes        = [];
    public $module            = [];
    public $templates         = [];
    public $mobile_templates  = [];
    public $default_source    = '';
    public $default_theme     = '';
    public $default_res_theme = '';
    public $popup             = null;
    public $link_type         = 'none';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('nx_before_metabox_load', [$this, 'init_fields']);
        add_filter('nx_type_trigger', [$this, 'type_trigger']);
        if(method_exists($this, 'preview_entry')){
            add_filter("nx_preview_entry_{$this->id}", array($this, 'preview_entry'), 10, 2);
        }
    }

    /**
     * admin int hook.
     *
     *
     * @return void
     */
    public function init(){

    }

    /**
     * admin int hook.
     *
     *
     * @return void
     */
    public function init_fields(){

    }

    /**
     * Get themes for the extension.
     *
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function type_trigger($triggers){
        $triggers[$this->id] = "@source:{$this->default_source}";
        return $triggers;
    }

    /**
     * Get themes for the extension.
     *
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function get_themes(){
        return $this->themes;
    }

    /**
     * Get responsive themes for the extension.
     *
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function get_res_themes(){
        return $this->res_themes;
    }

    /**
     * Get templates for the extension.
     *
     *
     * @return array
     */
    public function get_templates(){
        return $this->templates;
    }

    /**
     * Get mobile templates for the extension.
     *
     *
     * @return array
     */
    public function get_mobile_templates(){
        return $this->mobile_templates;
    }
}
