<?php 
    $image_url = $image_id = '';
    if( isset( $value['url'] ) ) {
        $image_url = $value['url'];
    }
    if( isset( $value['id'] ) ) {
        $image_id = $value['id'];
    }
?>

<div class="fomopress-media-field-wrapper">
    <div class="fomopress-thumb-container <?php echo $image_url == '' ? '' : 'fomopress-has-thumb'; ?>">
        <?php 
            if( $image_url ) {
                echo '<img src="'. esc_url( $image_url ) .'">';
            }
        ?>
    </div>
    <div class="fomopress-media-content">
        <input class="fomopress-media-url" type="text" name="<?php echo $name; ?>[url]" value="<?php echo esc_url( $image_url ); ?>">
        <input class="fomopress-media-id" type="hidden" name="<?php echo $name; ?>[id]" value="<?php echo esc_attr( $image_id ); ?>">
        <button class="fomopress-media-button fomopress-media-remove-button <?php echo $image_url == '' ? 'hidden' : ''; ?>">Remove</button>
    </div>
    <button class="fomopress-media-button fomopress-media-upload-button <?php echo $image_url == '' ? '' : 'hidden'; ?>">Upload</button>
</div>