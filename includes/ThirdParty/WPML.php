<?php

namespace NotificationX\ThirdParty;

use NotificationX\Admin\Settings;
use NotificationX\Core\Helper;
use NotificationX\Core\PostType;
use NotificationX\Core\REST;
use NotificationX\GetInstance;
use WP_Error;
use WP_REST_Server;

class WPML {
    /**
     * Instance of WPML
     *
     * @var WPML
     */
    use GetInstance;

    protected $inclued_entry_key = [
        'name',
        'first_name',
        'last_name',
        // 'link', // link is automatically translated.
        'title',
        'city',
        'state',
        'country',
        'city_country',
    ];

    private $template = [];

    /**
     * Constructor.
     *
     */
    public function __construct() {
        add_action('wpml_st_loaded', [$this, 'st_loaded'], 10);
        // localize moment even without wpml;
        add_action('notificationx_scripts', [$this, 'localize_moment'], 10);

        $this->template = [
            'custom_first_param'  => __("Custom First Parameter", 'notificationx'),
            'second_param'        => __("Second Param", 'notificationx'),
            'custom_third_param'  => __("Custom Third Param", 'notificationx'),
            'custom_fourth_param' => __("Custom Fourth Parameter", 'notificationx'),
            'custom_fifth_param'  => __("Custom Fifth Parameter", 'notificationx'),
            'custom_sixth_param'  => __("Custom Sixth Parameter", 'notificationx'),
            'map_fourth_param'    => __("Map Fourth Parameter", 'notificationx'),
            'ga_fourth_param'     => __("Google Analytics Fourth Parameter", 'notificationx'),
            'ga_fifth_param'      => __("Google Analytics Fifth Parameter", 'notificationx'),
            'review_fourth_param' => __("Review Fourth Parameter", 'notificationx'),
        ];

    }

    /**
     * This method is reponsible for Admin Menu of
     * NotificationX
     *
     * @return void
     */
    public function st_loaded() {

        add_action('init', [$this, 'init'], 10);

        add_action('nx_saved_post', [$this, 'register_package'], 10, 3);
        add_action('nx_delete_post', [$this, 'delete_translation'], 10, 2);
        add_filter('nx_get_post', [$this, 'translate_values'], 10);

        add_action('rest_api_init', [$this, 'register_routes']);
        add_filter('nx_rest_data', [$this, 'rest_data']);
        add_filter('nx_builder_configs', [$this, 'builder_configs']);

    }

    public function init(){
        Settings::get_instance()->_load();

    }

    public function localize_moment(){
        $locale      = strtolower(str_replace('_', '-', get_locale()));
        $locale_path = NOTIFICATIONX_ASSETS_PATH . "public/locale/$locale.js";
        if(file_exists($locale_path)){
            $locale_url  = NOTIFICATIONX_ASSETS . "public/locale/$locale.js";
            wp_enqueue_script( 'nx-moment-locale', $locale_url, ['moment']);
        }
    }

    public function wpml_translate($entry, $settings){
        $included = apply_filters('wpml_inclued_entry_key', $this->inclued_entry_key, $entry, $settings);
        if(is_array($entry)){
            foreach ($entry as $key => $value) {
                if(in_array($key, $included)){
                    $context = array(
                        'domain'  => "notificationx-entries-{$entry['source']}", //{$entry['source']}
                        'context' => $key,
                    );
                    do_action( 'wpml_register_single_string', $context, $value, $value );
                    $entry[$key] = apply_filters( 'wpml_translate_single_string', $value, $context, $value);
                }
            }
        }
        return $entry;
    }

    public function generate_package($post, $nx_id){
        return array(
            'kind'      => 'NotificationX',
            'name'      => "$nx_id",
            'title'     => "{$post['title']}", // ($nx_id)
            'edit_link' => PostType::get_instance()->get_edit_link($nx_id),
            'view_link' => PostType::get_instance()->get_edit_link($nx_id),
        );
    }

    public function register_package($post, $data, $nx_id){
        $data = array_merge($post, $data);
        if(empty($data['is_translated'])){
            return;
        }

        $package = $this->generate_package($data, $nx_id);
        do_action('wpml_register_string', $data['title'], 'title', $package, 'Title', 'LINE');

        do_action('wpml_register_string', $data['advanced_template'], 'advanced_template', $package, 'Advance Template', 'VISUAL');
        // @todo maybe keep only one.
        foreach ($this->template as $key => $param) {
            if(!empty($data['notification-template'][$key])){
                do_action('wpml_register_string', $data['notification-template'][$key], $key, $package, $param, 'LINE');
            }
        }

    }

    public function translate_values($post){
        $package = $this->generate_package($post, $post['nx_id']);
        $post['title'] = apply_filters( 'wpml_translate_string', $post['title'], 'title', $package );
        $post['advanced_template'] = apply_filters( 'wpml_translate_string', $post['advanced_template'], 'advanced_template', $package );

        // @todo maybe keep only one.
        foreach ($this->template as $key => $param) {
            if(!empty($post['notification-template'][$key])){
                $post['notification-template'][$key] = apply_filters( 'wpml_translate_string', $post['notification-template'][$key], $key, $package );
            }
        }
        return $post;
    }

    public function delete_translation($nx_id, $post){
        $package = $this->generate_package($post, $nx_id);
        do_action( 'wpml_delete_package', $package['name'], $package['kind'] );
    }


    public function translate($request){
        $params = $request->get_params();
        if(!empty($params['id'])){
            $nx_id = $params['id'];
            $post = PostType::get_instance()->get_post($nx_id);
            if($post){
                $post['is_translated'] = true;
                PostType::get_instance()->update_post([
                    'data' => $post,
                ], $nx_id);

                $this->register_package($post, [], $nx_id);
                return [
                    'redirect' => admin_url("admin.php?page=wpml-string-translation/menu/string-translation.php&context=notificationx-$nx_id"),
                ];
            }
        }
		return new WP_Error();
    }
    public function can_translate( $request ) {
        return current_user_can('wpml_manage_string_translation');
    }

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes() {
        $namespace = REST::_namespace();
        register_rest_route( $namespace, '/translate/(?P<id>[\d]+)', array(
            'methods'   => WP_REST_Server::READABLE,
            'callback'  => array( $this, 'translate' ),
            'permission_callback' => array($this, 'can_translate'),
        ));
    }

    /**
     * Frontend append lang param.
     *
     * @param [type] $rest
     * @return void
     */
    public function rest_data($rest){
        $my_default_lang = apply_filters('wpml_default_language', NULL );
        $my_current_lang = apply_filters( 'wpml_current_language', NULL );
        if($my_default_lang != $my_current_lang){
            $rest['lang'] = $my_current_lang;
        }
        return $rest;
    }

    /**
     * Backend check if translation is enabled.
     *
     * @param [type] $rest
     * @return void
     */
    public function builder_configs($tabs){
        $tabs['can_translate'] = true;
        return $tabs;
    }
}