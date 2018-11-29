<?php

    if ( absint( $value ) == 1 ) {
        $attrs .= ' checked="checked"';
    }

    if( isset( $field['disable'] ) && $field['disable'] === true ) {
        $attrs .= ' disabled';
    }

?>

<div class="fomopress-adv-checkbox-wrap">
    <input class="<?php echo esc_attr( $class ); ?>" type="checkbox" id="<?php echo $name; ?>" name="<?php echo $name; ?>" value="1" <?php echo $attrs; ?>/>
    <label for="<?php echo $name; ?>" class="fomopress-adv-checkbox-label">
        <?php _e( 'Advanced Design', 'fomopress' ); ?>
    </label>
</div>