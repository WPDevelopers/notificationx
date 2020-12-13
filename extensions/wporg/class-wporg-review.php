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
        add_filter( 'nx_notification_link', array( $this, 'notification_link' ), 10, 2 );
    }

    public function notification_link( $link, $settings ){
        if( $settings->display_type == 'reviews' && $settings->reviews_source == 'wp_reviews' && $settings->rs_url == 'none' ) {
            return '';
        }
        return $link;
    }

    public function template_string_by_theme( $template, $old_template, $posts_data ){
        if( $posts_data['nx_meta_display_type'] === 'reviews' && $posts_data['nx_meta_reviews_source'] === $this->type ) {
            $theme = $posts_data['nx_meta_wporg_theme'];
            switch( $theme ) {
                case 'review_saying':
                    $template = NotificationX_Helper::regenerate_the_theme( $old_template, array( 'br_before' => [ 'fifth_param', 'sixth_param' ] ) );
                    break;
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
        if( isset( $data['name'] ) ) {
            unset( $data['name'] );
        }
        if( isset( $saved_data['title'] ) ) {
            $data['title'] = htmlspecialchars( $saved_data['title'] );
        }
        $trim_length = 100;
        $name = $saved_data['username'];
        $review_content = __( 'Some review content', 'notificationx' );
        if($settings->wporg_theme == 'review-comment-2' || $settings->wporg_theme == 'review-comment-3'){
            $trim_length = 80;
            $exploded_username = explode(' ',$saved_data['username']);
            if($exploded_username >= 1){
                $name = ucfirst($exploded_username[0]);
                if( isset( $exploded_username[1] ) ) {
                    $surname = $exploded_username[1];
                    if( ! empty( $surname ) ){
                        $surname_substr = mb_substr( $surname, 0, 1 );
                        if (ctype_alpha( $surname_substr ) !== false){
                            $name .= ' '. $surname_substr . '.';
                        }
                    }
                }
            }
        }
        $nx_trimmed_length = apply_filters('nx_text_trim_length', $trim_length, $settings);
        if( ! empty( $saved_data['content'] ) ){
            $review_content = $saved_data['content'];
            if( strlen( $review_content ) > $nx_trimmed_length ) {
                $review_content = substr($saved_data['content'], 0, $nx_trimmed_length).'...';
            }
        }
        if($settings->wporg_theme == 'review-comment-2'){
            $review_content = '" '.$review_content.' "';
        }
        $data['username'] = $name;
        $data['plugin_name_text'] = __('try it out', 'notificationx');
        $data['anonymous_title'] = __('Anonymous', 'notificationx');
        $data['plugin_review'] = htmlspecialchars( $review_content );
        return $data;
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

        $avatar = $image_url = $alt_title =  '';
        switch( $settings->show_notification_image ) {
            case 'product_image' :
                if( isset( $data['icons']['2x'] ) ) {
                    $image_url = $data['icons']['2x'];
                } else {
                    $image_url = isset( $data['icons']['1x'] ) ? $data['icons']['1x'] : '';
                }
                break;
            case 'gravatar' :
                if( isset( $data['avatar'] ) ) {
                    $avatar = $data['avatar']['src'];
                    $image_url = add_query_arg( 's', '200', $avatar );
                }
                break;
        }

        $alt_title = isset( $data['plugin_name'] ) ? $data['plugin_name'] : '';
        $alt_title = empty( $alt_title ) && isset( $data['username'] ) ? $data['username'] : $alt_title;

        $image_data['classes'] = $settings->show_notification_image;

        $image_data['url'] = $image_url;
        $image_data['alt'] = $alt_title;

        return $image_data;
    }

    public function save_post( $post_id , $post, $update) {
        // Verify if this is an auto save routine.
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return $post_id;
        }
        if( $post->post_type !== 'notificationx' || ! $update ) {
            return;
        }
        if( ! $this->check_type( $post_id ) ) {
            return;
        }
        if( $post->post_status === 'trash' ) {
            NotificationX_Cron::clear_schedule( array( 'post_id' => $post_id ) );
            return;
        }
        $this->update_data( $post_id );
		NotificationX_Cron::set_cron( $post_id, 'nx_wp_review_interval' );
    }

    public function update_data( $post_id ){
        if ( empty( $post_id ) ) {
            return;
        }
        if( ! $this->check_type( $post_id ) ) {
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

        $new_data = array();

        $settings = NotificationX_MetaBox::get_metabox_settings( $id );

        $display_type = NotificationX_Helper::get_type( $settings );
        $product_type = $settings->wp_reviews_product_type;

        if( $display_type === $this->type ) {
            $design = NotificationX_Admin::get_post_meta( intval( $id ), 'wporg_theme', true );
            $saved_data = NotificationX_Admin::get_post_meta( intval( $id ), $this->meta_key, true );
            $new_data['rated'] = isset( $saved_data['ratings'] ) ? $saved_data['ratings']['5'] : '';
            $new_data['rating'] = '5';
            $new_data['slug'] = isset( $saved_data['slug'] ) ? $saved_data['slug'] : '';
            $new_data['icons'] = isset( $saved_data['icons'] ) ? $saved_data['icons'] : '';
            $new_data['plugin_name'] = isset( $saved_data['name'] ) ? $saved_data['name'] : '';

            if( $product_type == 'plugin' && isset( $saved_data['slug'] ) ) {
                //TODO: Its has to be specific reviews link.
                $new_data['link'] = 'https://wordpress.org/plugins/' . $saved_data['slug'];
            }

            if( $design === 'total-rated' ) {
                unset( $saved_data['reviews'] );
                $data[ $this->type ] = array( $new_data );
            } else {
                $data[ $this->type ] = array();
                if( isset( $saved_data['reviews'] ) && ! empty( $saved_data['reviews'] ) ) {
                    $this->new_data = $new_data;
                    $new_reviews = array_map( function( $ar ){
                        return array_merge( $ar, $this->new_data);
                    }, $saved_data['reviews'] );
                    $data[ $this->type ] = $new_reviews;
                }
            }
        }

        return $data;
    }

    /**
     * This function is responsible for making the notification ready for first time we make the notification.
     *
     * @param string $type
     * @param array $data
     * @return void
     */
    public function get_notification_ready( $type, $data = array() ){
        if( $this->type === $type ) {
            global $post;
            $post_id = null;
            if( ! is_null( $post ) && $post instanceof WP_Post ) {
                $post_id = $post->ID;
            } else {
                if( isset( $_GET['post'] ) ) {
                    $post_id = intval( $_GET['post'] );
                }
            }
            if( ! is_null( $post_id ) ) {
                $reviews = $this->get_plugins_data( $post_id );
                NotificationX_Admin::update_post_meta( $post_id, $this->meta_key, $reviews );
            }
        }
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
            $reviews = $this->helper->extract_reviews_from_html( $reviews_html, $plugin_slug );
        }

        return $reviews;
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
            'builder_hidden' => true,
            'fields' => array(
                'first_param' => array(
                    'type'     => 'select',
                    'label'    => __('Notification Template' , 'notificationx'),
                    'priority' => 1,
                    'options'  => array(
                        'tag_username' => __('Username' , 'notificationx'),
                        'tag_rated'    => __('Rated' , 'notificationx'),
                        'tag_custom'   => __('Custom' , 'notificationx'),
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
                        'tag_rated' => array(
                            'fields' => [ 'custom_first_param' ]
                        ),
                    ),
                    'default' => 'tag_username'
                ),
                'custom_first_param' => array(
                    'type'     => 'text',
                    'priority' => 2,
                    'default'  => __('Someone' , 'notificationx')
                ),
                'second_param' => array(
                    'type'     => 'text',
                    'priority' => 3,
                    'default'  => __('just reviewed' , 'notificationx')
                ),
                'third_param' => array(
                    'type'     => 'select',
                    'priority' => 4,
                    'options'  => array(
                        'tag_plugin_name'     => __('Plugin Name' , 'notificationx'),
                        'tag_plugin_review'     => __('Review' , 'notificationx'),
                        'tag_anonymous_title' => __('Anonymous Title' , 'notificationx'),
                    ),
                    'default' => 'tag_plugin_name'
                ),
                'fourth_param' => array(
                    'type'     => 'select',
                    'priority' => 5,
                    'options'  => array(
                        'tag_rating'      => __('Rating' , 'notificationx'),
                        'tag_time'        => __('Definite Time' , 'notificationx'),
                        'tag_sometime'        => __('Some time ago' , 'notificationx'),
                    ),
                    'default' => 'tag_rating',
                    'dependency' => array(
                        'tag_sometime' => array(
                            'fields' => [ 'custom_fourth_param' ]
                        )
                    ),
                    'hide' => array(
                        'tag_time' => array(
                            'fields' => [ 'custom_fourth_param' ]
                        ),
                        'tag_rating' => array(
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
            'priority' => 83,
        );

        $fields['review_saying_template_new'] = array(
            'type'     => 'template',
            'builder_hidden' => true,
            'fields' => array(
                'first_param' => array(
                    'type'     => 'select',
                    'label'    => __('Notification Template' , 'notificationx'),
                    'priority' => 1,
                    'options'  => array(
                        'tag_username' => __('Username' , 'notificationx'),
                        'tag_custom'   => __('Custom' , 'notificationx'),
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
                    'default'  => __('Someone' , 'notificationx')
                ),
                'second_param' => array(
                    'type'     => 'text',
                    'priority' => 3,
                    'default'  => __('saying' , 'notificationx')
                ),
                'third_param' => array(
                    'type'     => 'select',
                    'priority' => 4,
                    'options'  => array(
                        'tag_title'     => __('Review Title' , 'notificationx'),
                        'tag_anonymous_title' => __('Anonymous Title' , 'notificationx'),
                    ),
                    'default' => 'tag_title'
                ),
                'fourth_param' => array(
                    'type'     => 'text',
                    'priority' => 4,
                    'default' => __('about', 'notificationx')
                ),
                'fifth_param' => array(
                    'type'     => 'select',
                    'priority' => 5,
                    'options'  => array(
                        'tag_plugin_name'        => __('Plugin Name' , 'notificationx'),
                    ),
                    'default' => 'tag_plugin_name',
                ),
                'sixth_param' => array(
                    'type'     => 'select',
                    'priority' => 7,
                    'options'  => array(
                        'tag_plugin_name_text'        => __('Try it now' , 'notificationx'),
                    ),
                    'default' => 'tag_plugin_name_text',
                ),
            ),
            'label'    => __('Notification Template' , 'notificationx'),
            'priority' => 83,
        );

        $fields['wp_reviews_template_adv'] = array(
            'type'        => 'adv_checkbox',
            'priority'    => 84,
            'button_text' => __('Advanced Template' , 'notificationx'),
            'side'        => 'right',
            'swal'        => true,
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
                    'default'	=> 'total-rated',
                    'options'   => NotificationX_Helper::designs_for_review(),
                    'hide' => [
                        'total-rated' => [
                            'fields' => ['review_saying_template_new']
                        ],
                        'reviewed' => [
                            'fields' => ['review_saying_template_new']
                        ],
                        'review_saying' => [
                            'fields' => ['wp_reviews_template_new']
                        ],
                        'review-comment' => [
                            'fields' => ['review_saying_template_new']
                        ],
                        'review-comment-2' => [
                            'fields' => ['review_saying_template_new']
                        ],
                        'review-comment-3' => [
                            'fields' => ['review_saying_template_new']
                        ]
                    ],
                    'dependency' => [
                        'review_saying' => [
                            'fields' => ['review_saying_template_new']
                        ],
                        'reviewed' => [
                            'fields' => ['wp_reviews_template_new']
                        ],
                        'total-rated' => [
                            'fields' => ['wp_reviews_template_new']
                        ],
                        'review-comment' => [
                            'fields' => ['wp_reviews_template_new', 'content_trim_length']
                        ],
                        'review-comment-2' => [
                            'fields' => ['wp_reviews_template_new', 'content_trim_length']
                        ],
                        'review-comment-3' => [
                            'fields' => ['wp_reviews_template_new', 'content_trim_length']
                        ]
                    ],
                ),
                'wporg_advance_edit' => array(
                    'type'      => 'adv_checkbox',
                    'priority'	=> 10,
                    'dependency' => [
                        1 => [
                            'sections' => ['wporg_design', 'wporg_image_design', 'wporg_typography']
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

        $sections['wporg_image_design'] = array(
            'title'      => __('Image Appearance', 'notificationx'),
            'priority' => 9,
            'reset'    => true,
            'fields'   => array(
                'wporg_image_shape' => array(
                    'type'      => 'select',
                    'label'     => __('Image Shape' , 'notificationx'),
                    'priority'	=> 5,
                    'default'	=> 'circle',
                    'options'	=> [
                        'circle' => __('Circle', 'notificationx'),
                        'rounded' => __('Rounded', 'notificationx'),
                        'square' => __('Square', 'notificationx'),
                    ],
                ),
                'wporg_image_position' => array(
                    'type'      => 'select',
                    'label'     => __('Position' , 'notificationx'),
                    'priority'	=> 10,
                    'default'	=> 'left',
                    'options'	=> [
                        'left' => __('Left', 'notificationx'),
                        'right' => __('Right', 'notificationx'),
                    ],
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
        unset( $fields[ 'wp_reviews_template_adv' ] );

        unset( $sections['wporg_design'] );
        unset( $sections['wporg_image_design'] );
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
        // unset( $sections['wporg_design'] );
        // unset( $sections['wporg_typography'] );

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

        if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
            $fields[] = 'has_no_cron';
        }

        $options['dependency'][ $this->type ]['fields'] = array_merge( $fields, array( 'show_notification_image' ) );
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
        $fields = array_keys( $fields );
        if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
            $fields[] = 'has_no_cron';
        }
        $options['source_tab']['sections']['config']['fields']['reviews_source']['dependency'][ $this->type ]['fields'] = $fields;
        return $options;
    }

    public function frontend_html( $data = [], $settings = false, $args = [] ){

        $data = array_merge( $data, $this->defaults );
        $star = '';
        if( ! empty( $data['rating'] ) ) {
            for( $i = 1; $i <= $data['rating']; $i++ ) {
                $star .= '<svg height="14" viewBox="0 -10 511.98685 511" width="14" xmlns="http://www.w3.org/2000/svg"><path d="m510.652344 185.902344c-3.351563-10.367188-12.546875-17.730469-23.425782-18.710938l-147.773437-13.417968-58.433594-136.769532c-4.308593-10.023437-14.121093-16.511718-25.023437-16.511718s-20.714844 6.488281-25.023438 16.535156l-58.433594 136.746094-147.796874 13.417968c-10.859376 1.003906-20.03125 8.34375-23.402344 18.710938-3.371094 10.367187-.257813 21.738281 7.957031 28.90625l111.699219 97.960937-32.9375 145.089844c-2.410156 10.667969 1.730468 21.695313 10.582031 28.09375 4.757813 3.4375 10.324219 5.1875 15.9375 5.1875 4.839844 0 9.640625-1.304687 13.949219-3.882813l127.46875-76.183593 127.421875 76.183593c9.324219 5.609376 21.078125 5.097657 29.910156-1.304687 8.855469-6.417969 12.992187-17.449219 10.582031-28.09375l-32.9375-145.089844 111.699219-97.941406c8.214844-7.1875 11.351563-18.539063 7.980469-28.925781zm0 0" fill="#ffc107"/></svg>';
            }
            if( ( $data['rating'] + 1 ) <= 5 ) {
                for( $i = $data['rating'] + 1; $i <= 5; $i++ ) {
                    $star .= '<svg height="14" viewBox="0 -10 511.98685 511" width="14" xmlns="http://www.w3.org/2000/svg"><path d="m510.652344 185.902344c-3.351563-10.367188-12.546875-17.730469-23.425782-18.710938l-147.773437-13.417968-58.433594-136.769532c-4.308593-10.023437-14.121093-16.511718-25.023437-16.511718s-20.714844 6.488281-25.023438 16.535156l-58.433594 136.746094-147.796874 13.417968c-10.859376 1.003906-20.03125 8.34375-23.402344 18.710938-3.371094 10.367187-.257813 21.738281 7.957031 28.90625l111.699219 97.960937-32.9375 145.089844c-2.410156 10.667969 1.730468 21.695313 10.582031 28.09375 4.757813 3.4375 10.324219 5.1875 15.9375 5.1875 4.839844 0 9.640625-1.304687 13.949219-3.882813l127.46875-76.183593 127.421875 76.183593c9.324219 5.609376 21.078125 5.097657 29.910156-1.304687 8.855469-6.417969 12.992187-17.449219 10.582031-28.09375l-32.9375-145.089844 111.699219-97.941406c8.214844-7.1875 11.351563-18.539063 7.980469-28.925781zm0 0" fill="#f2f2f2"/></svg>';
                }
            }
            $data['rating'] = $star;
        }
        if(!empty($data['link'])){
            $data['link'] = $data['link'].'/#reviews';
        }
        return parent::frontend_html( $data, $settings, $args );
    }

}