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

$current_tab = get_post_meta( $post->ID, 'fomopress_current_tab', true );
if( ! $current_tab ) {
    $current_tab = 'source_tab';
}
?>

<div class="fomopress-metabox-wrapper">

    <div class="fomopress-meta-tab-menu">
        <ul>
            <?php 
                $i = 1;
                foreach( $tabs as $id => $tab ) {
                    $active = $current_tab === $id ? ' active ' : '';
                    echo '<li data-tab="'. $id .'" class="' . $active . '">'. $tab['title'] .'</li>';
                }
            ?>
        </ul>
    </div>

    <div class="fomopress-meta-tab-contents">
        <input id="fomopress_current_tab" type="hidden" name="fomopress_current_tab" value="<?php echo $current_tab; ?>">
        <?php 
            foreach( $tabs as $id => $tab  ){
                $active = $current_tab === $id ? ' active ' : '';
                $sections = FomoPress_Helper::sorter( $tab['sections'], 'priority', 'ASC' );
                ?>
                <div id="fomopress-<?php echo $id ?>" class="fomopress-meta-tab-content <?php echo $active; ?>">
                <?php 
                    foreach( $sections as $sec_id => $section ) {
                        $fields = FomoPress_Helper::sorter( $section['fields'], 'priority', 'ASC' );
                        if( ! empty( $fields ) )  :
                    ?>
                        <div id="fomopress-meta-section-<?php echo $sec_id; ?>" class="fomopress-metabox-section">
                            <h2 class="fomopress-metabox-section-title">
                                <?php echo $section['title']; ?>    
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
                    }
                ?>
                </div>
                <?php
            }
        ?>
    </div>
</div>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
