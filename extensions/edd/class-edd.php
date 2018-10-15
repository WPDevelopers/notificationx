<?php

class FomoPress_EDD_Extension extends FomoPress_Extension {
    /**
     *  Type of notification.
     *
     * @var string
     */
    public $type = 'edd';
    public $template = 'edd_template';
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

        // dump( $this->get_payments(2, 30) );
        
    }
    public function admin_actions( $loader ){
        if( ! $this->is_created( $this->type ) ) {
            return;
        }

        add_action( 'edd_complete_purchase', array( $this, 'get_orders' ) );
    }

    public function source_tab_section( $options ){
        $options['config']['fields']['display_type']['hide']['comments']['fields'][] = 'edd_template';
        $options['config']['fields']['display_type']['hide']['comments']['fields'][] = 'show_product_image';
        
        $options['config']['fields']['display_type']['hide']['press_bar']['fields'][] = 'edd_template';

        if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
            $options['config']['fields']['has_no_edd'] = array(
                'type'     => 'message',
                'message'    => __('You have to install Easy Digital Downloads plugin first.', 'fomopress'),
                'priority' => 0,
            );
        }

        return $options;
    }

    /**
     * Some extra field on the fly.
     * 
     * @param array $options
     * @return array
     */

    public function content_tab_section( $options ){
        $options[ 'content_config' ][ 'fields' ]['edd_template'] = array(
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

        return $options;
    }
    /**
     * This function is responsible for the some fields of 
     * wp comments notification in display tab
     *
     * @param array $options
     * @return void
     */
    public function display_tab_section( $options ){
        // $options['image']['fields']['edd_show_product_image'] = array(
        //     'label'       => __( 'Show Product Image', 'fomopress' ),
        //     'priority'    => 25,
        //     'type'        => 'checkbox',
        //     'default'     => true,
        //     'description' => __( 'Show the product image in notification', 'fomopress' ),
        // );

        return $options;
    }
    /**
     * Some toggleData & hideData manipulation.
     *
     * @param array $options
     * @return void
     */
    public function conversion_from( $options ){
        // $options['options'][ 'edd' ] = __( 'EDD', 'fomopress' );
        if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
            $options['toggle']['edd']['fields'] = [ 'has_no_edd' ];
            $options['hide']['custom']['fields'] = [ 'edd_template' ];
        } else {
            $options['toggle']['edd']['fields'] = [ 'edd_template', 'show_product_image' ];
            $options['toggle']['edd']['sections'] = [ 'image' ];
            $options['hide']['edd']['fields'] = [ 'show_custom_image' ];
        }

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
            if( ! is_null( $orders = $this->get_orders( $data ) ) ) {
                $this->save( $this->type, $orders );
            }
        }
    }

    public function get_payments( $days, $amount ) {
        $date 		= '-' . intval( $days ) . ' days';
        $start_date = strtotime( $date );

        $args = array(
            'number'    => $amount,
            'status'    => array('publish'),
            'date_query'	=> array(
                'after'			=> date( 'Y-m-d', $start_date )
            )
        );

        return edd_get_payments( $args );
    }

    public function status_transition( $id, $from, $to, $order ){
        
        
    }

    /**
     * Get all the orders from database using a date query
     * for limitation.
     *
     * @param array $data
     * @return void
     */
    public function get_orders( $payment_id ) {
        if( count( $this->notifications ) === $this->cache_limit ) {
            $sorted_data = FomoPress_Helper::sorter( $this->notifications, 'timestamp' );
            array_pop( $sorted_data );
            $this->notifications = $sorted_data;
        }
        $orders = null;
        $product_data = $ordered_data = [];
        $payment_meta = edd_get_payment_meta( $payment_id );
        $payment_key  = $payment_meta['key'];
        if( $payment_meta['user_info']['id'] ) {
            $user = new WP_User( $payment_meta['user_info']['id'] );
            if( $user->exists() ) {
                $ordered_data['user_id'] = $user->ID;
                $ordered_data['name'] = $user->display_name;
            }
        } else {
            $ordered_data['name'] = $payment_meta['user_info']['first_name'] . ' ' . $payment_meta['user_info']['last_name'];
        }

        $date = $payment_meta['date'];
        $ordered_data['timestamp'] = strtotime( $date );
        // // Cart details
        $cart_items = edd_get_payment_meta_cart_details( $payment_id );
        if( is_array( $cart_items ) && ! empty( $cart_items ) ) {
            foreach( $cart_items as $item ) {
                $product_data['product_id'] = $item['id'];
                $product_data['title']      = $item['name'];
                $product_data['link']       = get_permalink( $item['id'] );
                $this->notifications[ $payment_key . '-' . $item['id'] ] = array_merge( $ordered_data, $product_data );
            }
            $this->save( $this->type, $this->notifications );
        }
    }
    

    public function frontend_html( $data = [], $settings = false, $template = '' ){
        if( class_exists( 'Easy_Digital_Downloads' ) ) {
            return parent::frontend_html( $data, $settings, $template );
        }
    }

}

/**
 * Register the extension
 */
fomopress_register_extension( 'FomoPress_EDD_Extension' );