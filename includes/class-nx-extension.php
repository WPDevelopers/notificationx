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

    protected $limiter;
    
    /**
     * Default data
     * @var array
     */
    public $defaults = array();
    /**
     * Constructor of extension for ready the settings and cache limit.
     */
    public function __construct( ){
        $this->defaults = apply_filters('nx_fallback_data', array(
            'name' => __('Someone', 'notificationx')
        ));
        $this->limiter = new NotificationX_Array();
        $limit = intval( NotificationX_DB::get_settings( 'cache_limit' ) );
        if( $limit <= 0 ) {
            $limit = 100;
        }
        $this->limiter->setLimit( $limit );
        $this->limiter->sortBy = 'timestamp';

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
    protected function save( $type = '', $data = [], $key = '' ){
        if( empty( $type ) ) {
            return;
        }
        $notifications = NotificationX_DB::get_notifications();

        if( ! empty( $notifications[ $type ] ) ) {
            $input = $notifications[ $type ];
        } else {
            $input = array();
        }

        $this->limiter->setValues( $input );
        $this->limiter->append( $data, $key );
        $notifications[ $type ] = $this->limiter->values();
        // hook anythings on save
        do_action( 'nx_before_data_save', $type, $data, $key );
        return NotificationX_DB::update_notifications( $notifications );
    }

    protected function update_notifications( $type = '', $values = array() ){
        $notifications = NotificationX_DB::get_notifications();
        $this->limiter->setValues( $values );

        $notifications[ $type ] = $this->limiter->values();
        return NotificationX_DB::update_notifications( $notifications );
    }

    protected function remote_get( $url, $args = array() ){
        $defaults = array(
            'timeout'     => 20,
            'redirection' => 5,
            'httpversion' => '1.1',
            'user-agent'  => 'NotificationX/'. NOTIFICATIONX_VERSION .'; ' . home_url(),
            'body'        => null,
            'sslverify'   => false,
            'stream'      => false,
            'filename'    => null
        );
        $args = wp_parse_args( $args, $defaults );
        $request = wp_remote_get( $url, $args );

        if( is_wp_error( $request ) ) {
            return false;
        }
        $response = json_decode( $request['body'] );
        if( isset( $response->status ) && $response->status == 'fail' ) {
            return false;
        }
        return $response;
    }

    /**
     * This function will convert all the data key into double curly braces format
     * {{key}} = $value
     *
     * @param boolean $data
     * @return void
     */
    public static function newData( $data = array() ) {
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
        $data = wp_parse_args( $data, $this->defaults );

        extract( $args );
        $settings->themeName = $settings->{ $themeName };
        $template = apply_filters( 'nx_template_id' , $template, $settings);

        $wrapper_class = apply_filters( 'nx_frontend_wrapper_classes', array_merge( 
            ['nx-notification'], self::get_classes( $settings ) 
        ), $settings );
        
        $inner_class = apply_filters( 'nx_frontend_inner_classes', array_merge(
            ['notificationx-inner'], self::get_classes( $settings, 'inner' )
        ), $settings );

        $content_class = apply_filters( 'nx_frontend_content_classes', array(
            'notificationx-content'
        ), $settings );
        
        $image_class = apply_filters( 'nx_frontend_image_classes', self::get_classes( $settings, 'img' ), $settings );

        $frontend_classes = apply_filters( 'nx_frontend_classes', array( 
            'wrapper' => $wrapper_class,
            'inner' => $inner_class,
            'content' => $content_class,
            'image' => $image_class,
        ), $settings );

        $output = '';
        $unique_id = uniqid( 'notificationx-' ); 
        $image_data = self::get_image_url( $data, $settings );
        $output .= '<div id="'. esc_attr( $unique_id ) .'" class="'. implode( ' ', $frontend_classes['wrapper'] ) .'">';
            $file = apply_filters( 'nx_frontend_before_inner', '', $settings->themeName );
            if( ! empty( $file ) ) {
                $output .= $file;
            }
            $output .= '<div class="'. implode( ' ', $frontend_classes['inner'] ) .'">';
                if( $image_data ) :
                    $output .= '<div class="notificationx-image">';
                        $output .= '<img class="'. implode( ' ', $frontend_classes['image'] ) .'" src="'. $image_data['url'] .'" alt="'. esc_attr( $image_data['alt'] ) .'">';
                    $output .= '</div>';
                endif;
                $output .= '<div class="'. implode( ' ', $frontend_classes['content'] ) .'">';
                    $output .= NotificationX_Template::get_template_ready( $settings->{ $template }, self::newData( $data ) );
                    if( $settings->close_button ) :
                        $output .= '<span class="notificationx-close"><svg width="8px" height="8px" viewBox="0 0 48 48" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="Page-1" stroke="none" stroke-width="1" fill-rule="evenodd"><g id="close" fill-rule="nonzero"><path d="M28.228,23.986 L47.092,5.122 C48.264,3.951 48.264,2.051 47.092,0.88 C45.92,-0.292 44.022,-0.292 42.85,0.88 L23.986,19.744 L5.121,0.88 C3.949,-0.292 2.051,-0.292 0.879,0.88 C-0.293,2.051 -0.293,3.951 0.879,5.122 L19.744,23.986 L0.879,42.85 C-0.293,44.021 -0.293,45.921 0.879,47.092 C1.465,47.677 2.233,47.97 3,47.97 C3.767,47.97 4.535,47.677 5.121,47.091 L23.986,28.227 L42.85,47.091 C43.436,47.677 44.204,47.97 44.971,47.97 C45.738,47.97 46.506,47.677 47.092,47.091 C48.264,45.92 48.264,44.02 47.092,42.849 L28.228,23.986 Z" id="Shape"></path></g></g></svg></span>';
                    endif;
                    if( is_null( self::$powered_by ) ) :
                        $output .= '<small class="nx-branding">';
                            $output .= '<svg width="12px" height="16px" viewBox="0 0 387 392" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><desc>Created with Sketch.</desc><defs></defs><g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g id="NotificationX_final" transform="translate(-1564.000000, -253.000000)"><g id="Group" transform="translate(1564.000000, 253.000000)"><path d="M135.45,358.68 C173.45,358.68 211.27,358.68 249.07,358.68 C247.02,371.83 221.24,388.59 199.26,390.98 C173.92,393.73 143.23,378.38 135.45,358.68 Z" id="Shape" fill="#5614D5" fill-rule="nonzero"></path><path d="M372.31,305.79 C369.97,305.59 367.6,305.71 365.24,305.71 C359.63,305.7 354.02,305.71 347.08,305.71 C347.08,301.43 347.08,298.42 347.08,295.41 C347.07,248.75 347.25,202.09 346.91,155.43 C346.83,144.89 345.88,134.19 343.79,123.87 C326.39,37.9 239.94,-16.19 154.81,5.22 C86.84,22.31 37.91,84.26 38.19,154.7 C38.36,197.12 38.21,239.54 38.2,281.96 C38.2,285.8 38.18,297.79 38.16,305.7 C32.98,305.66 18.07,305.57 12.86,305.88 C5.13,306.33 -0.06,312.31 0.04,319.97 C0.14,327.43 5.08,332.74 12.67,333.42 C14.78,333.61 16.91,333.57 19.03,333.57 C134.74,333.61 250.46,333.64 366.17,333.66 C368.29,333.66 370.42,333.69 372.53,333.48 C380.01,332.73 385.14,327.23 385.28,319.95 C385.41,312.58 379.86,306.44 372.31,305.79 Z" id="Shape" fill="#5614D5" fill-rule="nonzero"></path><circle id="Oval" fill="#836EFF" fill-rule="nonzero" cx="281.55" cy="255.92" r="15.49"></circle><path d="M295.67,140.1 L295.91,139.94 C295.7,138.63 295.52,137.29 295.27,136.02 C285.87,89.57 245.83,55.34 198.79,52.53 C198.73,52.53 198.67,52.52 198.61,52.52 C196.59,52.4 194.57,52.32 192.53,52.32 C192.48,52.32 192.44,52.32 192.39,52.32 C192.34,52.32 192.3,52.32 192.25,52.32 C190.21,52.32 188.18,52.4 186.17,52.52 C186.11,52.52 186.05,52.53 185.99,52.53 C138.95,55.34 98.91,89.57 89.51,136.02 C89.25,137.29 89.07,138.63 88.87,139.94 L89.11,140.1 C88.2,145.6 87.72,151.22 87.74,156.9 C87.76,161.42 87.77,256.77 87.78,269.74 L119.91,304.42 C119.91,280.14 119.9,170.57 119.85,156.78 C119.72,124.18 142.81,94.69 174.76,86.66 C177.41,85.99 180.09,85.5 182.78,85.13 C183.23,85.07 183.67,85 184.13,84.95 C185.15,84.83 186.17,84.74 187.18,84.66 C188.64,84.56 190.1,84.48 191.58,84.47 C191.85,84.47 192.12,84.45 192.39,84.44 C192.66,84.44 192.93,84.46 193.2,84.47 C194.68,84.48 196.14,84.56 197.6,84.66 C198.62,84.74 199.64,84.83 200.65,84.95 C201.1,85 201.55,85.07 202,85.13 C204.69,85.5 207.37,85.99 210.02,86.66 C241.96,94.69 265.06,124.19 264.93,156.78 C264.91,161.95 264.9,207.07 264.89,228.18 L297.03,206.73 C297.03,194.5 297.04,158.28 297.04,156.91 C297.06,151.21 296.59,145.6 295.67,140.1 Z" id="Shape" fill="#836EFF" fill-rule="nonzero"></path><path d="M31.94,305.72 C25.58,305.85 19.2,305.51 12.86,305.88 C5.13,306.33 -0.06,312.31 0.04,319.97 C0.14,327.43 5.08,332.74 12.67,333.42 C14.78,333.61 16.91,333.57 19.03,333.57 C134.74,333.61 250.45,333.63 366.17,333.66 C368.29,333.66 370.42,333.69 372.53,333.48 C380.01,332.73 385.14,327.23 385.28,319.95 C385.42,312.58 379.87,306.45 372.32,305.79 C369.98,305.59 367.61,305.71 365.25,305.71 C359.64,305.7 354.03,305.71 347.09,305.71 C347.09,301.43 347.09,298.42 347.09,295.41 C347.08,254.74 347.2,214.07 347.01,173.41 L131.62,317.03 L53.58,232.81 L87.05,202.02 L138.72,257.62 L343.2,121.26 C324.59,36.81 239.08,-15.98 154.82,5.21 C86.85,22.3 37.92,84.25 38.2,154.69 C38.37,197.11 38.22,239.53 38.21,281.95 C38.21,287.84 38.3,293.74 38.16,299.62" id="Shape"></path><path d="M346.91,155.42 C346.95,161.41 346.97,167.41 347,173.4 L386.14,147.41 L360.9,109.57 L343.2,121.26 C343.39,122.13 343.62,122.98 343.8,123.85 C345.88,134.18 346.84,144.89 346.91,155.42 Z" id="Shape" fill="#00F9AC" fill-rule="nonzero"></path><path d="M87.05,202.03 L53.58,232.82 L131.62,317.04 L347,173.41 C346.97,167.42 346.96,161.42 346.91,155.43 C346.83,144.89 345.88,134.19 343.79,123.87 C343.61,122.99 343.39,122.14 343.19,121.28 L138.72,257.63 L87.05,202.03 Z" id="Shape"></path><path d="M87.05,202.03 L53.58,232.82 L131.62,317.04 L347,173.41 C346.97,167.42 346.96,161.42 346.91,155.43 C346.83,144.89 345.88,134.19 343.79,123.87 C343.61,122.99 343.39,122.14 343.19,121.28 L138.72,257.63 L87.05,202.03 Z" id="Shape" fill="#21D8A3" fill-rule="nonzero" opacity="0.9"></path></g></g></g></svg>';
                            $output .= ' by <a href="'. NOTIFICATIONX_PLUGIN_URL .'?utm_source='. urlencode( home_url() ) .'&utm_medium=notificationx_referrer" target="_blank" class="nx-powered-by">NotificationX</a>';
                        $output .= '</small>';
                    endif;
                $output .= '</div>';
            $output .= '</div>';
            if( self::is_link_visible( $settings ) ) :
                $notx_link = self::get_link( $data, $settings );
                if( ! empty( $notx_link ) ) {
                    $output .= '<a class="notificationx-link" href="'. esc_url( $notx_link ) .'"></a>';
                }
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
        $classes[ 'img' ] = [];
        
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

		return $classes[ $type ];
    }

    private static function get_theme( $settings ){
        $theme_name = '';
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
                    if( isset( $data['email'] ) ) {
                        $avatar = get_avatar_url( $data['email'], array(
                            'size' => '100',
                        ));
                    }
                    $image_url = $avatar;
                }
                break;
            case 'conversions' :
                switch( $settings->show_notification_image ) {
                    case 'product_image' : 
                        if( $settings->conversion_from != 'custom_notification' ) {
                            if( has_post_thumbnail( $data['product_id'] ) ) {
                                $product_image = wp_get_attachment_image_src( 
                                    get_post_thumbnail_id( $data['product_id'] ), '_nx_notification_thumb', false 
                                );
                                $image_url = is_array( $product_image ) ? $product_image[0] : '';
                            }
                        }
                        if( $settings->conversion_from == 'custom_notification' ) {
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
                    case 'gravatar' : 
                        $avatar = '';
                        if( isset( $data['email'] ) ) {
                            $avatar = get_avatar_url( $data['email'], array(
                                'size' => '100',
                            ));
                        }
                        $image_url = $avatar;
                        break;
                    case 'none' : 
                        $image_url = '';
                        break;
                }
                break;
        }

        if( isset( $settings->show_default_image ) && $settings->show_default_image && $image_url == '' ) {
            $product_image = wp_get_attachment_image_src( $settings->image_url['id'], '_nx_notification_thumb', false );
            $image_url = is_array( $product_image ) ? $product_image[0] : '';
        }     
        
        do_action( 'nx_notification_image_action' );
        $image_data = apply_filters( 'nx_notification_image', [ 'url' => $image_url, 'alt' => $alt_title ], $data, $settings );

        if( ! empty( $image_data['url'] ) ) {
            return $image_data;
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
            // 'template' => $extension->template,
            'template' => "temp_string",
            'themeName' => $extension->themeName,
        ];
        return $extension->frontend_html( $data, $settings, $args );
    }
}