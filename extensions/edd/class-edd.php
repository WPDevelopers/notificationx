<?php
/**
 * This Class is responsible for 
 * Easy Digital Downloads 
 * Conversions
 */
class NotificationX_EDD_Extension extends NotificationX_Extension {
    /**
     *  Type of notification.
     *
     * @var string
     */
    public $type = 'edd';
    public $template = 'woo_template';
    public $themeName = 'theme';
    protected $ordered_products = [];
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
        if( $posts_data['nx_meta_display_type'] === 'conversions' && $posts_data['nx_meta_conversion_from'] === $this->type ) {
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
        add_filter( 'nx_filtered_data', array( $this, 'multiorder_combine' ), 10, 2 );
    }
    public function multiorder_combine( $data, $settings ){
        if( $settings->display_type != 'conversions' ) {
            return $data;
        }
        if( $settings->conversion_from != 'edd' ) {
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

    public function fallback_data( $data, $saved_data, $settings ){
        if( NotificationX_Helper::get_type( $settings ) !== $this->type ) {
            return $data;
        }

        $data['name'] = $this->notEmpty( 'name', $saved_data ) ? $saved_data['name'] : __( 'Someone', 'notificationx' );
        $data['first_name'] = $this->notEmpty( 'first_name', $saved_data ) ? $saved_data['first_name'] : __( 'Someone', 'notificationx' );
        $data['last_name'] = $this->notEmpty( 'last_name', $saved_data ) ? $saved_data['last_name'] : __( 'Someone', 'notificationx' );
        $data['anonymous_title'] = __( 'Anonymous Product', 'notificationx' );
        $data['sometime'] = __( 'Some time ago', 'notificationx' );

        return $data;
    }
    /**
     * Main Screen Hooks
     */
    public function init_hooks(){
        add_filter( 'nx_show_image_options', array( $this, 'image_options' ) );
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
     * Image Options
     *
     * @param array $options
     * @return void
     */
    public function image_options( $options ){
        if( class_exists( 'Easy_Digital_Downloads' ) ) {
            $new = array(
                'product_image' => __('Product Image' , 'notificationx')
            );
            return array_merge( $new, $options );
        }
        return $options;
    }

    /**
     * Needed Fields
     */
    private function init_fields(){
        $fields = [];

        if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
            $fields['has_no_edd'] = array(
                'type'     => 'message',
                'message'    => sprintf( '%s <a href="%s">%s</a> %s', 
                    __( 'You have to install', 'notificationx' ),
                    admin_url('plugin-install.php?s=easy-digital-downloads&tab=search&type=term'),
                    __( 'Easy Digital Downloads', 'notificationx' ),
                    __( 'plugin first.', 'notificationx' )
                ),
                'priority' => 0,
            );
        }

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
            } else {
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
     * @hooked nx_admin_action
     * 
     * @return void
     */
    public function admin_actions(){
        if( ! $this->is_created( $this->type ) ) {
            return;
        }
        /**
         * @since 1.3.9
         */
        add_action( 'edd_update_payment_status', array( $this, 'update_payment_status' ), 10, 3 );
    }
    /**
     * Some toggleData & hideData manipulation.
     *
     * @param array $options
     * @return void
     */
    public function toggle_fields( $options ) {
        $fields = array_keys( $this->init_fields() );
        $fields = array_merge( [ 'show_notification_image' ], $fields );

        $options['dependency'][ $this->type ]['fields'] = array_merge( $fields, $options['dependency'][ $this->type ]['fields']);
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
        $options['source_tab']['sections']['config']['fields']['conversion_from']['dependency'][ $this->type ]['fields'] = array_merge( array_keys( $fields ), $old_fields );
        return $options;
    }
    /**
     * Update Payment Status
     *
     * @param int $payment_id
     * @param string $new_status
     * @param string $old_status
     * @return void
     * @since 1.3.9
     */
    public function update_payment_status( $payment_id, $new_status, $old_status ){
        if ( $new_status !== 'publish' ){
			return;
        }
        $offset = get_option('gmt_offset');
        $temp_notifications = $this->single_order( $payment_id, $offset );
        if( is_array( $temp_notifications ) ) {
            foreach( $temp_notifications as $notification ) {
                $key = uniqid('edd-') . '-' . $notification['product_id'];
                $this->save( $this->type, $notification, $key );
            }
        }
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
                $orders = NotificationX_Helper::sortBy( $orders, 'edd' );
                $this->update_notifications( $this->type, $orders );
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
     * Get all the orders from database using a date query
     * for limitation.
     *
     * @param array $data
     * @return void
     */
    public function get_orders( $data ) {
        if( empty( $data ) ) return;
        $days     = $data['_nx_meta_display_from'];
        $amount   = $data['_nx_meta_display_last'];
        $payments = $this->get_payments( $days, $amount );
        $notifications = [];
        if( is_array( $payments ) && ! empty( $payments ) ) {
            $notifications = $this->ordered_products( $payments, true );
        }
        return $notifications;
    }
    /**
     * This function is responsible for 
     * making ready the product notifications array
     *
     * @param int $payment_id
     * @return void
     */
    protected function ordered_products( $payments, $ready = false ){
        $offset = get_option('gmt_offset');
        $notifications = [];
        foreach( $payments as $payment ) :
            $temp_notifications = $this->single_order( $payment->ID, $offset );
            if( is_array( $temp_notifications ) ) {
                foreach( $temp_notifications as $notification ) {
                    $notifications[] = $notification;
                }
            }
        endforeach;
        return $notifications;
    }

    protected function single_order( $payment_id, $offset = 0 ){
        if( empty( $payment_id ) ) {
            return null;
        }
        $data         = [];
        $payment      = new EDD_Payment( $payment_id );
        $cart_details = $payment->cart_details;
        $user_info    = $payment->user_info;
        
        unset( $user_info['id'] );
        unset( $user_info['discount'] );
        unset( $user_info['address'] );

        $user_info['name'] = $this->name( $user_info['first_name'], $user_info['last_name'] );
        $user_info['timestamp']  = strtotime( $payment->date ) - ( $offset * 60 * 60 );
        $user_info['ip']  = $payment->ip;
        $user_info['id']  = $payment_id;
        if( ! empty( $user_info['ip'] ) ) {
            $user_ip_data = self::remote_get('http://ip-api.com/json/' . $user_info['ip'] );
            if( $user_ip_data ) {
                $user_info['country'] = isset( $user_ip_data->country ) ? $user_ip_data->country : '';
                $user_info['city']    = isset( $user_ip_data->city ) ? $user_ip_data->city : '';
                $user_info['state']    = isset( $user_ip_data->state ) ? $user_ip_data->state : '';
            }
        }
        if ( is_array( $cart_details ) ) {
            foreach ( $cart_details as $cart_index => $download ) {
                $if_has_course = false;
                if( function_exists('tutor_utils') ) {
                    $if_has_course = tutor_utils()->product_belongs_with_course( $download['id'] );
                }
                if( $if_has_course ) {
                    continue;
                }
				if ( ! $if_has_course ){
                    $data['title'] = $download['name'];
                    $data['link'] = get_permalink( $download['id'] );
                    $data['product_id'] = $download['id'];
                    $key = $payment->key . '-' . $download['id'];
                    $notification = array_merge( $user_info, $data );
                    $notifications[] = $notification;
                }
			}
        }
        return $notifications;
    }
    /**
     * This function is responsible for making ready the front of notifications
     *
     * @param array $data
     * @param boolean $settings
     * @param string $template
     * @return void
     */
    public function frontend_html( $data = [], $settings = false, $args = [] ){
        if( class_exists( 'Easy_Digital_Downloads' ) ) {
            return parent::frontend_html( $data, $settings, $args );
        }
    }
}
