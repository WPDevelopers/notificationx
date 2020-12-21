<?php

class NotificationX_WooCommerce_Extension extends NotificationX_Extension {
    /**
     *  Type of notification.
     *
     * @var string
     */
    public $type = 'woocommerce';
    public $template = 'woo_template';
    public $themeName = 'theme';
    /**
     * An array of all notifications
     *
     * @var [type]
     */
    protected $notifications = [];

    public function __construct() {
        parent::__construct( $this->template );
        $this->notifications = $this->get_notifications( $this->type );

        add_filter( 'nx_notification_link', array( $this, 'notification_link' ), 10, 2 );
    }

    public function template_string_by_theme( $template, $old_template, $posts_data ){
        if( NotificationX_Helper::get_type( $posts_data ) === $this->type ) {
            $theme = $posts_data['nx_meta_theme'];

            switch( $theme ) {
                default :
                    $template = NotificationX_Helper::regenerate_the_theme( $old_template, array( 'br_before' => [ 'third_param', 'fourth_param' ] ) );
                    break;
            }

            return $template;
        }
        return $template;
    }


    public function fallback_data( $data, $saved_data, $settings ){
        if( NotificationX_Helper::get_type( $settings ) !== $this->type ) {
            return $data;
        }

        $data['name']            = $this->notEmpty( 'name', $saved_data ) ? $saved_data['name'] : __( 'Someone', 'notificationx' );
        $data['first_name']      = $this->notEmpty( 'first_name', $saved_data ) ? $saved_data['first_name'] : __( 'Someone', 'notificationx' );
        $data['last_name']       = $this->notEmpty( 'last_name', $saved_data ) ? $saved_data['last_name'] : __( 'Someone', 'notificationx' );
        $data['anonymous_title'] = __( 'Anonymous Product', 'notificationx' );
        return $data;
    }

    /**
     * Main Screen Hooks
     */
    public function init_hooks(){
        add_filter( 'nx_metabox_tabs', array( $this, 'add_fields' ) );
        add_filter( 'nx_display_types_hide_data', array( $this, 'hide_fields' ) );
        add_filter( 'nx_conversion_from', array( $this, 'toggle_fields' ) );
    }
    /**
     * Builder Hooks
     */
    public function init_builder_hooks(){
        add_filter( 'nx_builder_tabs', array( $this, 'add_builder_fields' ) );
        add_filter( 'nx_display_types_hide_data', array( $this, 'hide_builder_fields' ) );
        add_filter( 'nx_builder_tabs', array( $this, 'builder_toggle_fields' ) );
    }

    public function notification_link( $link, $settings ){
        if( $settings->display_type === 'conversions' && $settings->conversion_url === 'none' ) {
            return '';
        }

        return $link;
    }
    /**
     * Needed Fields
     */
    private function init_fields(){
        $fields = [];

        if( ! class_exists( 'WooCommerce' ) ) {
            $installed = $this->plugins( 'woocommerce/woocommerce.php' );
            $url = admin_url('plugin-install.php?s=woocommerce&tab=search&type=term');
            $fields['has_no_woo'] = array(
                'type'     => 'message',
                'message'    => sprintf( '%s <a href="%s">%s</a> %s',
                    __( 'You have to install', 'notificationx' ),
                    $url,
                    __( 'WooCommerce', 'notificationx' ),
                    __( 'plugin first.', 'notificationx' )
                ),
                'priority' => 0,
            );
        }

        $fields['woo_template_new'] = array(
            'type'     => 'template',
            'builder_hidden' => true,
            'fields' => array(
                'first_param' => array(
                    'type'     => 'select',
                    'label'    => __('Notification Template' , 'notificationx'),
                    'priority' => 1,
                    'options'  => array(
                        'tag_name' => __('Full Name' , 'notificationx'),
                        'tag_first_name' => __('First Name' , 'notificationx'),
                        'tag_last_name' => __('Last Name' , 'notificationx'),
                        'tag_custom' => __('Custom' , 'notificationx'),
                    ),
                    'dependency' => array(
                        'tag_custom' => array(
                            'fields' => [ 'custom_first_param' ]
                        )
                    ),
                    'hide' => array(
                        'tag_name' => array(
                            'fields' => [ 'custom_first_param' ]
                        ),
                        'tag_first_name' => array(
                            'fields' => [ 'custom_first_param' ]
                        ),
                        'tag_last_name' => array(
                            'fields' => [ 'custom_first_param' ]
                        ),
                    ),
                    'default' => 'tag_name'
                ),
                'custom_first_param' => array(
                    'type'     => 'text',
                    'priority' => 2,
                    'default' => __('Someone' , 'notificationx')
                ),
                'second_param' => array(
                    'type'     => 'text',
                    'priority' => 3,
                    'default' => __('recently purchased' , 'notificationx')
                ),
                'third_param' => array(
                    'type'     => 'select',
                    'priority' => 4,
                    'options'  => array(
                        'tag_title'           => __('Product Title' , 'notificationx'),
                        'tag_anonymous_title' => __('Anonymous Product' , 'notificationx'),
                    ),
                    'default' => 'tag_title'
                ),
                'fourth_param' => array(
                    'type'     => 'select',
                    'priority' => 5,
                    'options'  => array(
                        'tag_time'       => __('Definite Time' , 'notificationx'),
                        'tag_sometime' => __('Some time ago' , 'notificationx'),
                    ),
                    'default' => 'tag_time',
                    'dependency' => array(
                        'tag_sometime' => array(
                            'fields' => [ 'custom_fourth_param' ]
                        )
                    ),
                    'hide' => array(
                        'tag_time' => array(
                            'fields' => [ 'custom_fourth_param' ]
                        ),
                    ),
                ),
                'custom_fourth_param' => array(
                    'type'     => 'text',
                    'priority' => 6,
                    'default' => __( 'Some time ago', 'notificationx' )
                ),
            ),
            'label'    => __('Notification Template' , 'notificationx'),
            'priority' => 90,
        );

        $fields['woo_template_adv'] = array(
            'type'        => 'adv_checkbox',
            'priority'    => 91,
            'button_text' => __('Advanced Template' , 'notificationx'),
            'side'        => 'right',
            'swal'        => true
        );

        $fields['combine_multiorder'] = array(
            'label'    => __('Combine Multi Order', 'notificationx'),
            'type'     => 'checkbox',
            'priority' => 100,
            'default' => 1,
            'description' => __('Combine order like, 2 more products.', 'notificationx') . ' <span data-swal="true" class="nx-cmo-conf">Configure</span>',
            'dependency' => array(
                1 => array(
                    'fields' => array( 'combine_multiorder_text' )
                ),
            ),
            'hide' => array(
                0 => array(
                    'fields' => array( 'combine_multiorder_text' )
                ),
            ),
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
            if( $name === 'has_no_woo' ) {
                $options[ 'source_tab' ]['sections']['config']['fields'][ $name ] = $field;
            }
            if( in_array( $name, array( 'woo_template_new', 'woo_template', 'woo_template_adv', 'combine_multiorder' ) ) ) {
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
        unset( $fields[ 'woo_template_adv' ] );

        foreach ( $fields as $name => $field ) {
            $options[ 'source_tab' ]['sections']['config']['fields'][ $name ] = $field;
        }

        return $options;
    }
    /**
     * This functions is hooked
     *
     * @hooked nx_public_action
     * @return void
     */
    public function public_actions(){
        if( ! $this->is_created( $this->type ) ) {
            return;
        }
        add_action( 'woocommerce_new_order_item', array( $this, 'save_new_orders' ), 10, 3 );
        add_filter( 'nx_filtered_data', array( $this, 'multiorder_combine' ), 10, 2 );
    }
    /**
     * This functions is hooked
     *
     * @hooked nx_admin_action
     * @return void
     */
    public function admin_actions(){
        if( ! $this->is_created( $this->type ) ) {
            return;
        }
        add_action( 'woocommerce_order_status_changed', array( $this, 'status_transition' ), 10, 4 );
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
     * Some toggleData & hideData manipulation.
     *
     * @param array $options
     * @return void
     */
    public function toggle_fields( $options ) {
        $fields = $this->init_fields();
        $fields = array_keys( $fields );
        $fields = array_merge( [ 'show_notification_image' ], $fields );

        $options['dependency'][ $this->type ]['fields'] = array_merge( $fields, $options['dependency'][ $this->type ]['fields'] );
        $options['dependency'][ $this->type ]['sections'] = array_merge( [ 'image' ], $options['dependency'][ $this->type ]['sections']);
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
        $old_fields = $options['source_tab']['sections']['config']['fields']['conversion_from']['dependency'][ $this->type ]['fields'];
        $options['source_tab']['sections']['config']['fields']['conversion_from']['dependency'][ $this->type ]['fields'] = array_merge( array_keys( $fields ), $old_fields);
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
        if( ! class_exists( 'WooCommerce' ) ) {
            return;
        }
        if( $this->type === $type ) {
            if( ! is_null( $orders = $this->get_orders( $data ) ) ) {
                $orders = NotificationX_Helper::sortBy( $orders, 'woocommerce' );
                $this->update_notifications( $this->type, $orders );
            }
        }
    }

    public function status_transition( $id, $from, $to, $order ){
        $items = $order->get_items();
        $status = [ 'on-hold', 'cancelled', 'refunded', 'failed', 'pending', 'wcf-main-order' ];
        $done = [ 'completed', 'processing' ];

        $if_has_course = false;

        if( in_array( $from, $done ) && in_array( $to, $status ) ) {
            foreach( $items as $item ) {
                if( function_exists( 'tutor_utils' ) ) {
                    $if_has_course = tutor_utils()->product_belongs_with_course( $item->get_product_id() );
                }
                if( $if_has_course ) {
                    continue;
                }
                $key = $id . '-' . $item->get_id();
                if( ! isset( $this->notifications[ $key ] ) ) continue;
                unset( $this->notifications[ $key ] );
            }
            $this->update_notifications( $this->type, $this->notifications );
        }

        if( in_array( $from, $status ) && in_array( $to, $done ) ) {
            $orders = [];

            foreach( $items as $item ) {
                $key = $id . '-' . $item->get_id();
                if( function_exists( 'tutor_utils' ) ) {
                    $if_has_course = tutor_utils()->product_belongs_with_course( $item->get_product_id() );
                }
                if( $if_has_course ) {
                    continue;
                }
                if( isset( $this->notifications[ $key ] ) ) continue;
                $single_notification = $this->ordered_product( $item->get_id(), $item, $order );
                if( ! empty( $single_notification ) ) {
                    $this->save( $this->type, $single_notification, $key );
                }
            }
        }

        return true;
    }
    /**
     * Get all the orders from database using a date query
     * for limitation.
     *
     * @param array $data
     * @return void
     */
    public function get_orders( $data = array() ) {
        if( empty( $data ) ) return null;
        $orders = [];
        $from = strtotime( date( 'Y-m-d', strtotime( '-' . intval( $data[ '_nx_meta_display_from' ] ) . ' days') ) );
        $wc_orders = wc_get_orders( [
            'status' => array( 'processing', 'completed', 'pending' ),
            'date_created' => '>' . $from,
            'numberposts' => isset( $data['_nx_meta_display_last'] ) ? intval( $data['_nx_meta_display_last'] ) : 10,
        ] );
        foreach( $wc_orders as $order ) {
            $items = $order->get_items();
            foreach( $items as $item ) {
                $tutor_product = metadata_exists( 'post', $item->get_product_id(), "_tutor_product" );
                if( $tutor_product ) {
                    continue;
                }
                $orders[ $order->get_id() . '-' . $item->get_id() ] = $this->ordered_product( $item->get_id(), $item, $order );
            }
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
    public function save_new_orders( $item_id,  $item,  $order_id ){
        $single_notification = $this->ordered_product( $item_id, $item, $order_id );
        if( ! empty( $single_notification ) ) {
            $key = $order_id . '-' . $item_id;
            $this->save( $this->type, $single_notification, $key );
            return true;
        }
        return false;
    }

    public function multiorder_combine( $data, $settings ){
        if( $settings->display_type != 'conversions' ) {
            return $data;
        }
        if( $settings->conversion_from != 'woocommerce' ) {
            return $data;
        }
        if( empty( $settings->combine_multiorder ) || $settings->combine_multiorder !== '1' ) {
            return $data;
        }
        $this->items = [];
        $this->item_counts = [];
        array_walk( $data, function( $item, $key ){
            if( ! isset( $this->items[ $item['id'] ] ) ) {
                $this->items[ $item['id'] ] = $item;
            } else {
                $this->item_counts[ $item['id'] ] = isset( $this->item_counts[ $item['id'] ] ) ? ++$this->item_counts[ $item['id'] ] : 1;
            }
        });

        $products_more_title = isset( $settings->combine_multiorder_text ) && ! empty( $settings->combine_multiorder_text ) ? __( $settings->combine_multiorder_text, 'notificationx' ) : ' ' . __( 'more products', 'notificationx' );
        array_walk( $this->item_counts, function( $item, $key ) use ( $products_more_title ) {
            $this->items[ $key ][ 'title' ] = $this->items[ $key ][ 'title' ] . ' & ' . $item . ' ' . $products_more_title;
        });

        return $this->sort_data( $this->items );
    }
    /**
     * This function is responsible for making ready the orders data.
     *
     * @param int $item_id
     * @param WC_Order_Item_Product $item
     * @param int $order_id
     * @return void
     */
    public function ordered_product( $item_id, $item, $order_id ) {
        if( ! $item instanceof WC_Order_Item_Product ) {
            return false;
        }
        $if_has_course = false;
        if( function_exists( 'tutor_utils' ) ) {
            $if_has_course = tutor_utils()->product_belongs_with_course( $item->get_product_id() );
        }
        if( $if_has_course ) {
            return false;
        }
        $new_order = [];
        if( is_int( $order_id ) ) {
            $order = new WC_Order( $order_id );
        } else {
            $order = $order_id;
        }

        if( ! $order instanceof WC_Order ) {
            return false;
        }

        $status = $order->get_status();
        $done = [ 'completed', 'processing', 'pending' ];
        if( ! in_array( $status, $done ) ){
            return false;
        }

        $date = $order->get_date_created();
        $countries = new WC_Countries();
        $shipping_country = $order->get_billing_country();
        if( empty( $shipping_country ) ) {
            $shipping_country = $order->get_shipping_country();
        }
        if( ! empty( $shipping_country ) ) {
            $new_order['country'] = isset( $countries->countries[ $shipping_country ] ) ? $countries->countries[ $shipping_country ]: '';
            $shipping_state = $order->get_shipping_state();
            if( ! empty( $shipping_state ) ) {
                $new_order['state'] = isset( $countries->states[ $shipping_country ], $countries->states[ $shipping_country ][ $shipping_state ] ) ? $countries->states[ $shipping_country ][ $shipping_state ] : $shipping_state;
            }
        }
        $new_order['city'] = $order->get_billing_city();
        if( empty( $new_order['city'] ) ) {
            $new_order['city'] = $order->get_shipping_city();
        }

        $new_order['ip'] = $order->get_customer_ip_address();
        $product_data = $this->ready_product_data( $item->get_data() );
        if( ! empty( $product_data ) ) {
            $new_order['id']         = is_int( $order_id ) ? $order_id : $order_id->get_id();
            $new_order['product_id'] = $item->get_product_id();
            $new_order['title']      = $product_data['title'];
            $new_order['link']       = $product_data['link'];
        }
        $new_order['timestamp'] = $date->getTimestamp();
        return array_merge( $new_order, $this->buyer( $order ));
    }
    /**
     * This function is responsible for getting
     * the buyer name from order.
     *
     * @param WC_Order $order
     * @return void
     */
    protected function buyer( WC_Order $order ){
        $first_name = $last_name = $email = '';
        $buyer_data = [];

        if( empty( $first_name ) ) {
            $first_name = $order->get_billing_first_name();
        }

        if( empty( $last_name ) ) {
            $last_name = $order->get_billing_last_name();
        }

        if( empty( $email ) ) {
            $email = $order->get_billing_email();
        }

        $buyer_data['first_name'] = $first_name;
        $buyer_data['last_name'] = $last_name;
        $buyer_data['name'] = $first_name . ' ' . mb_substr($last_name, 0, 1);
        $buyer_data['email'] = $email;

        return $buyer_data;
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

    public function frontend_html( $data = [], $settings = false, $args = [] ){
        if( class_exists( 'WooCommerce' ) ) {
            return parent::frontend_html( $data, $settings, $args );
        }
    }
}