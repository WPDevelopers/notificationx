<?php 
    $class .= ' fomopress-' . $key;
    
    // if( isset( $field['disable'] ) && $field['disable'] === true ) {
    //     $attrs .= ' disabled';
    // }
?>
<input class="<?php echo esc_attr( $class ); ?>" id="<?php echo $name; ?>" type="text" name="<?php echo $name; ?>" value="<?php echo $value; ?>" <?php echo $attrs; ?>>