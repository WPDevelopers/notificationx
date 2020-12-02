<?php

class NotificationX_WP_Comments_Extension extends NotificationX_Extension {

    public $type = 'wp_comments';
    public $template = 'comments_template';
    public $themeName = 'comment_theme';
    public $default_data;

    protected $notifications = [];

    public function __construct() {
        parent::__construct( $this->template );
        $this->notifications = $this->get_notifications( $this->type );

        add_filter( 'nx_notification_link', array( $this, 'notification_link' ), 10, 2 );
    }

    public function fallback_data( $data, $saved_data, $settings ){
        if( NotificationX_Helper::get_type( $settings ) !== $this->type ) {
            return $data;
        }
        $name = $this->notEmpty( 'name', $saved_data ) ? ucfirst($saved_data['name']) : 'Someone';
        $comment = 'Some comment';
        $trim_length = 100;
        if($settings->comment_theme == 'theme-seven-free' || $settings->comment_theme == 'theme-eight-free'){
            $trim_length = 80;
            if(explode(' ',$name) >= 1){
                $username = explode(' ',$name);
                $name = ucfirst($username[0]);
                if(!empty($username[1])){
                    $name .= ' '.mb_substr($username[1],0, 1).'.';
                }
            }
        }
        $nx_trimmed_length = apply_filters('nx_text_trim_length', $trim_length, $settings);
        if($this->notEmpty('id',$saved_data)){
            $comment = get_comment($saved_data['id'])->comment_content;
            if(strlen($comment) > $nx_trimmed_length){
                $comment = substr($comment,0, $nx_trimmed_length).'...';
            }
        }
        if($settings->comment_theme == 'theme-seven-free'){
            $comment = '" '.$comment.' "';
        }
        $data['name'] = __( $name, 'notificationx' );
        $data['first_name'] = __( $this->notEmpty( 'first_name', $saved_data ) ? $saved_data['first_name'] : 'Someone', 'notificationx' );
        $data['last_name'] = __( $this->notEmpty( 'last_name', $saved_data ) ? $saved_data['last_name'] : 'Someone', 'notificationx' );
        $data['display_name'] = __( $this->notEmpty( 'display_name', $saved_data ) ? $saved_data['display_name'] : 'Someone', 'notificationx' );
        $data['anonymous_post'] = __( 'Anonymous Post', 'notificationx' );
        $data['sometime'] = __( 'Some time ago', 'notificationx' );
        $data['post_comment'] = $comment;
        return $data;
    }

    /**
     * Main Screen Hooks
     */
    public function init_hooks(){
        add_filter( 'nx_metabox_tabs', array( $this, 'add_fields' ) );
    }

    public function init_builder_hooks(){
        add_filter( 'nx_builder_tabs', array( $this, 'add_builder_fields' ) );
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

    protected function init_fields(){
        $fields = array();

        $fields['comments_template_new'] = array(
            'type'           => 'template',
            'builder_hidden' => true,
            'fields'         => array(
                'first_param' => array(
                    'type'     => 'select',
                    'label'    => __('Notification Template' , 'notificationx'),
                    'priority' => 1,
                    'options'  => array(
                        'tag_name' => __('Full Name' , 'notificationx'),
                        'tag_first_name' => __('First Name' , 'notificationx'),
                        'tag_last_name' => __('Last Name' , 'notificationx'),
                        'tag_display_name' => __('Display Name' , 'notificationx'),
                        'tag_custom' => __('Custom' , 'notificationx'),
                    ),
                    'dependency' => array(
                        'tag_custom' => array(
                            'fields' => [ 'custom_first_param' ]
                        )
                    ),
                    'hide' => array(
                        'tag_name' => array(
                            'fields' => [ 'custom_first_param' ]
                        ),
                        'tag_first_name' => array(
                            'fields' => [ 'custom_first_param' ]
                        ),
                        'tag_last_name' => array(
                            'fields' => [ 'custom_first_param' ]
                        ),
                        'tag_display_name' => array(
                            'fields' => [ 'custom_first_param' ]
                        ),
                        'tag_post_title' => array(
                            'fields' => [ 'custom_first_param' ]
                        ),
                    ),
                    'default' => 'tag_name'
                ),
                'custom_first_param' => array(
                    'type'     => 'text',
                    'priority' => 2,
                    'default' => __('Someone' , 'notificationx')
                ),
                'second_param' => array(
                    'type'     => 'text',
                    'priority' => 3,
                    'default' => __('commented on' , 'notificationx')
                ),
                'third_param' => array(
                    'type'     => 'select',
                    'priority' => 4,
                    'options'  => array(
                        'tag_post_title'       => __('Post Title' , 'notificationx'),
                        'tag_post_comment'     => __('Post Comment' , 'notificationx'),
                        'tag_anonymous_post'   => __('Anonymous Post' , 'notificationx'),
                    ),
                    'default' => 'tag_post_title'
                ),
                'fourth_param' => array(
                    'type'     => 'select',
                    'priority' => 5,
                    'options'  => array(
                        'tag_time'       => __('Definite Time' , 'notificationx'),
                        'tag_sometime' => __('Some time ago' , 'notificationx'),
                    ),
                    'default' => 'tag_time',
                    'dependency' => array(
                        'tag_sometime' => array(
                            'fields' => [ 'custom_fourth_param' ]
                        )
                    ),
                    'hide' => array(
                        'tag_time' => array(
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
            'priority' => 80,
        );

        $fields['comments_template_adv'] = array(
            'builder_hidden'     => true,
            'type'        => 'adv_checkbox',
            'priority'    => 81,
            'button_text' => __('Advanced Template' , 'notificationx'),
            'side'        => 'right',
            'swal'        => true
        );

        return $fields;
    }

    public function add_fields( $options ){
        $fields = $this->init_fields();

        foreach ( $fields as $name => $field ) {
            $options[ 'content_tab' ]['sections']['content_config']['fields'][ $name ] = $field;
        }

        return $options;
    }
    public function add_builder_fields( $options ){
        $fields = $this->init_fields();

        foreach ( $fields as $name => $field ) {
            $options[ 'design_tab' ]['sections']['comment_themes']['fields'][ $name ] = $field;
        }

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

        global $wp_version;

        $from = isset( $data[ '_nx_meta_display_from' ] ) ? intval( $data[ '_nx_meta_display_from' ] ) : 0;
        $needed = isset( $data[ '_nx_meta_display_last' ] ) ? intval( $data[ '_nx_meta_display_last' ] ) : 0;

        $args = [
            'status'     => 'approve',
            'number'     => $needed,
            'date_query' => [
                'after' => $from .' days ago',
                'inclusive' => true,
            ]
        ];

        if( version_compare( $wp_version, '5.5', '==' ) ) {
            $args['type'] = 'comment';
        }

        $comments = get_comments( $args );

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
            $comment = $this->add( $comment_ID );
            if( is_array( $comment ) ) {
                $this->notifications[ $comment_ID ] = $comment;
                /**
                 * Save the data to
                 * notificationx_data ( options DB. )
                 */
                $this->save( $this->type, $comment, $comment_ID );
            }
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
        global $wp_version;
        $comment_data = [];

        if( ! $comment instanceof WP_Comment ) {
            $comment_id = intval( $comment );
            $comment = get_comment( $comment_id, 'OBJECT' );
        }
        if(
            ( $comment->comment_type !== '' && version_compare( $wp_version, '5.5', '<' ) ) ||
            ( $comment->comment_type !== 'comment' && version_compare( $wp_version, '5.5', '>=' ) )
        ) {
            return;
        }

        $comment_data['id']         = $comment->comment_ID;
        $comment_data['link']       = get_comment_link( $comment->comment_ID );
        $comment_data['post_title'] = get_the_title( $comment->comment_post_ID );
        $comment_data['post_link']  = get_permalink( $comment->comment_post_ID );
        $comment_data['timestamp']  = strtotime( $comment->comment_date );

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
            $comment_data['display_name']  = $user->display_name;
            $comment_data['name']       = $user->first_name . ' ' . mb_substr( $user->last_name, 0, 1 );
            $trimed = trim( $comment_data['name'] );
            if( empty( $trimed ) ) {
                $comment_data['name'] = $user->user_nicename;
            }

        } else {
            $commenter_name = get_comment_author( $comment->comment_ID );
            $comment_data['name'] = $commenter_name;
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

    public function notification_link( $link, $settings ){
        if( $settings->display_type == 'comments' && $settings->comments_source == 'wp_comments' && $settings->comments_url == 'none' ) {
            return '';
        }
        return $link;
    }
}