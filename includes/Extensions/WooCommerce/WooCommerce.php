<?php
/**
 * WooCommerce Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\WooCommerce;

use NotificationX\Admin\Entries;
use NotificationX\Core\Helper;
use NotificationX\Core\Rules;
use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\GlobalFields;

/**
 * WooCommerce Extension Class
 */
class WooCommerce extends Extension {
    /**
     * Instance of WOOReviews
     *
     * @var WOOReviews
     */
    use GetInstance;

    public $priority        = 5;
    public $id              = 'woocommerce';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/woocommerce.png';
    public $doc_link        = 'https://notificationx.com/docs/woocommerce-sales-notifications/';
    public $types           = 'conversions';
    public $module          = 'modules_woocommerce';
    public $module_priority = 3;
    public $class           = '\WooCommerce';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        $this->title = __('WooCommerce', 'notificationx');
        $this->module_title = __('WooCommerce', 'notificationx');
        parent::__construct();
    }

    public function init(){
        parent::init();
        add_action('woocommerce_order_status_changed', array($this, 'status_transition'), 10, 4);
        add_action('woocommerce_process_shop_order_meta', array( $this, 'manual_order'), 10, 2 );
        add_action('woocommerce_new_order_item', array($this, 'save_new_orders'), 10, 3);
    }

    public function init_fields(){
        parent::init_fields();
        add_filter('nx_link_types', [$this, 'link_types']);
        add_filter( 'nx_content_fields', array( $this, 'content_fields' ), 11 );

        add_filter('nx_conversion_product_list', [$this, 'products']);
        add_filter('nx_conversion_category_list', [$this, 'categories']);

    }

    /**
     * This functions is hooked
     *
     * @return void
     */
    public function admin_actions() {
        parent::admin_actions();
        if (!$this->is_active()) {
            return;
        }
    }

    public function public_actions(){
        parent::public_actions();
        if (!$this->is_active()) {
            return;
        }
        add_filter("nx_filtered_data_{$this->id}", array($this, 'multiorder_combine'), 11, 3);
    }

    public function source_error_message($messages) {
        if (!$this->class_exists()) {
            $url = admin_url('plugin-install.php?s=woocommerce&tab=search&type=term');
            $messages[$this->id] = [
                'message' => sprintf( '%s <a href="%s" target="_blank">%s</a> %s',
                    __( 'You have to install', 'notificationx' ),
                    $url,
                    __( 'WooCommerce', 'notificationx' ),
                    __( 'plugin first.', 'notificationx' )
                ),
                'html' => true,
                'type' => 'error',
                'rules' => Rules::is('source', $this->id),
            ];
        }
        return $messages;
    }


    public function content_fields($fields){
        $content_fields = &$fields['content']['fields'];
        $content_fields['product_control']    = Rules::includes('source', $this->id, false, $content_fields['product_control']);
        $content_fields['category_list']      = Rules::includes('source', $this->id, false, $content_fields['category_list']);
        $content_fields['product_list']       = Rules::includes('source', $this->id, false, $content_fields['product_list']);
        $content_fields['product_exclude_by'] = Rules::includes('source', $this->id, false, $content_fields['product_exclude_by']);
        $content_fields['exclude_categories'] = Rules::includes('source', $this->id, false, $content_fields['exclude_categories']);
        $content_fields['exclude_products']   = Rules::includes('source', $this->id, false, $content_fields['exclude_products']);

        return $fields;
    }


    /**
     * Adds option to Link Type field in Content tab.
     *
     * @param array $options
     * @return array
     */
    public function link_types($options) {
        $_options = GlobalFields::get_instance()->normalize_fields([
            'product_page' => __('Product Page', 'notificationx'),
        ], 'source', $this->id);

        $this->has_link_types = true;
        return array_merge($options, $_options);
    }

    public function saved_post($post, $data, $nx_id) {
        $this->delete_notification(null, $nx_id);
        $this->get_notification_ready($data);
    }

    /**
     * This function is responsible for making the notification ready for first time we make the notification.
     *
     * @param string $type
     * @param array $data
     * @return void
     */
    public function get_notification_ready($post = array()) {
        if (!is_null($orders = $this->get_orders($post))) {
            // $orders = Helper::sortBy($orders, 'woocommerce');
            $entries = [];
            foreach ($orders as $key => $order) {
                $entries[] = [
                    'nx_id'      => $post['nx_id'],
                    'source'     => $this->id,
                    'entry_key'  => $key,
                    'data'       => $order,
                    // 'updated_at' => Helper::mysql_time($order['timestamp']),
                ];
            }
            $this->update_notifications($entries);
        }
        return $orders;
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
    public function save_new_orders($item_id,  $item,  $order_id) {
        $single_notification = $this->ordered_product($item_id, $item, $order_id);
        if (!empty($single_notification)) {
            $key = $order_id . '-' . $item_id;
            $this->save([
                'source'    => $this->id,
                'entry_key' => $key,
                'data'      => $single_notification,
            ]);
            return true;
        }
        return false;
    }

    public function status_transition($id, $from, $to, $order) {
        $items  = $order->get_items();
        $status = ['on-hold', 'cancelled', 'refunded', 'failed', 'pending', 'wcf-main-order'];
        $done   = ['completed', 'processing'];

        $if_has_course = false;

        if (in_array($from, $done) && in_array($to, $status)) {
            foreach ($items as $item) {
                if (function_exists('tutor_utils')) {
                    $if_has_course = \tutor_utils()->product_belongs_with_course($item->get_product_id());
                }
                if ($if_has_course) {
                    continue;
                }
                $key = $id . '-' . $item->get_id();

                $this->delete_notification($key);
            }
        }

        if (in_array($from, $status) && in_array($to, $done)) {
            $orders = [];

            foreach ($items as $item) {
                $key = $id . '-' . $item->get_id();
                if (function_exists('tutor_utils')) {
                    $if_has_course = tutor_utils()->product_belongs_with_course($item->get_product_id());
                }
                if ($if_has_course) {
                    continue;
                }
                $single_notification = $this->ordered_product($item->get_id(), $item, $order);
                if (!empty($single_notification)) {
                    $this->update_notification([
                        'source'     => $this->id,
                        'entry_key'  => $key,
                        'data'       => $single_notification,
                    ], false);
                }
            }
        }

        return true;
    }

    // define the manual_order callback
    public function manual_order( $order_id, $post ){
        $orders = [];
        $order = wc_get_order($order_id);
        $items = $order->get_items();
        foreach( $items as $item ) {
            $tutor_product = metadata_exists('post', $item->get_product_id(), "_tutor_product");
            if($tutor_product ) {
                continue;
            }
            $order_item = $this->ordered_product($item->get_id(), $item, $order);
            if($order_item){
                $this->save([
                    'source'    => $this->id,
                    'entry_key' => $order->get_id() . '-' . $item->get_id(),
                    'data'      => $order_item,
                ], false);
            }
        }
    }

    /**
     * This function is responsible for making ready the orders data.
     *
     * @param int $item_id
     * @param WC_Order_Item_Product $item
     * @param int $order_id
     * @return void
     */
    public function ordered_product($item_id, $item, $order_id) {
        if (!$item instanceof \WC_Order_Item_Product) {
            return false;
        }
        $if_has_course = false;
        if (function_exists('tutor_utils')) {
            $if_has_course = tutor_utils()->product_belongs_with_course($item->get_product_id());
        }
        if ($if_has_course) {
            return false;
        }
        $new_order = [];
        if (is_int($order_id)) {
            $order = new \WC_Order($order_id);
        } else {
            $order = $order_id;
        }

        if (!$order instanceof \WC_Order) {
            return false;
        }

        $status = $order->get_status();
        // @todo add options.
        $done = ['completed', 'processing', 'pending'];
        if (!in_array($status, $done)) {
            return false;
        }

        $date = $order->get_date_created();
        $countries = new \WC_Countries();
        $shipping_country = $order->get_billing_country();
        if (empty($shipping_country)) {
            $shipping_country = $order->get_shipping_country();
        }
        if (!empty($shipping_country)) {
            $new_order['country'] = isset($countries->countries[$shipping_country]) ? $countries->countries[$shipping_country] : '';
            $shipping_state = $order->get_shipping_state();
            if (!empty($shipping_state)) {
                $new_order['state'] = isset($countries->states[$shipping_country], $countries->states[$shipping_country][$shipping_state]) ? $countries->states[$shipping_country][$shipping_state] : $shipping_state;
            }
        }
        $new_order['city'] = $order->get_billing_city();
        if (empty($new_order['city'])) {
            $new_order['city'] = $order->get_shipping_city();
        }

        $new_order['ip'] = $order->get_customer_ip_address();
        $product_data = $this->ready_product_data($item->get_data());
        if (!empty($product_data)) {
            $new_order['order_id']   = is_int($order_id) ? $order_id : $order_id->get_id();
            $new_order['product_id'] = $item->get_product_id();
            $new_order['title']      = $product_data['title'];
            $new_order['link']       = $product_data['link'];
        }
        $new_order['timestamp'] = $date->getTimestamp();
        return array_merge($new_order, $this->buyer($order));
    }

    /**
     * It will take an array to make data clean
     *
     * @param array $data
     * @return void
     */
    protected function ready_product_data($data) {
        if (empty($data)) {
            return;
        }
        return array(
            'title' => $data['name'],
            'link' => get_permalink($data['product_id']),
        );
    }

    /**
     * This function is responsible for getting
     * the buyer name from order.
     *
     * @param WC_Order $order
     * @return void
     */
    protected function buyer(\WC_Order $order) {
        $first_name = $last_name = $email = '';
        $buyer_data = [];

        if (empty($first_name)) {
            $first_name = $order->get_billing_first_name();
        }

        if (empty($last_name)) {
            $last_name = $order->get_billing_last_name();
        }

        if (empty($email)) {
            $email = $order->get_billing_email();
        }

        $buyer_data['first_name'] = $first_name;
        $buyer_data['last_name'] = $last_name;
        $buyer_data['name'] = $first_name . ' ' . mb_substr($last_name, 0, 1);
        $buyer_data['email'] = $email;

        return $buyer_data;
    }

    /**
     * Get all the orders from database using a date query
     * for limitation.
     *
     * @param array $data
     * @return array
     */
    public function get_orders($data = array()) {
        if (empty($data)) return null;
        $orders = [];
        $from = strtotime(date('Y-m-d', strtotime('-' . intval($data['display_from']) . ' days')));
        $wc_orders = \wc_get_orders([
            'status' => array('processing', 'completed', 'pending'),
            'date_created' => '>' . $from,
            'numberposts' => isset($data['display_last']) ? intval($data['display_last']) : 10,
        ]);
        foreach ($wc_orders as $order) {
            $items = $order->get_items();
            foreach ($items as $item) {
                $tutor_product = metadata_exists('post', $item->get_product_id(), "_tutor_product");
                if ($tutor_product) {
                    continue;
                }
                $orders[$order->get_id() . '-' . $item->get_id()] = $this->ordered_product($item->get_id(), $item, $order);
            }
        }
        return $orders;
    }


    public function categories($options){

        $product_categories = get_terms(array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ));

        $category_list = [];

        if( ! is_wp_error( $product_categories ) ) {
            foreach( $product_categories as $product ) {
                $category_list[ $product->slug ] = $product->name;
            }
        }

        $_options = GlobalFields::get_instance()->normalize_fields($category_list, 'source', $this->id);
        return array_merge($options, $_options);
    }

    public function products($options){
        $products = get_posts(array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'numberposts' => -1,
        ));
        $product_list = [];

        if( ! empty( $products ) ) {
            foreach( $products as $product ) {
                $product_list[ $product->ID ] = $product->post_title;
            }
        }
        wp_reset_postdata();

        $_options = GlobalFields::get_instance()->normalize_fields($product_list, 'source', $this->id);
        return array_merge($options, $_options);
    }


    public function multiorder_combine($data, $settings) {
        if (empty($settings['combine_multiorder']) || intval($settings['combine_multiorder']) != 1 )  {
            return $data;
        }
        $items = [];
        $item_counts = [];
        foreach ($data as $key => $item) {
            $order_id = isset( $item['order_id'] ) ? $item['order_id'] : ( isset( $item['id'] ) ? $item['id'] : null );
            if( $order_id === null ) {
                continue;
            }
            if (!isset($items[$order_id])) {
                $items[$order_id] = $item;
            } else {
                $item_counts[$order_id] = isset($item_counts[$order_id]) ? ++$item_counts[$order_id] : 1;
            }
        }

        $products_more_title = isset($settings['combine_multiorder_text']) && !empty($settings['combine_multiorder_text']) ? __($settings['combine_multiorder_text'], 'notificationx') : __('more products', 'notificationx');
        foreach ($item_counts as $key => $item) {
            $items[$key]['title'] = $items[$key]['title'] . ' & ' . $item . ' ' . $products_more_title;
        }

        // @todo maybe sort
        return $items;
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
            if (!empty($data['product_id']) && has_post_thumbnail($data['product_id'])) {
                $product_image = wp_get_attachment_image_src(
                    get_post_thumbnail_id($data['product_id']),
                    '_nx_notification_thumb',
                    false
                );
                $image_data['url'] = is_array($product_image) ? $product_image[0] : '';
            }
        }
        return $image_data;
    }

    public function fallback_data($data) {
        $data['name']            = __('Someone', 'notificationx');
        $data['first_name']      = __('Someone', 'notificationx');
        $data['last_name']       = __('Someone', 'notificationx');
        $data['anonymous_title'] = __('Anonymous Product', 'notificationx');
        return $data;
    }

    public function doc(){
        return '<p>Make sure that you have <a target="_blank" href="https://wordpress.org/plugins/woocommerce/">WooCommerce installed & activated</a> to use this campaign. For further assistance, check out our step by step <a target="_blank" href="https://notificationx.com/docs/woocommerce-sales-notifications/">documentation</a>.</p>
		<p>🎦 <a href="https://www.youtube.com/watch?v=dVthd36hJ-E&t=1s" target="_blank">Watch video tutorial</a> to learn quickly</p>
		<p>⭐ NotificationX Integration with WooCommerce</p>
		<p><strong>Recommended Blog:</strong></p>
		<p>🔥 Why NotificationX is The <a target="_blank" href="https://notificationx.com/integrations/woocommerce/">Best FOMO and Social Proof Plugin</a> for WooCommerce?</p>
		<p>🚀 How to <a target="_blank" href="https://notificationx.com/blog/best-fomo-and-social-proof-plugin-for-woocommerce/">boost WooCommerce Sales</a> Using NotificationX</p>';
    }

}