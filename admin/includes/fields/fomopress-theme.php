<?php 
    if( isset( $field['options'] ) && ! empty( $field['options'] ) ) {
        $options = $field['options'];
    }

    $theme_title = '';
?>


<div class="fomopress-theme-field-wrapper">
    <input type="text" name="<?php echo $name; ?>" value="<?php echo $value; ?>">
    <?php 
        if( isset( $field['title'] ) ) : 
            $theme_title = $field['title'];
    ?>
        <h2><?php echo $theme_title; ?></h2>
    <?php endif; ?>
    <div class="fomopress-theme-field-inner">
        <?php 
            if( is_array( $options ) ) {
                $selected = '';
                foreach( $options as $opt_key => $opt_value ) {
                    if( $value == $opt_key ) {
                        $selected = 'fomopress-theme-selected';
                    }
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