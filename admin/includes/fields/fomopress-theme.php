<?php 
    if( isset( $field['options'] ) && ! empty( $field['options'] ) ) {
        $options = $field['options'];
    }

    $theme_title = '';
?>


<div class="fomopress-theme-field-wrapper" data-name="<?php echo $name; ?>">
    <input id="<?php echo $name; ?>" type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>">
    <div class="fomopress-theme-field-inner">
        <?php 
            if( is_array( $options ) ) {
                
                foreach( $options as $opt_key => $opt_value ) {
                    $selected = ( $value == $opt_key ) ? 'fomopress-theme-selected' : '';
                    ?>
                    <div class="fomopress-single-theme-wrapper <?php echo $selected; ?>">
                        <img data-theme="<?php echo $opt_key; ?>" src="<?php echo $opt_value; ?>" alt="<?php echo $theme_title; ?>">
                    </div>
                    <?php
                }
            }
        ?>
    </div>

</div>