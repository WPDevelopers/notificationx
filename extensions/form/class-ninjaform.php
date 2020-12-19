<?php

class NotificationXPro_NinjaForms_Extension extends NotificationX_Extension {
    /**
     * Type of notification.
     * @var string
     */
    public $type = 'njf';
    /**
     * Template name
     * @var string
     */
    public $template = 'njf_template';
    /**
     * Theme name
     * @var string
     */
    public $themeName = 'form_theme';
    /**
     * An array of all notifications
     * @var [type]
     */
    protected $notifications = [];
    private $forms = [];

    public function __construct(){
        parent::__construct( $this->template );
        $this->forms = $this->njf_forms();
        add_action( 'wp_ajax_nx_njf_keys', array( $this, 'keys' ) );
        add_filter( 'nx_data_key', array( $this, 'key' ), 10, 2 );
    }

    public function key( $key, $settings ){
        if( $settings->display_type === 'form' && $settings->form_source === 'njf' ) {
            $key = $key . '_' . $settings->njf_form;
        }
        return $key;
    }

    public function keys(){
        if( ! class_exists( 'Ninja_Forms' ) ) {
            return [];
        }
        if( isset( $_GET['action'] ) && $_GET['action'] == 'nx_njf_keys' ) {
            if( !empty( $_GET['form_id'] ) ) {
                $form_id = intval( $_GET['form_id'] );

                global $wpdb;
                $queryresult = $wpdb->get_results( 'SELECT meta_value FROM `' . $wpdb->prefix . 'nf3_form_meta` WHERE parent_id = '.$form_id.' AND meta_key = "formContentData"' );

                $formdata = $queryresult[0]->meta_value;

                $keys = $this->keys_generator( $formdata );

                $returned_keys = array();

                if( is_array( $keys ) && ! empty( $keys ) ) {
                    foreach( $keys as $key ) {
                        $returned_keys[] = array(
                            'text' => ucwords( str_replace( '_', ' ', str_replace( '-', ' ', $key ) ) ),
                            'id' => "tag_$key",
                        );
                    }

                    $returned_keys[] = array(
                        'text' => 'Custom',
                        'id' => 'tag_custom',
                    );

                    echo json_encode( $returned_keys );
                }
            }
        }
        wp_die();
    }

    public function keys_generator( $fieldsString ){
        $fields = array();
        $fieldsdata = unserialize($fieldsString);
        if (!empty($fieldsdata)) {
            foreach ( $fieldsdata as $field ) {
                if (NotificationX_Helper::filter_contactform_key_names($field)){
                    $fields[] = NotificationX_Helper::rename_contactform_key_names($field);
                }
            }
        }
        return $fields;
    }

    public function njf_forms(){
        $forms = [];
        if( ! class_exists( 'Ninja_Forms' ) ) {
            return [];
        }
        global $wpdb;
        $formresult = $wpdb->get_results( 'SELECT id, title FROM `' . $wpdb->prefix . 'nf3_forms` ORDER BY title' );
        if( !empty( $formresult )) {
            foreach ($formresult as $form) {
                $forms[ $form->id ] = $form->title;
            }
        }

        return $forms;
    }

    public function init_fields(){
        $fields = [];

        if( ! class_exists( 'Ninja_Forms' ) ) {
            $installed = $this->plugins( 'ninja-forms/ninja-forms.php' );
            $url = admin_url('plugin-install.php?s=ninja+forms&tab=search&type=term');
            $fields['has_no_njf'] = array(
                'type'     => 'message',
                'message'    => sprintf( '%s <a href="%s">%s</a> %s',
                    __( 'You have to install', 'notificationx' ),
                    $url,
                    __( 'Ninja Forms', 'notificationx' ),
                    __( 'plugin first.', 'notificationx' )
                ),
                'priority' => 0,
            );
        }

        $fields['njf_form'] = array(
            'type' => 'select',
            'label' => __( 'Select a Form', 'notificationx' ),
            'options' => $this->forms,
            'priority' => 90,
        );

        $fields['njf_template_new'] = array(
            'type'     => 'template',
            'builder_hidden' => true,
            'fields' => array(
                'first_param' => array(
                    'type'          => 'select',
                    'ajax'          => 'njf_form',
                    'ajax_action'   => 'nx_njf_keys',
                    'label'         => __('Notification Template' , 'notificationx'),
                    'priority'      => 1,
                    'options'       => array(
                        'tag_name' => __('Select A Tag' , 'notificationx'),
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
                ),
                'custom_first_param' => array(
                    'type'     => 'text',
                    'priority' => 2,
                    'default' => __('Someone' , 'notificationx')
                ),
                'second_param' => array(
                    'type'     => 'text',
                    'priority' => 3,
                    'default' => __('recently contacted via' , 'notificationx')
                ),
                'third_param' => array(
                    'type'     => 'select',
                    'priority' => 4,
                    'options'  => array(
                        'tag_title'       => __('Form Title' , 'notificationx'),
                        'tag_custom_form_title' => __('Custom Title' , 'notificationx'),
                    ),
                    'default' => 'tag_title',
                    'dependency' => array(
                        'tag_custom_form_title' => array(
                            'fields' => [ 'custom_form_title_third_param' ]
                        )
                    ),
                    'hide' => array(
                        'tag_title' => array(
                            'fields' => [ 'custom_form_title_third_param' ]
                        )
                    ),
                ),
                'custom_form_title_third_param' => array(
                    'type'     => 'text',
                    'priority' => 4,
                    'default' => __('' , 'notificationx')
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

        return $fields;
    }

    public function add_fields( $options ){
        $fields = $this->init_fields();

        foreach ( $fields as $name => $field ) {
            if( $name === 'has_no_njf' ) {
                $options[ 'source_tab' ]['sections']['config']['fields'][ $name ] = $field;
                continue;
            }
            $options[ 'content_tab' ]['sections']['content_config']['fields'][ $name ] = $field;
        }

        return $options;
    }

    /**
     * Main Screen Hooks
     */
    public function init_hooks(){
        add_filter( 'nx_metabox_tabs', array( $this, 'add_fields' ) );
        add_filter( 'nx_display_types_hide_data', array( $this, 'hide_fields' ) );
        add_filter( 'nx_form_source', array( $this, 'toggle_fields' ) );
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
     * Some toggleData & hideData manipulation.
     *
     * @param array $options
     * @return void
     */
    public function toggle_fields( $options ) {
        $fields = $this->init_fields();
        $fields = array_keys( $fields );
        $options['dependency'][ $this->type ]['fields'] = $fields;
        // $options['dependency'][ $this->type ]['sections'] = array_merge( [ 'image' ], $options['dependency'][ $this->type ]['sections']);
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
     * This functions is hooked
     *
     * @hooked nx_public_action
     * @return void
     */
    public function public_actions(){
        if( ! $this->is_created( $this->type ) ) {
            return;
        }
        add_action( 'ninja_forms_after_submission', array( $this, 'save_new_records' ));
    }

    public function save_new_records( $form_data ){
        foreach ($form_data['fields'] as $field) {
            $arr = NotificationX_Helper::rename_contactform_key_names($field['key']);
            $data[$arr] = $field['value'];
        }
        $data['title'] = $form_data['settings']['title'];
        $data['timestamp'] = current_time('timestamp');

        if( ! empty( $data ) ) {
            $key = $this->type . '_' . $form_data['form_id'];
            $this->save( $key, $data, $data['timestamp'] );
            return true;
        }
        return false;
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
     * This function is responsible for builder fields
     *
     * @param array $options
     * @return void
     */
    public function builder_toggle_fields( $options ) {
        $fields = $this->init_fields();
        unset( $fields[ $this->template ] );
        $old_fields = isset( $options['source_tab']['sections']['config']['fields']['form_source']['dependency'][ $this->type ]['fields'] ) ? $options['source_tab']['sections']['config']['fields']['form_source']['dependency'][ $this->type ]['fields'] : [];
        $options['source_tab']['sections']['config']['fields']['form_source']['dependency'][ $this->type ]['fields'] = array_merge( array_keys( $fields ), $old_fields);
        return $options;
    }
}