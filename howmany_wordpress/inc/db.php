<?php


class HMDatabase {
    protected $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }

    public function load($table, $where=null, $params=null) {
        $statement = $this->prepare("SELECT * FROM $table", $where, $params);
        return $this->db->get_row($statement);
    }

    public function load_all($table, $where=null, $params=null) {
        $statement = $this->prepare("SELECT * FROM $table", $where, $params);
        return $this->db->get_results($statement);
    }

    public function load_all_extended($select, $table, $where=null, $params=null) {
        $statement = $this->prepare("SELECT $select FROM $table", $where, $params);
        return $this->db->get_results($statement);
    }

    public function insert($table, $values, $format=null) {
        return $this->db->insert($table, $values, $format);
    }

    public function query($query, $params=null) {
        $statement = $this->prepare($query, null, $params);
        $this->db->query($statement);
        return $this->db->last_result;
    }

    protected function prepare($query, $where=null, $params=null) {
        if (!$where && !$params) {
            return $query;
        }
        $query = $where ? "$query WHERE $where" : $query;
        $params = $params ? $params : array();
        if (empty($params)) {
            return $query;
        }
        array_unshift($params, $query);
        $statement = call_user_func_array(array($this->db, 'prepare'), $params);
        return $statement;
    }
}


