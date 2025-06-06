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


    public function insert_entry($entry) {
        if(empty($entry['data'])){
            return false;
        }
        $timestamp = !empty($entry['data']['timestamp']) ? $entry['data']['timestamp'] : time();
        if(empty($entry['created_at'])){
            $entry['created_at'] = Helper::mysql_time($timestamp);
        }
        if(empty($entry['updated_at'])){
            $entry['updated_at'] = Helper::mysql_time($timestamp);
        }
        $entry = apply_filters('nx_insert_entry', $entry);
        return Database::get_instance()->insert_post(Database::$table_entries, $entry, $this->format);
    }

    public function insert_entries($entries) {
        foreach ($entries as $key => $entry) {
            if(empty($entry['data'])){
                unset($entries[$key]);
                continue;
            }
            $timestamp = !empty($entry['data']['timestamp']) ? $entry['data']['timestamp'] : time();
            if(empty($entry['created_at'])){
                $entry['created_at'] = Helper::mysql_time($timestamp);
            }
            if(empty($entry['updated_at'])){
                $entry['updated_at'] = Helper::mysql_time($timestamp);
            }
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
            $entries = apply_filters('nx_get_entries', $entries);
            return $entries;
        }
        foreach ($entries as $key => $value) {
            if (!empty($value['data'])) {
                $value = array_merge($value['data'], $value);
                unset($value['data']);
            }
            $entries[$key] = apply_filters('nx_get_entry', $value);
        }
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
