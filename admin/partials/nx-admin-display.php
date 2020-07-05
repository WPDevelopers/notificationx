<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://wpdeveloper.net
 * @since      1.0.0
 *
 * @package    NotificationX
 * @subpackage NotificationX/admin/partials
 */

$current_tab = get_post_meta( $post->ID, '_nx_builder_current_tab', true );
$current_tab = empty( $current_tab ) ? true : $current_tab;
if( ! $current_tab == array_search( $current_tab, array_keys( $tabs) ) ) {
    $current_tab = 'source_tab';
}
$totaltabs = count( $tabs );
$position = intval( array_search( $current_tab, array_keys( $tabs) ) + 1 );
$active_modules = NotificationX_Helper::modules_in_action( NotificationX_DB::get_settings('nx_modules') );
$flag = empty( $active_modules ) ? true : false;
if( ! empty( $active_modules ) ) {
    foreach( $active_modules as $module ) {
        if( $module == true ) {
            $flag = true;
        }
    }
}
?>
<div class="notificationx-metabox-wrapper">
    <?php if( $flag ) : ?>
    <div class="nx-metatab-menu">
        <ul>
            <?php 
                $tid = 1;
                $tabids = array();
                foreach( $tabs as $id => $tab ) {
                    $tabids[] = $id;
                    $active = $current_tab === $id ? ' active' : '';
                    $class = isset( $tab['icon'] ) ? ' nx-has-icon' : '';
                    $class .= $active;
                    if( $position > $tid ){
                        $class .= ' nx-complete';
                    }
                    ?>
                        <li data-tabid="<?php echo $tid++; ?>" class="<?php echo $class; ?>" data-tab="<?php echo $id; ?>">
                            <?php if( isset( $tab['icon'] ) ) : ?>
                                <span class="nx-menu-icon">
                                    <img src="<?php echo NOTIFICATIONX_ADMIN_URL . 'assets/img/icons/' . $tab['icon']; ?>" alt="<?php echo $tab['title']; ?>">
                                </span>
                            <?php endif; ?>
                            <span class="nx-menu-title"><?php echo $tab['title']; ?></span>
                        </li>
                    <?php
                }
            ?>
        </ul>
    </div>

    <div class="nx-meta-contents nx-metatab-wrapper" data-totaltab="<?php echo $totaltabs; ?>">
        <div class="nx-preloader">
            <img src="<?php echo NOTIFICATIONX_ADMIN_URL . 'assets/img/nx-menu-icon-colored-large.png'; ?>" alt="NotificationX">
        </div>
        <div class="nx-metatab-inner-wrapper">
            <input id="nx_builder_current_tab" type="hidden" name="nx_builder_current_tab" value="<?php echo $current_tab; ?>">
            <?php 
                $tabid = 1;
                foreach( $tabs as $id => $tab  ){
                    do_action( 'nx_before_metabox_tab', $id, $tab );
                    $active = $current_tab === $id ? ' active ' : '';
                    $sections = NotificationX_Helper::sorter( $tab['sections'], 'priority', 'ASC' );
                    ?>
                    <div id="nx-<?php echo $id ?>" class="nx-metatab-content <?php echo $active; ?>">
                    <?php 
                        foreach( $sections as $sec_id => $section ) {
                            do_action( 'nx_before_metabox_tab_section', $sec_id, $id, $section );
                            if( isset( $section['fields'] ) ) :
                                $fields = NotificationX_Helper::sorter( $section['fields'], 'priority', 'ASC' );
                                if( ! empty( $fields ) )  :
                            ?>
                                <div id="nx-meta-section-<?php echo $sec_id; ?>" class="nx-meta-section <?php echo 'nx-' . $sec_id; ?>">
                                    <?php 
                                        if( isset( $section['is_pro'] ) && $section['is_pro'] ) {
                                            ?>
                                    <div class="nx-stats-pro-tease">
                                        <a href="https://wpdeveloper.net/in/notificationx">
                                            <p>Get PRO to Unlock</p>
                                        </a>
                                    </div>
                                            <?php
                                        }
                                    ?>
                                    <?php 
                                        if( isset( $section['title'] ) ) {
                                            ?>
                                                <h2 class="nx-meta-section-title">
                                                    <?php 
                                                        echo $section['title']; 
                                                        if( isset( $section['reset'] ) && $section['reset'] ) {
                                                            echo '<div class="nx-section-reset" data-tooltip="Reset"><span class="dashicons dashicons-image-rotate"></span></div>';
                                                        }
                                                    ?>
                                                </h2>
                                            <?php
                                        }
                                    ?>
                                    <table>
                                        <?php 
                                            foreach( $fields as $key => $field ) {
                                                NotificationX_MetaBox::render_meta_field( $key, $field );
                                            }
                                        ?>
                                    </table>
                                </div>
                            <?php
                                endif;
                            endif;
                            do_action( 'nx_after_metabox_tab_section', $sec_id, $id, $section );
                        }
                    ?>
                    <button class="nx-meta-next" data-tab="<?php echo isset( $tabids[ $tabid ] ) ? $tabids[ $tabid ] : ''; ?>" data-tabid="<?php echo ++$tabid; ?>">
                        <?php
                            if( $totaltabs < $tabid ) {
                                _e( 'Publish', 'notificationx' );
                            } else {
                                _e( 'Next', 'notificationx' );
                            }
                        ?>
                    </button>
                    </div>
                    <?php
                    do_action( 'nx_after_metabox_tab', $id, $tab );
                }
            ?>
        </div>
    </div>
    <?php else : ?>
    <style> #publish.button.button-primary.button-large {display: none;}</style>
    <div class="nx-no-module-on">
        <p><img src="<?php echo NOTIFICATIONX_URL; ?>/admin/assets/img/logo.svg" alt="NotificationX"><?php echo sprintf( '%s <a href="%s">%s</a>', __( 'Make sure you have module on from your NotificationX', 'notificationx' ), esc_url( admin_url('admin.php?page=nx-settings') ), __( 'Settings', 'notificationx' ) ); ?></p>
    </div>
    <?php endif; ?>
</div>