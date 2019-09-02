<?php 
    $class .= ' nx-select';

    $multiple = isset( $field['multiple'] ) ? 'multiple' : '';

    if( $multiple ) {
        $name .= "[]";
    }

    if( isset( $field['disable'] ) && $field['disable'] == true ) {
        $attrs .= ' disabled';
    }
?>
<select class="<?php echo esc_attr( $class ); ?>" <?php echo $multiple; ?> name="<?php echo $name; ?>" id="<?php echo $field_id; ?>" <?php echo $attrs; ?>>
    <?php 
        foreach( $field['options'] as $opt_id => $option ) {
            if( is_array( $option ) ) {
                echo '<optgroup label="'. $option['label'] .'">';
                foreach( $option['options'] as $i_opt_id => $i_option ) {
                    if( is_array( $value ) ) {
                        $selected = in_array( $i_opt_id, $value ) ? 'selected="true"' : '';
                    } else {
                        $selected = ( $value == $i_opt_id ) ? 'selected="true"' : '';
                    }
                    echo '<option value="'. $i_opt_id .'" '. $selected .'>'. $i_option .'</option>';
                }
                echo '</optgroup>';
            } else {
                if( is_array( $value ) ) {
                    $selected = in_array( $opt_id, $value ) ? 'selected="true"' : '';
                } else {
                    $selected = ( $value == $opt_id ) ? 'selected="true"' : '';
                }
                echo '<option value="'. $opt_id .'" '. $selected .'>'. $option .'</option>';
            }
        }
    ?>
</select>