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
    protected $template;
    /**
     * This function is responsible for registering an extension.
     *
     * @param string $extension
     * @return void
     */
    public function register( string $extension ){
        if ( empty( $extension ) ) {
			return;
        }
        $this->extensions = $this->add( $this->extensions, $extension );
    }
    /**
     * This function is responsible for adding an extension to the extensions array!
     *
     * @param array $extensions
     * @param string $classname
     * @return void
     */
    protected function add( $extensions, $classname ) {
		$extensions[] = $classname;
		return $extensions;
    }
    /**
     * This function is responsible for loading all extension 
     * and also firing the actions and filters method.
     *
     * @return void
     */
    public function load(){
        if( ! empty( $this->extensions ) ) {
            foreach( $this->extensions as $extension ) {
                $object = new $extension;
                $this->loaded_extensions[ $object->type ] = $extension;

                /**
                 * Hooked all actions to their responsible 
                 * methods if exists.
                 */
                if( method_exists( $object, 'admin_actions' ) ) {
                    add_action( 'fomopress_admin_action', array( $object, 'admin_actions' ) );
                }

                if( method_exists( $object, 'public_actions' ) ) {
                    add_action( 'fomopress_public_action', array( $object, 'public_actions' ) );
                }

                if( method_exists( $object, 'get_notification_ready' ) ) {
                    add_action( 'fomopress_get_conversions_ready', array( $object, 'get_notification_ready' ), 10, 3 );
                }

                /**
                 * Hooked all filters to their responsible 
                 * methods if exists.
                 */
                if( method_exists( $object, 'display_type' ) ) {
                    add_filter( 'fomopress_display_type', array( $object, 'display_type' ) );
                }

                if( method_exists( $object, 'conversion_from' ) ) {
                    add_filter( 'fomopress_conversion_from', array( $object, 'conversion_from' ) );
                }

                /**
                 * All tab filters
                 */
                if( method_exists( $object, 'source_tab_section' ) ) {
                    add_filter( 'fomopress_source_tab_sections', array( $object, 'source_tab_section' ) );
                }
                if( method_exists( $object, 'content_tab_section' ) ) {
                    add_filter( 'fomopress_content_tab_sections', array( $object, 'content_tab_section' ) );
                }
                if( method_exists( $object, 'display_tab_section' ) ) {
                    add_filter( 'fomopress_display_tab_sections', array( $object, 'display_tab_section' ) );
                }
                if( method_exists( $object, 'customize_tab_section' ) ) {
                    add_filter( 'fomopress_customize_tab_sections', array( $object, 'customize_tab_section' ) );
                }
                
            }
        }
    }

    public function get_extension( string $key ){
        return $this->loaded_extensions[ $key ];
    }

}

$GLOBALS['fomopress_extension_factory'] = new Extension_Factory();
/**
 * This function is responsible for register an extension.
 *
 * @param string $extension
 * @return void
 */
function fomopress_register_extension( string $extension ){
    global $fomopress_extension_factory;
    $fomopress_extension_factory->register( $extension );
}

function get_extention_frontend( $key, $data, $settings = false ){
    global $fomopress_extension_factory;
    $class_name = $fomopress_extension_factory->get_extension( $key );
    if( class_exists( $class_name ) ) {
        $object = new $class_name;
        return $object->frontend_html( $data, $settings );
    }
}

/**
 * Register all extensions for the plugin.
 * 
 * @link       https://wpdeveloper.net
 * @since      1.0.0
 * 
 * @package    FomoPress
 * @subpackage FomoPress/extensions
 * @author     WPDeveloper <support@wpdeveloper.net>
 */
class FomoPress_Extension {
    /**
     * Settings options for all notifications we saw
     * @var array
     */
    protected static $settings;
    /**
     * Limit of the store
     * for storing notification in options table.
     * @var array ( multi dimensional, has key for every types of notification );
     */
    protected $cache_limit;
    /**
     * Prefix
     *
     * @var string
     */
    protected $prefix = 'fomopress_';


    public $defaults_settings;

    /**
     * Constructor of extension for ready the settings and cache limit.
     */
    public function __construct( ){
        self::$settings      = FomoPress_DB::get_settings();

        if( ! empty( self::$settings ) && isset( self::$settings['cache_limit'] ) ) {
            $this->cache_limit = intval( self::$settings['cache_limit'] );
        }

        $this->defaults_settings = [
            'show_on',
            'show_on_display',
            'close_button',
            'hide_on_mobile',
        ];
    }

    public static function is_created( $type = '' ){
        if( empty( $type ) ) {
            return false;
        }
        $active_items = FomoPress_Admin::get_active_items();

        if( ! empty( $active_items ) ) {
            return in_array( $type, $active_items );
        } else {
            return false;
        }
    }
    /**
     * This method is responsible for get all 
     * the notifications we have stored
     *
     * @param string $type
     * @return array - Multidimensional, has a key for every type of notification with all data stored.
     */
    public function get_notifications( $type = '' ){
        $notifications = FomoPress_DB::get_notifications();
        if( empty( $type ) || empty( $notifications ) || ! isset( $notifications[ $type ] ) ) {
            return [];
        }
        return $notifications[ $type ];
    }
    /**
     * This method is responsible for save the data
     *
     * @param string $type - notification type
     * @param array $data - notification data to save.
     * @return boolean
     */
    protected function save( string $type = '', array $data = [] ){
        if( empty( $type ) ) {
            return;
        }
        $notifications = FomoPress_DB::get_notifications();
        $notifications[ $type ] = $data;
        return FomoPress_DB::update_notifications( $notifications );
    }

    public function merge( $old, $new ) {
        return array_merge( $old, $new );
    }

    public function toggleData( $options, $type, $name ){
        if( ! isset( $options[ 'toggle' ][ $type ][ $name ] ) ) {
            return array();
        }
        return $options[ 'toggle' ][ $type ][ $name ];
    }
    public function hideData( $options, $type, $name ){
        if( ! isset( $options[ 'hide' ][ $type ][ $name ] ) ) {
            return array();
        }
        return $options[ 'hide' ][ $type ][ $name ];
    }

    protected function newData( $data = false ) {
        if( ! $data ) {
            return;
        }
        $new_data = array();
        foreach( $data as $key => $single_data ) {
            if( $key == 'timestamp' ) {
                $new_data[ '{{time}}' ] = FomoPress_Helper::get_timeago_html( $single_data );
                continue;
            }
            $new_data[ '{{'. $key .'}}' ] = $single_data;
        }

        return $new_data;
    }

    public function frontend_html( $data, $settings = false ){
        ob_start();
        if( $data['user_id'] ) {
            $avatar = get_avatar_url( $data['user_id'], array(
                'size' => '60'    
            ));
        }
        ?>
            <div class="fomopress-notification-image">
                <img src="<?php echo $avatar; ?>" alt="<?php echo $data['name']; ?>">
            </div>
            <div class="fomopress-notification-content">
                <?php 
                    echo FomoPress_Helper::get_template_ready( $settings->{ $this->template }, $this->newData( $data ) );
                ?>
            </div>
        <?php
        return ob_get_clean();
    }

}