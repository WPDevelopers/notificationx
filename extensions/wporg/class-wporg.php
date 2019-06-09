<?php
/**
 * This Class is responsible for making wporg activity
 * notifications.
 */

class NotificationXPro_WPOrg_Extension extends NotificationX_Extension {
    /**
     *  Type of notification.
     * @var string
     */
    public  $type     = 'wporg';
    public  $template = 'wporg_template';
    public  $themeName = 'wporg_theme';
    public  $meta_key = 'wporg_content';
    public  $api_key = '';
    public  $helper = null;

    public function __construct() {
        parent::__construct();

        $this->load_dependencies();

        if( $this->helper === null ) {
            $this->helper = new NotificationXPro_WPOrg_Helper();
        }
        
        add_filter( 'nx_notification_types', array( $this, 'notification_types' ) );
        add_action( 'nx_notification_image_action', array( $this, 'image_action' ) );
        add_action( 'nx_cron_update_data', array( $this, 'update_data' ), 10, 1 );
        add_action( 'nx_admin_action', array( $this, 'admin_save' ) );
        add_filter( 'cron_schedules', array( $this, 'cache_duration' ) );
    }

    public function load_dependencies(){
        if( ! class_exists( 'NotificationXPro_WPOrg_Helper' ) ) {
            require_once __DIR__ . '/class-wporg-helper.php';
        }
    }
    
    public function admin_save(){
        add_action( 'save_post', array( $this, 'save_post' ), 10, 1 );
    }

    public function image_action(){
        add_filter( 'nx_notification_image', array( $this, 'notification_image' ), 10, 3 );
    }

    public function notification_image( $image_data, $data, $settings ){
        if( $settings->display_type != 'wporg' ) { 
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



    public function notification_types( $options ){
        $options['wporg'] = __('WPOrg' , 'notificationx-pro');
        return $options;
    }

    public function themes(){
        return apply_filters('nxpro_wporg_themes', array(
            'theme-one'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/total-download.png',
            'theme-two'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/7day-download.png',
            'theme-three' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/today-download.png',
            'theme-four' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/actively-using.png',
            'theme-five' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/reviewed.png',
            'theme-six' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/saying-review.png',
            'theme-seven' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/wporg/total-rated.png',
        ));
    }

    private function init_fields(){
        $fields = [];
        
        $fields['wporg_slug'] = array(
            'type'     => 'text',
            'label'    => __('Plugin Slug' , 'notificationx-pro'),
            'priority' => 80,
        );

        $fields['wporg_template'] = array(
            'type'     => 'template',
            'label'    => __('Notification Template' , 'notificationx-pro'),
            'priority' => 85,
            'defaults' => [
                __('{{name}} recently subscribed to', 'notificationx-pro'), '{{title}}', '{{time}}'
            ],
            'variables' => [
                '{{name}}', '{{title}}', '{{time}}'
            ],
        );
        
        return $fields;
    }
    private function init_sections(){
        $sections = [];

        $sections['wporg_themes'] = array(
            'title'      => __('Themes', 'notificationx-pro'),
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
                    'default'	=> 0,
                    'dependency' => [
                        1 => [
                            'sections' => ['wporg_design', 'wporg_typography']
                        ]
                    ]
                ),
            )
        );

        $sections['wporg_design'] = array(
            'title'    => __('Design', 'notificationx-pro'),
            'priority' => 15,
            'reset'    => true,
            'fields'   => array(
                'wporg_bg_color' => array(
                    'type'      => 'colorpicker',
                    'label'     => __('Background Color' , 'notificationx-pro'),
                    'priority'	=> 5,
                    'default'	=> ''
                ),
                'wporg_text_color' => array(
                    'type'      => 'colorpicker',
                    'label'     => __('Text Color' , 'notificationx-pro'),
                    'priority'	=> 10,
                    'default'	=> ''
                ),
                'wporg_border' => array(
                    'type'      => 'checkbox',
                    'label'     => __('Want Border?' , 'notificationx-pro'),
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
                    'label'     => __('Border Size' , 'notificationx-pro'),
                    'priority'	=> 20,
                    'default'	=> '1',
                    'description'	=> 'px',
                ),
                'wporg_border_style' => array(
                    'type'      => 'select',
                    'label'     => __('Border Style' , 'notificationx-pro'),
                    'priority'	=> 25,
                    'default'	=> 'solid',
                    'options'	=> [
                        'solid' => __('Solid', 'notificationx-pro'),
                        'dashed' => __('Dashed', 'notificationx-pro'),
                        'dotted' => __('Dotted', 'notificationx-pro'),
                    ],
                ),
                'wporg_border_color' => array(
                    'type'      => 'colorpicker',
                    'label'     => __('Border Color' , 'notificationx-pro'),
                    'priority'	=> 30,
                    'default'	=> ''
                ),
            )
        );

        $sections['wporg_typography'] = array(
            'title'      => __('Typography', 'notificationx-pro'),
            'priority' => 16,
            'reset'    => true,
            'fields'   => array(
                'wporg_first_font_size' => array(
                    'type'      => 'number',
                    'label'     => __('Font Size' , 'notificationx-pro'),
                    'priority'	=> 5,
                    'default'	=> '13',
                    'description'	=> 'px',
                    'help'	=> __( 'This font size will be applied for <mark>first</mark> row', 'notificationx-pro' ),
                ),
                'wporg_second_font_size' => array(
                    'type'      => 'number',
                    'label'     => __('Font Size' , 'notificationx-pro'),
                    'priority'	=> 10,
                    'default'	=> '14',
                    'description'	=> 'px',
                    'help'	=> __( 'This font size will be applied for <mark>second</mark> row', 'notificationx-pro' ),
                ),
                'wporg_third_font_size' => array(
                    'type'      => 'number',
                    'label'     => __('Font Size' , 'notificationx-pro'),
                    'priority'	=> 15,
                    'default'	=> '11',
                    'description'	=> 'px',
                    'help'	=> __( 'This font size will be applied for <mark>third</mark> row', 'notificationx-pro' ),
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
        add_filter( 'nx_display_type', array( $this, 'toggle_fields' ) );
        // add_filter( 'nx_conversion_from', array( $this, 'toggle_fields' ) );
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

            if( $name == 'has_no_wporg_key' ) {
                $options[ 'source_tab' ]['sections']['config']['fields'][ $name ] = $field;
                continue;    
            }

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
        unset( $sections['wporg_themes']['fields']['wporg_advance_edit'] );
        unset( $fields[ $this->template ] );
        unset( $sections['wporg_design'] );
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
        $fields = array_merge( $this->get_fields() );
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
        $fields = array_merge( $this->get_fields(), ['edd_template', 'woo_template'] );
        $sections = $this->get_sections();
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

        $fields = array_keys( $this->get_fields() );
        $fields = array_merge( 
            ['display_last', 'conversion_position', 'display_from', 'delay_before', 'display_for', 'delay_between', 'loop', 'notification_preview'], 
            $fields
        );

        $options['dependency'][ $this->type ]['fields'] = $fields;
        $options['dependency'][ $this->type ]['sections'] = ['image', 'wporg_themes'];
        $options['hide'][ $this->type ][ 'fields' ] = ['edd_template', 'woo_template', 'has_no_edd', 'has_no_woo', 'product_control', 'product_exclude_by', 'product_list', 'category_list', 'exclude_categories', 'exclude_products', 'edd_product_control', 'edd_product_exclude_by', 'edd_product_list', 'edd_category_list', 'edd_exclude_categories', 'edd_exclude_products', 'custom_contents', 'custom_template', 'show_custom_image' ];

        return $options;
    }
    public function builder_toggle_fields( $options ) {
        $fields = $this->get_fields();
        $sections = $this->get_sections();
        unset( $fields[ $this->template ] );
        $options['source_tab']['sections']['config']['fields']['display_type']['dependency'][ $this->type ]['fields'] = array_keys( $fields );
        $options['source_tab']['sections']['config']['fields']['display_type']['dependency'][ $this->type ]['sections'] = array_keys( $sections );
        return $options;
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
    public function admin_actions(){
        if( ! $this->is_created( $this->type ) ) {
            return;
        }
    }

    public function save_post( $post_id ) {
        $this->update_data( $post_id );
		NotificationX_Cron::set_cron( $post_id );
    }

    public function update_data( $post_id ){
        if ( empty( $post_id ) ) {
            return;
        }

        $plugin_info = $this->get_plugins_data( $post_id );
        
        // NotificationX_Admin::update_post_meta( $post_id, "{$this->meta_key}_reviews", $reviews );
        // NotificationX_Admin::update_post_meta( $post_id, "{$this->meta_key}_total_info", $reviews );
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

        $plugin_slug = NotificationX_Admin::get_post_meta( intval( $post_id ), 'wporg_slug', true );

        if( ! $plugin_slug ) {
            return;
        }

        $reviews_html = $this->helper->get_reviews( $plugin_slug );
        $reviews = $this->helper->extract_reviews_from_html( $reviews_html );


        // dump( $reviews );
        // die;

        return $data;
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
            'display'	=> sprintf( __('Every %s minutes', 'notificationx-pro'), $custom_duration )
        );

        return $schedules;
    }

    public function frontend_html( $data = [], $settings = false, $args = [] ){
        $wporg_api_key = get_option('nxpro_wporg_api_key');
        if( ! empty( $wporg_api_key ) ) {
            return parent::frontend_html( $data, $settings, $args );
        }
    }
}