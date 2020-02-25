<div class="nx-settings-form-wrapper">
    <form method="post" id="nx-settings-form" action="#">
    <?php 
        $i = 1;
        /**
         * Settings Tab Content Rendering
         */
        foreach( $settings_args as $tab_key => $setting ) {
            $active = $i++ === 1 ? 'active ' : '';
            $sections = isset( $setting['sections'] ) ? $setting['sections'] : [];
            $sections = NotificationX_Helper::sorter( $sections, 'priority', 'ASC' );
            $is_form = isset( $setting['form'] ) ? $setting['form'] : false;
        ?>
        <div 
            id="nx-<?php echo esc_attr( $tab_key ); ?>" 
            class="nx-settings-tab nx-settings-<?php echo esc_attr( $key );?> <?php echo $active; ?>">
            <?php 
                    if( isset( $setting['views'] ) && ! empty( $setting['views'] ) ) {
                        call_user_func_array( $setting['views'], isset( $setting['sections'] ) ? array( 'sections' => $setting['sections'] ) : [] );
                    }
                    if( ! empty( $sections ) && ! isset( $setting['views'] ) ) :
                        /**
                         * Every Section of a tab 
                         * Rendering.
                         */
                        foreach( $sections as $sec_key => $section ) :
                            $fields = isset( $section['fields'] ) ? $section['fields'] : [];
                            $fields = NotificationX_Helper::sorter( $fields, 'priority', 'ASC' );
                            ?>                                 
                            <div 
                                id="nx-meta-section-<?php echo esc_attr( $sec_key ); ?>" 
                                class="nx-settings-section nx-<?php echo esc_attr( $sec_key ); ?>">
                                <?php 
                                if( isset( $section['title'] ) ) {
                                    echo '<h2 data-text="'. $section['title'] .'">'. $section['title'] .'</h2>';
                                }
                                if( isset( $section['views'] ) && ! empty( $section['views'] ) ) {
                                    call_user_func_array( $section['views'], 
                                        isset( $section['fields'] ) ? array( 'fields' => $section['fields'] ) : [] );
                                }
                                /**
                                 * Every Section Field Rendering
                                 */
                                if( ! empty( $fields ) && ! isset( $section['views'] ) ) : ?>
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
                <button type="submit" class="btn-settings nx-settings-button nx-submit-<?php echo $tab_key; ?>" data-nonce="<?php echo wp_create_nonce('nx_'. $tab_key .'_nonce'); ?>" data-key="<?php echo $tab_key; ?>" id="nx-submit-<?php echo $tab_key; ?>"><?php _e( $setting['button_text'], 'notificationx' ); ?></button>
            <?php endif; ?>
        </div>
    <?php } ?>
    </form>
</div>