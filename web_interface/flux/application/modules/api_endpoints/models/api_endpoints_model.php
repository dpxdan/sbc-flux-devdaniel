<?php

// ##############################################################################
// Flux Telecom - Unindo pessoas e negócios
//
// Copyright (C) 2021 Flux Telecom
// Daniel Paixao <daniel@flux.net.br>
// FluxSBC Version 4.2 and above
// License https://www.gnu.org/licenses/agpl-3.0.html
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program. If not, see <http://www.gnu.org/licenses/>.
// ##############################################################################
class api_endpoints_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
    }

    function getapi_endpoints_list($flag, $start = 0, $limit = 0)
    {
        $this->db_model->build_search('api_endpoints_list_search');
        $accountdata = $this->session->userdata('accountinfo');

        if ($accountdata['type'] == '-1' || $accountdata['type'] == '2') {
            $where = array();
        } else {
            $where = array(
                "status" => "0"
            );
        }
        if ($flag) {
            $query = $this->db_model->select("*", "api_endpoints", $where, "id", "ASC", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("*", "api_endpoints", $where);
        }
        return $query;
    }

    function getpartners_endpoints_list($flag, $start = 0, $limit = 0)
    {
        $this->db_model->build_search('partners_endpoints_list_search');
        $accountdata = $this->session->userdata('accountinfo');
    
        if ($accountdata['type'] == '-1' || $accountdata['type'] == '2') {
            $where = array();
        } else {
            $where = array(
                "status" => "0"
            );
        }
        if ($flag) {
            $query = $this->db_model->select("*", "endpoints", $where, "nome", "ASC", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("*", "endpoints", $where);
        }
        return $query;
    }

    function getpartners_list($flag, $start = 0, $limit = 0)
    {
        $this->db_model->build_search('partners_list_search');
        $accountdata = $this->session->userdata('accountinfo');
    
        if ($accountdata['type'] == '-1' || $accountdata['type'] == '2') {
            $where = array();
        } else {
            $where = array(
                "status" => "0"
            );
        }
        if ($flag) {
            $query = $this->db_model->select("*", "api_partners", $where, "partner_name", "ASC", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("*", "api_partners", $where);
        }
        return $query;
    }

    function add_api_endpoints($add_array)
    {
        unset($add_array["action"]);
        $add_array['creation_date'] = gmdate('Y-m-d H:i:s');
//        $add_array['apply_on_endpoints'] = isset($add_array['apply_on_endpoints'])?implode(",",$add_array['apply_on_endpoints']):"";
        $add_array['last_modified_date'] = gmdate('Y-m-d H:i:s');
        $this->db->insert("api_endpoints", $add_array);
        return true;
    }

    function edit_api_endpoints($data, $id)
    {
        unset($data["action"]);
        $data['last_modified_date'] = gmdate('Y-m-d H:i:s');
        $this->db->where("id", $id);
        $this->db->update("api_endpoints", $data);
    }

    function add_partners_endpoints($add_array)
    {
        unset($add_array["action"]);
        $add_array['creation_date'] = gmdate('Y-m-d H:i:s');
        $add_array['last_modified_date'] = gmdate('Y-m-d H:i:s');
        $this->db->insert("endpoints", $add_array);
        return true;
    }

    function edit_partners_endpoints($data, $id)
    {
        unset($data["action"]);
        $data['last_modified_date'] = gmdate('Y-m-d H:i:s');
        $this->db->where("id", $id);
        $this->db->update("endpoints", $data);
    }

    function remove_api_endpoints($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('api_endpoints');
        return true;
    }

    function add_partners($add_array)
    {
        unset($add_array["action"]);
        $add_array['creation_date'] = gmdate('Y-m-d H:i:s');
        $add_array['last_modified_date'] = gmdate('Y-m-d H:i:s');
        $this->db->insert("api_partners", $add_array);
        return true;
    }
    
    function edit_partners($data, $id)
    {
        unset($data["action"]);
        $data['last_modified_date'] = gmdate('Y-m-d H:i:s');
        $this->db->where("id", $id);
        $this->db->update("api_partners", $data);
    }
    
    function remove_partners($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('api_partners');
        return true;
    }

    function bulk_insert_api_endpoints($field_value)
    {
        $this->db->insert_batch('api_endpoints', $field_value);
        $affected_row = $this->db->affected_rows();
        return $affected_row;
    }
}
