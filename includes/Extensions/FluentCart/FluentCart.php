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
    public $post_type       = 'fluent-products';

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
        // Default to 'paid' and 'processing' if no status is specified
        $allowed_statuses = !empty($settings['fluentcart_order_status']) ? $settings['fluentcart_order_status'] : ['paid', 'processing'];

        // Check if any of the order's statuses match the allowed statuses
        $status_matches = false;

        // Check payment status
        if (!empty($entry['data']['payment_status']) && in_array($entry['data']['payment_status'], $allowed_statuses)) {
            $status_matches = true;
        }

        // Check order status
        if (!empty($entry['data']['status']) && in_array($entry['data']['status'], $allowed_statuses)) {
            $status_matches = true;
        }

        // Check shipping status
        if (!empty($entry['data']['shipping_status']) && in_array($entry['data']['shipping_status'], $allowed_statuses)) {
            $status_matches = true;
        }

        // Check fulfillment status (FluentCart specific)
        if (!empty($entry['data']['fulfillment_status']) && in_array($entry['data']['fulfillment_status'], $allowed_statuses)) {
            $status_matches = true;
        }

        // Return the original return value if status matches, otherwise return false
        return $status_matches ? $return : false;
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
        // Get FluentCart product categories using the product-categories taxonomy
        $categories = get_terms([
            'taxonomy'   => 'product-categories',
            'hide_empty' => false,
        ]);

        $category_list = [];
        if (!is_wp_error($categories) && !empty($categories)) {
            foreach ($categories as $category) {
                $category_list[$category->slug] = $category->name;
            }
        }

        $options = GlobalFields::get_instance()->normalize_fields($category_list, 'source', $this->id, $options);
        return $options;
    }

    public function product_link($link, $post, $entry) {
        if(!empty($entry['permalink']) && !empty( $post['link_type'] ) && $post['link_type'] === 'product_page' ){
            $link = $entry['permalink'];
        }
        return $link;
    }

    public function order_status($options){
        // Get FluentCart's native order and payment statuses
        $order_status = [];

        try {
            // Check if FluentCart Status class is available
            if (class_exists('\FluentCart\App\Helpers\Status')) {
                // Get Order Statuses
                $order_statuses = \FluentCart\App\Helpers\Status::getOrderStatuses();
                foreach ($order_statuses as $key => $label) {
                    $order_status[$key] = $label;
                }

                // Get Payment Statuses
                $payment_statuses = \FluentCart\App\Helpers\Status::getPaymentStatuses();
                foreach ($payment_statuses as $key => $label) {
                    $order_status[$key] = $label . ' (' . __('Payment', 'notificationx') . ')';
                }

                // Get Shipping Statuses if available
                $shipping_statuses = \FluentCart\App\Helpers\Status::getShippingStatuses();
                foreach ($shipping_statuses as $key => $label) {
                    $order_status[$key] = $label . ' (' . __('Shipping', 'notificationx') . ')';
                }

            } else {
                // Fallback to manual status list if FluentCart classes are not available
                $order_status = [
                    // Order Statuses
                    'processing'  => __( 'Processing','notificationx' ),
                    'completed'   => __( 'Completed','notificationx' ),
                    'on-hold'     => __( 'On Hold','notificationx' ),
                    'canceled'    => __( 'Canceled','notificationx' ),
                    'failed'      => __( 'Failed','notificationx' ),

                    // Payment Statuses
                    'pending'              => __( 'Pending (Payment)','notificationx' ),
                    'paid'                 => __( 'Paid (Payment)','notificationx' ),
                    'partially_paid'       => __( 'Partially Paid (Payment)','notificationx' ),
                    'payment_failed'       => __( 'Failed (Payment)','notificationx' ),
                    'refunded'             => __( 'Refunded (Payment)','notificationx' ),
                    'partially_refunded'   => __( 'Partially Refunded (Payment)','notificationx' ),
                    'authorized'           => __( 'Authorized (Payment)','notificationx' ),

                    // Shipping Statuses
                    'unfulfilled' => __( 'Unfulfilled (Shipping)','notificationx' ),
                    'fulfilled'   => __( 'Fulfilled (Shipping)','notificationx' ),
                    'shipped'     => __( 'Shipped (Shipping)','notificationx' ),
                    'delivered'   => __( 'Delivered (Shipping)','notificationx' ),
                ];
            }
        } catch (\Exception $e) {
            // Fallback in case of any errors
            $order_status = [
                'processing'  => __( 'Processing','notificationx' ),
                'completed'   => __( 'Completed','notificationx' ),
                'paid'        => __( 'Paid','notificationx' ),
                'fulfilled'   => __( 'Fulfilled','notificationx' ),
            ];
        }

        $options = GlobalFields::get_instance()->normalize_fields( $order_status, 'source', $this->id, $options);
        return $options;
    }

    public function save_new_records($data) {
        $order    = isset($data['order']) ? $data['order'] : null;
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
        if( !empty( $order_item->post_title ) ) {
            $return['title'] = $order_item->post_title;
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
            if( !empty( $order->fulfillment_status ) ) {
                $return['fulfillment_status'] = $order->fulfillment_status;
            }
            if( !empty( $order->fulfillment_type ) ) {
                $return['fulfillment_type'] = $order->fulfillment_type;
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
                            // Get product categories for this order item
                            $categories = [];
                            if (!empty($order_item->post_id)) {
                                $product_categories = get_the_terms($order_item->post_id, 'product-categories');
                                if (!is_wp_error($product_categories) && !empty($product_categories)) {
                                    $categories = $product_categories;
                                }
                            }

                            if(( $this->_excludes_product($order_item, $post, $categories) === $this->_show_purchaseof($order_item, $post, $categories))){
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

    private function _excludes_product($order_item, $settings, $categories = []) {
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
        // Check category list
        if( $settings['product_exclude_by'] == 'product_category' && !empty( $settings['exclude_categories'] ) && count( $settings['exclude_categories'] ) > 0 ) {
            foreach ($categories as $category) {
                if( in_array( $category->slug, $settings['exclude_categories'] ) ) {
                    return true;
                }
            }
        }
        return false;
    }

    private function _show_purchaseof($order_item, $settings, $categories = []) {
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
        // Check category list
        if( $settings['product_control'] == 'product_category' && !empty( $settings['category_list'] ) && count( $settings['category_list'] ) > 0 ) {
            foreach ($categories as $category) {
                if( in_array( $category->slug, $settings['category_list'] ) ) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    /**
     * Lists available FluentCart products for search functionality.
     *
     * @param array $args An array of arguments, including inputValue.
     * @return array An indexed array of product IDs and titles.
     */
    public function restResponse($args) {
        // Check if inputValue is provided or if we should return empty results
        if ( empty( $args['search_empty']) && empty($args['inputValue'] ) ) {
            return [];
        }

        // Use FluentCart's native search if available, otherwise fallback to WordPress search
        $products = $this->search_fluentcart_products($args['inputValue']);

        // If FluentCart search didn't work, fallback to WordPress post search
        if (empty($products)) {
            $products = Helper::get_post_titles_by_search($this->post_type, $args['inputValue']);
        }

        // Normalize the fields and return as an indexed array
        return array_values(GlobalFields::get_instance()->normalize_fields($products, 'source', $this->id));
    }

    /**
     * Search FluentCart products using FluentCart's native methods
     *
     * @param string $search_term The search term
     * @return array Array of products with ID and title
     */
    private function search_fluentcart_products($search_term = '') {
        $products = [];

        try {
            // Check if FluentCart is available
            if (!class_exists('\FluentCart\App\Models\Product')) {
                return $products;
            }

            // Use FluentCart's Product model to search
            $query = \FluentCart\App\Models\Product::query()
                ->where('post_status', 'publish');

            // Add search condition if search term is provided
            if (!empty($search_term)) {
                $query->where(function($q) use ($search_term) {
                    // Search by product title
                    $q->where('post_title', 'like', '%' . $search_term . '%')
                      // Also search by product content/description
                      ->orWhere('post_content', 'like', '%' . $search_term . '%')
                      // Search by product excerpt
                      ->orWhere('post_excerpt', 'like', '%' . $search_term . '%');
                });
            }

            $fluent_products = $query->get();

            // Convert to the expected format
            foreach ($fluent_products as $product) {
                $products[$product->ID] = $product->post_title;
            }

        } catch (\Exception $e) {
            // If FluentCart search fails, return empty array to trigger fallback
            return [];
        }

        return $products;
    }

    /**
     * Get products by category for enhanced search functionality
     *
     * @param string $category_slug The category slug to search for
     * @return array Array of products in the specified category
     */
    public function get_products_by_category($category_slug = '') {
        $products = [];

        if (empty($category_slug)) {
            return $products;
        }

        try {
            // Get products that belong to the specified category
            $args = array(
                'post_type'      => $this->post_type,
                'post_status'    => 'publish',
                'posts_per_page' => 20,
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'product-categories',
                        'field'    => 'slug',
                        'terms'    => $category_slug,
                    ),
                ),
            );

            $query = new \WP_Query($args);

            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $products[get_the_ID()] = get_the_title();
                }
                wp_reset_postdata();
            }

        } catch (\Exception $e) {
            // If category search fails, return empty array
            return [];
        }

        return $products;
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

