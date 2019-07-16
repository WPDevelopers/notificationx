<?php
    $limit = isset( $field['max'] ) ? $field['max'] : '';
    if( $limit ) {
        $attrs .= ' max="'. $limit .'"';
    }
?>
<input class="<?php echo esc_attr( $class ); ?>" id="<?php echo $field_id; ?>" type="number" name="<?php echo $name; ?>" value="<?php echo $value; ?>" <?php echo $attrs; ?>>