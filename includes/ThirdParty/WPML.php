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

    protected $excluded_translation_key = [
        'updated_at',
        'created_at',
        'entry_key',
        'entry_id',
        'timestamp',
        'source',
        'ip',
        'id',
        'nx_id',
        'email',
        'lon',
        'lat',
    ];

    protected $template = [
        'custom_first_param',
        'second_param',
        'custom_third_param',
        'custom_fourth_param',
        'custom_fifth_param',
        'custom_sixth_param',
        'map_fourth_param',
        'ga_fourth_param',
        'ga_fifth_param',
        'review_fourth_param',
    ];

    /**
     * Constructor.
     *
     */
    public function __construct() {
        add_action('init', [$this, 'init'], 10);
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
        $excluded = apply_filters("nx_wpml_exclude", $this->excluded_translation_key);
        if(is_array($entry)){
            $context = array(
                'domain'  => "notificationx-{$entry['source']}",
                'context' => $entry['entry_id'],
            );
            foreach ($entry as $key => $value) {
                if(!in_array($key, $excluded)){
                    $key = str_replace('_', ' ', $key);
                    $key = ucwords($key);// . ' - ' . rand();
                    do_action( 'wpml_register_single_string', $context, $key, $value );
                    $entry[$key] = apply_filters( 'wpml_translate_single_string', $value, $context, $key);
                }
            }
        }
        return $entry;
    }

    public function wpml_register_string($entry){
        $excluded = apply_filters("nx_wpml_exclude", $this->excluded_translation_key);
        if(is_array($entry)){
            $context = array(
                'domain'  => "notificationx-{$entry['source']}",
                'context' => rand(),
            );
            foreach ($entry['data'] as $key => $value) {
                if(!in_array($key, $excluded)){
                    $key = str_replace('_', ' ', $key);
                    $key = ucwords($key);// . ' - ' . rand();
                    do_action( 'wpml_register_single_string', $context, "$key", $value );
                }
            }
        }
        return $entry;
    }

    public function generate_package($post, $nx_id){
        return array(
            'kind'      => 'NotificationX',
            'name'      => $nx_id,
            'title'     => "{$post['title']} ($nx_id)",
            'edit_link' => PostType::get_instance()->get_edit_link($nx_id),
        );
    }

    public function register_package($post, $data, $nx_id){
        $package = $this->generate_package(array_merge($post, $data), $nx_id);
        do_action('wpml_register_string', $post['title'], 'title', $package, 'Title', 'LINE');
        do_action('wpml_register_string', $data['advanced_template'], 'advanced_template', $package, 'Advance Template', 'VISUAL');
        do_action('wpml_register_string', $data['notification-template']['second_param'], 'second_param', $package, 'Second Parameter', 'LINE');

    }

    public function translate_values($post){
        $package = $this->generate_package($post, $post['nx_id']);
        $post['title'] = apply_filters( 'wpml_translate_string', $post['title'], 'title', $package );
        $post['advanced_template'] = apply_filters( 'wpml_translate_string', $post['advanced_template'], 'advanced_template', $package );
        $post['notification-template']['second_param'] = apply_filters( 'wpml_translate_string', $post['notification-template']['second_param'], 'second_param', $package );

        return $post;
    }

    public function delete_translation($nx_id, $post){
        $package = $this->generate_package($post, $nx_id);
        do_action( 'wpml_delete_package', $package['name'], $package['kind'] );
    }

}