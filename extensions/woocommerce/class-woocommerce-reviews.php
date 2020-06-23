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
                        $surname_substr = substr( $surname, 0, 1 );
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

        $comment_data['id']         = $comment->comment_ID;
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
            $comment_data['name']       = $user->first_name . ' ' . substr( $user->last_name, 0, 1 );
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
                $star .= '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="14" height="13" viewBox="0 0 14 13"><metadata><?xpacket begin="﻿" id="W5M0MpCehiHzreSzNTczkc9d"?><x:xmpmeta xmlns:x="adobe:ns:meta/" x:xmptk="Adobe XMP Core 5.6-c138 79.159824, 2016/09/14-01:09:01"><rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"><rdf:Description rdf:about=""/></rdf:RDF></x:xmpmeta><?xpacket end="w"?></metadata><image id="Capa_1_copy" data-name="Capa 1 copy" width="14" height="13" xlink:href="data:img/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA4AAAANCAMAAACuAq9NAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAn1BMVEXtihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihntihkAAAALB0bHAAAAM3RSTlMAAYjPGfNomdENAytlBJKtx+b87dKzpeIHvOksC6/eJPX6NzoaZ2PufFnbqlx/EgZXzp2UDFIsAAAAAWJLR0Q0qbHp/QAAAAlwSFlzAAALEgAACxIB0t1+/AAAAAd0SU1FB+MGDA4JMRMQH+0AAABvSURBVAjXY2AAAUYmZgYkwMJqzIbEZTc25uCEcbi4jYGAhxfE5uMXEBQCcY2FRUTFGMSNkQGDhCSCIyXNwCAjC+PJyYONUoDwFJUgJsNklcE8FRhXFcxVMzZW19DUMjbWBnN1BHX1GBj0DQyNGBgA1A4SzLVFctoAAAAASUVORK5CYII="/></svg> ';
            }
            $data['rating'] = $star;
        }
        return parent::frontend_html( $data, $settings, $args );
    }

}