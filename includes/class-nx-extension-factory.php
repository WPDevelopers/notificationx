<?php 
/**
 * This class responsivle for loading all extension at a time.
 */
class Extension_Factory {
    /**
     * An array of all extensions
     *
     * @var array
     */
    protected $extensions;
    protected $loaded_extensions;
    /**
     * This function is responsible for registering an extension.
     *
     * @param string $extension
     * @return void
     */
    public function register( $extension = '', $key = '' ){
        if ( empty( $extension ) || empty( $key ) ) {
			return;
        }
        $this->extensions = $this->add( $this->extensions, $extension, $key );
    }
    /**
     * This function is responsible for adding an extension to the extensions array!
     *
     * @param array $extensions
     * @param string $classname
     * @return void
     */
    protected function add( $extensions, $classname, $key ) {
		$extensions[ $key ] = $classname;
		return $extensions;
    }
    /**
     * This function is responsible for loading all extension 
     * and also firing the actions and filters method.
     *
     * @return void
     */
    public function load(){
        $extensions = $this->extensions;
        if( ! empty( $extensions ) ) {
            foreach( $extensions as $extension ) {
                $object = new $extension;
                $this->loaded_extensions[ $object->type ] = $extension;
                /**
                 * Hooked all actions to their responsible 
                 * methods if exists.
                 */
                if( method_exists( $object, 'admin_actions' ) ) {
                    add_action( 'nx_admin_action', array( $object, 'admin_actions' ) );
                }

                if( method_exists( $object, 'template_name' ) ) {
                    add_filter( 'nx_template_name', array( $object, 'template_name' ) );
                }

                if( method_exists( $object, 'fallback_data' ) ) {
                    add_filter( 'nx_fallback_data', array( $object, 'fallback_data' ), 11, 3 );
                }

                if( method_exists( $object, 'save_post' ) ) {
                    add_action( 'save_post_notificationx', array( $object, 'save_post' ), 11, 3 );
                }

                if( method_exists( $object, 'settings_by_theme' ) ) {
                    add_filter( 'nx_template_settings_by_theme', array( $object, 'settings_by_theme' ) );
                }
                if( method_exists( $object, 'template_string_by_theme' ) ) {
                    add_filter( 'nx_template_string_generate', array( $object, 'template_string_by_theme'), 10, 3 );
                }

                if( method_exists( $object, 'public_actions' ) ) {
                    add_action( 'nx_public_action', array( $object, 'public_actions' ) );
                }

                if( method_exists( $object, 'get_notification_ready' ) ) {
                    add_action( 'nx_get_conversions_ready', array( $object, 'get_notification_ready' ), 10, 2 );
                }

                /**
                 * Hooked all filters to their responsible 
                 * methods if exists.
                 */
                if( method_exists( $object, 'display_type' ) ) {
                    add_filter( 'nx_display_type', array( $object, 'display_type' ) );
                }

                if( method_exists( $object, 'settings_init_hook' ) ) {
                    add_action( 'nx_before_settings_load', array( $object, 'settings_init_hook' ) );
                }

                if( method_exists( $object, 'init_hooks' ) ) {
                    add_action( 'nx_before_metabox_load', array( $object, 'init_hooks' ) );
                }
                if( method_exists( $object, 'init_builder_hooks' ) ) {
                    add_action( 'nx_before_builder_load', array( $object, 'init_builder_hooks' ) );
                }
                /**
                 * All tab filters
                 */
                if( method_exists( $object, 'content_tab_section' ) ) {
                    add_filter( 'nx_content_tab_sections', array( $object, 'content_tab_section' ) );
                }
                if( method_exists( $object, 'display_tab_section' ) ) {
                    add_filter( 'nx_display_tab_sections', array( $object, 'display_tab_section' ) );
                }
                
            }
        }
    }
    /**
     * This function is responsible for getting the extension from loaded extension.
     *
     * @param string $key
     * @return bool|void
     */
    public function get_extension( $key ){
        if( empty( $key ) ) {
            return false;
        }
        return isset( $this->loaded_extensions[ $key ] ) ? $this->loaded_extensions[ $key ] : false;
    }
}
/**
 * Make The Extension Factory Global!
 */
$GLOBALS['nx_extension_factory'] = new Extension_Factory();
/**
 * This function is responsible for register an extension.
 *
 * @param string $extension
 * @return bool|void
 */
function nx_register_extension( $extension = '', $key = '' ){
    if( empty( $extension ) || empty( $key ) ) {
        return false;
    }
    global $nx_extension_factory;
    $nx_extension_factory->register( $extension, $key );
}