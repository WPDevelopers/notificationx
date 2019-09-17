<?php
    $link = isset( $field['link'] ) && !empty($field['link']) ? $field['link'] : '#';
    $label = isset( $field['link_text'] ) && !empty($field['link_text']) ? $field['link_text'] : 'Link';
    $classes = isset( $field['css_class'] ) && !empty($field['css_class']) ? $field['css_class'] : '';
?>

<a href="<?php echo $link?>" class="button-primary <?php echo $classes; ?>"><?php echo $label?></a>