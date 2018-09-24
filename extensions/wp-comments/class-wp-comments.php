<?php

class FomoPress_WP_Comments_Extension extends FomoPress_Extension {

    protected $type = 'comments';

    protected $notifications = [];

    public function __construct() {    
        parent::__construct();
        $this->notifications = $this->get_notifications( $this->type );
    }
    /**
     * This function is responsible for adding this notification type to admin option.
     * This functions fires when 
     * 
     * @filtered fomopress_display_type_options
     * 
     * @param array $options
     */
    public function display_type( $options ){
        $options['options'][ $this->type ] = __( 'WP Comments', 'fomopress' );
        return $options;
    }
    /**
     * This functions is hooked
     * 
     * @hooked fomopress_public_action
     *
     * @return void
     */
    public function public_actions( $loader ){
        $loader->add_action( 'comment_post', $this, 'post_comment', 10, 2 );
        $loader->add_action( 'trash_comment', $this, 'delete_comment', 10, 2 );
        $loader->add_action( 'delete_comment', $this, 'delete_comment', 10, 2 );
        $loader->add_action( 'transition_comment_status', $this, 'transition_comment_status', 10, 3 );
    }

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
            $sorted_data = FomoPress_Helper::sorter( $this->notifications, 'key' );
            array_pop( $sorted_data );
            $this->notifications = $sorted_data;
        }

        if( 1 === $comment_approved ){
            $comment                    = get_comment( $comment_ID, 'OBJECT' );
            $comment_data['link']       = get_comment_link( $comment_ID );
            $comment_data['post_title'] = get_the_title( $comment->comment_post_ID );
            $comment_data['post_link']  = get_permalink( $comment->comment_post_ID );
            $comment_data['timestamp']  = strtotime( $comment->comment_date );

            // if( $comment->user_id )  {
            //     $comment_data['author_link'] = get_the_author_link( $comment->user_id );
            // }
            $comment_data['author'] = get_comment_author( $comment_ID );
            $this->notifications[ $comment_ID ] = $comment_data;
            /**
             * Save the data to 
             * fomopress_notifications ( options DB. )
             */
            $this->save( $this->type, $this->notifications );
        }
        return;
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
             * fomopress_notifications ( options DB. )
             */
            $this->save( $this->type, $this->notifications );
        }
    }
}
/**
 * Register the extension
 */
fomopress_register_extension( 'FomoPress_WP_Comments_Extension' );