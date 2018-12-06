<?php
/**
 * This Class is responsible for 
 * Easy Digital Downloads 
 * Conversions
 */
class FomoPress_EDD_Extension extends FomoPress_Extension {
    /**
     *  Type of notification.
     *
     * @var string
     */
    public $type = 'edd';
    public $template = 'edd_template';
    protected $ordered_products = [];
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
     * Main Screen Hooks
     */
    public function init_hooks(){
        add_filter( 'fomopress_metabox_tabs', array( $this, 'add_fields' ) );
        add_filter( 'fomopress_display_types_hide_data', array( $this, 'hide_fields' ) );
        add_filter( 'fomopress_conversion_from', array( $this, 'toggle_fields' ) );
    }
    /**
     * Builder Hooks
     */
    public function init_builder_hooks(){
        add_filter( 'fomopress_builder_tabs', array( $this, 'add_builder_fields' ) );
        add_filter( 'fomopress_display_types_hide_data', array( $this, 'hide_builder_fields' ) );
        add_filter( 'fomopress_builder_tabs', array( $this, 'builder_toggle_fields' ) );
    }
    /**
     * Needed Fields
     */
    private function init_fields(){
        $fields = [];

        if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
            $fields['has_no_edd'] = array(
                'type'     => 'message',
                'message'    => __('You have to install Easy Digital Downloads plugin first.', 'fomopress'),
                'priority' => 0,
            );
        }

        $fields['edd_template'] = array(
            'type'     => 'template',
            'label'    => __('Notification Template' , 'fomopress'),
            'priority' => 90,
            'defaults' => [
                __('{{name}} recently purchased', 'fomopress'), '{{title}}', '{{time}}'
            ],
            'variables' => [
                '{{name}}', '{{title}}', '{{time}}'
            ],
        );

        return $fields;
    }
    /**
     * This function is responsible for adding fields in main screen
     *
     * @param array $options
     * @return void
     */
    public function add_fields( $options ){
        $fields = $this->init_fields();

        foreach ( $fields as $name => $field ) {
            if( $name === 'has_no_edd' ) {
                $options[ 'source_tab' ]['sections']['config']['fields'][ $name ] = $field;
            }
            if( $name === 'edd_template' ) {
                $options[ 'content_tab' ]['sections']['content_config']['fields'][ $name ] = $field;
            }
        }
        return $options;
    }
    /**
     * This function is responsible for adding fields in builder
     *
     * @param array $options
     * @return void
     */
    public function add_builder_fields( $options ){
        $fields = $this->init_fields();
        unset( $fields[ $this->template ] );
        
        foreach ( $fields as $name => $field ) {
            $options[ 'source_tab' ]['sections']['config']['fields'][ $name ] = $field;
        }
        return $options;
    }
    /**
     * This function is responsible for hide fields in main screen
     *
     * @param array $options
     * @return void
     */
    public function hide_fields( $options ) {
        $fields = $this->init_fields();
        foreach ( $fields as $name => $field ) {
            foreach( $options as $opt_key => $opt_value ) {
                $options[ $opt_key ][ 'fields' ][] = $name;
            }
        }
        return $options;
    }
    /**
     * This function is reponsible for hide fields on toggle
     * in builder
     *
     * @param array $options
     * @return void
     */
    public function hide_builder_fields( $options ) {
        $fields = $this->init_fields();
        foreach ( $fields as $name => $field ) {
            foreach( $options as $opt_key => $opt_value ) {
                $options[ $opt_key ][ 'fields' ][] = $name;
            }
        }
        return $options;
    }
    /**
     * This functions is hooked
     * @hooked fomopress_public_action
     *
     * @return void
     */
    public function public_actions(){
        if( ! $this->is_created( $this->type ) ) {
            return;
        }

        // some code will be here
    }
    /**
     * This functions is hooked
     * @hooked fomopress_admin_action
     * 
     * @return void
     */
    public function admin_actions(){
        if( ! $this->is_created( $this->type ) ) {
            return;
        }
        add_action( 'edd_complete_purchase', array( $this, 'update_notifications' ) );
    }
    /**
     * Some toggleData & hideData manipulation.
     *
     * @param array $options
     * @return void
     */
    public function toggle_fields( $options ) {
        $fields = array_keys( $this->init_fields() );
        $fields = array_merge( [ 'show_product_image' ], $fields );

        $options['toggle'][ $this->type ]['fields'] = $fields;
        $options['toggle'][ $this->type ]['sections'] = [ 'image' ];
        return $options;
    }
    /**
     * This function is responsible for builder fields
     *
     * @param array $options
     * @return void
     */
    public function builder_toggle_fields( $options ) {
        $fields = $this->init_fields();
        unset( $fields[ $this->template ] );
        $options['source_tab']['sections']['config']['fields']['conversion_from']['toggle'][ $this->type ]['fields'] = array_keys( $fields );
        return $options;
    }
    /**
     * This function is responsible for making the notification ready for first time we make the notification.
     *
     * @param string $type
     * @param array $data
     * @return void
     */
    public function get_notification_ready( $type, $data = array() ){
        if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
            return;
        }

        if( $this->type === $type ) {
            $orders = $this->get_orders( $data );
            if( is_array( $orders ) ) {
                $this->save( $this->type, $orders );
            }
        }
    }
    /**
     * This function is responsible for get all payments
     *
     * @param int $days
     * @param int $amount
     * @return array
     */
    public function get_payments( $days, $amount ) {
        $date 		= '-' . intval( $days ) . ' days';
        $start_date = strtotime( $date );

        $amount = $amount > 0 ? $amount : -1;

        $args = array(
            'number'    => $amount,
            'status'    => array('publish'),
            'date_query'	=> array(
                'after'			=> date( 'Y-m-d', $start_date )
            )
        );

        return edd_get_payments( $args );
    }
    /**
     * This function is responsible for update notification
     *
     * @param int $payment_id
     * @return void
     */
    public function update_notifications( $payment_id ){
        if( count( $this->notifications ) === $this->cache_limit ) {
            $sorted_data = FomoPress_Helper::sorter( $this->notifications, 'timestamp' );
            array_pop( $sorted_data );
            $this->notifications = $sorted_data;
        }
        $this->ordered_products = $this->notifications;
        $this->ordered_products( $payment_id );
        $this->save( $this->type, $this->ordered_products );
    }
    /**
     * Get all the orders from database using a date query
     * for limitation.
     *
     * @param array $data
     * @return void
     */
    public function get_orders( $data ) {
        if( empty( $data ) ) return;
        $days     = $data['_fomopress_display_from'];
        $amount   = $data['_fomopress_display_last'];
        $payments = $this->get_payments( $days, $amount );
      
        if( is_array( $payments ) && ! empty( $payments ) ) {
            foreach( $payments as $payment ) {
                $this->ordered_products( $payment->ID );
            }
        }

        return $this->ordered_products;
    }
    /**
     * This function is responsible for 
     * making ready the product notifications array
     *
     * @param int $payment_id
     * @return void
     */
    protected function ordered_products( $payment_id ){
        $payment_meta = edd_get_payment_meta( $payment_id );
        $payment_key  = $payment_meta['key'];
        $date         = $payment_meta['date'];
        $time['timestamp']  = strtotime( $date );
        $buyer        = $this->buyer( $payment_meta['user_info'] );

        $cart_items = edd_get_payment_meta_cart_details( $payment_id );                
        if( is_array( $cart_items ) && ! empty( $cart_items ) ) {
            foreach( $cart_items as $item ) {
                $product_data = $this->product_data( $item );
                $this->ordered_products[ $payment_key . '-' . $item['id'] ] = array_merge( $buyer, $product_data, $time );
            }
        }
    }
    /**
     * This function is responsible for 
     * making ready the product array
     *
     * @param array $item
     * @return array
     */
    protected function product_data( $item ){
        if( empty( $item ) ) return;
        $data = [];
        $data['product_id'] = $item['id'];
        $data['title']      = $item['name'];
        $data['link']       = get_permalink( $item['id'] );
        return $data;
    }
    /**
     * This function is responsible 
     * for making buyer array ready
     *
     * @param array $user_info
     * @return void
     */
    protected function buyer( $user_info ) {
        if( empty( $user_info ) ) return;
        $buyer_data = [];
        $buyer_data['name'] = $user_info['first_name'] . ' ' . $user_info['last_name'];
        if( $user_info['id'] ) {
            $user = new WP_User( $user_info['id'] );
            if( $user->exists() ) {
                $buyer_data['user_id'] = $user->ID;
                $buyer_data['name']    = $user->display_name;
            }
        }
        return $buyer_data;
    }
    /**
     * This function is responsible for making ready the front of notifications
     *
     * @param array $data
     * @param boolean $settings
     * @param string $template
     * @return void
     */
    public function frontend_html( $data = [], $settings = false, $template = '' ){
        if( class_exists( 'Easy_Digital_Downloads' ) ) {
            return parent::frontend_html( $data, $settings, $template );
        }
    }
}
