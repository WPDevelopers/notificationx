<?php 
/**
 * This class is responsible for all settings things happening in FomoPress Plugin
 */
class FomoPress_Settings {
    public static function init(){
        add_action( 'fomopress_before_settings_form', array( __CLASS__, 'notice_template' ), 9 );
        add_action( 'fomopress_settings_header', array( __CLASS__, 'header_template' ), 10 );
    }
    /**
     * This function is responsible for settings page notice
     * before the settings form start
     *
     * @hooked fomopress_before_settings_form
     * @return void
     */
    public static function notice_template(){
        ?>
            <div class="fomopress-settings-notice"></div>
        <?php
    }
    /**
     * This function is responsible for settings page header
     *
     * @hooked fomopress_settings_header
     * @return void
     */
    public static function header_template(){
        ?>
            <div class="fomopress-settings-header">
                <div class="fps-header-left">
                    <div class="fps-admin-logo-inline">
                        <!-- logo will be here -->
                    </div>
                    <h2 class="title"><?php _e( 'FomoPress Settings', 'fomopress' ); ?></h2>
                </div>
                <div class="fps-header-right">
                <!-- <input type="submit" class="fomopress-settings-button" name="fomopress_settings_submit" id="fomopress-submit" value="<?php // esc_html_e('Save Changes', 'fomopress'); ?>" /> -->
                    <button type="submit" class="fomopress-settings-button" name="fomopress_settings_submit" id="fomopress-submit"><?php _e( 'Save settings', 'fomopress' ); ?></button>
                </div>
            </div>
        <?php
    }
    /**
	 * Get all settings fields
	 *
	 * @param array $settings
	 * @return array
	 */
	private static function get_settings_fields( $settings ){
        $new_fields = [];

        foreach( $settings as $setting ) {
            $sections = $setting['sections'];
            foreach( $sections as $section ) {
                $fields = $section['fields'];
                foreach( $fields as $id => $field ) {
                    $new_fields[ $id ] = $field;
                }
            }
        }

        return apply_filters( 'fomopress_settings_fields', $new_fields );
	}
	/**
	 * Get the whole settings array
	 *
	 * @return void
	 */
	public static function settings_args(){
        if( ! function_exists( 'fomopress_settings_array' ) ) {
            require FOMOPRESS_ADMIN_DIR_PATH . 'includes/fomopress-settings-page-helper.php';
        }
        do_action( 'fomopress_before_settings_load' );
        return fomopress_settings_array();
	}
	/**
     * Render the settings page
	 *
     * @return void
	 */
    public static function settings_page(){
        $settings_args = self::settings_args();
		$value = FomoPress_DB::get_settings();

		if( isset( $_POST[ 'fomopress_settings_submit' ] ) ) : 
			self::save_settings( $_POST );
        endif;

		include_once FOMOPRESS_ADMIN_DIR_PATH . 'partials/fomopress-settings-display.php';
	}
    /**
     * This function is responsible for render settings field
     *
     * @param string $key
     * @param array $field
     * @return void
     */
    public static function render_field( $key = '', $field = [] ) {
        $post_id   = '';
        $name      = $key;
        // $id        = $key;
        $id        = FomoPress_Metabox::get_row_id( $key );
        $file_name = isset( $field['type'] ) ? $field['type'] : 'text';
        
        if( 'template' === $file_name ) {
            $default = isset( $field['defaults'] ) ? $field['defaults'] : [];
        } else {
            $default = isset( $field['default'] ) ? $field['default'] : '';
        }

        $saved_value = FomoPress_DB::get_settings( $name );
        if( ! empty( $saved_value ) ) {
            $value = $saved_value;
        } else {
            $value = $default;
        }
        
        $class  = 'fomopress-settings-field';
        $row_class = FomoPress_Metabox::get_row_class( $file_name );

        $attrs = '';

        if( isset( $field['toggle'] ) && in_array( $file_name, array( 'checkbox', 'select', 'toggle', 'theme' ) ) ) {
            $attrs .= ' data-toggle="' . esc_attr( json_encode( $field['toggle'] ) ) . '"';
        }

        if( isset( $field['hide'] ) && $file_name == 'select' ) {
            $attrs .= ' data-hide="' . esc_attr( json_encode( $field['hide'] ) ) . '"';
        }

        include FOMOPRESS_ADMIN_DIR_PATH . 'partials/fomopress-field-display.php';
    }
    /**
     * This function is responsible for 
     * save all settings data, including checking the disable field to prevent
     * users manipulation.
     *
     * @param array $values
     * @return void
     */
    public static function save_settings( $values = [] ){
		/**
         * Verify the Nonce
         */
        if ( ! isset( $values['fomopress_settings_nonce'] ) || ! wp_verify_nonce( $values['fomopress_settings_nonce'], 'fomopress_settings' ) ) {
            return;
		}

		if( ! isset( $values['fomopress_settings_submit'] ) || ! is_array( $values ) ) {
			return;
		}

		$settings_args = self::settings_args();
		$fields = self::get_settings_fields( $settings_args );

		foreach( $values as $key => $value ) {

			if( array_key_exists( $key, $fields ) ) {
				if( empty( $value ) ) {
					$value = $fields[ $key ]['default'];
                }
                
                if( isset( $fields[ $key ]['disable'] ) && $fields[ $key ]['disable'] === true ) {
                    $value = $fields[ $key ]['default'];
                }

				$value = FomoPress_Helper::sanitize_field( $fields[ $key ], $value );
				$data[ $key ] = $value;
			}
		}

		FomoPress_DB::update_settings( $data );
	}
}