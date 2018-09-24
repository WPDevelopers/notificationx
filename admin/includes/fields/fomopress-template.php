<?php
    $class .= ' fomopress-template-field';
    if( isset( $field['variables'] ) ) :
        $variables = $field['variables'];
    endif;
?>
<div id="<?php echo $name; ?>">
    <?php for( $i = 0; $i < 3; $i++ ) : ?>
        <div>
            <input type="text" class="<?php echo esc_attr( $class ); ?>" name="<?php echo $name; ?>[]" value="<?php echo $value[ $i ]; ?>">
        </div>
    <?php endfor; ?>
    <div class="<?php echo $name; ?>-variables">
        <span class="<?php echo $name; ?>-variable-title"><?php _e( 'Variables: ', 'fomopress' ); ?></span>
        <?php foreach ( $variables as $variable ) { ?>
            <span class="fomopress-variable-tag"><?php echo $variable; ?></span>
        <?php } ?>
    </div>
</div>