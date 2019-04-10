<?php
    $class .= ' nx-template-field';
    if( isset( $field['variables'] ) ) :
        $variables = $field['variables'];
    endif;
?>
<div id="<?php echo $name; ?>">

    <div contenteditable="true" class="nx-meta-template-editable">
        <?php for( $i = 0; $i < 3; $i++ ) : ?>
        <?php echo ! empty( $value[ $i ] ) ? $value[ $i ] . "\n<br>": "\n"; ?>
        <?php endfor; ?>
    </div>

    <div class="nx-meta-template-hidden-field">
        <?php for( $i = 0; $i < 3; $i++ ) : ?>
            <input type="hidden" class="<?php echo esc_attr( $class ); ?>" name="<?php echo $name; ?>[]" value="<?php echo ! empty( $value[ $i ] ) ? $value[ $i ] : ''; ?>">
        <?php endfor; ?>
    </div>
    <div class="<?php echo $name; ?>-variables">
        <span class="<?php echo $name; ?>-variable-title"><?php _e( 'Variables: ', 'notificationx' ); ?></span>
        <?php foreach ( $variables as $variable ) { ?>
            <span class="nx-variable-tag"><?php echo $variable; ?></span>
        <?php } ?>
    </div>
</div>