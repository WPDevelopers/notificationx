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

if( $settings->enable_countdown ) {
    $countdown = [];
    if( $settings->countdown_time ) {
        foreach( $settings->countdown_time as $key => $time ) {
            $time = empty( $time ) ? 0 : $time;
            $countdown[ $key ] = $time < 10 ? '0' . $time : $time;
        }
    }
}
?>
<div 
    id="fomopress-bar-<?php echo $settings->id; ?>"
    class="fomopress-press-bar fomopress-bar-<?php echo $settings->id; ?> <?php echo esc_attr( $pos_class ); ?> <?php echo esc_attr( $class ); ?>" <?php echo $wrapper_attrs; ?>>
    <div class="fomopress-bar-inner">
        <div class="fomopress-press-bar-content">
            <?php if( $settings->enable_countdown ) : ?>
                <div class="fomopress-countdown-wrapper">
                    <?php if( $settings->countdown_text ) : ?>
                        <div class="fomopress-countdown-text">
                            <?php echo esc_html__( $settings->countdown_text, 'fomopress' ); ?>
                        </div>
                    <?php endif; ?>             
                    <div class="fomopress-countdown" data-countdown="<?php echo esc_attr( json_encode( $countdown ) ); ?>">
                        <div class="fomopress-time-section">
                            <span class="fomopress-days">00</span>
                            <span class="fomopress-countdown-time-text"><?php esc_html_e('Days', 'fomopress'); ?></span>
                        </div>
                        <div class="fomopress-time-section">
                            <span class="fomopress-hours">00</span>
                            <span class="fomopress-countdown-time-text"><?php esc_html_e('Hrs', 'fomopress'); ?></span>
                        </div>
                        <div class="fomopress-time-section">
                            <span class="fomopress-minutes">00</span>
                            <span class="fomopress-countdown-time-text"><?php esc_html_e('Mins', 'fomopress'); ?></span>
                        </div>
                        <div class="fomopress-time-section">
                            <span class="fomopress-seconds">00</span>
                            <span class="fomopress-countdown-time-text"><?php esc_html_e('Secs', 'fomopress'); ?></span>
                        </div>
                        <span class="fomopress-expired-text"><?php esc_html_e('Expired!', 'fomopress'); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            <div class="fomopress-inner-content-wrapper">
                <div class="fomopress-bar-content"><?php echo $settings->press_content; ?></div>
                <?php if( $settings->button_url != '' ) : ?>
                    <a class="fomopress-bar-button" href="<?php echo esc_url( $settings->button_url ); ?>" <?php echo $attrs; ?>>
                        <?php echo esc_html_e( $settings->button_text, 'fomopress' ); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php if( $settings->close_button ) : ?>
            <p class="fomopress-close" title="Close">x</p>
        <?php endif; ?>
    </div>
</div>