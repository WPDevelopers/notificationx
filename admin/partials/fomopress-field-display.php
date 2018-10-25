<?php 
$opt_alert_class = '';
if( isset( $field['disable'] ) && $field['disable'] === true ) {
    $opt_alert_class = 'fomopress-opt-alert';
}
?>
<tr id="<?php echo esc_attr( $id ); ?>" class="fomopress-field <?php echo $row_class; ?>">
    <?php if( empty( $field['label'] ) ) : ?>
        <td class="fomopress-control" colspan="2">
    <?php else : ?>
    <th class="fomopress-label">
        <label for="<?php echo esc_attr( $name ); ?>"><?php esc_html_e( $field['label'], 'fomopress' ); ?></label>
    </th>
    <td class="fomopress-control">
    <?php 
        endif; 
        do_action( 'fomopress_field_before_wrapper', $name, $value, $field, $post_id );
    ?>
        <div class="fomopress-control-wrapper <?php echo $opt_alert_class; ?>">
        <?php 
            if( $file_name ) {
                include FOMOPRESS_ADMIN_DIR_PATH . 'includes/fields/fomopress-'. $file_name .'.php';
            } else {
                if( $field['view'] ) {
                    call_user_func( $field['view'] );
                }
            }
            if( isset( $field['description'] ) && ! empty( $field['description'] ) ) : 
                ?>
                    <span class="fomopress-field-description"><?php _e( $field['description'], 'fomopress' ); ?></span>
                <?php
            endif;
            if( isset( $field['help'] ) && ! empty( $field['help'] ) ) : 
                ?>
                    <p class="fomopress-field-help"><?php _e( $field['help'], 'fomopress' ); ?></p>
                <?php
            endif;
        ?>
        </div>
        <?php do_action( 'fomopress_field_after_wrapper', $name, $value, $field, $post_id ); ?>
    </td>
</tr>
