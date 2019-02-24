<?php 
    $class_name = $img_classes = '';
    if( $display_type ) {
        $class_name = 'nx-notification-preview-' . $display_type;
    }
    $settings = NotificationX_MetaBox::get_metabox_settings( $post->ID );
?>
<div id="nx-notification-preview" class="<?php echo $class_name; ?>">
    <div class="nx-notification-preview nx-notification-preview-conversions">
        <div class="nx-preview-inner" <?php NotificationX_Public::generate_preview_css( $settings ); ?>>
            <div class="nx-preview-image">
                <img class="<?php echo NotificationX_Extension::get_classes( $settings, 'img' ); ?>" src="<?php echo NOTIFICATIONX_ADMIN_URL . 'assets/img/placeholder-300x300.png'; ?>" alt="">
            </div>
            <div class="nx-preview-content">
                <span <?php NotificationX_Public::generate_preview_css( $settings, 'first-row' ); ?> class="nx-preview-row nx-preview-first-row nx-highlight"><?php _e( 'John D. recently purchased', 'notificationx' ); ?></span>
                <span <?php NotificationX_Public::generate_preview_css( $settings, 'second-row' ); ?> class="nx-preview-row nx-preview-second-row"><?php _e( 'Example Product', 'notificationx' ); ?></span>
                <span <?php NotificationX_Public::generate_preview_css( $settings, 'third-row' ); ?> class="nx-preview-row nx-preview-third-row"><?php _e( '1 hour ago', 'notificationx' ); ?></span>
            </div>
            <span class="nx-preview-close">x</span>
        </div>
    </div>

    <div class="nx-notification-preview nx-notification-preview-comments">
        <div class="nx-preview-inner" <?php NotificationX_Public::generate_preview_css( $settings ); ?>>
            <div class="nx-preview-image">
                <img class="<?php echo NotificationX_Extension::get_classes( $settings, 'img' ); ?>" src="<?php echo NOTIFICATIONX_ADMIN_URL . 'assets/img/placeholder-300x300.png'; ?>" alt="">
            </div>
            <div class="nx-preview-content">
                <span <?php NotificationX_Public::generate_preview_css( $settings, 'first-row' ); ?> class="nx-preview-row nx-preview-first-row nx-highlight"><?php _e( 'John D. posted a comment', 'notificationx' ); ?></span>
                <span <?php NotificationX_Public::generate_preview_css( $settings, 'second-row' ); ?> class="nx-preview-row nx-preview-second-row"><?php _e( 'on Example Post Title', 'notificationx' ); ?></span>
                <span <?php NotificationX_Public::generate_preview_css( $settings, 'third-row' ); ?> class="nx-preview-row nx-preview-third-row"><?php _e( '1 hour ago', 'notificationx' ); ?></span>
            </div>
            <span class="nx-preview-close">x</span>
        </div>
    </div>
</div>