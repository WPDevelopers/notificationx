<?php 
    if( isset( $field['fields'] ) ){
        $fields = NotificationX_Helper::sorter( $field['fields'], 'priority', 'ASC' );;
    } else {
        return;
    }

    $group_value = $value;
    $group_title = isset( $field['title'] ) ? $field['title'] : '';
    $parent_key = $key;
    $group_field_info = array();
?>

<div class="nx-group-field-wrapper" id="<?php echo $id; ?>" data-name="<?php echo $name; ?>">
    <script type="text/html" class="nx-group-template">
        <div class="nx-group-field" data-id="0" data-field-name="<?php echo $parent_key;?>">
            <h4 class="nx-group-field-title">
                <span><?php echo _e( $group_title, 'notificationx' ); ?></span>
                <div class="nx-group-controls">
                    <a href="#" class="nx-group-clone" data-tooltip="Duplicate"><span class="dashicons dashicons-admin-page"></span></a>
                    <a href="#" class="nx-group-remove" data-tooltip="Remove"><span class="dashicons dashicons-trash"></span></a>
                </div>
            </h4>
            <div class="nx-group-inner">
                <table>
                    <?php 
                        foreach( $fields as $inner_key => $inner_field ) {
                            $name = $parent_key . '[0][' . $inner_key . ']';
                            $group_field_info['group_field'] = $parent_key;
                            $group_field_info['group_sub_fields'][] = array(
                                'field_name'    => $name,
                                'original_name' => $inner_key,
                            );
                            NotificationX_MetaBox::render_meta_field( $name, $inner_field );
                        }
                        ?>
                </table>
                <div class="nx-group-field-info" data-info="<?php echo esc_attr( json_encode( $group_field_info ) ); ?>"></div>
            </div>
        </div>
    </script>

    <div class="nx-group-fields-wrapper">
        <?php if( empty( $group_value ) ) : ?>
            <div class="nx-group-field" data-id="0" data-field-name="<?php echo $parent_key;?>">
                <h4 class="nx-group-field-title">
                    <span><?php echo _e( $group_title, 'notificationx' ); ?></span>
                    <div class="nx-group-controls">
                        <a href="#" class="nx-group-clone" data-tooltip="Duplicate"><span class="dashicons dashicons-admin-page"></span></a>
                        <a href="#" class="nx-group-remove" data-tooltip="Remove"><span class="dashicons dashicons-trash"></span></a>
                    </div>
                </h4>
                <div class="nx-group-inner">
                    <table>
                        <?php 
                            $group_field_info = array();
                            foreach( $fields as $inner_key => $inner_field ) {
                                $name = $parent_key . '[0][' . $inner_key . ']';

                                $group_field_info['group_field'] = $parent_key;
                                $group_field_info['group_sub_fields'][] = array(
                                    'field_name'    => $name,
                                    'original_name' => $inner_key,
                                );

                                NotificationX_MetaBox::render_meta_field( $name, $inner_field );
                            }
                        ?>
                    </table>
                    <div class="nx-group-field-info" data-info="<?php echo esc_attr( json_encode( $group_field_info ) ); ?>"></div>
                </div>
            </div>
        <?php else : ?>
            <?php 
                $group_field_info = array();
                foreach( $group_value as $group_id => $field_data ) : ?>
            <div class="nx-group-field" data-id="<?php echo $group_id; ?>" data-field-name="<?php echo $parent_key;?>">
                <h4 class="nx-group-field-title">
                    <span><?php echo _e( $group_title, 'notificationx' ); ?></span>
                    <div class="nx-group-controls">
                        <a href="#" class="nx-group-clone" data-tooltip="Duplicate"><span class="dashicons dashicons-admin-page"></span></a>
                        <a href="#" class="nx-group-remove" data-tooltip="Remove"><span class="dashicons dashicons-trash"></span></a>
                    </div>
                </h4>
                <div class="nx-group-inner">
                    <table>
                        <?php 
                            foreach( $fields as $key => $field ) {
                                $name = $parent_key . '['. $group_id .'][' . $key . ']';
                                $group_field_info['group_field'] = $parent_key;
                                $group_field_info['group_sub_fields'][] = array(
                                    'field_name'    => $name,
                                    'original_name' => $key,
                                );
                                $field_value = isset( $field_data[ $key ] ) ? $field_data[ $key ] : '';
                                NotificationX_MetaBox::render_meta_field( $name, $field, $field_value );
                            }
                        ?>
                    </table>
                    <div class="nx-group-field-info" data-info="<?php echo esc_attr( json_encode( $group_field_info ) ); ?>"></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <button class="nx-group-field-add"><span class="dashicons dashicons-plus"></span></button>
    </div>
</div>
