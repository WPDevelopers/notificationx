<?php
global $pagenow;
$post_status           = self::count_posts();
$publish_notificationx = isset( $post_status->publish ) ? $post_status->publish : 0;
$trash_notificationx   = $post_status->trash;
$current_url           = admin_url('admin.php?page=nx-admin');
$publish_url           = add_query_arg('status', 'enabled', $current_url);
$disabled_url          = add_query_arg('status', 'disabled', $current_url);
$trash_url             = add_query_arg('status', 'trash', $current_url);
$empty_trash_url       = add_query_arg('delete_all', true, $current_url);
$get_enabled_post      = $post_status->enabled;
$get_disabled_post     = $post_status->disabled;
$total_notificationx   = $get_enabled_post + $get_disabled_post;
?>
<div class="nx-admin-wrapper">
    <?php do_action( 'notificationx_admin_header' ); ?>

    <div class="nx-admin-notice">
        <?php ?>
    </div>

    <div class="nx-admin-menu">
        <ul>
            <li <?php echo $all_active_class; ?>><a href="<?php echo esc_url( $current_url ); ?>">All (<?php echo $total_notificationx; ?>)</a></li>
            <?php if( $get_enabled_post > 0 ) : ?>
            <li <?php echo $enabled_active_class; ?>><a href="<?php echo esc_url( $publish_url ); ?>"><?php _e( 'Enabled', 'notificationx' ); ?> (<?php echo $get_enabled_post; ?>)</a></li>
            <?php endif; ?>
            <?php if( $get_disabled_post > 0 ) : ?>
            <li <?php echo $disabled_active_class; ?>><a href="<?php echo esc_url( $disabled_url ); ?>"><?php _e( 'Disabled', 'notificationx' ); ?> (<?php echo $get_disabled_post; ?>)</a></li>
            <?php endif; ?>
            <?php if( $trash_notificationx > 0 ) : ?>
                <li <?php echo $trash_active_class; ?>><a href="<?php echo esc_url( $trash_url ); ?>"><?php _e( 'Trash', 'notificationx' ); ?> (<?php echo $trash_notificationx; ?>)</a></li>
                <?php if( isset( $_GET['status'] ) && $_GET['status'] === 'trash' ) : ?>
                    <li class="nx-empty-trash-btn"><a href="<?php echo esc_url( $empty_trash_url ); ?>"><?php _e( 'Empty Trash', 'notificationx' ); ?></a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </div>

    <div class="nx-admin-items">
        <table class="wp-list-table widefat fixed striped notificationx-list">
            <thead>
                <tr>
                    <?php
                        if( ! empty( $table_header ) ) {
                            foreach( $table_header as $title ) {
                                echo '<td>' . $title . '</td>';
                            }
                        }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                    $trash_btn_title = __( 'Trash', 'notificationx' );
                    $trash_page = false;
                    $trashed = false;
                    if( count( $notificationx ) > 0 ) :
                        $post_type_object = get_post_type_object( 'notificationx' );
                        global $nx_extension_factory;
                        foreach( $notificationx as $single_nx ) : // $notificationx->the_post();
                            $idd = $single_nx->ID;
                            $duplicate_url = add_query_arg(array(
                                'action' => 'nxduplicate',
                                'post' => $idd,
                                'nx_duplicate_nonce' => wp_create_nonce( 'nx_duplicate_nonce' ),
                            ), $current_url);
                            $settings = NotificationX_MetaBox::get_metabox_settings( $idd );
                            $is_enabled = $settings->active_check;
                            $theme_name = NotificationX_Helper::get_theme( $settings );
                            $type = NotificationX_Helper::notification_types( $settings->display_type );
                            $nx_type = NotificationX_Helper::get_type( $settings );
                            $extension_class = $nx_extension_factory->get_extension( $nx_type );
                            $extension = null;
                            if( ! empty( $extension_class ) ) {
                                $extension = $extension_class::get_instance();
                            }
                            /**
                             * @since 1.4.0
                             * re-generating system
                             */
                            $regenerate_url = add_query_arg(array(
                                'action' => 'nx_regenerate',
                                'nx_type' => $nx_type,
                                'post' => $idd,
                                'from' => $settings->display_from,
                                'last' => $settings->display_last,
                                'nx_regenerate_nonce' => wp_create_nonce( 'nx_regenerate_nonce' ),
                            ), $current_url );

                            $is_enabled_before = false;
                            if( $nx_type !== 'press_bar' && ! NX_CONSTANTS::is_pro() ) {
                                $is_enabled_before = NotificationX_Extension::is_enabled( $nx_type );
                                if( $is_enabled == true ) {
                                    $is_enabled_before = $is_enabled_before == true ? false : true;
                                }
                                $is_enabled_before = apply_filters('nx_enabled_disabled_item', $is_enabled_before);
                            }
                            $edit_with_elementor = false;
                            if( $nx_type === 'press_bar' ) {
                                $post_meta = isset( $settings->elementor_type_id ) ? $settings->elementor_type_id : false;
                                if( is_numeric( $post_meta ) && class_exists( '\Elementor\Plugin' ) ) {

                                    $documents = \Elementor\Plugin::$instance->documents->get( $post_meta );
                                    if( $documents ) {
                                        $edit_with_elementor = $documents->get_edit_url();
                                    }

                                }
                            }

                            $status = $single_nx->post_status;
                            if( $pagenow === 'admin.php' && isset( $_GET['page'] ) && $_GET['page'] === 'nx-admin' ) {
                                if( isset( $_GET['status'] ) && $_GET['status'] === 'trash' ) {
                                    $trash_page = true;
                                    $trashed = true;
                                    if( $status !== 'trash' ) {
                                        continue;
                                    }
                                    $trash_btn_title = __( 'Delete Permanently', 'notificationx' );
                                } elseif( isset( $_GET['status'] ) && $_GET['status'] === 'enabled' ){
                                    if( $status !== 'publish' || $is_enabled != 1 ) {
                                        continue;
                                    }
                                } elseif( isset( $_GET['status'] ) && $_GET['status'] === 'disabled' ){
                                    if( $status !== 'publish' || $is_enabled != 0 ) {
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
                                            <strong>
                                                <?php
                                                    if( ! $trashed ) echo '<a href="post.php?action=edit&post='. $idd .'">';
                                                    echo $single_nx->post_title;
                                                    if( ! $trashed ) echo '</a>';
                                                ?>
                                            </strong>
                                            <div class="nx-admin-title-actions">
                                                <?php if( ! $trash_page ) : ?>
                                                    <a class="nx-admin-title-edit" href="post.php?action=edit&post=<?php echo $idd; ?>"><?php _e( 'Edit', 'notificationx' ); ?></a>
                                                    <?php if( $edit_with_elementor !== false ) : ?>
                                                    <a class="nx-admin-title-edit" href="<?php echo $edit_with_elementor; ?>"><?php _e( 'Edit with Elementor', 'notificationx' ); ?></a>
                                                    <?php endif; ?>
                                                    <a class="nx-admin-title-duplicate" href="<?php echo esc_url( $duplicate_url ); ?>"><?php _e( 'Duplicate', 'notificationx' ); ?></a>
                                                    <?php if( $settings->display_type != 'press_bar' && ! is_null( $extension ) && method_exists( $extension, 'get_notification_ready' ) ) : ?>
                                                        <a class="nx-admin-title-regenerate" href="<?php echo esc_url( $regenerate_url ); ?>"><?php _e( 'Re Generate', 'notificationx' ); ?></a>
                                                    <?php endif; ?>
                                                <?php do_action('nx_admin_title_actions', $idd); else :  ?>
                                                    <a class="nx-admin-title-restore" href="<?php echo wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $idd ) ), 'untrash-post_' . $idd ); ?>"><?php _e( 'Restore', 'notificationx' ); ?></a>
                                                <?php endif; ?>
                                                <a class="nx-admin-title-trash" href="<?php echo get_delete_post_link( $idd, '', $trashed ); ?>"><?php echo $trash_btn_title; ?></a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="nx-admin-preview">
                                            <?php
                                                $theme_preview = NX_CONSTANTS::themeSource( $theme_name, $settings->display_type );
                                                if( is_array( $theme_preview ) ) {
                                                    $theme_preview = $theme_preview['source'];
                                                }
                                                if( ! empty( $theme_preview ) ) :
                                            ?>
                                            <img width="250px" src="<?php echo $theme_preview; ?>" alt="<?php echo $single_nx->post_title; ?>">
                                            <?php $theme_preview = ''; endif;?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="nx-admin-status">
                                            <span class="nx-admin-status-title nxast-enable <?php echo $is_enabled ? 'active' : ''; ?>"><?php echo _e( 'Enabled', 'notificationx' ); ?></span>
                                            <span class="nx-admin-status-title nxast-disable <?php echo $is_enabled ? '' : 'active'; ?>"><?php echo _e( 'Disabled', 'notificationx' ); ?></span>
                                            <input type="checkbox" id="nx-toggle-<?php echo $idd; ?>" name="_nx_meta_active_check" <?php echo $is_enabled ? 'checked="checked"' : ''; ?>>
                                            <?php
                                            if( $is_enabled_before ) : ?>
                                                <label data-swal="true" data-post="<?php echo $idd; ?>" data-nonce="<?php echo wp_create_nonce('notificationx_status_nonce'); ?>" for="nx-toggle-disable-<?php echo $idd; ?>"></label>
                                            <?php else :  ?>
                                                <label data-swal="false" data-post="<?php echo $idd; ?>" data-nonce="<?php echo wp_create_nonce('notificationx_status_nonce'); ?>" for="nx-toggle-<?php echo $idd; ?>"></label>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="nx-admin-type"><?php echo is_array( $type ) ? $type['source'] : $type; ?></div>
                                    </td>
                                    <td>
                                        <div class="nx-admin-stats"><?php $this->get_stats( $idd ); ?></div>
                                    </td>
                                    <td>
                                        <div class="nx-admin-date">
                                            <?php
                                                if( $status === 'publish' ) {
                                                    echo '<span class="nx-admin-publish-status">' . _e('Published', 'notificationx') . '</span><br><span class="nx-admin-publish-date">' . $single_nx->post_date. '</span>';
                                                }
                                                if( $status === 'trash' ) {
                                                    echo '<span class="nx-admin-publish-status">' . _e('Last Modified', 'notificationx') . '</span><br><span class="nx-admin-publish-date">' . $single_nx->post_date . '</span>';
                                                }
                                            ?>
                                        </div>
                                    </td>
                                </tr>

                            <?php
                        endforeach;
                    endif;

                    if( ! $total_notificationx && ! $trashed ) {
                        echo '<tr><td colspan="6"><div class="nx-admin-not-found"><p>'. __('No NotificationX is found.', 'notificationx') .'</p></div></td></tr>';
                    }
                ?>
                <!-- <tr><td><p>No NotificationX is found.</p></td></tr> -->
            </tbody>
        </table>
    </div>
    <?php
    /**
     * Pagination
     * @since 1.2.6
     */
    if( $total_page > 1 ) : ?>
        <div class="nx-admin-items-pagination">
            <ul>
                <?php
                    if( $total_page > 1 ) {
                        if( $paged > 1 ) {
                            echo '<li class="nx-prev-page"><a href="'. $pagination_current_url .'&paged='. ($paged - 1) .'"><span class="dashicons dashicons-arrow-left-alt2"></span></a></li>';
                        }
                        for( $i = 1; $i <= $total_page; $i++ ) {
                            $active_page = $paged == $i ? 'class="nx-current-page"' : '';
                            echo '<li '. $active_page .'><a href="'. $pagination_current_url .'&paged='. $i .'">'. $i .'</a></li>';
                        }
                        if( $total_page > $paged ) {
                            echo '<li class="nx-next-page"><a href="'. $pagination_current_url .'&paged='. ($paged + 1) .'"><span class="dashicons dashicons-arrow-right-alt2"></span></a></li>';
                        }
                    }
                ?>
            </ul>
        </div>
    <?php endif; ?>
</div>