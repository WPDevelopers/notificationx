<?php

    if ( absint( $value ) == 1 ) {
        $attrs .= ' checked="checked"';
    }

?>

<input class="<?php echo esc_attr( $class ); ?>" type="checkbox" id="<?php echo $name; ?>" name="<?php echo $name; ?>" value="1" <?php echo $attrs; ?>/>