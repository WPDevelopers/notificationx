<?php 
    if( isset( $field['options'] ) && ! empty( $field['options'] ) ) {
        $options = $field['options'];
    }

    $theme_title = '';
?>


<div class="nx-theme-control-wrapper" data-name="<?php echo $name; ?>">
    <input id="<?php echo $name; ?>" type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>">
    <div class="nx-theme-field-inner">
        <?php 
            if( is_array( $options ) ) {
                
                foreach( $options as $opt_key => $opt_value ) {
                    $selected = ( $value == $opt_key ) ? 'nx-theme-selected' : '';
                    ?>
                    <div class="nx-single-theme-wrapper nx-meta-field <?php echo $selected; ?>" <?php echo $attrs; ?>>
                        <img data-theme="<?php echo $opt_key; ?>" src="<?php echo $opt_value; ?>" alt="<?php echo $theme_title; ?>">
                    </div>
                    <?php
                }
            }
        ?>
    </div>

</div>