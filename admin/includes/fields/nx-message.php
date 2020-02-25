<?php 
    $message = isset( $field['message'] ) ? $field['message'] : '';
    $class = isset( $field['class'] ) ? $field['class'] : '';
?>

<div class="nx-info-message <?php echo $class; ?>">
    <?php echo $message; ?>
</div>