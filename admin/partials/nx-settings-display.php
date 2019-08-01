<div class="nx-settings-wrap">
    <?php do_action( 'notificationx_settings_header' ); ?>

    <div class="nx-left-right-settings">
        <div class="nx-settings">
            <!-- Settings Menu -->
            <div class="nx-settings-menu">
                <ul>
                    <?php
                        $i = 1;
                        foreach( $settings_args as $key => $setting ) {
                            $active = $i++ === 1 ? 'active ' : '';
                            if( isset( $setting['is_pro'] ) && $setting['is_pro'] ) {
                                continue;
                            }
                            echo '<li class="'. $active .'" data-tab="'. $key .'"><a href="#'. $key .'">'. $setting['title'] .'</a></li>';
                        }
                    ?>
                </ul>
            </div> <!-- Settings Menu End -->
            <!-- Settings Content -->
            <div class="nx-settings-content">
                <?php 
                    include NOTIFICATIONX_ADMIN_DIR_PATH . 'partials/nx-settings-form.php';
                    if( ! NX_CONSTANTS::is_pro() ) {
                        include NOTIFICATIONX_ADMIN_DIR_PATH . 'partials/nx-settings-sidebar.php';
                    }
                ?>
            </div> <!-- Settings Content End -->
            <?php include NOTIFICATIONX_ADMIN_DIR_PATH . 'partials/nx-settings-blocks.php'; ?>
        </div>
    </div>
</div>