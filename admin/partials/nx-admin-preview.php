<?php 
    $class_name = $img_classes = '';
    if( $display_type ) {
        $class_name = 'nx-notification-preview-' . $display_type;
    }
    $settings = NotificationX_MetaBox::get_metabox_settings( $post->ID );
?>
<div id="nx-notification-preview" class="<?php echo $class_name; ?>">
    <div class="nx-notification-preview nx-notification-preview-conversions">
        <input id="preview_conversion_css" type="hidden" name="preview_conversion_css" value="<?php echo NotificationX_Public::generate_css_for_preview( $settings ); ?>">
        <?php echo self::preview_html( $settings ); ?>
    </div>

    <div class="nx-notification-preview nx-notification-preview-comments">
        <input id="preview_comment_css" type="hidden" name="preview_comment_css" value="<?php echo NotificationX_Public::generate_css_for_preview( $settings ); ?>">
        <?php echo self::preview_html( $settings, 'comment' ); ?>
    </div>
</div>