<?php 
    $current_tab = get_post_meta( $idd, '_fomopress_current_tab', true );
    if( ! $current_tab ) {
        $current_tab = 'source_tab';
    }

    $totaltabs = count( $tabs );
    $position = intval( array_search( $current_tab, array_keys( $tabs) ) + 1 );
?>

<div class="fomopress-builder-wrapper">

    <div class="fomopress-builder-header">
        <h1><?php _e( 'Quick Notification Builder', 'fomopress' ); ?></h1>
    </div>

    <div class="fomopress-builder-menu">
    <?php if( ! empty( $tabs ) ) : ?>
        <ul>
            <?php 
                $tid = 1;
                foreach( $tabs as $id => $tab ) {
                    $active = $current_tab === $id ? ' active' : '';
                    $class = isset( $tab['icon'] ) ? ' fomopress-has-icon' : '';
                    $class .= $active;
                    if( $position > $tid ){
                        $class .= ' fp-complete';
                    }
                    if( in_array( $id, FomoPress_Helper::not_in_builder( 'tabs' ) ) ) {
                        $tid++;
                        continue;
                    }
                    ?>
                        <li data-tabid="<?php echo $tid++; ?>" class="<?php echo $class; ?>" data-tab="<?php echo $id; ?>">
                            <?php if( isset( $tab['icon'] ) ) : ?>
                                <span class="fomopress-menu-icon">
                                    <img src="<?php echo FOMOPRESS_ADMIN_URL . 'assets/img/icons/' . $tab['icon']; ?>" alt="<?php echo $tab['title']; ?>">
                                </span>
                            <?php endif; ?>
                            <span class="fomopress-menu-title"><?php echo $tab['title']; ?></span>
                        </li>
                    <?php
                }
            ?>
        </ul>
    <?php endif; ?>
    </div>

    <div class="fomopress-builder-content-wrapper">
        <form method="post" id="fomopress-settings-form" action="<?php echo self::get_form_action( '', true ); ?>">
            <input id="fomopress_current_tab" type="hidden" name="fomopress_current_tab" value="<?php echo $current_tab; ?>">
            <?php 
                wp_nonce_field( $builder_args['id'], $builder_args['id'] . '_nonce' );
                foreach( $tabs as $id => $tab  ){
                    if( in_array( $id, FomoPress_Helper::not_in_builder( 'tabs' ) ) ) {
                        echo '<div class="fomopress-builder-hidden">';
                    }
                    $active = $current_tab === $id ? ' active ' : '';
                    $sections = FomoPress_Helper::sorter( $tab['sections'], 'priority', 'ASC' );
                    ?>
                    <div id="fomopress-<?php echo $id ?>" class="fomopress-builder-content <?php echo $active; ?>">
                    <?php 
                        foreach( $sections as $sec_id => $section ) {
                            if( in_array( $sec_id, FomoPress_Helper::not_in_builder( 'sections' ) ) ) {
                                echo '<div class="fomopress-builder-hidden">';
                            }
                            $fields = FomoPress_Helper::sorter( $section['fields'], 'priority', 'ASC' );
                            if( ! empty( $fields ) )  :
                        ?>
                            <div id="fomopress-meta-section-<?php echo $sec_id; ?>" class="fomopress-metabox-section">
                                <h2 class="fomopress-metabox-section-title">
                                    <?php echo $section['title']; ?>    
                                </h2>
                                <table>
                                    <?php 
                                        foreach( $fields as $key => $field ) {
                                            if( in_array( $key, FomoPress_Helper::not_in_builder( ) ) ) continue;
                                            FomoPress_MetaBox::render_meta_field( $key, $field, '', $idd );
                                        }
                                    ?>
                                </table>
                            </div>
                        <?php
                                if( in_array( $sec_id, FomoPress_Helper::not_in_builder( 'sections' ) ) ) {
                                    echo '</div>';
                                }
                            endif;
                            if( ! empty( $fields ) )  :
                                foreach( $fields as $key => $field ) {
                                    $name      = self::$prefix . $key;
                                    if( in_array( $key, FomoPress_Helper::not_in_builder( ) ) ) {
                                        if( 'template' === $field['type'] ) {
                                            $default = isset( $field['defaults'] ) ? $field['defaults'] : [];
                                        } else {
                                            $default = isset( $field['default'] ) ? $field['default'] : '';
                                        }

                                        if( is_array( $default ) ) :
                                            foreach( $default as $value ) {
                                                echo '<input type="hidden" name="'. $name .'[]" value="'. $value .'">';
                                            }
                                        else :
                                            echo '<input type="hidden" name="'. $name .'" value="'. $default .'">';
                                        endif;
                                    } else  {
                                        continue;
                                    }
                                }
                            endif;
                        }
                    ?>
                    </div>
                    <?php
                    if( in_array( $id, FomoPress_Helper::not_in_builder( 'tabs' ) ) ) {
                        echo '</div>';
                    }
                }
            ?>
            <?php if( $idd ) :?>
                <input name="fomopress_edit_notification_id" type="hidden" value="<?php echo $idd; ?>">
                <input class="quick-builder-submit-btn" name="fomopress_builder_edit_submit" type="submit" value="Edit">
            <?php else : ?>
                <input class="quick-builder-submit-btn" name="fomopress_builder_add_submit" type="submit" value="Add">
            <?php endif; ?>
        </form>
    </div>

</div>