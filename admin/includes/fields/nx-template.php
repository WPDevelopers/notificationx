<?php
    $class .= ' nx-template-field';
    if( isset( $field['variables'] ) ) :
        $variables = $field['variables'];
    endif;

    $inner_fields = isset( $field['fields'] ) ? $field['fields'] : false;   
?>
<div id="<?php echo $name; ?>">
    <?php if( $inner_fields ) :
            $main_value = $value;
        ?>
        <div class="template-items">
            <?php 
                foreach( $inner_fields as $key => $inner_field ) {
                    $main_name = $name;
                    $field_id = $name . "_" . $key;
                    $name = $name . "[" . $key . "]";

                    $attrs .= ' data-subkey="' . esc_attr( $key ) . '"';

                    $file_name = $inner_field['type'];
                    if( $file_name === 'select' ) {
                        $field['options'] = isset( $inner_field['options'] ) ? $inner_field['options'] : '';
                    }
                    $value = isset( $main_value[ $key ] ) ? $main_value[ $key ] : ( isset( $inner_field[ 'default'] ) ? $inner_field[ 'default'] : '' ) ;
                    
                    if( $file_name ) {
                        include NOTIFICATIONX_ADMIN_DIR_PATH . 'includes/fields/nx-'. $file_name .'.php';
                    }
                    $name = $main_name;
                }
            ?>
        </div>
    <?php endif; ?>
    <?php if( ! $inner_fields ) : ?>
        <div contenteditable="true" class="nx-meta-template-editable">
            <?php for( $i = 0; $i < 3; $i++ ) : ?>
            <?php echo ! empty( $value[ $i ] ) ? $value[ $i ] . "\n<br>": "\n"; ?>
            <?php endfor; ?>
        </div>

        <div class="nx-meta-template-hidden-field">
            <?php for( $i = 0; $i < 3; $i++ ) : ?>
                <input type="hidden" class="<?php echo esc_attr( $class ); ?>" name="<?php echo $name; ?>[]" value="<?php echo ! empty( $value[ $i ] ) ? $value[ $i ] : ''; ?>">
            <?php endfor; ?>
        </div>
        <div class="<?php echo $name; ?>-variables">
            <span class="<?php echo $name; ?>-variable-title"><?php _e( 'Variables: ', 'notificationx' ); ?></span>
            <?php foreach ( $variables as $variable ) { ?>
                <span class="nx-variable-tag"><?php echo $variable; ?></span>
            <?php } ?>
        </div>
    <?php endif; ?>

    
</div>