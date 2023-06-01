<?php

/**
 * Fluent_Form Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\FluentForm;

use NotificationX\Core\Helper;
use NotificationX\Core\Rules;
use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\GlobalFields;

/**
 * Fluent_Form Extension
 * @method static Fluent_Form get_instance($args = null)
 */
class Fluent_Form extends Extension {
    /**
     * Instance of Fluent_Form
     *
     * @var Fluent_Form
     */
    use GetInstance;

    public $priority        = 30;
    public $id              = 'fluentform';
    public $img             = '';
    public $doc_link        = 'https://notificationx.com/docs/contact-form-submission-alert/';
    public $types           = 'form';
    public $module          = 'modules_fluentform';
    public $module_priority = 10;
    public $class           = 'FluentForm\Framework\Foundation\Bootstrap';
    public $post_type       = 'fluentform';
    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        $this->title = __('Fluent Form', 'notificationx');
        $this->module_title = __('Fluent Form', 'notificationx');
        parent::__construct();
    }

    public function init() {
        parent::init();
        add_action('fluentform_submission_inserted', array($this, 'save_new_records'),10,3);
    }

    public function init_fields(){
        parent::init_fields();
        add_filter('nx_form_list', [$this, 'nx_form_list'], 9);
    }

    /**
     * This functions is hooked
     *
     * @return void
     */
    public function admin_actions() {
        parent::admin_actions();

        add_filter("nx_can_entry_{$this->id}", array($this, 'can_entry'), 10, 3);
    }

    /**
     * This functions is hooked
     *
     * @hooked nx_public_action
     * @return void
     */
    public function public_actions() {
        parent::public_actions();

    }

    public function source_error_message($messages) {
        if (!$this->class_exists()) {
            $url = admin_url('plugin-install.php?s=ninja+forms&tab=search&type=term');
            $messages[$this->id] = [
                'message' => sprintf( '%s <a href="%s" target="_blank">%s</a> %s',
                    __( 'You have to install', 'notificationx' ),
                    $url,
                    __( 'Fluent Form', 'notificationx' ),
                    __( 'plugin first.', 'notificationx' )
                ),
                'html' => true,
                'type' => 'error',
                'rules' => Rules::is('source', $this->id),
            ];
        }
        return $messages;
    }

    public function nx_form_list($forms) {
        $_forms = GlobalFields::get_instance()->normalize_fields($this->get_forms(), 'source', $this->id);
        return array_merge($forms, $_forms);
    }

    public function get_forms() {
        $forms = [];
        if (!class_exists('FluentForm\Framework\Foundation\Bootstrap')) {
            return [];
        }
        global $wpdb;

        $table_name = $wpdb->prefix . 'fluentform_forms'; 
        $limit      = 10;
        // Prepare the query with a WHERE condition
        $query = $wpdb->prepare(
            "SELECT id, title FROM {$table_name} WHERE status = %s LIMIT %d", 
            'published', $limit
        );
        // Execute the query and retrieve the results
        $form_result = $wpdb->get_results($query);

        if (!empty($form_result)) {
            foreach ($form_result as $form) {
                $key = $this->key($form->id);
                $forms[$key] = $form->title;
            }
        }
        return $forms;
    }

    public function restResponse($args) {
        $forms = [];
        if (!class_exists('FluentForm\Framework\Foundation\Bootstrap')) {
            return [];
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'fluentform_forms'; 
        if (!empty($args['inputValue'])) {
            $limit      = 10;
           // Prepare the query with a LIKE condition
            $query = $wpdb->prepare(
                "SELECT id, title FROM {$table_name} WHERE title LIKE %s AND status = %s LIMIT %d", 
                '%' . $wpdb->esc_like($args['inputValue']) . '%','published',$limit
            );
            // Execute the query and retrieve the results
            $form_result = $wpdb->get_results($query);
            if (!empty($form_result)) {
                foreach ($form_result as $form) {
                    $key = $this->key($form->id);
                    $forms[$key] = $form->title;
                }
            }
            $result = array_values(GlobalFields::get_instance()->normalize_fields($forms, 'source', $this->id));
            return $result;
        }
        if (isset($args['form_id'])) {

           // Prepare the query with a WHERE condition
            $query = $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d AND status = %s", 
                $args['form_id'], 'published'
            );
            // Execute the query and retrieve the results
            $fieldsString = $wpdb->get_results($query);
            if( !empty( $fieldsString ) ) {
                $fieldsString = json_decode($fieldsString[0]->form_fields);
                return $this->keys_generator($fieldsString);
            }
                        
        }

        wp_send_json_error([]);
    }

    public function keys_generator($fieldsString) {
        $formData = [];
        foreach ($fieldsString->fields as $key => $value) {
            if( is_object( $value->fields ) ) {
                foreach ($value->fields as $_key => $_value) {
                    if ( !empty( $_value->attributes->placeholder ) ) {
                        $formData[] = [
                            'label' => $_value->attributes->placeholder ?? '',
                            'value' => 'tag_'.$this->id.'_'.$_value->attributes->name ?? '',
                        ];
                    }
                    
                }
            }else{
                if( !empty( $value->attributes->placeholder ) ) {
                    $formData[] = [
                        'label'  => $value->attributes->placeholder ?? '',
                        'value'  => 'tag_'.$value->attributes->name ?? '',
                    ];
                }
                
            }
        }
        return $formData;
    }

    public function save_new_records($insertId,$formData,$form) {
        $data = [];
        foreach ($formData as $key => $field) {
            if( is_array($field) ){
                foreach ($field as $_key => $value) {
                    $data[$this->id.'_'.$_key] = $value;
                }
            }else{
                $data[$key] = $field;
            }
        }
        $data['title'] = $form->title ?? '';
        $data['timestamp'] = time();
        if (!empty($data)) {
            $key = $this->key($form->id);
            $this->save([
                'source'    => $this->id,
                'entry_key' => $key,
                'data'      => $data,
            ]);
            return true;
        }
        return false;
    }

    public function key($key = '') {
        $key = $this->id . '_' . $key;
        return $key;
    }

    /**
     * Limit entry by selected form in 'Select a Form';
     *
     * @param [type] $return
     * @param [type] $entry
     * @param [type] $settings
     * @return boolean
     */
    public function can_entry($return, $entry, $settings){
        if(!empty($settings['form_list']) && !empty($entry['entry_key'])){
            $selected_form = $settings['form_list'];
            $form_id = $entry['entry_key'];
            if($selected_form != $form_id){
                return false;
            }

        }
        return $return;
    }

    public function doc() {
        return sprintf(__('<p>Make sure that you have <a target="_blank" href="%1$s">Fluent Form installed & configured</a> to use its campaign & form subscriptions data. For further assistance, check out our step by step <a target="_blank" href="%2$s">documentation</a>.</p>
		<p>🎦 <a target="_blank" href="%3$s">Watch video tutorial</a> to learn quickly</p>
		<p>👉 NotificationX <a target="_blank" href="%4$s">Integration with Fluent Form</a></p>
		<p><strong>Recommended Blog:</strong></p>
		<p>🔥 Hacks to Increase Your <a target="_blank" href="%5$s">WordPress Contact Forms Submission Rate</a> Using NotificationX</p>', 'notificationx'),
        'https://wordpress.org/plugins/ninja-forms/',
        'https://notificationx.com/docs/ninja-forms/',
        'https://www.youtube.com/watch?v=Ibv84iGcBHE',
        'https://notificationx.com/integrations/ninja-forms/',
        'https://notificationx.com/blog/wordpress-contact-forms/'
        );
    }
}
