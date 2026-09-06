<?php
/**
 * Ability: export form entries (Popup / Exit Intent submissions) as CSV.
 *
 * @package NotificationX\Abilities\Read
 */

namespace NotificationX\Abilities\Read;

use NotificationX\Abilities\AbilityBase;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Exports NotificationX form entries as CSV, byte-for-byte matching the admin
 * "Feedback Entries" export: columns No, Date, Title and Message on Free, with
 * Name and Email Address added when Pro is active.
 *
 * A single response is capped to keep the payload reasonable; the full match
 * count is always reported so a truncated export is never mistaken for a
 * complete one (page through with list-entries, or narrow with nx_id/search).
 * Entries are personal data; this ability is administrator-only.
 */
class ExportEntries extends AbilityBase {

    use EntriesReader;

    /**
     * Maximum rows returned in one CSV export.
     */
    const MAX_ROWS = 1000;

    protected $id          = 'notificationx/export-entries';
    protected $label       = 'Export form entries (CSV)';
    protected $description = 'Export NotificationX Popup and Exit Intent form entries as CSV, matching the admin Feedback Entries export (Date, Title, Message on Free; Name and Email added on Pro). Supports nx_id, source and search filters. Note: entries contain personal data; large result sets are capped and the full count is reported.';

    public function input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'nx_id'  => array(
                    'type'        => 'integer',
                    'description' => 'Only entries for this notification id.',
                ),
                'source' => array(
                    'type'        => 'string',
                    'description' => 'Limit to one form source.',
                    'enum'        => array( 'popup_notification', 'exit_intent_custom' ),
                ),
                'search' => array(
                    'type'        => 'string',
                    'description' => 'Match entries whose data or date contains this text.',
                ),
            ),
        );
    }

    public function output_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'filename'       => array( 'type' => 'string' ),
                'csv_content'    => array( 'type' => 'string' ),
                'total_entries'  => array( 'type' => 'integer' ),
                'exported_count' => array( 'type' => 'integer' ),
                'truncated'      => array( 'type' => 'boolean' ),
                'is_pro'         => array( 'type' => 'boolean' ),
            ),
        );
    }

    public function execute( $input ) {
        list( $rows, $total ) = $this->query_entries( $input, self::MAX_ROWS, 0 );

        if ( empty( $rows ) ) {
            return new \WP_Error(
                'nx_mcp_no_entries',
                __( 'No entries found to export.', 'notificationx' ),
                array( 'status' => 404 )
            );
        }

        $is_pro = $this->entries_is_pro();

        // Header row — same columns and order as the admin CSV export.
        $headers = array(
            __( 'No', 'notificationx' ),
            __( 'Date', 'notificationx' ),
            __( 'NotificationX Title', 'notificationx' ),
        );
        if ( $is_pro ) {
            $headers[] = __( 'Name', 'notificationx' );
            $headers[] = __( 'Email Address', 'notificationx' );
        }
        $headers[] = __( 'Message', 'notificationx' );

        $table   = array( $headers );
        $counter = 1;
        foreach ( $rows as $row ) {
            $entry = $this->map_entry_row( $row, $is_pro );

            $date = $entry['created_at'];
            $ts   = strtotime( (string) $date );
            if ( $ts ) {
                $date = gmdate( 'F j, Y', $ts );
            }

            $line = array(
                $counter++,
                $date,
                $entry['notification'],
            );
            if ( $is_pro ) {
                $line[] = $entry['name'];
                $line[] = $entry['email'];
            }
            $line[] = $entry['message'];

            $table[] = $line;
        }

        // Serialise to CSV — quote every field, escape embedded quotes.
        $csv = '';
        foreach ( $table as $line ) {
            $csv .= '"' . implode(
                '","',
                array_map(
                    function ( $field ) {
                        return str_replace( '"', '""', (string) $field );
                    },
                    $line
                )
            ) . '"' . "\n";
        }

        return array(
            'filename'       => 'notificationx-feedback-entries-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv',
            'csv_content'    => $csv,
            'total_entries'  => $total,
            'exported_count' => count( $rows ),
            'truncated'      => $total > count( $rows ),
            'is_pro'         => $is_pro,
        );
    }
}
