<?php
    $readonly = isset( $field['readonly'] ) && $field['readonly'] == true ? 'readonly' : '';
?>

<input <?php echo $readonly; ?> class="<?php echo esc_attr( $class ); ?>" id="<?php echo $field_id; ?>" type="text" name="<?php echo $name; ?>" value="<?php echo $value; ?>" <?php echo $attrs; ?>>