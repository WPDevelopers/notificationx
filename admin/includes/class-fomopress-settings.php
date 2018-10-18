<?php 

class FomoPress_Settings {
    public static function init(){
        add_action( 'fomopress_before_settings_form', array( __CLASS__, 'notice_template' ), 9 );
        add_action( 'fomopress_settings_header', array( __CLASS__, 'header_template' ), 10 );
    }

    public function notice_template(){
        ?>
            <div class="fomopress-settings-notice"></div>
        <?php
    }

    public function header_template(){
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

}
FomoPress_Settings::init();