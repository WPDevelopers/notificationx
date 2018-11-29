<?php 

    if( ! is_array( $value ) ) {
        $value = [
            'days'    => '',
            'hours'   => '',
            'minutes' => '',
            'seconds' => '',
        ];
    }

?>

<div class="fomopress-countdown-inputs">
    <div class="fomopress-countdown-input fomopress-days">
        <input 
            placeholder="Days" step="1" min="0" max="31" 
            id="<?php echo $name; ?>[days]" 
            type="number" name="<?php echo $name; ?>[days]" value="<?php echo $value['days']; ?>">
    </div>
    <div class="fomopress-countdown-input fomopress-hours">
        <input 
            placeholder="Hours" step="1" min="0" max="23" 
            id="<?php echo $name; ?>[hours]" 
            type="number" name="<?php echo $name; ?>[hours]" value="<?php echo $value['hours']; ?>">
    </div>
    <div class="fomopress-countdown-input fomopress-minutes">
        <input 
            placeholder="Minutes" step="5" min="0" max="59" 
            id="<?php echo $name; ?>[minutes]" 
            type="number" name="<?php echo $name; ?>[minutes]" value="<?php echo $value['minutes']; ?>">
        </div>
    <div class="fomopress-countdown-input fomopress-seconds">
        <input 
            placeholder="Seconds" step="10" min="0" max="59" 
            id="<?php echo $name; ?>[seconds]" 
            type="number" name="<?php echo $name; ?>[seconds]" value="<?php echo $value['seconds']; ?>">
    </div>
</div>