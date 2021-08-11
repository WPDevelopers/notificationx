<?php
$attrs = $wrapper_attrs = $class = '';
$pos_class = 'nx-position-top';
if ($settings->link_open) {
    $attrs .= ' target="_blank"';
}

if (\NotificationX\Admin\Settings::get_instance()->get('settings.enable_analytics') != 1 && \NotificationX\Admin\Settings::get_instance()->get('settings.enable_analytics') !== '') {
    $wrapper_attrs .= ' data-analytics="false"';
} else {
    $wrapper_attrs .= ' data-analytics="true"';
}

if ($settings->initial_delay) {
    $wrapper_attrs .= ' data-initial_delay="' . $settings->initial_delay . '"';
}

if ($settings->close_button) {
    $wrapper_attrs .= ' data-close_button="' . $settings->close_button . '"';
}

if ($settings->hide_after) {
    $wrapper_attrs .= ' data-hide_after="' . $settings->hide_after . '"';
}

if ($settings->enable_countdown == '1' && !isset($settings->elementor_id)) {
    if ($settings->countdown_start_date) {
        $wrapper_attrs .= ' data-start_date="' . $settings->countdown_start_date . '"';
    } else {
        $wrapper_attrs .= ' data-start_date="' . date('D, M d, Y h:i A', current_time('timestamp')) . '"';
    }
    if ($settings->countdown_end_date) {
        $wrapper_attrs .= ' data-end_date="' . $settings->countdown_end_date . '"';
    }

    if ($settings->evergreen_timer == '1' && NX_CONSTANTS::is_pro()) {
        $wrapper_attrs .= ' data-evergreen="true"';
        $c_timestamps = current_time('timestamp');
        $wrapper_attrs .= ' data-start_date="' . date('D, M d, Y h:i A', $c_timestamps) . '"';
        $wrapper_attrs .= ' data-eg_expire_in="' . ($settings->time_rotation * 60 * 60 * 1000) . '"';
        $wrapper_attrs .= ' data-time_randomize="' . $settings->time_randomize . '"';
        $wrapper_attrs .= ' data-time_reset="' . $settings->time_reset . '"';

        if (is_array($settings->time_randomize_between)) {
            $random_number = rand($settings->time_randomize_between['start_time'], $settings->time_randomize_between['end_time']);
            $random_number = $random_number * 60 * 60 * 1000;
            $wrapper_attrs .= ' data-duration_timestamp="' . $random_number . '"';
        }
    }
}


// $wrapper_attrs .= ' data-body_push="pushed"';
if ($settings->pressbar_body == 1) {
    $wrapper_attrs .= ' data-body_push="overlap"';
}

if ($settings->close_forever) {
    $wrapper_attrs .= ' data-close_forever="' . $settings->close_forever . '"';
}

if ($settings->auto_hide) {
    $wrapper_attrs .= ' data-auto_hide="' . $settings->auto_hide . '"';
}

if ($settings->sticky_bar) {
    $wrapper_attrs .= ' data-sticky_bar="' . $settings->sticky_bar . '"';
}

if ($settings->nx_id) {
    $wrapper_attrs .= ' data-press_id="' . $settings->nx_id . '"';
}
if ($settings->pressbar_position) {
    $wrapper_attrs .= ' data-position="' . $settings->pressbar_position . '"';
}
$wrapper_attrs .= ' data-nonce="' . wp_create_nonce('_notificationx_bar_nonce') . '"';

if ('bottom' == $settings->pressbar_position) {
    $pos_class = 'nx-position-bottom';
}

if (isset($settings->bar_close_position) && !empty($settings->bar_close_position)) {
    $pos_class .= ' nx-close-' . $settings->bar_close_position;
}

if (is_admin_bar_showing()) {
    $class .= 'nx-admin';
}

$countdown = [];
if ($settings->enable_countdown) {
    if (property_exists($settings, 'countdown_time')) {
        foreach ($settings->countdown_time as $key => $time) {
            $time = empty($time) ? 0 : $time;
            $countdown[$key] = $time < 10 ? '0' . $time : $time;
        }
    }
}

if ($settings->bar_advance_edit) {
    $class .= ' nx-customize-style-' . $settings->nx_id;
}
if ($settings->sticky_bar) {
    $class .= ' nx-sticky-bar';
}

$elementor_post_id = isset($settings->elementor_id) ? $settings->elementor_id : '';

if ($elementor_post_id !== '' && get_post_status($elementor_post_id) === 'publish' && class_exists('\Elementor\Plugin')) {
    $class .= ' nx-bar-has-elementor';
}

?>
<div id="nx-bar-<?php echo $settings->nx_id; ?>" class="nx-bar <?php echo $is_shortcode ? 'nx-bar-shortcode nx-bar-visible' : ''; ?> <?php echo $settings->bar_theme; ?> nx-bar-<?php echo $settings->nx_id; ?> <?php echo esc_attr($pos_class); ?> <?php echo esc_attr($class); ?>" <?php echo $wrapper_attrs; ?>>
    <div class="nx-bar-inner">
        <div class="nx-bar-content-wrap">
            <?php
            $is_elementor_builder = false;
            if ($elementor_post_id != '' && get_post_status($elementor_post_id) === 'publish' && class_exists('\Elementor\Plugin')) {
                if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                    $is_elementor_builder = true;
                }
                echo \Elementor\Plugin::$instance->frontend->get_builder_content($elementor_post_id, false);
            }
            if ($elementor_post_id == '' || get_post_status($elementor_post_id) !== 'publish') :
            ?>
                <?php if (boolval($settings->enable_countdown) || boolval($settings->evergreen_timer)) : ?>
                    <div class="nx-countdown-wrapper">
                        <?php if ($settings->countdown_text) : ?>
                            <div class="nx-countdown-text"><?php echo esc_html_e($settings->countdown_text, 'notificationx'); ?></div>
                        <?php endif; ?>
                        <div class="nx-countdown" data-countdown="<?php echo esc_attr(json_encode($countdown)); ?>">
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
                            <span class="nx-expired-text"><?php esc_html_e(trim($settings->countdown_expired_text), 'notificationx'); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="nx-inner-content-wrapper">
                    <?php if (!empty($settings->press_content)) : ?>
                        <div class="nx-bar-content"><?php echo do_shortcode($settings->press_content); ?></div>
                    <?php else : ?>
                        <div class="nx-bar-content"><?php esc_html_e('You should setup NX Bar properly', 'notificationx'); ?></div>
                    <?php
                    endif;
                    if ($settings->button_url != '') :
                        $pressbar_url = apply_filters('nx_pressbar_link', $settings->button_url, $settings);
                    ?>
                        <a class="nx-bar-button" href="<?php echo esc_url($pressbar_url); ?>" <?php echo $attrs; ?>>
                            <?php echo esc_html_e($settings->button_text, 'notificationx'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        if ($settings->close_button && $is_elementor_builder == false) : ?>
            <!-- <p class="nx-close" title="Close"><svg viewBox="0 0 48 48" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="Page-1" stroke="none" stroke-width="1" fill-rule="evenodd"><g id="close" fill-rule="nonzero"><path d="M28.228,23.986 L47.092,5.122 C48.264,3.951 48.264,2.051 47.092,0.88 C45.92,-0.292 44.022,-0.292 42.85,0.88 L23.986,19.744 L5.121,0.88 C3.949,-0.292 2.051,-0.292 0.879,0.88 C-0.293,2.051 -0.293,3.951 0.879,5.122 L19.744,23.986 L0.879,42.85 C-0.293,44.021 -0.293,45.921 0.879,47.092 C1.465,47.677 2.233,47.97 3,47.97 C3.767,47.97 4.535,47.677 5.121,47.091 L23.986,28.227 L42.85,47.091 C43.436,47.677 44.204,47.97 44.971,47.97 C45.738,47.97 46.506,47.677 47.092,47.091 C48.264,45.92 48.264,44.02 47.092,42.849 L28.228,23.986 Z" id="Shape"></path></g></g></svg></p> -->
        <?php endif; ?>
    </div>
</div>