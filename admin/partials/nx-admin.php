<?php 
global $pagenow;
$post_status = wp_count_posts('notificationx');
$publish_notificationx = isset( $post_status->publish ) ? $post_status->publish : 0;
$trash_notificationx = isset( $post_status->trash ) ? $post_status->trash : 0;
$total_notificationx = ( $notificationx->post_count > $trash_notificationx ) ? $notificationx->post_count - $trash_notificationx : 0;
$current_url = admin_url('admin.php?page=nx-admin');
$publish_url = add_query_arg('post_status', 'publish', $current_url);
$trash_url = add_query_arg('post_status', 'trash', $current_url);

if( isset( $_GET['post_status'] ) && $_GET['post_status'] === 'trash' && $trash_notificationx === 0 ) {
    wp_safe_redirect( $current_url, 200 );
}

if( isset( $_GET['action'], $_GET['post'], $_GET['nx_duplicate_nonce'] ) && $_GET['action'] === 'nxduplicate' ) {
    if( wp_verify_nonce( $_GET['nx_duplicate_nonce'], 'nx_duplicate_nonce' ) ) {
        $nx_post_id = intval( $_GET['post'] );
        $get_post = get_post( $nx_post_id );
        $post_data = json_decode( json_encode( $get_post ), true );
        unset( $post_data['ID'] );
        $post_data['post_title'] = $post_data['post_title'] . ' - Copy';
        $duplicate_post_id = wp_insert_post( $post_data );
        $get_post_meta = get_metadata( 'post', $nx_post_id );
        if( ! empty( $get_post_meta ) ) {
            foreach( $get_post_meta as $key => $value ){
                if( in_array( $key, array( '_edit_lock', '_edit_last' ) ) ) {
                    continue;
                }
                add_post_meta( intval( $duplicate_post_id ), $key, $value[0], true );
            }
        }
        wp_safe_redirect( $current_url, 200 );
    }
}

?>
<div class="nx-admin-wrapper">
    <div class="nx-admin-header">
        <img src="<?php echo NOTIFICATIONX_URL; ?>/admin/assets/img/nx-black-white-logo.png" alt="">
        <a class="nx-add-new-btn" href="post-new.php?post_type=notificationx"><?php echo _e('Add New', 'notificationx'); ?></a>
    </div>

    <div class="nx-admin-notice">
        <?php ?>
    </div>

    <div class="nx-admin-menu">
        <ul>
            <li class="active"><a href="<?php echo esc_url( $current_url ); ?>">All (<?php echo $total_notificationx; ?>)</a></li>
            <?php if( $publish_notificationx > 0 ) : ?>
            <li><a href="<?php echo esc_url( $publish_url ); ?>">Published (<?php echo $publish_notificationx; ?>)</a></li>
            <?php endif; ?>
            <?php if( $trash_notificationx > 0 ) : ?>
                <li><a href="<?php echo esc_url( $trash_url ); ?>">Trash (<?php echo $trash_notificationx; ?>)</a></li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="nx-admin-items">
        <table class="wp-list-table widefat fixed striped notificationx-list">
            <thead>
                <tr>
                    <td>NotificationX Title</td>
                    <td><?php _e('Preview', 'notificationx'); ?></td>
                    <td><?php _e('Status', 'notificationx'); ?></td>
                    <td><?php _e('Type', 'notificationx'); ?></td>
                    <td><?php _e('Date', 'notificationx'); ?></td>
                </tr>
            </thead>
            <tbody>
                <?php 
                    $trash_btn_title = __( 'Trash', 'notificationx' );
                    $trash_page = false;
                    $trashed = false;
                    if( $notificationx->have_posts() ) :
                        $post_type_object = get_post_type_object( 'notificationx' );
                        while( $notificationx->have_posts() ) : $notificationx->the_post(); 
                            $idd = get_the_ID();
                            $duplicate_url = add_query_arg(array(
                                'action' => 'nxduplicate',
                                'post' => $idd,
                                'nx_duplicate_nonce' => wp_create_nonce( 'nx_duplicate_nonce' ),
                            ), $current_url);
                            $is_enabled = get_post_meta( $idd, '_nx_meta_active_check', true );
                            $settings = NotificationX_MetaBox::get_metabox_settings( $idd );
                            $theme_name = NotificationX_Helper::get_theme( $settings );
                            $type = NotificationX_Helper::notification_types( $settings->display_type );
                            $status = get_post_status( $idd );
                            if( $pagenow === 'admin.php' && isset( $_GET['page'] ) && $_GET['page'] === 'nx-admin' ) {
                                if( isset( $_GET['post_status'] ) && $_GET['post_status'] === 'trash' ) {
                                    $trash_page = true;
                                    $trashed = true;
                                    if( $status !== 'trash' ) {
                                        continue;
                                    }
                                    $trash_btn_title = __( 'Delete Permanently', 'notificationx' );
                                } elseif( isset( $_GET['post_status'] ) && $_GET['post_status'] === 'publish' ){
                                    if( $status !== 'publish' ) {
                                        continue;
                                    }
                                } else {
                                    if( $status === 'trash' ) {
                                        continue;
                                    }
                                }
                            }

                            ?>
                                <tr>
                                    <td>
                                        <div class="nx-admin-title">
                                            <strong><a href="post.php?action=edit&post=<?php echo $idd; ?>"><?php echo get_the_title(); ?></a></strong>
                                            <div class="nx-admin-title-actions">
                                                <?php if( ! $trash_page ) : ?>
                                                    <a class="nx-admin-title-edit" href="post.php?action=edit&post=<?php echo $idd; ?>"><?php _e( 'Edit', 'notificationx' ); ?></a>
                                                    <a class="nx-admin-title-duplicate" href="<?php echo esc_url( $duplicate_url ); ?>"><?php _e( 'Duplicate', 'notificationx' ); ?></a>
                                                <?php else :  ?>
                                                    <a class="nx-admin-title-restore" href="<?php echo wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $idd ) ), 'untrash-post_' . $idd ); ?>"><?php _e( 'Restore', 'notificationx' ); ?></a>
                                                <?php endif; ?>
                                                <a class="nx-admin-title-trash" href="<?php echo get_delete_post_link( $idd, '', $trashed ); ?>"><?php echo $trash_btn_title; ?></a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="nx-admin-preview">
                                            <img width="250px" src="<?php echo NX_CONSTANTS::themeSource( $theme_name, $settings->display_type ); ?>" alt="<?php echo get_the_title(); ?>">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="nx-admin-status">
                                            <span class="nx-admin-status-title nxast-enable <?php echo $is_enabled ? 'active' : ''; ?>"><?php echo _e( 'Enable', 'notificationx' ); ?></span>
                                            <span class="nx-admin-status-title nxast-disable <?php echo $is_enabled ? '' : 'active'; ?>"><?php echo _e( 'Disable', 'notificationx' ); ?></span>
                                            <input type="checkbox" id="nx-toggle-<?php echo $idd; ?>" name="_nx_meta_active_check" <?php echo $is_enabled ? 'checked="checked"' : ''; ?>>
                                            <label data-post="<?php echo $idd; ?>" data-nonce="<?php echo wp_create_nonce('notificationx_status_nonce'); ?>" for="nx-toggle-<?php echo $idd; ?>"></label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="nx-admin-type">
                                            <?php echo $type; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="nx-admin-date">
                                            <?php 
                                                if( get_post_status( get_the_ID() ) === 'publish' ) {
                                                    echo '<span class="nx-admin-publish-status">' . _e('Published', 'notificationx') . '</span><br><span class="nx-admin-publish-date">' . get_the_time( __( 'Y/m/d' ) ). '</span>';
                                                }
                                                if( get_post_status( get_the_ID() ) === 'trash' ) {
                                                    echo '<span class="nx-admin-publish-status">' . _e('Last Modified', 'notificationx') . '</span><br><span class="nx-admin-publish-date">' . get_the_time( __( 'Y/m/d' ) ). '</span>';
                                                }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                            
                            <?php
                        endwhile;
                    endif;

                    if( ! $total_notificationx && ! $trashed ) {
                        echo '<tr><td colspan="5"><div class="nx-admin-not-found">'. __('No NotificationX is found.', 'notificationx') .'</div></td></tr>';
                    }
                ?>
                <!-- <tr><td><p>No NotificationX is found.</p></td></tr> -->
            </tbody>
        </table>
    </div>

</div>