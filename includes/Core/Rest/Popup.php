<?php 

namespace NotificationX\Core\Rest;

use NotificationX\GetInstance;
use NotificationX\Core\PopupNotification;
use NotificationX\Extensions\Popup\PopupNotification as PopupPopupNotification;
use WP_REST_Server;

/**
 * @method static Popup get_instance($args = null)
 */
class Popup {
    /**
     * Instance of Popup
     *
     * @var Popup
     */
    use GetInstance;
    public $namespace;
    public $rest_base;
    public $id              = 'popup_notification';

    /**
     * Constructor.
     *
     * @since 4.7.0
     *
     * @param string $post_type Post type.
     */
    public function __construct() {
        $this->namespace = 'notificationx/v1';
        $this->rest_base = 'popup-submit';
        add_action('rest_api_init', [$this, 'register_routes']);
    }


    /**
     * Registers the routes for the objects of the controller.
     *
     * @since 3.1.12
     *
     * @see register_rest_route()
    */
    public function register_routes() {
         register_rest_route('notificationx/v1', '/popup-submit', [
            'methods' => 'POST',
            'callback' => [ $this , 'handle_popup_submission' ],
            'permission_callback' => '__return_true',
            'args' => [
                'nx_id' => [
                    'required' => true,
                    'type'     => 'string',
                ],
                'email' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ],
                'message' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
                'name' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
                'timestamp' => [
                    'type' => 'integer',
                ],
            ],
        ]);

        // Feedback entries endpoint
        register_rest_route('notificationx/v1', '/feedback-entries', [
            'methods' => 'GET',
            'callback' => [$this, 'get_feedback_entries'],
            'permission_callback' => function() {
                return current_user_can('read_notificationx');
            },
            'args' => [
                'page' => [
                    'default' => 1,
                    'type' => 'integer',
                    'minimum' => 1,
                ],
                'per_page' => [
                    'default' => 20,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 200,
                ],
                's' => [
                    'default' => '',
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'notification_id' => [
                    'default' => '',
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // Delete feedback entry endpoint
        register_rest_route('notificationx/v1', '/feedback-entries/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_feedback_entry'],
            'permission_callback' => function() {
                return current_user_can('edit_notificationx');
            },
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);

        // Bulk delete feedback entries endpoint
        register_rest_route('notificationx/v1', '/feedback-entries/bulk-delete', [
            'methods' => 'POST',
            'callback' => [$this, 'bulk_delete_feedback_entries'],
            'permission_callback' => function() {
                return current_user_can('edit_notificationx');
            },
            'args' => [
                'ids' => [
                    'required' => true,
                    'type' => 'array',
                    'items' => [
                        'type' => 'integer',
                    ],
                ],
            ],
        ]);

        // Export feedback entries endpoint
        register_rest_route('notificationx/v1', '/feedback-entries/export', [
            'methods' => 'POST',
            'callback' => [$this, 'export_feedback_entries'],
            'permission_callback' => function() {
                return current_user_can('read_notificationx');
            },
            'args' => [
                's' => [
                    'required' => false,
                    'type' => 'string',
                ],
                'notification_id' => [
                    'required' => false,
                    'type' => 'string',
                ],
            ],
        ]);
    }

    /**
     * Handle popup form submission
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_popup_submission($request) {
        $popup = PopupPopupNotification::get_instance();
        return $popup->handle_popup_submission($request);
    }


    /**
     * Get feedback entries
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_feedback_entries($request) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nx_entries';

        // Get pagination parameters
        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 20;
        $search = $request->get_param('s') ?: '';
        $notification_id = $request->get_param('notification_id') ?: '';
        $offset = ($page - 1) * $per_page;

        // Build WHERE clause
        $where_conditions = ["e.source = %s"];
        $where_values = [$this->id];

        // Add notification filter
        if (!empty($notification_id)) {
            $where_conditions[] = "e.nx_id = %d";
            $where_values[] = intval($notification_id);
        }

        // Add search functionality
        if (!empty($search)) {
            $where_conditions[] = "(e.data LIKE %s OR e.created_at LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where_conditions);

        // Get total count for pagination
        $total_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} e WHERE {$where_clause}",
            ...$where_values
        );
        $total_items = (int) $wpdb->get_var($total_query);

        // Get paginated entries with notification information
        $posts_table = $wpdb->prefix . 'nx_posts';
        $entries_query = $wpdb->prepare(
            "SELECT e.*, p.title as notification_name, p.nx_id as notification_id
             FROM {$table_name} e
             LEFT JOIN {$posts_table} p ON e.nx_id = p.nx_id
             WHERE {$where_clause}
             ORDER BY e.created_at DESC
             LIMIT %d OFFSET %d",
            ...array_merge($where_values, [$per_page, $offset])
        );
        $entries = $wpdb->get_results($entries_query, ARRAY_A);

        $formatted_entries = [];
        foreach ($entries as $entry) {
            $data = maybe_unserialize($entry['data']);
            $formatted_entries[] = [
                'id'                => $entry['entry_id'],
                'date'              => $entry['created_at'],
                'name'              => $data['name'] ?? '',
                'email'             => $data['email'] ?? '',
                'message'           => $data['message'] ?? '',
                'title'             => $data['title'] ?? '',
                'theme'             => $data['theme'] ?? '',
                'ip'                => $data['ip'] ?? '',
                'notification_name' => $entry['notification_name'] ?? '',
                'notification_id'   => $entry['notification_id'] ?? 0,
                'nx_id'             => $entry['nx_id'] ?? 0,
            ];
        }

        return new \WP_REST_Response([
            'entries' => $formatted_entries,
            'total' => $total_items,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ], 200);
    }

    /**
     * Delete feedback entry
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function delete_feedback_entry($request) {
        global $wpdb;

        $entry_id = $request->get_param('id');
        $table_name = $wpdb->prefix . 'nx_entries';

        $result = $wpdb->delete(
            $table_name,
            [
                'entry_id' => $entry_id,
                'source' => $this->id
            ],
            ['%d', '%s']
        );

        if ($result === false) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Failed to delete entry', 'notificationx'),
            ], 500);
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Entry deleted successfully', 'notificationx'),
        ], 200);
    }

    /**
     * Bulk delete feedback entries
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function bulk_delete_feedback_entries($request) {
        global $wpdb;

        $entry_ids = $request->get_param('ids');
        $table_name = $wpdb->prefix . 'nx_entries';

        if (empty($entry_ids) || !is_array($entry_ids)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('No entries selected for deletion', 'notificationx'),
            ], 400);
        }

        // Sanitize entry IDs
        $entry_ids = array_map('absint', $entry_ids);
        $entry_ids = array_filter($entry_ids); // Remove any zero values

        if (empty($entry_ids)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Invalid entry IDs provided', 'notificationx'),
            ], 400);
        }

        // Create placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($entry_ids), '%d'));

        // Prepare the query with source filter
        $query = $wpdb->prepare(
            "DELETE FROM {$table_name} WHERE entry_id IN ({$placeholders}) AND source = %s",
            array_merge($entry_ids, [$this->id])
        );

        $result = $wpdb->query($query);

        if ($result === false) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Failed to delete entries', 'notificationx'),
            ], 500);
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => sprintf(
                /* translators: %d: Number of entries deleted */
                _n('%d entry deleted successfully', '%d entries deleted successfully', $result, 'notificationx'),
                $result
            ),
            'deleted_count' => $result,
        ], 200);
    }

    /**
     * Export feedback entries
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function export_feedback_entries($request) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nx_entries';
        $search = $request->get_param('s') ?: '';
        $notification_id = $request->get_param('notification_id') ?: '';

        // Build WHERE clause
        $where_conditions = ["e.source = %s"];
        $where_values = [$this->id];

        // Add notification filter if provided
        if (!empty($notification_id)) {
            $where_conditions[] = "e.nx_id = %d";
            $where_values[] = intval($notification_id);
        }

        // Add search functionality if provided
        if (!empty($search)) {
            $where_conditions[] = "(e.data LIKE %s OR e.created_at LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where_conditions);

        // Get all entries for export (no pagination)
        $posts_table = $wpdb->prefix . 'nx_posts';
        $query = $wpdb->prepare(
            "SELECT e.entry_id, e.nx_id, e.data, e.created_at, p.title as notification_name
             FROM {$table_name} e
             LEFT JOIN {$posts_table} p ON e.nx_id = p.nx_id
             WHERE {$where_clause}
             ORDER BY e.created_at DESC",
            ...$where_values
        );

        $entries = $wpdb->get_results($query, ARRAY_A);

        if (empty($entries)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('No entries found to export', 'notificationx'),
            ], 404);
        }

        // Generate CSV content
        $csv_data = $this->generate_csv_data($entries);

        // Generate filename
        $filename = 'notificationx-feedback-entries-' . date('Y-m-d-H-i-s') . '.csv';

        return new \WP_REST_Response([
            'success' => true,
            'csv_content' => $csv_data,
            'filename' => $filename,
            'total_entries' => count($entries),
            'message' => sprintf(__('Successfully prepared %d entries for export', 'notificationx'), count($entries))
        ], 200);
    }

    /**
     * Generate CSV data from entries
     *
     * @param array $entries
     * @return string
     */
    private function generate_csv_data($entries) {
        $csv_data = [];

        // CSV Headers
        $csv_data[] = [
            __('No', 'notificationx'),
            __('Date', 'notificationx'),
            __('NotificationX Title', 'notificationx'),
            __('Name', 'notificationx'),
            __('Email Address', 'notificationx'),
            __('Message', 'notificationx'),
            __('IP Address', 'notificationx'),
            __('Theme', 'notificationx'),
        ];

        // Add data rows
        $counter = 1;
        foreach ($entries as $entry) {
            $data = maybe_unserialize($entry['data']);
            $date = new \DateTime($entry['created_at']);

            $csv_data[] = [
                $counter++,
                $date->format('F j, Y'),
                $entry['notification_name'] ?: sprintf(__('Notification #%d', 'notificationx'), $entry['nx_id']),
                $data['name'] ?? '',
                $data['email'] ?? '',
                $data['message'] ?? '',
                $data['ip'] ?? '',
                $data['theme'] ?? '',
            ];
        }

        // Convert array to CSV string
        $csv_content = '';
        foreach ($csv_data as $row) {
            $csv_content .= '"' . implode('","', array_map(function($field) {
                return str_replace('"', '""', $field); // Escape quotes
            }, $row)) . '"' . "\n";
        }

        return $csv_content;
    }
}