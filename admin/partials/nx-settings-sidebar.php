<div class="nx-settings-right">
    <div class="nx-sidebar">
        <div class="nx-sidebar-block">
            <div class="nx-admin-sidebar-logo">
                <img src="<?php echo plugins_url( '/', __FILE__ ).'../assets/img/nx-logo.png'; ?>">
            </div>
            <div class="nx-admin-sidebar-cta">
                <?php     
                    if(class_exists('NotificationXPro')) {
                        printf( __( '<a href="%s" target="_blank">Manage License</a>', 'notificationx' ), 'https://wpdeveloper.net/account' ); 
                    }else{
                        printf( __( '<a href="%s" target="_blank">Upgrade to Pro</a>', 'notificationx' ), 'https://wpdeveloper.net/in/notificationx-pro' );
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

    <div class="nx-settings-documentation">
        <div class="nx-admin-block nx-admin-block-docs">
            <header class="nx-admin-block-header">
                <div class="nx-admin-block-header-icon">
                    <img src="https://eae.dev/wp-content/plugins/essential-addons-for-elementor-lite/assets/admin/images/icon-documentation.svg" alt="essential-addons-for-elementor-documentation">
                </div>
                <h4 class="nx-admin-title"><?php _e( 'Documentation', 'notificationx' ); ?></h4>
            </header>
            <div class="nx-admin-block-content">
                <p><?php _e('Get started by spending some time with the documentation to get familiar with Essential Addons. Build awesome websites for you or your clients with ease.', 'notificationx');?></p>
                <a href="https://notificationx.com/docs/" class="nx-button" target="_blank"><?php _e( 'Documentation', 'notificationx' ); ?></a>
            </div>
        </div>
        <div class="nx-admin-block nx-admin-block-docs">
            <header class="nx-admin-block-header">
                <div class="nx-admin-block-header-icon">
                    <img src="https://eae.dev/wp-content/plugins/essential-addons-for-elementor-lite/assets/admin/images/icon-documentation.svg" alt="essential-addons-for-elementor-documentation">
                </div>
                <h4 class="nx-admin-title"><?php _e( 'Contribute to Essential Addons', 'notificationx' ); ?></h4>
            </header>
            <div class="nx-admin-block-content">
                <p><?php echo sprintf( '%1$s <a href="%2$s">Github</a>.', __( 'You can contribute to make Essential Addons better reporting bugs, creating issues, pull requests at', 'notificationx' ), 'https://github.com/WPDevelopers/notificationx' ); ?></p>
                <a href="https://github.com/WPDevelopers/notificationx/issues/new" class="nx-button" target="_blank"><?php _e( 'Report a bug', 'notificationx' ); ?></a>
            </div>
        </div>
        <div class="nx-admin-block nx-admin-block-docs">
            <header class="nx-admin-block-header">
                <div class="nx-admin-block-header-icon">
                    <img src="https://eae.dev/wp-content/plugins/essential-addons-for-elementor-lite/assets/admin/images/icon-documentation.svg" alt="essential-addons-for-elementor-documentation">
                </div>
                <h4 class="nx-admin-title"><?php _e( 'Need Help?', 'notificationx' ); ?></h4>
            </header>
            <div class="nx-admin-block-content">
                <p><?php _e('Stuck with something? Get help from live chat or support ticket.', 'notificationx'); ?></p>
                <a href="https://wpdeveloper.net" class="nx-button" target="_blank"><?php _e( 'Initiate a Chat', 'notificationx' ); ?></a>
            </div>
        </div>
        <div class="nx-admin-block nx-admin-block-docs">
            <header class="nx-admin-block-header">
                <div class="nx-admin-block-header-icon">
                    <img src="https://eae.dev/wp-content/plugins/essential-addons-for-elementor-lite/assets/admin/images/icon-documentation.svg" alt="essential-addons-for-elementor-documentation">
                </div>
                <h4 class="nx-admin-title"><?php _e( 'Join the Community', 'notificationx' ); ?></h4>
            </header>
            <div class="nx-admin-block-content">
                <p><?php _e( 'Join the Facebook community and discuss with fellow developers and users. Best way to connect with people and get feedback on your projects.', 'notificationx' ); ?></p>
                <a href="https://www.facebook.com/NotificationXforWP/" class="nx-button" target="_blank"><?php _e( 'Join Facebook Community', 'notificationx' ); ?></a>
            </div>
        </div>
    </div>
</div>