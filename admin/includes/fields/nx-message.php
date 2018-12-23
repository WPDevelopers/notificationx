<?php 
    $message = isset( $field['message'] ) ? $field['message'] : '';
?>

<div class="nx-info-message">
    <?php echo esc_html_e( $message ); ?>
</div>