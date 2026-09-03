<?php
/**
 * Ability: list form entries (Popup / Exit Intent submissions).
 *
 * @package NotificationX\Abilities\Read
 */

namespace NotificationX\Abilities\Read;

use NotificationX\Abilities\AbilityBase;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Lists the submissions collected by NotificationX form notifications (the
 * Popup and Exit Intent forms) — the same data as the admin "Feedback Entries"
 * screen. Each entry always includes its message; the submitter's name and
 * email are included only when NotificationX Pro is active (mirroring the
 * admin CSV export).
 *
 * These entries are personal data (messages, and on Pro, names and email
 * addresses). The ability is administrator-only like every NotificationX
 * ability, and the MCP server additionally refuses non-admin grants.
 */
class ListEntries extends AbilityBase {

    use EntriesReader;

    protected $id          = 'notificationx/list-entries';
    protected $label       = 'List form entries';
    protected $description = 'List submissions collected by NotificationX Popup and Exit Intent forms (the "Feedback Entries"). Each entry includes its message; on Pro the submitter name and email are also returned. Supports filtering by notification (nx_id), source and a search term, with pagination. Note: entries contain personal data.';

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
                'limit'  => array(
                    'type'        => 'integer',
                    'description' => 'Max entries to return (default 25, max 100).',
                ),
                'offset' => array(
                    'type'        => 'integer',
                    'description' => 'Number of entries to skip (for pagination).',
                ),
            ),
        );
    }

    public function output_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'total'   => array( 'type' => 'integer' ),
                'count'   => array( 'type' => 'integer' ),
                'offset'  => array( 'type' => 'integer' ),
                'is_pro'  => array( 'type' => 'boolean' ),
                'entries' => array( 'type' => 'array' ),
            ),
        );
    }

    public function execute( $input ) {
        $limit  = isset( $input['limit'] ) ? (int) $input['limit'] : 25;
        $limit  = max( 1, min( 100, $limit ) );
        $offset = isset( $input['offset'] ) ? max( 0, (int) $input['offset'] ) : 0;

        list( $rows, $total ) = $this->query_entries( $input, $limit, $offset );

        $is_pro  = $this->entries_is_pro();
        $entries = array();
        foreach ( $rows as $row ) {
            $entries[] = $this->map_entry_row( $row, $is_pro );
        }

        return array(
            'total'   => $total,
            'count'   => count( $entries ),
            'offset'  => $offset,
            'is_pro'  => $is_pro,
            'entries' => $entries,
        );
    }
}
