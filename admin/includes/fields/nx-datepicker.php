<?php
    if( isset( $field['only'] ) && $field['only'] === 'timepicker' ) {
        $attrs .= 'data-only="timepicker"';
    }
    $group_value = $value;
?>

<div class="nx-countdown-timepicker">
<?php
    if( ! isset( $field['multiple'] ) ) {
        ?>
        <div class="nx-countdown-datepicker">
        <input class="<?php echo esc_attr( $class ); ?>" id="<?php echo $field_id; ?>" type="text" name="<?php echo $name; ?>" value="<?php echo $value; ?>" <?php echo $attrs; ?>>
        </div>
        <?php
    } else {
        if( isset( $field['fields'] ) && is_array( $field['fields'] ) ) {
            foreach( $field['fields'] as $iField_id => $iField ) {
                $iValue = '';
                if( is_array( $group_value ) && isset( $group_value[ $iField_id ] ) ) {
                    $iValue = $group_value[ $iField_id ];
                }
                if( empty( $iValue ) ) {
                    $iValue = $iField['default'];
                }
                ?>

                    <input class="<?php echo esc_attr( $class ); ?>" id="<?php echo $field_id; ?>" type="number" name="<?php echo $name; ?>[<?php echo $iField_id; ?>]" value="<?php echo $iValue; ?>" <?php echo $attrs; ?>>
                <?php
            }
        }
    }
?>
</div>