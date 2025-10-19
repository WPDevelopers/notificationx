<?php
/**
 * FluentCart Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\FluentCart;
use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\GlobalFields;
use NotificationX\Admin\Entries;
use NotificationX\Core\Helper;
use NotificationX\Core\Rules;

/**
 * FluentCart Extension Class
 * @method static FluentCart get_instance($args = null)
 */
class FluentCart extends Extension {
    use GetInstance;
    public $priority        = 8;
    public $id              = 'fluentcart';
    public $doc_link        = 'https://notificationx.com/docs/fluentcart-sales-notifications/';
    public $types           = 'conversions';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/fluentcart.png';
    public $module          = 'modules_fluentcart';
    public $module_priority = 35;
    public $class           = '\FluentCart\Framework\Foundation\App';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
    }

    public function init_extension()
    {
        $this->title = __('FluentCart', 'notificationx');
        $this->module_title = __('FluentCart', 'notificationx');
    }

    public function init(){
        parent::init();
        add_action('fluent_cart/order_paid_done', array( $this, 'save_new_records'), 10, 1);
        add_action('fluent_cart/order_created', array( $this, 'save_new_records'), 10, 1);
        add_action('fluent_cart/payment_status_changed', [$this, 'status_transition'], 10, 1);
        add_action('fluent_cart/order_status_changed', [$this, 'status_transition'], 10, 1);
    }

    public function init_fields(){
        parent::init_fields();
        add_filter('nx_link_types', [$this, 'link_types']);
        add_filter( 'nx_fluentcart_order_status', array( $this, 'order_status' ), 11 );
        add_filter("nx_notification_link_{$this->id}", [$this, 'product_link'], 10, 3);
        add_filter('nx_conversion_category_list', [$this, 'collections']);
        add_filter('nx_conversion_product_list', [$this, 'product_lists']);

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

    public function public_actions(){
        parent::public_actions();
    }

    public function check_order_status($return, $entry, $settings){
        $done = !empty($settings['fluentcart_order_status']) ? $settings['fluentcart_order_status'] : ['paid'];
        if( ( !empty( $entry['data']['payment_status'] ) && in_array($entry['data']['payment_status'], $done ) ) || ( !empty( $entry['data']['status'] ) && in_array($entry['data']['status'], $done ) ) || ( !empty( $entry['data']['shipping_status'] ) && in_array($entry['data']['shipping_status'], $done ) ) ){
            return $return;
        }
        return true;
        return false;
    }

    public function product_lists($products) {
        $all_products = \FluentCart\App\Models\Product::published()->get();
        $product_lists = [];
        if( ! is_wp_error( $all_products ) && $all_products->count() > 0 ) {
            foreach( $all_products as $product ) {
                $product_lists[ $product->post_name ] = $product->post_title;
            }
        }
        $options = GlobalFields::get_instance()->normalize_fields($product_lists, 'source', $this->id, $products);
        return $options;
    }

    public function collections($options) {
        // FluentCart doesn't have collections like SureCart, so we return empty
        $collection_list = [];
        $options = GlobalFields::get_instance()->normalize_fields($collection_list, 'source', $this->id, $options);
        return $options;
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
            'shipped'     => __( 'Shipped','notificationx' ),
            'delivered'   => __( 'Delivered','notificationx' ),
            'not-shipped' => __( 'Not Shipped','notificationx' ),
         ];
        $options = GlobalFields::get_instance()->normalize_fields( $order_status, 'source', $this->id, $options);
        return $options;
    }

    public function save_new_records($data) {
        $order = isset($data['order']) ? $data['order'] : null;
        $customer = isset($data['customer']) ? $data['customer'] : null;

        if (!$order || !$customer) {
            return;
        }

        // Load order items
        $order->load('order_items');

        foreach ($order->order_items as $order_item) {
            if (!empty($order_item)) {
                $single_notifications = $this->prepare_order_data($order_item, $customer, [], $order);
                $key = $order->id . '_' . $order_item->id;
                $this->save([
                    'source'    => $this->id,
                    'entry_key' => $key,
                    'data'      => $single_notifications,
                ], true);
            }
        }
    }

    public function save_order_created($data) {
        // This can be used for order created events if needed
        // For now, we'll focus on paid orders
        return;
    }

    public function prepare_order_data( $order_item, $customer, $return = [], $order = null ) {
        $address_fields = ['city','country','address_1','address_2','postcode'];
        $customer_fields = ['first_name','last_name','email','full_name'];

        // Get product information from order item
        if( !empty( $order_item->title ) ) {
            $return['title'] = $order_item->title;
        }
        if( !empty( $order_item->post_title ) ) {
            $return['product_title'] = $order_item->post_title;
        }
        if( !empty( $order_item->post_id ) ) {
            $return['product_id'] = $order_item->post_id;
            // Get product permalink
            $return['permalink'] = get_permalink($order_item->post_id);
            // Get product image
            $return['image_url'] = get_the_post_thumbnail_url($order_item->post_id, 'thumbnail');
        }
        if( !empty( $order_item->unit_price ) ) {
            $return['price'] = $order_item->unit_price;
        }
        if( !empty( $order_item->quantity ) ) {
            $return['quantity'] = $order_item->quantity;
        }

        // Get customer information
        foreach ($customer_fields as $customer_field) {
            if( !empty( $customer->{$customer_field} ) ) {
                $return[$customer_field] = $customer->{$customer_field};
            }
        }

        // Get address information from order if available
        if ($order) {
            $order->load('billing_address', 'shipping_address');
            $address = $order->billing_address ?: $order->shipping_address;
            if ($address) {
                foreach ($address_fields as $address_field) {
                    if( !empty( $address->{$address_field} ) ) {
                        $return[$address_field] = $address->{$address_field};
                    }
                }
            }

            // Order information
            if( !empty( $order->id ) ) {
                $return['order_id'] = $order->id;
            }
            if( !empty( $order->payment_status ) ) {
                $return['payment_status'] = $order->payment_status;
            }
            if( !empty( $order->status ) ) {
                $return['status'] = $order->status;
            }
            if( !empty( $order->shipping_status ) ) {
                $return['shipping_status'] = $order->shipping_status;
            }
            if( !empty( $order->created_at ) ) {
                $return['timestamp'] = $order->created_at;
            }
            if( !empty( $order->ip_address ) ) {
                $return['ip'] = $order->ip_address;
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
        $this->delete_notification(null, $nx_id);
        $this->get_notification_ready($data);
    }

    public function get_notification_ready( $post = array() ) {
        // Validate required parameters
        if( empty( $post ) || empty( $post['nx_id'] ) ) {
            return;
        }

        // Get orders based on settings
        $orders = $this->get_orders( $post );
        if ( is_array( $orders ) && ! empty( $orders ) ) {
            $entries = [];
            foreach ( $orders as $order ) {
                // Ensure we have all required data
                if( empty( $order['entry_key'] ) || empty( $order['updated_at'] ) ) {
                    continue;
                }

                $entries[] = [
                    'nx_id'      => $post['nx_id'],
                    'source'     => $this->id,
                    'entry_key'  => $order['entry_key'], // Use the unique entry key we created
                    'data'       => $order,
                    'updated_at' => Helper::mysql_time($order['updated_at']),
                ];
            }

            // Only update if we have valid entries
            if( !empty( $entries ) ) {
                $this->update_notifications($entries);
            }
        }
    }

    public function status_transition($data) {
        $order = isset($data['order']) ? $data['order'] : null;
        $customer = isset($data['customer']) ? $data['customer'] : null;

        if (!$order || !$customer) {
            return $data;
        }

        // Find all entries for this order (there can be multiple for different order items)
        // We need to search for entries that start with the order ID
        $all_entries = Entries::get_instance()->get_entries(['source' => $this->id]);
        $order_entries = [];

        if (!empty($all_entries)) {
            foreach ($all_entries as $entry) {
                // Check if entry_key starts with order ID (format: order_id_item_id)
                if (strpos($entry['entry_key'], $order->id . '_') === 0) {
                    $order_entries[] = $entry;
                }
            }
        }

        if (!empty($order_entries) && count($order_entries) > 0) {
            $entries = [];
            foreach ($order_entries as $existing_order) {
                $update_data = !empty($existing_order) ? $existing_order : [];
                if (empty($existing_order) || empty($order)) {
                    continue;
                }

                // Update status information
                if (!empty($order->payment_status)) {
                    $update_data['data']['payment_status'] = $order->payment_status;
                }
                if (!empty($order->status)) {
                    $update_data['data']['status'] = $order->status;
                }
                if (!empty($order->shipping_status)) {
                    $update_data['data']['shipping_status'] = $order->shipping_status;
                }

                $entries[] = [
                    'nx_id'      => $update_data['nx_id'],
                    'source'     => $this->id,
                    'entry_key'  => $existing_order['entry_key'], // Keep the original entry key
                    'data'       => $update_data['data'],
                    'updated_at' => Helper::mysql_time($order->updated_at),
                ];
            }

            // Delete old entries and add updated ones
            foreach ($order_entries as $existing_order) {
                $this->delete_notification($existing_order['entry_key'], $existing_order['nx_id']);
            }
            $this->update_notifications($entries);
        }

        return $data;
    }

    public function get_orders( $post = array() ) {
        if( empty( $post ) ) {
            return;
        }

        $dateFrom = !empty( $post['display_from'] ) ? date('Y-m-d',strtotime('-'.$post['display_from'].' days',time())) : '';
        $dateTo = date('Y-m-d',strtotime('1 days',time()));
        $amount = !empty( $post['display_last'] ) ? $post['display_last'] : 10;

        $get_orders = \FluentCart\App\Models\Order::with(['customer', 'order_items', 'billing_address', 'shipping_address'])
            ->orderBy('created_at', 'desc')
            ->limit($amount)
            ->get();

        $orders = [];
        if( $get_orders->count() > 0 ) {
            foreach ($get_orders as $order) {
                $createdAt = strtotime($order->created_at);
                if ($createdAt >= strtotime($dateFrom) && $createdAt <= strtotime($dateTo)) {
                    if( !empty( $order->order_items ) && $order->order_items->count() > 0 ) {
                        foreach ($order->order_items as $order_item) {
                            if(( $this->_excludes_product($order_item, $post) === $this->_show_purchaseof($order_item, $post))){
                                continue;
                            }

                            $make_orders = $this->prepare_order_data($order_item, $order->customer, [], $order);
                            $make_orders['order'] = $order->id;
                            $make_orders['order_item_id'] = $order_item->id;
                            $make_orders['entry_key'] = $order->id . '_' . $order_item->id;
                            $make_orders['updated_at'] = $order->updated_at;

                            $orders[] = $make_orders;
                        }
                    }
                }
            }
        }

        return $orders;
    }

    private function _excludes_product($order_item, $settings) {
        if( !empty( $settings['product_exclude_by'] ) && $settings['product_exclude_by'] === 'none' ) {
            if( !empty( $settings['product_control'] ) && $settings['product_control'] === 'none' ) {
                return true;
            }
            return false;
        }
        // Check product list
        if( $settings['product_exclude_by'] == 'manual_selection' && !empty( $settings['exclude_products'] ) && count( $settings['exclude_products'] ) > 0 ) {
            foreach ( $settings['exclude_products'] as $__product ) {
                $product_slug = get_post_field('post_name', $order_item->post_id);
                if( $__product['value'] == $product_slug ) {
                    return true;
                }
            }
        }
        return false;
    }

    private function _show_purchaseof($order_item, $settings) {
        if( !empty( $settings['product_control'] ) && $settings['product_control'] === 'none' ) {
            if( !empty( $settings['product_exclude_by'] ) && $settings['product_exclude_by'] === 'none' ) {
                return false;
            }
            return true;
        }
        // Check product list
        if( $settings['product_control'] == 'manual_selection' && !empty( $settings['product_list'] ) && count( $settings['product_list'] ) > 0 ) {
            foreach ( $settings['product_list'] as $__product ) {
                $product_slug = get_post_field('post_name', $order_item->post_id);
                if( $__product['value'] == $product_slug ) {
                    return true;
                }
            }
            return false;
        }
        return true;
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

    public function source_error_message($messages) {
        if (!$this->class_exists()) {
            $url = admin_url('plugin-install.php?s=fluent-cart&tab=search&type=term');
            $messages[$this->id] = [
                'message' => sprintf( '%s <a href="%s" target="_blank">%s</a> %s',
                    __( 'You have to install', 'notificationx' ),
                    $url,
                    __( 'FluentCart', 'notificationx' ),
                    __( 'plugin first.', 'notificationx' )
                ),
                'html' => true,
                'type' => 'error',
                'rules' => Rules::is('source', $this->id),
            ];
        }
        return $messages;
    }

    public function doc(){
        return sprintf(__('<p>Make sure that you have the <a target="_blank" href="%1$s">FluentCart WordPress plugin installed & configured</a> to use its campaign and selling data. For detailed guidelines, check out the step-by-step <a target="_blank" href="%2$s">documentation</a>.</p>
        <a target="_blank" href="%3$s">👉 NotificationX Integration with FluentCart</a>', 'notificationx'),
        'https://fluentcart.com/',
        'https://notificationx.com/docs/fluentcart-sales-notifications/',
        'https://notificationx.com/fluentcart/'
        );
    }

}

