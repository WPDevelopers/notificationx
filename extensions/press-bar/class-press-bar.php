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
        add_action('admin_head-post.php', [ $this, 'my_post_type_xhr'] );
        add_action('admin_head-post-new.php', [ $this, 'my_post_type_xhr'] );
    }
    function my_post_type_xhr(){
        global $post;
        if('notificationx' === $post->post_type){
            $post_url = admin_url('post.php'); #In case we're on post-new.php
            echo "
            <script>
                jQuery(document).ready(function($){
                    //Click handler - you might have to bind this click event another way
                    $('button.nx-bar_with_elementor').click(function(e){
                        e.preventDefault();
                        //Post to post.php
                        var postURL = '$post_url';

                        //Collate all post form data
                        var data = $('form#post').serializeArray();

                        //Set a trigger for our save_post action
                        data.push({ name: 'nx_bar_ajax', value: true });
                        data.push({ name: 'post_status', value: 'publish' });
                        console.log( data );
                        //The XHR Goodness
                        $.post(postURL, data, function(response){
                            var obj = $.parseJSON(response);
                            if(obj.success)
                                alert('Successfully saved post!');
                            else
                                alert('Something went wrong. ' + response);
                        });
                        $('.nx-press-bar-modal-wrapper').addClass('active');
                        return false;
                    });
                });
            </script>";
        }
    }
    public function add_button_for_elementor( $name, $value, $field, $post_id ){
        if( $name !== 'nx_meta_bar_theme' ) {
            return;
        }

        $nonce     = wp_create_nonce( 'nx_create_bar' );
        $the_post  = get_post();
        $bar_id    = $the_post->ID;
        $output    = '';
        $post_meta = get_post_meta( $bar_id, '_nx_bar_elementor_type_id', true );
        if( is_numeric( $post_meta ) && class_exists( '\Elementor\Plugin' ) ) {
            $edit_link = \Elementor\Plugin::$instance->documents->get( $post_meta )->get_edit_url();
            $output .= '<a class="active nx-ele-bar-button" href="'. esc_url( $edit_link ) .'">';
                $output .= __('Edit With Elementor', 'notificationx');
            $output .= '</a>';
            $output .= '<button class="nx-ele-bar-button nx-bar_with_elementor-remove" data-nonce="'. $nonce .'" data-post_id="'. $post_meta .'" data-bar_id="'. $bar_id .'">';
                $output .= __('Remove Elementor Design', 'notificationx');
            $output .= '</button>';
        } else {
            if( class_exists( '\Elementor\Plugin' ) ) {
                $output = '<button class="nx-ele-bar-button nx-bar_with_elementor" data-nonce="'. $nonce .'" data-the_post="'. $bar_id .'">';
                    $output .= __('Build With Elementor', 'notificationx');
                $output .= '</button>';
                ob_start();
                include_once __DIR__ . '/modal.php';
                $output .= ob_get_clean();
            }
        }

        echo $output;
    }
    public function template(){
        return NotificationX_Helper::bar_colored_themes();
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
    public function convert_to_php_array( $array ){
        $new_array = [];
        foreach( $array as $arr ) {
            preg_match('/(.*)[\[](.*)[\]]/', $arr['name'], $matches );
            if( ! empty( $matches ) && is_array( $matches ) ) {
                $new_array[ $matches[1] ][ $matches[2] ] = $arr['value'];
            } else {
                $new_array[ $arr['name'] ] = $arr['value'];
            }
        }
        return $new_array;
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
        if( ! isset( $_POST['bar_id'] ) || ! isset( $_POST['theme'] ) ) {
            return;
        }

        $bar_id = intval( $_POST['bar_id'] );
        $theme = sanitize_text_field( $_POST['theme'] );
        $post_data = $this->convert_to_php_array( $_POST['post_data'] );

        if( ! class_exists( 'NxImporter' ) ) {
            require_once __DIR__ . '/importer.php';
        }

        $importer = new NxImporter();

        $templateID = $importer->create_nx( [
            'theme' => $theme,
            'post_title' => 'Design for NotificationX Bar - ' . $bar_id
        ] );

        if( $templateID && ! is_wp_error( $templateID ) ) {
            $post_data['post_status'] = 'publish';
            NotificationX_MetaBox::save_data( $post_data, $bar_id );
            update_post_meta( $bar_id, '_nx_bar_elementor_type_id', $templateID );
            update_post_meta( $bar_id, '_nx_meta_active_check', 1 );
            update_post_meta( $templateID, '_wp_page_template', 'elementor_canvas' );
            wp_send_json_success(array(
                'post_id' => $templateID,
                'edit_link' => get_edit_post_link( $templateID, 'internal' ),
                'elementor_edit_link' => \Elementor\Plugin::$instance->documents->get( $templateID )->get_edit_url(),
                'visit' => get_permalink( $templateID )
            ));
        } else {
            wp_send_json_error( 'failed' );
        }
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
        require_once plugin_dir_path( __FILE__ ) . 'press-bar-frontend.php';
    }
}