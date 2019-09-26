<?php
    $limit = isset( $field['max'] ) ? $field['max'] : '';
    $min = isset($field['min']) ? $field['min'] : '';
    if( $limit ) {
        $attrs .= ' max="'. $limit .'"';
    }
    if($min){
        $attrs .= ' min="'. $min .'"';
    }
?>
<input class="<?php echo esc_attr( $class ); ?>" id="<?php echo $field_id; ?>" type="number" name="<?php echo $name; ?>" value="<?php echo $value; ?>" <?php echo $attrs; ?>>