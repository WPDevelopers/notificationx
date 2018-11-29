<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://wpdeveloper.net
 * @since      1.0.0
 *
 * @package    FomoPress
 * @subpackage FomoPress/admin/partials
 */

$current_tab = get_post_meta( $post->ID, '_fomopress_current_tab', true );
if( ! $current_tab ) {
    $current_tab = 'source_tab';
}
$totaltabs = count( $tabs );
$position = intval( array_search( $current_tab, array_keys( $tabs) ) + 1 );
?>
<div class="fomopress-metabox-wrapper">
    <div class="fomopress-meta-tab-menu">
        <ul>
            <?php 
                $tid = 1;
                foreach( $tabs as $id => $tab ) {
                    $active = $current_tab === $id ? ' active' : '';
                    $class = isset( $tab['icon'] ) ? ' fomopress-has-icon' : '';
                    $class .= $active;
                    if( $position > $tid ){
                        $class .= ' fp-complete';
                    }
                    ?>
                        <li data-tabid="<?php echo $tid++; ?>" class="<?php echo $class; ?>" data-tab="<?php echo $id; ?>">
                            <?php if( isset( $tab['icon'] ) ) : ?>
                                <span class="fomopress-menu-icon">
                                    <img src="<?php echo FOMOPRESS_ADMIN_URL . 'assets/img/icons/' . $tab['icon']; ?>" alt="<?php echo $tab['title']; ?>">
                                </span>
                            <?php endif; ?>
                            <span class="fomopress-menu-title"><?php echo $tab['title']; ?></span>
                        </li>
                    <?php
                }
            ?>
        </ul>
    </div>

    <div class="fomopress-meta-tab-contents fomopress-tab-content-wrapper" data-totaltab="<?php echo $totaltabs; ?>">
        <input id="fomopress_current_tab" type="hidden" name="fomopress_current_tab" value="<?php echo $current_tab; ?>">
        <?php 
            $tabid = 1;
            foreach( $tabs as $id => $tab  ){
                do_action( 'fomopress_before_metabox_tab', $id, $tab );
                $active = $current_tab === $id ? ' active ' : '';
                $sections = FomoPress_Helper::sorter( $tab['sections'], 'priority', 'ASC' );
                ?>
                <div id="fomopress-<?php echo $id ?>" class="fomopress-meta-tab-content <?php echo $active; ?>">
                <?php 
                    foreach( $sections as $sec_id => $section ) {
                        do_action( 'fomopress_before_metabox_tab_section', $sec_id, $id, $section );
                        if( isset( $section['fields'] ) ) :
                            $fields = FomoPress_Helper::sorter( $section['fields'], 'priority', 'ASC' );
                            if( ! empty( $fields ) )  :
                        ?>
                            <div id="fomopress-meta-section-<?php echo $sec_id; ?>" class="fomopress-metabox-section">
                                <h2 class="fomopress-metabox-section-title">
                                    <?php 
                                        echo $section['title']; 
                                        if( isset( $section['reset'] ) && $section['reset'] ) {
                                            echo '<div class="fomopress-section-reset" data-tooltip="Reset"><span class="dashicons dashicons-image-rotate"></span></div>';
                                        }
                                    ?>
                                </h2>
                                <table>
                                    <?php 
                                        foreach( $fields as $key => $field ) {
                                            FomoPress_MetaBox::render_meta_field( $key, $field );
                                        }
                                    ?>
                                </table>
                            </div>
                        <?php
                            endif;
                        endif;
                        do_action( 'fomopress_after_metabox_tab_section', $sec_id, $id, $section );
                    }
                ?>
                <button class="fomopress-meta-next" data-tab="<?php echo $id; ?>" data-tabid="<?php echo ++$tabid; ?>">
                    <?php
                        if( $totaltabs < $tabid ) {
                            _e( 'Publish', 'fomopress' );
                        } else {
                            _e( 'Next', 'fomopress' );
                        }
                    ?>
                </button>
                </div>
                <?php
                do_action( 'fomopress_after_metabox_tab', $id, $tab );
            }
        ?>
    </div>
</div>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
