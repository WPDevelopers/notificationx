<?php 
/**
 * This class is responsible for all settings things happening in FomoPress Plugin
 */
class FomoPress_Settings {
    public static function init(){
        add_action( 'fomopress_before_settings_form', array( __CLASS__, 'notice_template' ), 9 );
        add_action( 'fomopress_settings_header', array( __CLASS__, 'header_template' ), 10 );
        add_action( 'wp_ajax_fomopress_general_settings_ac', array( __CLASS__, 'general_settings_ac' ), 10 );
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
                        <svg width="32px" height="32px" viewBox="0 0 512 512" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                            <title>FomoPress Icon</title>
                            <defs>
                                <linearGradient x1="50%" y1="0%" x2="50%" y2="100%" id="linearGradient-1">
                                    <stop stop-color="#806EE8" stop-opacity="0.985309103" offset="0%"></stop>
                                    <stop stop-color="#6044EA" offset="100%"></stop>
                                </linearGradient>
                                <circle id="path-2" cx="55" cy="55" r="55"></circle>
                                <filter x="-66.4%" y="-44.5%" width="223.6%" height="223.6%" filterUnits="objectBoundingBox" id="filter-3">
                                    <feOffset dx="-5" dy="19" in="SourceAlpha" result="shadowOffsetOuter1"></feOffset>
                                    <feGaussianBlur stdDeviation="19.5" in="shadowOffsetOuter1" result="shadowBlurOuter1"></feGaussianBlur>
                                    <feColorMatrix values="0 0 0 0 0   0 0 0 0 0   0 0 0 0 0  0 0 0 0.28161798 0" type="matrix" in="shadowBlurOuter1"></feColorMatrix>
                                </filter>
                            </defs>
                            <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                <g id="Artboard-1-alt-Copy" transform="translate(-244.000000, -244.000000)">
                                    <g id="fomopress-logo" transform="translate(244.000000, 244.000000)">
                                        <circle id="Oval" stroke="url(#linearGradient-1)" stroke-width="50" fill="#EAEAEA" cx="256" cy="256" r="231"></circle>
                                        <circle id="Oval-Inner" fill="#252526" cx="256" cy="256" r="125"></circle>
                                        <g id="eye" transform="translate(126.000000, 127.000000)" opacity="0.95">
                                            <g id="Oval-3">
                                                <use fill="black" fill-opacity="1" filter="url(#filter-3)" xlink:href="#path-2"></use>
                                                <use fill="#FFFFFF" fill-rule="evenodd" xlink:href="#path-2"></use>
                                            </g>
                                        </g>
                                    </g>
                                </g>
                            </g>
                        </svg>
                    </div>
                    <h2 class="title"><?php _e( 'FomoPress Settings', 'fomopress' ); ?></h2>
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
    public static function save_settings( $posted_fields = [] ){
		$settings_args = self::settings_args();
		$fields = self::get_settings_fields( $settings_args );
        $data = [];

		foreach( $posted_fields as $posted_field ) {
			if( array_key_exists( $posted_field['name'], $fields ) ) {
                if( empty( $posted_field['value'] ) ) {
					$posted_value = $fields[ $posted_field['name'] ]['default'];
                }
                if( isset( $fields[ $posted_field['name'] ]['disable'] ) && $fields[ $posted_field['name'] ]['disable'] === true ) {
                    $posted_value = $fields[ $posted_field['name'] ]['default'];
                }
                $posted_value = FomoPress_Helper::sanitize_field( $fields[ $posted_field['name'] ], $posted_field['value'] );

				$data[ $posted_field['name'] ] = $posted_value;
			}
        }
        
		FomoPress_DB::update_settings( $data );
    }
    
    public static function general_settings_ac(){
        /**
         * Verify the Nonce
         */
        if ( ( ! isset( $_POST['nonce'] ) && ! isset( $_POST['key'] ) ) || ! 
            wp_verify_nonce( $_POST['nonce'], 'fomopress_'. $_POST['key'] .'_nonce' ) ) {
            return;
        }
        if( isset( $_POST['form_data'] ) ) {
            self::save_settings( $_POST['form_data'] );
            echo 'success';
        } else {
            echo 'error';
        }

        die;
    }
}