<?php

class NotificationXPro_WPOrgReview_Extension extends NotificationX_Extension {
    /**
     *  Type of notification.
     *
     * @var string
     */
    public $type      = 'wp_reviews';
    public $template  = 'wp_reviews_template';
    public $themeName = 'wporg_theme';
    public $meta_key  = 'wporg_review_content';
    public $api_key   = '';
    public $helper    = null;
    /**
     * An array of all notifications
     *
     * @var [type]
     */
    protected $notifications = [];

    public function __construct() {
        parent::__construct( $this->template );

        $this->load_dependencies();

        if( $this->helper === null ) {
            $this->helper = new NotificationXPro_WPOrg_Helper();
        }

        add_action( 'nx_notification_image_action', array( $this, 'image_action' ) ); // Image Action for gravatar
        add_action( 'nx_cron_update_data', array( $this, 'update_data' ), 10, 1 );
        add_action( 'nx_admin_action', array( $this, 'admin_save' ) );
        add_filter( 'cron_schedules', array( $this, 'cache_duration' ) );
    }

    public function load_dependencies(){
        if( ! class_exists( 'NotificationXPro_WPOrg_Helper' ) ) {
            require_once __DIR__ . '/class-wporg-helper.php';
        }
    }

    /**
     * Image Action
     */
    public function image_action(){
        add_filter( 'nx_notification_image', array( $this, 'notification_image' ), 10, 3 );
    }

    public function notification_image( $image_data, $data, $settings ){
        if( $settings->display_type != 'reviews' || $settings->reviews_source != $this->type ) { 
            return $image_data;
        }
    
        $avatar = '';
        $alt_title = isset( $data['name'] ) ? $data['name'] : $data['title'];

        if( isset( $data['email'] ) ) {
            $avatar = get_avatar_url( $data['email'], array(
                'size' => '100',
            ));
        }

        $image_data['url'] = $avatar;
        $image_data['alt'] = $alt_title;

        return $image_data;
    }

    public function admin_save(){
        add_action( 'save_post', array( $this, 'save_post' ), 10, 1 );
    }

    public function save_post( $post_id ) {
        $this->update_data( $post_id );
		NotificationX_Cron::set_cron( $post_id );
    }

    public function update_data( $post_id ){
        if ( empty( $post_id ) ) {
            return;
        }

        $reviews = $this->get_plugins_data( $post_id );

        NotificationX_Admin::update_post_meta( $post_id, $this->meta_key, $reviews );
    }

    /**
     * This functions is hooked
     * 
     * @hooked nx_public_action
     *
     * @return void
     */
    public function public_actions(){
        if( ! $this->is_created( $this->type ) ) {
            return;
        }
        
        add_filter( 'nx_fields_data', array( $this, 'conversion_data' ), 10, 2 );
    }

    public function conversion_data( $data, $id ){
        if( ! $id ) {
            return $data;
        }
        $data[ $this->type ] = NotificationX_Admin::get_post_meta( intval( $id ), $this->meta_key, true );
        return $data;
    }

    private function member( $data, $title ){
        $member['link'] = '';
        
        return $member;
    }

    public function get_plugins_data( $post_id ) {
        if( ! $post_id ) {
            return;
        }

        $product_type = NotificationX_Admin::get_post_meta( intval( $post_id ), 'wp_reviews_product_type', true );
        $plugin_slug = NotificationX_Admin::get_post_meta( intval( $post_id ), 'wp_reviews_slug', true );

        $reviews = [];

        if( ! $plugin_slug ) {
            return;
        }

        if( $product_type == 'plugin' ) { 
            $reviews_html = $this->helper->get_plugin_reviews( $plugin_slug );
            $reviews = $this->helper->extract_reviews_from_html( $reviews_html );
        }
        

        return $reviews;
    }

    public function cache_duration( $schedules ) {
        $custom_duration = NotificationX_DB::get_settings( 'wporg_cache_duration' );

        if ( ! $custom_duration || empty( $custom_duration ) ) {
            $custom_duration = 45;
        }

        if ( $custom_duration < 5 ) {
            $custom_duration = 5;
        }

        $schedules['nx_cache_interval'] = array(
            'interval'	=> $custom_duration * 60,
            'display'	=> sprintf( __('Every %s minutes', 'notificationx'), $custom_duration )
        );

        return $schedules;
    }

    private function init_fields(){
        $fields = [];

        $fields['wp_reviews_product_type'] = array(
            'type'     => 'select',
            'label'    => __('Product Type' , 'notificationx'),
            'priority' => 79,
            'options' => array(
                'plugin' => __('Plugin' , 'notificationx'),
            )
        );
        
        $fields['wp_reviews_slug'] = array(
            'type'     => 'text',
            'label'    => __('Slug' , 'notificationx'),
            'priority' => 80,
        );

        $fields['wp_reviews_template_new'] = array(
            'type'     => 'template',
            'fields' => array(
                'first_param' => array(
                    'type'     => 'select',
                    'label'    => __('Notification Template' , 'notificationx'),
                    'priority' => 1,
                    'options'  => array(
                        'tag_username' => __('Username' , 'notificationx'),
                        'tag_custom' => __('Custom' , 'notificationx'),
                    ),
                    'dependency' => array(
                        'tag_custom' => array(
                            'fields' => [ 'custom_first_param' ]
                        )
                    ),
                    'hide' => array(
                        'tag_username' => array(
                            'fields' => [ 'custom_first_param' ]
                        ),
                    ),
                    'default' => 'tag_username'
                ),
                'custom_first_param' => array(
                    'type'     => 'text',
                    'priority' => 2,
                    'default' => __('Someone' , 'notificationx')
                ),
                'second_param' => array(
                    'type'     => 'text',
                    'priority' => 3,
                    'default' => __('just reviewed' , 'notificationx')
                ),
                'third_param' => array(
                    'type'     => 'select',
                    'priority' => 4,
                    'options'  => array(
                        'tag_plugin_name'       => __('Plugin/Theme Name' , 'notificationx'),
                        'tag_anonymous_title' => __('Anonymous Title' , 'notificationx'),
                    ),
                    'default' => 'tag_title'
                ),
                'fourth_param' => array(
                    'type'     => 'select',
                    'priority' => 5,
                    'options'  => array(
                        'tag_time'       => __('Definite Time' , 'notificationx'),
                        'sometime' => __('Sometimes ago' , 'notificationx'),
                    ),
                    'default' => 'tag_time'
                ),
            ),
            'label'    => __('Notification Template' , 'notificationx'),
            'priority' => 83,
        );

        $fields['wp_reviews_template_adv'] = array(
            'type'        => 'adv_checkbox',
            'priority'    => 84,
            'button_text' => __('Advance Template' , 'notificationx'),
            'side'        => 'right',
            'dependency'  => array(
                1 => array(
                    'fields' => [ 'wp_reviews_template' ]
                )
            ),
        );

        $fields['wp_reviews_template'] = array(
            'type'     => 'template',
            'priority' => 85,
            'defaults' => [
                __('{{username}} recently reviewed', 'notificationx'), '{{plugin_name}}', '{{time}}'
            ],
            'variables' => [
                '{{username}}', '{{plugin_name}}', '{{title}}', '{{time}}'
            ],
        );
        
        return $fields;
    }
    private function init_sections(){
        $sections = [];

        $sections['wporg_themes'] = array(
            'title'      => __('Themes', 'notificationx'),
            'priority' => 14,
            'fields'   => array(
                'wporg_theme' => array(
                    'type'      => 'theme',
                    'priority'	=> 3,
                    'default'	=> 'theme-one',
                    'options'   => $this->themes(),
                ),
                'wporg_advance_edit' => array(
                    'type'      => 'adv_checkbox',
                    'priority'	=> 10,
                    'dependency' => [
                        1 => [
                            'sections' => ['wporg_design', 'wporg_typography']
                        ]
                    ],
                ),
            )
        );

        $sections['wporg_design'] = array(
            'title'    => __('Design', 'notificationx'),
            'priority' => 15,
            'reset'    => true,
            'fields'   => array(
                'wporg_bg_color' => array(
                    'type'      => 'colorpicker',
                    'label'     => __('Background Color' , 'notificationx'),
                    'priority'	=> 5,
                    'default'	=> ''
                ),
                'wporg_text_color' => array(
                    'type'      => 'colorpicker',
                    'label'     => __('Text Color' , 'notificationx'),
                    'priority'	=> 10,
                    'default'	=> ''
                ),
                'wporg_border' => array(
                    'type'      => 'checkbox',
                    'label'     => __('Want Border?' , 'notificationx'),
                    'priority'	=> 15,
                    'default'	=> 0,
                    'dependency'	=> [
                        1 => [
                            'fields' => [ 'wporg_border_size', 'wporg_border_style', 'wporg_border_color' ]
                        ]
                    ],
                ),
                'wporg_border_size' => array(
                    'type'      => 'number',
                    'label'     => __('Border Size' , 'notificationx'),
                    'priority'	=> 20,
                    'default'	=> '1',
                    'description'	=> 'px',
                ),
                'wporg_border_style' => array(
                    'type'      => 'select',
                    'label'     => __('Border Style' , 'notificationx'),
                    'priority'	=> 25,
                    'default'	=> 'solid',
                    'options'	=> [
                        'solid' => __('Solid', 'notificationx'),
                        'dashed' => __('Dashed', 'notificationx'),
                        'dotted' => __('Dotted', 'notificationx'),
                    ],
                ),
                'wporg_border_color' => array(
                    'type'      => 'colorpicker',
                    'label'     => __('Border Color' , 'notificationx'),
                    'priority'	=> 30,
                    'default'	=> ''
                ),
            )
        );

        $sections['wporg_typography'] = array(
            'title'      => __('Typography', 'notificationx'),
            'priority' => 16,
            'reset'    => true,
            'fields'   => array(
                'wporg_first_font_size' => array(
                    'type'      => 'number',
                    'label'     => __('Font Size' , 'notificationx'),
                    'priority'	=> 5,
                    'default'	=> '13',
                    'description'	=> 'px',
                    'help'	=> __( 'This font size will be applied for <mark>first</mark> row', 'notificationx' ),
                ),
                'wporg_second_font_size' => array(
                    'type'      => 'number',
                    'label'     => __('Font Size' , 'notificationx'),
                    'priority'	=> 10,
                    'default'	=> '14',
                    'description'	=> 'px',
                    'help'	=> __( 'This font size will be applied for <mark>second</mark> row', 'notificationx' ),
                ),
                'wporg_third_font_size' => array(
                    'type'      => 'number',
                    'label'     => __('Font Size' , 'notificationx'),
                    'priority'	=> 15,
                    'default'	=> '11',
                    'description'	=> 'px',
                    'help'	=> __( 'This font size will be applied for <mark>third</mark> row', 'notificationx' ),
                ),
            )
        );
        
        return $sections;
    }

    private function get_fields(){
        return $this->init_fields();
    }

    private function get_sections(){
        return $this->init_sections();
    }

    public function init_hooks(){
        add_filter( 'nx_metabox_tabs', array( $this, 'add_fields' ) );
        add_filter( 'nx_display_types_hide_data', array( $this, 'hide_fields' ) );
        add_filter( 'nx_reviews_source', array( $this, 'toggle_fields' ) );
    }

    public function init_builder_hooks(){
        add_filter( 'nx_builder_tabs', array( $this, 'add_builder_fields' ) );
        add_filter( 'nx_display_types_hide_data', array( $this, 'hide_builder_fields' ) );
        add_filter( 'nx_builder_tabs', array( $this, 'builder_toggle_fields' ) );
    }

    /**
     * This function is responsible for adding fields to helper files.
     *
     * @param array $options
     * @return void
     */
    public function add_fields( $options ){
        $fields = $this->get_fields();

        foreach ( $fields as $name => $field ) {
            $options[ 'content_tab' ]['sections']['content_config']['fields'][ $name ] = $field;
        }

        $sections = $this->get_sections();
        foreach ( $sections as $parent_key => $section ) {
            $options[ 'design_tab' ]['sections'][ $parent_key ] = $section;
        }

        return $options;
    }

    public function add_builder_fields( $options ){
        $fields = $this->get_fields();
        $sections = $this->get_sections();
        unset( $fields[ $this->template ] );
        unset( $fields[ $this->template . '_new' ] );
        unset( $fields[ $this->template . '_adv' ] );
        unset( $sections['wporg_design'] );
        unset( $sections['wporg_themes']['fields']['wporg_advance_edit'] );
        unset( $sections['wporg_typography'] );
        
        foreach ( $fields as $name => $field ) {
            $options['source_tab']['sections']['config']['fields'][ $name ] = $field;
        }
        foreach ( $sections as $sec_name => $section ) {
            $options['design_tab']['sections'][ $sec_name ] = $section;
        }

        return $options;
    }

    /**
     * This function is responsible for hide fields when others type selected.
     *
     * @param array $options
     * @return void
     */
    public function hide_fields( $options ) {
        $fields = $this->get_fields();
        $sections = $this->get_sections();

        foreach ( $fields as $name => $field ) {
            foreach( $options as $opt_key => $opt_value ) {
                if( $opt_key != $this->type ) {
                    $options[ $opt_key ][ 'fields' ][] = $name;
                }
            }
        }

        foreach ( $sections as $section_name => $section ) {
            foreach( $options as $opt_key => $opt_value ) {
                if( $opt_key != $this->type ) { 
                    $options[ $opt_key ][ 'sections' ][] = $section_name;
                }
            }
        }

        return $options;
    }

    public function hide_builder_fields( $options ) {
        $fields = array_merge( $this->get_fields(), [] );
        $sections = $this->get_sections();
        unset( $sections['wporg_design'] );
        unset( $sections['wporg_typography'] );

        // Hide fields from other field types.
        foreach( $fields as $field_key => $field_value ) {
            foreach( $options as $opt_key => $opt_value ) {
                $options[ $opt_key ][ 'fields' ][] = $field_key;
            }
        }

        foreach( $sections as $sec_key => $sec_value ) {
            foreach( $options as $opt_key => $opt_value ) {
                $options[ $opt_key ][ 'sections' ][] = $sec_key;
            }
        }
        
        return $options;
    }
    /**
     * This function is responsible for render toggle data for conversion
     *
     * @param array $options
     * @return void
     */
    public function toggle_fields( $options ) {

        $fields = $this->init_fields();
        $sections = $this->init_sections();
        $sections = array_keys( $sections );
        $fields = array_keys( $fields );

        $options['dependency'][ $this->type ]['fields'] = $fields;
        $options['dependency'][ $this->type ]['sections'] = $sections;

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
        $sections = $this->init_sections();
        unset( $fields[ $this->template ] );
        $old_fields = [];
        $options['source_tab']['sections']['config']['fields']['reviews_source']['dependency'][ $this->type ]['fields'] = array_keys( $fields );
        return $options;
    }

    public function themes(){
        return apply_filters('nxpro_wporg_themes', array(
            'theme-one' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/reviewed.png',
            'theme-two' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/saying-review.png',
            'theme-three' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/total-rated.png',
        ));
    }

    public function frontend_html( $data = [], $settings = false, $args = [] ){
        return parent::frontend_html( $data, $settings, $args );
    }

}