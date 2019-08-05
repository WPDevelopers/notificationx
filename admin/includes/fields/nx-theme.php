<?php 
    if( isset( $field['options'] ) && ! empty( $field['options'] ) ) {
        $options = $field['options'];
    }
    if( isset( $field['inner_title'] ) && ! empty( $field['inner_title'] ) ) {
        $inner_title = $field['inner_title'];
    }

    $theme_title = '';
?>


<div class="nx-theme-control-wrapper" data-name="<?php echo $name; ?>">
    <?php if( ! empty( $inner_title ) ) : ?>
        <h3><?php echo $inner_title; ?></h3>
    <?php endif; ?>
    <div class="nx-theme-field-inner">
        <?php 
            if( is_array( $options ) ) {
                foreach( $options as $opt_key => $opt_value ) {
                    $selected = ( $value == $opt_key ) ? 'checked="true"' : '';
                    ?>
                    <div class="nx-single-theme-wrapper">
                        <input <?php echo $selected; ?> class="nx-meta-radio nx-meta-field <?php echo $name; ?>" id="<?php echo $opt_key . '_' . $name; ?>" type="radio" name="<?php echo $name; ?>" value="<?php echo $opt_key; ?>">
                        <label for="<?php echo $opt_key . '_' . $name; ?>">
                            <img src="<?php echo $opt_value; ?>" alt="<?php echo $theme_title; ?>">
                        </label>
                    </div>
                    <?php
                }
            }
        ?>
    </div>

</div>