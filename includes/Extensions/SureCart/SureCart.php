<?php
/**
 * SureCart Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\SureCart;
use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\GlobalFields;
use NotificationX\Admin\Entries;
use NotificationX\Core\Helper;

/**
 * SureCart Extension Class
 * @method static SureCart get_instance($args = null)
 */
class SureCart extends Extension {
    use GetInstance;
    public $priority        = 30;
    public $id              = 'surecart';
    public $doc_link        = 'https://notificationx.com/docs/surecart-sales-notifications/';
    public $types           = 'conversions';
    public $module          = 'modules_surecart';
    public $module_priority = 35;
    public $class           = '\SureCart';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('SureCart', 'notificationx');
        $this->module_title = __('SureCart', 'notificationx');
        parent::__construct();
    }

    public function init(){
        parent::init();
        add_action('surecart/checkout_confirmed', array( $this, 'save_new_records'), 10, 2);
        // add_action('surecart/purchase_revoked', array( $this, 'change_status'), 10, 2);
    }

    public function init_fields(){
        parent::init_fields();
        add_filter('nx_link_types', [$this, 'link_types']);
        add_filter( 'nx_surecart_order_status', array( $this, 'order_status' ), 11 );
        add_filter("nx_notification_link_{$this->id}", [$this, 'product_link'], 10, 3);
    }

    public function change_status($purchase, $data) {
        echo "update";
        print_r( $purchase );
        die();
    }

    public function product_link($link, $post, $entry) {
        if(!empty($entry['permalink']) && !empty( $post['link_type'] ) && $post['link_type'] === 'product_page' ){
            $link = $entry['permalink'];
        }
        return $link;
    }

    public function order_status($options){
        $order_status = [
            'processing'  => __( 'Processing','notificationx' ),
            'unfulfilled' => __( 'Unfulfilled','notificationx' ),
            'fulfilled'   => __( 'Fulfilled','notificationx' ),
            'delivered'   => __( 'Delivered','notificationx' ),
            'not-shipped' => __( 'Not Shipped','notificationx' ),
         ];
        $options = GlobalFields::get_instance()->normalize_fields( $order_status, 'source', $this->id, $options);
        return $options;
    }

    /**
     * This functions is hooked
     *
     * @return void
     */
    public function admin_actions() {
        parent::admin_actions();
        add_filter("nx_can_entry_{$this->id}", array($this, 'check_order_status'), 10, 3);
    }

     /**
     * Limit entry by selected status;
     *
     * @param bool $return
     * @param array $entry
     * @param array $settings
     * @return boolean
     */
    public function check_order_status($return, $entry, $settings){
        return true;
        $done     = !empty($settings['order_status']) ? $settings['order_status'] : ['wc-completed', 'wc-processing'];
        if(!in_array($entry['data']['status'], $done)){
            return false;
        }
        return $return;
    }

    public function public_actions(){
        parent::public_actions();
    }

    public function save_new_records($checkout, $request) {
        foreach ($checkout->line_items->data as $product ) {
            if( !empty( $product ) ) {
                $single_notifications = $this->ordered_product($checkout, $request, $product );
                $key = $checkout->order;
                $this->save([
                    'source'    => $this->id,
                    'entry_key' => $key,
                    'data'      => $single_notifications,
                ], true );
            }
        }
    }

    public function ordered_product($checkout, $request, $product ) {
        $new_order = [];
        $finalized =  $checkout->where( $request->get_query_params() )->finalize( $request->get_body_params() );
        $new_order = $this->prepare_order_data( $product, $finalized->customer, $new_order );
        // Others information
        if( !empty( $finalized->ip_address ) ) {
            $new_order['ip'] = $finalized->ip_address;
        }
        if( !empty( $finalized->order ) ) {
            $new_order['order'] = $finalized->order;
        }
        if( !empty( $finalized->order ) ) {
            $new_order['order'] = $finalized->order;
        }
        if( !empty( $checkout->status ) ) {
            $new_order['status'] = $checkout->status;
        }
        return $new_order;
    }

    public function prepare_order_data( $product, $customer, $return = [], $checkout = null ) {
        $address_fields = ['city','country','line_1','line_2','postal_code'];
        $customer_fields = ['first_name','last_name','email','name'];
        // Get product information 
        if( !empty( $product->price->product->name ) ) {
            $return['title'] = $product->price->product->name;
        }
        if( !empty( $product->price->product->id ) ) {
            $return['product_id'] = $product->price->product->id;
        }
        if( !empty( $product->price->product->permalink ) ) {
            $return['permalink'] = $product->price->product->permalink;
        }
        if( !empty( $product->price->product->image_url ) ) {
            $return['image_url'] = $product->price->product->image_url;
        }
        // Get billing and shipping address
        if( $customer->billing_matches_shipping ) {
            if( is_object( $customer->shipping_address ) ) {
                foreach ( $address_fields as $fields ) {
                    $return[$fields] = $customer->shipping_address->{$fields};
                }
            }else{
                foreach ( $address_fields as $fields ) {
                    $return[$fields] = $checkout->shipping_address->{$fields};
                }
            }
            
        }else{
            if( is_object( $customer->billing_address ) ) {
                foreach ( $address_fields as $fields ) {
                    $return[$fields] = $customer->billing_address->{$fields};
                }
            }else{
                foreach ( $address_fields as $fields ) {
                    $return[$fields] = $checkout->billing_address->{$fields};
                }
            }
        }
        // Get customer information
        foreach ($customer_fields as $customer_field) {
            if( !empty( $customer->{$customer_field} ) ) {
                $return[$customer_field] = $customer->{$customer_field};
            }
        }
        return $return;
    }

    /**
     * Image action callback
     * @param array $image_data
     * @param array $data
     * @param stdClass $settings
     * @return array
     */
    public function notification_image($image_data, $data, $settings) {
        if (!$settings['show_default_image'] && $settings['show_notification_image'] === 'featured_image') {
            if ( !empty( $data['image_url'] ) ) {
                $image_data['url'] = $data['image_url'];
            }
        }
        return $image_data;
    }

    public function saved_post($post, $data, $nx_id) {
        $this->get_notification_ready($data);
    }

    public function get_notification_ready( $post = array() ) {
        $orders = $this->get_orders( $post );
        if ( is_array( $orders ) && ! empty( $orders ) ) {
            $entries = [];
            foreach ( $orders as $order ) {
                $entries[] = [
                    'nx_id'      => $post['nx_id'],
                    'source'     => $this->id,
                    'entry_key'  => $order['order'],
                    'data'       => $order,
                    'updated_at' => Helper::mysql_time($order['updated_at']),
                ];
            }
            $this->update_notifications($entries);
        }
    }

    public function get_orders( $post = array() ) {
        if( empty( $post ) ) {
            return;
        }
        $dateFrom = !empty( $post['display_from'] ) ? date('Y-m-d',strtotime('-'.$post['display_from'].' days',time())) : '';
        $dateTo = date('Y-m-d',strtotime('1 days',time()));
        $amount = !empty( $post['display_last'] ) ? $post['display_last'] : 10;
        $get_orders = \SureCart\Models\Order::with( [ 'checkout', 'checkout.charge', 'checkout.customer','checkout.line_items','line_item.price','price.product','checkout.shipping_address','checkout.billing_address'] )->paginate( [ 'per_page' => $amount ] );
        $orders = [];
        if( count( $get_orders->data ) > 0 ) {
            foreach ($get_orders->data as $order) {
                if( $this->is_entry_exists( (int) $post['nx_id'], $order->id ) ) {
                    continue;
                }
                $createdAt = $order['created_at'];
                if ($createdAt >= strtotime($dateFrom) && strtotime($createdAt) <= $dateTo) {
                    if( !empty( $order->checkout->line_items->data ) && count( $order->checkout->line_items->data ) > 0 ) {
                        foreach ($order->checkout->line_items->data as $product) {
                            $make_orders = [];
                            if( !empty( $order->checkout->ip_address ) ) {
                                $make_orders['ip'] = $order->checkout->ip_address;
                            }
                            if( !empty( $order->id ) ) {
                                $make_orders['order'] = $order->id;
                            }
                            if( !empty( $order->id ) ) {
                                $make_orders['updated_at'] = $order->updated_at;
                            }
                            if( !empty( $order->fulfillment_status ) ) {
                                $make_orders['fulfillment_status'] = $order->fulfillment_status;
                            }
                            if( !empty( $order->fulfillment_status ) ) {
                                $make_orders['shipment_status'] = $order->shipment_status;
                            }
                            if( !empty( $order->status ) ) {
                                $make_orders['status'] = $order->status;
                            }
                            $orders[] = $this->prepare_order_data( $product, $order->checkout->customer, $make_orders, $order->checkout );
                        }
                    }
                }
            }
        }
        return $orders;
    }

    /**
     * Check entry already exists or not
     */
    public function is_entry_exists( $nx_id, $entry_id ) {
        $entries = Entries::get_instance()->get_entries($nx_id);
        $is_entry_exists = array_filter($entries, function ($item) use ($entry_id) {
            return $item['order'] === $entry_id;
        });
        return $is_entry_exists ? true : false;
    }

    /**
     * Adds option to Link Type field in Content tab.
     *
     * @param array $options
     * @return array
     */
    public function link_types($options) {
        $options = GlobalFields::get_instance()->normalize_fields([
            'product_page' => __('Product Page', 'notificationx'),
        ], 'source', $this->id, $options);

        return $options;
    }

    public function fallback_data($data, $entry) {
        $data['name']            = __('Someone', 'notificationx');
        $data['first_name']      = __('Someone', 'notificationx');
        $data['last_name']       = __('Someone', 'notificationx');
        $data['anonymous_title'] = __('Anonymous Product', 'notificationx');
        if(empty($entry['product_title']) && !empty($entry['title'])){
            $data['product_title'] = $entry['title'];
        }
        return $data;
    }

    public function doc(){
        return sprintf(__('<p>Make sure that you have <a target="_blank" href="%1$s">SureCart installed & activated</a> to use this campaign. For further assistance, check out our step by step <a target="_blank" href="%2$s">documentation</a>.</p>
        <p>🎦 <a href="%3$s" target="_blank">Watch video tutorial</a> to learn quickly</p>
        <p>⭐ NotificationX Integration with SureCart</p>
        <p><strong>Recommended Blog:</strong></p>
        <p>🔥 Why NotificationX is The <a target="_blank" href="%4$s">Best FOMO and Social Proof Plugin</a> for SureCart?</p>
        <p>🚀 How to <a target="_blank" href="%5$s">boost SureCart Sales</a> Using NotificationX</p>', 'notificationx'),
        'https://wordpress.org/plugins/woocommerce/',
        'https://notificationx.com/docs/woocommerce-sales-notifications/',
        'https://www.youtube.com/watch?v=dVthd36hJ-E&t=1s',
        'https://notificationx.com/integrations/woocommerce/',
        'https://notificationx.com/blog/best-fomo-and-social-proof-plugin-for-woocommerce/'
        );
    }

}

