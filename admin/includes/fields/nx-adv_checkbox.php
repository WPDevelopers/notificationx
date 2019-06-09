<?php

    if ( absint( $value ) == 1 ) {
        $attrs .= ' checked="checked"';
    }

    if( isset( $field['disable'] ) && $field['disable'] === true ) {
        $attrs .= ' disabled';
    }

    $button_text = isset( $field['button_text'] ) ? $field['button_text'] : __( 'Advanced Design', 'notificationx' );

?>

<div class="nx-adv-checkbox-wrap">
    <input class="<?php echo esc_attr( $class ); ?>" type="checkbox" id="<?php echo $field_id; ?>" name="<?php echo $name; ?>" value="1" <?php echo $attrs; ?>/>
    <label for="<?php echo $name; ?>" class="nx-adv-checkbox-label">
        <?php echo $button_text; ?>
    </label>
</div>