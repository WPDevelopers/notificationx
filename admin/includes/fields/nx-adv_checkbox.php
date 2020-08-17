<?php

    if ( absint( $value ) == 1 ) {
        $attrs .= ' checked="checked"';
    }

    if( isset( $field['disable'] ) && $field['disable'] === true ) {
        $attrs .= ' disabled';
    }

    $button_text = isset( $field['button_text'] ) ? $field['button_text'] : __( 'Advanced Design', 'notificationx' );
    $side = isset( $field['side'] ) ? $field['side'] : 'left';

    $swal = isset( $field['swal'] ) ? 'data-swal="' . $field['swal'] . '"': '';
?>
<div class="nx-adv-checkbox-wrap adv-btn-<?php echo $side; ?>">
    <input class="<?php echo esc_attr( $class ); ?>" type="checkbox" id="<?php echo $field_id; ?>" name="<?php echo $name; ?>" value="1" <?php echo $attrs; ?>/>
    <label
        <?php echo $swal; ?> for="<?php echo $name; ?>" class="nx-adv-checkbox-label">
        <?php echo $button_text; ?>
    </label>
</div>