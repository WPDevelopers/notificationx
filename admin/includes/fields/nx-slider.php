<?php
    $readonly = isset( $field['readonly'] ) && $field['readonly'] == true ? 'readonly' : '';
?>

<div class="nx-slider-wrap">
    <input <?php echo $readonly; ?> class="<?php echo esc_attr( $class ); ?>" id="<?php echo $field_id; ?>" type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>" <?php echo $attrs; ?>>
    <div class="ui-slider-handle"></div>
</div>