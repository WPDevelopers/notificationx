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
    }

    public function init_fields(){
        parent::init_fields();
        add_filter('nx_link_types', [$this, 'link_types']);
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

    public function check_order_status($one,$two,$three) {
        return true;
    }

    public function public_actions(){
        parent::public_actions();
    }

    public function save_new_records($checkout, $request) {
        foreach ($checkout->line_items->data as $product ) {
            if( !empty( $product ) ) {
                $single_notifications = $this->ordered_product($checkout, $request, $product );
                $key = $checkout->order . '-';
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
        $address_fields = ['city','country','line_1','line_2','postal_code'];
        $customer_fields = ['first_name','last_name','email','name'];
        // Get product information 
        if( !empty( $product->price->product->name ) ) {
            $new_order['title'] = $product->price->product->name;
        }
        if( !empty( $product->price->product->id ) ) {
            $new_order['product_id'] = $product->price->product->id;
        }
        if( !empty( $product->price->product->image_url ) ) {
            $new_order['image_url'] = $product->price->product->image_url;
        }
        // Get billing and shipping address
        if( $finalized->customer->billing_matches_shipping ) {
            foreach ( $address_fields as $fields ) {
                $new_order[$fields] = $finalized->customer->shipping_address->{$fields};
            }
        }else{
            foreach ( $address_fields as $fields ) {
                $new_order[$fields] = $finalized->customer->billing_address->{$fields};
            }
        }
        // Get customer information
        foreach ($customer_fields as $customer_field) {
            if( !empty( $finalized->customer->{$customer_field} ) ) {
                $new_order[$customer_field] = $finalized->customer->{$customer_field};
            }
        }
        // Others information
        if( !empty( $finalized->ip_address ) ) {
            $new_order['ip'] = $finalized->ip_address;
        }
        if( !empty( $finalized->order ) ) {
            $new_order['order'] = $finalized->order;
        }
        return $new_order;
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

