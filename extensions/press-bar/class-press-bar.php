<?php

class NotificationX_PressBar_Extension extends NotificationX_Extension {
    /**
     *  Type of notification.
     *
     * @var string
     */
    public $type = 'press_bar';

    public function __construct() {
        parent::__construct();
        add_action( 'nx_field_after_wrapper', [ $this, 'add_button_for_elementor' ], 10, 4 );
        add_action( 'init', [ $this, 'register_post_type' ] );
        add_action( 'wp_ajax_nx_create_bar', [ $this, 'create_bar_of_type_bar_with_elementor' ] );
        add_action( 'wp_ajax_nx_create_bar_remove', [ $this, 'remove_bar_from_elementor' ] );
    }
    public function add_button_for_elementor( $name, $value, $field, $post_id ){
        if( $name !== 'nx_meta_bar_theme' ) {
            return;
        }

        $nonce = wp_create_nonce( 'nx_create_bar' );
        $the_post = get_post();
        $bar_id = $the_post->ID;

        $post_meta = get_post_meta( $bar_id, '_nx_bar_elementor_type_id', true );

        if( is_numeric( $post_meta ) ) {
            $edit_link = get_edit_post_link( $post_meta );
            $output = '<a class="nx-ele-bar-button" href="'. esc_url( $edit_link ) .'">';
                $output .= __('Edit With Elementor', 'notificationx');
            $output .= '</a>';
            $output .= '<button class="nx-ele-bar-button nx-bar_with_elementor-remove" data-nonce="'. $nonce .'" data-post_id="'. $post_meta .'" data-bar_id="'. $bar_id .'">';
                $output .= __('Remove Elementor Design', 'notificationx');
            $output .= '</button>';
        } else {
            $output = '<button class="nx-ele-bar-button nx-bar_with_elementor" data-nonce="'. $nonce .'" data-the_post="'. $bar_id .'">';
                $output .= __('Build With Elementor', 'notificationx');
            $output .= '</button>';
        }

        echo $output;
    }
    /**
     * Register Post Type for NotificationX Bar.
     *
     * @return void
     */
    public function register_post_type(){
        $args = [
            'label' => __( 'NotificationX Bar', 'notificationx' ),
            'public' => true,
            // 'show_ui' => false,
			'rewrite' => false,
			'menu_icon' => 'dashicons-admin-page',
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => false,
			'exclude_from_search' => true,
			'capability_type' => 'post',
			'hierarchical' => false,
			'supports' => [ 'title', 'content', 'author', 'elementor' ],
        ];
        register_post_type('nx_bar', $args);
    }
    /**
     * This methods is responsible for creating nx_bar post
     * to enable elementor to design the bar for you.
     *
     * @return void
     */
    public function create_bar_of_type_bar_with_elementor(){
        if( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'nx_create_bar' ) ) {
            return;
        }
        if( ! isset( $_POST['bar_id'] ) ) {
            return;
        }

        $bar_id = intval( $_POST['bar_id'] );

        // Create Bar in NX_BAR type, get the ID.
        $nx_bar_type_ID = wp_insert_post([
            'post_type' => 'nx_bar',
            'post_title' => 'Design for NotificationX Bar - ' . $bar_id,
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
        ]);
        // Add nx_bar to bar_id.
        if( is_wp_error( $nx_bar_type_ID ) ) {
            wp_send_json_error( 'failed' );
        }
        update_post_meta( $bar_id, '_nx_bar_elementor_type_id', $nx_bar_type_ID );
        update_post_meta( $nx_bar_type_ID, '_wp_page_template', 'elementor_canvas' );
        $edit_link = get_edit_post_link( $nx_bar_type_ID );
        wp_send_json_success( [
            'link' => $edit_link
        ] );
    }
    public function remove_bar_from_elementor(){
        if( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'nx_create_bar' ) ) {
            return;
        }
        if( ! isset( $_POST['post_id'] ) || ! isset( $_POST['bar_id'] )  ) {
            return;
        }
        $post_id = intval( $_POST['post_id'] );
        $deleted = wp_delete_post( $post_id, true );
        if( $deleted instanceof WP_Post ) {
            $bar_id = intval( $_POST['bar_id'] );
            delete_post_meta( $bar_id, '_nx_bar_elementor_type_id' );
            wp_send_json_success( 'deleted' );
        }
        wp_send_json_error( 'not_deleted' );
    }
    /**
     * Undocumented function
     *
     * @return void
     */
    public static function display( $settings ){
        require plugin_dir_path( __FILE__ ) . 'press-bar-frontend.php';
    }
}