<?php

class NotificationX_ReviewX_Extension extends NotificationX_WooCommerceReview_Extension {
    public $type      = 'reviewx';

    public function __construct() {
        parent::__construct();
        $this->notifications = $this->get_notifications( $this->type );
    }

    /**
     * Main Screen Hooks
     */
    public function init_hooks(){
        add_filter( 'nx_metabox_tabs', array( $this, 'add_fields' ) );
        add_filter( 'nx_display_types_hide_data', array( $this, 'hide_fields' ) );
        parent::init_hooks();
    }

    public function hide_fields( $options ) {
        $fields = $this->init_fields();
        foreach ( $fields as $name => $field ) {
            foreach( $options as $opt_key => $opt_value ) {
                $options[ $opt_key ][ 'fields' ][] = $name;
            }
        }
        return $options;
    }

    private function init_fields(){
        $fields = [];
        if( ! class_exists( 'ReviewX' ) ) {
            $installed = $this->plugins( 'reviewx/reviewx.php' );
            $url = admin_url('plugin-install.php?s=woocommerce&tab=search&type=term');
            $fields['has_no_reviewx'] = array(
                'type'     => 'message',
                'message'    => sprintf( '%s <a href="%s">%s</a> %s <button class="nx-on-click-install" data-slug="reviewx" data-plugin_file="reviewx.php" data-nonce="%s">%s</button>',
                    __( 'You have to install', 'notificationx' ),
                    $url,
                    __( 'ReviewX', 'notificationx' ),
                    __( 'plugin.', 'notificationx' ),
                    wp_create_nonce('wpdeveloper_upsale_core_install_notificationx'),
                    __( 'Click Here To Install', 'notificationx' )
                ),
                'priority' => 0,
            );
        }

        return $fields;
    }
    /**
     * This function is responsible for adding fields in main screen
     *
     * @param array $options
     * @return void
     */
    public function add_fields( $options ){
        $fields = $this->init_fields();

        foreach ( $fields as $name => $field ) {
            if( $name === 'has_no_reviewx' ) {
                $options[ 'source_tab' ]['sections']['config']['fields'][ $name ] = $field;
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
        $sections = [];
        $fields = array_keys( $this->init_fields() );
        $options['dependency'][ $this->type ]['fields'] = array_merge( $fields, array( 'show_notification_image', 'wp_reviews_template_adv', 'has_no_woo' ) );
        $options['dependency'][ $this->type ]['sections'] = ['wporg_themes'];

        return $options;
    }

    /**
     * This function is responsible for making ready the comments data!
     *
     * @param int|WP_Comment $comment
     * @return void
     */
    public function add( $comment ){
        $comment_data = [];
        if( ! $comment instanceof WP_Comment ) {
            $comment_id = intval( $comment );
            $comment = get_comment( $comment_id, 'OBJECT' );
        }
        if( $comment->comment_type !== 'review' ) {
            return;
        }

        $comment_data['id']         = $comment->comment_ID;
        $comment_data['product_id'] = $comment->comment_post_ID;
        $comment_data['content']    = $comment->comment_content;
        $comment_data['link']       = get_comment_link( $comment->comment_ID );
        $comment_data['post_title'] = get_the_title( $comment->comment_post_ID );
        $comment_data['post_link']  = get_permalink( $comment->comment_post_ID );
        $comment_data['timestamp']  = strtotime( $comment->comment_date );
        $comment_data['rating']     = get_comment_meta( $comment->comment_ID, 'rating', true );
        $comment_data['title']      = get_comment_meta( $comment->comment_ID, 'reviewx_title', true );

        $comment_data['ip']  = $comment->comment_author_IP;
        $user_ip_data = self::remote_get('http://ip-api.com/json/' . $comment->comment_author_IP );
        if( $user_ip_data ) {
            $comment_data['country'] = isset( $user_ip_data->country ) ? $user_ip_data->country : '';
            $comment_data['city']    = isset( $user_ip_data->city ) ? $user_ip_data->city : '';
            $comment_data['state']    = isset( $user_ip_data->state ) ? $user_ip_data->state : '';
        }

        if( $comment->user_id )  {
            $comment_data['user_id']    = $comment->user_id;
            $user                       = get_userdata( $comment->user_id );
            $comment_data['first_name'] = $user->first_name;
            $comment_data['last_name']  = $user->last_name;
            $comment_data['username']  = $user->display_name;
            $comment_data['name']       = $user->first_name . ' ' . mb_substr( $user->last_name, 0, 1 );
            $trimed = trim( $comment_data['name'] );
            if( empty( $trimed ) ) {
                $comment_data['name'] = $user->user_nicename;
            }
        } else {
            $commenter_name = get_comment_author( $comment->comment_ID );
            $comment_data['username'] = $commenter_name;
            $commenter_name = explode(' ', $commenter_name);
            if( isset( $commenter_name[0] ) ) {
                $comment_data['first_name'] = $commenter_name[0];
            }
            $commenter_count = count( $commenter_name );
            if( isset( $commenter_name[ $commenter_count - 1 ] ) ) {
                $comment_data['last_name'] = $commenter_name[ $commenter_count - 1 ];
            }
        }
        $comment_data['email'] = get_comment_author_email( $comment->comment_ID );
        return $comment_data;
    }
}