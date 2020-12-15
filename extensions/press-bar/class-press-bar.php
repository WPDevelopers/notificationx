<?php
class NotificationX_PressBar_Extension extends NotificationX_Extension {
    /**
     *  Type of notification.
     *
     * @var string
     */
    public $type = 'press_bar';

    private $nx_elementor_id = null;

    public function __construct() {
        parent::__construct();
        add_action( 'nx_field_after_wrapper', [ $this, 'add_button_for_elementor' ], 10, 4 );
        add_action( 'wp_ajax_nx_create_bar', [ $this, 'create_bar_of_type_bar_with_elementor' ] );
        add_action( 'wp_ajax_nx_create_bar_remove', [ $this, 'remove_bar_from_elementor' ] );
        add_action( 'after_delete_post', [ $this, 'after_delete_post' ], 10, 2 );
        add_action( 'before_delete_post', [ $this, 'before_delete_post' ] );
    }

    public function before_delete_post( $postid ){
        $post_meta = get_post_meta( $postid, '_nx_bar_elementor_type_id', true );
        $this->nx_elementor_id = [
            'post_meta' => $post_meta,
            'postid' => $postid,
        ];
    }

    public function after_delete_post( $postid, $post ){
        if( ! is_null( $this->nx_elementor_id ) && isset( $this->nx_elementor_id['post_meta'] ) && is_numeric( $this->nx_elementor_id['post_meta'] ) && isset( $this->nx_elementor_id['postid'] ) && $this->nx_elementor_id['postid'] === $postid ) {
            wp_delete_post( $this->nx_elementor_id['post_meta'], true );
        }
    }

    public function add_button_for_elementor( $name, $value, $field, $post_id ){
        if( $name !== 'nx_meta_bar_theme' || is_null( $post_id ) ) {
            return;
        }

        $nonce     = wp_create_nonce( 'nx_create_bar' );
        $bar_id    = $post_id;
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
            } else {
                $output = '<div class="nx-bar-install-elementor">';
                    $output .= '<p>To Design Notification Bar with <strong>Elementor Page Builder</strong>, Click on the Following Button: </p>';
                    $output .= '<button data-nonce="'. wp_create_nonce('wpdeveloper_upsale_core_install_notificationx') .'" data-slug="elementor" data-plugin_file="elementor.php" class="nx-ele-bar-button nx-bar_with_elementor_install nx-on-click-install">';
                    $output .= __('Install Elementor', 'notificationx');
                    $output .= '</button>';
                $output .= '</div>';
                ob_start();
                include_once __DIR__ . '/modal.php';
                $output .= ob_get_clean();
                $output .= '<button class="nx-ele-bar-button nx-bar_with_elementor hidden" data-nonce="'. $nonce .'" data-the_post="'. $bar_id .'">';
                    $output .= __('Build With Elementor', 'notificationx');
                $output .= '</button>';
            }
        }

        echo $output;
    }
    public function template(){
        return apply_filters('nx_bar_colored_elementor_themes', array(
            'theme-one'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/bar-elementor/theme-one.jpg',
            'theme-two'   => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/bar-elementor/theme-two.jpg',
            'theme-three' => NOTIFICATIONX_ADMIN_URL . 'assets/img/themes/bar-elementor/theme-three.jpg',
        ));;
    }
    /**
     * Register Post Type for NotificationX Bar.
     *
     * @return void
     */
    public static function register_post_type(){
        $args = [
            'label' => __( 'NotificationX Bar', 'notificationx' ),
            'public' => true,
            'show_ui' => false,
			'rewrite' => false,
			'menu_icon' => 'dashicons-admin-page',
			'show_in_menu' => false,
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
    public static function display( $settings, $is_shortcode = false ){
        require plugin_dir_path( __FILE__ ) . 'press-bar-frontend.php';
    }
}