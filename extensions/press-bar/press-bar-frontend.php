<?php
$attrs = $wrapper_attrs = $class = '';
$pos_class = 'nx-position-top';

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
    $pos_class = '';
    $pos_class = ' nx-position-bottom';
}

if( is_admin_bar_showing() ) {
    $class .= 'nx-admin';
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

if( $settings->bar_advance_edit ) {
    $class = ' nx-customize-style-' . $settings->id;
}

?>
<div 
    id="nx-bar-<?php echo $settings->id; ?>"
    class="nx-bar <?php echo $settings->bar_theme; ?> nx-bar-<?php echo $settings->id; ?> <?php echo esc_attr( $pos_class ); ?> <?php echo esc_attr( $class ); ?>" <?php echo $wrapper_attrs; ?>>
    <div class="nx-bar-inner">
        <div class="nx-bar-content-wrap">
            <?php if( $settings->enable_countdown ) : ?>
                <div class="nx-countdown-wrapper">
                    <?php if( $settings->countdown_text ) : ?>
                        <div class="nx-countdown-text">
                            <?php echo esc_html__( $settings->countdown_text, 'notificationx' ); ?>
                        </div>
                    <?php endif; ?>             
                    <div class="nx-countdown" data-countdown="<?php echo esc_attr( json_encode( $countdown ) ); ?>">
                        <div class="nx-time-section">
                            <span class="nx-days">00</span>
                            <span class="nx-countdown-time-text"><?php esc_html_e('Days', 'notificationx'); ?></span>
                        </div>
                        <div class="nx-time-section">
                            <span class="nx-hours">00</span>
                            <span class="nx-countdown-time-text"><?php esc_html_e('Hrs', 'notificationx'); ?></span>
                        </div>
                        <div class="nx-time-section">
                            <span class="nx-minutes">00</span>
                            <span class="nx-countdown-time-text"><?php esc_html_e('Mins', 'notificationx'); ?></span>
                        </div>
                        <div class="nx-time-section">
                            <span class="nx-seconds">00</span>
                            <span class="nx-countdown-time-text"><?php esc_html_e('Secs', 'notificationx'); ?></span>
                        </div>
                        <span class="nx-expired-text"><?php esc_html_e('Expired!', 'notificationx'); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            <div class="nx-inner-content-wrapper">
                <div class="nx-bar-content"><?php echo $settings->press_content; ?></div>
                <?php if( $settings->button_url != '' ) : ?>
                    <a class="nx-bar-button" href="<?php echo esc_url( $settings->button_url ); ?>" <?php echo $attrs; ?>>
                        <?php echo esc_html_e( $settings->button_text, 'notificationx' ); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php if( $settings->close_button ) : ?>
            <p class="nx-close" title="Close"><svg viewBox="0 0 48 48" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="Page-1" stroke="none" stroke-width="1" fill-rule="evenodd"><g id="close" fill-rule="nonzero"><path d="M28.228,23.986 L47.092,5.122 C48.264,3.951 48.264,2.051 47.092,0.88 C45.92,-0.292 44.022,-0.292 42.85,0.88 L23.986,19.744 L5.121,0.88 C3.949,-0.292 2.051,-0.292 0.879,0.88 C-0.293,2.051 -0.293,3.951 0.879,5.122 L19.744,23.986 L0.879,42.85 C-0.293,44.021 -0.293,45.921 0.879,47.092 C1.465,47.677 2.233,47.97 3,47.97 C3.767,47.97 4.535,47.677 5.121,47.091 L23.986,28.227 L42.85,47.091 C43.436,47.677 44.204,47.97 44.971,47.97 C45.738,47.97 46.506,47.677 47.092,47.091 C48.264,45.92 48.264,44.02 47.092,42.849 L28.228,23.986 Z" id="Shape"></path></g></g></svg></p>
        <?php endif; ?>
    </div>
</div>