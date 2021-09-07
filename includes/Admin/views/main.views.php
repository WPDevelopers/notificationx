<hr class="wp-header-end"/>
<div class="wrap">
    <div class="notificationx-admin">
        <noscript>
            <p class="notificationx-disabled-javascript-notice">
                <?php
                    echo sprintf(
                        __( 'To work %sNotificationX%s properly you need to %sEnable JavaScript%s in your browser or make sure you have installed updated browser in your device.', 'notificationx' ),
                        '<strong><em>', '</em></strong>',
                        '<strong>', '</strong>'
                    );
                ?>
            </p>
        </noscript>
        <div id="notificationx">
            <div style="display: flex;align-items: center;justify-content: center;height: 60vh;">
                <img src="<?php  echo self::ASSET_URL . 'images/logos/logo-preloader.gif'; ?>" alt="">
            </div>
        </div>
    </div>
</div>