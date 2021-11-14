<?php
namespace NotificationX\Admin;

use NotificationX\Core\Rules;
use NotificationX\GetInstance;

class ImportExport{
    use GetInstance;

    public function __construct(){
        add_filter('nx_settings_tab_miscellaneous', [$this, 'settings_tab_help']);
        add_filter('upload_mimes', [$this, 'cc_mime_types']);
    }

    public function cc_mime_types($mimes) {
        $mimes['json'] = 'text/plain';
        return $mimes;
    }

    public function settings_tab_help($tabs) {

        $tabs['fields']['import-export'] = array(
            'name'     => 'import-export',
            'type'     => "section",
            'label'    => __('Import/Export', 'notificationx'),
            'priority' => 6,
            'fields'   => array(
                'export' => array(
                    'name'     => 'export',
                    'type'     => 'message',
                    'label'    => __('Export', 'notificationx'),
                    'priority' => 5,
                    'html'     => true,
                    'classes'  => 'wprf-control-wrapper wprf-inline-label',
                    'message'  => "<div class='wprf-control-label'><label for='import'>Export</label></div><div class='wprf-control-field'><a href='#'>Download export file.</a></div>",
                ),
                'import' => array(
                    'name'         => 'import',
                    'type'         => 'media',
                    'label'        => __('Import', 'notificationx'),
                    'reset'        => __('Change', 'notificationx'),
                    'priority'     => 20,
                    'notImage'     => true,
                ),
                'run_import' => array(
                    'name'     => 'run_import',
                    // 'label'    => __('Import', 'notificationx'),
                    'text'     => __('Import', 'notificationx'),
                    'type'     => 'button',
                    'priority' => 25,
                    'rules'    => Rules::is( 'import', null, true ),
                    'ajax'     => [
                        'on'   => 'click',
                        'api'  => '/notificationx/v1/import',
                        'data' => [
                            'import'   => '@import',
                        ],
                        'swal' => [
                            'text'      => __('Successfully Sent a Test Report in Your Email.', 'notificationx'),
                            'icon'      => 'success',
                            'autoClose' => 2000
                        ],
                    ],
                ),
            ),
        );

        return $tabs;
    }
}
