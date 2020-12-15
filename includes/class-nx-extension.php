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
     * NotificationX_Extension or null
     * @var NotificationX_Extension
     */
    protected static $_instance = null;
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
    public static $enabled_types = [];

    public static $powered_by = null;

    protected $limiter;

    protected $template_name;

    /**
     * Default data
     * @var array
     */
    public $defaults = array();
    /**
     * Get instance of NotificationX_Extension
     * @return NotificationX_Extension
     */
    public static function get_instance(){
        $class = get_called_class();
        if( ! isset( self::$_instance[ $class ] ) || self::$_instance[ $class ] === null ) {
            self::$_instance[ $class ] = new $class;
        }
        return self::$_instance[ $class ];
    }
    /**
     * Constructor of extension for ready the settings and cache limit.
     */
    public function __construct( $template = '' ){
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

        $this->template_name = $template;
    }
    public function sort_data( $data ){
        $this->limiter->setValues( $data );
        return $this->limiter->values();
    }
    /**
     * To check plugins installed or not
     * @param string $plugin_file
     * @return boolean
     * @since 1.2.4
     */
    public function plugins( $plugin_file = '' ){
        if( empty( $plugin_file ) ) {
            return false;
        }
        if( ! function_exists( 'get_plugins' ) ) {
            require ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugins = get_plugins();
        if( isset( $plugins[ $plugin_file ] ) ) {
            return true;
        }
        return false;
    }

    public function template_name( $data ){
        if( $this->template_name ) {
            $data[] = $this->template_name;
        }

        return $data;
    }
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

        if( empty( self::$active_items ) ) {
            self::$active_items = NotificationX_Admin::get_active_items();
        }

        if( ! empty( self::$active_items ) ) {
            return in_array( $type, array_keys( self::$active_items ) );
        } else {
            return false;
        }
    }

    public static function is_enabled( $type = '' ){
        if( empty( $type ) ) {
            return false;
        }

        if( $type == 'press_bar' ) {
            return true;
        }

        $types = array(
            'wp_comments',
            'wp_stats',
            'wp_reviews',
            'woocommerce',
            'woo_reviews',
            'edd',
            'give',
            'tutor',
            'cf7'
        );

        self::$enabled_types = NotificationX_Admin::$enabled_types;

        if( ! empty( self::$enabled_types ) ) {
            foreach( $types as $type ) {
                if( in_array( $type, array_keys( self::$enabled_types ) ) ) {
                    return true;
                }
            }
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
        if( empty( $type ) ) {
            return $notifications;
        }
        if( empty( $notifications ) || ! isset( $notifications[ $type ] ) ) {
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

    public static function remote_get( $url, $args = array() ){
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

    public function trimed( &$value ) {
        if( ! is_array( $value ) ) {
            $value = trim( $value );
        } else {
            $value = $value;
        }
    }

    public static function notEmpty( $key, $data ){
        if( isset( $data[ $key ] ) ) {
            if( ! empty( $data[ $key ] ) ) {
                return true;
            }
        }
        return false;
    }
    /**
     * For checking type
     *
     * @param [type] $post_id
     * @return boolean
     *
     * @since 1.1.3
     */
    public function check_type( $post_id ){
        $settings = NotificationX_MetaBox::get_metabox_settings( $post_id );
        if( $this->type !== NotificationX_Helper::get_type( $settings ) ) {
            return false;
        }
        return true;
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

        $raw_data = $data;
        array_walk( $data, array( $this, 'trimed' ) );
        $this->defaults = apply_filters('nx_fallback_data', array(), $data, $settings );
        $data = array_merge( $data, $this->defaults );

        extract( $args );
        $settings->themeName = $settings->{ $themeName };
        if( empty( $settings->{ $template . '_adv' } ) ) {
            $template =  $template . '_new_string';
            $theme_names = apply_filters( 'nx_themes_for_template', array( 'review_saying', 'actively_using' ));
            if( in_array( $settings->themeName, $theme_names ) ) {
                $template =  $settings->themeName . '_template_new_string';
            }
        }

        $template = apply_filters( 'nx_template_id' , $template, $settings);

        $image_class = apply_filters( 'nx_frontend_image_classes', self::get_classes( $settings, 'img' ), $settings );

        $content_class = apply_filters( 'nx_frontend_content_classes', array(), $settings );

        $inner_class = apply_filters( 'nx_frontend_inner_classes', array_merge(
            ['notificationx-inner'], self::get_classes( $settings, 'inner' ), $image_class
        ), $settings );
        $if_is_mobile = wp_is_mobile() ? 'nx-mobile-notification' : '';
        $wrapper_class = apply_filters( 'nx_frontend_wrapper_classes', array_merge(
            ['nx-notification'], self::get_classes( $settings ), array( $if_is_mobile )
        ), $settings );

        $frontend_classes = apply_filters( 'nx_frontend_classes', array(
            'wrapper' => $wrapper_class,
            'inner' => $inner_class,
            'content' => $content_class,
            'image' => $image_class,
        ), $settings );

        $output = '';
        $unique_id = uniqid( 'notificationx-' );
        $image_data = self::get_image_url( $raw_data, $settings );
        $has_no_image = '';
        if( $image_data == false || empty( $image_data ) ) {
            $has_no_image = 'has-no-image';
        }
        $output .= '<div id="'. esc_attr( $unique_id ) .'" class="'. implode( ' ', $frontend_classes['wrapper'] ) .'">';
            $output .= apply_filters( 'nx_frontend_before_html', '', $settings );
            $file = apply_filters( 'nx_frontend_before_inner', '', $settings->themeName );
            if( ! empty( $file ) ) {
                $output .= $file;
            }
            $output .= '<div class="'. implode( ' ', $frontend_classes['inner'] ) .' '. $has_no_image .'">';
                if( $image_data ) :
                    $img_attr = isset( $image_data['attr'] ) ? implode( ' ', $image_data['attr'] ) : '';
                    $img_classes = isset( $image_data['classes'] ) ? $image_data['classes'] : '';
                    $output .= '<div class="notificationx-image '. $img_classes .'" '. $img_attr .'>';
                        $before_image = apply_filters( 'nx_frontend_before_image', '', $settings->themeName );
                        if( ! empty( $before_image ) ) {
                            $output .= $before_image;
                        }
                        if( isset( $image_data['gravatar'] ) && $image_data['gravatar'] ) {
                            $output .= $image_data['url'];
                        } else {
                            $output .= '<img src="'. $image_data['url'] .'" alt="'. esc_attr( $image_data['alt'] ) .'">';
                        }
                    $output .= '</div>';
                endif;
                $output .= '<div class="notificationx-content '. implode(' ', $frontend_classes['content'] ) .'">';
                    $before_content = apply_filters( 'nx_frontend_before_content', '', $settings->themeName );
                    if( ! empty( $before_content ) ) {
                        $output .= $before_content;
                    }
                    $output .= NotificationX_Template::get_template_ready( $settings->{ $template }, self::newData( $data ), $settings, ! self::$powered_by );

                $output .= '</div>';
                if( $settings->close_button ) :
                    $output .= '<span class="notificationx-close"><svg width="8px" height="8px" viewBox="0 0 48 48" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="Page-1" stroke="none" stroke-width="1" fill-rule="evenodd"><g id="close" fill-rule="nonzero"><path d="M28.228,23.986 L47.092,5.122 C48.264,3.951 48.264,2.051 47.092,0.88 C45.92,-0.292 44.022,-0.292 42.85,0.88 L23.986,19.744 L5.121,0.88 C3.949,-0.292 2.051,-0.292 0.879,0.88 C-0.293,2.051 -0.293,3.951 0.879,5.122 L19.744,23.986 L0.879,42.85 C-0.293,44.021 -0.293,45.921 0.879,47.092 C1.465,47.677 2.233,47.97 3,47.97 C3.767,47.97 4.535,47.677 5.121,47.091 L23.986,28.227 L42.85,47.091 C43.436,47.677 44.204,47.97 44.971,47.97 C45.738,47.97 46.506,47.677 47.092,47.091 C48.264,45.92 48.264,44.02 47.092,42.849 L28.228,23.986 Z" id="Shape"></path></g></g></svg></span>';
                endif;
            $output .= '</div>';
            if( self::is_link_visible( $settings ) ) :
                $notx_link = self::get_link( $data, $settings );
                if( ! empty( $notx_link ) ) {
                    if( $settings->link_open ) {
                        $output .= '<a class="notificationx-link" target="_blank" href="'. esc_url( $notx_link ) .'"></a>';
                    } else {
                        $output .= '<a class="notificationx-link" href="'. esc_url( $notx_link ) .'"></a>';
                    }
                }
            endif;
            $output .= apply_filters( 'nx_frontend_after_html', '', $settings );
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
            case 'conversions' || 'form' :
                if( $settings->advance_edit || $settings->form_advance_edit ) {
                    $classes[ 'inner' ][] = 'nx-customize-style-' . $settings->id;
                    $classes[ 'inner' ][] =  'nx-img-' . $settings->image_position;
                    $classes[ 'img' ][] = 'nx-img-' . $settings->image_shape;
                    if( $settings->image_position == 'right' ) {
                        $classes[ 'inner' ][] =  'nx-flex-reverse';
                    }
                }
                break;
            case 'elearning' :
                if( $settings->elearning_advance_edit ) {
                    $classes[ 'inner' ][] = 'nx-customize-style-' . $settings->id;
                    $classes[ 'inner' ][] =  'nx-img-' . $settings->image_position;
                    $classes[ 'img' ][] = 'nx-img-' . $settings->image_shape;
                    if( $settings->image_position == 'right' ) {
                        $classes[ 'inner' ][] =  'nx-flex-reverse';
                    }
                }
                break;
            case 'donation' :
                if( $settings->donation_advance_edit ) {
                    $classes[ 'inner' ][] = 'nx-customize-style-' . $settings->id;
                    $classes[ 'inner' ][] =  'nx-img-' . $settings->image_position;
                    $classes[ 'img' ][] = 'nx-img-' . $settings->image_shape;
                    if( $settings->image_position == 'right' ) {
                        $classes[ 'inner' ][] =  'nx-flex-reverse';
                    }
                }
                break;
            case 'reviews' :
                if( $settings->wporg_advance_edit ) {
                    $classes[ 'inner' ][] = 'nx-customize-style-' . $settings->id;
                    $classes[ 'inner' ][] =  'nx-img-' . $settings->wporg_image_position;
                    $classes[ 'img' ][] = 'nx-img-' . $settings->wporg_image_shape;
                    if( $settings->wporg_image_position == 'right' ) {
                        $classes[ 'inner' ][] =  'nx-flex-reverse';
                    }
                }
                break;
            case 'download_stats' :
                if( $settings->wpstats_advance_edit ) {
                    $classes[ 'inner' ][] =  'nx-img-' . $settings->wpstats_image_position;
                    if( $settings->wpstats_image_position == 'right' ) {
                        $classes[ 'inner' ][] =  'nx-flex-reverse';
                    }
                    $classes[ 'inner' ][] = 'nx-customize-style-' . $settings->id;
                }
                break;
            case 'press_bar' :
                if( $settings->bar_advance_edit ) {
                    $classes[ 'inner' ][] = 'nx-customize-style-' . $settings->id;
                }
        }

		if( $settings->close_button ) {
			$classes[ 'inner' ][] = 'nx-has-close-btn';
        }

        $classes[ 'wrapper' ][] = 'nx-' . NotificationX_Helper::get_type( $settings );
		$classes[ 'wrapper' ][] = 'nx-' . esc_attr( $settings->conversion_position );
        $classes[ 'wrapper' ][] = 'notificationx-' . $settings->id;
		$classes[ 'wrapper' ][] = 'nx-' . $settings->display_type;
        $classes[ 'inner' ][] = 'nx-notification-' . esc_attr( NotificationX_Helper::get_theme( $settings ) );

		return $classes[ $type ];
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
        $link = isset( $data['link'] ) ? $data['link'] : '';
        $link = apply_filters('nx_notification_link', $link, $settings );
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
        $alt_title = isset( $data['name'] ) ? $data['name'] : '';

        if( empty( $alt_title ) ) {
            $alt_title = isset( $data['title'] ) ? $data['title'] : '';
        }

        $type = NotificationX_Helper::get_type( $settings ); //TODO: something has to do in future.

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
                        if( $settings->conversion_from == 'woocommerce' || $settings->conversion_from == 'edd' ) {
                            if( has_post_thumbnail( $data['product_id'] ) ) {
                                $product_image = wp_get_attachment_image_src(
                                    get_post_thumbnail_id( $data['product_id'] ), 'medium', false
                                );
                                $image_url = is_array( $product_image ) ? $product_image[0] : '';
                            }
                        }
                        break;
                    case 'gravatar' :
                        $avatar = '';
                        if( isset( $data['email'] ) ) {
                            // $avatar = get_avatar_url( $data['email'], array(
                            //     'size' => '100',
                            // ));
                            $avatar = get_avatar( $data['email'], 100, '', $alt_title, array( 'extra_attr' => 'title="'. $alt_title .'"' ) );
                        }
                        $image_data['gravatar'] = true;
                        $image_url = $avatar;
                        break;
                    case 'none' :
                        $image_url = '';
                        break;
                }
                break;
        }

        if( isset( $settings->show_default_image ) && $settings->show_default_image && $image_url == '' ) {
            if( isset( $settings->image_url['id'] ) ) {
                $product_image = wp_get_attachment_image_src( $settings->image_url['id'], '_nx_notification_thumb', false );
                $image_url = is_array( $product_image ) ? $product_image[0] : '';
            }
        }

        do_action( 'nx_notification_image_action' );
        $image_data = apply_filters( 'nx_notification_image', [ 'url' => $image_url, 'alt' => $alt_title ], $data, $settings );

        if( ! empty( $image_data['url'] ) ) {
            return $image_data;
        }

        return false;
    }

    public function template_string_by_theme( $template, $old_template, $posts_data ){
        if( NotificationX_Helper::get_type( $posts_data ) === $this->type ) {
            $breaks_data = apply_filters( 'nx_theme_breaks_data', array( 'br_before' => [ 'third_param', 'fourth_param' ] ));
            $template = NotificationX_Helper::regenerate_the_theme( $old_template, $breaks_data );
            return $template;
        }
        return $template;
    }
    /**
     * Generating Full Name with one letter from last name
     * @since 1.3.9
     * @param string $first_name
     * @param string $last_name
     * @return string
     */
    protected function name( $first_name = '', $last_name = '' ){
        $name = $first_name;
        $name .= ! empty( $last_name ) ? ' ' . mb_substr( $last_name, 0, 1 ) : '';
        return $name;
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