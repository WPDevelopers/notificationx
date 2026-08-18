<?php
namespace NotificationX\Admin;

use NotificationX\Core\Database;
use NotificationX\Core\PostType;
use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;

/**
 * @method static ImportExport get_instance($args = null)
 */
class ImportExport{
    use GetInstance;

    public function __construct(){
        add_filter('nx_settings_tab_miscellaneous', [$this, 'settings_tab_help']);
        add_filter('upload_mimes', [$this, 'cc_mime_types']);
        add_filter('nx_settings', [$this, 'save_settings']);
    }

    public function save_settings($settings) {
        $remove_before_save = [
            'export-notification',
            'export-analytics',
            'export-status',
            'export-settings',
            'run_export',
            'import',
            'run_import',
        ];
        foreach ($remove_before_save as $key) {
            if(isset($settings[$key])){
                unset($settings[$key]);
            }
        }
        return $settings;
    }

    public function cc_mime_types($mimes) {
        $mimes['json'] = 'text/plain';
        return $mimes;
    }

    public function settings_tab_help($tabs) {

        $tabs['fields']['import-section'] = array(
            'name'     => 'import-section',
            'type'     => "section",
            'label'    => __('Import/Export', 'notificationx'),
            'priority' => 30,
            'fields'   => array(
                'export-notification' => [
                    'name'     => "export-notification",
                    'type'     => 'checkbox',
                    'label'    => __('Export Notifications', 'notificationx'),
                    'default'  => 0,
                    'priority' => 10,
                ],
                'export-analytics' => [
                    'name'     => "export-analytics",
                    'type'     => 'checkbox',
                    'label'    => __('Analytics', 'notificationx'),
                    'default'  => 0,
                    'priority' => 15,
                    'rules'    => Rules::is( 'export-notification', true ),
                    // 'description' => __('Click, if you want to disable powered by text from notification', 'notificationx'),
                ],
                'export-status' => array(
                    'name'     => 'export-status',
                    'type'     => 'select',
                    'label'    => __('Status', 'notificationx'),
                    'priority' => 20,
                    'rules'    => Rules::is( 'export-notification', true ),
                    'default'  => ['all'],
                    'options'  => GlobalFields::get_instance()->normalize_fields([
                        'all' => 'ALL',
                        'enabled' => 'Enabled',
                        'disabled' => 'Disabled',
                    ]),
                ),
                'export-settings' => [
                    'name'     => "export-settings",
                    'type'     => 'checkbox',
                    'label'    => __('Export Settings', 'notificationx'),
                    'default'  => 0,
                    'priority' => 30,
                ],
                'run_export' => array(
                    'name'     => 'run_export',
                    // 'label'    => __('Import', 'notificationx'),
                    'text'    => [
                        'normal'  => __('Export', 'notificationx'),
                        'saved'   => __('Export', 'notificationx'),
                        'loading' => __('Exporting...', 'notificationx'),
                    ],
                    'type'     => 'button',
                    'priority' => 40,
                    // 'rules'    => Rules::is( 'import', null, true ),
                    'rules'    => Rules::logicalRule([
                        Rules::is( 'export-notification', true ),
                        Rules::is( 'export-settings', true ),
                    ], 'or'),
                    'ajax'     => [
                        'on'   => 'click',
                        'api'  => '/notificationx/v1/export',
                        'data' => [
                            'export-notification' => '@export-notification',
                            'export-settings'     => '@export-settings',
                            'export-analytics'    => '@export-analytics',
                            'export-status'       => '@export-status',
                        ],
                        'swal' => [
                            'text'      => __('Export completed successfully.', 'notificationx'),
                            'icon'      => 'success',
                            'autoClose' => 2000
                        ],
                    ],
                ),

                'import' => array(
                    'name'         => 'import',
                    'type'         => 'jsonuploader',
                    'label'        => __('Import (*.json)', 'notificationx'),
                    'reset'        => __('Change', 'notificationx'),
                    'priority'     => 60,
                    'notImage'     => true,
                ),
                'run_import' => array(
                    'name'     => 'run_import',
                    // 'label'    => __('Import', 'notificationx'),
                    'text'    => [
                        'normal'  => __('Import', 'notificationx'),
                        'saved'   => __('Import', 'notificationx'),
                        'loading' => __('Importing...', 'notificationx'),
                    ],
                    'type'     => 'button',
                    'priority' => 70,
                    'rules'    => Rules::is( 'import', null, true ),
                    'ajax'     => [
                        'on'   => 'click',
                        'api'  => '/notificationx/v1/import',
                        'data' => [
                            'import'   => '@import',
                        ],
                        'swal' => [
                            'text'      => __('Import completed successfully.', 'notificationx'),
                            'icon'      => 'success',
                            'autoClose' => 2000
                        ],
                    ],
                ),
            ),
        );

        return $tabs;
    }

    public function import($request){
        // Importing/exporting many notifications can exceed the default limit.
        // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
        @set_time_limit(0);
        $params = $request->get_params();
        $status = 'error';
        if(!empty($params['import'])){
            try {
                $data = json_decode($params['import'], true);

                if(!empty($data['settings'])){
                    // The route requires `edit_notificationx` -- the capability
                    // for creating notifications. Replacing the settings blob is
                    // a different boundary, guarded everywhere else (including
                    // the settings screen this UI lives on) by its own capability.
                    if ( ! current_user_can( 'edit_notificationx_settings' ) ) {
                        return new \WP_Error(
                            'nx_cannot_import_settings',
                            __( 'You are not allowed to import NotificationX settings.', 'notificationx' ),
                            [ 'status' => 403 ]
                        );
                    }
                    // Route through save_settings() instead of writing the option
                    // directly, so an import cannot do what an ordinary save is
                    // not allowed to do -- it is save_settings() that applies the
                    // `nx_settings` filters and preserve_protected_settings().
                    Settings::get_instance()->save_settings(
                        $this->restore_redacted_credentials( $data['settings'] )
                    );
                    $status = 'success';
                }

                if(!empty($data['notifications'])){
                    $analytics = [];
                    if(!empty($data['analytics'])){
                        $analytics = $this->group_stats_by_nx_id($data['analytics']);
                    }
                    foreach ($data['notifications'] as $key => $post) {
                        $nx_id = $post['nx_id'];
                        unset($post['nx_id']);
                        unset($post['id']);

                        if(isset($post['source']) && $post['source'] == 'press_bar' && !empty($post['elementor_id'])){
                            $el_id = $this->import_elementor_template(
                                isset($data['elementor'][$post['elementor_id']]) ? $data['elementor'][$post['elementor_id']] : []
                            );
                            if ($el_id) {
                                $post['elementor_id'] = $el_id;
                            } else {
                                // Better no template than one pointing at whatever
                                // local post happens to share the exported ID.
                                unset($post['elementor_id']);
                            }
                        }


                        $notification = PostType::get_instance()->save_post($post); //, ['no_hooks' => true]
                        $nx_id_new    = $notification['nx_id'];

                        if(!empty($analytics[$nx_id])){
                            foreach ($analytics[$nx_id] as $key => $value) {
                                $value['nx_id'] = $nx_id_new;
                                $analytics[$nx_id][$key] = $value;
                            }
                            // Database::get_instance()->insert_posts(Database::$table_stats, array_values($analytics[$nx_id]));
                        }
                    }
                    if(!empty($analytics)){
                        $_analytics = [];
                        foreach ($analytics as $key => $value) {
                            $_analytics = array_merge($_analytics, $value);
                        }
                        Database::get_instance()->insert_posts(Database::$table_stats, array_values($_analytics));
                    }

                    $status = 'success';
                }

            } catch (\Throwable $th) {
                //throw $th;
                $status = 'error';
            }
        }

        return [
            'status'  => $status,
            'data'    => [
                'context' => [
                    'import' => null,
                ]
            ]
        ];
    }

    public function export($request){
        // Importing/exporting many notifications can exceed the default limit.
        // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
        @set_time_limit(0);
        $params = $request->get_params();
        $export = [];
        if(!empty($params['export-settings'])){
            if ( ! current_user_can( 'edit_notificationx_settings' ) ) {
                return new \WP_Error(
                    'nx_cannot_export_settings',
                    __( 'You are not allowed to export NotificationX settings.', 'notificationx' ),
                    [ 'status' => 403 ]
                );
            }
            $file_name = 'nx-settings-export.json';
            $export['settings'] = $this->redact_credentials( Settings::get_instance()->get('settings') );
        }
        if(!empty($params['export-notification'])){
            $where = [];
            $file_name = 'nx-notification-export.json';
            if(!empty($params['export-status']) && ($params['export-status'] == 'enabled' || $params['export-status'] == 'disabled')){
                $where = [
                    'enabled' => $params['export-status'] == 'enabled',
                ];
            }
            if(!empty($params['export-notification-ids']) && is_array($params['export-notification-ids'])){
                $where = [
                    'nx_id' => [
                        'IN',
                        $params['export-notification-ids'],
                    ],
                ];
            }
            $export['notifications'] = PostType::get_instance()->get_posts($where);
            if(!empty($params['export-analytics']) && !empty($export['notifications'])){
                $nx_ids = array_column($export['notifications'], 'nx_id');
                $export['analytics'] = Database::get_instance()->get_posts(Database::$table_stats, '*', [
                    'nx_id' => [ 'IN', $nx_ids ],
                ]);
            }

            if(!empty($export['notifications'])){
                foreach ($export['notifications'] as $key => $post) {
                    if($post['source'] == 'press_bar' && !empty($post['elementor_id'])){
                        $export['elementor'][$post['elementor_id']]['post'] = get_post($post['elementor_id']);
                        $meta = get_post_meta($post['elementor_id']);
                        foreach ($meta as $key => $value) {
                            $export['elementor'][$post['elementor_id']]['meta'][$key] = array_map('maybe_unserialize', $value);
                        }
                    }
                }
            }
        }
        if(!empty($params['export-settings']) && !empty($params['export-notification'])){
            $file_name = 'nx-export.json';
        }
        return [
            'success' => true,
            'data'    => [
                'filename'  => $file_name,
                'download'  => $export,
                'context' => [
                    'export-notification' => false,
                    'export-settings'     => false,
                    'export-analytics'    => false,
                    'export-status'       => 'all',
                ]
            ]
        ];
    }

    /**
     * Settings keys holding integration credentials rather than configuration.
     *
     * These live in the same `settings` blob as ordinary options, so anything
     * that hands the blob out wholesale hands these out too. They are kept out
     * of exports and restored from storage on import.
     *
     * This is a list and not a pattern because the two cannot be told apart by
     * name: `openai_max_tokens` and `enable_rest_api` read like credentials and
     * are not, while `nx_pa_settings` is a token payload and does not read like
     * one. A new credential setting must be added here; the filter is there so
     * Pro and third-party integrations can register their own.
     *
     * @return array
     */
    public function credential_setting_keys() {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
        return (array) apply_filters( 'nx_credential_settings', [
            'nx_pa_settings',          // Google Analytics OAuth token payload.
            'token_info',
            'ga_client_id',
            'ga_client_secret',
            'yt_client_id',
            'yt_client_secret',
            'activecampaign_api_key',
            'convertkit_api_key',
            'convertkit_api_secret',
            'envato_token',
            'gmap_token',
            'google_review_api_key',
            'google_youtube_api_key',
            'mailchimp_api_key',
            'openai_access_token',
            'ifttt_api_key',
            'zapier_api_key',
        ] );
    }

    /**
     * Strip credentials from a settings blob on its way into an export file.
     *
     * An export is a downloadable file that gets mailed around and dropped in
     * shared drives; API keys, client secrets and OAuth tokens have no reason to
     * travel in one. Importing the result on the same site keeps working because
     * restore_redacted_credentials() puts the stored values back.
     *
     * @param mixed $settings Settings blob.
     * @return mixed
     */
    protected function redact_credentials( $settings ) {
        if ( ! is_array( $settings ) ) {
            return $settings;
        }
        foreach ( $this->credential_setting_keys() as $key ) {
            unset( $settings[ $key ] );
        }
        return $settings;
    }

    /**
     * Put stored credentials back into an incoming settings blob.
     *
     * Import replaces the blob wholesale, so a redacted export would otherwise
     * wipe every integration credential on the site it is imported into -- the
     * keys are absent from the file, and absent means "clear this" to a
     * whole-blob write. A key the file *does* carry is left alone: that is
     * either an export from before redaction or a deliberate move between
     * sites, and whoever got this far is allowed to set them.
     *
     * @param mixed $settings Incoming settings blob.
     * @return mixed
     */
    protected function restore_redacted_credentials( $settings ) {
        if ( ! is_array( $settings ) ) {
            return $settings;
        }

        // `get()` returns false for a missing key and false is also a legitimate
        // stored value, so only a sentinel separates the two.
        $missing = new \stdClass();

        foreach ( $this->credential_setting_keys() as $key ) {
            if ( array_key_exists( $key, $settings ) ) {
                continue;
            }
            $stored = Settings::get_instance()->get( "settings.{$key}", $missing );
            if ( $missing !== $stored ) {
                $settings[ $key ] = $stored;
            }
        }

        return $settings;
    }

    /**
     * Create the Elementor template a press-bar notification points at.
     *
     * The import payload is entirely client-supplied, so the exported `post`
     * array must not reach wp_insert_post() as-is: it carries post_type,
     * post_status and post_author, which let anyone allowed to import a
     * notification create any post as any author -- authority the notification
     * capability does not grant. Only the fields a template actually needs are
     * read out of it, everything deciding *what kind of object this is* is fixed
     * here, and meta is limited to Elementor's own keys, where the old loop
     * added whatever the file listed.
     *
     * @param array $elementor_data `post` and `meta` as written by export().
     * @return int Post ID, or 0 when no template was created.
     */
    protected function import_elementor_template( $elementor_data ) {
        if ( empty( $elementor_data['post'] ) || ! is_array( $elementor_data['post'] ) ) {
            return 0;
        }

        // Publishing a template is a WordPress post capability, not a
        // NotificationX one. Ask the post type when Elementor is active so
        // custom capability mappings are honoured; a notification editor without
        // it still imports the notification, just without the bar template.
        $cap      = 'publish_posts';
        $type_obj = get_post_type_object( 'elementor_library' );
        if ( $type_obj && ! empty( $type_obj->cap->publish_posts ) ) {
            $cap = $type_obj->cap->publish_posts;
        }
        if ( ! current_user_can( $cap ) ) {
            return 0;
        }

        $source = $elementor_data['post'];
        $el_id  = wp_insert_post( [
            'post_title'   => isset( $source['post_title'] ) ? sanitize_text_field( $source['post_title'] ) : '',
            'post_content' => isset( $source['post_content'] ) ? $source['post_content'] : '',
            'post_excerpt' => isset( $source['post_excerpt'] ) ? $source['post_excerpt'] : '',
            'post_type'    => 'elementor_library',
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
        ], true );

        if ( is_wp_error( $el_id ) || empty( $el_id ) ) {
            return 0;
        }

        if ( empty( $elementor_data['meta'] ) || ! is_array( $elementor_data['meta'] ) ) {
            return $el_id;
        }

        foreach ( $elementor_data['meta'] as $key => $values ) {
            // Elementor's own keys only. `_elementor_css` stays out because it is
            // regenerated, and anything outside the prefix has no business riding
            // along with a template.
            if ( ! is_string( $key ) || 0 !== strpos( $key, '_elementor' ) || '_elementor_css' === $key ) {
                continue;
            }
            foreach ( (array) $values as $s_value ) {
                if ( '_elementor_data' === $key ) {
                    $s_value = wp_slash( wp_json_encode( json_decode( $s_value ) ) );
                }
                add_post_meta( $el_id, $key, $s_value );
            }
        }

        return $el_id;
    }

    public function group_stats_by_nx_id($stats){
        $new_stats = [];
        if(!empty($stats)){
            foreach ($stats as $key => $value) {
                unset($value['stat_id']);
                $new_stats[$value['nx_id']][] = $value;
            }
        }

        return $new_stats;
    }
}
