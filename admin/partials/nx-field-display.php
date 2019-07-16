<?php 
$opt_alert_class = '';
if( isset( $field['disable'] ) && $field['disable'] === true ) {
    $opt_alert_class = 'nx-opt-alert';
}
?>
<tr data-id="<?php echo $key; ?>" id="<?php echo esc_attr( $id ); ?>" class="nx-field <?php echo $row_class; ?>">
    <?php if( empty( $field['label'] ) ) : ?>
        <td class="nx-control" colspan="2">
    <?php else : ?>
    <th class="nx-label">
        <label for="<?php echo esc_attr( $name ); ?>"><?php esc_html_e( $field['label'], 'notificationx' ); ?></label>
    </th>
    <td class="nx-control">
    <?php 
        endif; 
        do_action( 'nx_field_before_wrapper', $name, $value, $field, $post_id );
    ?>
        <div class="nx-control-wrapper <?php echo $opt_alert_class; ?>">
        <?php 
            if( $file_name ) {
                include NOTIFICATIONX_ADMIN_DIR_PATH . 'includes/fields/nx-'. $file_name .'.php';
            } else {
                if( $field['view'] ) {
                    call_user_func( $field['view'] );
                }
            }
            if( isset( $field['description'] ) && ! empty( $field['description'] ) ) : 
                ?>
                    <span class="nx-field-description"><?php _e( $field['description'], 'notificationx' ); ?></span>
                <?php
            endif;
            if( isset( $field['help'] ) && ! empty( $field['help'] ) ) : 
                ?>
                    <p class="nx-field-help"><?php _e( $field['help'], 'notificationx' ); ?></p>
                <?php
            endif;
        ?>
        </div>
        <?php do_action( 'nx_field_after_wrapper', $name, $value, $field, $post_id ); ?>
    </td>
</tr>
