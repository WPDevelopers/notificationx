<?php
    $themes = $this->template();
?>

<div class="nx-press-bar-modal-wrapper">
    <div class="nx-press-bar-modal">
        <div class="nx-press-bar-modal-header">
            <h3>Choose your template</h3>
            <span class="nx-modal-close">x</span>
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
                                    data-the_post="<?php echo $bar_id; ?>">Import</button>
                            </div>
                        <?php
                    }
                }

            ?>
        </div>
    </div>
</div>