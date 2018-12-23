<?php 
    $image_url = $image_id = '';
    if( isset( $value['url'] ) ) {
        $image_url = $value['url'];
    }
    if( isset( $value['id'] ) ) {
        $image_id = $value['id'];
    }
?>

<div class="nx-media-field-wrapper">
    <div class="nx-thumb-container <?php echo $image_url == '' ? '' : 'nx-has-thumb'; ?>">
        <?php 
            if( $image_url ) {
                echo '<img src="'. esc_url( $image_url ) .'">';
            }
        ?>
    </div>
    <div class="nx-media-content">
        <input class="nx-media-url" type="text" name="<?php echo $name; ?>[url]" value="<?php echo esc_url( $image_url ); ?>">
        <input class="nx-media-id" type="hidden" name="<?php echo $name; ?>[id]" value="<?php echo esc_attr( $image_id ); ?>">
        <button class="nx-media-button nx-media-remove-button <?php echo $image_url == '' ? 'hidden' : ''; ?>">Remove</button>
    </div>
    <button class="nx-media-button nx-media-upload-button <?php echo $image_url == '' ? '' : 'hidden'; ?>">Upload</button>
</div>