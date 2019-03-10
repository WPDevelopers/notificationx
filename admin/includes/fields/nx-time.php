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

<div class="nx-countdown-inputs">
    <div class="nx-countdown-input nx-days">
        <input 
            placeholder="Days" min="0" max="31" 
            id="<?php echo $name; ?>[days]" 
            type="number" name="<?php echo $name; ?>[days]" value="<?php echo $value['days']; ?>">
    </div>
    <div class="nx-countdown-input nx-hours">
        <input 
            placeholder="Hours" min="0" max="23" 
            id="<?php echo $name; ?>[hours]" 
            type="number" name="<?php echo $name; ?>[hours]" value="<?php echo $value['hours']; ?>">
    </div>
    <div class="nx-countdown-input nx-minutes">
        <input 
            placeholder="Minutes" min="0" max="59" 
            id="<?php echo $name; ?>[minutes]" 
            type="number" name="<?php echo $name; ?>[minutes]" value="<?php echo $value['minutes']; ?>">
        </div>
    <div class="nx-countdown-input nx-seconds">
        <input 
            placeholder="Seconds" min="0" max="59" 
            id="<?php echo $name; ?>[seconds]" 
            type="number" name="<?php echo $name; ?>[seconds]" value="<?php echo $value['seconds']; ?>">
    </div>
</div>