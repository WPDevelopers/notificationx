<?php
/**
 * Shared query/formatting for the entry-reading abilities (list + export).
 *
 * Entries are the submissions collected by NotificationX form notifications —
 * the Popup and Exit Intent forms — and stored in the `nx_entries` table. They
 * are the same rows surfaced on the admin "Feedback Entries" screen and its CSV
 * export, so this trait mirrors that feature exactly: the same two form sources,
 * the same search/notification filters, and the same Free/Pro field split
 * (message on Free; name + email added when Pro is active).
 *
 * @package NotificationX\Abilities\Read
 */

namespace NotificationX\Abilities\Read;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

trait EntriesReader {

    /**
     * The notification sources whose submissions are treated as form entries —
     * identical to the admin Feedback Entries screen.
     *
     * @return string[]
     */
    protected function form_sources() {
        return array( 'popup_notification', 'exit_intent_custom' );
    }

    /**
     * Whether Pro is active (unlocks the name + email fields, exactly like the
     * admin CSV export's `is_pro()` branch).
     *
     * @return bool
     */
    protected function entries_is_pro() {
        return class_exists( '\NotificationXPro\NotificationX' ) && \NotificationX\NotificationX::is_pro();
    }

    /**
     * Query form entries with optional notification/source/search filters and
     * optional pagination.
     *
     * @param array    $args   nx_id, source, search.
     * @param int|null $limit  Max rows (null = no limit, for export).
     * @param int      $offset Row offset.
     * @return array{0: array[], 1: int} [rows, total_matching]
     */
    protected function query_entries( $args, $limit = null, $offset = 0 ) {
        global $wpdb;

        $entries_table = $wpdb->prefix . 'nx_entries';
        $posts_table   = $wpdb->prefix . 'nx_posts';

        // Default to both form sources; allow narrowing to one of them.
        $sources = $this->form_sources();
        if ( ! empty( $args['source'] ) && in_array( $args['source'], $sources, true ) ) {
            $sources = array( $args['source'] );
        }

        $placeholders = implode( ',', array_fill( 0, count( $sources ), '%s' ) );
        $where        = array( "e.source IN ({$placeholders})" );
        $values       = $sources;

        if ( ! empty( $args['nx_id'] ) ) {
            $where[]  = 'e.nx_id = %d';
            $values[] = (int) $args['nx_id'];
        }
        if ( isset( $args['search'] ) && '' !== $args['search'] ) {
            $where[]  = '(e.data LIKE %s OR e.created_at LIKE %s)';
            $like     = '%' . $wpdb->esc_like( (string) $args['search'] ) . '%';
            $values[] = $like;
            $values[] = $like;
        }

        $where_sql = implode( ' AND ', $where );

        // Total matching (before pagination).
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Prepared below; only $wpdb->prefix table names are interpolated.
        $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$entries_table} e WHERE {$where_sql}", ...$values ) );

        $limit_sql   = '';
        $page_values = $values;
        if ( null !== $limit ) {
            $limit_sql     = ' LIMIT %d OFFSET %d';
            $page_values[] = (int) $limit;
            $page_values[] = max( 0, (int) $offset );
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Prepared below; only $wpdb->prefix table names are interpolated.
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.entry_id, e.nx_id, e.source, e.data, e.created_at, p.title AS notification_name
                 FROM {$entries_table} e
                 LEFT JOIN {$posts_table} p ON e.nx_id = p.nx_id
                 WHERE {$where_sql}
                 ORDER BY e.created_at DESC{$limit_sql}",
                ...$page_values
            ),
            ARRAY_A
        );

        return array( is_array( $rows ) ? $rows : array(), $total );
    }

    /**
     * Flatten a raw entry row into the response shape. `message` is always
     * present; `name` and `email` are included only when Pro is active.
     *
     * @param array $row    Raw DB row (entry_id, nx_id, source, data, created_at, notification_name).
     * @param bool  $is_pro Whether to expose the Pro contact fields.
     * @return array
     */
    protected function map_entry_row( $row, $is_pro ) {
        $data = maybe_unserialize( isset( $row['data'] ) ? $row['data'] : '' );
        if ( ! is_array( $data ) ) {
            $data = array();
        }

        $nx_id = isset( $row['nx_id'] ) ? (int) $row['nx_id'] : 0;
        $title = ! empty( $row['notification_name'] )
            ? $row['notification_name']
            /* translators: %d: notification id. */
            : sprintf( __( 'Notification #%d', 'notificationx' ), $nx_id );

        $entry = array(
            'entry_id'     => isset( $row['entry_id'] ) ? (int) $row['entry_id'] : 0,
            'nx_id'        => $nx_id,
            'notification' => $title,
            'source'       => isset( $row['source'] ) ? $row['source'] : '',
            'created_at'   => isset( $row['created_at'] ) ? $row['created_at'] : '',
        );

        if ( $is_pro ) {
            $entry['name']  = isset( $data['name'] ) ? $data['name'] : '';
            $entry['email'] = isset( $data['email'] ) ? $data['email'] : '';
        }

        $entry['message'] = isset( $data['message'] ) ? $data['message'] : '';

        return $entry;
    }
}
