<?php
/**
 * This Class is responsible for Give donation integration.
 *
 * @since 1.2.5
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
    public $template = 'donation_template';
    /**
     * Theme name
     * @var string
     */
    public $themeName = 'donation_theme';
    /**
     * An array of all notifications
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
        $data['sometime']        = __( 'Some time ago', 'notificationx' );
        if( isset( $saved_data['amount'] ) ) {
            $data['amount']      = give_currency_filter( $saved_data['amount'] ) ;
        }

        return $data;
    }
    /**
     * Main Screen Hooks
     */
    public function init_hooks(){
        add_filter( 'nx_metabox_tabs', array( $this, 'add_fields' ) );
        add_filter( 'nx_display_types_hide_data', array( $this, 'hide_fields' ) );
        add_filter( 'nx_donation_source', array( $this, 'toggle_fields' ) );
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
        if( $settings->display_type === 'donation' && $settings->donation_url === 'none' ) {
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
        if( $settings->display_type != 'donation' || $settings->donation_source != $this->type ) {
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
            $url = admin_url('plugin-install.php?s=give&tab=search&type=term');
            $fields['has_no_give'] = array(
                'type'     => 'message',
                'message'    => sprintf( '%s <a href="%s">%s</a> %s',
                    __( 'You have to install', 'notificationx' ),
                    $url,
                    __( 'GiveWP Donation', 'notificationx' ),
                    __( 'plugin first.', 'notificationx' )
                ),
                'priority' => 0,
            );
        }
        $fields['donation_template_new'] = array(
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
                    'default' => __('recently donated for' , 'notificationx')
                ),
                'amount_param' => array(
                    'type'     => 'select',
                    'priority' => 4,
                    'options'  => array(
                        'tag_amount'       => __('Donation Amount' , 'notificationx'),
                        'tag_none' => __('None' , 'notificationx'),
                    ),
                    'default' => 'tag_none'
                ),
                'third_param' => array(
                    'type'     => 'select',
                    'priority' => 4,
                    'options'  => array(
                        'tag_title'       => __('Donation For Title' , 'notificationx'),
                        'tag_anonymous_title' => __('Anonymous Title' , 'notificationx'),
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

        $fields['donation_template_adv'] = array(
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
        $options['dependency'][ $this->type ]['fields'] = array_merge( $fields, $options['dependency'][ $this->type ]['fields'] );
        $options['dependency'][ $this->type ]['sections'] = array_merge( [ 'image' ], $options['dependency'][ $this->type ]['sections']);
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
                    'amount' => $result->total . ' ' . __( 'for', 'notificationx' ),
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
                        'amount' => $result->total . ' ' . __( 'for', 'notificationx' ),
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
        $user_data['name'] = trim( $user_data[ 'first_name' ] . ' ' . mb_substr( $user_data[ 'last_name' ], 0, 1 ) );
        $user_data['email'] = $donation->email;
        $user_data['country'] = $donation->address['country'];
        $user_data['city'] = $donation->address['city'];
        $user_data['ip'] = give_get_payment_user_ip( $donation->ID );
        if( ( empty( $user_data['country'] ) || empty( $user_data['city'] ) ) && ! empty( $user_data['ip'] ) ) {
            $user_ip_data = self::remote_get('http://ip-api.com/json/' . $user_data['ip']);
            if( $user_ip_data ) {
                if( empty( $user_data['country'] ) ) {
                    $user_data['country'] = isset( $user_ip_data->country ) ? $user_ip_data->country : '';
                }
                if(empty($user_data['city'])){
                    $user_data['city']    = isset( $user_ip_data->city ) ? $user_ip_data->city : '';
                }
            }
        }

        return $user_data;
    }

    public function frontend_html( $data = [], $settings = false, $args = [] ){
        if( class_exists( 'Give' ) ) {
            return parent::frontend_html( $data, $settings, $args );
        }
    }
}