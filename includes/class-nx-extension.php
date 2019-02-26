<?php
/**
 * Register all extensions for the plugin.
 * 
 * @link       https://wpdeveloper.net
 * @since      1.0.0
 * 
 * @package    NotificationX
 * @subpackage    NotificationX/extensions
 * @author     WPDeveloper <support@wpdeveloper.net>
 */
class NotificationX_Extension {
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
    protected $prefix = 'nx_';
    /**
     * All Active Notification Items
     *
     * @var array
     */
    public static $active_items = [];

    public static $powered_by = null;
    /**
     * Constructor of extension for ready the settings and cache limit.
     */
    public function __construct( ){
        self::$settings      = NotificationX_DB::get_settings();

        if( ! empty( self::$settings ) && isset( self::$settings['cache_limit'] ) ) {
            $this->cache_limit = intval( self::$settings['cache_limit'] );
        }

        if( ! empty( self::$settings ) && isset( self::$settings['disable_powered_by'] ) ) {
            self::$powered_by = intval( self::$settings['disable_powered_by'] );
        }
        /**
         * Get all Active Notification Items
         */
        self::$active_items = NotificationX_Admin::get_active_items();
    }
    /**
     * This function is responsible for making hide option.
     *
     * @return void
     */
    // public function hide_field(){
        // add_filter( 'nx_display_types_hide_data', array( $this, 'hide_fields' ) );
    // }
    /**
     * this function is responsible for check a type of notification is created or not
     *
     * @param string $type
     * @return boolean
     */
    public static function is_created( $type = '' ){
        if( empty( $type ) ) {
            return false;
        }

        if( ! empty( self::$active_items ) ) {
            return in_array( $type, array_keys( self::$active_items ) );
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
        $notifications = NotificationX_DB::get_notifications();
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
    protected function save( $type = '', array $data = [] ){
        if( empty( $type ) ) {
            return;
        }
        $notifications = NotificationX_DB::get_notifications();
        $notifications[ $type ] = $data;
        return NotificationX_DB::update_notifications( $notifications );
    }
    /**
     * This function will convert all the data key into double curly braces format
     * {{key}} = $value
     *
     * @param boolean $data
     * @return void
     */
    protected static function newData( $data = array() ) {
        if( empty( $data ) ) return;
        $new_data = array();
        foreach( $data as $key => $single_data ) {
            if( $key == 'link' || $key == 'post_link' ) continue;
            if( $key == 'timestamp' ) {
                $new_data[ '{{time}}' ] = NotificationX_Helper::get_timeago_html( $single_data );
                continue;
            }
            $new_data[ '{{'. $key .'}}' ] = $single_data;
        }
        return $new_data;
    }
    /**
     * This function responsible for all
     *
     * @param array $data
     * @param boolean $settings
     * @return void
     */
    public function frontend_html( $data = [], $settings = false, $args = [] ){
        if( ! is_object( $settings ) || empty( $data ) ) {
            return;
        }
        extract( $args );
        $settings->themeName = $settings->{ $themeName };

        $output = '';
        $unique_id = uniqid( 'notificationx-' ); 
        $image_data = self::get_image_url( $data, $settings );
        $output .= '<div id="'. esc_attr( $unique_id ) .'" class="nx-notification '. self::get_classes( $settings ) .'">';
            $output .= '<div class="notificationx-inner '. self::get_classes( $settings, 'inner' ) .'">';
                if( $image_data ) :
                    $output .= '<div class="notificationx-image">';
                        $output .= '<img class="'. self::get_classes( $settings, 'img' ) .'" src="'. $image_data['url'] .'" alt="'. esc_attr( $image_data['alt'] ) .'">';
                    $output .= '</div>';
                endif;
                $output .= '<div class="notificationx-content">';
                    $output .= NotificationX_Template::get_template_ready( $settings->{ $template }, self::newData( $data ) );
                    if( $settings->close_button ) :
                        $output .= '<span class="notificationx-close"><svg width="8px" height="8px" viewBox="0 0 48 48" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="Page-1" stroke="none" stroke-width="1" fill-rule="evenodd"><g id="close" fill-rule="nonzero"><path d="M28.228,23.986 L47.092,5.122 C48.264,3.951 48.264,2.051 47.092,0.88 C45.92,-0.292 44.022,-0.292 42.85,0.88 L23.986,19.744 L5.121,0.88 C3.949,-0.292 2.051,-0.292 0.879,0.88 C-0.293,2.051 -0.293,3.951 0.879,5.122 L19.744,23.986 L0.879,42.85 C-0.293,44.021 -0.293,45.921 0.879,47.092 C1.465,47.677 2.233,47.97 3,47.97 C3.767,47.97 4.535,47.677 5.121,47.091 L23.986,28.227 L42.85,47.091 C43.436,47.677 44.204,47.97 44.971,47.97 C45.738,47.97 46.506,47.677 47.092,47.091 C48.264,45.92 48.264,44.02 47.092,42.849 L28.228,23.986 Z" id="Shape"></path></g></g></svg></span>';
                    endif;
                    if( is_null( self::$powered_by ) ) :
                        $output .= '<small class="nx-branding">';
                            $output .= '<svg width="7" height="13" viewBox="0 0 7 13" xmlns="http://www.w3.org/2000/svg" title="Powered by NotificationX"><g fill-rule="evenodd" fill="none"><path fill="#F6A623" d="M4.127.496C4.51-.12 5.37.356 5.16 1.07L3.89 5.14H6.22c.483 0 .757.616.464 1.044l-4.338 6.34c-.407.595-1.244.082-1.01-.618L2.72 7.656H.778c-.47 0-.748-.59-.48-1.02L4.13.495z"></path><path fill="#FEF79E" d="M4.606.867L.778 7.007h2.807l-1.7 5.126 4.337-6.34H3.16"></path></g></svg>';
                            $output .= ' by <a href="'. NOTIFICATIONX_PLUGIN_URL .'?utm_source='. urlencode( home_url() ) .'&utm_medium=notificationx_referrer" target="_blank" class="nx-powered-by">NotificationX</a>';
                        $output .= '</small>';
                    endif;
                $output .= '</div>';
            $output .= '</div>';
            if( self::is_link_visible( $settings ) ) :
                $output .= '<a class="notificationx-link" href="'. self::get_link( $data ) .'"></a>';
            endif;
        $output .= '</div>';
        return $output;
    }
    /**
     * This function is responsible for generate classes for wrapper, inner
     *
     * @param stdClass $settings
     * @param string $type
     * @return string
     */
	public static function get_classes( $settings, $type = 'wrapper' ){
		if( empty( $settings ) ) return;
		$classes = [];
        
        switch( $settings->display_type ) {
            case 'comments' : 
                if( $settings->comment_advance_edit ) {
                    $classes[ 'inner' ][] = 'nx-customize-style-' . $settings->id;
                    $classes[ 'inner' ][] =  'nx-img-' . $settings->comment_image_position;
                    $classes[ 'img' ][] = 'nx-img-' . $settings->comment_image_shape;
                    if( $settings->comment_image_position == 'right' ) {
                        $classes[ 'inner' ][] =  'nx-flex-reverse';
                    }
                }
                break;
            case 'conversions' : 
                if( $settings->advance_edit ) {
                    $classes[ 'inner' ][] = 'nx-customize-style-' . $settings->id;
                    $classes[ 'inner' ][] =  'nx-img-' . $settings->image_position;
                    $classes[ 'img' ][] = 'nx-img-' . $settings->image_shape;
                    if( $settings->image_position == 'right' ) {
                        $classes[ 'inner' ][] =  'nx-flex-reverse';
                    }
                }
                break;
            case 'press_bar' : 
                if( $settings->bar_advance_edit ) {
                    $classes[ 'inner' ][] = 'nx-customize-style-' . $settings->id;
                }
                break;
        }

		if( $settings->close_button ) {
			$classes[ 'inner' ][] = 'nx-has-close-btn';
        }
        if( $settings->display_type !== 'comments' ) {
            $classes[ 'wrapper' ][] = 'nx-' . esc_attr( $settings->conversion_from );
        }
		$classes[ 'wrapper' ][] = 'nx-' . esc_attr( $settings->conversion_position );
        $classes[ 'wrapper' ][] = 'notificationx-' . $settings->id;
        
		$classes[ 'wrapper' ][] = 'nx-' . $settings->display_type;

		$classes[ 'inner' ][] = 'nx-notification-' . esc_attr( self::get_theme( $settings ) );

		return implode( ' ', $classes[ $type ] );
    }

    private static function get_theme( $settings ){
        switch( $settings->display_type ) {
            case 'comments' : 
                $theme_name = $settings->comment_theme;
                break;
            case 'conversions' : 
                $theme_name = $settings->theme;
                break;
            default: 
                    if( ! empty( $settings->themeName ) ) :
                        $theme_name = $settings->themeName;
                    endif;
                break;
        }

        return $theme_name;
    }
    /**
     * This function is responsible for checking, is the notification is visible or not.
     *
     * @return void
     */
    public static function is_link_visible( $settings = [] ){
        if( empty( $settings ) ) return false;
        $link = true;
        $link = apply_filters('nx_notification_link_visible', $link );
        return $link;
    }
    /**
     * This function is responsible for make ready the link for notifications.
     *
     * @param array $data
     * @return void
     */
    public static function get_link( $data = [], $settings = [] ){
        if( empty( $data ) ) {
            return false;
        }

        $link = apply_filters('nx_notification_link', $data['link'], $settings );

        return $link;
    }
    /**
     * This function is responsible for getting the image url 
     * using Product ID or from default image settings.
     *
     * @param array $data
     * @param stdClass $settings
     * @return array of image data, contains url and title as alt text
     */
    protected static function get_image_url( $data = [], $settings ) {
        $image_url = $alt_title = '';
        $alt_title = isset( $data['name'] ) ? $data['name'] : $data['title'];
        switch( $settings->display_type ) {
            case 'comments' :
                if( $settings->show_avatar ) {
                    $avatar = '';
                    if( isset( $data['user_id'] ) ) {
                        $avatar = get_avatar_url( $data['user_id'], array(
                            'size' => '60'    
                        ));
                    }
                    $image_url = $avatar;
                }
                break;
            case 'conversions' :
                if( $settings->conversion_from != 'custom' ) {
                    if( $settings->show_product_image && has_post_thumbnail( $data['product_id'] ) ) {
                        $product_image = wp_get_attachment_image_src( get_post_thumbnail_id( $data['product_id'] ), '_nx_notification_thumb', false );
                        $image_url = is_array( $product_image ) ? $product_image[0] : '';
                    }
                }
                if( $settings->conversion_from == 'custom' ) {
                    if( ! empty( $data ) ) {
                        $image_url = $alt_title = '';
                        if( isset( $data['image'] ) && ! empty( $data['image'] ) ) {
                            $product_image = wp_get_attachment_image_src( $data['image']['id'], '_nx_notification_thumb', false );
                            $image_url = is_array( $product_image ) ? $product_image[0] : '';
                        }
                        if( isset( $data['title'] ) && ! empty( $data['title'] ) ) {
                            $alt_title = $data['title'];
                        }
                    }
                }
                break;
        }

        if( isset( $settings->show_default_image ) && $settings->show_default_image && $image_url == '' ) {
            $product_image = wp_get_attachment_image_src( $settings->image_url['id'], '_nx_notification_thumb', false );
            $image_url = is_array( $product_image ) ? $product_image[0] : '';
        }       

        if( $image_url ) {
            return [ 'url' => $image_url, 'alt' => $alt_title ];
        }

        return false;
    }
}

/**
 * This function is responsible for getting frontend
 * html to generate the output.
 * 
 * @param string $key
 * @param array $data
 * @param stdObject $settings
 */
function get_extension_frontend( $key, $data, $settings = false ){
    if( empty( $key ) ) return;
    global $nx_extension_factory;
    $extension_name = $nx_extension_factory->get_extension( $key );
    if( class_exists( $extension_name ) ) {
        $extension = new $extension_name;
        $args = [
            'template' => $extension->template,
            'themeName' => $extension->themeName,
        ];
        return $extension->frontend_html( $data, $settings, $args );
    }
}