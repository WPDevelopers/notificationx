<?php 

class NotificationXPro_CF7_Extension extends NotificationX_Extension {
    /**
     * Type of notification.
     * @var string
     */
    public $type = 'cf7';
    /**
     * Template name
     * @var string
     */
    public $template = 'form_template';
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

    public function __construct(){
        parent::__construct( $this->template );
    }

    public function cf7_forms(){
        $args = array(
			'post_type' => 'wpcf7_contact_form',
			'order' => 'ASC',
			'posts_per_page' => -1,
        );
        $the_query = new \WP_Query($args);
        $forms = [];
        if( $the_query->have_posts() ) {
            foreach ($the_query->posts as $form) {
                $forms[ $form->ID ] = $form->post_title;
            }
        }
        wp_reset_postdata();
        return $forms;
    }

    public function init_fields(){
        $fields = [];

        if( ! class_exists( 'WPCF7_ContactForm' ) ) {
            $installed = $this->plugins( 'contact-form-7/wp-contact-form-7.php' );
            $url = admin_url('plugin-install.php?s=contact+form+7&tab=search&type=term');
            $fields['has_no_cf7'] = array(
                'type'     => 'message',
                'message'    => __('You have to install <a href="'. $url .'">Contact Form 7</a> plugin first.' , 'notificationx'),
                'priority' => 0,
            );
        }

        $fields['cf7_form'] = array(
            'type' => 'select',
            'label' => __( 'Select a Form', 'notificationx' ),
            'options' => $this->cf7_forms(),
            'priority' => 0,
        );

        $fields['form_template_new'] = array(
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
                        'tag_sometime' => __('Sometimes ago' , 'notificationx'),
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
                    'default' => __( 'Sometimes ago', 'notificationx' )
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
            if( $name === 'has_no_cf7' ) {
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
}