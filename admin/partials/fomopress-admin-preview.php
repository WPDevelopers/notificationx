<?php 
    $class_name = $img_classes = '';
    if( $display_type ) {
        $class_name = 'fomopress-notification-preview-' . $display_type;
    }
    $settings = NotificationX_MetaBox::get_metabox_settings( $post->ID );
    $img_classes .= ' fp-img-' . $settings->image_shape;
    $img_classes .= ' fp-img-' . $settings->image_position;
?>
<div id="fomopress-notification-preview" class="<?php echo $class_name; ?>">
    <div class="fomopress-notification-preview fomopress-notification-preview-conversions">
        <div class="fomopress-preview-inner" <?php NotificationX_Public::generate_preview_css( $settings ); ?>>
            <div class="fomopress-preview-image <?php echo $img_classes; ?>">
                <img src="<?php echo NOTIFICATIONX_ADMIN_URL . 'assets/img/placeholder-300x300.png'; ?>" alt="">
            </div>
            <div class="fomopress-preview-content">
                <span <?php NotificationX_Public::generate_preview_css( $settings, 'first-row' ); ?> class="fomopress-preview-row fomopress-preview-first-row fomopress-highlight"><?php _e( 'John D. recently purchased', 'notificationx' ); ?></span>
                <span <?php NotificationX_Public::generate_preview_css( $settings, 'second-row' ); ?> class="fomopress-preview-row fomopress-preview-second-row"><?php _e( 'Example Product', 'notificationx' ); ?></span>
                <span <?php NotificationX_Public::generate_preview_css( $settings, 'third-row' ); ?> class="fomopress-preview-row fomopress-preview-third-row"><?php _e( '1 hour ago', 'notificationx' ); ?></span>
            </div>
            <span class="fomopress-preview-close">x</span>
        </div>
    </div>

    <div class="fomopress-notification-preview fomopress-notification-preview-comments">
        <div class="fomopress-preview-inner" <?php NotificationX_Public::generate_preview_css( $settings ); ?>>
            <div class="fomopress-preview-image">
                <img src="<?php echo NOTIFICATIONX_ADMIN_URL . 'assets/img/placeholder-300x300.png'; ?>" alt="">
            </div>
            <div class="fomopress-preview-content">
                <span class="fomopress-preview-row fomopress-preview-first-row fomopress-highlight"><?php _e( 'John D. posted a comment', 'notificationx' ); ?></span>
                <span class="fomopress-preview-row fomopress-preview-second-row"><?php _e( 'on Example Post Title', 'notificationx' ); ?></span>
                <span class="fomopress-preview-row fomopress-preview-third-row"><?php _e( '1 hour ago', 'notificationx' ); ?></span>
            </div>
            <span class="fomopress-preview-close">x</span>
        </div>
    </div>
</div>