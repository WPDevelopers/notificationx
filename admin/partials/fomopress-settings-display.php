<div class="fomopress-settings-wrap">
    <?php do_action( 'fomopress_settings_header' ); ?>
    <div class="fomopress-left-right-settings">
        <?php do_action( 'fomopress_before_settings_left' ); ?>
        <div class="fomopress-settings-left">
            <div class="fomopress-settings">
                <div class="fomopress-settings-menu">
                    <ul>
                        <?php
                            $i = 1;
                            foreach( $settings_args as $key => $setting ) {
                                $active = $i++ === 1 ? 'active ' : '';
                                echo '<li class="'. $active .'" data-tab="'. $key .'"><a href="#'. $key .'">'. $setting['title'] .'</a></li>';
                            }
                        ?>
                    </ul>
                </div>

                <div class="fomopress-settings-content">
                    <?php 
                        $i = 1;
                        /**
                         * Settings Tab Content Rendering
                         */
                        foreach( $settings_args as $tab_key => $setting ) {
                            $active = $i++ === 1 ? 'active ' : '';
                            $sections = isset( $setting['sections'] ) ? $setting['sections'] : [];
                            $sections = FomoPress_Helper::sorter( $sections, 'priority', 'ASC' );
                            ?>
                            <div id="fs-<?php echo esc_attr( $tab_key ); ?>" class="fomopress-settings-tab fomopress-settings-<?php echo esc_attr( $key );?> <?php echo $active; ?>">
                                <form method="post" id="fomopress-settings-<?php echo $tab_key; ?>-form" action="#">
                                    <?php 
                                        if( ! empty( $sections ) ) :
                                            /**
                                             * Every Section of a tab 
                                             * Rendering.
                                             */
                                            foreach( $sections as $sec_key => $section ) :
                                                $fields = isset( $section['fields'] ) ? $section['fields'] : [];
                                                $fields = FomoPress_Helper::sorter( $fields, 'priority', 'ASC' );
                                                ?>                                 
                                                <div 
                                                    id="fomoporess-<?php echo esc_attr( $sec_key ); ?>" 
                                                    class="fomopress-settings-section fomopress-<?php echo esc_attr( $sec_key ); ?>">
                                                    <?php 
                                                    /**
                                                     * Every Section Field Rendering
                                                     */
                                                    if( ! empty( $fields ) ) : ?>
                                                    <table>
                                                        <tbody>
                                                        <?php 
                                                            foreach( $fields as $field_key => $field ) :
                                                                FomoPress_Settings::render_field( $field_key, $field );
                                                            endforeach;
                                                        ?>
                                                        </tbody>
                                                    </table>
                                                    <?php endif; // fields rendering end ?>
                                                </div>
                                                <?php
                                            endforeach;
                                        endif; // sections rendering end

                                        // Submit Button
                                        if( isset( $setting['button_text'] ) && ! empty( $setting['button_text'] ) ) :
                                    ?>
                                    <button type="submit" class="fomopress-settings-button fomopress-submit-<?php echo $tab_key; ?>" data-nonce="<?php echo wp_create_nonce('fomopress_'. $tab_key .'_nonce'); ?>" data-key="<?php echo $tab_key; ?>" id="fomopress-submit-<?php echo $tab_key; ?>"><?php _e( $setting['button_text'], 'notificationx' ); ?></button>
                                    <?php endif; ?>
                                </form>
                            </div>
                            <?php
                        } // settings rendering loop end;
                    ?>
                </div>
            </div>
        </div>
        <?php 
            do_action( 'fomopress_after_settings_left' );
            do_action( 'fomopress_before_settings_right' );
        ?>
        <div class="fomopress-settings-right">
            <div class="fomopress-sidebar">
                <div class="fomopress-sidebar-block">
                    <div class="fomopress-admin-sidebar-logo">
                        <img src="<?php echo plugins_url( '/', __FILE__ ).'../assets/img/fomopress-logo.svg'; ?>">
                    </div>
                    <div class="fomopress-admin-sidebar-cta">
                        <?php     
                            if(class_exists('FomoPressPro')) {
                                printf( __( '<a href="%s" target="_blank">Manage License</a>', 'notificationx' ), 'https://wpdeveloper.net/account' ); 
                            }else{
                                printf( __( '<a href="%s" target="_blank">Upgrade to Pro</a>', 'notificationx' ), 'https://wpdeveloper.net/in/fomopress' );
                            }
                        ?>
                    </div>
                </div>
                <div class="fomopress-sidebar-block fomopress-license-block">
                    <?php
                        if(class_exists('FomoPressPro')) {
                        do_action( 'fomopress_licensing' );
                    }
                ?>
                </div>
            </div>
        </div>
        <?php do_action( 'fomopress_after_settings_right' ); ?>
    </div>
</div>
