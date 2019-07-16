<?php
/**
 * This class responsible for database work
 * using wordpress functionality 
 * get_option and update_option.
 */
class NotificationX_DB {
    /**
     * Get all the notification 
     * saved in options table.
     * @return array
     */
    public static function get_notifications(){
        $notifications = get_option( 'notificationx_data', true );
        return is_array( $notifications ) ? $notifications : [];
    }
    /**
     * Update notifications.
     * @param array $new_value
     * @return boolean
     */
    public static function update_notifications( $new_value ){
        return update_option( 'notificationx_data', $new_value );
    }
    /**
     * Get all settings value from options table.
     * or, get settings for a specific $key
     *
     * @param string $name
     * @return array
     */
    public static function get_settings( $name = '', $key = '' ){
        if( ! empty( $key ) ) {
            $settings = get_option( $key );
            return $settings;
        }
        $settings = get_option( 'notificationx_settings', true );
        
        if( ! empty( $name ) && isset( $settings[ $name ] ) ) {
            return $settings[ $name ];
        }
        
        if( ! empty( $name ) && ! isset( $settings[ $name ] ) ) {
            return '';
        }

        return is_array( $settings ) ? $settings : [];
    }
    /**
     * Update settings 
     * @param array $value
     * @return boolean
     */
    public static function update_settings( $value, $key = '' ){
        if( ! empty( $key ) ) {
            return update_option( $key, $value );
        }
        return update_option( 'notificationx_settings', $value );
    }
}