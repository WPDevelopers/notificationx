<?php
$attrs = $wrapper_attrs = $class = '';
$pos_class = 'fomopress-position-top';

if( $settings->link_open ) {
    $attrs .= ' target="_blank"';
}

if( $settings->initial_delay ) {
    $wrapper_attrs .= ' data-initial_delay="'. $settings->initial_delay .'"';
}

if( $settings->close_button ) {
    $wrapper_attrs .= ' data-close_button="'. $settings->close_button .'"';
}

if( $settings->hide_after ) {
    $wrapper_attrs .= ' data-hide_after="'. $settings->hide_after .'"';
}

if( $settings->auto_hide ) {
    $wrapper_attrs .= ' data-auto_hide="'. $settings->auto_hide .'"';
}

if( $settings->id ) {
    $wrapper_attrs .= ' data-press_id="'. $settings->id .'"';
}

if( 'bottom' == $settings->pressbar_position ) {
    $pos_class = ' fomopress-position-bottom';
}

if( is_admin_bar_showing() ) {
    $class .= 'fomopress-admin';
}

?>

<div 
    id="fomopress-bar-<?php echo $settings->id; ?>"
    class="fomopress-press-bar fomopress-bar-<?php echo $settings->id; ?> <?php echo esc_attr( $pos_class ); ?> <?php echo esc_attr( $class ); ?>" <?php echo $wrapper_attrs; ?>>
    <div class="fomopress-bar-inner">
        <div class="fomopress-press-bar-content">
            <?php 
                echo esc_html_e( $settings->press_content, 'fomopress' ); 
                if( $settings->button_url != '' ) :
            ?>
                <a href="<?php echo esc_url( $settings->button_url ); ?>" <?php echo $attrs; ?>>
                    <?php echo esc_html_e( $settings->button_text, 'fomopress' ); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php if( $settings->close_button ) : ?>
            <p class="fomopress-close" title="Close">x</p>
        <?php endif; ?>
    </div>
</div>