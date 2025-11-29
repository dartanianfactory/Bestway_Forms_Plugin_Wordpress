<?php
if (!defined('ABSPATH')) exit;

abstract class BestwayForms_Model {
    protected $table_name;
    
    public function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . $this->table_name;
    }
    
    public function create_tables() {}
}