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
class FluentForm extends Extension {
    /**
     * Instance of Fluent_Form
     *
     * @var FluentForm
     */
    use GetInstance;

    public $priority        = 20;
    public $id              = 'fluentform';
    public $img             = '';
    public $doc_link        = 'https://notificationx.com/docs/contact-form-submission-alert/';
    public $types           = 'form';
    public $module          = 'modules_fluentform';
    public $module_priority = 10;
    public $constant        = 'FLUENTFORM_VERSION';
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

        add_filter("nx_filtered_data_{$this->id}", array($this, 'filter_by_form'), 11, 3);
    }

    public function source_error_message($messages) {
        if (!$this->class_exists()) {
            $url = admin_url('plugin-install.php?s=fluent-forms&tab=search&type=term');
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
        if (!$this->class_exists()) {
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
        if (!$this->class_exists()) {
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
            return $this->keys_generator($args['form_id']);
        }

        wp_send_json_error([]);
    }

    public function keys_generator($form_id) {
        $formData = [];
        $formApi = fluentFormApi('forms')->form($form_id);
        $formFields = $formApi->labels();
        foreach ($formFields as $key => $value) {
            $formData[] = [
                'label' => $value ?? '',
                'value' => 'tag_'.$key ?? '',
            ];
        }
        return $formData;
    }


    public function save_new_records($insertId,$formData,$form) {
        $submission = wpFluent()->table('fluentform_submissions')
        ->where('form_id', $form->id)
        ->where('id', $insertId)
        ->first();
        $data = [];
        if( !empty( $submission ) ) {
            $inputs = \FluentForm\App\Modules\Form\FormFieldsParser::getEntryInputs($form);
            $submission = \FluentForm\App\Modules\Form\FormDataParser::parseFormEntry($submission, $form, $inputs, false);
            foreach ($submission->user_inputs as $key => $field) {
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
     * This function responsible for making ready the notifications for the first time
     * we have made a notification.
     *
     * @param string $type
     * @param array $data
     * @return void
     */
    public function get_notification_ready($data = array()) {
        if( !empty( $data['__form_list']['value'] ) ) {
            $form_list = explode('_',$data['__form_list']['value']);
            if( !empty( $form_list[1] ) ) {
                $form = wpFluent()->table('fluentform_forms')->where('id', $form_list[1])->first();
                $valueFrom = date('Y-m-d',strtotime('-'.$data['display_from'].' days',time()));
                $valueTo = date('Y-m-d',strtotime('1 days',time()));
                $submissionArr = wpFluent()->table('fluentform_submissions')
                ->where('form_id', $form->id)
                ->whereBetween('created_at', $valueFrom, $valueTo )
                ->orderBy('id','DESC')
                ->limit($data['display_last'])
                ->get();
                $entries = [];
                foreach ($submissionArr as $sub) {
                    if( !empty( $sub ) ) {
                        $entry_data = [];
                        $inputs = \FluentForm\App\Modules\Form\FormFieldsParser::getEntryInputs($form);
                        $submission = \FluentForm\App\Modules\Form\FormDataParser::parseFormEntry($sub, $form, $inputs, false);
                        foreach ($submission->user_inputs as $key => $field) {
                            $entry_data[$key] = $field;
                        }
                        $entry_data['title'] = $form->title ?? '';
                        $entry_data['timestamp'] = $sub->created_at;
                        $_key = $this->key($form->id);
                        if (!empty($data)) {
                            $entries[] = [
                                'nx_id'      => $data['nx_id'],
                                'source'     => $this->id,
                                'entry_key'  => $_key,
                                'data'       => $entry_data,
                            ];
                        }
                    }
                }
                $this->update_notifications($entries);
            }
        }


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

    /**
     * Filter entries based on selected form
     *
     * @param [type] $data
     * @param [type] $settings
     * @return boolean
    */
    public function filter_by_form($data, $settings){
        if( empty( $settings['form_list'] )) {
            return $data;
        }

        $new_data = [];

        if( ! empty( $data ) ) {
            foreach( $data as $key => $entry ) {
                $selected_form = $settings['form_list'];
                $form_id = $entry['entry_key'];
                if($selected_form == $form_id){
                    $new_data[] = $entry;
                }
            }
        }
        return $new_data;
    }


    public function doc() {
        return sprintf(__('
        <p>To use the campaign & form subscription data, make sure that you have <a target="_blank" href="%1$s">Fluent Forms installed and configured</a> on your website. For detailed guidelines, follow this <a target="_blank" href="%2$s">documentation</a>.</p>

        <p>🎥 Learn quickly from the <a target="_blank" href="%3$s">video tutorial</a>.</p>

        <p>⚙️ NotificationX integration with Fluent Forms</p>

        <p>📖 Recommended Reading: </p>
        <p>🔥How To <a target="_blank" href="%4$s">Display Fluent Form Submission Alert</a> Using NotificationX?</p>
        ', 'notificationx'),
        'https://wordpress.org/plugins/fluentform/',
        'https://notificationx.com/docs/fluent-forms-submission-alert-notificationx',
        'https://youtu.be/cl0WEazGflU',
        'https://notificationx.com/blog/display-fluent-forms-submission-alert/'
        );
    }
}
