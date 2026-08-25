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

    /**
     * Elementor meta keys that may cross the import/export boundary.
     *
     * Everything else is dropped. On import the incoming array used to be
     * looped verbatim into `add_post_meta()`, which let a caller write any meta
     * key it liked onto a post it had just created; on export every meta row of
     * the linked post was returned, which leaked whatever other plugins store
     * there.
     */
    const ELEMENTOR_META_ALLOWLIST = [
        '_elementor_data',
        '_elementor_edit_mode',
        '_elementor_template_type',
        '_elementor_page_settings',
        '_elementor_version',
        '_wp_page_template',
    ];

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
                    /*
                     * This route resolves `edit_notificationx`, but replacing the
                     * settings blob is settings authority. Writing through
                     * `set()` also skipped the capability check, the `nx_settings`
                     * filter and `preserve_protected_settings()` that the real
                     * save path applies -- so import was a way around every guard
                     * on `/settings`. Go through `save_settings()` instead.
                     */
                    if ( ! current_user_can( 'edit_notificationx_settings' ) ) {
                        return new \WP_Error(
                            'nx_forbidden_settings_import',
                            __( 'You are not allowed to import NotificationX settings.', 'notificationx' ),
                            [ 'status' => 403 ]
                        );
                    }
                    Settings::get_instance()->save_settings( $data['settings'] );
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
                            $el_id = $this->import_elementor_document(
                                isset($data['elementor'][$post['elementor_id']]) ? $data['elementor'][$post['elementor_id']] : []
                            );
                            if($el_id){
                                $post['elementor_id'] = $el_id;
                            }
                            else{
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
                    'nx_forbidden_settings_export',
                    __( 'You are not allowed to export NotificationX settings.', 'notificationx' ),
                    [ 'status' => 403 ]
                );
            }
            $file_name = 'nx-settings-export.json';
            /*
             * Credentials never travel in an export file. The download lands in
             * a Downloads folder and gets attached to support tickets; a live
             * OAuth refresh token or API key in there outlives any access
             * control the site applies. Import restores whatever the target site
             * already had, so a round trip does not blank integrations.
             */
            $export['settings'] = Settings::redact_secret_settings( Settings::get_instance()->get('settings') );
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
                foreach ($export['notifications'] as $post) {
                    if(isset($post['source']) && $post['source'] == 'press_bar' && !empty($post['elementor_id'])){
                        /*
                         * `elementor_id` is stored inside the notification's own
                         * data blob, which is whatever the client submitted, and
                         * `get_posts()` merges that blob up to the top level. So
                         * this ID is attacker-controlled: without the type check
                         * an `edit_notificationx` user could point it at any post
                         * and read it back, with every meta row attached.
                         */
                        $linked = get_post( $post['elementor_id'] );
                        if ( ! $linked || 'nx_bar' !== $linked->post_type ) {
                            continue;
                        }

                        $export['elementor'][$post['elementor_id']]['post'] = $linked;
                        $meta = get_post_meta($post['elementor_id']);
                        foreach ($meta as $meta_key => $value) {
                            if ( ! in_array( $meta_key, self::ELEMENTOR_META_ALLOWLIST, true ) ) {
                                continue;
                            }
                            $export['elementor'][$post['elementor_id']]['meta'][$meta_key] = array_map('maybe_unserialize', $value);
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
     * Create the Elementor document that a `press_bar` notification links to.
     *
     * The previous implementation handed the client-supplied `post` array
     * straight to `wp_insert_post()` with only `ID` removed, so `post_type`,
     * `post_status` and `post_author` were all attacker-chosen -- an import file
     * could publish a page, authored by anyone, from a Contributor account. The
     * document is now built here and only its title is taken from the payload.
     *
     * @param array $document Untrusted `['post' => [...], 'meta' => [...]]`.
     * @return int New post ID, or 0 when nothing was created.
     */
    protected function import_elementor_document( $document ) {
        if ( empty( $document['post'] ) || ! is_array( $document['post'] ) ) {
            return 0;
        }

        $incoming = $document['post'];
        $title    = isset( $incoming['post_title'] ) ? sanitize_text_field( $incoming['post_title'] ) : '';
        if ( '' === $title ) {
            $title = __( 'NotificationX Bar', 'notificationx' );
        }

        $el_id = wp_insert_post( [
            'post_title'   => wp_slash( $title ),
            'post_content' => isset( $incoming['post_content'] ) ? wp_slash( (string) $incoming['post_content'] ) : '',
            'post_type'    => 'nx_bar',
            'post_status'  => current_user_can( 'publish_posts' ) ? 'publish' : 'pending',
            'post_author'  => get_current_user_id(),
        ], true );

        if ( is_wp_error( $el_id ) || ! $el_id ) {
            return 0;
        }

        /*
         * `_elementor_data` is a widget tree that Elementor renders on the front
         * end, and `add_post_meta()` applies no sanitising of its own. Elementor
         * gates raw markup on `unfiltered_html` in its own editor; mirror that
         * here so an import cannot become a route to stored XSS.
         */
        $allow_raw_html = current_user_can( 'unfiltered_html' );
        $meta           = ( isset( $document['meta'] ) && is_array( $document['meta'] ) ) ? $document['meta'] : [];

        foreach ( $meta as $meta_key => $values ) {
            if ( ! in_array( $meta_key, self::ELEMENTOR_META_ALLOWLIST, true ) ) {
                continue;
            }

            foreach ( (array) $values as $value ) {
                if ( '_elementor_data' === $meta_key ) {
                    $decoded = json_decode( is_string( $value ) ? $value : wp_json_encode( $value ), true );
                    if ( null === $decoded ) {
                        continue;
                    }
                    if ( ! $allow_raw_html ) {
                        $decoded = self::kses_deep( $decoded );
                    }
                    $value = wp_slash( wp_json_encode( $decoded ) );
                }
                elseif ( is_string( $value ) && ! $allow_raw_html ) {
                    $value = wp_kses_post( $value );
                }

                /*
                 * `update_` rather than `add_`: every allowlisted key is
                 * single-valued, and `wp_insert_post()` has already written its
                 * own `_wp_page_template` row. Appending left the imported value
                 * behind WordPress's, so `get_post_meta( ..., true )` returned
                 * the default and the imported template never took effect.
                 */
                update_post_meta( $el_id, $meta_key, $value );
            }
        }

        return $el_id;
    }

    /**
     * Run `wp_kses_post()` over every string in a nested structure.
     *
     * @param mixed $value
     * @return mixed
     */
    protected static function kses_deep( $value ) {
        if ( is_array( $value ) ) {
            return array_map( [ __CLASS__, 'kses_deep' ], $value );
        }
        if ( is_string( $value ) ) {
            return wp_kses_post( $value );
        }
        return $value;
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
