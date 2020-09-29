<?php
    $themes = $this->template();
?>

<div class="nx-press-bar-modal-wrapper">
    <div class="nx-press-bar-modal">
        <div class="nx-press-bar-modal-preload">
            <div class="nx-press-bar-modal-preload-text">
                <span class="nx-modal-loading-text">
                    <span class="nx-modal-loader"></span>
                    <?php _e('Loading...', 'notificationx'); ?>
                </span>
                <div class="nx-modal-success-text">
                    <?php _e('Successfully Imported', 'notificationx'); ?>&nbsp;✅<br/>
                    <?php _e('Hit on the <strong>Next</strong> Button and configure the other Settings. After Notification Bar is published, you will be automatically redirected to <strong>Edit with Elementor</strong> page for your imported Template.', 'notificationx'); ?>
                    <span>
                        <input type="checkbox" name="_nx_elementor_auto_redirect" checked id="nx_elementor_auto_redirect">
                        <label for="nx_elementor_auto_redirect"><?php _e('Redirect To Edit With Elementor', 'notificationx');?></label>
                    </span>

                    <button class="nx-meta-modal-next nx-ele-bar-button" data-tab="display_tab" data-tabid="4"><?php _e( 'Next', 'notificationx' ); ?></button>
                </div>
            </div>
        </div>
        <div class="nx-press-bar-modal-header">
            <h3><?php _e('Choose Your Template', 'notificationx'); ?></h3>
            <span class="nx-modal-close"><span class="dashicons dashicons-no-alt"></span></span>
        </div>
        <div class="nx-press-bar-modal-content">
            <?php
                if( ! empty( $themes ) ) {
                    foreach( $themes as $key => $image_source ) {
                        ?>
                            <div class="nx-press-single-template">
                                <img src="<?php echo $image_source; ?>" alt=""/>
                                <button
                                    class="nx-bar_with_elementor-import nx-ele-bar-button"
                                    data-theme="<?php echo $key; ?>"
                                    data-nonce="<?php echo $nonce; ?>"
                                    data-the_post="<?php echo $bar_id; ?>"><?php _e( 'Import', 'notificationx' ); ?></button>
                            </div>
                        <?php
                    }
                }

            ?>
        </div>
    </div>
</div>