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
class IPMAP_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
    }

    function ipmap_list($flag, $start = 0, $limit = 0)
    {
        $accountinfo = $this->session->userdata('accountinfo');
        if ($this->session->userdata('logintype') == 1 || $this->session->userdata('logintype') == 5) {
            $qry = $this->db_model->getselect('id', 'accounts', array(
                'reseller_id' => $accountinfo['id']
            ));
            $result = $qry->result_array();
            foreach ($result as $value1) {
                $value[] = $value1['id'];
            }
            if (! empty($value)) {
                $this->db->where_in('accountid', $value);
            } else {
                $this->db->where_in('accountid', '0');
            }
        } else {

            $qry = $this->db_model->getselect('id', 'accounts', ''
            );
            $result = $qry->result_array();

            foreach ($result as $value1) {
                $value[] = $value1['id'];
            }
            if (! empty($value)) {
                $this->db->where_in('accountid', $value);
            } else {
                $this->db->where_in('accountid', '0');
            }
        }
        $this->db_model->build_search('ipmap_list_search');
        if ($accountinfo['type'] == '0') {
            $where = array(
                'accountid' => $accountinfo['id']
            );
        } else {
            $where = '';
        }
        if ($flag) {
            $query = $this->db_model->select("*", "ip_map", $where, "id", "ASC", $limit, $start);
        } else {
            $query = $this->db_model->countQuery("*", "ip_map", $where);
        }
        return $query;
    }

    function add_ipmap($add_array)
    {
        $account_data = $this->session->userdata("accountinfo");
        $reseller_id = isset($add_array['reseller_id']) ? $add_array['reseller_id'] : $account_data['id'];
        if ($account_data['type'] == '0') {
            $add_array['accountid'] = $account_data['id'];
        }
        $data = array(
            'created_date' => gmdate('Y-m-d H:i:s'),
            'last_modified_date' => gmdate('Y-m-d H:i:s'),
            'name' => $add_array['name'],
            'ip' => $add_array['ip'],
            'prefix' => $add_array['prefix'],
            'accountid' => $add_array['accountid'],
            'reseller_id' => $reseller_id,
            'status' => $add_array['status'],
            'context' => 'default'
        );
        $this->db->insert("ip_map", $data);
        return $this->db->insert_id();
    }

    function edit_ipmap($add_array, $id)
    {
        $account_data = $this->session->userdata("accountinfo");
        if ($account_data['type'] == '0') {
            $add_array['accountid'] = $account_data['id'];
        }
        $data = array(
            'last_modified_date' => gmdate('Y-m-d H:i:s'),
            'name' => $add_array['name'],
            'ip' => $add_array['ip'],
            'prefix' => $add_array['prefix'],
            'accountid' => $add_array['accountid'],
            'status' => $add_array['status'],
            'context' => 'default'
        );
        $this->db->where("id", $id);
        return $this->db->update("ip_map", $data);
    }

    function remove_ipmap($id)
    {
        $this->db->where("id", $id);
        $this->db->delete("ip_map");
        return true;
    }

    function find_duplicate_ipmap($prefix, $ip, $exclude_id = null)
    {
        $this->db->select('prefix,ip');
        $this->db->where(array(
            'prefix' => $prefix,
            'ip' => $ip
        ));
        if ($exclude_id !== null && $exclude_id !== '') {
            $this->db->where('id <>', $exclude_id);
        }
        return (array) $this->db->get('ip_map')->first_row();
    }

    function normalize_ids($selected_ids)
    {
        return array_values(array_filter(array_map('intval', array_map('trim', explode(',', $selected_ids)))));
    }

    function delete_multiple_ipmaps($ids)
    {
        $id_list = $this->normalize_ids($ids);
        if (empty($id_list)) {
            return false;
        }
        $this->db->where_in('id', $id_list);
        return $this->db->delete("ip_map");
    }

    function get_reseller_customer_accounts($reseller_id)
    {
        $query = $this->db->get_where('accounts', array(
            "reseller_id" => $reseller_id,
            "type" => "GLOBAL"
        ));
        return $query->num_rows() > 0 ? $query->result_array() : array();
    }

}
