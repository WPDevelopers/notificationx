<?php

namespace NotificationX\Core;

use NotificationX\Admin\Admin;
use NotificationX\Admin\Cron;
use NotificationX\Admin\Entries;
use NotificationX\Admin\Settings;
use NotificationX\Extensions\ExtensionFactory;
use NotificationX\Extensions\GlobalFields;
use NotificationX\FrontEnd\FrontEnd;
use NotificationX\GetInstance;
use NotificationX\NotificationX;

/**
 * @method static PostType get_instance($args = null)
 */
class PostType {
    /**
     * Instance of PostType
     *
     * @var PostType
     */
    use GetInstance;

    /**
     * The type.
     *
     * @since    1.0.0
     * @access   public
     * @var string the post type of notificationx.
     */
    public $type = 'notificationx';
    public $context = 'normal';
    public $active_items;
    public $enabled_source;
    public $_edit_link = 'admin.php?page=nx-edit&post=%d';
    public $format = [
        'nx_id'        => '%d',
        'type'         => '%s',
        'source'       => '%s',
        'theme'        => '%s',
        'enabled'      => '%d',
        'is_inline'    => '%d',
        'global_queue' => '%d',
        'title'        => '%s',
        'created_at'   => '%s',
        'updated_at'   => '%s',
        'data'         => '%s',
    ];

    /**
     * Initially Invoked when initialized.
     *
     * @hook init
     */
    public function __construct() {
        // add_action('init', array($this, 'register'));
        add_action( 'admin_menu', [ $this, 'menu' ], 15 );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
        add_filter( 'nx_get_post', [ $this, 'get_theme_preview_image' ] );
        add_filter( 'nx_get_post', [ $this, 'responsive_size_backward_comp' ] );
        add_filter( 'nx_get_post', [ $this, 'async_select_get_label' ], 10, 2 );
        add_filter( 'nx_save_post', [ $this, 'async_select_remove_label' ], 10, 3 );
        // add_image_size( '_nx_notification_thumb', 100, 100, true );
        add_filter( 'nx_save_post', [ $this, 'maximize_notification_size' ], 10, 3 );
        add_filter( 'nx_get_post', [ $this, 'get_maximize_notification_size' ], 10, 3 );
    }

    /**
     * This method is reponsible for Admin Menu of
     * NotificationX
     *
     * @return void
     */
    public function menu() {
        add_submenu_page( 'nx-admin', __( 'Add New', 'notificationx' ), __( 'Add New', 'notificationx' ), 'edit_notificationx', 'nx-edit', [ Admin::get_instance(), 'views' ], 20 );
        // add_submenu_page('nx-admin', 'Edit', 'Edit', 'edit_notificationx', 'nx-edit', [Admin::get_instance(), 'views'], 20);
    }

    /**
     * Register scripts and styles.
     *
     * @param string $hook
     * @return void
     */
    function admin_enqueue_scripts( $hook ) {
        if ( $hook !== 'toplevel_page_nx-admin' && $hook !== 'notificationx_page_nx-edit' && $hook !== 'notificationx_page_nx-settings' && $hook !== 'notificationx_page_nx-analytics' && $hook !== 'notificationx_page_nx-dashboard' && $hook !== 'notificationx_page_nx-builder' ) {
            return;
        }
        // @todo not sure why did it. maybe remove.
        wp_enqueue_media();

        $tabs = $this->get_localize_scripts();

        $d = include Helper::file( 'admin/js/admin.asset.php' );

        wp_enqueue_script(
            'notificationx-admin',
            Helper::file( 'admin/js/admin.js', true ),
            $d['dependencies'],
            $d['version'],
            true
        );
        wp_localize_script( 'notificationx-admin', 'notificationxTabs', $tabs );
        wp_enqueue_style( 'notificationx-admin', Helper::file( 'admin/css/admin.css', true ), [], $d['version'], 'all' );
        wp_set_script_translations( 'notificationx-admin', 'notificationx' );
        do_action( 'notificationx_admin_scripts' );

        // removing emoji support
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );

    }

    public function get_localize_scripts() {
        $global_fields = GlobalFields::get_instance()->tabs();
        $tabs = NotificationX::get_instance()->normalize( $global_fields );

        $tabs['createRedirect']               = ! current_user_can( 'edit_notificationx' );
        $tabs['analyticsRedirect']            = ! ( current_user_can( 'read_notificationx_analytics' ) && Settings::get_instance()->get( 'settings.enable_analytics', true ) );
        $tabs['quick_build']                  = NotificationX::get_instance()->normalize( QuickBuild::get_instance()->tabs($global_fields) );
        $tabs['rest']                         = REST::get_instance()->rest_data();
        $tabs['current_page']                 = 'add-nx';
        $tabs['analytics']                    = Analytics::get_instance()->get_total_count();
        $tabs['settings']                     = Settings::get_instance()->get_form_data();
        $tabs['settings']['settingsRedirect'] = ! current_user_can( 'edit_notificationx_settings' );
        $tabs['settings']['analytics']        = $tabs['analytics'];
        $tabs['admin_url']                    = get_admin_url();
        $tabs['nx_feedback_shared']           = get_option('nx_feedback_shared',false);
        $tabs['assets']                       = [
            'admin'  => NOTIFICATIONX_ADMIN_URL,
            'public' => NOTIFICATIONX_PUBLIC_URL,
        ];

        $tabs = apply_filters( 'nx_builder_configs', $tabs );
        return $tabs;
    }

    /**
     * Save data on post save.
     *
     * @param int $post_id
     * @return void
     */
    public function save_post( $data ) {
        $results = [
            'status' => 'success',
        ];

        if ( ! empty( $data['update_status'] ) ) {
            return $this->update_status( $data );
        }

        if ( ! isset( $data['enabled'] ) ) {
            $data['enabled'] = $this->can_enable( $data['source'] );
        }

        $title = isset( $data['title'] ) ? $data['title'] : '';
        unset( $data['title'] );

        $post = [
            'type'         => $data['type'],
            'source'       => $data['source'],
            'theme'        => $data['themes'],
            'global_queue' => ! empty( $data['global_queue'] ) ? $data['global_queue'] : false,
            'enabled'      => $data['enabled'],
            'is_inline'    => ! empty( $data['inline_location'] ),
            'title'        => $title,
            'data'         => $data,
        ];
        if ( isset( $data['updated_at'] ) ) {
            $post['updated_at'] = $data['updated_at'];
        }

        $nx_id = isset( $data['nx_id'] ) ? $data['nx_id'] : 0;

        $post = apply_filters( "nx_save_post_{$data['source']}", $post, $data, $nx_id );
        $post = apply_filters( 'nx_save_post', $post, $data, $nx_id );

        if ( ! empty( $nx_id ) ) {
            if ( empty( $post['updated_at'] ) ) {
                $post['updated_at'] = Helper::mysql_time();
            }
            if ( $this->update_post( $post, $nx_id ) === false ) {
                $results['status'] = 'error';
            }
        } else {
            $nx_id = $this->insert_post( $post );
        }
        $data['nx_id']         = $nx_id;
        $post['nx_id']         = $nx_id;
        $post['data']['nx_id'] = $nx_id;
        // return $GLOBALS['wpdb']->last_query;

        $data = apply_filters( "nx_get_post_{$data['source']}", $data );
        $data = apply_filters( 'nx_get_post', $data );
        do_action( "nx_saved_post_{$data['source']}", $post, $data, $nx_id );
        do_action( 'nx_saved_post', $post, $data, $nx_id );

        $results['nx_id'] = $nx_id;
        return $data;
    }

    /**
     * Save data on post save.
     *
     * @param int $post_id
     * @return bool
     */
    public function update_status( $data ) {
        $is_enabled = $this->is_enabled( $data['nx_id'] );
        if ( $is_enabled == $data['enabled'] ) {
            return true;
        }
        if( empty( $data['source'] ) ) {
            return false;
        }
        if ( $this->can_enable( $data['source'] ) || ( isset( $data['enabled'] ) && $data['enabled'] == false ) ) {
            $post = [
                'enabled' => $data['enabled'],
                // 'updated_at' => Helper::mysql_time(),
            ];
            if ( $data['enabled'] == false ) {
                // clear cron when disabled.
                Cron::get_instance()->clear_schedule( $data['nx_id'] );
            }
            else {
                $extension = ExtensionFactory::get_instance()->get($data['source']);
                if (!empty($extension) && !empty($extension->cron_schedule)) {
                    Cron::get_instance()->set_cron($data['nx_id'], $extension->cron_schedule);
                }
            }
            $this->update_enabled_source( $data );
            return $this->update_post( $post, $data['nx_id'] );
        }
        else if ( !$this->can_enable( $data['source'] ) ) {
            return $this->can_enable( $data['source'], true );
        }
        return false;
    }

    /**
     * Save data on post save.
     *
     * @param int $post_id
     * @return void
     */
    public function update_meta( $nx_id, $key, $value ) {
        $post                 = Database::get_instance()->get_post( Database::$table_posts, $nx_id, 'data, updated_at' );
        $post['data'][ $key ] = $value;
        return $this->update_post( $post, $nx_id );
    }

    public function get_active_items() {
        if ( ! is_array( $this->active_items ) ) {
            $this->active_items = $this->get_col( 'source', [] );
        }
        return $this->active_items;
    }

    public function get_enabled_source() {
        if ( ! is_array( $this->enabled_source ) ) {
            $this->enabled_source = [];
            $enabled_source       = $this->get_posts(
                [
                    'enabled' => true,
                ],
                'nx_id, source, type'
            );
            if ( is_array( $enabled_source ) ) {
                foreach ( $enabled_source as $post ) {
                    $this->enabled_source[ $post['source'] ][] = $post['nx_id'];
                }
            }
        }
        return $this->enabled_source;
    }

    public function update_enabled_source( $post ) {
        if ( empty( $post['source'] ) || empty( $post['nx_id'] ) ) {
            return;
        }
        if ( ! empty( $this->enabled_source[ $post['source'] ] ) ) {
            foreach ( $this->enabled_source as $source => $ids ) {
                if ( $post['enabled'] ) {
                    if ( ! in_array( $post['nx_id'], $ids ) ) {
                        $this->enabled_source[ $source ][] = $post['nx_id'];
                    }
                } else {
                    if ( $key = array_search( $post['nx_id'], $ids ) ) {
                        unset( $this->enabled_source[ $source ][ $key ] );
                    }
                }
            }
        } else {
            if ( $post['enabled'] ) {
                $this->enabled_source[ $post['source'] ][] = $post['nx_id'];
            }
        }
    }

    public function is_enabled( $id ) {
        $enabled_source = $this->get_enabled_source();
        foreach ( $enabled_source as $source => $ids ) {
            if ( in_array( $id, $ids ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks whether a notification can be enabled.
     *
     * @param string $source
     * @return boolean
     */
    public function can_enable( $source ) {
        $rest   = func_num_args() == 2 ? func_get_arg(1) : null;
        $return = false;

        // if ( $source === 'press_bar' ) {
        //     $return = true;
        // }

        $enabled_source = $this->get_enabled_source();
        if ( $source == 'press_bar' && ( isset( $enabled_source['press_bar'] ) && count( $enabled_source['press_bar'] ) ) ) {
            $return = false;
        }
        // unset( $enabled_source['press_bar'] );
        if ( count( $enabled_source ) == 0 ) {
            $return = true;
        }

        $ext = ExtensionFactory::get_instance()->get( $source );
        if ( $ext && $ext->is_pro && ! NotificationX::is_pro() ) {
            $return = false;
        }
        
        return apply_filters('nx_can_enable', $return, $source, $rest);
    }

    // Wrapper function for Database functions.
    public function insert_post( $post ) {
        if ( empty( $post['created_at'] ) ) {
            $post['created_at'] = Helper::mysql_time();
        }
        if ( empty( $post['updated_at'] ) ) {
            $post['updated_at'] = Helper::mysql_time();
        }
        return Database::get_instance()->insert_post( Database::$table_posts, $post, $this->format );
    }

    public function update_post( $post, $post_id ) {
        return Database::get_instance()->update_post( Database::$table_posts, $post, $post_id, $this->format );
    }

    public function get_post( $post_id, $select = '*' ) {
        $posts = $this->get_posts([
            'nx_id' => intval( $post_id ),
            ], $select
        );

        return ! empty( $posts[0] ) ? $posts[0] : null;
    }

    public function __get_posts( $posts , $select = '') {
        foreach ( $posts as $key => $value ) {
            $value = get_object_vars($value);
            if ( ! empty( $value['data'] ) ) {
                $data                  = (array) maybe_unserialize( $value['data'] );
                $value                 = array_merge( $data, $value );
                $value['enabled']      = (bool) $value['enabled'];
                $value['global_queue'] = (bool) $value['global_queue'];
                unset( $value['data'] );
            }
            // @todo maybe remove if there is another better way.
            if ( '*' === $select ) {
                $value = NotificationX::get_instance()->normalize_post( $value );
            }
            if ( ! empty( $value['source'] ) ) {
                $value = apply_filters( "nx_get_post_{$value['source']}", $value, $this->context );
            }
            $posts[ $key ] = apply_filters( 'nx_get_post', $value, $this->context );
            $source                          = $value['source'];
            $posts[ $key ]['can_regenerate'] = false;
            $extension                       = ExtensionFactory::get_instance()->get( $source );
            $posts[ $key ]['source_label']   = $extension->title;
            if ( ! empty( $extension ) && method_exists( $extension, 'get_notification_ready' ) && $extension->is_active( false ) ) {
                $posts[ $key ]['can_regenerate'] = true;
            }
            if ( ! empty( $extension ) && $extension->get_type() ) {
                $type                        = $extension->get_type();
                $posts[ $key ]['type_label'] = $type->dashboard_title ?: $type->title;
            }
        }
        $posts = apply_filters( 'nx_get_posts', $posts, $this->context );
        return $posts;
    }

    public function get_posts( $wheres = [], $select = '*', $join_table = '', $group_by_col = '', $join_type = 'LEFT JOIN', $extra_query = '' ) {
        $posts = Database::get_instance()->get_posts( Database::$table_posts, $select, $wheres, $join_table, $group_by_col, $join_type, $extra_query );
        foreach ( $posts as $key => $value ) {
            if ( ! empty( $value['data'] ) ) {
                $value                 = array_merge( $value['data'], $value );
                $value['enabled']      = (bool) $value['enabled'];
                $value['global_queue'] = (bool) $value['global_queue'];
                unset( $value['data'] );
            }
            // @todo maybe remove if there is another better way.
            if ( '*' === $select ) {
                $value = NotificationX::get_instance()->normalize_post( $value );
            }
            if ( ! empty( $value['source'] ) ) {
                $value = apply_filters( "nx_get_post_{$value['source']}", $value, $this->context );
            }
            $posts[ $key ] = apply_filters( 'nx_get_post', $value, $this->context );
        }
        $posts = apply_filters( 'nx_get_posts', $posts, $this->context );
        return $posts;
    }

    public function get_posts_by_ids( $nx_ids, $source = '', $select = '*' ) {
        $nx_ids = array_map( 'absint', $nx_ids );
        $wheres = [ 'nx_id' => [ 'IN', $nx_ids ] ];
        if ( ! empty( $source ) ) {
            $wheres['source'] = $source;
        }
        $posts = $this->get_posts( $wheres, $select );
        return $posts;
    }

    public function get_post_with_analytics( $wheres = [], $extra_query = '' ) {
        $posts = $this->get_posts( $wheres, 'a.*, SUM(b.clicks) AS clicks, SUM(b.views) AS views', Database::$table_stats, 'a.nx_id', 'LEFT JOIN', $extra_query );
        foreach ( $posts as $key => $post ) {
            $source                          = $post['source'];
            $posts[ $key ]['can_regenerate'] = false;
            $extension                       = ExtensionFactory::get_instance()->get( $source );
            $posts[ $key ]['source_label']   = $extension->title;
            if ( ! empty( $extension ) && method_exists( $extension, 'get_notification_ready' ) && $extension->is_active( false ) ) {
                $posts[ $key ]['can_regenerate'] = true;
            }
            if ( ! empty( $extension ) && $extension->get_type() ) {
                $type                        = $extension->get_type();
                $posts[ $key ]['type_label'] = $type->dashboard_title ?: $type->title;
            }
        }
        return $posts;
    }

    public function get_col( $col, $wheres ) {
        return Database::get_instance()->get_col( Database::$table_posts, $col, $wheres );
    }

    public function delete_post( $post_id ) {
        $post    = $this->get_post( $post_id );
        $results = Database::get_instance()->delete_post( Database::$table_posts, $post_id );
        Entries::get_instance()->delete_entries( $post_id );
        Database::get_instance()->delete_posts( Database::$table_stats, [ 'nx_id' => $post_id ] );

        do_action( 'nx_delete_post', $post_id, $post );
        return $results;
    }

    public function get_theme_preview_image( $post ) {
        $url = '';

        if ( ! empty( $post['source'] ) && ! empty( $post['themes'] ) ) {
            $source = $post['source'];
            $theme  = $post['themes'];
            if ( $ex = ExtensionFactory::get_instance()->get( $source ) ) {
                $themes = $ex->get_themes();
                if ( ! empty( $themes[ $theme ]['source'] ) ) {
                    $url = $themes[ $theme ]['source'];
                }
            }
            $post['preview'] = apply_filters( "nx_theme_preview_{$post['source']}", $url, $post );
        }
        // Disable animation options if NX Pro not exists
        if ( !NotificationX::is_pro() ) {
            $post['animation_notification_show']     = 'default';
            $post['animation_notification_hide']     = 'default';
        }

        return $post;
    }

    public function get_edit_link( $nx_id ) {
        return admin_url( "admin.php?page=nx-edit&id=$nx_id" );
    }

    public function responsive_size_backward_comp($post){
        if(empty($post['size'])){
            $post['size'] = [
                "desktop" => 500,
                "tablet"  => 500,
                "mobile"  => 500,
            ];
        }
        else if(!is_array($post['size'])){
            $post['size'] = [
                "desktop" => $post['size'],
                "tablet"  => $post['size'],
                "mobile"  => $post['size'],
            ];
        }
        return $post;
    }

    public function set_context($context){
        $this->context = $context;
    }

    public function get_select_async_fields(){
        return [
            'product_list',
            'exclude_products',
            'form_list',
            'ld_course_list',
            'give_form_list',
            // 'google_reviews_place_data' // no need to add this field, because it can handle value in [label, value] format.
        ];
    }

    public function async_select_get_label($post, $context = null){
        if('edit' === $context){
            foreach ($this->get_select_async_fields() as $field_name) {
                if(isset($post["__$field_name"])){
                    $post[ $field_name ] = $post["__$field_name"];
                }
            }
        }

        return $post;
    }

    /**
     * This function removes the label from the select async fields in the post data.
     *
     * @param array $post The post data array.
     * @param array $data The data array.
     * @param int $nx_id The notification ID.
     * @return array The modified post data array.
     */
    public function async_select_remove_label($post, $data, $nx_id)
    {
        // Get the notification instance
        $notification = NotificationX::get_instance();

        // Loop through the select async fields
        foreach ($this->get_select_async_fields() as $field_name) {
            // Get the field details and the field value from the post data
            $field_details = $notification->get_field($field_name);
            // Get the field value from the post data
            $field_value = isset($post['data'][$field_name]) ? $post['data'][$field_name] : [];
            $post['data']["__$field_name"] = $field_value;

            // Check if the field value is an array
            if (!empty($field_value) && is_array($field_value)) {
                // Put a copy of it in the same array with a _ prefix in the key name

                // Check if the field is multiple
                if (isset($field_details['multiple']) && $field_details['multiple']) {
                    // Use array_map to apply a function to each element of the field value
                    $post['data'][$field_name] = array_map(function ($option) {
                        // Use ternary operator to return the value key or an empty string
                        return isset($option['value']) ? $option['value'] : '';
                    }, $field_value);
                } else {
                    // Use ternary operator to return the value key or an empty string
                    $post['data'][$field_name] = isset($field_value['value']) ? $field_value['value'] : '';
                }
            }
        }

        // Return the modified post data
        return $post;
    }

    /**
     * Adjusts the notification size to ensure a minimum size of 300px for mobile, desktop, and tablet devices.
     *
     * (mobile, desktop, and tablet) is less than 300px. If so, it updates the size
     * to 300px to maintain a consistent and usable notification display.
     *
     * @param array $post The existing post data, which will be modified with the updated sizes.
     * @param array $data The data array.
     * @param int $nx_id The unique ID of the notification being processed.
     *
     * @return array The modified post data with updated notification sizes.
    */
    public function maximize_notification_size($post, $data, $nx_id)
    {
        if (!empty($data['size']) && is_array($data['size'])) {
            foreach (['mobile', 'desktop', 'tablet'] as $device) {
                if (!empty($data['size'][$device]) && $data['size'][$device] < 300) {
                    $post['data']['size'][$device] = 300;
                }
            }
        }
        return $post;
    }

    /**
     * Adjusts the notification size to ensure a minimum size of 300px for mobile, desktop, and tablet devices in dashboard frontend.
     * @param array $data The data array.
     *
     * @return array The modified data with updated notification sizes.
    */
    public function get_maximize_notification_size($data) {
        if (!empty($data['size']) && is_array($data['size'])) {
            foreach (['mobile', 'desktop', 'tablet'] as $device) {
                if (!empty($data['size'][$device]) && $data['size'][$device] < 300) {
                    $data['size'][$device] = 300;
                }
            }
        }
        return $data;
    }

}
