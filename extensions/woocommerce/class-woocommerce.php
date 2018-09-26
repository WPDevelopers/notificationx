<?php

class FomoPress_WooCommerce_Extension extends FomoPress_Extension {
    /**
     *  Type of notification.
     *
     * @var string
     */
    public $type = 'woocommerce';
    public $template = 'woo_template';
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
        $loader->add_action( 'woocommerce_new_order_item', $this, 'save_new_orders', 9, 3 );
    }

    /**
     * Some extra field on the fly.
     * 
     * @param array $options
     * @return array
     */

    public function content_tab_section( $options ){

        $options[ 'content_config' ][ 'fields' ] = $this->merge(
            $options[ 'content_config' ][ 'fields' ],
            array(
                'woo_template'  => array(
                    'type'     => 'template',
                    'label'    => __('Notification Template' , 'fomopress'),
                    'priority' => 90,
                    'defaults' => [
                        __('{{name}} recently purchased', 'fomopress'), '{{title}}', '{{time}}'
                    ],
                    'variables' => [
                        '{{name}}', '{{title}}', '{{time}}'
                    ],
                )
            )
        );

        return $options;
    }
    /**
     * Some toggleData & hideData manipulation.
     *
     * @param array $options
     * @return void
     */
    public function conversion_from( $options ){
        $options['options'][ 'woocommerce' ]                = __( 'WooCommerce', 'fomopress' );
        $options['default']                                 = 'woocommerce';        
        return $options;
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
        if( $item instanceof WC_Order_Item_Shipping ) {
            return;
        }

        $order = new WC_Order( $order_id );
        $date = $order->get_date_created();

        if( ! empty( $product_data = $this->ready_product_data( $item->get_data() ) ) ) {
            $new_order['title'] = $product_data['title'];
            $new_order['link']  = $product_data['link'];
        }
        $new_order['timestamp'] = $date->getTimestamp();

        $new_order = array_merge( $new_order, $this->buyer( $order ));
        
        $this->notifications[] = $new_order;
        $this->save( $this->type, $this->notifications );
    }

    /**
     * This function is responsible for getting 
     * the buyer name from order.
     *
     * @param WC_Order $order
     * @return void
     */
    protected function buyer( WC_Order $order ){
        $user = $order->get_user();
        if( $user ) {
            return array(
                'name' => $user->display_name,
                'user_id' => $user->ID,
            );
        }
        return array(
            'name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        );
    }

    /**
     * It will take an array to make data clean
     *
     * @param array $data
     * @return void
     */
    protected function ready_product_data( $data ){
        if( empty( $data ) ) {
            return;
        }
        return array(
            'title' => $data['name'],
            'link' => get_permalink( $data['product_id'] ),
        );
    }

}

/**
 * Register the extension
 */
fomopress_register_extension( 'FomoPress_WooCommerce_Extension' );