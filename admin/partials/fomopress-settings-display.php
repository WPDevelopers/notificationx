<div class="fomopress-settings-wrap">
    <?php
        do_action( 'fomopress_before_settings_form' );
        if( ! empty( $settings_args ) ) : ?>
        <form method="post" id="fomopress-settings-form" action="<?php echo FomoPress_Admin::get_form_action(); ?>">
            <?php do_action( 'fomopress_settings_header' ); ?>
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
                        foreach( $settings_args as $key => $setting ) {
                            $active = $i++ === 1 ? 'active ' : '';
                            $sections = isset( $setting['sections'] ) ? $setting['sections'] : [];
                            $sections = FomoPress_Helper::sorter( $sections, 'priority', 'ASC' );
                            ?>
                            <div id="<?php echo esc_attr( $key ); ?>" class="fomopress-settings-tab fomopress-settings-<?php echo esc_attr( $key );?> <?php echo $active; ?>">
                                <?php 
                                    if( ! empty( $sections ) ) :
                                        /**
                                         * Every Section of a tab 
                                         * Rendering.
                                         */
                                        foreach( $sections as $key => $section ) :
                                            $fields = isset( $section['fields'] ) ? $section['fields'] : [];
                                            $fields = FomoPress_Helper::sorter( $fields, 'priority', 'ASC' );
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
                                                            FomoPress_Settings::render_field( $key, $field );
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
            </div>
        </form>
    <?php 
        endif; 
        do_action( 'fomopress_after_settings_form' );
    ?>
</div>
