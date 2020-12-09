<?php

class NotificationX_WooCommerceReview_Extension extends NotificationX_Extension {
    /**
     *  Type of notification.
     *
     * @var string
     */
    public $type      = 'woo_reviews';
    public $template  = 'wp_reviews_template';
    public $themeName = 'wporg_theme';
    /**
     * An array of all notifications
     *
     * @var [type]
     */
    protected $notifications = [];

    public function __construct() {
        parent::__construct( $this->template );

        add_action( 'nx_notification_image_action', array( $this, 'image_action' ) ); // Image Action for gravatar
        add_filter( 'nx_notification_link', array( $this, 'notification_link' ), 10, 2 );
    }

    public function notification_link( $link, $settings ){
        if( $settings->display_type == 'reviews' && $settings->reviews_source == 'woo_reviews' && $settings->rs_url == 'none' ) {
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
            $exploded_username = explode(' ', $saved_data['username']);
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
        $data['plugin_name'] = $saved_data['post_title'];
        $data['plugin_name_text'] = __('try it out', 'notificationx');
        $data['anonymous_title'] = __('Anonymous', 'notificationx');
        $data['plugin_review'] = htmlspecialchars( $review_content );
        return $data;
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
                if( has_post_thumbnail( $data['product_id'] ) ) {
                    $product_image = wp_get_attachment_image_src(
                        get_post_thumbnail_id( $data['product_id'] ), 'medium', false
                    );
                    $image_url = is_array( $product_image ) ? $product_image[0] : '';
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
        add_action( 'comment_post', array( $this, 'post_comment' ), 10, 2 );
        add_action( 'trash_comment', array( $this, 'delete_comment' ), 10, 2 );
        add_action( 'deleted_comment', array( $this, 'delete_comment' ), 10, 2 );
        add_action( 'transition_comment_status', array( $this, 'transition_comment_status' ), 10, 3 );
        add_filter( 'nx_fields_data', array( $this, 'conversion_data' ), 10, 2 );
    }
    public function conversion_data( $data, $id ){
        if( ! $id ) {
            return $data;
        }
        $new_data = array();
        $settings = NotificationX_MetaBox::get_metabox_settings( $id );
        $display_type = NotificationX_Helper::get_type( $settings );
        if( $display_type !== 'woo_reviews' ) {
            return $data;
        }
        $templates = get_post_meta( $id, '_nx_meta_wp_reviews_template_new', true );
        if( $templates['first_param'] !== 'tag_rated' ) {
            return $data;
        }

        $from = isset( $settings->display_from ) ? intval( $settings->display_from ) : 0;
        $needed = isset( $settings->display_last ) ? intval( $settings->display_last ) : 0;

        $comments = get_comments([
            'status'     => 'approve',
            'number'     => $needed,
            'post_type'  => 'product',
            'date_query' => [
                'after'     => $from .' days ago',
                'inclusive' => true,
            ]
        ]);
        $typed_data = $data[ $this->type ];
        $this->ratings = [];
        unset( $data[ $this->type ] );
        $this->data = $data;
        array_walk( $comments, function( $item ){
            $rating = get_comment_meta( $item->comment_ID, 'rating', true );
            if( $rating === '5' ) {
                if( isset( $this->ratings[ $item->comment_post_ID ] ) ) {
                    $this->ratings[ $item->comment_post_ID ] = [
                        'comment_ID' => $item->comment_ID,
                        'rated' => ++$this->ratings[ $item->comment_post_ID ]['rated']
                    ];
                } else {
                    $this->ratings[ $item->comment_post_ID ] = [
                        'comment_ID' => $item->comment_ID,
                        'rated' => 1
                    ];
                }
            }
        });
        array_walk( $this->ratings, function( $item, $key ){
            $data = $this->add( $item['comment_ID'] );
            $data['rated'] = $item['rated'];

            $this->data[ $this->type ][] = $data;
        });

        return $this->data;
    }
    /**
     * This function is responsible for making comment notifications ready if comments is approved.
     *
     * @param int $comment_ID
     * @param bool $comment_approved
     * @return void
     */
    public function post_comment( $comment_ID, $comment_approved ){
        if( count( $this->notifications ) === $this->cache_limit ) {
            $sorted_data = NotificationX_Helper::sorter( $this->notifications, 'key' );
            array_pop( $sorted_data );
            $this->notifications = $sorted_data;
        }

        if( 1 === $comment_approved ){
            $this->notifications[ $comment_ID ] = $this->add( $comment_ID );
            /**
             * Save the data to
             * notificationx_data ( options DB. )
             */
            $this->save( $this->type, $this->add( $comment_ID ), $comment_ID );
        }
        return;
    }
        /**
     * This function is responsible for transition comment status
     * from approved to unapproved or unapproved to approved
     *
     * @param string $new_status
     * @param string $old_status
     * @param WP_Comment $comment
     * @return void
     */
    public function transition_comment_status( $new_status, $old_status, $comment ){
        if( 'unapproved' === $new_status ) {
            $this->delete_comment( $comment->comment_ID, $comment );
        }
        if( 'approved' === $new_status ) {
            $this->post_comment( $comment->comment_ID, 1 );
        }
    }
/**
     * If a comment delete, than the notifications data set has to be updated as well.
     * this function is responsible for doing this.
     *
     * @param int $comment_ID
     * @param WP_Comment $comment
     * @return void
     */
    public function delete_comment( $comment_ID, $comment ){
        if( ! empty( $this->notifications ) ) {
            foreach( $this->notifications as $key => $notification ) {
                if( isset( $notification['id'] ) && $notification['id'] === $comment_ID ) {
                    unset( $this->notifications[ $key ] );
                }
            }
            $this->update_notifications( $this->type, $this->notifications );
        }
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
            if( ! is_null( $comments = $this->get_comments( $data ) ) ) {
                $this->update_notifications( $this->type, $comments );
            }
        }
    }
    /**
     * This function is responsible for getting the comments from wp_comments data table.
     *
     * @param array $data
     * @return void
     */
    public function get_comments( $data ) {
        if( empty( $data ) ) return null;

        $from = isset( $data[ '_nx_meta_display_from' ] ) ? intval( $data[ '_nx_meta_display_from' ] ) : 0;
        $needed = isset( $data[ '_nx_meta_display_last' ] ) ? intval( $data[ '_nx_meta_display_last' ] ) : 0;

        $comments = get_comments([
            'status'     => 'approve',
            'number'     => $needed,
            'post_type'  => 'product',
            'date_query' => [
                'after'     => $from .' days ago',
                'inclusive' => true,
            ]
        ]);

        if( empty( $comments ) ) return null;
        $new_comments = [];
        foreach( $comments as $comment ) {
            $new_comments[ $comment->comment_ID ] = $this->add( $comment );;
        }
        return $new_comments;
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
    public function init_hooks(){
        add_filter( 'nx_reviews_source', array( $this, 'toggle_fields' ) );
    }
    /**
     * This function is responsible for render toggle data for conversion
     *
     * @param array $options
     * @return void
     */
    public function toggle_fields( $options ) {
        $sections = [];
        $fields = [];
        $options['dependency'][ $this->type ]['fields'] = array_merge( $fields, array( 'show_notification_image', 'wp_reviews_template_adv', 'has_no_woo' ) );
        $options['dependency'][ $this->type ]['sections'] = ['wporg_themes'];

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
        return parent::frontend_html( $data, $settings, $args );
    }

}