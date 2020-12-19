<?php
/**
 * This Class is responsible for Tutor LMS integration.
 * @since 1.3.9
 */
class NotificationX_Tutor_Extension extends NotificationX_Extension {
    /**
     * Type of notification.
     * @var string
     */
    public $type = 'tutor';
    /**
     * Template name
     * @var string
     */
    public $template = 'elearning_template';
    /**
     * Theme name
     * @var string
     */
    public $themeName = 'elearning_theme';
    /**
     * An array of all notifications
     * @var [type]
     */
    protected $notifications = [];

    /**
     * NotificationXPro_LearnDash_Extension constructor.
     */
    public function __construct() {
        parent::__construct( $this->template );
        $this->notifications = $this->get_notifications( $this->type );

        add_filter( 'nx_notification_link', array( $this, 'notification_link' ), 10, 2 );
        add_action( 'nx_notification_image_action', array( $this, 'image_action' ) );
    }
    /**
     * @param array $data
     * @param array $saved_data
     * @param stdClass $settings
     * @return array
     */
    public function fallback_data($data, $saved_data, $settings ){
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
        add_filter( 'nx_elearning_source', array( $this, 'toggle_fields' ) );
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
        if( $settings->display_type === 'elearning' && $settings->elearning_url === 'none' ) {
            return '';
        }
        return $link;
    }
    /**
     * Image action
     * @hooked nx_notification_image_action
     * @return void
     */
    public function image_action(){
        add_filter( 'nx_notification_image', array( $this, 'notification_image' ), 10, 3 );
    }
    /**
     * Image action callback
     * @param array $image_data
     * @param array $data
     * @param stdClass $settings
     * @return array
     */
    public function notification_image( $image_data, $data, $settings ){
        if( $settings->display_type != 'elearning' || $settings->elearning_source != $this->type ) {
            return $image_data;
        }
        $image_url = $alt_title =  '';

        if( $settings->show_default_image ) {
            $default_image = $settings->image_url['url'];
        }

        switch( $settings->show_notification_image ) {
            case 'product_image' :
                $image_url = get_the_post_thumbnail_url( $data['product_id'],'thumbnail' );
                $alt_title = !empty( $data['title'] ) ? $data['title'] : '';
                break;
            case 'gravatar':
                $image_url = get_avatar_url($data['user_id'],['size' => '100']);
                $alt_title = !empty( $data['name']) ? $data['name'] : '';
        }

        if( ! $image_url && ! empty( $default_image ) ) {
            $image_url = $default_image;
        }

        $image_data['classes'] = $settings->show_notification_image;

        $image_data['url'] = $image_url;
        $image_data['alt'] = $alt_title;

        return $image_data;
    }
    /**
     * Needed content fields
     * @return array
     */
    private function init_fields(){
        $fields = [];
        if( ! function_exists( 'tutor_lms' ) ) {
            $installed = $this->plugins( 'tutor/tutor.php' );
            $url = admin_url('plugin-install.php?s=tutor&tab=search&type=term');

            $fields['has_no_tutor'] = array(
                'type'     => 'message',
                'message'    => sprintf( '%s <a href="%s">%s</a> %s',
                    __( 'You have to install', 'notificationx' ),
                    $url,
                    __( 'Tutor LMS', 'notificationx' ),
                    __( 'plugin first.', 'notificationx' )
                ),
                'priority' => 0,
            );
        }

        $fields['elearning_template_new'] = array(
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
                    'default' => __('recently enrolled' , 'notificationx')
                ),
                'third_param' => array(
                    'type'     => 'select',
                    'priority' => 4,
                    'options'  => array(
                        'tag_title'       => __('Course Title' , 'notificationx'),
                        'tag_anonymous_title' => __('Anonymous Course' , 'notificationx'),
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

        $fields['elearning_template_adv'] = array(
            'type'        => 'adv_checkbox',
            'priority'    => 91,
            'button_text' => __('Advanced Template' , 'notificationx'),
            'side'        => 'right',
            'swal'        => true
        );

        return $fields;
    }
    /**
     * This function is responsible for adding fields in main screen
     *
     * @param array $options
     * @return array
     */
    public function add_fields( $options ){

        $fields = $this->init_fields();
        if( empty( $fields ) ) {
            return $options;
        }
        foreach ( $fields as $name => $field ) {
            if( $name === 'has_no_tutor' ) {
                $options[ 'source_tab' ]['sections']['config']['fields'][ $name ] = $field;
            }
            $options[ 'content_tab' ]['sections']['content_config']['fields'][ $name ] = $field;
        }
        return $options;
    }
    /**
     * This function is responsible for adding fields in builder
     *
     * @param array $options
     * @return array
     */
    public function add_builder_fields( $options ){
        $fields = $this->init_fields();
        unset( $fields['tutor_product_control'] );
        unset( $fields['tutor_course_list'] );
        if( empty( $fields ) ) {
            return $options;
        }

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

        $monetize_by = 'free';
        if( function_exists( 'tutils' ) ) {
            $temp_monetize_by = tutils()->get_option('monetize_by');
            if( ! empty( $temp_monetize_by ) ) {
                $monetize_by = tutils()->get_option('monetize_by');
            }
        }
        // public actions will be here
        if( class_exists( 'WooCommerce' ) && $monetize_by === 'wc' ) {
            add_action( 'woocommerce_new_order_item', array( $this, 'save_new_enrollment' ), 10, 3 );
        }
        // public actions will be here
        if( class_exists( 'Easy_Digital_Downloads' ) && $monetize_by === 'edd' ) {
            add_action('edd_update_payment_status', array($this, 'save_new_enroll_payment_status'), 10, 3);
        }

        if(
            $monetize_by === 'free' ||
            ( ! class_exists( 'WooCommerce' ) && $monetize_by === 'wc' ) ||
            ( ! class_exists( 'Easy_Digital_Downloads' ) && $monetize_by === 'edd' ) ) {
            add_action( 'tutor_after_enroll', array( $this, 'do_enroll' ), 10, 2 );
        }
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
        $monetize_by = 'free';
        if( function_exists( 'tutils' ) ) {
            $monetize_by = tutils()->get_option('monetize_by');
        }
        // admin actions will be here ...
        if( class_exists('WooCommerce') && $monetize_by == 'wc' ) {
            add_action( 'woocommerce_order_status_changed', array( $this, 'status_transition' ), 10, 4 );
        }
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
     * This function is responsible for hide fields on toggle
     * in builder
     *
     * @param array $options
     * @return array
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
     * @return array
     */
    public function toggle_fields( $options ) {
        $fields = $this->init_fields();
        $fields = array_keys( $fields );
        // FIXME: $fields is removed for Template issue, but it should work if exists.
        $options['dependency'][ $this->type ]['fields'] = array_merge($fields, $options['dependency'][ $this->type ]['fields'] );
        $options['dependency'][ $this->type ]['sections'] = array_merge( [ 'image' ], $options['dependency'][ $this->type ]['sections']);
        $options['hide'][ $this->type ][ 'fields' ] = [ 'woo_template', 'has_no_edd', 'has_no_ld', 'has_no_woo', 'product_control', 'product_exclude_by', 'product_list', 'category_list', 'exclude_categories', 'exclude_products', 'edd_product_control', 'edd_product_exclude_by', 'edd_product_list', 'edd_category_list', 'edd_exclude_categories', 'edd_exclude_products', 'custom_contents', 'show_custom_image' ];
        return $options;
    }
    /**
     * This function is responsible for builder fields
     *
     * @param array $options
     * @return array
     */
    public function builder_toggle_fields( $options ) {
        $fields = $this->init_fields();
        return $options;
    }
    /**
     * This function is generate and save a new notification when user enroll in a new course
     * @param int $user_id
     * @param int $course_id
     * @return void
     */

    public function save_new_enrollment( $item_id,  $item,  $order_id ) {
        $single_notification = $this->ordered_product( $item_id, $item, $order_id );
        if( ! empty( $single_notification ) ) {
            $key = $order_id . '-' . $item_id;
            $this->save( $this->type, $single_notification, $key );
            return true;
        }
        return false;
    }
    public function save_new_enroll_payment_status( $payment_id, $new_status, $old_status ) {
        if ($new_status !== 'publish'){
			return;
        }

        $data         = [];
        $offset       = get_option('gmt_offset');
        $payment      = new \EDD_Payment( $payment_id );
        $cart_details = $payment->cart_details;
        $user_info    = $payment->user_info;

        unset( $user_info['id'] );
        unset( $user_info['discount'] );
        unset( $user_info['address'] );

        $user_info['name'] = $this->name( $user_info['first_name'], $user_info['last_name'] );
        $user_info['timestamp']  = strtotime( $payment->date ) - ( $offset * 60 * 60 );
        $user_info['ip']  = $payment->ip;
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
                $if_has_course = tutor_utils()->product_belongs_with_course( $download['id'] );
				if ( $if_has_course ){
                    $data['title'] = $download['name'];
                    $course_id = $if_has_course->post_id;
                    $data['link'] = get_permalink( $course_id );
                    $data['product_id'] = $course_id;
                    $key = $payment->key . '-' . $download['id'];
                    $notification = array_merge( $user_info, $data );
                    $this->save( $this->type, $notification, $key );
                }
			}
        }
    }
    public function do_enroll( $course_id, $isEnrolled ) {
        $user_id = get_current_user_id();
        $userdata = get_userdata( $user_id );
        $data = [];
        if( isset( $_SERVER['REMOTE_ADDR'] ) ) {
            $user_ip = $_SERVER['REMOTE_ADDR'];
            $data['ip'] = $user_ip;
            if( ! empty( $user_ip ) ) {
                $user_ip_data = self::remote_get('http://ip-api.com/json/' . $user_ip );
                if( $user_ip_data ) {
                    $data['country'] = isset( $user_ip_data->country ) ? $user_ip_data->country : '';
                    $data['city']    = isset( $user_ip_data->city ) ? $user_ip_data->city : '';
                    $data['state']    = isset( $user_ip_data->state ) ? $user_ip_data->state : '';
                }
            }
        }
        $data['first_name'] = $userdata->first_name;
        $data['last_name']  = $userdata->last_name;
        $data['name']       = $userdata->display_name;
        $data['email']       = $userdata->user_email;
        $data['title']      = get_the_title( $course_id );
        $data['link']       = get_permalink( $course_id );
        $data['product_id'] = $course_id;
        $data['timestamp']  = current_time('timestamp');

        if( ! empty( $data ) ) {
            $key = $course_id . '-' . $isEnrolled;
            return $this->save( $this->type, $data, $key );
        }
    }

    public function ready_enrolled_data( $enrolled_data ) {
        $userdata     = get_userdata( $enrolled_data->post_author );
        $data         = [];

        $meta_info = metadata_exists( 'post', $enrolled_data->ID, '_tutor_enrolled_by_order_id' );
        $order_id = 0;
        if( $meta_info == true ) {
            $order_id = get_post_meta( $enrolled_data->ID, '_tutor_enrolled_by_order_id', true );
        }
        $user_data = $this->user_data_by( $enrolled_data->post_author, $order_id );

        $data['title']      = get_the_title( $enrolled_data->post_parent );
        $data['link']       = get_permalink( $enrolled_data->post_parent );
        $data['product_id'] = $enrolled_data->post_parent;
        $data['timestamp']  = strtotime( $enrolled_data->post_date );
        $data = array_merge( $data, $user_data );

        if( ! empty( $data ) ) {
            return $data;
        }
    }

    protected function user_data_by( $user_id = 0, $order_id = 0 ) {
        $data = [];

        if( $user_id != 0 && $order_id === 0 ) {
            $userdata     = get_userdata( $user_id );
            $data['first_name'] = $userdata->first_name;
            $data['last_name']  = $userdata->last_name;
            $data['name']       = $this->name( $userdata->first_name, $userdata->last_name );;
            $data['email']      = $userdata->user_email;
        }

        if( $order_id && class_exists( 'WC_Order' ) ) {
            $order = wc_get_order( $order_id );
            if( $order ) {
                $date = $order->get_date_created();
                $first_name = $last_name = $email = '';
                $first_name = $order->get_billing_first_name();
                $last_name = $order->get_billing_last_name();
                $email = $order->get_billing_email();
                $data['first_name'] = $first_name;
                $data['last_name'] = $last_name;
                $data['name'] = $this->name( $first_name, $last_name );
                $data['email'] = $email;
                $data['timestamp'] = $date->getTimestamp();
            }
        }
        if( $order_id && function_exists( 'edd_get_payment_meta' ) ) {
            $offset       = get_option('gmt_offset');
            $payment_meta = edd_get_payment_meta( $order_id );
            if( is_array( $payment_meta ) ) {
                $userinfo     = $payment_meta['user_info'];
                $date         = $payment_meta['date'];

                $data['name']       = $this->name( $user_info['first_name'], $user_info['last_name'] );
                $data['first_name'] = $user_info['first_name'];
                $data['last_name']  = $user_info['last_name'];
                $data['email']      = $user_info['email'];
                $data['timestamp']  = strtotime( $date ) - ( $offset * 60 * 60 );
            }
        }

        return $data;
    }

    public function status_transition( $id, $from, $to, $order ){
        $items = $order->get_items();
        $if_has_course = true;
        $status = [ 'on-hold', 'cancelled', 'refunded', 'failed', 'pending', 'wcf-main-order' ];
        $done = [ 'completed', 'processing' ];

        if( in_array( $from, $done ) && in_array( $to, $status ) ) {
            foreach( $items as $item ) {
                if( function_exists( 'tutor_utils' ) ) {
                    $if_has_course = tutor_utils()->product_belongs_with_course( $item->get_product_id() );
                }
                if( ! $if_has_course ) {
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
                if( ! $if_has_course ) {
                    continue;
                }

                if( isset( $this->notifications[ $key ] ) ) continue;
                $single_notification = $this->ordered_product( $item->get_id(), $item, $order );
                if( ! empty( $single_notification ) ) {
                    $this->save( $this->type, $single_notification, $key );
                }
            }
        }

        return;
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
        if( $item instanceof WC_Order_Item_Shipping ) {
            return false;
        }

        $product_id = $item->get_product_id();
        $if_has_course = tutor_utils()->product_belongs_with_course( $product_id );
        if( ! $if_has_course ) {
            return false;
        }

        $new_order = [];
        if( is_int( $order_id ) ) {
            $order = new WC_Order( $order_id );
            $status = $order->get_status();
            $done = [ 'completed', 'processing', 'pending' ];
            if( ! in_array( $status, $done ) ){
                return false;
            }
        } else {
            $order = $order_id;
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
        $course_id = $if_has_course->post_id;
        if( ! empty( $product_data ) ) {
            $new_order['id']         = is_int( $order_id ) ? $order_id : $order_id->get_id();
            $new_order['product_id'] = $course_id;
            $new_order['title']      = $product_data['title'];
        }
        $new_order['timestamp'] = $date->getTimestamp();
        $new_order['link']       = get_permalink( $course_id );

        $data = array_merge( $new_order, $this->buyer( $order ));
        return $data;
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
            'title' => $data['name']
        );
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
        $buyer_data['name'] = $this->name( $first_name, $last_name );
        $buyer_data['email'] = $email;

        return $buyer_data;
    }
    /**
     * This function is responsible for making the notification ready for first time we make the notification.
     *
     * @param string $type
     * @param array $data
     * @return void
     */
    public function get_notification_ready( $type, $data = array() ) {
        if( ! function_exists( 'tutor_lms' ) ) {
            return;
        }
        if( $this->type === $type ) {
            $enrollments = $this->get_purchased_course( $data );
            if( ! empty( $enrollments ) ) {
                $enrollments = NotificationX_Helper::sortBy( $enrollments, 'tutor' );
                $this->update_notifications( $this->type, $enrollments);
            }
        }
    }
    /**
     * Get all the orders from database using a date query
     * for limitation.
     *
     * @param array $data
     * @return void
     */
    public function get_purchased_course( $data = array() ) {
        if( empty( $data ) ) return null;
        $orders = [];
        $from =  date( get_option( 'date_format' ), strtotime( '-' . intval( $data[ '_nx_meta_display_from' ] ) . ' days') );
        $enrolled = get_posts( [
            'post_type' => 'tutor_enrolled',
            'post_status' => 'completed',
            'date_query' => array(
                array(
                    'after'     => $from,
                ),
            ),
            'numberposts' => -1,
            'posts_per_page' => -1,
        ] );
        foreach( $enrolled as $single_enroll ) {
            $orders[ $single_enroll->post_parent . '-' . $single_enroll->ID ] = $this->ready_enrolled_data( $single_enroll );
        }
        wp_reset_postdata();
        return $orders;
    }
    public function frontend_html( $data = [], $settings = false, $args = [] ){
        if( function_exists( 'tutor_lms' ) ) {
            return parent::frontend_html( $data, $settings, $args );
        }
    }
}