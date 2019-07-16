<div class="nx-settings-wrap">
    <?php do_action( 'notificationx_settings_header' ); ?>
    <div class="nx-left-right-settings">
        <?php do_action( 'nx_before_settings_left' ); ?>
        <div class="nx-settings-left">
            <div class="nx-settings">
                <div class="nx-settings-menu">
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

                <div class="nx-settings-content">
                    <?php 
                        $i = 1;
                        /**
                         * Settings Tab Content Rendering
                         */
                        foreach( $settings_args as $tab_key => $setting ) {
                            $active = $i++ === 1 ? 'active ' : '';
                            $sections = isset( $setting['sections'] ) ? $setting['sections'] : [];
                            $sections = NotificationX_Helper::sorter( $sections, 'priority', 'ASC' );
                            ?>
                            <div id="nx-<?php echo esc_attr( $tab_key ); ?>" class="nx-settings-tab nx-settings-<?php echo esc_attr( $key );?> <?php echo $active; ?>">
                                <form method="post" id="nx-settings-<?php echo $tab_key; ?>-form" action="#">
                                    <?php 
                                        if( ! empty( $sections ) ) :
                                            /**
                                             * Every Section of a tab 
                                             * Rendering.
                                             */
                                            foreach( $sections as $sec_key => $section ) :
                                                $fields = isset( $section['fields'] ) ? $section['fields'] : [];
                                                $fields = NotificationX_Helper::sorter( $fields, 'priority', 'ASC' );
                                                ?>                                 
                                                <div 
                                                    id="nx-settings-<?php echo esc_attr( $sec_key ); ?>" 
                                                    class="nx-settings-section nx-<?php echo esc_attr( $sec_key ); ?>">
                                                    <?php 
                                                    /**
                                                     * Every Section Field Rendering
                                                     */
                                                    if( ! empty( $fields ) ) : ?>
                                                    <table>
                                                        <tbody>
                                                        <?php 
                                                            foreach( $fields as $field_key => $field ) :
                                                                NotificationX_Settings::render_field( $field_key, $field );
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
                                    <button type="submit" class="nx-settings-button nx-submit-<?php echo $tab_key; ?>" data-nonce="<?php echo wp_create_nonce('nx_'. $tab_key .'_nonce'); ?>" data-key="<?php echo $tab_key; ?>" id="nx-submit-<?php echo $tab_key; ?>"><?php _e( $setting['button_text'], 'notificationx' ); ?></button>
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
            do_action( 'nx_after_settings_left' );
            do_action( 'nx_before_settings_right' );
        ?>
        <div class="nx-settings-right">
            <div class="nx-sidebar">
                <div class="nx-sidebar-block">
                    <div class="nx-admin-sidebar-logo">
                        <img src="<?php echo plugins_url( '/', __FILE__ ).'../assets/img/nx-logo.png'; ?>">
                    </div>
                    <div class="nx-admin-sidebar-cta">
                        <?php     
                            if(class_exists('NotificationXPro')) {
                                printf( __( '<a href="%s" target="_blank">Manage License</a>', 'notificationx' ), 'https://wpdeveloper.net/account' ); 
                            }else{
                                printf( __( '<a href="%s" target="_blank">Upgrade to Pro</a>', 'notificationx' ), 'https://wpdeveloper.net/in/notificationx-pro' );
                            }
                        ?>
                    </div>
                </div>
                <div class="nx-sidebar-block nx-license-block">
                    <?php
                        if(class_exists('NotificationXPro')) {
                        do_action( 'nx_licensing' );
                    }
                ?>
                </div>
            </div>
        </div>
        <?php do_action( 'nx_after_settings_right' ); ?>
    </div>
</div>
