<?php

class NotificationX_MetaBox {

    public $type = 'notificationx';

    public static $args;
    public static $prefix = 'nx_meta_';
    public static $post_id;
    public static $object_types;

    public $defaults = array(
        'id'            => '',
        'title'         => '',
        'object_types'  => array(),
        'context'       => 'normal',
        'priority'      => 'low',
        'show_header'   => true,
        'prefix' => ''
    );

    public function finalize_builder( $id, $tab ){
        if( $id === 'finalize_tab' ) {
            echo __( 'You are about to publish <strong class="finalize_notificationx_name"></strong>. You can rename this and edit everything whenever you want from <a href="'. admin_url('edit.php?post_type=notificationx') .'">NotificationX</a> Page.', 'notificationx' );
        }
    }

    public static function render_metabox( $post = null ) {

        self::$post_id = $post->ID;

        $tabs       = self::$args['tabs'];
        $prefix     = self::$prefix;
        $metabox_id = self::$args['id'];

        $tabnumber	= isset( self::$args['tabnumber'] ) && self::$args['tabnumber'] ? true : false;
        
        wp_nonce_field( $metabox_id, $metabox_id . '_nonce' );
        include_once NOTIFICATIONX_ADMIN_DIR_PATH . 'partials/nx-admin-display.php';
    }
    /**
     * This function is responsible for get all metabox arguments
     *
     * @return void
     */
    public static function get_args() {
        if( ! function_exists( 'notificationx_metabox_args' ) ) {
            require NOTIFICATIONX_ADMIN_DIR_PATH . 'includes/nx-metabox-helper.php';
        }
        do_action( 'nx_before_metabox_load' );
        return notificationx_metabox_args();
    }
    
    public static function get_builder_args() {
        if( ! function_exists( 'notificationx_builder_args' ) ) {
            require NOTIFICATIONX_ADMIN_DIR_PATH . 'includes/nx-builder-helper.php';
        }
        do_action( 'nx_before_builder_load' );
        return notificationx_builder_args();
    }

    public static function render_meta_field( $key = '', $field = [], $value = '', $idd = null ) {
        $post_id   = self::$post_id;
        $attrs = '';
        if( ! is_null( $idd ) ){
            $post_id   = $idd;
        }
        $name      = self::$prefix . $key;
        $field_id  = $name;
        $id        = self::get_row_id( $key );
        $file_name = isset( $field['type'] ) ? $field['type'] : '';
        
        if( 'template' === $file_name ) {
            $default = isset( $field['defaults'] ) ? $field['defaults'] : [];
        } else {
            $default = isset( $field['default'] ) ? $field['default'] : '';
        }

        if( empty( $value ) ) {
            if( metadata_exists( 'post', $post_id, "_{$name}" ) ) {
                $value = get_post_meta( $post_id, "_{$name}", true );
            } else {
                $value = $default;
            }
        } else {
            $value = $value;
        }

        $default_attr = is_array( $default ) ? json_encode( $default ) : $default;

        if( ! empty( $default_attr ) ) {
            $attrs .= ' data-default="' . esc_attr( $default_attr ) . '"';
        }

        $class  = 'nx-meta-field';
        $row_class = self::get_row_class( $file_name );
        $row_class .= ' nx-' . $key;
                
        $attrs .= ' data-key="' . esc_attr( $key ) . '"';

        if( isset( $field['tab'] ) && $file_name == 'select' ) {
            $attrs .= ' data-tab="' . esc_attr( json_encode( $field['tab'] ) ) . '"';
        }

        include NOTIFICATIONX_ADMIN_DIR_PATH . 'partials/nx-field-display.php';
    }
    /**
     * Get the row id ready
     *
     * @param string $key
     * @return string
     */
    public static function get_row_id( $key ) {
        return str_replace( '_', '-', self::$prefix ) . $key;
    }
    /**
     * Get the row id ready
     *
     * @param string $key
     * @return string
     */
    public static function get_row_class( $file ) {
        $prefix = str_replace( '_', '-', self::$prefix );

        switch( $file ) {
            case 'group':
                $row_class = $prefix .'group-row';
                break;
            case 'colorpicker':
                $row_class = $prefix .'colorpicker-row';
                break;
            case 'message':
                $row_class = $prefix . 'info-message-wrapper';
                break;
            case 'theme':
                $row_class = $prefix . 'theme-field-wrapper';
                break;
            default :
                $row_class = $prefix . $file;
                break;
        }

        return $row_class;
    }

    /**
     * Add the metabox to the posts
     *
     * @return void
     */
	public function add_meta_boxes() {
        self::$args         = wp_parse_args( $this->get_args(), $this->defaults );
        self::$object_types = (array)self::$args['object_types'];
        add_meta_box( self::$args['id'], self::$args['title'], __CLASS__ . '::render_metabox', self::$object_types, self::$args['context'], self::$args['priority'] );
    }

    public static function get_metabox_fields( $prefix = '' ) {
        $args = self::get_args();
        $tabs = $args['tabs'];

        $new_fields = [];

        foreach( $tabs as $tab ) {
            $sections = $tab['sections'];
            foreach( $sections as $section ) {
                $fields = $section['fields'];
                foreach( $fields as $id => $field ) {
                    $new_fields[ $prefix . $id ] = $field;
                }    
            }
        }

        return apply_filters('nx_meta_fields', $new_fields );
    }
    
    public static function save_metabox( $post_id ) {
        $args = self::get_args();

        $metabox_id     = $args['id'];
        $object_types   = $args['object_types'];

        // Verify the nonce.
        if ( ! isset( $_POST[$metabox_id . '_nonce'] ) || ! wp_verify_nonce( $_POST[$metabox_id . '_nonce'], $metabox_id ) ) {
            return $post_id;
        }

        // Verify if this is an auto save routine.
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return $post_id;
        }
        
        // Check permissions to edit pages and/or posts
        if ( in_array( $_POST['post_type'], $object_types ) ) {
            if ( ! current_user_can( 'edit_page', $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
                return $post_id;
            }
        }
        
        /**
         * Save all meta!
         */ 
        self::save_data( $_POST, $post_id);
        do_action('notificationx_save_post'); 
    }

    protected static function template_generate( $template ){
        $template_string = [];
        $temp_val = '';
        
        if( ! empty( $template ) ) {
            $i = $j = 0;
            foreach( $template as $key => $value ) {
                if( in_array( $key, array( 'third_param', 'fourth_param' ) ) ) {
                    $i++;
                }
                if( strpos( $value, 'tag_' ) === 0 ) {
                    $tag = str_replace( 'tag_', '', $value );
                    if( $tag == 'custom' ) {
                        continue;
                    }
                    $template_string[ $i ] = "{{{$tag}}} ";
                } else {
                    if( ! empty( trim( $template_string[ $i ] ) ) ) {
                        $temp_val = trim( $template_string[ $i ] ) . ' ';
                    }
                    if( ! empty( $temp_val ) && strpos( $key, 'custom_' ) === 0 ) {
                        $value = '';
                    }
                    $template_string[ $i ] =  $temp_val . $value . " ";
                }
                $temp_val = $value = '';
            }
        }
        
        $saved_template = $template_string;
        $template_string = [];

        return $saved_template;
    }

    public static function save_data( $posts, $post_id ){
        $prefix       = self::$prefix;
        $fields       = self::get_metabox_fields();
        $old_settings = self::get_metabox_settings( $post_id );
        $data         = [];
        $new_settings = new stdClass();

        foreach ( $fields as $name => $field ) {
            $field_id = $prefix . $name;
            $value = '';

            if ( isset( $posts[$field_id] ) ) {
                $value = NotificationX_Helper::sanitize_field( $field, $posts[$field_id] );
            } else {
                if ( 'checkbox' == $field['type'] ) {
                    $value = '0';
                }
            }

            if( strpos( $field_id, 'template_new', -12 ) !== false && strpos( $field_id, 'template_new', -12 ) >= 0 ) {
                $template_string = self::template_generate( $posts[ $field_id ] );
                update_post_meta( $post_id, "_{$field_id}_string", $template_string );
            }
            update_post_meta( $post_id, "_{$field_id}", $value );
            $data[ "_{$field_id}" ] = $new_settings->{ $name } = $value;
        }
        update_post_meta( $post_id, '_nx_meta_active_check', true );

        $d_type = get_post_meta( $post_id, '_nx_meta_current_data_ready_for', true );

        switch( $posts['nx_meta_display_type'] ) {
            case 'comments' : 
                $type = $posts['nx_meta_comments_source'];
                break;
            case 'conversions' : 
                $type = $posts['nx_meta_conversion_from'];
                break;
            case 'reviews' : 
                $type = $posts['nx_meta_reviews_source'];
                break;
            case 'download_stats' : 
                $type = $posts['nx_meta_stats_source'];
                break;
        }

        if( self::check_any_changes( $old_settings, $new_settings ) ) {
            do_action( 'nx_get_conversions_ready', $type, $data );
        }

        update_post_meta( $post_id, '_nx_meta_current_data_ready_for', $type );
        update_post_meta( $post_id, '_nx_builder_current_tab', $posts['nx_builder_current_tab'] );
    }
    /**
     * This function is responsible for checking all the old_settings with new_settings for changes
     *
     * @param stdClass $old_settings
     * @param stdClass $new_settings
     * @return boolean
     */
    protected static function check_any_changes( stdClass $old_settings, stdClass $new_settings ){
        if( empty( $new_settings ) || empty( $old_settings ) ) return;

        $opt_in = apply_filters('nx_update_changes', array(
            'display_from',
            'display_type',
            'conversion_from'
        ));

        foreach( $old_settings as $key => $value ) {
            if( in_array( $key, $opt_in ) ) {
                if( $new_settings->{$key} == $value ) {
                    $status = false;
                } else {
                    $status = true;
                    break;
                }
            }
        }

        return $status;
    }

    /**
     * Get all the meta settings of a noitification post
     *
     * @param int $id
     * @return stdClass object
     */
    public static function get_metabox_settings( $id ){
        $fields     = self::get_metabox_fields();
        $prefix     = self::$prefix;
        $settings   = new stdClass();

        if( empty( $id ) ) {
            return;
        }

        foreach ( $fields as $name => $field ) {
            $field_id   = $prefix . $name;
            $default    = isset( $field['default'] ) ? $field['default'] : '';

            if( isset( $field['type'] ) && $field['type'] == 'template' ) {
                $default    = isset( $field['defaults'] ) ? $field['defaults'] : [];

                if( strpos( $name, 'template_new', -12 ) >= 0 && metadata_exists( 'post', $id, "_{$field_id}_string" ) ) {
                    $value  = get_post_meta( $id, "_{$field_id}_string", true );
                    $settings->{ "{$name}_string" } = $value;
                }

            }

            if ( metadata_exists( 'post', $id, "_{$field_id}" ) ) {
                $value  = get_post_meta( $id, "_{$field_id}", true );
            } else {
                $value  = $default;
            }

            $settings->{$name} = $value;
        }
        
        $settings->id = $id;

        return $settings;
    }
}