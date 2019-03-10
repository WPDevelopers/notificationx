<?php 
    $class .= ' nx-select';
?>
<select class="<?php echo esc_attr( $class ); ?>" name="<?php echo $name; ?>" id="<?php echo $name; ?>" <?php echo $attrs; ?>>
    <?php 
        foreach( $field['options'] as $opt_id => $option ) {
            $selected = ( $value == $opt_id ) ? 'selected="true"' : '';
            echo '<option value="'. $opt_id .'" '. $selected .'>'. $option .'</option>';

        }
    ?>
</select>