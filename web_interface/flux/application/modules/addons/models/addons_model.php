<?php

class Addons_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    function get_installed_addons()
    {
        return $this->db->query('select * from addons')->result_array();
    }

    function get_addon_by_package($package_name)
    {
        return (array) $this->db->query("select * from addons where package_name='" . $package_name . "'")->first_row();
    }

    function get_addon_files($package_name)
    {
        $result = $this->db->query("Select files from addons where package_name='" . $package_name . "'");
        $encoded = (array) $result->first_row();
        return isset($encoded['files']) ? json_decode($encoded['files'], true) : array();
    }

    function execute_statement($statement)
    {
        $this->db->db_debug = false;
        $this->db->query($statement);
        return array(
            'error_no' => $this->db->_error_number(),
            'error_msg' => $this->db->_error_message(),
            'statement' => $statement
        );
    }

    function execute_statements_in_transaction($sqls)
    {
        $this->db->trans_start();
        $this->db->query('SET autocommit=0');
        foreach ($sqls as $statement) {
            $result = $this->execute_statement($statement);
            if (!empty($result['error_msg'])) {
                $this->db->trans_rollback();
                return array('success' => false) + $result;
            }
        }
        $this->db->trans_commit();
        $this->db->trans_complete();
        return array('success' => true);
    }

    function insert_addon($data)
    {
        return $this->db->insert('addons', $data);
    }
}
