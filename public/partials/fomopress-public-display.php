<?php

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://wpdeveloper.net
 * @since      1.0.0
 *
 * @package    FomoPress
 * @subpackage FomoPress/public/partials
 */
$type = $settings->display_type;
$data = apply_filters( 'fomopress_fields_data', $data );

if( ! empty( $data ) ) {
    foreach( $data as $key => $value ) {
        $value = FomoPress_Helper::sortBy( $value, $key );
        foreach( $value as $single ) {
            $unique_id = uniqid( 'fomopress-notification-' );
            if( $key === 'comments' ) {
?>
    <div id="<?php echo esc_attr( $unique_id ); ?>" class="fomopress-notification fomopress-notification-<?php echo $id; ?>">
        <h3><a href="<?php echo isset( $single['author_link'] ) ? esc_url( $single['author_link'] ) : ''; ?>"><?php echo $single['author']; ?></a></h3>
        posted a comment on <a href="<?php echo esc_url( $single['post_link'] ); ?>"><?php echo $single['post_title']; ?></a>
    </div>
<?php
            }
        }
    }
}

