<?php
    $class .= ' nx-template-field';
    if( isset( $field['variables'] ) ) :
        $variables = $field['variables'];
    endif;

    if( ! isset( $field['fields'] ) ) {
        return;
    }

    $inner_fields = $field['fields'];

    $main_value = $value;
?>
<div id="<?php echo $name; ?>">
    <div class="template-items">
        <?php 
            foreach( $inner_fields as $key => $inner_field ) {
                $main_name = $name;
                $field_id = $name . "_" . $key;
                $name = $name . "[" . $key . "]";

                $attrs .= ' data-subkey="' . esc_attr( $key ) . '"';

                $file_name = $inner_field['type'];
                if( $file_name === 'select' ) {
                    $field['options'] = isset( $inner_field['options'] ) ? $inner_field['options'] : '';
                }
                $value = isset( $main_value[ $key ] ) ? $main_value[ $key ] : ( isset( $inner_field[ 'default'] ) ? $inner_field[ 'default'] : '' ) ;
                
                if( $file_name ) {
                    include NOTIFICATIONX_ADMIN_DIR_PATH . 'includes/fields/nx-'. $file_name .'.php';
                }
                $name = $main_name;
            }
        ?>
    </div>
</div>