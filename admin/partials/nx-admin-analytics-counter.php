<div class="nx-analytics-header-counter-wrapper <?php echo esc_attr( $class ); ?>">
    <div class="nx-header-analytics-counter-wrapper">
        <div>
            <div class="nx-header-analytics-counter">
                <a href="<?php echo esc_url( $views_link );?> ">
                    <span class="nx-counter-icon">
                        <img src="<?php echo NOTIFICATIONX_ADMIN_URL . 'assets/img/views-icon.png'; ?>" alt="<?php _e( 'Total Views' ); ?>">
                    </span>
                    <div>
                        <span class="nx-counter-number"><?php _e( $views ) ?></span>
                        <span class="nx-counter-label"><?php _e( 'Total Views', 'notificationx-pro' ); ?></span>
                    </div>
                </a>
            </div>
        </div>
        <div>
            <div class="nx-header-analytics-counter">
                <a href="<?php echo esc_url( $clicks_link );?> ">
                    <span class="nx-counter-icon">
                        <img src="<?php echo NOTIFICATIONX_ADMIN_URL . 'assets/img/clicks-icon.png'; ?>" alt="<?php _e( 'Total Clicks' ); ?>">
                    </span>
                    <div>
                        <span class="nx-counter-number"><?php _e( $clicks ) ?></span>
                        <span class="nx-counter-label"><?php _e( 'Total Clicks', 'notificationx-pro' ); ?></span>
                    </div>
                </a>
            </div>
        </div>
        <div>
            <div class="nx-header-analytics-counter">
                <a href="<?php echo esc_url( $ctr_link );?> ">
                    <span class="nx-counter-icon">
                        <img src="<?php echo NOTIFICATIONX_ADMIN_URL . 'assets/img/ctr-icon.png'; ?>" alt="<?php _e( 'Click-Through-Rate' ); ?>">
                    </span>
                    <div>
                        <span class="nx-counter-number"><?php _e( $ctr ) ?></span>
                        <span class="nx-counter-label"><?php _e( 'Click-Through-Rate', 'notificationx-pro' ); ?></span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
