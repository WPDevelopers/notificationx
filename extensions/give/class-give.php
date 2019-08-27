<?php
/**
 * This Class is responsible for Give donation integration.
 *
 * @since 1.2.1
 */
class NotificationX_Give_Extension extends NotificationX_Extension {
    /**
     * Type of notification.
     * @var string
     */
    public $type = 'give';
    /**
     * Template name
     * @var string
     */
    public $template = 'woo_template';
    /**
     * Theme name
     * @var string
     */
    public $themeName = 'theme';
    /**
     * An array of all notifications
     * @var [type]
     */
    protected $notifications = [];

    /**
     * NotificationXPro_Give_Extension constructor.
     */
    public function __construct() {
        parent::__construct( $this->template );
        $this->notifications = $this->get_notifications( $this->type );
        add_filter( 'nx_notification_link', array( $this, 'notification_link' ), 10, 2 );
        add_action( 'nx_notification_image_action', array( $this, 'image_action' ) );
        add_filter( 'nx_filtered_data', array( $this, 'filtered_data' ), 10, 2 );
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
        $data['sometime']        = __( 'Sometimes ago', 'notificationx' );

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
    /**
     * This function is hooked
     * @hooked nx_notification_link
     * @param string $link
     * @param stdClass $settings
     * @return string
     */
    public function notification_link( $link, $settings ){
        if( $settings->display_type === 'conversions' && $settings->conversion_url === 'none' ) {
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
        if( $settings->display_type != 'conversions' || $settings->conversion_from != $this->type ) {
            return $image_data;
        }
        $image_url = $alt_title =  '';

        if( $settings->show_default_image ) {
            $default_image = $settings->image_url['url'];
        }

        switch( $settings->show_notification_image ) {
            case 'product_image' :
                if(!empty($data['give_form_id'])){
                    $image_url = get_the_post_thumbnail_url($data['give_form_id'],'thumbnail');
                }
                if(empty($image_url) && !empty($data['give_page_id'])){
                    $image_url = get_the_post_thumbnail_url($data['give_page_id'],'thumbnail');
                }
                $alt_title = !empty( $data['title'] ) ? $data['title'] : '';
                break;
            case 'gravatar':
                $hash = md5( strtolower( trim( $data['email'] ) ) );
                $image_url = "https://www.gravatar.com/avatar/" . $hash . "?s=100";
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
        if( ! class_exists( 'Give' ) ) {
            $fields['has_no_give'] = array(
                'type'     => 'message',
                'message'    => __('You have to install Give Donation plugin first.' , 'notificationx-pro'),
                'priority' => 0,
            );
        }
        $fields['give_forms_control'] = array(
            'label'    => __('Show Notification Of', 'notificationx-pro'),
            'type'     => 'select',
            'priority' => 200,
            'default'  => 'none',
            'options'  => array(
                'none'      => __('All', 'notificationx-pro'),
                'give_form' => __('By Form', 'notificationx-pro'),
            ),
            'dependency' => array(
                'give_form' => array(
                    'fields' => array( 'give_form_list' )
                ),
            )
        );
        $fields['give_form_list'] = array(
            'label'    => __('Select Donation Form', 'notificationx-pro'),
            'type'     => 'select',
            'multiple' => true,
            'priority' => 201,
            'options'  => self::donation_forms()
        );

        return $fields;
    }

    /**
     * Get donation forms
     * @return array
     */
    protected static function donation_forms(){
        $forms = get_posts(array(
            'post_type' => 'give_forms',
            'numberposts' => -1,
        ));
        $forms_list = [];
        if( ! empty( $forms ) ) {
            foreach( $forms as $form ) {
                $forms_list[ $form->ID ] = $form->post_title;
            }
        }
        return $forms_list;
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
            if( $name === 'has_no_give' ) {
                $options[ 'source_tab' ]['sections']['config']['fields'][ $name ] = $field;
                continue;
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
        unset( $fields['give_forms_control'] );
        unset( $fields['give_form_list'] );
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
        // public actions will be here
        add_action( 'give_complete_donation', [ $this, 'save_new_donation' ], 10, 1 );
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
        // admin actions will be here ...
    }
    /**
     * This function is responsible for hide fields in main screen
     *
     * @param array $options
     * @return array
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
        $sales_fields = NotificationX_ToggleFields::woocommerce();
        $fields = array_merge( $sales_fields['fields'], $fields, array('show_notification_image', 'woo_template_new') );
        $sales_fields['fields'] = $fields;
        $options['dependency'][ $this->type ] = $sales_fields;
        $options['hide'][ $this->type ][ 'fields' ] = [ 'woo_template', 'has_no_edd','has_no_ld', 'has_no_woo', 'product_control', 'product_exclude_by', 'product_list', 'category_list', 'exclude_categories', 'exclude_products', 'edd_product_control', 'edd_product_exclude_by', 'edd_product_list', 'edd_category_list', 'edd_exclude_categories', 'edd_exclude_products', 'custom_contents', 'show_custom_image' ];

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
     * This function is generate and save a new notification when a new donation occur
     * @param object $donation
     * @return void
     */

    public function save_new_donation($payment_id) {
        if( empty( $payment_id )) {
            return;
        }
        $args = array(
            'post__in' => [$payment_id]
        );

        $result = new Give_Payments_Query( $args );
        $result = $result->get_payments();
        if(!empty($result)){
            $result = $result[0];
            $key = $result->ID . '-' . $result->form_id;
            if(!in_array($key,$this->notifications)){
                $donation_data = array_merge(array(
                    'id'=> $result->ID,
                    'title' => $result->form_title,
                    'link' => $result->payment_meta['_give_current_url'],
                    'give_form_id' => $result->form_id,
                    'give_page_id' => $result->payment_meta['_give_current_page_id'],
                    'timestamp' => strtotime( $result->date),
                ), $this->get_donor($result));
                $this->save( $this->type, $donation_data, $key);
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
    public function get_notification_ready( $type, $data = array() ) {
        if( ! class_exists( 'Give' ) ) {
            return;
        }
        if( $this->type === $type ) {
            $donations = $this->get_give_donations( $data );
            if( ! empty( $donations ) ) {
                $this->update_notifications( $this->type, $donations );
            }
        }
    }
    /**
     * Get previous give donations after select as source
     * @param array $data
     * @return array
     */
    private function get_give_donations( $data ) {
        if( empty( $data ) ) {
            return null;
        }
        $donations = [];
        $from = date( get_option( 'date_format' ), strtotime( '-' . intval( $data[ '_nx_meta_display_from' ] ) . ' days') );
        $args = array(
            'number' => -1,
            'date_query' => array(
                array(
                    'after'     => $from,
                ),
            ),
        );

        $results = new Give_Payments_Query( $args );
        $results = $results->get_payments();
        if( ! empty( $results ) ) {
            foreach( $results as $result ) {
                $donations[] = array_merge(
                    array(
                        'id'=> $result->ID,
                        'title' => $result->form_title,
                        'link' => $result->payment_meta['_give_current_url'],
                        'give_form_id' => $result->form_id,
                        'give_page_id' => $result->payment_meta['_give_current_page_id'],
                        'timestamp' => strtotime( $result->date),
                    ),
                    $this->get_donor($result)
                );
            }
        }
        return $donations;
    }

    /**
     * Get donor information
     * @param object $donation
     * @return array
     */
    private function get_donor( $donation ) {
        $user_data = [];
        $first_name = $donation->first_name;
        $last_name = $donation->last_name;
        if( ! empty( $first_name ) ) {
            $user_data['first_name'] = $first_name;
        } else {
            $user_data['first_name'] = '';
        }
        if( ! empty( $last_name ) ) {
            $user_data['last_name'] = $last_name;
        } else {
            $user_data['last_name'] = '';
        }
        $user_data['name'] = trim( $user_data[ 'first_name' ].' '.$user_data[ 'last_name' ] );
        $user_data['email'] = $donation->email;
        $user_data['country'] = $donation->address['country'];
        $user_data['city'] = $donation->address['city'];
        if(isset( $_SERVER['REMOTE_ADDR'])){
            $user_data['ip'] = $_SERVER['REMOTE_ADDR'];
            if(empty($user_data['country']) || empty($user_data['city'])){
                $user_ip_data = $this->remote_get('http://ip-api.com/json/' . $user_data['ip']);
                if($user_ip_data){
                    if(empty($user_data['country'])){
                        $user_data['country'] = $user_ip_data->country;
                    }
                    if(empty($user_data['city'])){
                        $user_data['city'] = $user_ip_data->city;
                    }
                }
            }
        }

        return $user_data;
    }
    /**
     * This function is hooked
     * @hooked 'nx_filtered_data'
     * @param array $data
     * @param stdClass $settings
     * @return array
     */
    public function filtered_data( $data, $settings ){
        if( $settings->display_type != 'conversions' ) {
            return $data;
        }
        if( $settings->conversion_from != 'give' ) {
            return $data;
        }
        if( empty( $settings->give_forms_control ) || $settings->give_form_list === 'none' ) {
            return $data;
        }

        if( empty( $settings->give_form_list ) ){
            return $data;
        }
        $new_data_array = [];
        if( ! empty( $data ) ) {
            foreach( $data as $key => $single ) {
                if( in_array( $single['give_form_id'], $settings->give_form_list ) ) {
                    $new_data_array[ $key ] = $single;
                }
            }
        }

        return $new_data_array;
    }

    public function frontend_html( $data = [], $settings = false, $args = [] ){
        if( class_exists( 'Give' ) ) {
            return parent::frontend_html( $data, $settings, $args );
        }
        return '';
    }
}