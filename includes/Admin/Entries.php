<?php

/**
 * Extension Factory
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Admin;

use NotificationX\Core\Database;
use NotificationX\Core\Helper;
use NotificationX\GetInstance;

/**
 * @method static Entries get_instance($args = null)
 */
class Entries {
    /**
     * Instance of Entries
     *
     * @var Entries
     */
    use GetInstance;

    protected $wpdb;
    protected $count = [];
    public $format = [
        'entry_id'    => '%d',
        'nx_id'       => '%d',
        'source'      => '%s',
        'entry_key'   => '%s',
        'data'        => '%s',
        'created_at'  => '%s',
        'updated_at'  => '%s',
    ];

    /**
     * Initially Invoked when initialized.
     * @hook init
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function count($source, $col = 'source'){
        if(empty($this->count)){
            $this->count = Database::get_instance()->get_source_count(Database::$table_entries, $col, [$col => $source]);
        }
        if(!empty($this->count[$source])){
            return $this->count[$source];
        }
        elseif(!empty($source)){
            return 0;
        }
        return $this->count;
    }


    /**
     * Recursively sanitize the user-controlled `data` payload of an entry
     * before it is stored. Entry data frequently originates from low-trust,
     * even unauthenticated sources — public form submissions (Gravity Forms,
     * CF7, FluentForm, WPForms, Ninja Forms, WeForms, Formidable, …) and the
     * Zapier / IFTTT REST ingest — and is later rendered on the frontend. The
     * JS renderer only escapes string-typed values, so an array-wrapped value
     * (e.g. `name => ['<img src=x onerror=alert(1)>']`) is coerced back to its
     * raw string and injected via `dangerouslySetInnerHTML` — a stored XSS
     * (Patchstack, NotificationX Pro <= 3.1.3). insert_entry()/insert_entries()
     * are the single storage chokepoint every extension (Free and Pro) funnels
     * through, so sanitizing every string leaf here closes that class of issue
     * regardless of ingest path. Non-string scalars (ints, bools, timestamps)
     * are left untouched so downstream typing is preserved; only strings can
     * carry markup.
     *
     * @param mixed $data
     * @return mixed
     */
    public function sanitize_entry_data($data) {
        if (is_array($data)) {
            array_walk_recursive($data, function (&$val) {
                if (is_string($val)) {
                    $val = sanitize_text_field($val);
                }
            });
        } elseif (is_string($data)) {
            $data = sanitize_text_field($data);
        }
        return $data;
    }

    public function insert_entry($entry) {
        if(empty($entry['data'])){
            return false;
        }
        $entry['data'] = $this->sanitize_entry_data($entry['data']);
        $timestamp = !empty($entry['data']['timestamp']) ? $entry['data']['timestamp'] : time();
        if(empty($entry['created_at'])){
            $entry['created_at'] = Helper::mysql_time($timestamp);
        }
        if(empty($entry['updated_at'])){
            $entry['updated_at'] = Helper::mysql_time($timestamp);
        }
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
        $entry  = apply_filters('nx_insert_entry', $entry);
        $result = Database::get_instance()->insert_post(Database::$table_entries, $entry, $this->format);
        if ( $result ) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
            do_action( 'nx_after_entry_inserted', $entry );
        }
        return $result;
    }

    public function insert_entries($entries) {
        foreach ($entries as $key => $entry) {
            if(empty($entry['data'])){
                unset($entries[$key]);
                continue;
            }
            $entry['data'] = $this->sanitize_entry_data($entry['data']);
            $timestamp = !empty($entry['data']['timestamp']) ? $entry['data']['timestamp'] : time();
            if(empty($entry['created_at'])){
                $entry['created_at'] = Helper::mysql_time($timestamp);
            }
            if(empty($entry['updated_at'])){
                $entry['updated_at'] = Helper::mysql_time($timestamp);
            }
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
            $entries[$key] = apply_filters('nx_insert_entry', $entry);
        }
        return Database::get_instance()->insert_posts(Database::$table_entries, $entries, $this->format);
    }

    public function get_entries($where__or_nx_id = [], $select = "*", $join_table = '', $group_by_col = '', $data_in_entry = false) {
        if (is_int($where__or_nx_id)) {
            $where__or_nx_id = ['nx_id' => $where__or_nx_id];
        }
        $entries = Database::get_instance()->get_posts(Database::$table_entries, $select, $where__or_nx_id, $join_table, $group_by_col, '', 'ORDER BY `created_at` DESC');
        if ($data_in_entry) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
            $entries = apply_filters('nx_get_entries', $entries);
            return $entries;
        }
        foreach ($entries as $key => $value) {
            if (!empty($value['data'])) {
                $value = array_merge($value['data'], $value);
                unset($value['data']);
            }
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
            $entries[$key] = apply_filters('nx_get_entry', $value);
        }
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reviewed for the NotificationX codebase: acceptable in this context.
        $entries = apply_filters('nx_get_entries', $entries);
        return $entries;
    }

    public function delete_entries($where__or_nx_id, $limit = 0) {
        if (!is_array($where__or_nx_id)) {
            $where__or_nx_id = ['nx_id' => $where__or_nx_id];
        }
        $results = Database::get_instance()->delete_posts(Database::$table_entries, $where__or_nx_id, $limit);
        // @todo add action.
        return $results;
    }

}
