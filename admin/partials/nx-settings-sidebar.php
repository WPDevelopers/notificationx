<div class="nx-settings-right">
    <div class="nx-sidebar">
        <div class="nx-sidebar-block">
            <div class="nx-admin-sidebar-logo">
                <img alt="NotificationX" src="<?php echo plugins_url( '/', __FILE__ ).'../assets/img/logo.svg'; ?>">
            </div>
            <div class="nx-admin-sidebar-cta">
                <?php     
                    if(class_exists('NotificationXPro')) {
                        printf( __( '<a rel="nofollow" href="%s" target="_blank">Manage License</a>', 'notificationx' ), 'https://wpdeveloper.net/account' ); 
                    }else{
                        printf( __( '<a rel="nofollow" href="%s" target="_blank">Upgrade to Pro</a>', 'notificationx' ), 'https://wpdeveloper.net/in/notificationx-pro' );
                    }
                ?>
            </div>
        </div>
        <div class="nx-sidebar-block nx-license-block">
            <?php
                if(class_exists('NotificationXPro')) {
                do_action( 'nx_licensing' );
            }
        ?>
        </div>
    </div>
</div>