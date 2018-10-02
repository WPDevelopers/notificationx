<?php

class FomoPress_Custom_Extension extends FomoPress_Extension {
    /**
     *  Type of notification.
     *
     * @var string
     */
    public $type = 'custom';
    public $template = 'custom_template';
    /**
     * An array of all notifications
     *
     * @var [type]
     */
    protected $notifications = [];

    public function __construct() {
        parent::__construct();
        $this->notifications = $this->get_notifications( $this->type );

    }
    /**
     * This functions is hooked
     * 
     * @hooked fomopress_public_action
     *
     * @return void
     */
    public function public_actions( $loader ){
        if( ! $this->is_created( $this->type ) ) {
            return;
        }
        
        // make you actions here...
    }
    public function admin_actions( $loader ){
        if( ! $this->is_created( $this->type ) ) {
            return;
        }
        
        var_dump( self::$active_items );
        die;
    }
    
    /**
     * This function is responsible for making the notification ready for first time we make the notification.
     *
     * @param string $type
     * @param array $data
     * @return void
     */
    // public function get_notification_ready( $type, $data = array() ){
    //     if( ! class_exists( 'WooCommerce' ) ) {
    //         return;
    //     }
    //     if( $this->type === $type ) {
    //         if( ! is_null( $orders = $this->get_orders( $data ) ) ) {
    //             $this->save( $this->type, $orders );
    //         }
    //     }
    // }


}

/**
 * Register the extension
 */
fomopress_register_extension( 'FomoPress_Custom_Extension' );