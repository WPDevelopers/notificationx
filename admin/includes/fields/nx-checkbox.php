<?php

    if ( absint( $value ) == 1 ) {
        if( isset( $field['is_pro'] ) && ! $field['is_pro'] ) {
            $attrs .= ' checked="checked"';
        }
        if( ! isset( $field['is_pro'] ) ) {
            $attrs .= ' checked="checked"';
        }
    }

    if( isset( $field['disable'] ) && $field['disable'] === true ) {
        $attrs .= ' disabled';
    }

    if( isset( $field['is_pro'] ) && $field['is_pro'] ) {
        $attrs .= ' data-swal="true"';
    }
?>

<input class="<?php echo esc_attr( $class ); ?>" type="checkbox" id="<?php echo $field_id; ?>" name="<?php echo $name; ?>" value="1" <?php echo $attrs; ?>/>