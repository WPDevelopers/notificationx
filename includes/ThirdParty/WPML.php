<?php

namespace NotificationX\ThirdParty;

use NotificationX\Core\PostType;
use NotificationX\GetInstance;

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
        add_action('init', [$this, 'init'], 10);
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
    public function init() {
        add_action('nx_filtered_entry', [$this, 'wpml_translate'], 10, 2);
        // add_action('nx_insert_entry', [$this, 'wpml_register_string'], 10, 2);
        add_action('nx_saved_post', [$this, 'register_package'], 10, 3);
        add_filter('nx_get_post', [$this, 'translate_values'], 10);
        add_filter('nx_delete_post', [$this, 'delete_translation'], 10, 2);
    }

    public function wpml_translate($entry, $settings){
        $included = apply_filters('wpml_inclued_entry_key', $this->inclued_entry_key, $entry, $settings);
        if(is_array($entry)){
            foreach ($entry as $key => $value) {
                if(in_array($key, $included)){
                    $context = array(
                        'domain'  => "notificationx-entries", //{$entry['source']}
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
            'name'      => "notificationx-$nx_id",
            'title'     => "{$post['title']}", // ($nx_id)
            'edit_link' => PostType::get_instance()->get_edit_link($nx_id),
            'view_link' => PostType::get_instance()->get_edit_link($nx_id),
        );
    }

    public function register_package($post, $data, $nx_id){
        $package = $this->generate_package(array_merge($post, $data), $nx_id);
        do_action('wpml_register_string', $post['title'], 'title', $package, 'Title', 'LINE');

        do_action('wpml_register_string', $data['advanced_template'], 'advanced_template', $package, 'Advance Template', 'VISUAL');
        // @todo maybe keep only one.
        foreach ($this->template as $key => $param) {
            do_action('wpml_register_string', $data['notification-template'][$key], $key, $package, $param, 'LINE');
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

}