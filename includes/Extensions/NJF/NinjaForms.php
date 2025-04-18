<?php

/**
 * CF7 Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\NJF;

use NotificationX\Core\Helper;
use NotificationX\Core\Rules;
use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\GlobalFields;

/**
 * NinjaForms Extension
 * @method static NinjaForms get_instance($args = null)
 */
class NinjaForms extends Extension {
    /**
     * Instance of NinjaForms
     *
     * @var NinjaForms
     */
    use GetInstance;

    public $priority        = 15;
    public $id              = 'njf';
    public $img             = '';
    public $doc_link        = 'https://notificationx.com/docs/contact-form-submission-alert/';
    public $types           = 'form';
    public $module          = 'modules_njf';
    public $module_priority = 10;
    public $class           = 'Ninja_Forms';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
    }

    public function init_extension()
    {
        $this->title = __('Ninja Forms', 'notificationx');
        $this->module_title = __('Ninja Forms', 'notificationx');
    }

    public function init() {
        parent::init();

        add_action('ninja_forms_after_submission', array($this, 'save_new_records'));
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
                    __( 'Ninja Forms', 'notificationx' ),
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
        if (!class_exists('Ninja_Forms')) {
            return [];
        }
        global $wpdb;
        $form_result = $wpdb->get_results('SELECT id, title FROM `' . $wpdb->prefix . 'nf3_forms` ORDER BY title');
        if (!empty($form_result)) {
            foreach ($form_result as $form) {
                $key = $this->key($form->id);
                $forms[$key] = $form->title;
            }
        }

        return $forms;
    }

    public function saved_post($post, $data, $nx_id) {
        $this->delete_notification(null, $nx_id);
        $this->get_notification_ready($data);
    }

    /**
     * This function responsible for making ready the notifications for the first time
     * we have made a notification.
     *
     * @param string $type
     * @param array $data
     * @return void
     */
    public function get_notification_ready($data = array()) {
        $form_list = !empty($data['__form_list']['value']) ? $data['__form_list']['value'] : (!empty($data['form_list']['value']) ? $data['form_list']['value'] : null);
        if( !empty($form_list) ) {
            $form_list = explode('_',$form_list);
            $submissions = $this->get_submissions($form_list[1], $data);
            if( count( $submissions ) > 0 ) {
                $entries = [];
                foreach ( $submissions as $submission ) {
                    if( !empty( $submission ) ) {
                        if (!empty($submission)) {
                            $key = $this->key($form_list[1]);
                            $entries[] = [
                                'nx_id'      => $data['nx_id'],
                                'source'    => $this->id,
                                'entry_key' => $key,
                                'data'      => $submission,
                            ];
                        }
                    }
                }
                $this->update_notifications($entries);
            }
        }
    }

    public function get_submissions( $form_id, $data ) {
        $subs               = Ninja_Forms()->form( $form_id )->get_subs( array(), FALSE );
        $fields             = Ninja_Forms()->form( $form_id )->get_fields();
        $hidden_field_types = apply_filters( 'nf_sub_hidden_field_types', array() );
        $display_from = !empty( $data['display_from'] ) ? intval( $data['display_from'] ) : 30;        
        $cutoff_timestamp = strtotime("-{$display_from} days");

        foreach( $subs as $sub ){
            $timestamp = strtotime( $sub->get_sub_date('Y-m-d H:i') );
            // Skip submissions older than $display_from days
            if ($timestamp < $cutoff_timestamp) {
                continue;
            }
            $value[ 'title' ] = $sub->get_form_title();
            $value[ 'timestamp' ] = $timestamp;

            // boolean - does this submission use a repeater
            $hasRepeater = false;
            // How many repeater submissions does this submission have
            $submissionCount = 0;
            // Ids of fields in the repeater
            $fieldsetFieldIds=[];

            foreach ($fields as $field_id => $field) {
                        // Bypass existing method if fieldset repeater
                if('repeater'===$field->get_setting('type')){
                    $hasRepeater = true;
                    
                    $fieldsetSubmission=    $sub->get_field_value( $field_id );
                    $fieldsetSettings = $field->get_settings();
                    $fieldsetLabels = Ninja_Forms()->fieldsetRepeater
                            ->getFieldsetLabels($field_id, $fieldsetSettings, true);
                                    
                    foreach($fieldsetLabels as $fieldsetFieldId =>$fieldsetFieldLabel){
                        
                        $fieldsetFieldIds[]=$fieldsetFieldId;

                        $field_labels[$fieldsetFieldId]= \WPN_Helper::maybe_escape_csv_column( $fieldsetFieldLabel );
                        
                        $fieldType = Ninja_Forms()->fieldsetRepeater->getFieldtype($fieldsetFieldId, $fieldsetSettings);
                        
                        $fieldsetFieldSubmissionCollection=Ninja_Forms()->fieldsetRepeater
                                ->extractSubmissionsByFieldsetField($fieldsetFieldId, $fieldsetSubmission);
                       
                       $submissionCount = count($fieldsetFieldSubmissionCollection);
                       
                            foreach ($fieldsetFieldSubmissionCollection as  &$fieldsetFieldSubmission) {
                                
                                if(is_array($fieldsetFieldSubmission['value'])){

                                    $fieldsetFieldSubmission['value']= implode(', ',$fieldsetFieldSubmission['value']);
                                }
                            }
                            

                        $value[$fieldsetFieldId]= array_column($fieldsetFieldSubmissionCollection,'value');
                    }
                                      
                }else{
                    if (!is_int($field_id)) continue;
                  if( in_array( $field->get_setting( 'type' ), $hidden_field_types ) ) continue;

                  if ( $field->get_setting( 'admin_label' ) ) {
                      $field_labels[ $field->get_id() ] = \WPN_Helper::maybe_escape_csv_column( $field->get_setting( 'admin_label' ) );
                  } else {
                      $field_labels[ $field->get_id() ] = \WPN_Helper::maybe_escape_csv_column( $field->get_setting( 'label' ) );
                  }

                  $field_value = maybe_unserialize( $sub->get_field_value( $field_id ) );

                  $field_value = apply_filters('nf_subs_export_pre_value', $field_value, $field_id);
                  $field_value = apply_filters('ninja_forms_subs_export_pre_value', $field_value, $field_id, $form_id);
                  $field_value = apply_filters( 'ninja_forms_subs_export_field_value_' . $field->get_setting( 'type' ), $field_value, $field );

                  if ( is_array($field_value ) ) {
                      $field_value = implode( ',', $field_value );
                  }

                  $value[ $field_labels[ $field->get_id() ] ] = $field_value;
                  
                }   
            }

            if(!$hasRepeater){
                $value_array[] = $value;
            }else{
                // The the submission has repeater fields, create an indexed array first
                $repeatingValueArray=[];
                $index = 0;

                do {
                    // iterate each column in the row 'value'
                    foreach($value as $fieldId=>$columnValue){
                        
                        // If the column in the row value is not a repeater
                        // fieldset field, simply copy it into a new row of the
                        // repeating value array
                        if(!in_array($fieldId,$fieldsetFieldIds)){
                            $repeatingValueArray[$index][]=$columnValue;
                        }else{

                            // If the column in the row value is a repeater
                            // fieldset field, copy the next submission index value
                            
                            
                            $repeatingValueArray[$index][]=$columnValue[$index];
                        }
                    }
                    // at the end of the row value columns, increment the index
                    // until all the submission index values are added
                    $index++;
                } while ($index < $submissionCount);

                // After iterating the row value once for each submission index,
                // add the repeatingValueArray to the value array

                $value_array[]=$repeatingValueArray;
            }

        }
        return $value_array;
    }

    public function restResponse($args) {
        if (!class_exists('Ninja_Forms')) {
            return [];
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'nf3_forms'; 
        if (!empty($args['inputValue'])) {
            $limit      = 10;
           // Prepare the query with a LIKE condition
            $query = $wpdb->prepare(
                "SELECT id, title FROM {$table_name} WHERE title LIKE %s LIMIT %d", 
                '%' . $wpdb->esc_like($args['inputValue']) . '%',$limit
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
            if( is_array( $args['form_id'] ) ) {
                $form_id = intval($args['form_id']['value']);
            }else{
                $form_id = intval($args['form_id']);
            }
            $queryresult = $wpdb->get_results('SELECT meta_value FROM `' . $wpdb->prefix . 'nf3_form_meta` WHERE parent_id = ' . $form_id . ' AND meta_key = "formContentData"');

            if(isset($queryresult[0]) && isset($queryresult[0]->meta_value)){
                $formdata = $queryresult[0]->meta_value;

                $keys = $this->keys_generator($formdata);

                $returned_keys = array();

                if (is_array($keys) && !empty($keys)) {
                    foreach ($keys as $key) {
                        $returned_keys[] = array(
                            'label' => ucwords(str_replace('_', ' ', str_replace('-', ' ', $key))),
                            'value' => "tag_$key",
                        );
                    }

                    return $returned_keys;
                }
            }
        }
        wp_send_json_error([]);
    }

    public function keys_generator($fieldsString) {
        $fields = array();
        $fieldsdata = unserialize($fieldsString);
        if (!empty($fieldsdata)) {
            foreach ($fieldsdata as $field) {
                if(!is_string($field)){
                    $field = !empty($field['cells'][0]['fields'][0]) ? $field['cells'][0]['fields'][0] : null;
                }
                if ($field && Helper::filter_contactform_key_names($field)) {
                    $fields[] = Helper::rename_contactform_key_names($field);
                }
            }
        }
        return $fields;
    }

    public function save_new_records($form_data) {
        foreach ($form_data['fields'] as $field) {
            $arr = Helper::rename_contactform_key_names($field['key']);
            $data[$arr] = $field['value'];
        }
        $data['title'] = $form_data['settings']['title'];
        $data['timestamp'] = time();

        if (!empty($data)) {
            $key = $this->key($form_data['form_id']);
            $this->save([
                'source'    => $this->id,
                'entry_key' => $key,
                'data'      => $data,
            ]);
            return true;
        }
        return false;
    }

    public function key($key) {
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
        return sprintf(__('<p>Make sure that you have <a target="_blank" href="%1$s">Ninja Forms installed & configured</a> to use its campaign & form subscriptions data. For further assistance, check out our step by step <a target="_blank" href="%2$s">documentation</a>.</p>
		<p>🎦 <a target="_blank" href="%3$s">Watch video tutorial</a> to learn quickly</p>
		<p>👉 NotificationX <a target="_blank" href="%4$s">Integration with Ninja Forms</a></p>
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
