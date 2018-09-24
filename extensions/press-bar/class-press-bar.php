<?php

class FomoPress_PressBar_Extension extends FomoPress_Extension {
    /**
     *  Type of notification.
     *
     * @var string
     */
    protected $type = 'press_bar';
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
     * @hooked fomopress_admin_action
     *
     * @return void
     */
    public function admin_actions( $loader ){
        
    }

    public function display_type( $options ){
        $options[ 'hide' ][ 'press_bar' ] = [ 
            'sections' => [ 'behaviour' ],
            'fields'   => [ 'notification_template', 'display_for', 'delay_between', 'delay_before' ]
        ];
        $options[ 'toggle' ][ 'press_bar' ] = [ 
            'fields'   => [ 'sticky_bar', 'pressbar_position' ]
        ];

        return $options;
    }
    /**
     * Undocumented function
     *
     * @return void
     */
    public static function display( $settings ){
        require plugin_dir_path( __FILE__ ) . 'press-bar-frontend.php';
    }
    /**
     * It will generate and save a notification
     * when orders are placed.
     *
     * @param int $item_id
     * @param  WC_Order_Item_Product $item
     * @param int $order_id
     * @return void
     */
    public function save_new_orders( $item_id,  $item,  $order_id ){        
        

        $this->save( $this->type, $this->notifications );
    }
}

/**
 * Register the extension
 */
fomopress_register_extension( 'FomoPress_PressBar_Extension' );