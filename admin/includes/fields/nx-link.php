<?php
    $link = isset( $field['link'] ) && !empty($field['link']) ? $field['link'] : '#';
    $label = isset( $field['link_text'] ) && !empty($field['link_text']) ? $field['link_text'] : 'Link';
    $classes = isset( $field['css_class'] ) && !empty($field['css_class']) ? $field['css_class'] : '';
    $data_atts = '';
    if(isset( $field['data_atts'] ) && !empty($field['data_atts'])){
        foreach ($field['data_atts'] as $key => $value){
            $data_atts .= $key . '="' . $value .'"';
        }
    }
?>

<a href="<?php echo $link?>" class="btn-settings <?php echo $classes; ?>" <?php echo $data_atts?>><?php echo $label?></a>
