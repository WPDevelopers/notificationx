<div class="fomopress-settings-wrap">
    <div class="fomopress-settings-notice"></div>
    <div class="fomopress-settings-header">
        <h2><?php echo _e( 'FomoPress', 'fomopress' ); ?></h2>
    </div>
    <?php if( ! empty( $settings_args ) ) : ?>
        <form method="post" id="fomopress-settings-form" action="<?php echo self::get_form_action(); ?>">
            <div class="fomopress-settings">
                <div class="fomopress-settings-menu">
                    <ul>
                        <?php
                            $i = 1;
                            foreach( $settings_args as $key => $setting ) {
                                $active = $i++ === 1 ? 'active ' : '';
                                echo '<li class="'. $active .'" data-tab="'. $key .'">'. $setting['title'] .'</li>';
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
                        foreach( $settings_args as $key => $setting ) {
                            $active = $i++ === 1 ? 'active ' : '';
                            $sections = isset( $setting['sections'] ) ? $setting['sections'] : [];
                            ?>
                            <div id="<?php echo esc_attr( $key ); ?>" class="fomopress-settings fomopress-settings-<?php echo esc_attr( $key );?> <?php echo $active; ?>">
                                <?php 
                                    if( ! empty( $sections ) ) :
                                        /**
                                         * Every Section of a tab 
                                         * Rendering.
                                         */
                                        foreach( $sections as $key => $section ) :
                                            $fields = isset( $section['fields'] ) ? $section['fields'] : [];
                                            ?>                                 
                                            <div 
                                                id="fomoporess-<?php echo esc_attr( $key ); ?>" 
                                                class="fomopress-settings-section fomopress-<?php echo esc_attr( $key ); ?>">
                                                <?php 
                                                /**
                                                 * Every Section Field Rendering
                                                 */
                                                if( ! empty( $fields ) ) : ?>
                                                <table>
                                                    <tbody>
                                                    <?php 
                                                        foreach( $fields as $key => $field ) :
                                                            if( empty( $field['label'] ) ) :
                                                                echo '<td colspan="2">';
                                                            else : 
                                                                echo '<th>';
                                                                    echo $field['label'];
                                                                echo '</th>';
                                                                echo '<td>';
                                                            endif;
                                                                self::render_field( $key, $field );
                                                            echo '</td>';
                                                        endforeach;
                                                    ?>
                                                    </tbody>
                                                </table>
                                                <?php endif; // fields rendering end ?>
                                            </div>
                                            <?php
                                        endforeach;
                                    endif; // sections rendering end
                                ?>
                            </div>
                            <?php
                        } // settings rendering loop end;
                    ?>
                </div>
                <?php wp_nonce_field( 'fomopress_settings', 'fomopress_settings_nonce' ); ?>
                <input type="submit" class="fomopress-settings-button" name="fomopress_settings_submit" id="fomopress-submit" value="<?php esc_html_e('Save Changes', 'fomopress'); ?>" />
            </div>
        </form>
    <?php endif; ?>
</div>
