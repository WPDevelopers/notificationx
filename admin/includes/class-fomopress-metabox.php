<?php

class FomoPress_MetaBox {

    public $type = 'fomopress';

    public static $args;
    public static $prefix = 'fomopress_';
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

    public static function render_fomopress_metabox( $post = null ) {

        self::$post_id = $post->ID;

        $tabs       = self::$args['tabs'];
        $prefix     = self::$prefix;
        $metabox_id = self::$args['id'];

        $tabnumber	= isset( self::$args['tabnumber'] ) && self::$args['tabnumber'] ? true : false;
        
        wp_nonce_field( self::$args['id'], self::$args['id'] . '_nonce' );
		include_once FOMOPRESS_ADMIN_DIR_PATH . 'partials/fomopress-admin-display.php';
    }

    public static function get_args() {
        return require FOMOPRESS_ADMIN_DIR_PATH . 'includes/fomopress-metabox-helper.php';
    }

    public static function render_meta_field( $key = '', $field = [], $value = '' ) {
        $post_id   = self::$post_id;
        $name      = self::$prefix . $key;
        $id        = self::get_row_id( $key );
        $file_name = isset( $field['type'] ) ? $field['type'] : 'text';
        
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

        $class  = 'fomopress-meta-field';
        $row_class = '';

        switch( $file_name ) {
            case 'group':
                $row_class .= ' fomopress-group-row';
                # code...
                break;
            case 'message':
                $row_class .= ' fomopress-info-message-wrapper';
                # code...
                break;
        }

        $attrs = '';

        if( isset( $field['toggle'] ) && in_array( $file_name, array( 'checkbox', 'select', 'toggle' ) ) ) {
            $attrs .= ' data-toggle="' . esc_attr( json_encode( $field['toggle'] ) ) . '"';
        }

        if( isset( $field['hide'] ) && $file_name == 'select' ) {
            $attrs .= ' data-hide="' . esc_attr( json_encode( $field['hide'] ) ) . '"';
        }

        include FOMOPRESS_ADMIN_DIR_PATH . 'partials/fomopress-field-display.php';
    }
    /**
     * Get the row id ready
     *
     * @param string $key
     * @return string
     */
    protected static function get_row_id( $key ) {
        return str_replace( '_', '-', self::$prefix ) . $key;
    }

    /**
     * Add the metabox to the posts
     *
     * @return void
     */
	public function add_meta_boxes() {
        self::$args         = wp_parse_args( $this->get_args(), $this->defaults );
        self::$object_types = (array)self::$args['object_types'];
        add_meta_box( self::$args['id'], self::$args['title'], __CLASS__ . '::render_fomopress_metabox', self::$object_types, self::$args['context'], self::$args['priority'] );
    }

    public static function get_metabox_fields() {
        $args = self::get_args();
        $tabs = $args['tabs'];

        $new_fields = [];

        foreach( $tabs as $tab ) {
            $sections = $tab['sections'];
            foreach( $sections as $section ) {
                $fields = $section['fields'];
                foreach( $fields as $id => $field ) {
                    $new_fields[ $id ] = $field;
                }    
            }
        }

        return apply_filters( 'fomopress_meta_fields', $new_fields );
    }
    
    public static function save_metabox( $post_id ) {
        $args = self::get_args();

        $metabox_id     = $args['id'];
        $object_types   = $args['object_types'];
        $prefix         = self::$prefix;

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

        $fields = self::get_metabox_fields();
        $data = [];
        foreach ( $fields as $name => $field ) {

            $field_id = $prefix . $name;
            $value = '';

            if ( isset( $_POST[$field_id] ) ) {
                $value = FomoPress_Helper::sanitize_field( $field, $_POST[$field_id] );
            } else {
                if ( 'checkbox' == $field['type'] ) {
                    $value = '0';
                }
            }

            update_post_meta( $post_id, "_{$field_id}", $value );
            $data[ "_{$field_id}" ] = $value;
        }

        
        $d_type = get_post_meta( $post_id, '_fomopress_current_data_ready_for', true );
        $type = $_POST['fomopress_display_type'];
        
        if( $type == 'conversions' ) {
            $type = $_POST['fomopress_conversion_from'];
        }
        
        if( $type != $d_type ) {
            do_action( 'fomopress_get_conversions_ready', $type, $data );
        }

        update_post_meta( $post_id, '_fomopress_current_data_ready_for', $type );
        update_post_meta( $post_id, '_fomopress_current_tab', $_POST['fomopress_current_tab'] );
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