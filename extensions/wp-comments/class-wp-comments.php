<?php

class NotificationX_WP_Comments_Extension extends NotificationX_Extension {

    public $type = 'comments';
    public $template = 'comments_template';
    public $themeName = 'comment_theme';

    protected $notifications = [];

    public function __construct() {    
        parent::__construct();
        $this->notifications = $this->get_notifications( $this->type );

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
        add_action( 'delete_comment', array( $this, 'delete_comment' ), 10, 2 );
        add_action( 'transition_comment_status', array( $this, 'transition_comment_status' ), 10, 3 );
    }
    /**
     * This function is responsible for the some fields of 
     * wp comments notification in display tab
     *
     * @param array $options
     * @return void
     */
    public function display_tab_section( $options ){
        $options['image']['fields']['show_avatar'] = array(
            'label'       => __( 'Show Gravatar', 'notificationx' ),
            'priority'    => 20,
            'type'        => 'checkbox',
            'default'     => true,
            'description' => __( 'Show the commenter gravatar in notification', 'notificationx' ),
        );

        return $options;
    }
    /**
     * This function is responsible for the some fields of 
     * wp comments notification in display tab
     *
     * @param array $options
     * @return void
     */
    public function content_tab_section( $options ){

        $options['content_config']['fields']['comments_template'] = array(
            'type'     => 'template',
            'label'    => __('Notification Template' , 'notificationx'),
            'priority' => 80,
            'defaults' => [
                __('{{name}} posted comment on', 'notificationx'), '{{post_title}}', '{{time}}'
            ],
            'variables' => [
                '{{name}}', '{{time}}', '{{post_title}}'
            ],
        );

        return $options;
    }
    /**
     * This function responsible for making ready the notifications for the first time
     * we have made a notification.
     *
     * @param string $type
     * @param array $data
     * @return void
     */
    public function get_notification_ready( $type, $data = array() ){
        if( $this->type === $type ) {
            if( ! is_null( $comments = $this->get_comments( $data ) ) ) {
                $this->save( $this->type, $comments );
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
            'status' => 'approve',
                'number'=> $needed,
                'date_query' => [
                    'after' => $from .' days ago',
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
            $this->save( $this->type, $this->notifications );
        }
        return;
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
            $comment = get_comment( intval( $comment ), 'OBJECT' );  
        }
        
        $comment_data['id']         = $comment->comment_ID;
        $comment_data['link']       = get_comment_link( $comment->comment_ID );
        $comment_data['post_title'] = get_the_title( $comment->comment_post_ID );
        $comment_data['post_link']  = get_permalink( $comment->comment_post_ID );
        $comment_data['timestamp']  = strtotime( $comment->comment_date );
        // $comment_data['name'] = get_comment_author( $comment->comment_ID );
        
        if( $comment->user_id )  {
            $comment_data['user_id'] = $comment->user_id;
            $user = get_userdata( $comment->user_id );
            $comment_data['name'] = $user->first_name . ' ' . substr( $user->last_name );
        }
        return $comment_data;
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
        if( isset( $this->notifications[ $comment_ID ] ) ) {
            unset( $this->notifications[ $comment_ID ] );
            /**
             * Delete the data from 
             * notificationx_data ( options DB. )
             */
            $this->save( $this->type, $this->notifications );
        }
    }

}