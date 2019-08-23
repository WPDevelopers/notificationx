<?php 
    $options = '';
    if( isset( $field['options'] ) && ! empty( $field['options'] ) ) {
        $options = $field['options'];
    }
    if( isset( $field['inner_title'] ) && ! empty( $field['inner_title'] ) ) {
        $inner_title = $field['inner_title'];
    }
    
    $theme_title = '';
    $type_content = isset( $field['type_content'] ) ? $field['type_content'] : false;
?>


<div class="nx-theme-control-wrapper" data-name="<?php echo $name; ?>">
    <?php if( ! empty( $inner_title ) ) : ?>
        <h3><?php echo $inner_title; ?></h3>
    <?php endif; ?>
    <div class="nx-theme-field-inner">
        <?php 
            if( is_array( $options ) ) {
                $is_pro = false;
                foreach( $options as $opt_key => $opt_value ) {
                    $selected = ( $value == $opt_key ) ? 'checked="true"' : '';
                    if( is_array( $opt_value ) ) {
                        $is_pro = isset( $opt_value['is_pro'] ) ? $opt_value['is_pro'] : $is_pro;
                        $is_version = 'Pro';
                        if( isset( $opt_value['version'] ) && ! $is_pro && defined( 'NOTIFICATIONX_PRO_VERSION' ) ) {
                            $is_pro = version_compare( NOTIFICATIONX_PRO_VERSION, $opt_value['version'], '<=' );
                            $is_version = '>' . $opt_value['version'];
                        }
                    
                        $opt_value = isset( $opt_value['source'] ) ? $opt_value['source'] : '';

                    }
                    ?>
                    <div class="nx-single-theme-wrapper <?php echo $is_pro ? 'nx-radio-pro' : ''; ?>">
                        <?php if( $is_pro ) : ?><sup class="pro-label"><?php _e( $is_version, 'notificationx' ); ?></sup><?php endif; ?>
                        <input <?php echo $is_pro ? 'disabled' : ''; ?> <?php echo $selected; ?> class="nx-meta-radio nx-meta-field <?php echo $name; ?>" id="<?php echo $opt_key . '_' . $name; ?>" type="radio" name="<?php echo $name; ?>" value="<?php echo $opt_key; ?>">
                        <label for="<?php echo $opt_key . '_' . $name; ?>">
                            <?php 
                                if( $type_content != 'text' ) {
                                    echo '<img src="'. $opt_value .'" alt="'. $theme_title .'">';
                                } else {
                                    echo $opt_value;
                                }
                            ?>
                        </label>
                    </div>
                    <?php
                    $is_pro = false;
                }
            }
        ?>
    </div>

</div>