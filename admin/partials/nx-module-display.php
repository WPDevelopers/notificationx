<div class="nx-module-section <?php echo esc_attr( $module_key ); ?>">
    <?php 
        if( ! is_array( $module ) ) :
            echo '<h5>'. $module .'</h5>';
        else :
            echo '<h5>'. $module['title'] .'</h5>';
            $inner_modules = isset( $module['modules'] ) ? $module['modules'] : [];
            if( ! empty( $inner_modules ) ) : 
                foreach( $inner_modules as $inner_module_key => $inner_module ) :
            ?>
            <div class="nx-checkbox">
                <input type="checkbox" id="<?php echo $inner_module_key; ?>" name="" disabled="">
                <label for="<?php echo $inner_module_key; ?>" class="eael-get-pro"></label>
                <p class="nx-module-title">Testimonial Slider<sup class="pro-label">Pro</sup></p>
            </div>
            <?php
                endforeach;
            endif;
        endif;
    ?>
</div>